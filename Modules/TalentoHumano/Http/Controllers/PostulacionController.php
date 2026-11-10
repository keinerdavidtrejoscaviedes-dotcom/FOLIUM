<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\AnalisisHv;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\SICA\Entities\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\TalentoHumano\Entities\PostulacionDocumento;
use Modules\TalentoHumano\Entities\PostulacionOpcion;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;

class PostulacionController extends Controller
{
    /**
     * Listado de postulaciones para revisión y análisis de hojas de vida (HU-024).
     */
    public function index(Request $request)
    {
        // Bloque 1: Convocatoria activa o más reciente
        $convocatoria = $this->obtenerConvocatoriaActiva();
        $convocatoriaId = $convocatoria ? $convocatoria->id : null;

        // Bloque 2: Filtrar postulaciones por criterios (nombre, ficha, cargo, estado)
        $postulaciones = $this->filtrarPostulaciones($request, $convocatoriaId);

        // Bloque 3: Calcular métricas generales de postulación
        $metricas = $this->calcularMetricasConvocatoria($convocatoriaId);

        // Bloque 4: Opciones de filtrado (fichas y cargos)
        $opcionesFiltro = $this->obtenerOpcionesFiltros();

        return view('talentohumano::postulaciones.index', array_merge([
            'convocatoria' => $convocatoria,
            'postulaciones' => $postulaciones,
        ], $metricas, $opcionesFiltro));
    }

    /**
     * Muestra la ficha completa del aspirante (Mockup INS-05).
     */
    public function show($id)
    {
        $postulacion = Postulacion::with([
            'person',
            'course.program',
            'opciones.convocatoriaCargo.cargoActivado.cargo',
            'documentos',
            'analisisHv.evaluador',
            'convocatoria.fase',
        ])->findOrFail($id);

        $user = auth()->user();
        $esInstructor = $user->roles->contains(fn ($r) => in_array($r->slug, ['talentohumano.instructor', 'talentohumano.admin']));

        // Resultados de la prueba y de las entrevistas: Líder, Instructor de TH y Administrador; el Gestor no los ve (SRS 2.6)
        $designaciones = AccesoTH::designaciones($user);
        $verResultados = AccesoTH::esAdministrador($user) || array_intersect(['lider_estrategia', 'instructor_th'], $designaciones);
        $designacion = match (true) {
            in_array('lider_estrategia', $designaciones) => 'Líder de la estrategia',
            in_array('instructor_th', $designaciones) => 'Instructor de Talento Humano',
            default => 'Administrador',
        };

        return view('talentohumano::postulaciones.show', [
            'postulacion' => $postulacion,
            'esInstructor' => $esInstructor,
            'verResultados' => $verResultados,
            'designacion' => $designacion,
            'pruebaPsicotecnica' => $verResultados ? $this->prepararPruebaPsicotecnica($postulacion) : null,
            'entrevistas' => $verResultados ? $this->prepararEvaluacionesEntrevista($postulacion) : null,
        ]);
    }

    /**
     * Descarga un documento o una propuesta de trabajo de la postulación (almacenamiento privado).
     */
    public function documento(Request $request, $id, $documento)
    {
        $registro = $request->query('tipo') === 'propuesta'
            ? PostulacionOpcion::where('postulacion_id', $id)->findOrFail($documento)
            : PostulacionDocumento::where('postulacion_id', $id)->findOrFail($documento);
        $ruta = $registro instanceof PostulacionOpcion ? $registro->propuesta_trabajo : $registro->ruta_archivo;
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta, $registro instanceof PostulacionOpcion
            ? 'propuesta-' . $registro->preferencia . '.' . pathinfo($ruta, PATHINFO_EXTENSION)
            : $registro->nombre_original);
    }

    /**
     * Guarda o actualiza el concepto de análisis de la hoja de vida (HU-024 · RF-006).
     */
    public function guardarAnalisis(Request $request, $id)
    {
        $request->validate([
            'formacion' => 'required|string|min:5',
            'habilidades' => 'required|string|min:5',
            'afinidades' => 'nullable|array',
            'observaciones' => 'nullable|string',
        ]);

        $postulacion = Postulacion::with('opciones.convocatoriaCargo.cargoActivado.cargo')->findOrFail($id);
        $evaluadorId = auth()->user()->person_id ?? 1;

        // Bloque: Construcción de afinidad estructurada
        $afinidadFinal = $this->procesarTextoAfinidades($request, $postulacion);

        $analisis = AnalisisHv::updateOrCreate(
            ['postulacion_id' => $postulacion->id],
            [
                'evaluador_id' => $evaluadorId,
                'formacion' => $request->formacion,
                'habilidades' => $request->habilidades,
                'afinidad' => $afinidadFinal,
                'observaciones' => $request->observaciones,
            ]
        );

        Auditoria::registrar('analisis_hv_guardado', $analisis, [], [
            'aspirante' => trim(optional($postulacion->person)->first_name . ' ' . optional($postulacion->person)->first_last_name),
            'formacion' => $request->formacion, 'habilidades' => $request->habilidades, 'observaciones' => $request->observaciones,
        ]);
        [, $designacion] = Auditoria::origen();

        return back()->with('success', "Concepto guardado con tu designación {$designacion}.");
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Obtiene la convocatoria activa o más reciente en curso.
     */
    private function obtenerConvocatoriaActiva(): ?Convocatoria
    {
        return Convocatoria::with('fase')
            ->whereIn('estado', ['publicada', 'cerrada', 'finalizada', 'borrador'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Filtra las postulaciones según los parámetros de búsqueda del usuario.
     */
    private function filtrarPostulaciones(Request $request, ?int $convocatoriaId)
    {
        $query = Postulacion::with([
            'person',
            'course.program',
            'opciones.convocatoriaCargo.cargoActivado.cargo',
            'documentos',
            'analisisHv.evaluador',
        ]);

        if ($convocatoriaId) {
            $query->where('convocatoria_id', $convocatoriaId);
        }

        // Filtro por búsqueda de nombre o documento
        if ($buscar = $request->query('buscar')) {
            $query->whereHas('person', function ($q) use ($buscar) {
                $q->where('first_name', 'like', "%{$buscar}%")
                  ->orWhere('first_last_name', 'like', "%{$buscar}%")
                  ->orWhere('second_last_name', 'like', "%{$buscar}%")
                  ->orWhere('document_number', 'like', "%{$buscar}%");
            });
        }

        // Filtro por ficha
        if ($courseId = $request->query('course_id')) {
            $query->where('course_id', $courseId);
        }

        // Filtro por estado de análisis
        if ($request->query('estado_analisis') === 'analizadas') {
            $query->has('analisisHv');
        } elseif ($request->query('estado_analisis') === 'pendientes') {
            $query->doesntHave('analisisHv');
        }

        // Filtro por cargo (en opciones)
        if ($cargoId = $request->query('cargo_id')) {
            $query->whereHas('opciones.convocatoriaCargo.cargoActivado', function ($q) use ($cargoId) {
                $q->where('cargo_id', $cargoId);
            });
        }

        return $query->orderByDesc('puntaje_total')->get();
    }

    /**
     * Calcula los contadores y promedios estadísticos de la convocatoria.
     */
    private function calcularMetricasConvocatoria(?int $convocatoriaId): array
    {
        if (!$convocatoriaId) {
            return [
                'totalPostulados' => 0,
                'totalAnalizadas' => 0,
                'totalPendientes' => 0,
                'promedioPuntaje' => 0,
            ];
        }

        $totalPostulados = Postulacion::where('convocatoria_id', $convocatoriaId)->count();
        $totalAnalizadas = Postulacion::where('convocatoria_id', $convocatoriaId)->has('analisisHv')->count();
        $totalPendientes = $totalPostulados - $totalAnalizadas;
        $promedioPuntaje = Postulacion::where('convocatoria_id', $convocatoriaId)->whereNotNull('puntaje_total')->avg('puntaje_total') ?: 0;

        return [
            'totalPostulados' => $totalPostulados,
            'totalAnalizadas' => $totalAnalizadas,
            'totalPendientes' => $totalPendientes,
            'promedioPuntaje' => $promedioPuntaje,
        ];
    }

    /**
     * Obtiene los conjuntos de datos para poblar los filtros de búsqueda.
     */
    private function obtenerOpcionesFiltros(): array
    {
        return [
            'fichas' => Course::whereIn('id', Postulacion::pluck('course_id')->unique())->with('program')->get(),
            'cargos' => Cargo::where('estado', 'activo')->orderBy('nombre')->get(),
        ];
    }

    /**
     * Resultado de la prueba psicotécnica presentada, con el puntaje de cada pregunta (RF-007).
     */
    private function prepararPruebaPsicotecnica(Postulacion $postulacion): ?array
    {
        $aplicacion = DB::table('th_aplicaciones_prueba')->where('postulacion_id', $postulacion->id)->latest('id')->first();
        if (!$aplicacion) {
            return null;
        }

        $preguntas = DB::table('th_cuestionario_preguntas as p')
            ->leftJoin('th_respuestas_prueba as r', fn ($j) => $j->on('r.pregunta_id', '=', 'p.id')->where('r.aplicacion_id', $aplicacion->id))
            ->where('p.cuestionario_id', $aplicacion->cuestionario_id)->orderBy('p.orden')
            ->get(['p.enunciado', 'p.tipo', 'p.puntaje_maximo', 'r.puntaje', 'r.respuesta']);

        return [
            'estado' => $aplicacion->estado,
            'puntaje' => $aplicacion->puntaje !== null ? (float) $aplicacion->puntaje : null,
            'maximo' => (float) $preguntas->where('tipo', '!=', 'abierta')->sum('puntaje_maximo'),
            'fecha' => $aplicacion->fecha_presentacion ? \Illuminate\Support\Carbon::parse($aplicacion->fecha_presentacion)->format('d/m/Y') : null,
            'preguntas' => $preguntas,
        ];
    }

    /**
     * Calificaciones de cada evaluador en las entrevistas técnica y psicológica (RF-009).
     */
    private function prepararEvaluacionesEntrevista(Postulacion $postulacion): array
    {
        $evaluaciones = DB::table('th_calificaciones as c')
            ->join('th_entrevistas as e', 'e.id', '=', 'c.entrevista_id')
            ->leftJoin('people as p', 'p.id', '=', 'c.evaluador_id')
            ->where('c.postulacion_id', $postulacion->id)
            ->orderBy('e.fecha_hora')
            ->selectRaw("TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name)) as evaluador, e.tipo, e.modalidad, c.puntaje, c.concepto, c.observaciones")
            ->get();

        return [
            'puntaje' => $evaluaciones->isNotEmpty() ? round($evaluaciones->avg('puntaje'), 2) : null,
            'evaluaciones' => $evaluaciones->map(fn ($e) => [
                'evaluador' => $e->evaluador ?: 'Evaluador',
                'rol' => ($e->tipo === 'psicologica' ? 'psicológica' : 'técnica') . ' ' . $e->modalidad,
                'puntaje' => (float) $e->puntaje,
                'concepto' => $e->concepto === 'apto' ? 'Apto' : 'No apto',
                'comentarios' => $e->observaciones,
            ])->all(),
        ];
    }

    /**
     * Procesa la cadena consolidada de afinidades según opciones postuladas.
     */
    private function procesarTextoAfinidades(Request $request, Postulacion $postulacion): ?string
    {
        if (!$request->has('afinidades')) {
            return null;
        }

        $afinidadesTexto = [];
        foreach ($postulacion->opciones as $op) {
            $cargoNom = $op->cargo_nombre;
            $nivel = $request->input("afinidades.{$op->id}", 'Media');
            $afinidadesTexto[] = "{$op->preferencia}. {$cargoNom} · {$nivel}";
        }

        return !empty($afinidadesTexto) ? implode(' | ', $afinidadesTexto) : null;
    }
}
