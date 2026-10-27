<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
use Modules\TalentoHumano\Support\ComiteReubicacion;
use Modules\TalentoHumano\Support\Correo;

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
     * Casos de aprendices no seleccionados en sus 3 opciones y cargos con cupos vacantes (HU-036, esc. 1, 5, 6 y 8).
     */
    public function reubicaciones(Request $request)
    {
        $convocatoria = ComiteReubicacion::convocatoria();
        if (!$convocatoria) {
            return view('talentohumano::seleccion.reubicaciones', ['convocatoria' => null]);
        }

        $pendientes = ComiteReubicacion::pendientes($convocatoria);
        $caso = $pendientes->firstWhere('id', (int) $request->query('postulacion', old('postulacion_id'))) ?? $pendientes->first();
        $vacantes = ComiteReubicacion::vacantes($convocatoria);

        return view('talentohumano::seleccion.reubicaciones', [
            'convocatoria' => $convocatoria,
            'pendientes' => $pendientes,
            'caso' => $caso,
            'evaluacion' => $caso ? $this->evaluacionDe($caso) : null,
            'vacantes' => $caso
                ? $vacantes->map(fn ($v) => $v + ['compatible' => ComiteReubicacion::esCompatible($v, $caso)])->sortByDesc('compatible')->values()
                : $vacantes,
            'integrantes' => ComiteReubicacion::integrantes($convocatoria),
            'decididas' => ComiteReubicacion::decididas($convocatoria),
        ]);
    }

    /**
     * Registra la decisión del comité y notifica al aprendiz (HU-036, esc. 2 a 4 y 6; RN-022, RN-049).
     */
    public function guardarReubicacion(Request $request)
    {
        $convocatoria = ComiteReubicacion::convocatoria();
        abort_unless($convocatoria, 404);

        $datos = $request->validate([
            'postulacion_id' => ['required', 'integer'],
            'cargo_activado_id' => ['required', 'regex:/^(sin_cargo|\d+)$/'],
            'participantes' => ['required', 'array', 'min:1'],
            'participantes.*' => ['integer'],
            'fecha_reunion' => ['required', 'date', 'before_or_equal:today'],
            'justificacion' => ['nullable', 'string', 'max:2000'],
        ], [
            'cargo_activado_id.required' => 'Seleccione el cargo en el que se reubica al aprendiz',
            'cargo_activado_id.regex' => 'Seleccione el cargo en el que se reubica al aprendiz',
            'participantes.required' => 'Registre los participantes de la reunión de reubicación',
            'participantes.min' => 'Registre los participantes de la reunión de reubicación',
            'fecha_reunion.required' => 'Indique la fecha de la reunión del comité',
            'fecha_reunion.before_or_equal' => 'La fecha de la reunión no puede ser posterior a hoy',
        ]);

        $caso = ComiteReubicacion::pendientes($convocatoria)->firstWhere('id', (int) $datos['postulacion_id']);
        if (!$caso) {
            return redirect()->route('talentohumano.seleccion.reubicaciones')->with('error', 'Ese aprendiz ya no tiene la reubicación pendiente.');
        }

        $integrantes = ComiteReubicacion::integrantes($convocatoria)->keyBy('id');
        $participantes = collect($datos['participantes'])->map(fn ($id) => $integrantes[(int) $id] ?? null)->filter()->values();
        $justificacion = trim((string) ($datos['justificacion'] ?? ''));
        $sinCargo = (string) $datos['cargo_activado_id'] === 'sin_cargo';
        $vacante = $sinCargo ? null : ComiteReubicacion::vacantes($convocatoria)->first(fn ($v) => $v['cargo_activado']->id === (int) $datos['cargo_activado_id']);

        $errores = array_filter([
            'participantes' => $participantes->isEmpty() ? 'Registre los participantes de la reunión de reubicación' : null,
            'cargo_activado_id' => !$sinCargo && !$vacante ? 'Ese cargo ya no tiene cupos vacantes' : null,
            'justificacion' => match (true) {
                $sinCargo && $justificacion === '' => 'La decisión «Sin cargo asignado» requiere justificación',
                $vacante && !ComiteReubicacion::esCompatible($vacante, $caso) && $justificacion === '' => 'La reubicación en un cargo no compatible requiere justificación',
                default => null,
            },
        ]);
        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        DB::transaction(function () use ($caso, $vacante, $participantes, $datos, $justificacion) {
            $reubicacion = Reubicacion::create([
                'postulacion_id' => $caso->id,
                'cargo_activado_id' => $vacante ? $vacante['cargo_activado']->id : null,
                'participantes' => $participantes->map(fn ($p) => ['person_id' => $p->id, 'nombre' => $p->nombre, 'funcion' => $p->funcion])->all(),
                'fecha_reunion' => $datos['fecha_reunion'],
                'justificacion' => $justificacion ?: null,
                'registrada_por' => auth()->user()->person_id,
            ]);
            if ($vacante) {
                $caso->update(['estado' => 'reubicado']);
            }
            Auditoria::registrar('reubicacion_registrada', $reubicacion, ['estado' => 'no_seleccionado'], [
                'aprendiz' => $caso->nombre_completo,
                'cargo' => $vacante ? $vacante['cargo_activado']->cargo->nombre : 'Sin cargo asignado',
                'compatible' => $vacante ? ComiteReubicacion::esCompatible($vacante, $caso) : null,
                'participantes' => $participantes->pluck('nombre')->implode(', '),
                'fecha_reunion' => $datos['fecha_reunion'],
                'justificacion' => $justificacion ?: null,
            ]);
        });

        // HU-036, esc. 2: aviso al aprendiz de que quedó en un cargo distinto a sus opciones
        $aviso = '';
        if ($vacante) {
            $correo = DB::table('users')->where('person_id', $caso->person_id)->whereNull('deleted_at')->value('email')
                ?? $caso->person?->misena_email ?? $caso->person?->sena_email ?? $caso->person?->personal_email;
            $aviso = !$correo
                ? ' El aprendiz no tiene correo registrado, así que no se le pudo notificar.'
                : (Correo::enviar([$correo], 'FOLIUM: fuiste reubicado en un cargo de SENA Empresa',
                    "FOLIUM · Talento Humano

Hola, {$caso->nombre_completo}.

No quedaste seleccionado en ninguna de tus tres opciones de la convocatoria, "
                    . "pero el comité de reubicación te asignó el cargo {$vacante['cargo_activado']->cargo->nombre}.

"
                    . 'La reubicación quedará en el acta de seleccionados de la convocatoria.')
                    ? ' Se le notificó por correo.'
                    : ' El correo no se pudo entregar; quedó en «Notificaciones no entregadas».');
        }

        return redirect()->route('talentohumano.seleccion.reubicaciones')->with('success', $vacante
            ? "Reubicación registrada: {$caso->nombre_completo} queda en el cargo {$vacante['cargo_activado']->cargo->nombre}.{$aviso}"
            : "Decisión registrada: {$caso->nombre_completo} queda sin cargo asignado.");
    }

    /**
     * Puntaje total, prueba psicotécnica, conceptos de entrevista y opciones del aprendiz (HU-036, esc. 1).
     */
    private function evaluacionDe(Postulacion $postulacion): array
    {
        $calificaciones = DB::table('th_calificaciones as c')->join('th_entrevistas as e', 'e.id', '=', 'c.entrevista_id')
            ->where('c.postulacion_id', $postulacion->id)
            ->get(['e.tipo', 'c.puntaje', 'c.concepto', 'c.observaciones']);

        return [
            'prueba' => DB::table('th_aplicaciones_prueba')->where('postulacion_id', $postulacion->id)->first(['estado', 'puntaje']),
            'entrevistas' => $calificaciones->groupBy('tipo')->map(fn ($c) => [
                'puntaje' => round($c->avg('puntaje'), 1),
                'concepto' => $c->every(fn ($x) => $x->concepto === 'apto') ? 'Apto' : ($c->contains('concepto', 'apto') ? 'Dividido' : 'No apto'),
                'observacion' => $c->pluck('observaciones')->filter()->first(),
            ]),
            'opciones' => $postulacion->opciones()->with('convocatoriaCargo.cargoActivado.cargo')->get(),
        ];
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
