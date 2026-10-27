<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Services\FaseService;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\Correo;

/**
 * Aprobaciones pendientes del Instructor Líder (HU-034, HU-035, HU-036; mockups INS-01 e INS-02).
 * El acceso lo controla el submódulo: Administrador o Líder de la estrategia (HU-034, esc. 6).
 */
class AprobacionesController extends Controller
{
    public function index()
    {
        $cronogramas = Cronograma::with(['fase', 'enviadoPor'])->withCount('etapas')
            ->where('estado', 'enviado')->latest('updated_at')->get();
        $decididos = Cronograma::with(['fase', 'aprobadoPor'])
            ->whereIn('estado', ['aprobado', 'rechazado'])->latest('updated_at')->limit(5)->get();

        $convocatoria = DB::table('th_convocatorias')->whereNull('deleted_at')->whereIn('estado', ['publicada', 'cerrada'])->latest('id')->value('id');
        $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria ?? 0);

        return view('talentohumano::lider.aprobaciones.index', [
            'cronogramas' => $cronogramas,
            'decididos' => $decididos,
            'selecciones' => DB::table('th_selecciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->where('estado', 'propuesta')->count(),
            'reubicar' => (clone $postulaciones)->where('estado', 'no_seleccionado')
                ->whereNotExists(fn ($q) => $q->from('th_reubicaciones')->whereColumn('th_reubicaciones.postulacion_id', 'th_postulaciones.id'))->count(),
        ]);
    }

    /**
     * Revisión del cronograma: sus etapas y, si es la modificación de uno aprobado, las fechas vigentes (HU-034, esc. 5).
     */
    public function show(Cronograma $cronograma)
    {
        $cronograma->load(['fase', 'etapas', 'enviadoPor', 'aprobadoPor']);
        $vigente = Cronograma::with('etapas')->where('fase_id', $cronograma->fase_id)->where('estado', 'aprobado')
            ->where('id', '!=', $cronograma->id)->latest('fecha_aprobacion')->first();

        return view('talentohumano::lider.aprobaciones.cronograma', [
            'cronograma' => $cronograma,
            'etapas' => $this->etapas($cronograma),
            'vigentes' => $vigente && $cronograma->estado === 'enviado' ? $this->etapas($vigente) : null,
            'vigente' => $vigente,
        ]);
    }

    /**
     * HU-034, esc. 1: aprueba el cronograma, avisa a los aprendices de las fichas de la fase y habilita la convocatoria.
     */
    public function aprobar(Cronograma $cronograma)
    {
        if ($cronograma->estado !== 'enviado') {
            return back()->with('error', 'Ese cronograma ya no está en aprobación.');
        }

        $cronograma->update(['estado' => 'aprobado', 'aprobado_por' => auth()->user()->person_id, 'fecha_aprobacion' => now(), 'motivo_rechazo' => null]);
        $fase = $cronograma->fase->nombre_completo;
        Auditoria::registrar('cronograma_aprobado', $cronograma, ['estado' => 'enviado'], ['estado' => 'aprobado', 'fase' => $fase]);

        $etapas = collect($this->etapas($cronograma->load('etapas')))->map(fn ($e) => "- {$e['nombre']}: {$e['fechas']}")->implode("\n");
        $correos = $this->correosAprendices($cronograma->fase_id);
        $aviso = match (true) {
            $correos === [] => ' Las fichas de la fase no tienen aprendices con correo registrado, así que no se envió ningún aviso.',
            Correo::enviar($correos, "FOLIUM: cronograma del proceso de selección de la {$fase}",
                "FOLIUM · Talento Humano\n\nSe aprobó el cronograma del proceso de selección de SENA Empresa ({$fase}).\n\n{$etapas}\n\n"
                . 'Las fechas también se pueden consultar en FOLIUM.') => ' Se avisó por correo a ' . count($correos) . ' aprendices de las fichas de la fase.',
            default => ' El correo a los aprendices no se pudo entregar; quedó en «Notificaciones no entregadas».',
        };

        return redirect()->route('talentohumano.lider.aprobaciones.cronograma', $cronograma)
            ->with('success', 'Cronograma aprobado. El Gestor de Talento Humano ya puede publicar la convocatoria.' . $aviso);
    }

    /**
     * HU-034, esc. 2 y 3: lo devuelve al Gestor de Talento Humano con el motivo.
     */
    public function rechazar(Request $request, Cronograma $cronograma)
    {
        if ($cronograma->estado !== 'enviado') {
            return back()->with('error', 'Ese cronograma ya no está en aprobación.');
        }

        $motivo = trim((string) $request->validate(
            ['motivo' => 'required|string|max:1000'],
            ['motivo.required' => 'El motivo del rechazo es obligatorio', 'motivo.max' => 'El motivo no puede superar los 1000 caracteres']
        )['motivo']);

        $cronograma->update(['estado' => 'rechazado', 'motivo_rechazo' => $motivo]);
        $fase = $cronograma->fase->nombre_completo;
        Auditoria::registrar('cronograma_rechazado', $cronograma, ['estado' => 'enviado'], ['estado' => 'rechazado', 'fase' => $fase, 'motivo' => $motivo]);

        $correo = Correo::de($cronograma->enviado_por);
        $aviso = match (true) {
            !$correo => ' El Gestor de Talento Humano no tiene correo registrado, así que no se le pudo avisar.',
            Correo::enviar([$correo], "FOLIUM: el cronograma de la {$fase} fue rechazado",
                "FOLIUM · Talento Humano\n\nEl Instructor Líder rechazó el cronograma del proceso de selección de la {$fase}.\n\n"
                . "Motivo: {$motivo}\n\nAjústelo y envíelo de nuevo a aprobación desde la página de la fase.") => ' Se le avisó por correo.',
            default => ' El correo no se pudo entregar; quedó en «Notificaciones no entregadas».',
        };

        return redirect()->route('talentohumano.lider.aprobaciones.cronograma', $cronograma)
            ->with('warning', 'Cronograma devuelto al Gestor de Talento Humano.' . $aviso);
    }

    /**
     * Etapas en el orden del proceso, con sus fechas en texto: [clave => [nombre, fechas]].
     */
    private function etapas(Cronograma $cronograma): array
    {
        $guardadas = $cronograma->etapas->keyBy('etapa');

        return collect(CronogramaController::ETAPAS)->map(function ($etapa, $clave) use ($guardadas) {
            $fila = $guardadas[$clave] ?? null;
            $fechas = match (true) {
                !$fila => 'Sin fecha',
                !$fila->fecha_fin || $fila->fecha_fin->equalTo($fila->fecha_inicio) => $fila->fecha_inicio->format('d/m/Y'),
                default => $fila->fecha_inicio->format('d/m/Y') . ' – ' . $fila->fecha_fin->format('d/m/Y'),
            };

            return ['nombre' => $etapa['nombre'], 'fechas' => $fechas];
        })->all();
    }

    /**
     * Correos de los aprendices activos de las fichas de la fase.
     */
    private function correosAprendices(int $faseId): array
    {
        return DB::table('apprentices as a')
            ->join('th_fase_fichas as ff', 'ff.course_id', '=', 'a.course_id')
            ->join('people as p', 'p.id', '=', 'a.person_id')
            ->leftJoin('users as u', fn ($j) => $j->on('u.person_id', '=', 'p.id')->whereNull('u.deleted_at'))
            ->where('ff.fase_id', $faseId)
            ->whereNull('a.deleted_at')
            ->whereIn('a.apprentice_status', FaseService::ESTADOS_APRENDIZ_ACTIVO)
            ->selectRaw("COALESCE(NULLIF(u.email, ''), NULLIF(p.misena_email, ''), NULLIF(p.sena_email, ''), NULLIF(p.personal_email, '')) as correo")
            ->pluck('correo')->filter()->unique()->values()->all();
    }
}
