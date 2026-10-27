<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SICA\Entities\Program;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Http\Requests\CargoRequest;
use Modules\TalentoHumano\Support\Auditoria;

/**
 * Catálogo de cargos (RF-019; HU-003 a HU-006).
 */
class CatalogoCargosController extends Controller
{
    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['activo', 'inactivo', 'todos']) ? $request->query('estado') : 'activo';

        $cargos = Cargo::with(['area', 'superior'])
            ->withCount('programas')
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
            ->when($request->query('q'), fn ($q, $texto) => $q->where('nombre', 'like', '%' . $texto . '%'))
            ->when($request->query('nivel'), fn ($q, $nivel) => $q->where('nivel', $nivel))
            ->when($request->query('area'), fn ($q, $area) => $q->where('area_id', $area))
            ->orderByRaw("FIELD(nivel, 'Gerente', 'Gestor', 'Lider', 'Equipo')")
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('talentohumano::lider.catalogo.index', [
            'cargos' => $cargos,
            'estado' => $estado,
            'conteo' => Cargo::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
            'areas' => Area::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function show(Cargo $cargo)
    {
        $cargo->load(['area', 'superior', 'subordinados' => fn ($q) => $q->orderBy('nombre'), 'programas' => fn ($q) => $q->orderBy('name'), 'requisitosAdicionales']);

        return view('talentohumano::lider.catalogo.show', ['cargo' => $cargo]);
    }

    public function create()
    {
        return view('talentohumano::lider.catalogo.form', $this->datosFormulario(new Cargo(['numero_aprendices' => 1])));
    }

    public function store(CargoRequest $request)
    {
        $cargo = DB::transaction(function () use ($request) {
            $cargo = Cargo::create($request->datosCargo() + ['estado' => 'activo']);
            $this->guardarRelaciones($cargo, $request->datosProgramas(), $request->datosRequisitos());
            Auditoria::registrar('created', $cargo, [], $this->instantanea($cargo->fresh()));

            return $cargo;
        });

        $redirect = redirect()->route('talentohumano.lider.catalogo.show', $cargo)->with('success', 'Cargo creado correctamente');

        return $request->datosProgramas()
            ? $redirect
            : $redirect->with('warning', 'Este cargo no tiene programas compatibles: no se propondrá automáticamente en la activación y solo podrá agregarse de forma manual');
    }

    public function edit(Cargo $cargo)
    {
        return view('talentohumano::lider.catalogo.form', $this->datosFormulario($cargo->load(['programas', 'requisitosAdicionales'])));
    }

    public function update(CargoRequest $request, Cargo $cargo)
    {
        $antes = $this->instantanea($cargo);
        $cambiosCargo = array_diff_assoc(array_map('strval', $request->datosCargo()), array_map('strval', $cargo->only(array_keys($request->datosCargo()))));
        $cambiaProgramas = $request->datosProgramas() != $this->programasActuales($cargo);
        $cambiaRequisitos = $request->datosRequisitos() != $this->requisitosActuales($cargo);

        // HU-005, esc. 7 y HU-006, esc. 9
        if (!$cambiosCargo && !$cambiaProgramas && !$cambiaRequisitos) {
            return redirect()->route('talentohumano.lider.catalogo.show', $cargo)->with('info', 'No se detectaron cambios');
        }

        DB::transaction(function () use ($request, $cargo, $antes) {
            $cargo->update($request->datosCargo());
            $this->guardarRelaciones($cargo, $request->datosProgramas(), $request->datosRequisitos());

            $despues = $this->instantanea($cargo->fresh());
            $campos = array_keys(array_filter($despues, fn ($valor, $campo) => $valor !== ($antes[$campo] ?? null), ARRAY_FILTER_USE_BOTH));
            Auditoria::registrar('updated', $cargo, array_intersect_key($antes, array_flip($campos)), array_intersect_key($despues, array_flip($campos)));
        });

        return redirect()->route('talentohumano.lider.catalogo.show', $cargo)->with('success', 'Cargo actualizado correctamente');
    }

    public function inactivar(Cargo $cargo)
    {
        return $this->cambiarEstado($cargo, 'inactivo', 'Cargo inactivado correctamente');
    }

    public function reactivar(Cargo $cargo)
    {
        return $this->cambiarEstado($cargo, 'activo', 'Cargo reactivado correctamente');
    }

    public function destroy(Cargo $cargo)
    {
        if ($cargo->tieneHistorial()) {
            return back()->with('error', 'Este cargo tiene asignaciones de fases anteriores y solo puede inactivarse')->with('ofrecer_inactivar', $cargo->id);
        }
        if ($cargo->subordinados()->exists()) {
            return back()->with('error', 'Este cargo es jefe inmediato de otros cargos: asígneles otro jefe antes de eliminarlo');
        }

        DB::transaction(function () use ($cargo) {
            Auditoria::registrar('deleted', $cargo, $this->instantanea($cargo));
            $cargo->forceDelete();
        });

        return redirect()->route('talentohumano.lider.catalogo.index')->with('success', 'Cargo eliminado correctamente');
    }

    private function cambiarEstado(Cargo $cargo, string $estado, string $mensaje)
    {
        if ($cargo->estado !== $estado) {
            $anterior = $cargo->estado;
            $cargo->update(['estado' => $estado]);
            Auditoria::registrar($estado === 'activo' ? 'reactivado' : 'inactivado', $cargo, ['estado' => $anterior], ['estado' => $estado]);
        }

        return back()->with('success', $mensaje);
    }

    private function guardarRelaciones(Cargo $cargo, array $programas, array $requisitos): void
    {
        $cargo->programas()->sync(collect($programas)->map(fn ($tipo) => ['tipo' => $tipo])->all());

        if ($requisitos != $this->requisitosActuales($cargo)) {
            $cargo->requisitosAdicionales()->delete();
            $cargo->requisitosAdicionales()->createMany($requisitos);
        }
    }

    private function programasActuales(Cargo $cargo): array
    {
        return $cargo->programas()->get()->mapWithKeys(fn ($p) => [$p->id => $p->pivot->tipo])->sortKeys()->all();
    }

    private function requisitosActuales(Cargo $cargo): array
    {
        return $cargo->requisitosAdicionales()->orderBy('id')->get()
            ->map(fn ($r) => ['descripcion' => $r->descripcion, 'exige_soporte' => $r->exige_soporte])->all();
    }

    /**
     * Estado completo del cargo para la auditoría (valores anteriores y nuevos).
     */
    private function instantanea(Cargo $cargo): array
    {
        $cargo->load(['area', 'superior']);

        return [
            'nombre' => $cargo->nombre,
            'nivel' => $cargo->nivel,
            'area' => $cargo->area?->nombre,
            'jefe_inmediato' => $cargo->nombreJefe(),
            'numero_aprendices' => $cargo->numero_aprendices,
            'macroproceso' => $cargo->macroproceso,
            'proceso' => $cargo->proceso,
            'subproceso' => $cargo->subproceso,
            'rol_principal' => $cargo->rol_principal,
            'actividades' => $cargo->actividades,
            'responsabilidades_sig' => $cargo->responsabilidades_sig,
            'documentacion' => $cargo->documentacion,
            'requisitos' => $cargo->requisitos,
            'estado' => $cargo->estado,
            'programas' => $cargo->programas()->orderBy('name')->get()
                ->map(fn ($p) => $p->name . ' (' . $p->sofia_code . '): ' . Cargo::TIPOS_PROGRAMA[$p->pivot->tipo])->implode('; '),
            'requisitos_no_verificables' => collect($this->requisitosActuales($cargo))
                ->map(fn ($r) => $r['descripcion'] . ($r['exige_soporte'] ? ' (exige soporte)' : ''))->implode('; '),
        ];
    }

    private function datosFormulario(Cargo $cargo): array
    {
        $vinculados = $cargo->exists ? $cargo->programas->pluck('id') : collect();

        return [
            'cargo' => $cargo,
            'areas' => Area::orderBy('nombre')->pluck('nombre', 'id'),
            'jefes' => Cargo::where('estado', 'activo')->where('nivel', '!=', 'Equipo')->when($cargo->exists, fn ($q) => $q->where('id', '!=', $cargo->id))
                ->orderByRaw("FIELD(nivel, 'Gerente', 'Gestor', 'Lider')")->orderBy('nombre')->get(['id', 'nombre', 'nivel']),
            'programas' => Program::where('program_type', 'Tecnólogo')->orWhereIn('id', $vinculados)
                ->orderBy('name')->orderBy('sofia_code')->get(['id', 'name', 'sofia_code']),
        ];
    }
}
