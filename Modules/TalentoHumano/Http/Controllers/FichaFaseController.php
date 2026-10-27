<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Fichas de formación de la fase (HU-017, RF-018, RN-038).
 */
class FichaFaseController extends Controller
{
    public function __construct(private FaseService $fases)
    {
    }

    public function index(int $fase)
    {
        $fase = $this->fases->buscar($fase);
        $disponibles = $this->fases->fichasDisponibles();

        // También las ya asociadas que hayan dejado de estar disponibles, para no retirarlas sin querer
        $asociadas = $this->fases->fichasDeLaFase($fase->id)->whereNotIn('id', $disponibles->pluck('id'));

        return view('talentohumano::fases.fichas', [
            'fase' => $fase,
            'fichas' => $disponibles->concat($asociadas),
            'seleccionadas' => DB::table('th_fase_fichas')->where('fase_id', $fase->id)->pluck('course_id')->all(),
            'bloqueada' => $this->fases->convocatoriaPublicada($fase->id),
        ]);
    }

    /**
     * 'Confirmar selección': asocia las fichas marcadas y retira las desmarcadas.
     */
    public function update(Request $request, int $fase)
    {
        $fase = $this->fases->buscar($fase);

        $seleccion = collect($request->input('fichas', []))->map(fn ($id) => (int) $id)->unique();

        // Escenario 4
        if ($seleccion->isEmpty()) {
            return back()->withErrors(['fichas' => 'Debe seleccionar al menos una ficha para la fase.']);
        }

        $actuales = DB::table('th_fase_fichas')->where('fase_id', $fase->id)->pluck('course_id');
        $nuevas = $seleccion->diff($actuales);
        $retiradas = $actuales->diff($seleccion);

        // Escenario 7
        if (($nuevas->isNotEmpty() || $retiradas->isNotEmpty()) && $this->fases->convocatoriaPublicada($fase->id)) {
            return back()->with('error', 'La convocatoria ya fue publicada; no se pueden modificar las fichas de la fase.');
        }

        // Escenario 2: solo fichas tecnólogo presenciales, aunque se manipule el formulario
        if ($nuevas->diff($this->fases->fichasDisponibles()->pluck('id'))->isNotEmpty()) {
            return back()->withErrors(['fichas' => 'Solo se pueden seleccionar fichas de nivel tecnólogo y modalidad presencial.']);
        }

        DB::transaction(function () use ($fase, $nuevas, $retiradas) {
            DB::table('th_fase_fichas')->where('fase_id', $fase->id)->whereIn('course_id', $retiradas)->delete();

            DB::table('th_fase_fichas')->insert($nuevas->map(fn ($courseId) => [
                'fase_id' => $fase->id,
                'course_id' => $courseId,
                'registrada_por' => auth()->user()->person_id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all());
        });

        Auditoria::registrar('Confirmar fichas de la fase', 'th_fase_fichas', $fase->id, [
            'agregadas' => $nuevas->values()->all(),
            'retiradas' => $retiradas->values()->all(),
        ]);

        $fichas = $this->fases->fichasDeLaFase($fase->id);

        return redirect()->route('talentohumano.fases.fichas.index', $fase->id)
            ->with('success', "Fichas asociadas a la fase correctamente: {$fichas->count()} fichas y {$fichas->sum('aprendices')} aprendices.");
    }

    /**
     * Aprendices vinculados a una ficha (los que podrán ver la convocatoria si la ficha queda habilitada).
     */
    public function aprendices(int $fase, int $ficha)
    {
        $fase = $this->fases->buscar($fase);
        $datosFicha = $this->fases->fichasDisponibles()->firstWhere('id', $ficha)
            ?? $this->fases->fichasDeLaFase($fase->id)->firstWhere('id', $ficha);

        abort_unless($datosFicha, 404);

        $aprendices = DB::table('apprentices as a')
            ->join('people as p', 'p.id', '=', 'a.person_id')
            ->where('a.course_id', $ficha)
            ->whereNull('a.deleted_at')
            ->whereIn('a.apprentice_status', FaseService::ESTADOS_APRENDIZ_ACTIVO)
            ->select('p.document_number', 'p.first_name', 'p.first_last_name', 'p.second_last_name',
                'p.misena_email', 'p.personal_email', 'a.apprentice_status')
            ->orderBy('p.first_last_name')
            ->orderBy('p.first_name')
            ->get();

        return view('talentohumano::fases.aprendices', [
            'fase' => $fase,
            'ficha' => $datosFicha,
            'aprendices' => $aprendices,
        ]);
    }
}
