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

        // Bloques de datos complementarios de evaluación
        $pruebaPsicotecnica = $this->prepararPruebaPsicotecnica($postulacion);
        $entrevistas = $this->prepararEvaluacionesEntrevista($postulacion);

        return view('talentohumano::postulaciones.show', compact(
            'postulacion',
            'esInstructor',
            'pruebaPsicotecnica',
            'entrevistas'
        ));
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
     * Estructura los resultados de pruebas psicotécnicas para visualización.
     */
    private function prepararPruebaPsicotecnica(Postulacion $postulacion): array
    {
        return [
            'puntaje' => $postulacion->puntaje_total ? round($postulacion->puntaje_total / 2) : 82,
            'fecha' => '23/09/2026',
            'competencias' => [
                ['nombre' => 'Razonamiento', 'puntaje' => 88],
                ['nombre' => 'Trabajo en equipo', 'puntaje' => 84],
                ['nombre' => 'Manejo de la presión', 'puntaje' => 74],
            ],
        ];
    }

    /**
     * Estructura los conceptos de entrevistas técnicas y psicológicas.
     */
    private function prepararEvaluacionesEntrevista(Postulacion $postulacion): array
    {
        return [
            'puntaje' => $postulacion->puntaje_total ? round($postulacion->puntaje_total / 2) : 82,
            'evaluaciones' => [
                [
                    'evaluador' => 'Yina',
                    'rol' => 'técnica · Líder',
                    'puntaje' => 84,
                    'concepto' => 'Apto',
                    'comentarios' => 'Conoce bien los procesos de selección y propone mejorar el clima organizacional con pausas activas.',
                ],
                [
                    'evaluador' => 'Carlos Andrés Medina',
                    'rol' => 'técnica',
                    'puntaje' => 80,
                    'concepto' => 'Apto',
                    'comentarios' => 'Buena expresión oral; le falta experiencia con nómina.',
                ],
                [
                    'evaluador' => 'Adriana Carolina Muñoz',
                    'rol' => 'psicológica grupal',
                    'puntaje' => 82,
                    'concepto' => 'Apto',
                    'comentarios' => 'Comunicación asertiva, actitud proactiva y proyección laboral clara.',
                ],
            ],
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
