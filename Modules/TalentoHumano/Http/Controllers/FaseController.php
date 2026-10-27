<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Fases de SENA Empresa, identificadas por su número (SRS v1.4, 1.5), y la preparación de la siguiente:
 * fichas → activación de cargos → cronograma → convocatoria (RF-002, HU-021).
 *
 * Supuesto: el SRS no define quién registra la fase entrante (pendiente de HU-060); por ahora la registra
 * quien prepara la fase, el Gestor de TH o el instructor que lo acompaña.
 */
class FaseController extends Controller
{
    public const ESTADOS = [
        'planeacion' => 'En preparación',
        'seleccion' => 'En selección',
        'empalme' => 'En empalme',
        'en_curso' => 'En curso',
        'cerrada' => 'Cerrada',
    ];

    public function __construct(private FaseService $fases)
    {
    }

    public function index()
    {
        $fases = DB::table('th_fases as f')
            ->whereNull('f.deleted_at')
            ->select('f.*')
            ->addSelect([
                'fichas' => DB::table('th_fase_fichas')->selectRaw('count(*)')->whereColumn('fase_id', 'f.id'),
                'cargos' => DB::table('th_cargos_activados')->selectRaw('count(*)')->whereColumn('fase_id', 'f.id'),
            ])
            ->orderByDesc('f.numero')
            ->get();

        return view('talentohumano::fases.index', [
            'fases' => $fases,
            'siguienteNumero' => ($fases->max('numero') ?? 0) + 1,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'numero' => 'required|integer|min:1|max:65535|unique:th_fases,numero',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ], [
            'numero.required' => 'Indique el número de la fase.',
            'numero.unique' => 'Ya existe una fase con ese número.',
            'fecha_inicio.required' => 'Indique la fecha de inicio de la fase.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        $id = DB::table('th_fases')->insertGetId($datos + [
            'estado' => 'planeacion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auditoria::registrar('Registrar fase', 'th_fases', $id, $datos);

        return redirect()->route('talentohumano.fases.show', $id)
            ->with('success', "Fase {$datos['numero']} registrada. Siga con las fichas de la fase.");
    }

    /**
     * Preparación de la fase: estado de cada paso y qué falta para el siguiente.
     */
    public function show(int $fase)
    {
        $fase = $this->fases->buscar($fase);

        return view('talentohumano::fases.show', [
            'fase' => $fase,
            'resumen' => $this->fases->resumen($fase->id),
        ]);
    }
}
