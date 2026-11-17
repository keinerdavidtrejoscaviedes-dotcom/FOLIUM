<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Cuestionario;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\AvisosGestor;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\ProcesoSeleccion;
use Modules\TalentoHumano\Support\PruebaVencida;

/**
 * El Gestor de TH habilita la prueba psicotécnica a los postulantes con análisis de hoja de vida, define el plazo
 * y verifica quién la presentó, sin ver respuestas ni puntajes (HU-025A, HU-025B, RN-012, RN-013, RN-063).
 */
class HabilitacionPruebaController extends Controller
{
    public function index(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        PruebaVencida::sincronizar($convocatoria);
        [$inicio, $fin] = ProcesoSeleccion::etapa($fase, 'prueba_psicotecnica');
        $postulantes = $convocatoria ? ProcesoSeleccion::postulantes($convocatoria) : collect();

        return view('talentohumano::proceso.prueba', [
            'fase' => $fase,
            'convocatoria' => $convocatoria,
            'bloqueo' => $this->bloqueo($convocatoria),
            'inicio' => $inicio,
            'fin' => $fin,
            'pruebas' => Cuestionario::where('tipo', 'prueba_psicotecnica')->where('estado', 'vigente')->orderByDesc('updated_at')->get(),
            'postulantes' => $postulantes->each(function ($p) {
                $p->situacion = $this->situacion($p);
            }),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'programacion'),
        ]);
    }

    /**
     * 'Habilitar prueba' (HU-025A esc. 1 a 5). Se puede repetir: solo habilita a quienes aún no la tienen.
     */
    public function habilitar(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);

        if ($bloqueo = $this->bloqueo($convocatoria)) {
            AvisosGestor::prerrequisito($fase, 'Prueba psicotécnica', $bloqueo);

            return back()->with('error', $bloqueo);
        }

        [$inicio, $fin] = ProcesoSeleccion::etapa($fase, 'prueba_psicotecnica');
        $datos = $request->validate([
            'cuestionario_id' => 'required|integer',
            'plazo' => 'required|date|after_or_equal:today',
        ], [
            'cuestionario_id.required' => 'Elija la prueba psicotécnica que presentarán los postulantes.',
            'plazo.required' => 'Defina el plazo para presentar la prueba.',
            'plazo.date' => 'Defina el plazo para presentar la prueba.',
            'plazo.after_or_equal' => 'El plazo no puede ser anterior a hoy.',
        ]);

        $prueba = Cuestionario::where('tipo', 'prueba_psicotecnica')->where('estado', 'vigente')->find($datos['cuestionario_id']);
        if (!$prueba) {
            return back()->withInput()->withErrors(['cuestionario_id' => 'No hay una prueba psicotécnica disponible. Solicítela al Evaluador psicológico.']);
        }

        $plazo = Carbon::parse($datos['plazo'])->startOfDay();
        if ($plazo->lt(today()) || ($inicio && $plazo->lt($inicio)) || ($fin && $plazo->gt($fin))) {
            return back()->withInput()->withErrors(['plazo' => 'El plazo debe estar dentro de las fechas del cronograma aprobado.']);
        }

        $postulantes = ProcesoSeleccion::postulantes($convocatoria)->where('estado', 'postulado')->whereNull('prueba');
        $habilitar = $postulantes->where('analizada', 1);
        $sinAnalisis = $postulantes->where('analizada', 0);

        DB::transaction(function () use ($habilitar, $prueba, $plazo) {
            foreach ($habilitar as $postulante) {
                DB::table('th_aplicaciones_prueba')->insert([
                    'postulacion_id' => $postulante->id,
                    'cuestionario_id' => $prueba->id,
                    'estado' => 'habilitada',
                    'fecha_limite' => $plazo->copy()->endOfDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        if ($habilitar->isNotEmpty()) {
            Auditoria::registrar('prueba_habilitada', $convocatoria, [], [
                'prueba' => $prueba->titulo . ' v' . $prueba->version,
                'plazo' => $plazo->toDateString(),
                'postulantes' => $habilitar->pluck('nombre')->all(),
            ]);
            Correo::enviar(ProcesoSeleccion::correos($habilitar->pluck('person_id')),
                'FOLIUM: ya puede presentar la prueba psicotécnica',
                "FOLIUM · Talento Humano\n\nSu prueba psicotécnica de la convocatoria «{$convocatoria->titulo}» está habilitada.\n\n"
                . 'Plazo para presentarla: hasta el ' . $plazo->format('d/m/Y') . " a las 11:59 p. m.\n\n"
                . 'Preséntela en ' . route('talentohumano.aprendiz.prueba') . "\n\nLa prueba es obligatoria para ser citado a entrevista.");
        }

        // Escenario 2: quienes no tienen análisis quedan listados con el motivo
        return redirect()->route('talentohumano.fases.prueba.index', $fase->id)
            ->with($habilitar->isNotEmpty() ? 'success' : 'info', $habilitar->isNotEmpty()
                ? "Prueba habilitada a {$habilitar->count()} postulantes, con plazo hasta el {$plazo->format('d/m/Y')}."
                : 'No hay postulantes con análisis de hoja de vida pendientes de habilitar.')
            ->with('sinAnalisis', $sinAnalisis->pluck('nombre')->all());
    }

    /**
     * Motivo por el que todavía no se puede habilitar la prueba (RN-004: convocatoria publicada y cerrada).
     */
    private function bloqueo($convocatoria): ?string
    {
        return match (true) {
            !$convocatoria => 'Primero publique la convocatoria de la fase.',
            !$convocatoria->estaCerrada() => 'La prueba se habilita después del cierre de la convocatoria ('
                . $convocatoria->fecha_cierre->format('d/m/Y') . '), cuando ya se hayan analizado las hojas de vida.',
            default => null,
        };
    }

    /**
     * Situación de la prueba de un postulante para el seguimiento (HU-025B): sin mostrar resultados.
     */
    private function situacion(object $postulante): string
    {
        return match (true) {
            $postulante->prueba === 'presentada' => 'presentada',
            $postulante->prueba === 'no_evaluado', $postulante->estado === 'no_evaluado' => 'no_evaluado',
            in_array($postulante->prueba, ['habilitada', 'en_curso']) => 'pendiente',
            !$postulante->analizada => 'sin_analisis',
            default => 'sin_habilitar',
        };
    }

    /**
     * El Administrador consulta esta sección, pero no habilita la prueba (HU-013A).
     */
    private function exigirPermiso(Request $request): void
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'programacion')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }
    }
}
