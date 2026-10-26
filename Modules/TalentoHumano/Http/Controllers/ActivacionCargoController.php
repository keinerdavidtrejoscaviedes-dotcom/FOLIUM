<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\FaseFicha;
use Modules\SICA\Entities\Course;
use Modules\SICA\Entities\Program;

class ActivacionCargoController extends Controller
{
    /**
     * Muestra la interfaz de activación de cargos y gestión de fichas para la fase.
     * Cumple con HU-018 y HU-019 (RF-001, RF-018, RN-037, RN-047, RN-048).
     */
    public function index(Request $request)
    {
        // Bloque 1: Resolución de la fase formativa
        $fase = $this->resolverFaseActiva($request->query('fase_id'));
        $todasFases = Fase::orderByDesc('numero')->get();

        // Bloque 2: Programas y fichas participantes
        $fichasFase = $fase ? $fase->faseFichas : collect();
        $programasFaseIds = $this->obtenerProgramasFaseIds($fase);
        $cargosActivadosMap = $fase ? $fase->cargosActivados->keyBy('cargo_id') : collect();

        // Bloque 3: Filtrado y mapeo de compatibilidad de cargos
        $cargosBase = $this->filtrarCargos($request);
        $cargos = $this->mapearCompatibilidadCargos($cargosBase, $programasFaseIds, $cargosActivadosMap, $request);

        // Bloque 4: Métricas del módulo
        $metricas = $this->calcularMetricasActivacion($cargosActivadosMap, $fase, $programasFaseIds);

        // Bloque 5: Catálogos para modales de apoyo
        $areas = Area::orderBy('nombre')->get();
        $cursosDisponibles = $this->obtenerCursosDisponibles($fichasFase);
        $programas = $this->obtenerProgramasFormacion();

        return view('talentohumano::activacion.index', array_merge([
            'fase' => $fase,
            'todasFases' => $todasFases,
            'fichasFase' => $fichasFase,
            'programasFaseIds' => $programasFaseIds,
            'areas' => $areas,
            'cargos' => $cargos,
            'cursosDisponibles' => $cursosDisponibles,
            'programas' => $programas,
        ], $metricas));
    }

    /**
     * Activa o desactiva un cargo en la fase (HU-019).
     */
    public function toggleCargo(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:th_fases,id',
            'cargo_id' => 'required|exists:th_cargos,id',
            'numero_aprendices' => 'nullable|integer|min:1|max:99',
        ]);

        $fase = Fase::findOrFail($request->fase_id);
        $cargo = Cargo::findOrFail($request->cargo_id);
        $personId = auth()->user()->person_id ?? 1;

        // Bloque: Operación toggle
        $resultado = $this->ejecutarToggleCargo($fase, $cargo, $request->numero_aprendices, $personId);

        if ($request->wantsJson()) {
            return response()->json(array_merge(['success' => true], $resultado));
        }

        return back()->with('success', $resultado['mensaje']);
    }

    /**
     * Actualiza el número de cupos / aprendices de un cargo activado (HU-019).
     */
    public function updateCupos(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:th_fases,id',
            'cargo_id' => 'required|exists:th_cargos,id',
            'numero_aprendices' => 'required|integer|min:1|max:99',
        ]);

        $fase = Fase::findOrFail($request->fase_id);
        $cargo = Cargo::findOrFail($request->cargo_id);
        $personId = auth()->user()->person_id ?? 1;

        $activado = CargoActivado::updateOrCreate(
            ['fase_id' => $fase->id, 'cargo_id' => $cargo->id],
            [
                'numero_aprendices' => $request->numero_aprendices,
                'activado_por' => $personId,
            ]
        );

        $mensaje = "Cupos actualizados para «{$cargo->nombre}»: {$activado->numero_aprendices} cupo(s).";

        if ($request->wantsJson()) {
            $totalCupos = CargoActivado::where('fase_id', $fase->id)->sum('numero_aprendices');
            return response()->json([
                'success' => true,
                'mensaje' => $mensaje,
                'numero_aprendices' => $activado->numero_aprendices,
                'total_cupos' => $totalCupos,
            ]);
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Activa todos los cargos de un área de manera masiva.
     */
    public function activarArea(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:th_fases,id',
            'area_id' => 'required|exists:th_areas,id',
        ]);

        $fase = Fase::findOrFail($request->fase_id);
        $area = Area::findOrFail($request->area_id);
        $personId = auth()->user()->person_id ?? 1;

        $cargos = Cargo::where('area_id', $area->id)->where('estado', 'activo')->get();
        $activados = 0;

        foreach ($cargos as $cargo) {
            CargoActivado::firstOrCreate(
                ['fase_id' => $fase->id, 'cargo_id' => $cargo->id],
                [
                    'numero_aprendices' => $cargo->numero_aprendices ?: 1,
                    'activado_por' => $personId,
                ]
            );
            $activados++;
        }

        return back()->with('success', "Se activaron {$activados} cargos del área «{$area->nombre}».");
    }

    /**
     * Agrega una ficha de formación a la fase participante (HU-018).
     * Si la ficha no tenía programa y se proporciona, se lo asigna (HU-018).
     */
    public function agregarFicha(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:th_fases,id',
            'course_id' => 'required|exists:courses,id',
            'program_id' => 'nullable|exists:programs,id',
        ]);

        $fase = Fase::findOrFail($request->fase_id);
        $course = Course::findOrFail($request->course_id);
        $personId = auth()->user()->person_id ?? 1;

        // HU-018: Registrar manualmente el programa de la ficha si falta o se especifica
        if ($request->filled('program_id') && ($course->program_id != $request->program_id)) {
            $course->program_id = $request->program_id;
            $course->save();
        }

        // Asociar la ficha a la fase
        FaseFicha::firstOrCreate(
            ['fase_id' => $fase->id, 'course_id' => $course->id],
            ['registrada_por' => $personId]
        );

        return back()->with('success', "Ficha {$course->code} agregada a la {$fase->romano}.");
    }

    /**
     * Elimina una ficha de la fase.
     */
    public function eliminarFicha(Request $request, $id)
    {
        $faseFicha = FaseFicha::findOrFail($id);
        $codigo = $faseFicha->course->code ?? 'Ficha';
        $faseFicha->delete();

        return back()->with('success', "Ficha {$codigo} removida de la fase.");
    }

    /**
     * Asigna manualmente el programa de formación a una ficha (HU-018 específico).
     */
    public function asignarPrograma(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'program_id' => 'required|exists:programs,id',
        ]);

        $course = Course::findOrFail($request->course_id);
        $program = Program::findOrFail($request->program_id);

        $course->program_id = $program->id;
        $course->save();

        $mensaje = "Programa «{$program->name}» asignado exitosamente a la ficha {$course->code}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'mensaje' => $mensaje,
                'program_name' => $program->name,
            ]);
        }

        return back()->with('success', $mensaje);
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Resuelve la fase activa por parámetro o la más reciente.
     */
    private function resolverFaseActiva(?int $faseId): ?Fase
    {
        if ($faseId) {
            return Fase::with(['faseFichas.course.program', 'cargosActivados'])->findOrFail($faseId);
        }

        return Fase::with(['faseFichas.course.program', 'cargosActivados'])
            ->whereIn('estado', ['planeacion', 'seleccion', 'en_curso'])
            ->orderByDesc('numero')
            ->first() ?? Fase::orderByDesc('numero')->first();
    }

    /**
     * Obtiene los IDs de los programas asociados a las fichas de la fase.
     */
    private function obtenerProgramasFaseIds(?Fase $fase): array
    {
        if (!$fase) {
            return [];
        }

        return $fase->faseFichas
            ->pluck('course.program_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Realiza la consulta inicial filtrada de cargos activos.
     */
    private function filtrarCargos(Request $request)
    {
        $query = Cargo::with(['area', 'cargoProgramas.program'])
            ->where('estado', 'activo');

        if ($buscar = $request->query('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('rol_principal', 'like', "%{$buscar}%")
                  ->orWhere('requisitos', 'like', "%{$buscar}%");
            });
        }

        if ($areaId = $request->query('area_id')) {
            $query->where('area_id', $areaId);
        }

        if ($nivel = $request->query('nivel')) {
            $query->where('nivel', $nivel);
        }

        return $query->orderBy('area_id')->orderBy('nivel')->orderBy('nombre')->get();
    }

    /**
     * Mapea compatibilidad técnica y estado de activación en la fase.
     */
    private function mapearCompatibilidadCargos($cargos, array $programasFaseIds, $cargosActivadosMap, Request $request)
    {
        $cargos = $cargos->map(function ($cargo) use ($cargosActivadosMap, $programasFaseIds) {
            $activado = $cargosActivadosMap->get($cargo->id);
            $cargo->is_activado = (bool) $activado;
            $cargo->cupos_activados = $activado ? $activado->numero_aprendices : $cargo->numero_aprendices;
            $cargo->activado_registro = $activado;

            $programasCargo = $cargo->cargoProgramas;
            $programasCompatiblesPresentes = $programasCargo->filter(function ($cp) use ($programasFaseIds) {
                return in_array($cp->program_id, $programasFaseIds);
            });

            $cargo->es_compatible = $programasCompatiblesPresentes->isNotEmpty();
            $cargo->programas_compatibles_en_fase = $programasCompatiblesPresentes;
            $cargo->total_programas_afines = $programasCargo->count();

            return $cargo;
        });

        // Filtro por estado de activación
        if ($request->query('estado_activacion') === 'activados') {
            $cargos = $cargos->where('is_activado', true)->values();
        } elseif ($request->query('estado_activacion') === 'pendientes') {
            $cargos = $cargos->where('is_activado', false)->values();
        }

        // Filtro por compatibilidad
        if ($request->query('solo_compatibles') === '1') {
            $cargos = $cargos->where('es_compatible', true)->values();
        }

        return $cargos;
    }

    /**
     * Calcula métricas globales de activación para el panel de cabecera.
     */
    private function calcularMetricasActivacion($cargosActivadosMap, ?Fase $fase, array $programasFaseIds): array
    {
        return [
            'totalCargos' => Cargo::where('estado', 'activo')->count(),
            'totalActivados' => $cargosActivadosMap->count(),
            'totalCupos' => $cargosActivadosMap->sum('numero_aprendices'),
            'totalFichas' => $fase ? $fase->faseFichas->count() : 0,
            'totalProgramas' => count($programasFaseIds),
        ];
    }

    /**
     * Obtiene los cursos formativos disponibles para asociar.
     */
    private function obtenerCursosDisponibles($fichasFase)
    {
        $fichasExistentesIds = $fichasFase->pluck('course_id')->all();

        return Course::with('program')
            ->whereNotIn('id', $fichasExistentesIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * Obtiene los programas de formación para selección rápida.
     */
    private function obtenerProgramasFormacion()
    {
        return Program::orderBy('name')->get(['id', 'name', 'sofia_code']);
    }

    /**
     * Ejecuta la activación o desactivación de un cargo.
     */
    private function ejecutarToggleCargo(Fase $fase, Cargo $cargo, ?int $numeroAprendices, int $personId): array
    {
        $activado = CargoActivado::where('fase_id', $fase->id)
            ->where('cargo_id', $cargo->id)
            ->first();

        if ($activado) {
            $activado->delete();
            $estado = 'desactivado';
            $mensaje = "El cargo «{$cargo->nombre}» fue desactivado de la {$fase->romano}.";
        } else {
            $cupos = $numeroAprendices ? (int) $numeroAprendices : ($cargo->numero_aprendices ?: 1);

            CargoActivado::create([
                'fase_id' => $fase->id,
                'cargo_id' => $cargo->id,
                'numero_aprendices' => $cupos,
                'activado_por' => $personId,
            ]);
            $estado = 'activado';
            $mensaje = "El cargo «{$cargo->nombre}» fue activado con {$cupos} cupo(s).";
        }

        $totalActivados = CargoActivado::where('fase_id', $fase->id)->count();
        $totalCupos = CargoActivado::where('fase_id', $fase->id)->sum('numero_aprendices');

        return [
            'estado' => $estado,
            'mensaje' => $mensaje,
            'total_activados' => $totalActivados,
            'total_cupos' => $totalCupos,
        ];
    }
}
