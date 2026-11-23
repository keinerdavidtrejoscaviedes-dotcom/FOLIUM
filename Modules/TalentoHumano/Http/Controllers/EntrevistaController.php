<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\PropuestaSeleccion;

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

        // Mis calificaciones de procesos terminados: solo puntaje y concepto, sin datos del aspirante (HU-041B esc. 3, HU-042B esc. 4)
        $terminadas = Calificacion::with('entrevista')->where('evaluador_id', $personId)
            ->whereHas('entrevista.convocatoria', fn ($q) => $q->where('estado', 'finalizada'))
            ->latest('updated_at')->get();

        return view('talentohumano::entrevistas.index', array_merge([
            'convocatoria' => $convocatoria,
            'fase' => $fase,
            'entrevistas' => $entrevistas,
            'user' => $user,
            'terminadas' => $terminadas,
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
        ], [
            // HU-040A esc. 3 y 4
            'preguntas.required' => 'Agregue al menos una pregunta.',
            'preguntas.*.enunciado.required' => 'Escriba el enunciado de cada pregunta.',
            'preguntas.*.puntaje.required' => 'Asigne un puntaje numérico a la pregunta.',
            'preguntas.*.puntaje.numeric' => 'El puntaje debe ser un número.',
            'preguntas.*.puntaje.min' => 'El puntaje debe ser mayor que cero.',
        ]);

        $personId = auth()->user()->person_id ?? 1;

        // Solo el formato técnico vigente del propio evaluador: no el de otro evaluador ni la prueba psicotécnica (HU-040A)
        $cuestionarioActual = Cuestionario::where('autor_id', $personId)->where('tipo', 'entrevista_tecnica')
            ->whereIn('estado', ['vigente', 'borrador'])->find($request->cuestionario_id);
        if (!$cuestionarioActual) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        // Bloque: Control de versiones histórico si ya cuenta con calificaciones
        [$cuestionarioTarget, $mensaje] = $this->versionarOReutilizarCuestionario($cuestionarioActual, $personId);

        // Bloque: Registro de nuevas preguntas en el cuestionario destino
        $this->registrarPreguntasCuestionario($cuestionarioTarget, $request->preguntas);
        Auditoria::registrar('formato_entrevista_guardado', $cuestionarioTarget, [], ['preguntas' => count($request->preguntas), 'resultado' => $mensaje]);

        return back()->with('success', $mensaje);
    }

    /**
     * Vista de calificación de una entrevista a un aspirante (HU-041).
     */
    public function calificar($entrevistaId, $postulacionId)
    {
        $personId = auth()->user()->person_id ?? 1;
        $this->verificarEvaluador((int) $entrevistaId, (int) $postulacionId, $personId);

        $entrevista = Entrevista::with('convocatoria')->findOrFail($entrevistaId);
        // HU-042B esc. 4: terminado el proceso, el evaluador ya no ve los datos de los aspirantes
        if ($entrevista->convocatoria?->estado === 'finalizada') {
            return redirect()->route('talentohumano.entrevistas.index')->with('info', 'El proceso de selección terminó: solo puede consultar sus propias calificaciones.');
        }
        $postulacion = Postulacion::with(['person', 'course.program', 'opciones.convocatoriaCargo.cargoActivado.cargo'])->findOrFail($postulacionId);

        // Bloques: Cuestionario aplicable y calificación previa
        $cuestionario = $this->resolverCuestionarioVigente($personId, $entrevista);
        if (!$cuestionario) {
            // Cada evaluador califica con su propio formato del tipo de la entrevista (HU-040, RN-018)
            return $entrevista->tipo === 'psicologica'
                ? redirect()->route('talentohumano.formato.edit')->with('warning', 'Primero arme su formato de entrevista psicológica para poder calificar.')
                : redirect()->route('talentohumano.entrevistas.formato')->with('warning', 'Primero arme su formato de entrevista técnica para poder calificar.');
        }
        $calificacion = Calificacion::where('entrevista_id', $entrevista->id)
            ->where('postulacion_id', $postulacion->id)
            ->where('evaluador_id', $personId)
            ->first();

        // HU-041B: las de los demás evaluadores solo se ven después de registrar la propia (RN-043)
        $otras = $calificacion
            ? Calificacion::with('evaluador')->where('entrevista_id', $entrevista->id)->where('postulacion_id', $postulacion->id)
                ->where('evaluador_id', '!=', $personId)->get()
            : collect();
        $umbral = (float) ($entrevista->convocatoria?->umbral ?? 0);

        // HU-041A esc. 7: la entrevista grupal se califica en un solo formulario, un bloque por cada aprendiz del grupo
        $grupo = $entrevista->modalidad === 'grupal'
            ? $entrevista->postulaciones()->with(['person', 'course'])->orderBy('th_postulaciones.id')->get()
            : collect([$postulacion]);
        $calificacionesGrupo = Calificacion::where('entrevista_id', $entrevista->id)->where('evaluador_id', $personId)
            ->whereIn('postulacion_id', $grupo->pluck('id'))->get()->keyBy('postulacion_id');

        return view('talentohumano::entrevistas.calificar', compact(
            'entrevista',
            'postulacion',
            'cuestionario',
            'calificacion',
            'otras',
            'umbral',
            'grupo',
            'calificacionesGrupo'
        ));
    }

    /**
     * Guarda la calificación individual del evaluador (HU-041 · RF-009).
     */
    public function guardarCalificacion(Request $request, $entrevistaId, $postulacionId)
    {
        $request->validate(['cuestionario_id' => 'required|integer']);

        $personId = auth()->user()->person_id ?? 1;
        $this->verificarEvaluador((int) $entrevistaId, (int) $postulacionId, $personId);

        $entrevista = Entrevista::with('convocatoria')->findOrFail($entrevistaId);
        if ($entrevista->convocatoria?->estado === 'finalizada') {
            return redirect()->route('talentohumano.entrevistas.index')->with('error', 'El proceso de selección terminó: ya no se pueden registrar calificaciones.');
        }

        // La calificación se registra con el formato vigente del evaluador para ese tipo de entrevista
        $cuestionario = $this->resolverCuestionarioVigente($personId, $entrevista);
        abort_unless($cuestionario && (int) $request->cuestionario_id === $cuestionario->id, 422, 'El formato de la entrevista cambió; vuelva a abrir la calificación.');

        // HU-041A esc. 7: la entrevista grupal se califica completa, cada aprendiz con sus puntajes y observaciones;
        // la individual, solo el aspirante. Los datos llegan como puntajes[postulación][pregunta] y observaciones[postulación]
        $grupal = $entrevista->modalidad === 'grupal';
        $ids = $grupal ? $entrevista->aspirantes()->pluck('postulacion_id')->map(fn ($id) => (int) $id)->all() : [(int) $postulacionId];
        $nombres = $grupal ? Postulacion::with('person')->whereIn('id', $ids)->get()->mapWithKeys(fn ($p) => [$p->id => $p->nombre_completo])->all() : [];
        $puntajes = fn ($id) => $grupal ? (array) $request->input("puntajes.{$id}", []) : (array) $request->input('puntajes', []);
        $observaciones = fn ($id) => trim((string) ($grupal ? $request->input("observaciones.{$id}") : $request->input('observaciones')));
        $campo = fn ($id, $resto) => $grupal ? "{$resto[0]}.{$id}" . (isset($resto[1]) ? ".{$resto[1]}" : '') : $resto[0] . (isset($resto[1]) ? ".{$resto[1]}" : '');

        // HU-041A esc. 2 y 3: cada pregunta con un puntaje entre 0 y su máximo, y observaciones escritas
        $errores = [];
        foreach ($ids as $id) {
            $quien = $grupal ? ' de ' . ($nombres[$id] ?? 'un aprendiz del grupo') : '';
            foreach ($cuestionario->preguntas as $pregunta) {
                $valor = $puntajes($id)[$pregunta->id] ?? null;
                if (!is_numeric($valor) || $valor < 0 || $valor > $pregunta->puntaje_maximo) {
                    $errores[$campo($id, ['puntajes', $pregunta->id])] = "Califique la pregunta «{$pregunta->enunciado}»{$quien} con un puntaje entre 0 y " . (float) $pregunta->puntaje_maximo . '.';
                }
            }
            if ($observaciones($id) === '') {
                $errores[$campo($id, ['observaciones'])] = 'Escriba sus observaciones de la entrevista' . $quien . '.';
            }
        }
        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        // Bloque: Persistir evaluación y cálculo de puntaje total (todo el grupo o nada)
        $resultados = DB::transaction(fn () => collect($ids)->mapWithKeys(fn ($id) => [
            $id => $this->registrarCalificacionAspirante($puntajes($id), $observaciones($id), $cuestionario, $entrevista, $id, $personId),
        ]));
        // El puntaje total y la propuesta de selección se actualizan con cada calificación (RF-010)
        PropuestaSeleccion::sincronizar($entrevista->convocatoria);
        foreach ($resultados as $id => $total) {
            $calificacion = Calificacion::where('entrevista_id', $entrevista->id)->where('postulacion_id', $id)->where('evaluador_id', $personId)->first();
            Auditoria::registrar('calificacion_registrada', $calificacion, [], [
                'entrevista' => (int) $entrevistaId, 'postulacion' => $id, 'puntaje' => $total, 'concepto' => $calificacion->concepto,
            ]);
        }

        if ($grupal) {
            return redirect()->route('talentohumano.entrevistas.index')
                ->with('success', 'Calificaciones del grupo guardadas (' . count($ids) . ' aprendices).');
        }
        $total = $resultados[(int) $postulacionId];

        return redirect()->route('talentohumano.entrevistas.index')
            ->with('success', "Calificación guardada exitosamente ({$total} de 100 · " . ($total >= (float) ($entrevista->convocatoria?->umbral ?? 0) ? 'Apto' : 'No apto') . ').');
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Solo califica quien es evaluador de esa entrevista y a un aspirante citado a ella (HU-043; HU-002, esc. 4).
     */
    private function verificarEvaluador(int $entrevistaId, int $postulacionId, int $personId): void
    {
        $esEvaluador = EntrevistaEvaluador::where('entrevista_id', $entrevistaId)->where('person_id', $personId)->exists();
        $estaCitado = EntrevistaAspirante::where('entrevista_id', $entrevistaId)->where('postulacion_id', $postulacionId)->exists();

        if (!$esEvaluador || !$estaCitado) {
            Auditoria::registrarIntentoDenegado(request());

            // Un evaluador (instructor o psicólogo) que no fue convocado recibe el motivo (HU-041A esc. 5 y 6);
            // los demás usuarios y el aspirante no asignado, el mensaje general (HU-027C esc. 2, HU-042B esc. 2)
            $funciones = \Modules\TalentoHumano\Support\AccesoTH::funciones(auth()->user());
            $esPsicologo = in_array('psicologo', $funciones);
            $esInstructor = (bool) array_intersect(['instructor', 'instructor_th', 'instructor_tecnico', 'lider_estrategia'], $funciones);
            $tipo = Entrevista::whereKey($entrevistaId)->value('tipo');
            abort(403, match (true) {
                $esEvaluador => 'No tiene permisos para realizar esta acción',
                $esPsicologo && $tipo === 'tecnica' => 'La entrevista técnica la califican solo los instructores técnicos convocados o el Instructor Líder',
                $esPsicologo || $esInstructor => 'No está asignado como evaluador de esta entrevista',
                default => 'No tiene permisos para realizar esta acción',
            });
        }
    }

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
        // HU-042B esc. 4: las de procesos terminados salen de la lista (se consultan aparte, sin datos de aspirantes)
        return Entrevista::whereHas('evaluadores', function ($q) use ($personId) {
            $q->where('person_id', $personId);
        })
        ->whereHas('convocatoria', fn ($q) => $q->where('estado', '!=', 'finalizada'))
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
        // Se cuenta por aspirante: una entrevista grupal tiene una calificación por cada aprendiz del grupo
        $totalAsignadas = $entrevistas->sum(fn ($e) => $e->postulaciones->count());
        $totalCalificadas = $entrevistas->sum(fn ($e) => $e->postulaciones->filter(fn ($p) => $e->calificaciones->contains('postulacion_id', $p->id))->count());
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
     * Formato vigente del evaluador para el tipo de la entrevista: técnico del instructor o psicológico del psicólogo.
     */
    private function resolverCuestionarioVigente(int $personId, Entrevista $entrevista): ?Cuestionario
    {
        return Cuestionario::with('preguntas')
            ->where('autor_id', $personId)
            ->where('tipo', $entrevista->tipo === 'psicologica' ? 'entrevista_psicologica' : 'entrevista_tecnica')
            ->where('estado', 'vigente')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Registra o actualiza la calificación del aspirante.
     */
    private function registrarCalificacionAspirante(array $puntajes, string $observaciones, Cuestionario $cuestionario, Entrevista $entrevista, int $postulacionId, int $personId): float
    {
        // RF-009: el puntaje de cada evaluador se lleva a una escala común de 0 a 100
        $detalle = $cuestionario->preguntas->mapWithKeys(fn ($p) => [$p->id => (float) ($puntajes[$p->id] ?? 0)])->all();
        $maximo = (float) $cuestionario->preguntas->sum('puntaje_maximo');
        $totalPuntaje = $maximo > 0 ? round(array_sum($detalle) * 100 / $maximo, 2) : 0.0;

        Calificacion::updateOrCreate(
            [
                'entrevista_id' => $entrevista->id,
                'postulacion_id' => $postulacionId,
                'evaluador_id' => $personId,
            ],
            [
                'cuestionario_id' => $cuestionario->id,
                'puntaje' => $totalPuntaje,
                // RN-017: apto si alcanza el umbral de la convocatoria
                'concepto' => $totalPuntaje >= (float) ($entrevista->convocatoria?->umbral ?? 0) ? 'apto' : 'no_apto',
                'observaciones' => $observaciones,
                'detalle' => $detalle,
            ]
        );

        return $totalPuntaje;
    }
}
