<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\AvisosGestor;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\ProcesoSeleccion;
use Modules\TalentoHumano\Support\PruebaVencida;
use Modules\TalentoHumano\Support\PropuestaSeleccion;

/**
 * El Gestor de TH lanza el cálculo de la propuesta de selección y la envía a validación del Instructor Líder y del
 * Instructor de TH, sin ver puntajes (HU-034, RF-010, RN-019, RN-020, RN-063). El cálculo es el de PropuestaSeleccion.
 */
class CalculoSeleccionController extends Controller
{
    public function show(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        PruebaVencida::sincronizar($convocatoria);

        if (!$convocatoria) {
            return view('talentohumano::proceso.seleccion', ['fase' => $fase, 'convocatoria' => null]);
        }

        $calculo = PropuestaSeleccion::calcular($convocatoria);
        $nombres = $this->nombres($convocatoria);

        return view('talentohumano::proceso.seleccion', [
            'fase' => $fase,
            'convocatoria' => $convocatoria,
            'pendientes' => $this->calificacionesPendientes($convocatoria),
            // Escenario 2: los no evaluados quedan aparte
            'noEvaluados' => DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria->id)->where('estado', 'no_evaluado')->pluck('id')->map(fn ($id) => $nombres[$id] ?? ''),
            // Solo nombres y estado de cada cargo: el Gestor no ve puntajes (escenario 1, RN-063)
            'cargos' => $calculo->cargos->map(fn ($c) => (object) [
                'nombre' => $c->nombre,
                'cupos' => $c->cupos,
                'estado' => match (true) {
                    $c->validado => 'validado',
                    (bool) $c->empatados => 'empate',
                    $c->vacante => 'vacante',
                    (bool) $c->propuestos => 'propuesta',
                    default => 'sin_candidatos',
                },
                'propuestos' => collect($c->validado ? $c->validados : $c->propuestos)->map(fn ($id) => $nombres[$id] ?? '')->all(),
                'sin_aptos' => $c->sin_aptos,
            ])->sortBy('nombre')->values(),
            'propuestaGuardada' => DB::table('th_selecciones as s')->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
                ->where('cc.convocatoria_id', $convocatoria->id)->exists(),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'programacion'),
        ]);
    }

    /**
     * 'Calcular selección' (HU-034 esc. 1 a 7).
     */
    public function calcular(Request $request, int $fase)
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'programacion')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);

        // Escenario 6: no se calcula mientras falte alguna calificación
        if (($pendientes = $this->calificacionesPendientes($convocatoria))->isNotEmpty()) {
            AvisosGestor::prerrequisito($fase, 'Propuesta de selección', 'Calificaciones pendientes: ' . $pendientes->implode('; '));

            return back()->with('error', 'No se calculó la selección: hay postulantes con calificaciones pendientes.');
        }
        if (!DB::table('th_calificaciones')->join('th_entrevistas as e', 'e.id', '=', 'th_calificaciones.entrevista_id')->where('e.convocatoria_id', $convocatoria->id)->exists()) {
            return back()->with('error', 'Aún no hay entrevistas calificadas en la convocatoria.');
        }

        $calculo = PropuestaSeleccion::sincronizar($convocatoria);
        Auditoria::registrar('seleccion_calculada', $convocatoria, [], [
            'cargos' => $calculo->cargos->count(),
            'empates' => $calculo->cargos->filter(fn ($c) => $c->empatados)->count(),
            'vacantes' => $calculo->cargos->where('vacante', true)->count(),
        ]);

        // Se envía a validación del Líder de la estrategia y del Instructor de TH
        $validadores = DB::table('th_designaciones')->whereIn('tipo', ['lider_estrategia', 'instructor_th'])
            ->whereNull('fecha_retiro')->whereNull('deleted_at')->pluck('person_id');
        Correo::enviar(ProcesoSeleccion::correos($validadores), "FOLIUM: propuesta de selección de la {$fase->nombre()} para validar",
            "FOLIUM · Talento Humano\n\nEl Gestor de Talento Humano calculó la propuesta de selección de la convocatoria «{$convocatoria->titulo}».\n\n"
            . 'Cargos: ' . $calculo->cargos->count() . ' · con empate por resolver: ' . $calculo->cargos->filter(fn ($c) => $c->empatados)->count()
            . ' · vacantes: ' . $calculo->cargos->where('vacante', true)->count() . "\n\nValide o ajuste cada cargo en " . route('talentohumano.seleccion.index'));

        return back()->with('success', 'Selección calculada y enviada a validación del Instructor Líder y del Instructor de Talento Humano.');
    }

    /**
     * Aspirante, entrevista y evaluadores que aún no registran su calificación.
     */
    private function calificacionesPendientes(Convocatoria $convocatoria)
    {
        $nombres = $this->nombres($convocatoria);
        $calificadas = DB::table('th_calificaciones')->get(['entrevista_id', 'postulacion_id', 'evaluador_id'])
            ->map(fn ($c) => "{$c->entrevista_id}-{$c->postulacion_id}-{$c->evaluador_id}")->flip();

        return DB::table('th_entrevistas as e')
            ->join('th_entrevista_aspirantes as ea', 'ea.entrevista_id', '=', 'e.id')
            ->join('th_entrevista_evaluadores as ev', 'ev.entrevista_id', '=', 'e.id')
            ->join('people as p', 'p.id', '=', 'ev.person_id')
            ->where('e.convocatoria_id', $convocatoria->id)->whereNull('e.deleted_at')
            ->get(['e.id', 'e.tipo', 'ea.postulacion_id', 'ev.person_id', 'p.first_name', 'p.first_last_name'])
            ->reject(fn ($f) => isset($calificadas["{$f->id}-{$f->postulacion_id}-{$f->person_id}"]))
            ->groupBy(fn ($f) => "{$f->postulacion_id}-{$f->tipo}")
            ->map(fn ($filas) => ($nombres[$filas->first()->postulacion_id] ?? '') . ' · entrevista ' . ($filas->first()->tipo === 'tecnica' ? 'técnica' : 'psicológica')
                . ': falta ' . $filas->map(fn ($f) => trim("{$f->first_name} {$f->first_last_name}"))->implode(', '))
            ->values();
    }

    private function nombres(Convocatoria $convocatoria)
    {
        return DB::table('th_postulaciones as po')->join('people as p', 'p.id', '=', 'po.person_id')
            ->where('po.convocatoria_id', $convocatoria->id)
            ->selectRaw("po.id, TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name)) as nombre")
            ->pluck('nombre', 'id');
    }
}
