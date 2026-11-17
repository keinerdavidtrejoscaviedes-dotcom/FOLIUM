<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Services\FaseService;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\GestionFases;

/**
 * Fases de SENA Empresa, identificadas por su número (SRS v1.4, 1.5), y la preparación de la siguiente:
 * fichas → activación de cargos → cronograma → convocatoria (RF-002, HU-021).
 *
 * La fase la registran, modifican y eliminan el Gestor de TH y el Administrador (HU-011), con las mismas reglas
 * (Support\GestionFases): número único y fechas que no se cruzan con otra fase.
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
            'puedeGestionar' => AccesoTH::puedeModificar(auth()->user(), 'fases'),
            'bloqueos' => Fase::whereIn('id', $fases->pluck('id'))->get()->mapWithKeys(fn ($f) => [$f->id => GestionFases::bloqueos($f)]),
        ]);
    }

    /**
     * 'Registrar fase' (HU-011): el Gestor de TH o el Administrador, con las mismas reglas.
     */
    public function store(Request $request)
    {
        $datos = GestionFases::validar($request->only('numero', 'fecha_inicio', 'fecha_fin'), null, 'registrar');
        $fase = GestionFases::registrar($datos);

        return redirect()->route('talentohumano.fases.show', $fase->id)
            ->with('success', "Fase {$fase->numero} registrada correctamente. Siga con las fichas de la fase.");
    }

    /**
     * 'Guardar cambios' de una fase: número y fechas, sin cruzarse con otra fase.
     */
    public function update(Request $request, int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $datos = GestionFases::validar($request->only('numero', 'fecha_inicio', 'fecha_fin'), $fase, "editar_{$fase->id}");
        GestionFases::modificar($fase, $datos);

        return redirect()->route('talentohumano.fases.index')->with('success', "Fase {$fase->numero} modificada correctamente.");
    }

    /**
     * 'Eliminar fase': solo si aún no tiene fichas, cargos, cronograma, convocatoria, asignaciones ni empalmes.
     */
    public function destroy(int $fase)
    {
        $fase = Fase::findOrFail($fase);

        try {
            GestionFases::eliminar($fase);
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()->route('talentohumano.fases.index')->with('success', "Fase {$fase->numero} eliminada.");
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
