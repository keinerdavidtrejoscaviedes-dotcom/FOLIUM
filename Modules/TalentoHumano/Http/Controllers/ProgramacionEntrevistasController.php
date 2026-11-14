<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Modules\TalentoHumano\Entities\Entrevista;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\AvisosGestor;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\ProcesoSeleccion;

/**
 * El Gestor de TH programa las entrevistas técnica y psicológica, envía el cronograma por correo y hace seguimiento
 * a qué evaluadores ya calificaron, sin ver las calificaciones (HU-027A, HU-027B, HU-027C, HU-042A, RN-015, RN-063).
 */
class ProgramacionEntrevistasController extends Controller
{
    public const TIPOS = ['tecnica' => 'Técnica', 'psicologica' => 'Psicológica'];

    public function index(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        $bloqueo = $this->bloqueo($convocatoria);

        if ($bloqueo) {
            return view('talentohumano::proceso.entrevistas', ['fase' => $fase, 'bloqueo' => $bloqueo]);
        }

        $entrevistas = Entrevista::with(['postulaciones.person', 'evaluadores.person'])
            ->where('convocatoria_id', $convocatoria->id)->orderBy('fecha_hora')->get();

        // Seguimiento (HU-027C): quién calificó a quién, sin puntajes ni observaciones
        $calificaron = DB::table('th_calificaciones')->whereIn('entrevista_id', $entrevistas->pluck('id'))
            ->get(['entrevista_id', 'postulacion_id', 'evaluador_id'])
            ->groupBy(fn ($c) => "{$c->entrevista_id}-{$c->postulacion_id}")
            ->map(fn ($filas) => $filas->pluck('evaluador_id')->all());

        $postulantes = ProcesoSeleccion::postulantes($convocatoria);
        $citados = DB::table('th_entrevista_aspirantes as ea')->join('th_entrevistas as e', 'e.id', '=', 'ea.entrevista_id')
            ->where('e.convocatoria_id', $convocatoria->id)->whereNull('e.deleted_at')
            ->get(['ea.postulacion_id', 'e.tipo'])->groupBy('tipo')->map(fn ($f) => $f->pluck('postulacion_id')->flip());

        return view('talentohumano::proceso.entrevistas', [
            'fase' => $fase,
            'bloqueo' => null,
            'convocatoria' => $convocatoria,
            'entrevistas' => $entrevistas,
            'calificaron' => $calificaron,
            // Solo se cancela una entrevista que aún no tiene calificaciones
            'conCalificaciones' => DB::table('th_calificaciones')->whereIn('entrevista_id', $entrevistas->pluck('id'))->pluck('entrevista_id')->flip(),
            // Postulantes con la prueba presentada que aún no tienen alguna de las dos entrevistas
            'porCitar' => $postulantes->where('prueba', 'presentada')
                ->each(function ($p) use ($citados) {
                    $p->falta_tecnica = !isset($citados['tecnica'][$p->id]);
                    $p->falta_psicologica = !isset($citados['psicologica'][$p->id]);
                })
                ->filter(fn ($p) => $p->falta_tecnica || $p->falta_psicologica)->values(),
            'sinPrueba' => $postulantes->where('prueba', '!=', 'presentada')->count(),
            'etapa' => ProcesoSeleccion::etapa($fase, 'entrevistas'),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'programacion'),
        ]);
    }

    public function create(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        if ($bloqueo = $this->bloqueo($convocatoria)) {
            AvisosGestor::prerrequisito($fase, 'Entrevistas', $bloqueo);

            return redirect()->route('talentohumano.fases.entrevistas.index', $fase->id)->with('error', $bloqueo);
        }

        $tipo = $request->query('tipo') === 'psicologica' ? 'psicologica' : 'tecnica';
        ProcesoSeleccion::incluirEvaluadoresObligatorios($convocatoria);

        return view('talentohumano::proceso.entrevista-form', [
            'fase' => $fase,
            'tipo' => $tipo,
            'postulantes' => ProcesoSeleccion::postulantes($convocatoria)->where('prueba', 'presentada')->values(),
            'evaluadores' => $convocatoria->evaluadores()->activos()->with('persona')
                ->where('tipo', $tipo === 'psicologica' ? 'psicologico' : 'tecnico')->get(),
            'etapa' => ProcesoSeleccion::etapa($fase, 'entrevistas'),
        ]);
    }

    /**
     * 'Guardar' la citación (HU-027A esc. 1 a 6, HU-027B esc. 1 a 5).
     */
    public function store(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        if ($bloqueo = $this->bloqueo($convocatoria)) {
            AvisosGestor::prerrequisito($fase, 'Entrevistas', $bloqueo);

            return back()->with('error', $bloqueo);
        }

        // Escenario 5: datos de la citación incompletos, se marcan en rojo y se conserva lo diligenciado
        $datos = $request->validate([
            'tipo' => 'required|in:tecnica,psicologica',
            'modalidad' => 'required|in:individual,grupal',
            'postulaciones' => 'required|array|min:1',
            'postulaciones.*' => 'integer',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:150',
            'evaluadores' => 'nullable|array',
            'evaluadores.*' => 'integer',
        ], [
            'postulaciones.required' => 'Elija al aspirante que se citará.',
            'fecha.required' => 'Indique la fecha de la entrevista.',
            'hora.required' => 'Indique la hora de la entrevista.',
            'lugar.required' => 'Indique el lugar de la entrevista.',
        ]);

        $errores = new MessageBag();
        $tipo = $datos['tipo'];
        // Escenario 4 de HU-027A: la técnica es siempre individual
        $modalidad = $tipo === 'tecnica' ? 'individual' : $datos['modalidad'];
        $ids = array_values(array_unique(array_map('intval', $datos['postulaciones'])));

        if ($modalidad === 'individual' && count($ids) > 1) {
            $errores->add('postulaciones', 'La entrevista individual es para un solo aspirante; use la modalidad grupal para varios.');
        }

        // RN-015: solo se cita a quien presentó la prueba psicotécnica
        $postulantes = ProcesoSeleccion::postulantes($convocatoria)->whereIn('id', $ids);
        if ($postulantes->count() !== count($ids)) {
            $errores->add('postulaciones', 'Uno de los aspirantes no pertenece a esta convocatoria.');
        } elseif ($postulantes->contains(fn ($p) => $p->prueba !== 'presentada')) {
            $errores->add('postulaciones', 'La prueba psicotécnica es prerrequisito obligatorio de la entrevista.');
        }

        $yaCitados = DB::table('th_entrevista_aspirantes as ea')->join('th_entrevistas as e', 'e.id', '=', 'ea.entrevista_id')
            ->where('e.convocatoria_id', $convocatoria->id)->where('e.tipo', $tipo)->whereNull('e.deleted_at')
            ->whereIn('ea.postulacion_id', $ids)->pluck('ea.postulacion_id');
        if ($yaCitados->isNotEmpty()) {
            $nombres = $postulantes->whereIn('id', $yaCitados)->pluck('nombre')->implode(', ');
            $errores->add('postulaciones', "Ya tienen entrevista " . mb_strtolower(self::TIPOS[$tipo]) . " programada: {$nombres}.");
        }

        // Escenario 3 (técnica) y 2 (psicológica): al menos un evaluador válido de la convocatoria
        $evaluadores = $convocatoria->evaluadores()->activos()->with('persona')
            ->where('tipo', $tipo === 'psicologica' ? 'psicologico' : 'tecnico')
            ->whereIn('person_id', array_map('intval', $datos['evaluadores'] ?? []))->get();
        if ($evaluadores->isEmpty()) {
            $errores->add('evaluadores', $tipo === 'psicologica'
                ? 'Asigne al menos un Evaluador psicológico.'
                : 'Asigne al menos un instructor técnico o al Instructor Líder.');
        }

        // Escenario 6: dentro del rango de entrevistas del cronograma aprobado
        $fechaHora = Carbon::createFromFormat('Y-m-d H:i', "{$datos['fecha']} {$datos['hora']}");
        [$inicio, $fin] = ProcesoSeleccion::etapa($fase, 'entrevistas');
        if (!$inicio || $fechaHora->lt($inicio) || $fechaHora->gt($fin->copy()->endOfDay())) {
            $errores->add('fecha', 'La fecha debe estar dentro del rango de entrevistas del cronograma aprobado.');
        } elseif ($fechaHora->isPast()) {
            $errores->add('fecha', 'La fecha y la hora de la entrevista no pueden haber pasado.');
        }

        if ($errores->isNotEmpty()) {
            return back()->withErrors($errores)->withInput();
        }

        $entrevista = DB::transaction(function () use ($convocatoria, $tipo, $modalidad, $fechaHora, $datos, $ids, $evaluadores) {
            $entrevista = Entrevista::create([
                'convocatoria_id' => $convocatoria->id,
                'tipo' => $tipo,
                'modalidad' => $modalidad,
                'fecha_hora' => $fechaHora,
                'lugar' => trim($datos['lugar']),
            ]);
            $entrevista->aspirantes()->createMany(array_map(fn ($id) => ['postulacion_id' => $id], $ids));
            $entrevista->evaluadores()->createMany($evaluadores->map(fn ($e) => ['person_id' => $e->person_id])->all());

            return $entrevista;
        });

        Auditoria::registrar('entrevista_programada', $entrevista, [], [
            'tipo' => self::TIPOS[$tipo],
            'modalidad' => $modalidad,
            'fecha_hora' => $fechaHora->format('Y-m-d H:i'),
            'lugar' => $entrevista->lugar,
            'aspirantes' => $postulantes->pluck('nombre')->all(),
            'evaluadores' => $evaluadores->map(fn ($e) => $this->nombre($e->persona))->all(),
        ]);
        $this->enviarCitaciones($entrevista, $postulantes, $evaluadores);

        return redirect()->route('talentohumano.fases.entrevistas.index', $fase->id)
            ->with('success', 'Entrevista ' . mb_strtolower(self::TIPOS[$tipo]) . ' programada. Se envió la citación por correo a '
                . ($postulantes->count() === 1 ? 'el aspirante' : 'los aspirantes') . ' y a los evaluadores.');
    }

    /**
     * Cancela una citación que aún no tiene calificaciones (por ejemplo, para corregir la fecha) y avisa por correo.
     */
    public function destroy(Request $request, int $fase, int $entrevista)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);
        $entrevista = Entrevista::with(['postulaciones', 'evaluadores'])->where('convocatoria_id', $convocatoria->id)->findOrFail($entrevista);

        if ($entrevista->calificaciones()->exists()) {
            return back()->with('error', 'No se puede cancelar: la entrevista ya tiene calificaciones registradas.');
        }

        $entrevista->delete();
        Auditoria::registrar('entrevista_cancelada', $entrevista, ['fecha_hora' => $entrevista->fecha_hora->format('Y-m-d H:i'), 'lugar' => $entrevista->lugar], []);
        Correo::enviar(
            ProcesoSeleccion::correos($entrevista->postulaciones->pluck('person_id')->merge($entrevista->evaluadores->pluck('person_id'))),
            'FOLIUM: se canceló una entrevista',
            "FOLIUM · Talento Humano\n\nSe canceló la entrevista " . mb_strtolower(self::TIPOS[$entrevista->tipo]) . ' del '
            . $entrevista->fecha_hora->translatedFormat('l j \d\e F \a \l\a\s g:i a') . " en {$entrevista->lugar}.\n\nEl Gestor de Talento Humano enviará la nueva citación."
        );

        return back()->with('success', 'Entrevista cancelada. Se avisó por correo a los aspirantes y a los evaluadores.');
    }

    /**
     * Citación a cada aspirante y cronograma a los evaluadores (HU-027A esc. 1, HU-042A).
     */
    private function enviarCitaciones(Entrevista $entrevista, $postulantes, $evaluadores): void
    {
        $cuando = $entrevista->fecha_hora->translatedFormat('l j \d\e F \d\e Y \a \l\a\s g:i a');
        $tipo = mb_strtolower(self::TIPOS[$entrevista->tipo]);
        $detalle = "Fecha y hora: {$cuando}\nLugar: {$entrevista->lugar}\nModalidad: " . ($entrevista->modalidad === 'grupal' ? 'grupal' : 'individual');

        Correo::enviar(ProcesoSeleccion::correos($postulantes->pluck('person_id')), "FOLIUM: citación a su entrevista {$tipo}",
            "FOLIUM · Talento Humano\n\nUsted está citado a la entrevista {$tipo} del proceso de selección de SENA Empresa.\n\n{$detalle}\n\n"
            . 'Consulte sus citaciones en ' . route('talentohumano.aprendiz.postulacion'));

        Correo::enviar(ProcesoSeleccion::correos($evaluadores->pluck('person_id')), "FOLIUM: entrevista {$tipo} asignada",
            "FOLIUM · Talento Humano\n\nUsted es evaluador de una entrevista {$tipo}.\n\n{$detalle}\nAspirantes: "
            . $postulantes->pluck('nombre')->implode(', ') . "\n\nRegistre su calificación en " . route('talentohumano.entrevistas.index'));
    }

    /**
     * Las entrevistas se programan con la convocatoria publicada y cerrada (RF-002, HU-021 esc. 2).
     */
    private function bloqueo($convocatoria): ?string
    {
        return match (true) {
            !$convocatoria => 'Primero publique la convocatoria de la fase.',
            !$convocatoria->estaCerrada() => 'La convocatoria debe cerrarse primero.',
            default => null,
        };
    }

    private function nombre($persona): string
    {
        return trim("{$persona?->first_name} {$persona?->first_last_name}");
    }

    private function exigirPermiso(Request $request): void
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'programacion')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }
    }
}
