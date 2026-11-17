<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;
use Modules\TalentoHumano\Support\AccesoTH;

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
            'programas' => $this->fases->programasTecnologo(),
            'fichas' => $disponibles->concat($asociadas),
            'seleccionadas' => DB::table('th_fase_fichas')->where('fase_id', $fase->id)->pluck('course_id')->all(),
            'bloqueada' => $this->fases->convocatoriaPublicada($fase->id),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'fichas_cargos'),
            'avisoConsulta' => AccesoTH::esLiderEstrategia(auth()->user()) && AccesoTH::hayGestorDisponible()
                ? 'La selección de fichas corresponde al Gestor de Talento Humano' : 'Consulta de las fichas de la fase, sin modificarlas.',
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
        $disponibles = $this->fases->fichasDisponibles()->keyBy('id');
        if ($nuevas->diff($disponibles->keys())->isNotEmpty()) {
            return back()->withErrors(['fichas' => 'Solo se pueden seleccionar fichas de nivel tecnólogo y modalidad presencial.']);
        }

        // HU-018 esc. 2 y 3: una ficha sin trimestre o sin programa no se selecciona hasta registrar el dato
        $incompletas = $nuevas->map(fn ($id) => $disponibles[$id])->filter(fn ($f) => $f->sin_programa || $f->sin_trimestre);
        if ($incompletas->isNotEmpty()) {
            return back()->withInput()->withErrors(['fichas' => $incompletas->map(fn ($f) => $f->sin_programa
                ? "Ficha {$f->code}: registre el programa de formación antes de seleccionarla"
                : "Ficha {$f->code}: registre el trimestre de la ficha antes de seleccionarla")->implode('. ') . '.']);
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
     * 'Guardar' el trimestre o el programa de una ficha que llegó sin ese dato (HU-018 esc. 1 y 3).
     */
    public function registrarDatos(Request $request, int $fase, int $ficha)
    {
        $fase = $this->fases->buscar($fase);
        $datosFicha = $this->fases->fichasDisponibles()->firstWhere('id', $ficha);
        abort_unless($datosFicha, 404);

        $programas = $this->fases->programasTecnologo();
        $maximo = (int) ($programas->firstWhere('id', (int) $request->input('program_id'))->quarter_number ?? $datosFicha->quarter_number ?? 8) ?: 8;
        $datos = $request->validate([
            'trimestre' => [$datosFicha->sin_trimestre ? 'required' : 'nullable', 'integer', 'min:1', "max:{$maximo}"],
            'program_id' => [$datosFicha->sin_programa ? 'required' : 'nullable', 'integer', 'in:' . $programas->pluck('id')->implode(',')],
        ], [
            'trimestre.required' => 'Elija el trimestre en curso de la ficha.',
            'trimestre.min' => 'El trimestre debe ser un número entre 1 y ' . $maximo . '.',
            'trimestre.max' => 'El trimestre debe ser un número entre 1 y ' . $maximo . '.',
            'trimestre.integer' => 'El trimestre debe ser un número entre 1 y ' . $maximo . '.',
            'program_id.required' => 'Elija el programa de formación de la ficha.',
            'program_id.in' => 'Elija un programa tecnólogo presencial del centro.',
        ]);

        // Solo se registra lo que falta; lo que trae SICEFA no se sobrescribe
        $cambios = array_filter([
            'trimestre' => $datosFicha->sin_trimestre ? (int) $datos['trimestre'] : null,
            'program_id' => $datosFicha->sin_programa ? (int) $datos['program_id'] : null,
        ]);
        if (!$cambios) {
            return back()->with('info', "La ficha {$datosFicha->code} ya tiene su programa y su trimestre.");
        }

        DB::table('th_fichas_datos')->updateOrInsert(['course_id' => $ficha], $cambios + [
            'registrado_por' => auth()->user()->person_id,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
        Auditoria::registrar('Registrar datos de la ficha', 'th_fase_fichas', $fase->id, ['ficha' => $datosFicha->code] + $cambios + ['origen' => 'manual']);

        return back()->with('success', "Datos de la ficha {$datosFicha->code} registrados. Ya puede seleccionarla para la fase.");
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
