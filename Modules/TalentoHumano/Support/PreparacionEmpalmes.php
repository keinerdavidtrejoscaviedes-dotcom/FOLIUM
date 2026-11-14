<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\EmpalmeParticipante;
use Modules\TalentoHumano\Entities\Fase;

/**
 * Prepara los empalmes de la fase entrante: quién entrega y quién recibe cada cargo (HU-029A, RF-013, RN-056 a RN-060).
 *
 * - Gerentes, gestores y líderes entregan al entrante de su mismo cargo; si el cargo no está activo (o no tiene entrante),
 *   al de su jefe inmediato, subiendo por la jerarquía hasta encontrar uno.
 * - Los equipos entregan siempre al líder del que dependen o, si no hay líder activo o dependen de un gestor, al gestor.
 * - Varios salientes o varios entrantes del mismo empalme (equipos, los dos Gestores de TH) forman un empalme grupal.
 * - Un cargo sin saliente recibe un empalme con inventario inicial, que valida el Gestor (HU-033); si su inventario lo
 *   custodia otro cargo, ese cargo se lo entrega (cargo que vuelve a activarse, RN-060).
 *
 * El empalme pertenece a la fase entrante; los salientes son las asignaciones vigentes de la fase anterior.
 */
class PreparacionEmpalmes
{
    /**
     * Fase saliente: la anterior a la entrante que todavía tiene aprendices ocupando cargos.
     */
    public static function faseSaliente(Fase $entrante): ?Fase
    {
        return Fase::where('numero', '<', $entrante->numero)
            ->whereExists(fn ($q) => $q->from('th_asignaciones')->whereColumn('th_asignaciones.fase_id', 'th_fases.id')
                ->whereIn('th_asignaciones.estado', ['vigente', 'suspendida']))
            ->orderByDesc('numero')->first();
    }

    /**
     * Crea los empalmes que falten (se puede repetir sin duplicar).
     *
     * @return array{creados: int, sin_receptor: array<int, string>}
     */
    public static function preparar(Fase $entrante): array
    {
        $saliente = self::faseSaliente($entrante);
        $cargos = Cargo::get(['id', 'nombre', 'nivel', 'cargo_superior_id'])->keyBy('id');
        $activados = DB::table('th_cargos_activados')->where('fase_id', $entrante->id)->pluck('cargo_id')->flip();
        $entrantes = Asignacion::where('fase_id', $entrante->id)->whereIn('estado', ['asignada', 'vigente'])->get()->groupBy('cargo_id');
        $salientes = $saliente
            ? Asignacion::where('fase_id', $saliente->id)->whereIn('estado', ['vigente', 'suspendida'])->get()->groupBy('cargo_id')
            : collect();

        // Cargo que recibe: el primero, subiendo desde $desde, que esté activado y tenga entrante
        $receptor = function (?int $desde) use ($cargos, $activados, $entrantes) {
            for ($id = $desde, $vueltas = 0; $id && $vueltas < 10; $id = $cargos[$id]->cargo_superior_id ?? null, $vueltas++) {
                if (isset($activados[$id]) && isset($entrantes[$id])) {
                    return $id;
                }
            }

            return null;
        };

        $creados = 0;
        $sinReceptor = [];

        DB::transaction(function () use ($entrante, $cargos, $activados, $entrantes, $salientes, $receptor, &$creados, &$sinReceptor) {
            // 1. Entregas de los salientes (escenarios 1 a 6)
            foreach ($salientes as $cargoId => $grupo) {
                $cargo = $cargos[$cargoId] ?? null;
                if (!$cargo) {
                    continue;
                }
                $receptorId = $receptor($cargo->nivel === 'Equipo' ? $cargo->cargo_superior_id : $cargo->id);
                if (!$receptorId) {
                    $sinReceptor[] = $cargo->nombre;
                    continue;
                }
                $creados += self::crear($entrante, $cargoId, $receptorId, $grupo, $entrantes[$receptorId]);
            }

            // 2. Cargos que nadie entrega: inventario custodiado por otro cargo (escenario 8) o inventario inicial (7, 10 y 11)
            foreach ($entrantes as $cargoId => $grupo) {
                $cargo = $cargos[$cargoId] ?? null;
                if (!$cargo || $cargo->nivel === 'Equipo' || !isset($activados[$cargoId]) || isset($salientes[$cargoId])) {
                    continue;
                }
                $custodio = DB::table('th_inventario_cargo')->where('cargo_id', $cargoId)->where('custodio_cargo_id', '!=', $cargoId)->value('custodio_cargo_id');
                $creados += self::crear($entrante, $cargoId, $cargoId, $custodio ? ($salientes[$custodio] ?? collect()) : collect(), $grupo);
            }
        });

        return ['creados' => $creados, 'sin_receptor' => array_values(array_unique($sinReceptor))];
    }

    /**
     * Salientes que aún no tienen empalme de entrega en la fase entrante (HU-029B esc. 2, RN-058).
     */
    public static function salientesSinEmpalme(Fase $entrante): Collection
    {
        $saliente = self::faseSaliente($entrante);
        if (!$saliente) {
            return collect();
        }

        $conEmpalme = EmpalmeParticipante::where('papel', 'saliente')
            ->whereHas('empalme', fn ($q) => $q->where('fase_id', $entrante->id))->pluck('asignacion_id');

        return Asignacion::with(['persona', 'cargo'])->where('fase_id', $saliente->id)
            ->whereIn('estado', ['vigente', 'suspendida'])->whereNotIn('id', $conEmpalme)->get();
    }

    /**
     * Crea el empalme (si no existe) con sus participantes, su acta con la plantilla del área y el inventario precargado.
     * Devuelve 1 si lo creó y 0 si ya existía.
     */
    private static function crear(Fase $fase, int $cargoId, int $receptorId, Collection $salientes, Collection $receptores): int
    {
        $grupal = $salientes->count() > 1 || $receptores->count() > 1;
        $empalme = Empalme::firstOrCreate(
            ['fase_id' => $fase->id, 'cargo_id' => $cargoId, 'cargo_receptor_id' => $receptorId],
            ['tipo' => $grupal ? 'grupal' : 'individual', 'estado' => 'pendiente']
        );

        foreach ([['saliente', $salientes], ['receptor', $receptores]] as [$papel, $asignaciones]) {
            foreach ($asignaciones as $asignacion) {
                EmpalmeParticipante::firstOrCreate(['empalme_id' => $empalme->id, 'asignacion_id' => $asignacion->id], ['papel' => $papel]);
            }
        }

        // Acta con la plantilla del área (o la general) e inventario del cargo o lista base (escenarios 9 a 11)
        $empalme->load(['cargo', 'fase']);
        ActaRecibimiento::asegurar($empalme);
        ActaRecibimiento::precargarInventario($empalme);

        if ($empalme->wasRecentlyCreated) {
            Auditoria::registrar('empalme_preparado', $empalme, [], [
                'cargo' => $empalme->cargo?->nombre,
                'receptor' => Cargo::find($receptorId)?->nombre,
                'tipo' => $empalme->tipo,
                'salientes' => $salientes->count(),
                'receptores' => $receptores->count(),
            ]);
        }

        return $empalme->wasRecentlyCreated ? 1 : 0;
    }
}
