<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaEvaluador;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\Instructores;
use Modules\TalentoHumano\Support\ProcesoSeleccion;

/**
 * Evaluadores de la convocatoria: el Gestor de TH, con el Instructor de TH, agrega instructores técnicos y psicólogos
 * disponibles; el Instructor Líder y los instructores de las fichas se incluyen siempre (HU-026A, HU-026B, RN-042).
 */
class EvaluadoresController extends Controller
{
    /**
     * Acceso desde el panel: lleva a los evaluadores de la convocatoria publicada más reciente.
     */
    public function actual()
    {
        $convocatoria = Convocatoria::whereIn('estado', ['publicada', 'cerrada'])->latest('id')->first();

        return $convocatoria
            ? redirect()->route('talentohumano.fases.evaluadores.index', $convocatoria->fase_id)
            : redirect()->route('talentohumano.panel')->with('info', 'Aún no hay una convocatoria publicada para definir sus evaluadores.');
    }

    public function index(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);

        if (!$convocatoria) {
            return view('talentohumano::proceso.evaluadores', ['fase' => $fase, 'convocatoria' => null]);
        }

        ProcesoSeleccion::incluirEvaluadoresObligatorios($convocatoria);
        $todos = $convocatoria->evaluadores()->with('persona')->get()
            ->sortBy(fn ($e) => [$e->incluidoPorSistema() ? 0 : 1, $e->tipo, $e->persona?->first_name])->values();
        // Los retirados no se ofrecen para agregar: se vuelven a incluir desde su propia lista
        $elegidos = $todos->pluck('person_id');

        return view('talentohumano::proceso.evaluadores', [
            'fase' => $fase,
            'convocatoria' => $convocatoria,
            'evaluadores' => $todos->whereNull('retirado_en')->values(),
            'retirados' => $todos->whereNotNull('retirado_en')->values(),
            'entrevistas' => $this->entrevistasPorEvaluador($convocatoria),
            'tecnicos' => $this->tecnicosDisponibles($fase)->whereNotIn('id', $elegidos)->values(),
            'psicologos' => Instructores::psicologos()->whereNotIn('id', $elegidos)->values(),
            'hayPsicologos' => Instructores::psicologos()->isNotEmpty(),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'evaluadores'),
        ]);
    }

    /**
     * 'Agregar evaluador' (HU-026A esc. 3, 4 y 5).
     */
    public function store(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);

        $datos = $request->validate(['tipo' => 'required|in:tecnico,psicologico', 'person_id' => 'nullable|integer']);
        $disponibles = $datos['tipo'] === 'psicologico' ? Instructores::psicologos() : $this->tecnicosDisponibles($fase);

        // Escenario 5: sin psicólogos se avisa al Administrador
        if ($datos['tipo'] === 'psicologico' && $disponibles->isEmpty()) {
            $alerta = Alerta::prerrequisito($fase->id, 'Evaluadores', ['No hay psicólogos disponibles'], InstructorLiderController::url('designaciones'));
            if ($alerta) {
                Correo::enviar(AccesoTH::correosAdministradores()->all(), "FOLIUM: no hay psicólogos disponibles para la {$fase->nombre()}",
                    "El Gestor de Talento Humano no pudo agregar un evaluador psicológico a la convocatoria de la {$fase->nombre()} porque no hay ningún usuario activo con el rol Psicólogo.");
            }

            return back()->with('error', 'No hay psicólogos disponibles.');
        }

        $persona = $disponibles->firstWhere('id', (int) ($datos['person_id'] ?? 0));
        if (!$persona) {
            return back()->withErrors(['person_id' => 'Elija un evaluador de la lista.']);
        }

        $evaluador = ConvocatoriaEvaluador::firstOrCreate(
            ['convocatoria_id' => $convocatoria->id, 'person_id' => $persona->id],
            ['tipo' => $datos['tipo'], 'motivo' => 'agregado', 'agregado_por' => auth()->user()->person_id]
        );
        // Si estaba retirado, vuelve a la convocatoria
        if ($evaluador->retirado_en) {
            $evaluador->update(['retirado_en' => null, 'motivo_retiro' => null, 'retirado_por' => null]);
        }
        Auditoria::registrar('evaluador_agregado', $evaluador, [], ['evaluador' => $persona->nombre, 'tipo' => ConvocatoriaEvaluador::TIPOS[$datos['tipo']]]);

        return back()->with('success', "{$persona->nombre} quedó como evaluador de la convocatoria.");
    }

    /**
     * 'Quitar' un evaluador. Ajuste del equipo a HU-026A esc. 2: el Instructor Líder y los instructores de las fichas
     * también se pueden retirar (por ejemplo, si no están en el centro), pero con un motivo; quedan marcados como
     * retirados para que el sistema no los vuelva a incluir. Los agregados se quitan de la lista.
     */
    public function destroy(Request $request, int $fase, int $evaluador)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);
        $evaluador = $convocatoria->evaluadores()->activos()->with('persona')->findOrFail($evaluador);
        $nombre = trim("{$evaluador->persona?->first_name} {$evaluador->persona?->first_last_name}");

        if ($this->entrevistasPorEvaluador($convocatoria)->get($evaluador->person_id)) {
            return back()->with('error', "No se puede quitar a {$nombre}: tiene entrevistas programadas. Cancélelas o reprográmelas con otro evaluador.");
        }

        if (!$evaluador->incluidoPorSistema()) {
            $evaluador->delete();
            Auditoria::registrar('evaluador_retirado', $evaluador, ['evaluador' => $nombre], []);

            return back()->with('success', "{$nombre} ya no es evaluador de la convocatoria.");
        }

        $motivo = trim((string) $request->input('motivo_retiro'));
        if ($motivo === '') {
            return back()->withErrors(["motivo_retiro.{$evaluador->id}" => "Escriba el motivo para retirar a {$nombre} (por ejemplo, que no está en el centro)."]);
        }

        $evaluador->update(['retirado_en' => now(), 'motivo_retiro' => mb_substr($motivo, 0, 255), 'retirado_por' => auth()->user()->person_id]);
        Auditoria::registrar('evaluador_retirado', $evaluador, ['evaluador' => $nombre, 'motivo' => ConvocatoriaEvaluador::MOTIVOS[$evaluador->motivo]], ['motivo_retiro' => $motivo]);

        return back()->with('success', "{$nombre} fue retirado de la convocatoria. Puede volver a incluirlo si se necesita.");
    }

    /**
     * Vuelve a incluir a un evaluador que el sistema había incluido y luego se retiró.
     */
    public function restaurar(Request $request, int $fase, int $evaluador)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);
        $evaluador = $convocatoria->evaluadores()->whereNotNull('retirado_en')->with('persona')->findOrFail($evaluador);

        $anterior = $evaluador->motivo_retiro;
        $evaluador->update(['retirado_en' => null, 'motivo_retiro' => null, 'retirado_por' => null]);
        Auditoria::registrar('evaluador_restaurado', $evaluador, ['motivo_retiro' => $anterior], ['evaluador' => trim("{$evaluador->persona?->first_name} {$evaluador->persona?->first_last_name}")]);

        return back()->with('success', 'El evaluador volvió a la convocatoria.');
    }

    /**
     * Instructores técnicos disponibles; primero los designados para un programa de las fichas de la fase (HU-026A esc. 1).
     */
    private function tecnicosDisponibles(Fase $fase)
    {
        $programas = DB::table('th_fase_fichas as ff')->join('courses as c', 'c.id', '=', 'ff.course_id')
            ->where('ff.fase_id', $fase->id)->pluck('c.program_id');
        $delPrograma = DB::table('th_designaciones')->where('tipo', 'instructor_tecnico')->whereNull('fecha_retiro')->whereNull('deleted_at')
            ->whereIn('program_id', $programas)->pluck('person_id')->flip();

        return Instructores::designables()
            ->each(function ($i) use ($delPrograma) {
                $i->mismo_programa = $delPrograma->has($i->id);
            })
            ->sortBy(fn ($i) => [$i->mismo_programa ? 0 : 1, $i->nombre])
            ->values();
    }

    /**
     * Número de entrevistas de la convocatoria asignadas a cada evaluador (person_id => total).
     */
    private function entrevistasPorEvaluador(Convocatoria $convocatoria)
    {
        return DB::table('th_entrevista_evaluadores as ee')
            ->join('th_entrevistas as e', 'e.id', '=', 'ee.entrevista_id')
            ->where('e.convocatoria_id', $convocatoria->id)->whereNull('e.deleted_at')
            ->groupBy('ee.person_id')
            ->pluck(DB::raw('count(*)'), 'ee.person_id');
    }

    private function exigirPermiso(Request $request): void
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'evaluadores')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }
    }
}
