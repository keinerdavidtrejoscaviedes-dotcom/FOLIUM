<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\PlantillaActa;

/**
 * Acta de recibimiento del cargo de un empalme (RF-013, RF-021): plantilla del área, inventario precargado,
 * firmantes requeridos y estado de las firmas. La usan el Aprendiz y la pantalla de empalmes.
 */
class ActaRecibimiento
{
    /** Roles con los que firman los aprendices del empalme (th_firmas.rol_firmante). */
    public const ROL_SALIENTE = 'Aprendiz saliente';
    public const ROL_RECEPTOR = 'Receptor del cargo';

    /**
     * Plantilla vigente del área del cargo o, si el área no tiene, la plantilla general (RF-021).
     */
    public static function plantilla(Empalme $empalme): ?PlantillaActa
    {
        $areaId = $empalme->cargo?->area_id;
        $vigente = fn (?int $area) => PlantillaActa::deLaMismaArea($area)->where('estado', 'vigente')->latest('version')->first();

        return ($areaId ? $vigente($areaId) : null) ?? $vigente(null);
    }

    /**
     * Firmantes que exige la plantilla (saliente, receptor, instructor_area, jefe_inmediato).
     */
    public static function firmantes(Empalme $empalme): array
    {
        return self::plantilla($empalme)?->firmantes ?: PlantillaActa::BASE['firmantes'];
    }

    /**
     * Genera el acta con la plantilla del área si aún no existe (RN-026).
     */
    public static function asegurar(Empalme $empalme): Acta
    {
        if ($empalme->acta) {
            return $empalme->acta;
        }

        $acta = Acta::create([
            'empalme_id' => $empalme->id,
            'fase_id' => $empalme->fase_id,
            'plantilla_id' => self::plantilla($empalme)?->id,
            'tipo' => 'recibimiento',
            'contenido' => [
                'codigo' => 'ACT-REC-F' . ($empalme->fase?->numero ?? $empalme->fase_id) . '-' . str_pad($empalme->id, 3, '0', STR_PAD_LEFT),
                'cargo' => $empalme->cargo?->nombre,
                'fecha_reunion' => now()->toDateString(),
            ],
            'estado' => 'borrador',
        ]);
        $empalme->setRelation('acta', $acta);

        return $acta;
    }

    /**
     * Precarga el inventario del empalme con el último inventario del cargo o, si no tiene, con los implementos
     * base de la plantilla del área (RN-061). No hace nada si el empalme ya tiene ítems.
     */
    public static function precargarInventario(Empalme $empalme): void
    {
        if ($empalme->inventarios()->exists()) {
            return;
        }

        $items = DB::table('th_inventario_cargo')->where('cargo_id', $empalme->cargo_id)->orderBy('id')->get(['item', 'cantidad', 'estado'])
            ->map(fn ($i) => (array) $i);
        if ($items->isEmpty()) {
            $items = collect(self::plantilla($empalme)?->items_base ?? [])
                ->map(fn ($i) => ['item' => $i['item'], 'cantidad' => $i['cantidad'], 'estado' => null]);
        }

        foreach ($items as $item) {
            InventarioEmpalme::create($item + ['empalme_id' => $empalme->id, 'verificado' => false]);
        }
    }

    /**
     * Personas que deben firmar como aprendices: todos los salientes y los receptores del empalme (RN-059).
     *
     * @return array<int, string> person_id => rol con el que firma
     */
    public static function aprendicesFirmantes(Empalme $empalme): array
    {
        $empalme->loadMissing('participantes.asignacion');
        $requeridos = self::firmantes($empalme);
        $firmantes = [];
        foreach ($empalme->participantes as $participante) {
            $personId = $participante->asignacion?->person_id;
            if ($personId && in_array($participante->papel, $requeridos)) {
                $firmantes[$personId] = $participante->papel === 'saliente' ? self::ROL_SALIENTE : self::ROL_RECEPTOR;
            }
        }

        return $firmantes;
    }

    /**
     * Actualiza el estado del acta y del empalme después de una firma (HU-057, esc. 3 y 4).
     * Devuelve 'firmada', 'faltan_instructores' o 'pendiente'.
     */
    public static function actualizarEstado(Empalme $empalme, Acta $acta): string
    {
        $firmas = $acta->firmas()->get();
        $aprendices = self::aprendicesFirmantes($empalme);
        $faltanAprendices = array_diff(array_keys($aprendices), $firmas->pluck('person_id')->all());

        // Instructor del área o jefe inmediato: los firma quien no es aprendiz del empalme
        $exigeOtros = array_intersect(self::firmantes($empalme), ['instructor_area', 'jefe_inmediato']);
        $firmaronOtros = $firmas->whereNotIn('person_id', array_keys($aprendices))->isNotEmpty();

        if (!$faltanAprendices && (!$exigeOtros || $firmaronOtros)) {
            $acta->update(['estado' => 'firmada', 'fecha_publicacion' => $acta->fecha_publicacion ?? now()]);
            $empalme->update(['estado' => 'firmado']);

            return 'firmada';
        }

        if ($empalme->estado === 'pendiente') {
            $empalme->update(['estado' => 'en_curso']);
        }

        return $faltanAprendices ? 'pendiente' : 'faltan_instructores';
    }

    /**
     * ¿Diligenciaron el Formulario de Satisfacción todos los aprendices del empalme? (RN-027)
     */
    public static function satisfaccionCompleta(Empalme $empalme): bool
    {
        $personas = array_keys(self::aprendicesFirmantes($empalme));
        $respondieron = DB::table('th_satisfaccion_empalme')->where('empalme_id', $empalme->id)->pluck('person_id')->all();

        return $personas && !array_diff($personas, $respondieron);
    }
}
