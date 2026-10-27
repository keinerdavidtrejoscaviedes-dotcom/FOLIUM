<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\SICA\Entities\Program;
use Modules\TalentoHumano\Entities\Designacion;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\Instructores;

/**
 * Designaciones de instructores (RF-016; HU-009 y HU-010).
 */
class DesignacionesController extends Controller
{
    public function index(Request $request)
    {
        $vista = in_array($request->query('vista'), ['vigentes', 'historial', 'psicologos']) ? $request->query('vista') : 'vigentes';
        $relaciones = ['persona', 'programa', 'fase', 'asignadaPor', 'retiradaPor'];
        $vigentes = Designacion::vigentes()->with($relaciones)->orderByRaw("FIELD(tipo, 'lider_estrategia', 'instructor_th', 'instructor_tecnico')")->latest('id')->get();
        $instructores = Instructores::designables();

        return view('talentohumano::lider.designaciones.index', [
            'vista' => $vista,
            'vigentes' => $vigentes,
            'historial' => Designacion::retiradas()->with($relaciones)->latest('fecha_retiro')->latest('id')->get(),
            'psicologos' => Instructores::psicologos(),
            'instructores' => $instructores,
            'correos' => $instructores->pluck('correo', 'id'),
            'programas' => $this->programas(),
            'fase' => Fase::actual(),
            'conteo' => $vigentes->countBy('tipo'),
        ]);
    }

    /**
     * Nueva designación (HU-009).
     */
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $fase = $request->input('vigencia') === 'fase' ? Fase::actual() : null;

        // Solo un Líder de la estrategia vigente (HU-009, esc. 7)
        if ($datos['tipo'] === 'lider_estrategia' && Designacion::vigentes()->where('tipo', 'lider_estrategia')->exists()) {
            throw ValidationException::withMessages(['tipo' => 'Solo puede haber un Líder de la estrategia por fase. Retire primero la designación actual'])->errorBag('designar');
        }
        $repetida = Designacion::vigentes()->where('person_id', $datos['person_id'])->where('tipo', $datos['tipo'])
            ->where('program_id', $datos['program_id'] ?? null)->exists();
        if ($repetida) {
            throw ValidationException::withMessages(['person_id' => 'El instructor ya tiene esta designación vigente'])->errorBag('designar');
        }

        $designacion = Designacion::create([
            'person_id' => $datos['person_id'],
            'tipo' => $datos['tipo'],
            'program_id' => $datos['tipo'] === 'instructor_tecnico' ? $datos['program_id'] : null,
            'fase_id' => $fase?->id,
            'asignada_por' => auth()->user()->person_id,
        ]);
        Auditoria::registrar('designacion_asignada', $designacion, [], $this->instantanea($designacion));

        return redirect()->route('talentohumano.lider.designaciones.index')->with('success', 'Designación asignada. El instructor ya ve sus funciones en FOLIUM.');
    }

    /**
     * La función pasa a otro instructor; quien la ejercía queda en el historial (HU-010, esc. 2).
     */
    public function reasignar(Request $request, Designacion $designacion)
    {
        abort_if($designacion->fecha_retiro, 404);
        $datos = $request->validateWithBag('reasignar', [
            'person_id' => ['required', 'integer', Rule::notIn([$designacion->person_id]), function ($campo, $valor, $falla) {
                if (!Instructores::esDesignable((int) $valor)) {
                    $falla('Seleccione un instructor de la lista');
                }
            }],
            'motivo' => ['required', 'string', 'max:200'],
        ], ['required' => 'Este campo es obligatorio', 'person_id.not_in' => 'Seleccione un instructor distinto al actual']);

        $nueva = DB::transaction(function () use ($designacion, $datos) {
            $nueva = Designacion::create($designacion->only(['tipo', 'program_id', 'fase_id']) + [
                'person_id' => $datos['person_id'],
                'asignada_por' => auth()->user()->person_id,
            ]);
            $this->retirarDesignacion($designacion, 'Reasignada a ' . $this->nombre($nueva) . ': ' . $datos['motivo']);
            Auditoria::registrar('designacion_reasignada', $nueva, $this->instantanea($designacion), $this->instantanea($nueva));

            return $nueva;
        });

        return redirect()->route('talentohumano.lider.designaciones.index')
            ->with('success', 'Designación reasignada a ' . $this->nombre($nueva) . '.');
    }

    /**
     * Retiro de una designación sin borrarla (HU-010, esc. 1 y 3).
     */
    public function retirar(Request $request, Designacion $designacion)
    {
        abort_if($designacion->fecha_retiro, 404);
        $datos = $request->validateWithBag('retirar', ['motivo' => ['required', 'string', 'max:200']], ['required' => 'Este campo es obligatorio']);

        if ($pendientes = $designacion->pendientes()) {
            return back()
                ->with('error', 'No se puede retirar: el instructor tiene pendientes en un proceso en curso')
                ->with('pendientes', ['designacion' => $designacion->id, 'nombre' => $this->nombre($designacion), 'lista' => $pendientes]);
        }

        $antes = $this->instantanea($designacion);
        $this->retirarDesignacion($designacion, $datos['motivo']);
        Auditoria::registrar('designacion_retirada', $designacion, $antes, $this->instantanea($designacion->fresh()));

        return back()->with('success', 'Designación retirada. Se conserva en el historial.');
    }

    private function retirarDesignacion(Designacion $designacion, string $motivo): void
    {
        $designacion->update([
            'fecha_retiro' => now()->toDateString(),
            'retirada_por' => auth()->user()->person_id,
            'motivo_retiro' => mb_substr($motivo, 0, 255),
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validateWithBag('designar', [
            'person_id' => ['required', 'integer', function ($campo, $valor, $falla) {
                if (!Instructores::esDesignable((int) $valor)) {
                    $falla('Seleccione un instructor de la lista: las designaciones son solo para instructores');
                }
            }],
            'tipo' => ['required', Rule::in(array_keys(Designacion::TIPOS))],
            'program_id' => ['required_if:tipo,instructor_tecnico', 'nullable', Rule::in($this->programas()->pluck('id'))],
            'vigencia' => ['required', Rule::in(Fase::actual() ? ['indefinida', 'fase'] : ['indefinida'])],
        ], [
            'required' => 'Este campo es obligatorio',
            'program_id.required_if' => 'Indique el programa de formación del instructor técnico',
            'vigencia.in' => 'No hay una fase en curso: la vigencia debe ser indefinida',
        ]);
    }

    /**
     * Programas de los instructores técnicos: los tecnólogos compatibles de algún cargo del catálogo.
     */
    private function programas()
    {
        return Program::whereIn('id', DB::table('th_cargo_programas')->select('program_id'))->orderBy('name')->orderBy('sofia_code')->get(['id', 'name', 'sofia_code']);
    }

    private function nombre(Designacion $designacion): string
    {
        $persona = $designacion->persona;

        return trim($persona->first_name . ' ' . $persona->first_last_name . ' ' . $persona->second_last_name);
    }

    private function instantanea(Designacion $designacion): array
    {
        $designacion->load(['persona', 'programa', 'fase']);

        return [
            'instructor' => $this->nombre($designacion),
            'designacion' => $designacion->nombreTipo(),
            'vigencia' => $designacion->fase?->nombre() ?? 'Indefinida',
            'fecha_retiro' => $designacion->fecha_retiro?->toDateString(),
            'motivo_retiro' => $designacion->motivo_retiro,
        ];
    }
}
