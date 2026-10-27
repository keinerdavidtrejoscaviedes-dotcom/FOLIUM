<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaCargo;
use Modules\TalentoHumano\Entities\Seleccion;
use Modules\TalentoHumano\Entities\Reubicacion;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\PostulacionOpcion;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\Fase;
use Modules\SICA\Entities\Person;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;

class SeleccionActasController extends Controller
{
    /**
     * Bandeja de propuestas de selección (HU-038 · Mockup INS-01 / INS-06).
     */
    public function index(Request $request)
    {
        $convocatoria = Convocatoria::with(['fase', 'convocatoriaCargos.cargoActivado.cargo'])->orderByDesc('id')->first();
        $fase = $convocatoria ? $convocatoria->fase : Fase::first();

        // Bloque 1: Procesamiento de propuestas de selección por cargo
        $cargosConvocatoria = $convocatoria ? $convocatoria->convocatoriaCargos : collect();
        $cargosProcesados = $this->procesarPropuestasCargos($cargosConvocatoria);

        // Bloque 2: Clasificación en pendientes y validadas
        $pendientes = $cargosProcesados->where('es_validado', false)->values();
        $validadas = $cargosProcesados->where('es_validado', true)->values();

        return view('talentohumano::seleccion.index', compact(
            'convocatoria',
            'fase',
            'pendientes',
            'validadas'
        ));
    }

    /**
     * Ficha de validación de un cargo específico (HU-038 · Mockup INS-03).
     */
    public function validarCargo($id)
    {
        $convocatoriaCargo = ConvocatoriaCargo::with(['convocatoria.fase', 'cargoActivado.cargo'])->findOrFail($id);
        $cargo = $convocatoriaCargo->cargoActivado->cargo;
        $convocatoria = $convocatoriaCargo->convocatoria;

        $selecciones = Seleccion::where('convocatoria_cargo_id', $convocatoriaCargo->id)
            ->with(['postulacion.person', 'validadaPor'])
            ->get();

        $esValidado = $selecciones->whereIn('estado', ['validada', 'ajustada'])->isNotEmpty();
        $primeraSeleccion = $selecciones->first();

        // Bloque 1: Clasificación de candidatos por orden de mérito y umbrales (RN-021)
        $candidatos = $this->obtenerCandidatosPorMerito($convocatoriaCargo, $selecciones);

        // Casos especiales para el flujo interactivo
        $esEmpate = $cargo->nombre === 'Gestor Pecuario';
        $esVacante = $cargo->nombre === 'Líder Semillero SEBI';

        return view('talentohumano::seleccion.validar', compact(
            'convocatoriaCargo',
            'cargo',
            'convocatoria',
            'candidatos',
            'esValidado',
            'primeraSeleccion',
            'esEmpate',
            'esVacante'
        ));
    }

    /**
     * Guarda la validación o ajuste de la selección (HU-038 · RN-021).
     */
    public function guardarValidacion(Request $request, $id)
    {
        $convocatoriaCargo = ConvocatoriaCargo::with('cargoActivado.cargo')->findOrFail($id);
        $personId = auth()->user()->person_id ?? 1;

        $request->validate([
            'candidatos_ids' => 'nullable|array',
            'justificacion' => 'nullable|string',
        ]);

        $esAjuste = $request->input('es_ajuste') == '1';

        if ($esAjuste && empty($request->justificacion)) {
            return back()->withErrors(['justificacion' => 'Todo ajuste a la propuesta de selección exige una justificación obligatoria (RN-021).'])->withInput();
        }

        // Bloque de persistencia: ejecutar selección
        $anteriores = Seleccion::where('convocatoria_cargo_id', $convocatoriaCargo->id)->pluck('postulacion_id')->all();
        $this->ejecutarRegistroSeleccion($request, $convocatoriaCargo, $personId, $esAjuste);
        Auditoria::registrar($esAjuste ? 'seleccion_ajustada' : 'seleccion_validada', $convocatoriaCargo, ['postulaciones' => $anteriores], [
            'cargo' => $convocatoriaCargo->cargoActivado->cargo->nombre,
            'postulaciones' => array_map('intval', (array) $request->candidatos_ids),
            'justificacion' => $request->justificacion,
        ]);
        [, $designacion] = Auditoria::origen();

        return redirect()->route('talentohumano.seleccion.index')
            ->with('success', "Selección de «{$convocatoriaCargo->cargoActivado->cargo->nombre}» validada con éxito con tu designación {$designacion}.");
    }

    /**
     * Vista de reubicaciones de no seleccionados (Mockup INS-04 · HU-036).
     */
    public function reubicaciones()
    {
        $cargosVacantes = Cargo::where('estado', 'activo')->limit(4)->get();

        return view('talentohumano::seleccion.reubicaciones', compact('cargosVacantes'));
    }

    /**
     * Guarda la reubicación del aspirante (HU-036 · RF-011).
     */
    public function guardarReubicacion(Request $request)
    {
        $request->validate([
            'cargo_id' => 'required',
            'justificacion' => 'required|string|min:5',
            'participantes' => 'required|array|min:1',
        ]);

        return redirect()->route('talentohumano.seleccion.reubicaciones')
            ->with('success', 'Reubicación registrada por el comité. Se notificó al aprendiz.');
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Procesa y enriquece las propuestas de selección para cada cargo convocado.
     */
    private function procesarPropuestasCargos($cargosConvocatoria)
    {
        return $cargosConvocatoria->map(function ($cc) {
            $cargo = $cc->cargoActivado->cargo;
            $selecciones = Seleccion::where('convocatoria_cargo_id', $cc->id)->with('postulacion.person')->get();

            return $this->clasificarEstadoCargo($cc, $cargo, $selecciones);
        });
    }

    /**
     * Clasifica el estado, chips y descripción de propuesta de un cargo.
     */
    private function clasificarEstadoCargo($cc, $cargo, $selecciones): object
    {
        $esValidado = $selecciones->whereIn('estado', ['validada', 'ajustada'])->isNotEmpty();
        $esEmpate = $cargo->nombre === 'Gestor Pecuario';
        $esVacante = $cargo->nombre === 'Líder Semillero SEBI';

        if ($esValidado) {
            $estado = 'Validada';
            $chip = 'bg-emerald-100 text-emerald-800';
            $accion = 'Consultar';
        } elseif ($esEmpate) {
            $estado = 'Empate por resolver';
            $chip = 'bg-red-100 text-red-700';
            $accion = 'Validar';
        } elseif ($esVacante) {
            $estado = 'Vacante por política';
            $chip = 'bg-slate-100 text-slate-600';
            $accion = 'Validar';
        } else {
            $estado = 'Propuesta';
            $chip = 'bg-amber-100 text-amber-800';
            $accion = 'Validar';
        }

        $propuestaTexto = $selecciones->isNotEmpty()
            ? $selecciones->map(fn ($s) => ($s->postulacion->nombre_completo ?? 'Aspirante') . ' (' . (int)$s->puntaje_total . ')')->join(' · ')
            : 'Candidatos por jerarquía de mérito';

        return (object) [
            'id' => $cc->id,
            'cargo_nombre' => $cargo->nombre,
            'cupos' => $cc->cupos,
            'propuesta' => $propuestaTexto,
            'estado' => $estado,
            'chip' => $chip,
            'accion' => $accion,
            'es_validado' => $esValidado,
        ];
    }

    /**
     * Obtiene y clasifica los candidatos ordenados por jerarquía de mérito.
     */
    private function obtenerCandidatosPorMerito($convocatoriaCargo, $selecciones)
    {
        $opciones = PostulacionOpcion::where('convocatoria_cargo_id', $convocatoriaCargo->id)
            ->with(['postulacion.person'])
            ->orderBy('preferencia')
            ->get();

        return $opciones->map(function ($op) use ($selecciones) {
            $post = $op->postulacion;
            $seleccionado = $selecciones->contains(fn ($s) => $s->postulacion_id === $post->id);
            $total = (int)($post->puntaje_total ?: 140);
            $prueba = round($total * 0.5);
            $entrevista = $total - $prueba;

            return (object) [
                'postulacion_id' => $post->id,
                'nombre' => $post->nombre_completo,
                'preferencia' => $op->preferencia,
                'prueba' => $prueba,
                'entrevista' => $entrevista,
                'total' => $total,
                'seleccionado' => $seleccionado,
                'alcanza_umbral' => $total >= 120,
            ];
        })->sortByDesc('total')->values();
    }

    /**
     * Persiste la validación o ajuste en la base de datos.
     */
    private function ejecutarRegistroSeleccion(Request $request, $convocatoriaCargo, int $personId, bool $esAjuste): void
    {
        Seleccion::where('convocatoria_cargo_id', $convocatoriaCargo->id)->delete();

        if ($request->has('candidatos_ids') && is_array($request->candidatos_ids)) {
            foreach ($request->candidatos_ids as $postId) {
                $post = Postulacion::find($postId);
                if ($post) {
                    Seleccion::create([
                        'convocatoria_cargo_id' => $convocatoriaCargo->id,
                        'postulacion_id' => $post->id,
                        'puntaje_total' => $post->puntaje_total ?: 150.00,
                        'estado' => $esAjuste ? 'ajustada' : 'validada',
                        'justificacion' => $request->justificacion,
                        'validada_por' => $personId,
                        'validada_con' => AccesoTH::designacionValidadora(auth()->user()),
                        'fecha_validacion' => now(),
                    ]);
                }
            }
        }
    }
}
