<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Entrevista;
use Modules\TalentoHumano\Entities\EntrevistaAspirante;
use Modules\TalentoHumano\Entities\EntrevistaEvaluador;
use Modules\TalentoHumano\Entities\Calificacion;
use Modules\TalentoHumano\Entities\Cuestionario;
use Modules\TalentoHumano\Entities\CuestionarioPregunta;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\SICA\Entities\Person;

class EntrevistaController extends Controller
{
    /**
     * Mis entrevistas asignadas como evaluador (HU-042 · Mockup INS-07).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $personId = $user->person_id ?? 1;

        // Bloque 1: Convocatoria y fase activa
        ['convocatoria' => $convocatoria, 'fase' => $fase] = $this->resolverConvocatoriaYFase();

        // Bloque 2: Entrevistas asignadas al evaluador
        $entrevistas = $this->obtenerEntrevistasEvaluador($personId);

        // Bloque 3: Métricas de progreso del evaluador
        $metricas = $this->calcularMetricasEntrevistas($entrevistas);

        return view('talentohumano::entrevistas.index', array_merge([
            'convocatoria' => $convocatoria,
            'fase' => $fase,
            'entrevistas' => $entrevistas,
            'user' => $user,
        ], $metricas));
    }

    /**
     * Mi formato de preguntas técnicas para entrevistas (HU-040 · Mockup INS-08).
     */
    public function formato(Request $request)
    {
        $personId = auth()->user()->person_id ?? 1;

        // Bloque: Cuestionario vigente o inicialización si es primera vez
        $cuestionario = $this->obtenerOCrearCuestionarioTecnico($personId);

        $totalCalificaciones = Calificacion::where('cuestionario_id', $cuestionario->id)->count();
        $puntajeTotal = $cuestionario->preguntas->sum('puntaje_maximo');

        return view('talentohumano::entrevistas.formato', compact(
            'cuestionario',
            'totalCalificaciones',
            'puntajeTotal'
        ));
    }

    /**
     * Guarda o actualiza el formato de preguntas (HU-040).
     */
    public function guardarFormato(Request $request)
    {
        $request->validate([
            'cuestionario_id' => 'required|exists:th_cuestionarios,id',
            'preguntas' => 'required|array|min:1',
            'preguntas.*' => 'required|array',
            'preguntas.*.enunciado' => 'required|string',
            'preguntas.*.puntaje' => 'required|numeric|min:1',
        ]);

        $personId = auth()->user()->person_id ?? 1;
        $cuestionarioActual = Cuestionario::findOrFail($request->cuestionario_id);

        // Bloque: Control de versiones histórico si ya cuenta con calificaciones
        [$cuestionarioTarget, $mensaje] = $this->versionarOReutilizarCuestionario($cuestionarioActual, $personId);

        // Bloque: Registro de nuevas preguntas en el cuestionario destino
        $this->registrarPreguntasCuestionario($cuestionarioTarget, $request->preguntas);

        return back()->with('success', $mensaje);
    }

    /**
     * Vista de calificación de una entrevista a un aspirante (HU-041).
     */
    public function calificar($entrevistaId, $postulacionId)
    {
        $personId = auth()->user()->person_id ?? 1;

        $entrevista = Entrevista::findOrFail($entrevistaId);
        $postulacion = Postulacion::with(['person', 'course.program', 'opciones.convocatoriaCargo.cargoActivado.cargo'])->findOrFail($postulacionId);

        // Bloques: Cuestionario aplicable y calificación previa
        $cuestionario = $this->resolverCuestionarioVigente($personId);
        $calificacion = Calificacion::where('entrevista_id', $entrevista->id)
            ->where('postulacion_id', $postulacion->id)
            ->where('evaluador_id', $personId)
            ->first();

        return view('talentohumano::entrevistas.calificar', compact(
            'entrevista',
            'postulacion',
            'cuestionario',
            'calificacion'
        ));
    }

    /**
     * Guarda la calificación individual del evaluador (HU-041 · RF-009).
     */
    public function guardarCalificacion(Request $request, $entrevistaId, $postulacionId)
    {
        $request->validate([
            'cuestionario_id' => 'required|exists:th_cuestionarios,id',
            'concepto' => 'required|in:apto,no_apto',
            'observaciones' => 'nullable|string',
            'puntajes' => 'required|array',
        ]);

        $personId = auth()->user()->person_id ?? 1;

        // Bloque: Persistir evaluación y cálculo de puntaje total
        $totalPuntaje = $this->registrarCalificacionAspirante($request, $entrevistaId, $postulacionId, $personId);

        return redirect()->route('talentohumano.entrevistas.index')
            ->with('success', "Calificación guardada exitosamente ({$totalPuntaje} pts · " . ucfirst($request->concepto) . ").");
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Resuelve la convocatoria más reciente y su correspondiente fase formativa.
     */
    private function resolverConvocatoriaYFase(): array
    {
        $convocatoria = Convocatoria::with('fase')->orderByDesc('id')->first();
        $fase = $convocatoria ? $convocatoria->fase : Fase::first();

        return [
            'convocatoria' => $convocatoria,
            'fase' => $fase,
        ];
    }

    /**
     * Obtiene el listado de entrevistas asignadas al evaluador con relaciones.
     */
    private function obtenerEntrevistasEvaluador(int $personId)
    {
        return Entrevista::whereHas('evaluadores', function ($q) use ($personId) {
            $q->where('person_id', $personId);
        })
        ->with([
            'postulaciones.person',
            'postulaciones.course.program',
            'postulaciones.opciones.convocatoriaCargo.cargoActivado.cargo',
            'evaluadores.person',
            'calificaciones' => function ($q) use ($personId) {
                $q->where('evaluador_id', $personId);
            },
        ])
        ->orderBy('fecha_hora')
        ->get();
    }

    /**
     * Calcula métricas de entrevistas asignadas, calificadas y pendientes.
     */
    private function calcularMetricasEntrevistas($entrevistas): array
    {
        $totalAsignadas = $entrevistas->count();
        $totalCalificadas = $entrevistas->filter(fn ($e) => $e->calificaciones->isNotEmpty())->count();
        $totalPorCalificar = $totalAsignadas - $totalCalificadas;

        return [
            'totalAsignadas' => $totalAsignadas,
            'totalCalificadas' => $totalCalificadas,
            'totalPorCalificar' => $totalPorCalificar,
        ];
    }

    /**
     * Obtiene o crea el cuestionario técnico inicial del evaluador.
     */
    private function obtenerOCrearCuestionarioTecnico(int $personId): Cuestionario
    {
        $cuestionario = Cuestionario::with('preguntas')
            ->where('autor_id', $personId)
            ->where('tipo', 'entrevista_tecnica')
            ->whereIn('estado', ['vigente', 'borrador'])
            ->orderByDesc('version')
            ->first();

        if (!$cuestionario) {
            $cuestionario = Cuestionario::create([
                'tipo' => 'entrevista_tecnica',
                'titulo' => 'Formato de Entrevista Técnica',
                'autor_id' => $personId,
                'version' => 1,
                'estado' => 'vigente',
            ]);

            $this->inicializarPreguntasBase($cuestionario);
            $cuestionario->load('preguntas');
        }

        return $cuestionario;
    }

    /**
     * Inicializa las preguntas por defecto en un nuevo formato técnico.
     */
    private function inicializarPreguntasBase(Cuestionario $cuestionario): void
    {
        $base = [
            ['orden' => 1, 'enunciado' => 'Presentación personal', 'puntaje' => 10],
            ['orden' => 2, 'enunciado' => 'Tipo de comunicación (pasiva, agresiva o asertiva)', 'puntaje' => 15],
            ['orden' => 3, 'enunciado' => 'Actitud y proyección personal y laboral', 'puntaje' => 15],
            ['orden' => 4, 'enunciado' => 'Conocimientos técnicos del cargo', 'puntaje' => 30],
            ['orden' => 5, 'enunciado' => '¿Cómo programaría el riego de un cultivo de cacao en época seca?', 'puntaje' => 15],
            ['orden' => 6, 'enunciado' => '¿Qué registros de producción llevaría en su unidad y para qué?', 'puntaje' => 15],
        ];

        foreach ($base as $b) {
            CuestionarioPregunta::create([
                'cuestionario_id' => $cuestionario->id,
                'orden' => $b['orden'],
                'enunciado' => $b['enunciado'],
                'tipo' => 'escala',
                'puntaje_maximo' => $b['puntaje'],
            ]);
        }
    }

    /**
     * Crea una nueva versión si ya existen calificaciones o reutiliza el cuestionario actual.
     */
    private function versionarOReutilizarCuestionario(Cuestionario $cuestionarioActual, int $personId): array
    {
        $calificacionesExistentes = Calificacion::where('cuestionario_id', $cuestionarioActual->id)->count();

        if ($calificacionesExistentes > 0) {
            $cuestionarioActual->update(['estado' => 'inactivo']);

            $nuevo = Cuestionario::create([
                'tipo' => $cuestionarioActual->tipo,
                'titulo' => $cuestionarioActual->titulo,
                'autor_id' => $personId,
                'version' => $cuestionarioActual->version + 1,
                'version_anterior_id' => $cuestionarioActual->id,
                'estado' => 'vigente',
            ]);

            return [$nuevo, "Se creó y guardó la Versión {$nuevo->version} de tu formato."];
        }

        $cuestionarioActual->preguntas()->delete();
        return [$cuestionarioActual, 'Formato de entrevista guardado exitosamente.'];
    }

    /**
     * Registra las preguntas en el cuestionario especificado.
     */
    private function registrarPreguntasCuestionario(Cuestionario $cuestionario, array $preguntas): void
    {
        $orden = 1;
        foreach ($preguntas as $p) {
            CuestionarioPregunta::create([
                'cuestionario_id' => $cuestionario->id,
                'orden' => $orden++,
                'enunciado' => $p['enunciado'],
                'tipo' => 'escala',
                'puntaje_maximo' => $p['puntaje'],
            ]);
        }
    }

    /**
     * Resuelve el cuestionario vigente del evaluador o el institucional general.
     */
    private function resolverCuestionarioVigente(int $personId): ?Cuestionario
    {
        return Cuestionario::with('preguntas')
            ->where('autor_id', $personId)
            ->where('tipo', 'entrevista_tecnica')
            ->where('estado', 'vigente')
            ->first() ?? Cuestionario::with('preguntas')->where('tipo', 'entrevista_tecnica')->first();
    }

    /**
     * Registra o actualiza la calificación del aspirante.
     */
    private function registrarCalificacionAspirante(Request $request, int $entrevistaId, int $postulacionId, int $personId): float
    {
        $totalPuntaje = (float) array_sum($request->puntajes);

        Calificacion::updateOrCreate(
            [
                'entrevista_id' => $entrevistaId,
                'postulacion_id' => $postulacionId,
                'evaluador_id' => $personId,
            ],
            [
                'cuestionario_id' => $request->cuestionario_id,
                'puntaje' => $totalPuntaje,
                'concepto' => $request->concepto,
                'observaciones' => $request->observaciones,
                'detalle' => $request->puntajes,
            ]
        );

        return $totalPuntaje;
    }
}
