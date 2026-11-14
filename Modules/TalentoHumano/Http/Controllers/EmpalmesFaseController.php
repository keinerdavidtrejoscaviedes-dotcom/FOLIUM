<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\ActaRecibimiento;
use Modules\TalentoHumano\Support\ActaSeleccionados;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\AvisosGestor;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\GestionAsignaciones;
use Modules\TalentoHumano\Support\PreparacionEmpalmes;
use Modules\TalentoHumano\Support\ProcesoSeleccion;

/**
 * Empalmes de la fase entrante, desde el Gestor de TH: prepararlos y citar (HU-029A), seguimiento (HU-029B), inventario
 * inicial (HU-033), vinculación (HU-031A) y cierre de la fase saliente (HU-031B). El detalle de cada empalme, las firmas
 * y el visto bueno (HU-030) están en «Empalmes» (EmpalmeController).
 */
class EmpalmesFaseController extends Controller
{
    public function index(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        if (!ActaSeleccionados::publicada($fase->id)) {
            return view('talentohumano::proceso.empalmes', ['fase' => $fase, 'bloqueo' => 'Primero publique el acta de seleccionados de la fase.']);
        }

        $empalmes = Empalme::with(['cargo', 'cargoReceptor', 'acta', 'participantes.asignacion.persona', 'inventarios'])
            ->where('fase_id', $fase->id)->orderBy('cargo_id')->get();

        return view('talentohumano::proceso.empalmes', [
            'fase' => $fase,
            'bloqueo' => null,
            'saliente' => PreparacionEmpalmes::faseSaliente($fase),
            'empalmes' => $empalmes->each(function ($empalme) {
                $empalme->pasos = $this->pasos($empalme);
            }),
            'sinEmpalme' => PreparacionEmpalmes::salientesSinEmpalme($fase),
            'vinculacion' => $this->vinculacion($fase, $empalmes),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'programacion'),
        ]);
    }

    /**
     * 'Preparar empalmes' (HU-029A esc. 1 a 11). Se puede repetir: solo crea los que falten.
     */
    public function preparar(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        if (!ActaSeleccionados::publicada($fase->id)) {
            AvisosGestor::prerrequisito($fase, 'Empalmes', 'Primero publique el acta de seleccionados de la fase.');

            return back()->with('error', 'Primero publique el acta de seleccionados de la fase.');
        }

        $resultado = PreparacionEmpalmes::preparar($fase);
        $respuesta = redirect()->route('talentohumano.fases.empalmes.index', $fase->id)
            ->with($resultado['creados'] ? 'success' : 'info', $resultado['creados']
                ? "Se prepararon {$resultado['creados']} empalmes. Revise las partes de cada uno y envíe las citaciones."
                : 'No hay empalmes nuevos por preparar.');

        return $resultado['sin_receptor']
            ? $respuesta->with('warning', 'Estos cargos no tienen a quién entregar porque ni ellos ni sus superiores tienen entrante en la fase: '
                . implode(', ', $resultado['sin_receptor']) . '. Revise la activación de cargos y el acta de seleccionados.')
            : $respuesta;
    }

    /**
     * 'Enviar citaciones' al empalme a cada saliente y a su receptor (HU-029A esc. 12).
     */
    public function citar(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $fase = Fase::findOrFail($fase);
        [$inicio, $fin] = ProcesoSeleccion::etapa($fase, 'empalme');
        $fechas = $inicio ? 'entre el ' . $inicio->format('d/m/Y') . ' y el ' . $fin->format('d/m/Y') : 'en las fechas que le indique el Gestor de Talento Humano';

        $empalmes = Empalme::with(['cargo', 'cargoReceptor', 'participantes.asignacion'])->where('fase_id', $fase->id)->where('estado', '!=', 'cerrado')->get();
        foreach ($empalmes as $empalme) {
            $personas = $empalme->participantes->pluck('asignacion.person_id')->filter();
            Correo::enviar(ProcesoSeleccion::correos($personas), "FOLIUM: citación al empalme del cargo {$empalme->cargo?->nombre}",
                "FOLIUM · Talento Humano\n\nUsted participa en el empalme del cargo {$empalme->cargo?->nombre}"
                . ($empalme->cargo_receptor_id != $empalme->cargo_id ? ", que recibe el cargo {$empalme->cargoReceptor?->nombre}" : '')
                . ".\n\nEl empalme se hace {$fechas}: registro y verificación del inventario, visita a la unidad productiva, firma del acta y Formulario de Satisfacción.\n\n"
                . 'Ingrese a ' . route('talentohumano.aprendiz.empalme'));
        }
        Auditoria::registrar('empalmes_citados', $fase, [], ['empalmes' => $empalmes->count()]);

        return back()->with('success', "Se enviaron las citaciones de {$empalmes->count()} empalmes.");
    }

    /**
     * 'Validar inventario' inicial de un cargo que se activa por primera vez (HU-033 esc. 1).
     */
    public function validarInventario(Request $request, int $fase, int $empalme)
    {
        $this->exigirPermiso($request);
        $empalme = $this->empalmeInicial($fase, $empalme);
        $acta = $empalme->acta;

        if (empty($acta?->contenido['inventario_registrado'])) {
            return back()->with('error', 'El aprendiz entrante aún no registra el inventario inicial.');
        }

        $acta->update(['contenido' => array_merge($acta->contenido, [
            'inventario_validado' => ['fecha' => now()->toDateTimeString(), 'por' => auth()->user()->person_id],
            'inventario_devuelto' => null,
        ])]);
        Auditoria::registrar('inventario_inicial_validado', $empalme, [], ['cargo' => $empalme->cargo?->nombre, 'items' => $empalme->inventarios()->count()]);

        return back()->with('success', "Inventario inicial del cargo {$empalme->cargo?->nombre} validado: queda como el inventario vigente del cargo.");
    }

    /**
     * 'Devolver' el inventario inicial con observaciones (HU-033 esc. 2 y 3).
     */
    public function devolverInventario(Request $request, int $fase, int $empalme)
    {
        $this->exigirPermiso($request);
        $empalme = $this->empalmeInicial($fase, $empalme);
        $observaciones = trim((string) $request->input('observaciones'));

        if ($observaciones === '') {
            return back()->withErrors(["observaciones.{$empalme->id}" => 'Escriba las observaciones para el aprendiz.']);
        }

        $acta = ActaRecibimiento::asegurar($empalme);
        $acta->update(['contenido' => array_merge($acta->contenido ?? [], [
            // Vuelve a quedar sin registrar: el aprendiz corrige y lo guarda de nuevo
            'inventario_registrado' => null,
            'inventario_validado' => null,
            'inventario_devuelto' => ['fecha' => now()->toDateTimeString(), 'por' => auth()->user()->person_id, 'observaciones' => mb_substr($observaciones, 0, 1000)],
        ])]);
        Auditoria::registrar('inventario_inicial_devuelto', $empalme, [], ['cargo' => $empalme->cargo?->nombre, 'observaciones' => $observaciones]);

        Correo::enviar(ProcesoSeleccion::correos($empalme->participantes->pluck('asignacion.person_id')->filter()),
            "FOLIUM: corrija el inventario inicial del cargo {$empalme->cargo?->nombre}",
            "FOLIUM · Talento Humano\n\nEl Gestor de Talento Humano devolvió el inventario inicial del cargo {$empalme->cargo?->nombre} con estas observaciones:\n\n"
            . "{$observaciones}\n\nCorríjalo y guárdelo de nuevo en " . route('talentohumano.aprendiz.empalme'));

        return back()->with('success', 'Inventario devuelto: quedó pendiente de corrección y se avisó al aprendiz entrante.');
    }

    /**
     * 'Confirmar cierre' de la fase saliente cuando todos entregaron su cargo (HU-031B, RN-030, RN-058).
     */
    public function cerrarFaseSaliente(Request $request, int $fase)
    {
        $this->exigirPermiso($request);
        $entrante = Fase::findOrFail($fase);
        $saliente = PreparacionEmpalmes::faseSaliente($entrante);
        if (!$saliente) {
            return back()->with('error', 'No hay una fase saliente con aprendices en sus cargos.');
        }

        if ($saliente->fecha_fin && $saliente->fecha_fin->isFuture()) {
            return back()->with('error', "La {$saliente->nombre()} termina el {$saliente->fecha_fin->format('d/m/Y')}; se cierra después de esa fecha.");
        }

        // Escenario 2: todo saliente debe haber entregado con el empalme cerrado
        $pendientes = Asignacion::with(['persona', 'cargo'])->where('fase_id', $saliente->id)->whereIn('estado', ['vigente', 'suspendida'])
            ->whereNotExists(fn ($q) => $q->from('th_empalme_participantes as ep')->join('th_empalmes as e', 'e.id', '=', 'ep.empalme_id')
                ->whereColumn('ep.asignacion_id', 'th_asignaciones.id')->where('ep.papel', 'saliente')
                ->where('e.fase_id', $entrante->id)->where('e.estado', 'cerrado'))
            ->get()
            ->map(fn ($a) => trim("{$a->persona?->first_name} {$a->persona?->first_last_name}") . " ({$a->cargo?->nombre})");

        if ($pendientes->isNotEmpty()) {
            $alerta = Alerta::prerrequisito($saliente->id, 'Cierre de la fase', ['Empalmes de salida sin cerrar: ' . $pendientes->implode(', ')]);
            if ($alerta) {
                Correo::enviar(AccesoTH::correosAdministradores()->all(), "FOLIUM: la {$saliente->nombre()} no se pudo cerrar",
                    "El Gestor de Talento Humano intentó cerrar la {$saliente->nombre()}, pero estos aprendices no han cerrado su empalme de salida:\n\n- "
                    . $pendientes->implode("\n- "));
            }

            return back()->with('error', 'No se puede cerrar la fase: faltan empalmes de salida de ' . $pendientes->implode(', ') . '.');
        }

        $finalizadas = DB::transaction(function () use ($saliente, $entrante) {
            $asignaciones = Asignacion::with('cargo')->where('fase_id', $saliente->id)->whereIn('estado', ['vigente', 'suspendida'])->get();
            foreach ($asignaciones as $asignacion) {
                $asignacion->update(['estado' => 'finalizada', 'fecha_fin' => $asignacion->fecha_fin ?? today()]);
                // RN-067: quien deja el cargo Gestor de TH pierde el rol
                GestionAsignaciones::sincronizarRolGestor($asignacion);
            }

            DB::table('th_designaciones')->where('fase_id', $saliente->id)->whereNull('fecha_retiro')->whereNull('deleted_at')
                ->update(['fecha_retiro' => today(), 'motivo_retiro' => "Cierre de la {$saliente->nombre()}", 'updated_at' => now()]);
            $saliente->update(['estado' => 'cerrada']);
            $entrante->update(['estado' => 'en_curso']);

            return $asignaciones->count();
        });
        Auditoria::registrar('fase_cerrada', $saliente, ['estado' => 'en_curso'], ['estado' => 'cerrada', 'asignaciones_finalizadas' => $finalizadas, 'fase_entrante' => $entrante->nombre()]);

        return back()->with('success', "La {$saliente->nombre()} quedó cerrada: {$finalizadas} asignaciones pasaron a finalizada y la {$entrante->nombre()} quedó en curso.");
    }

    /**
     * Paso de cada empalme y lo que le falta (HU-029B esc. 1). Sin saliente, el inventario es inicial y lo valida el Gestor.
     */
    private function pasos(Empalme $empalme): array
    {
        $contenido = $empalme->acta?->contenido ?? [];
        $inicial = $empalme->participantes->where('papel', 'saliente')->isEmpty();

        return [
            'Inventario registrado' => !empty($contenido['inventario_registrado']),
            $inicial ? 'Inventario inicial validado' : 'Inventario verificado por quien recibe' => $inicial ? !empty($contenido['inventario_validado']) : !empty($contenido['inventario_verificado']),
            'Visita a la unidad productiva' => (bool) $empalme->visita_confirmada,
            'Acta firmada' => $empalme->acta?->estado === 'firmada',
            'Formulario de Satisfacción' => ActaRecibimiento::satisfaccionCompleta($empalme),
            'Visto bueno' => $empalme->estado === 'cerrado',
        ];
    }

    /**
     * Estado de vinculación de cada aprendiz seleccionado o reubicado (HU-031A esc. 1).
     */
    private function vinculacion(Fase $fase, $empalmes)
    {
        $cerrados = $empalmes->where('estado', 'cerrado')->flatMap(fn ($e) => $e->participantes->where('papel', 'receptor')->pluck('asignacion_id'))->flip();

        return Asignacion::with(['persona', 'cargo'])->where('fase_id', $fase->id)->whereIn('origen', ['acta', 'reubicacion'])->get()
            ->map(fn ($a) => (object) [
                'aprendiz' => trim("{$a->persona?->first_name} {$a->persona?->first_last_name} {$a->persona?->second_last_name}"),
                'cargo' => $a->cargo?->nombre,
                'origen' => Asignacion::ORIGENES[$a->origen] ?? $a->origen,
                'empalme_cerrado' => isset($cerrados[$a->id]),
                'estado' => Asignacion::ESTADOS[$a->estado] ?? $a->estado,
                'vinculado' => $a->estado === 'vigente',
            ])
            ->sortBy('aprendiz')->values();
    }

    private function empalmeInicial(int $fase, int $empalme): Empalme
    {
        $empalme = Empalme::with(['acta', 'cargo', 'participantes.asignacion'])->where('fase_id', $fase)->findOrFail($empalme);
        abort_unless($empalme->participantes->where('papel', 'saliente')->isEmpty(), 422, 'Este empalme tiene aprendiz saliente: el inventario lo verifica quien recibe.');

        return $empalme;
    }

    private function exigirPermiso(Request $request): void
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'programacion')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }
    }
}
