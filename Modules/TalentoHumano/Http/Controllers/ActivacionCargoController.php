<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Activación de los cargos que operarán en la fase (HU-019, RF-001).
 *
 * La propuesta sale de los programas de las fichas de la fase (RN-037) y siempre incluye los cargos
 * de nivel Gestor, porque reciben los empalmes de su área (RN-056).
 */
class ActivacionCargoController extends Controller
{
    private const ORDEN_NIVEL = ['Gerente' => 1, 'Gestor' => 2, 'Lider' => 3, 'Equipo' => 4];

    public function __construct(private FaseService $fases)
    {
    }

    public function edit(int $fase)
    {
        $fase = $this->fases->buscar($fase);
        $fichas = $this->fases->fichasDeLaFase($fase->id);

        // Escenario 11: sin fichas no hay propuesta
        if ($fichas->isEmpty()) {
            return view('talentohumano::fases.cargos', ['fase' => $fase, 'fichas' => $fichas]);
        }

        $cargos = $this->cargosDelCatalogo();
        $activados = DB::table('th_cargos_activados')->where('fase_id', $fase->id)->pluck('numero_aprendices', 'cargo_id');
        $compatibles = DB::table('th_cargo_programas as cp')
            ->join('programs as p', 'p.id', '=', 'cp.program_id')
            ->whereIn('cp.program_id', $fichas->pluck('program_id')->unique())
            ->select('cp.cargo_id', 'p.name')
            ->get()
            ->groupBy('cargo_id')
            ->map(fn ($programas) => $programas->pluck('name')->unique()->values()->all());

        // Si la fase aún no tiene activación guardada, se marca la propuesta del sistema
        $hayActivacion = $activados->isNotEmpty();

        $filas = $cargos->map(function ($cargo) use ($activados, $compatibles, $hayActivacion) {
            $cargo->programas = $compatibles[$cargo->id] ?? [];
            $cargo->propuesto = $cargo->programas || $cargo->nivel === 'Gestor';
            $cargo->activo = $hayActivacion ? $activados->has($cargo->id) : $cargo->propuesto;
            $cargo->numero = $activados[$cargo->id] ?? $cargo->numero_aprendices;

            return $cargo;
        });

        return view('talentohumano::fases.cargos', [
            'fase' => $fase,
            'fichas' => $fichas,
            'propuesta' => $filas->filter(fn ($c) => $c->propuesto || $c->activo)->groupBy('area'),
            'otros' => $filas->reject(fn ($c) => $c->propuesto || $c->activo)->values(),
            'subordinados' => $this->subordinados($cargos),
            'unidades' => DB::table('productive_units as u')
                ->leftJoin('sectors as s', 's.id', '=', 'u.sector_id')
                ->whereNull('u.deleted_at')
                ->select('u.name', 's.name as sector')
                ->orderBy('s.name')->orderBy('u.name')
                ->get(),
            'hayActivacion' => $hayActivacion,
            'bloqueada' => $this->fases->convocatoriaPublicada($fase->id),
        ]);
    }

    /**
     * 'Confirmar activación' (escenarios 7, 8, 9, 12 y 13).
     */
    public function update(Request $request, int $fase)
    {
        $fase = $this->fases->buscar($fase);

        if ($this->fases->convocatoriaPublicada($fase->id)) {
            return back()->with('error', 'La convocatoria ya fue publicada; no se pueden modificar los cargos activados.');
        }

        if ($this->fases->fichasDeLaFase($fase->id)->isEmpty()) {
            return redirect()->route('talentohumano.fases.cargos.edit', $fase->id);
        }

        $catalogo = $this->cargosDelCatalogo()->keyBy('id');
        $seleccion = collect($request->input('cargos', []))->filter(fn ($c) => !empty($c['activo']));
        $errores = new MessageBag();

        if ($seleccion->isEmpty()) {
            $errores->add('cargos', 'Active al menos un cargo para la fase.');
        }

        foreach ($seleccion as $cargoId => $datos) {
            $cargo = $catalogo[$cargoId] ?? null;
            $numero = trim((string) ($datos['numero'] ?? ''));

            if (!$cargo) {
                $errores->add("cargos.$cargoId.activo", 'Uno de los cargos ya no está activo en el catálogo. Recargue la página.');
            } elseif (!$cargo->area_id) {
                $errores->add("cargos.$cargoId.activo", "{$cargo->nombre}: el cargo no tiene nivel jerárquico o área funcional definidos. Solicite al Administrador completarlos.");
            } elseif ($numero === '') {
                $errores->add("cargos.$cargoId.numero", "{$cargo->nombre}: indique el número de aprendices requeridos.");
            } elseif (!ctype_digit($numero) || (int) $numero < 1) {
                $errores->add("cargos.$cargoId.numero", "{$cargo->nombre}: el número de aprendices debe ser un entero mayor que cero.");
            } elseif ((int) $numero > 255) {
                $errores->add("cargos.$cargoId.numero", "{$cargo->nombre}: el número de aprendices no puede ser mayor que 255.");
            }
        }

        if ($errores->isNotEmpty()) {
            return back()->withErrors($errores)->withInput();
        }

        $actuales = DB::table('th_cargos_activados')->where('fase_id', $fase->id)->get()->keyBy('cargo_id');
        $retirados = $actuales->keys()->diff($seleccion->keys());

        DB::transaction(function () use ($fase, $seleccion, $actuales, $retirados) {
            // Un cargo retirado sale también de la convocatoria en borrador (publicada ya no se llega hasta aquí)
            $idsRetirados = $actuales->only($retirados->all())->pluck('id');
            DB::table('th_convocatoria_cargos')->whereIn('cargo_activado_id', $idsRetirados)->delete();
            DB::table('th_cargos_activados')->whereIn('id', $idsRetirados)->delete();

            foreach ($seleccion as $cargoId => $datos) {
                $numero = (int) $datos['numero'];

                if ($actuales->has($cargoId)) {
                    if ((int) $actuales[$cargoId]->numero_aprendices !== $numero) {
                        DB::table('th_cargos_activados')->where('id', $actuales[$cargoId]->id)
                            ->update(['numero_aprendices' => $numero, 'updated_at' => now()]);
                    }
                    continue;
                }

                DB::table('th_cargos_activados')->insert([
                    'fase_id' => $fase->id,
                    'cargo_id' => $cargoId,
                    'numero_aprendices' => $numero,
                    'activado_por' => auth()->user()->person_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Auditoria::registrar('Confirmar activación de cargos', 'th_cargos_activados', $fase->id, [
            'activados' => $seleccion->map(fn ($c) => (int) $c['numero'])->all(),
            'retirados' => $retirados->values()->all(),
        ]);

        return redirect()->route('talentohumano.fases.cargos.edit', $fase->id)
            ->with('success', 'Cargos activados correctamente: ' . $seleccion->count() . ' cargos y '
                . $seleccion->sum(fn ($c) => (int) $c['numero']) . ' aprendices requeridos.');
    }

    /**
     * Cargos activos del catálogo (los inactivos no se proponen ni se pueden agregar, escenario 10).
     */
    private function cargosDelCatalogo(): Collection
    {
        return DB::table('th_cargos as c')
            ->leftJoin('th_areas as a', 'a.id', '=', 'c.area_id')
            ->whereNull('c.deleted_at')
            ->where('c.estado', 'activo')
            ->select('c.id', 'c.nombre', 'c.nivel', 'c.area_id', 'c.numero_aprendices', 'c.cargo_superior_id', 'a.nombre as area')
            ->get()
            ->sortBy(fn ($c) => [$c->area ?? 'zzz', self::ORDEN_NIVEL[$c->nivel] ?? 9, $c->nombre])
            ->values();
    }

    /**
     * Para cada cargo de nivel Gestor, los cargos que dependen de él (directa o indirectamente).
     * Se muestran en la advertencia al retirarlo: sus empalmes quedarían sin receptor (escenario 5).
     */
    private function subordinados(Collection $cargos): array
    {
        $hijos = $cargos->groupBy('cargo_superior_id');
        $resultado = [];

        foreach ($cargos->where('nivel', 'Gestor') as $gestor) {
            $nombres = [];
            $pendientes = [$gestor->id];

            while ($pendientes) {
                foreach ($hijos[array_pop($pendientes)] ?? [] as $hijo) {
                    $nombres[] = $hijo->nombre;
                    $pendientes[] = $hijo->id;
                }
            }

            $resultado[$gestor->id] = $nombres;
        }

        return $resultado;
    }
}
