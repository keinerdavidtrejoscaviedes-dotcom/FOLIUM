<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Services\FaseService;
use Modules\TalentoHumano\Support\ActaSeleccionados;
use Modules\TalentoHumano\Support\ComiteReubicacion;

/**
 * Supervisión del proceso en modo de solo lectura (HU-014; HU-037, esc. 5; mockups ADM-14 a ADM-16).
 * No muestra puntajes, resultados psicotécnicos ni calificaciones individuales.
 */
class SupervisionController extends Controller
{
    /** Pasos del empalme que se muestran en el avance (ADM-15). */
    public const PASOS_EMPALME = ['Acta generada', 'Inventario verificado', 'Visita a la unidad', 'Firmas', 'Visto bueno'];

    public function __construct(private FaseService $fases)
    {
    }

    /**
     * Selección: cronograma aprobado, convocatoria completa, avance y bloqueos (HU-014, esc. 1, 2 y 5).
     */
    public function seleccion(Request $request)
    {
        $fase = $this->fase($request);
        if (!$fase) {
            return view('talentohumano::lider.supervision.seleccion', ['fase' => null, 'fichas' => collect(), 'convocatoria' => null]);
        }

        $cronograma = Cronograma::with(['etapas', 'aprobadoPor'])->where('fase_id', $fase->id)->where('estado', 'aprobado')->latest('fecha_aprobacion')->first()
            ?? Cronograma::with('etapas')->where('fase_id', $fase->id)->latest('id')->first();
        $convocatoria = Convocatoria::with(['convocatoriaCargos.cargoActivado.cargo', 'publicadaPor'])->where('fase_id', $fase->id)->first();
        $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria->id ?? 0);
        $avance = $convocatoria ? $this->avance($convocatoria, $postulaciones) : null;

        return view('talentohumano::lider.supervision.seleccion', [
            'fase' => $fase,
            'cronograma' => $cronograma,
            'etapas' => $cronograma ? $this->etapas($cronograma) : [],
            'convocatoria' => $convocatoria,
            'fichas' => $this->fases->fichasDeLaFase($fase->id),
            'postulados' => (clone $postulaciones)->count(),
            'avance' => $avance,
            'evaluadores' => $convocatoria ? ComiteReubicacion::integrantes($convocatoria) : collect(),
            'bloqueos' => $this->bloqueos($fase, $cronograma, $convocatoria, $avance),
            'hayEmpalmes' => DB::table('th_empalmes')->where('fase_id', $fase->id)->exists(),
        ]);
    }

    /**
     * Seguimiento de empalmes: avance de cada uno por pasos, sin modificar inventarios, firmas ni actas (HU-014, esc. 3).
     */
    public function empalmes(Request $request)
    {
        $fase = $this->fase($request);
        $todos = $fase
            ? Empalme::with(['cargo.area', 'cargoReceptor', 'participantes.asignacion.persona', 'inventarios', 'acta.firmas', 'vistoBuenoPor'])
                ->where('fase_id', $fase->id)->get()->map(fn ($e) => $this->conAvance($e))
            : collect();

        $filtros = $request->only(['estado', 'area', 'tipo']);
        $empalmes = $todos
            ->when($filtros['estado'] ?? null, fn ($c, $v) => $c->where('situacion', $v))
            ->when($filtros['area'] ?? null, fn ($c, $v) => $c->filter(fn ($e) => $e->cargo?->area_id == $v))
            ->when($filtros['tipo'] ?? null, fn ($c, $v) => $c->where('tipo', $v))
            ->sortBy(fn ($e) => $e->cargo?->nombre)->values();
        $etapa = $fase ? Cronograma::with('etapas')->where('fase_id', $fase->id)->where('estado', 'aprobado')->latest('fecha_aprobacion')->first()?->etapas->firstWhere('etapa', 'empalme') : null;

        return view('talentohumano::lider.supervision.empalmes', [
            'fase' => $fase,
            'todos' => $todos,
            'empalmes' => $empalmes,
            'filtros' => $filtros,
            'areas' => $todos->pluck('cargo.area')->filter()->unique('id')->sortBy('nombre'),
            'pendientesEntrega' => $todos->where('situacion', '!=', 'Cerrado')->sum(fn ($e) => $e->salientes->count()),
            'etapaEmpalme' => $etapa,
        ]);
    }

    /**
     * Actas de la fase e historial de fases anteriores (HU-014, esc. 4; HU-037, esc. 5).
     */
    public function actas(Request $request)
    {
        $fase = $this->fase($request);
        $actaSeleccionados = $fase ? Acta::with('publicadaPor')->where('fase_id', $fase->id)->where('tipo', 'seleccionados')->where('estado', 'publicada')->latest('id')->first() : null;
        $convocatoria = $fase ? Convocatoria::where('fase_id', $fase->id)->first() : null;

        return view('talentohumano::lider.supervision.actas', [
            'fase' => $fase,
            'cronograma' => $fase ? Cronograma::where('fase_id', $fase->id)->where('estado', 'aprobado')->latest('fecha_aprobacion')->first() : null,
            'actaSeleccionados' => $actaSeleccionados,
            'seleccionados' => $actaSeleccionados && $convocatoria ? ActaSeleccionados::filas($convocatoria->id) : collect(),
            'porValidar' => $convocatoria ? $this->cargosPorValidar($convocatoria) : null,
            'recibimiento' => $fase ? DB::table('th_actas')->where('fase_id', $fase->id)->where('tipo', 'recibimiento')->selectRaw("count(*) as total, sum(estado = 'firmada') as firmadas")->first() : null,
            'historial' => $this->historial(),
        ]);
    }

    /**
     * Fase supervisada: la indicada (desde el historial) o la fase en curso.
     */
    private function fase(Request $request): ?Fase
    {
        return $request->filled('fase') ? Fase::findOrFail((int) $request->query('fase')) : Fase::actual();
    }

    /**
     * Etapas del cronograma con su situación respecto a hoy: hecho, actual o pendiente.
     */
    private function etapas(Cronograma $cronograma): array
    {
        $hoy = today();
        $guardadas = $cronograma->etapas->keyBy('etapa');

        return collect(CronogramaController::ETAPAS)->map(function ($etapa, $clave) use ($guardadas, $hoy) {
            $fila = $guardadas[$clave] ?? null;
            $fin = $fila?->fecha_fin ?? $fila?->fecha_inicio;

            return [
                'nombre' => $etapa['nombre'],
                'fechas' => !$fila ? 'Sin fecha' : $fila->fecha_inicio->format('d/m/Y') . ($fila->fecha_fin && !$fila->fecha_fin->equalTo($fila->fecha_inicio) ? ' – ' . $fila->fecha_fin->format('d/m/Y') : ''),
                'situacion' => match (true) {
                    !$fila => 'pendiente',
                    $fin->lt($hoy) => 'hecho',
                    $fila->fecha_inicio->lte($hoy) => 'actual',
                    default => 'pendiente',
                },
            ];
        })->values()->all();
    }

    /**
     * Avance de la selección en cantidades, sin puntajes (ADM-14).
     */
    private function avance(Convocatoria $convocatoria, $postulaciones): array
    {
        $ids = (clone $postulaciones)->select('id');
        $total = (clone $postulaciones)->count();
        $pruebas = DB::table('th_aplicaciones_prueba')->whereIn('postulacion_id', $ids);
        $cargos = $convocatoria->convocatoriaCargos->count();

        return [
            ['Hojas de vida analizadas', DB::table('th_analisis_hv')->whereIn('postulacion_id', $ids)->distinct()->count('postulacion_id'), $total],
            ['Pruebas presentadas', (clone $pruebas)->where('estado', 'presentada')->count(), (clone $pruebas)->count()],
            ['Entrevistas calificadas', DB::table('th_calificaciones')->whereIn('postulacion_id', $ids)->distinct()->count('postulacion_id'), $total],
            ['Cargos validados', $cargos - $this->cargosPorValidar($convocatoria), $cargos],
            'no_evaluados' => (clone $pruebas)->where('estado', 'no_evaluado')->count(),
            'por_reubicar' => ComiteReubicacion::pendientes($convocatoria)->count(),
        ];
    }

    private function cargosPorValidar(Convocatoria $convocatoria): int
    {
        return DB::table('th_convocatoria_cargos as cc')->where('cc.convocatoria_id', $convocatoria->id)
            ->whereNotExists(fn ($q) => $q->from('th_selecciones as s')->whereColumn('s.convocatoria_cargo_id', 'cc.id')->whereIn('s.estado', ['validada', 'ajustada']))
            ->count();
    }

    /**
     * Lo que detiene el avance del proceso (HU-014, esc. 2; RF-002).
     */
    private function bloqueos(Fase $fase, ?Cronograma $cronograma, ?Convocatoria $convocatoria, ?array $avance): array
    {
        $bloqueos = [];
        $ultimo = DB::table('th_cronogramas')->where('fase_id', $fase->id)->latest('id')->first();

        if ($ultimo?->estado === 'enviado') {
            $bloqueos[] = ['fa-hourglass-half', 'amber', 'Cronograma en aprobación', 'Mientras el Instructor Líder no lo apruebe, el Gestor de Talento Humano no puede publicar la convocatoria.'];
        } elseif ($ultimo?->estado === 'rechazado') {
            $bloqueos[] = ['fa-rotate-left', 'amber', 'Cronograma devuelto al Gestor de Talento Humano', "Motivo: «{$ultimo->motivo_rechazo}». La convocatoria no se puede publicar hasta que se apruebe."];
        } elseif ($convocatoria && $convocatoria->estado === 'borrador' && $cronograma?->estado === 'aprobado') {
            $bloqueos[] = ['fa-bullhorn', 'amber', 'Convocatoria sin publicar', 'El cronograma está aprobado y la convocatoria sigue en borrador.'];
        }

        if ($avance && $avance['no_evaluados']) {
            $bloqueos[] = ['fa-lock', 'red', "Entrevistas bloqueadas para {$avance['no_evaluados']} postulantes", 'No presentaron la prueba psicotécnica dentro del plazo y quedaron como «no evaluado».'];
        }

        $acta = $cronograma?->etapas->firstWhere('etapa', 'acta_seleccionados');
        $porValidar = $convocatoria && in_array($convocatoria->estado, ['cerrada', 'finalizada']) ? $this->cargosPorValidar($convocatoria) : 0;
        if ($porValidar) {
            $bloqueos[] = ['fa-hourglass-half', 'amber', 'El acta de seleccionados no se puede publicar todavía',
                "Faltan {$porValidar} cargos por validar." . ($acta ? ' La publicación está programada para el ' . $acta->fecha_inicio->format('d/m/Y') . '.' : '')];
        }

        return $bloqueos;
    }

    /**
     * Estado de cada paso (1 hecho, 0.5 con novedad, 0 pendiente) y la situación del empalme.
     */
    private function conAvance(Empalme $empalme): Empalme
    {
        $inventario = $empalme->inventarios;
        $novedades = $inventario->filter(fn ($i) => filled($i->novedad));
        $firmas = $empalme->acta?->firmas->count() ?? 0;
        $firmado = $empalme->acta?->estado === 'firmada' || in_array($empalme->estado, ['firmado', 'cerrado']);

        $empalme->pasos = [
            $empalme->acta ? 1 : 0,
            $novedades->isNotEmpty() ? 0.5 : ($inventario->isNotEmpty() && $inventario->every('verificado') ? 1 : 0),
            $empalme->visita_confirmada ? 1 : 0,
            $firmado ? 1 : ($firmas ? 0.5 : 0),
            $empalme->visto_bueno_por || $empalme->estado === 'cerrado' ? 1 : 0,
        ];
        $empalme->detalle = [
            $empalme->acta ? 'Acta generada el ' . $empalme->acta->created_at->format('d/m/Y') : 'Acta sin generar',
            $inventario->isEmpty() ? 'Inventario sin registrar'
                : "Inventario: {$inventario->where('verificado', true)->count()} de {$inventario->count()} ítems verificados"
                    . ($novedades->isNotEmpty() ? ' · novedades: ' . $novedades->map(fn ($i) => "«{$i->item}: {$i->novedad}»")->implode(', ') : ''),
            $empalme->visita_confirmada ? 'Visita a la unidad confirmada' : 'Visita a la unidad pendiente',
            $firmado ? 'Acta firmada' : "Firmas registradas: {$firmas}",
            $empalme->vistoBuenoPor ? 'Visto bueno de ' . trim($empalme->vistoBuenoPor->first_name . ' ' . $empalme->vistoBuenoPor->first_last_name) . ($empalme->fecha_visto_bueno ? ' el ' . $empalme->fecha_visto_bueno->format('d/m/Y') : '') : 'Visto bueno del Gestor de Talento Humano pendiente',
        ];
        $empalme->situacion = match (true) {
            // El visto bueno del Gestor cierra el empalme, aunque su estado no se haya actualizado
            $empalme->estado === 'cerrado' || $empalme->visto_bueno_por => 'Cerrado',
            $novedades->isNotEmpty() => 'Con novedad',
            $empalme->estado === 'pendiente' && !array_filter($empalme->pasos) => 'Sin iniciar',
            default => 'En curso',
        };
        $nombre = fn ($p) => $p->asignacion?->persona ? trim($p->asignacion->persona->first_name . ' ' . $p->asignacion->persona->first_last_name) : 'Sin registro';
        $empalme->salientes = $empalme->participantes->where('papel', 'saliente')->map($nombre)->values();
        $empalme->receptores = $empalme->participantes->where('papel', 'receptor')->map($nombre)->values();

        return $empalme;
    }

    /**
     * Fases con sus cifras principales, de la más reciente a la más antigua.
     */
    private function historial()
    {
        return Fase::orderByDesc('numero')->get()->map(function (Fase $fase) {
            $convocatoria = DB::table('th_convocatorias')->whereNull('deleted_at')->where('fase_id', $fase->id)->value('id');
            $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria ?? 0);
            $empalmes = DB::table('th_empalmes')->where('fase_id', $fase->id);

            return (object) [
                'fase' => $fase,
                'cargos' => DB::table('th_cargos_activados')->where('fase_id', $fase->id)->count(),
                'postulados' => $convocatoria ? (clone $postulaciones)->count() : null,
                'reubicados' => $convocatoria ? DB::table('th_reubicaciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->count() : null,
                'empalmes' => (clone $empalmes)->count(),
                'cerrados' => (clone $empalmes)->where('estado', 'cerrado')->count(),
                'cargaInicial' => DB::table('th_asignaciones')->where('fase_id', $fase->id)->where('origen', 'carga_inicial')->exists(),
            ];
        });
    }
}
