<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\CargaInicial;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\Aprendices;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\CargaMasiva;
use Modules\TalentoHumano\Support\GestionAsignaciones;
use Modules\TalentoHumano\Support\HojaExcel;
use RuntimeException;

/**
 * Asignaciones de cargo (RF-015; HU-012, HU-013 y HU-016).
 */
class AsignacionesController extends Controller
{
    private const MENSAJE_JUSTIFICACION = 'La justificación es obligatoria';

    /**
     * Consulta por fase, con estado y origen (HU-016).
     */
    public function index(Request $request)
    {
        $fases = Fase::orderByDesc('numero')->get();
        $fase = $fases->firstWhere('id', (int) $request->query('fase')) ?? Fase::actual() ?? $fases->first();

        $base = Asignacion::query()->where('th_asignaciones.fase_id', $fase?->id ?? 0);
        $asignaciones = (clone $base)
            ->with(['cargo.area', 'unidades', 'persona'])
            ->when($request->query('q'), function ($q, $texto) {
                $personas = DB::table('people')->where('document_number', 'like', preg_replace('/\D/', '', $texto) ?: '-')
                    ->orWhereRaw("CONCAT_WS(' ', first_name, first_last_name, second_last_name) like ?", ['%' . $texto . '%'])->select('id');
                $q->where(fn ($w) => $w->whereIn('th_asignaciones.person_id', $personas)->orWhereHas('cargo', fn ($c) => $c->where('nombre', 'like', '%' . $texto . '%')));
            })
            ->when($request->query('estado'), fn ($q, $estado) => $q->where('th_asignaciones.estado', $estado))
            ->when($request->query('origen'), fn ($q, $origen) => $q->where('th_asignaciones.origen', $origen))
            ->when($request->query('area'), fn ($q, $area) => $q->whereHas('cargo', fn ($c) => $c->where('area_id', $area)))
            ->join('th_cargos', 'th_cargos.id', '=', 'th_asignaciones.cargo_id')
            ->orderByRaw("FIELD(th_cargos.nivel, 'Gerente', 'Gestor', 'Lider', 'Equipo')")->orderBy('th_cargos.nombre')
            ->select('th_asignaciones.*')
            ->paginate(20)
            ->withQueryString();

        return view('talentohumano::lider.asignaciones.index', [
            'fases' => $fases,
            'fase' => $fase,
            'asignaciones' => $asignaciones,
            'conteo' => (clone $base)->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
            'areas' => DB::table('th_areas')->whereNull('deleted_at')->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    /**
     * Puesta en marcha: registro de la fase que ya está en curso cuando se implementa FOLIUM.
     */
    public function registrarFase(Request $request)
    {
        abort_if(Fase::actual(), 409, 'Ya hay una fase en curso');
        $datos = $request->validateWithBag('fase', [
            'numero' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('th_fases', 'numero')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after:fecha_inicio'],
        ], [
            'required' => 'Este campo es obligatorio',
            'numero.unique' => 'Ya existe una fase con ese número',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la de inicio',
        ]);

        $fase = Fase::create($datos + ['estado' => 'en_curso']);
        Auditoria::registrar('fase_registrada', $fase, [], $datos + ['estado' => 'en_curso']);

        return redirect()->route('talentohumano.lider.asignaciones.index')->with('success', "Se registró la {$fase->nombre()} como fase en curso.");
    }

    /**
     * Detalle del aprendiz: sus asignaciones y los movimientos registrados en la auditoría.
     */
    public function show(Asignacion $asignacion)
    {
        $todas = Asignacion::with(['cargo', 'fase', 'unidades'])->where('person_id', $asignacion->person_id)->latest('id')->get();

        return view('talentohumano::lider.asignaciones.show', [
            'asignacion' => $asignacion,
            'aprendiz' => Aprendices::porPersona($asignacion->person_id),
            'asignaciones' => $todas,
            'movimientos' => DB::table('audits')->leftJoin('users', 'users.id', '=', 'audits.user_id')
                ->where('auditable_type', Asignacion::class)->whereIn('auditable_id', $todas->pluck('id'))
                ->orderByDesc('audits.id')->get(['audits.*', 'users.nickname']),
        ]);
    }

    /**
     * Suspende una asignación vigente con justificación (HU-012).
     */
    public function suspender(Request $request, Asignacion $asignacion)
    {
        return $this->cambiarEstado($request, $asignacion, 'vigente', 'suspendida', 'Asignación suspendida. El aprendiz perdió las funciones del cargo.');
    }

    /**
     * Reactiva una asignación suspendida con justificación (HU-012).
     */
    public function reactivar(Request $request, Asignacion $asignacion)
    {
        return $this->cambiarEstado($request, $asignacion, 'suspendida', 'vigente', 'Asignación reactivada. El aprendiz recuperó las funciones del cargo.');
    }

    /**
     * Formulario de asignación inicial, una por una (HU-013, esc. 1).
     */
    public function create(Request $request)
    {
        $fase = Fase::actual();
        $documento = trim((string) $request->query('documento', old('documento', '')));
        $aprendiz = $documento !== '' ? Aprendices::porDocumento($documento) : null;

        return view('talentohumano::lider.asignaciones.create', [
            'fase' => $fase,
            'documento' => $documento,
            'aprendiz' => $aprendiz,
            'ocupada' => $aprendiz && $fase ? GestionAsignaciones::ocupada($aprendiz->id, $fase->id) : null,
            'cargos' => Cargo::where('estado', 'activo')->orderByRaw("FIELD(nivel, 'Gerente', 'Gestor', 'Lider', 'Equipo')")->orderBy('nombre')->get(['id', 'nombre', 'nivel', 'numero_aprendices']),
            'ocupados' => $fase ? Asignacion::where('fase_id', $fase->id)->whereIn('estado', Asignacion::OCUPAN)->selectRaw('cargo_id, count(*) as total')->groupBy('cargo_id')->pluck('total', 'cargo_id') : collect(),
            'unidades' => DB::table('productive_units')->whereNull('deleted_at')->orderBy('name')->pluck('name', 'id'),
            'sinGestor' => $fase && !$this->hayGestorVigente($fase),
        ]);
    }

    public function store(Request $request)
    {
        $fase = Fase::actual();
        abort_unless($fase, 409, 'No hay una fase en curso');

        $datos = $request->validate([
            'documento' => ['required', 'string'],
            'cargo_id' => ['required', Rule::exists('th_cargos', 'id')->where('estado', 'activo')->whereNull('deleted_at')],
            'fecha_inicio' => ['required', 'date'],
            'unidades' => ['array'],
            'unidades.*' => [Rule::exists('productive_units', 'id')->whereNull('deleted_at')],
            'justificacion' => ['required', 'string', 'max:2000'],
        ], ['required' => 'Este campo es obligatorio', 'justificacion.required' => self::MENSAJE_JUSTIFICACION, 'cargo_id.exists' => 'Seleccione un cargo activo del catálogo']);

        $aprendiz = Aprendices::porDocumento($datos['documento']);
        if (!$aprendiz || !$aprendiz->aprendiz_id) {
            throw ValidationException::withMessages(['documento' => 'Solo se pueden asignar aprendices registrados en SICEFA']);
        }
        if ($ocupada = GestionAsignaciones::ocupada($aprendiz->id, $fase->id)) {
            throw ValidationException::withMessages(['documento' => "Ya tiene una asignación en la {$fase->nombre()}: {$ocupada->cargo->nombre}. Un aprendiz solo puede tener una asignación por fase."]);
        }

        $asignacion = DB::transaction(fn () => GestionAsignaciones::crearCargaInicial(
            $aprendiz->id, Cargo::find($datos['cargo_id']), $fase, $datos['fecha_inicio'], trim($datos['justificacion']), $datos['unidades'] ?? []
        ));

        return redirect()->route('talentohumano.lider.asignaciones.index')->with('success',
            'Asignación creada en estado «vigente» con origen «carga inicial».' . ($asignacion->esGestorTH() ? ' El aprendiz recibió el rol Gestor de Talento Humano.' : ''));
    }

    /**
     * Carga masiva desde Excel en tres pasos: subir, revisar y confirmar (HU-013, esc. 4 a 6).
     */
    public function cargaMasiva()
    {
        $resultado = session('carga_resultado') ? CargaInicial::with('asignaciones.cargo')->find(session('carga_resultado')) : null;

        return view('talentohumano::lider.asignaciones.carga-masiva', [
            'fase' => Fase::actual(),
            'revision' => session('carga_masiva'),
            'resultado' => $resultado,
        ]);
    }

    public function plantilla()
    {
        return response(CargaMasiva::plantilla(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="plantilla_carga_inicial_folium.xlsx"',
        ]);
    }

    public function validarCarga(Request $request)
    {
        $fase = Fase::actual();
        abort_unless($fase, 409, 'No hay una fase en curso');
        $request->validate(['archivo' => ['required', 'file', 'max:5120']], ['archivo.required' => 'Seleccione el archivo diligenciado', 'archivo.max' => 'El archivo no puede pesar más de 5 MB']);

        $archivo = $request->file('archivo');
        try {
            if (strtolower($archivo->getClientOriginalExtension()) !== 'xlsx') {
                throw new RuntimeException(CargaMasiva::MENSAJE_FORMATO);
            }
            $revision = CargaMasiva::validar(HojaExcel::leer($archivo->getRealPath()), $fase);
        } catch (RuntimeException) {
            return back()->withErrors(['archivo' => CargaMasiva::MENSAJE_FORMATO]);
        }

        session(['carga_masiva' => $revision + ['archivo' => $archivo->getClientOriginalName(), 'fase_id' => $fase->id, 'validado' => now()->toDateTimeString()]]);

        return redirect()->route('talentohumano.lider.asignaciones.carga-masiva');
    }

    public function confirmarCarga(Request $request)
    {
        $revision = session('carga_masiva');
        $fase = Fase::actual();
        abort_unless($revision && $fase && $fase->id === $revision['fase_id'], 409, 'La revisión de la carga ya no es válida');
        $justificacion = trim($request->validate(['justificacion' => ['required', 'string', 'max:2000']], ['justificacion.required' => self::MENSAJE_JUSTIFICACION])['justificacion']);

        $carga = DB::transaction(function () use ($revision, $fase, $justificacion) {
            $errores = $revision['errores'];
            $correctas = [];
            foreach ($revision['correctas'] as $fila) {
                // Pudo cambiar algo entre la revisión y la confirmación
                if ($ocupada = GestionAsignaciones::ocupada($fila['person_id'], $fase->id)) {
                    $errores[] = ['fila' => $fila['fila'], 'documento' => $fila['documento'], 'cargo' => $fila['cargo'], 'motivo' => "Ya tiene el cargo {$ocupada->cargo->nombre} en la {$fase->nombre()}."];
                    continue;
                }
                $correctas[] = $fila;
            }

            $carga = CargaInicial::create([
                'fase_id' => $fase->id, 'archivo' => mb_substr($revision['archivo'], 0, 255), 'filas_correctas' => count($correctas),
                'filas_con_error' => count($errores), 'errores' => $errores, 'justificacion' => $justificacion, 'cargada_por' => auth()->user()->person_id,
            ]);
            $cargos = Cargo::whereIn('id', array_column($correctas, 'cargo_id'))->get()->keyBy('id');
            foreach ($correctas as $fila) {
                GestionAsignaciones::crearCargaInicial($fila['person_id'], $cargos[$fila['cargo_id']], $fase, $fila['fecha_inicio'], $justificacion, $fila['unidades'], $carga->id);
            }
            Auditoria::registrar('carga_masiva', $carga, [], ['archivo' => $carga->archivo, 'creadas' => count($correctas), 'con_error' => count($errores), 'justificacion' => $justificacion]);
            Alerta::crear('carga_masiva', 'Carga masiva registrada', count($correctas) . ' asignaciones creadas y ' . count($errores) . " filas rechazadas ({$carga->archivo}).",
                route('talentohumano.lider.auditoria.index', ['seccion' => 'asignaciones']));

            return $carga;
        });
        session()->forget('carga_masiva');

        return redirect()->route('talentohumano.lider.asignaciones.carga-masiva')->with('carga_resultado', $carga->id);
    }

    public function cancelarCarga()
    {
        session()->forget('carga_masiva');

        return redirect()->route('talentohumano.lider.asignaciones.carga-masiva');
    }

    /**
     * Filas rechazadas de una carga, en Excel, para corregirlas y subirlas en otra carga.
     */
    public function rechazadas(CargaInicial $carga)
    {
        $filas = collect($carga->errores)->map(fn ($e) => [$e['documento'], $e['cargo'], '', '', "Fila {$e['fila']}: {$e['motivo']}"]);

        return response(HojaExcel::escribir(['Asignaciones' => array_merge([array_merge(CargaMasiva::COLUMNAS, ['motivo_del_error'])], $filas->all())]), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="filas_rechazadas_carga_' . $carga->id . '.xlsx"',
        ]);
    }

    private function cambiarEstado(Request $request, Asignacion $asignacion, string $desde, string $hacia, string $mensaje)
    {
        abort_unless($asignacion->estado === $desde, 409, 'La asignación no está en estado ' . $desde);
        $justificacion = $request->validateWithBag($hacia === 'suspendida' ? 'suspender' : 'reactivar',
            ['justificacion' => ['required', 'string', 'max:2000']], ['justificacion.required' => self::MENSAJE_JUSTIFICACION])['justificacion'];

        GestionAsignaciones::cambiarEstado($asignacion, $hacia, trim($justificacion), $request->user());

        return back()->with('success', $mensaje);
    }

    private function hayGestorVigente(Fase $fase): bool
    {
        return Asignacion::where('fase_id', $fase->id)->where('estado', 'vigente')
            ->whereHas('cargo', fn ($q) => $q->where('nombre', Asignacion::CARGO_GESTOR_TH))->exists();
    }
}
