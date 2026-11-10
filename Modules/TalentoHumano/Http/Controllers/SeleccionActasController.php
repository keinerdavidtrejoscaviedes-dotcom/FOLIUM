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
use Modules\TalentoHumano\Support\PropuestaSeleccion;

class SeleccionActasController extends Controller
{
    /**
     * Bandeja de propuestas de selección (HU-038 · Mockup INS-01 / INS-06).
     */
    public function index(Request $request)
    {
        $convocatoria = ComiteReubicacion::convocatoria();
        $fase = $convocatoria?->fase ?? Fase::actual();

        // Propuesta calculada por puntaje total y jerarquía de preferencia (RF-010)
        $cargosProcesados = $convocatoria ? $this->procesarPropuestasCargos(PropuestaSeleccion::sincronizar($convocatoria)) : collect();

        return view('talentohumano::seleccion.index', [
            'convocatoria' => $convocatoria,
            'fase' => $fase,
            'pendientes' => $cargosProcesados->where('es_validado', false)->values(),
            'validadas' => $cargosProcesados->where('es_validado', true)->values(),
            'porReubicar' => $convocatoria ? ComiteReubicacion::pendientes($convocatoria)->count() : 0,
        ]);
    }

    /**
     * Ficha de validación de un cargo específico (HU-038 · Mockup INS-03).
     */
    public function validarCargo($id)
    {
        $convocatoriaCargo = ConvocatoriaCargo::with(['convocatoria.fase', 'cargoActivado.cargo'])->findOrFail($id);
        $convocatoria = $convocatoriaCargo->convocatoria;
        $calculo = PropuestaSeleccion::sincronizar($convocatoria);
        $propuesta = $calculo->cargos[$convocatoriaCargo->id];

        $primeraSeleccion = Seleccion::with('validadaPor')->where('convocatoria_cargo_id', $convocatoriaCargo->id)
            ->whereIn('estado', ['validada', 'ajustada'])->first();

        return view('talentohumano::seleccion.validar', [
            'convocatoriaCargo' => $convocatoriaCargo,
            'cargo' => $convocatoriaCargo->cargoActivado->cargo,
            'convocatoria' => $convocatoria,
            'candidatos' => $this->obtenerCandidatosPorMerito($propuesta, $calculo),
            'esValidado' => $propuesta->validado,
            'primeraSeleccion' => $primeraSeleccion,
            'esEmpate' => (bool) $propuesta->empatados,
            'esVacante' => $propuesta->vacante,
            'sinAptos' => $propuesta->sin_aptos,
            'umbral' => $calculo->umbral,
            'politica' => $calculo->politica,
        ]);
    }

    /**
     * Valida o ajusta la propuesta de un cargo; todo ajuste o empate exige justificación (HU-035, HU-038 · RN-021).
     */
    public function guardarValidacion(Request $request, $id)
    {
        $convocatoriaCargo = ConvocatoriaCargo::with(['convocatoria', 'cargoActivado.cargo'])->findOrFail($id);
        $nombreCargo = $convocatoriaCargo->cargoActivado->cargo->nombre;
        $request->validate([
            'candidatos_ids' => 'nullable|array',
            'candidatos_ids.*' => 'integer',
            'justificacion' => 'nullable|string|max:2000',
        ]);

        $calculo = PropuestaSeleccion::calcular($convocatoriaCargo->convocatoria);
        $propuesta = $calculo->cargos[$convocatoriaCargo->id];

        // Cada cargo se valida una sola vez, por el Instructor Líder o por el Instructor de TH (HU-035 esc. 6, HU-038 esc. 5)
        if ($propuesta->validado) {
            return redirect()->route('talentohumano.seleccion.validar', $convocatoriaCargo->id)->with('error', 'Este cargo ya fue validado y no se puede volver a validar.');
        }

        $elegidos = collect($request->input('candidatos_ids', []))->map(fn ($v) => (int) $v)->unique()->sort()->values();
        $yaSeleccionados = $calculo->cargos->where('validado', true)->pluck('validados')->flatten();
        $disponibles = $propuesta->candidatos->where('elegible', true)->pluck('postulacion_id')->diff($yaSeleccionados);
        $errores = array_filter([
            'candidatos_ids' => match (true) {
                $elegidos->count() > $propuesta->cupos => "El cargo tiene {$propuesta->cupos} " . ($propuesta->cupos == 1 ? 'cupo' : 'cupos') . '.',
                $elegidos->diff($disponibles)->isNotEmpty() => 'Solo puede elegir aspirantes evaluados que no estén seleccionados en otro cargo.',
                default => null,
            },
        ]);

        $esAjuste = $elegidos->all() !== collect($propuesta->propuestos)->sort()->values()->all();
        $justificacion = trim((string) $request->justificacion);
        if (($esAjuste || $propuesta->empatados) && $justificacion === '') {
            $errores['justificacion'] = 'Todo ajuste a la selección requiere justificación';
        }
        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        $this->ejecutarRegistroSeleccion($convocatoriaCargo, $elegidos->all(), $calculo, $esAjuste, $justificacion);
        PropuestaSeleccion::sincronizar($convocatoriaCargo->convocatoria);

        // La validación la habilita la designación, aunque quien valide también sea Administrador (RN-021, RN-036)
        $designacion = AccesoTH::designacionValidadora(auth()->user()) === 'instructor_th' ? 'Instructor de Talento Humano' : 'Líder de la estrategia';
        Auditoria::registrar($esAjuste ? 'seleccion_ajustada' : 'seleccion_validada', $convocatoriaCargo, ['postulaciones' => $propuesta->propuestos], [
            'cargo' => $nombreCargo,
            'postulaciones' => $elegidos->all(),
            'justificacion' => $justificacion ?: null,
        ], ['Designación', $designacion]);

        return redirect()->route('talentohumano.seleccion.index')->with('success', $elegidos->isEmpty()
            ? "«{$nombreCargo}» quedó declarado vacante con tu designación {$designacion}."
            : "Selección de «{$nombreCargo}» validada con éxito con tu designación {$designacion}.");
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
            $correo = Correo::de($caso->person_id);
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
     * Fila de la bandeja por cargo, con el estado de su propuesta.
     */
    private function procesarPropuestasCargos(object $calculo)
    {
        $nombres = Postulacion::with('person')->whereIn('id', $calculo->totales->keys())->get()->keyBy('id');
        $listar = fn (array $ids) => collect($ids)->map(fn ($id) => ($nombres[$id]->nombre_completo ?? 'Aspirante') . ' (' . ($calculo->totales[$id]->total ?? '—') . ')')->join(' · ');

        return $calculo->cargos->map(function ($cargo) use ($listar) {
            [$estado, $chip] = match (true) {
                $cargo->validado => ['Validada', 'bg-emerald-100 text-emerald-800'],
                (bool) $cargo->empatados => ['Empate por resolver', 'bg-red-100 text-red-700'],
                $cargo->vacante => [$cargo->candidatos->isEmpty() ? 'Sin postulantes' : 'Vacante por política', 'bg-slate-100 text-slate-600'],
                $cargo->sin_aptos => ['Nadie alcanza el umbral', 'bg-amber-100 text-amber-800'],
                default => ['Propuesta', 'bg-amber-100 text-amber-800'],
            };

            return (object) [
                'id' => $cargo->id,
                'cargo_nombre' => $cargo->nombre,
                'cupos' => $cargo->cupos,
                'propuesta' => match (true) {
                    $cargo->validado => $cargo->validados ? $listar($cargo->validados) : 'Declarado vacante',
                    (bool) $cargo->propuestos => $listar($cargo->propuestos),
                    default => $cargo->candidatos->isEmpty() ? 'Sin postulantes' : 'Sin candidatos disponibles',
                },
                'estado' => $estado,
                'chip' => $chip,
                'accion' => $cargo->validado ? 'Consultar' : 'Validar',
                'es_validado' => $cargo->validado,
            ];
        })->values();
    }

    /**
     * Candidatos del cargo ordenados por opción y puntaje total, marcando la propuesta del sistema.
     */
    private function obtenerCandidatosPorMerito(object $propuesta, object $calculo)
    {
        $nombres = Postulacion::with('person')->whereIn('id', $propuesta->candidatos->pluck('postulacion_id'))->get()->keyBy('id');
        $otrosValidados = $calculo->cargos->where('validado', true)->where('id', '!=', $propuesta->id)->pluck('validados')->flatten()->flip();
        $marcados = $propuesta->validado ? $propuesta->validados : $propuesta->propuestos;

        return $propuesta->candidatos->map(fn ($c) => (object) [
            'postulacion_id' => $c->postulacion_id,
            'nombre' => $nombres[$c->postulacion_id]->nombre_completo ?? 'Aspirante',
            'preferencia' => $c->preferencia,
            'prueba' => $c->prueba,
            'entrevista' => $c->entrevista,
            'evaluadores' => $c->evaluadores,
            'total' => $c->total,
            'seleccionado' => in_array($c->postulacion_id, $marcados),
            'empate' => in_array($c->postulacion_id, $propuesta->empatados),
            'alcanza_umbral' => $c->total !== null && $c->total >= $calculo->umbral,
            'nota' => match (true) {
                !$c->elegible => 'Sin prueba presentada o sin entrevista calificada',
                isset($otrosValidados[$c->postulacion_id]) => 'Ya seleccionado en otro cargo',
                default => null,
            },
        ])->sortBy([['preferencia', 'asc'], ['total', 'desc']])->values();
    }

    /**
     * Guarda la decisión: una fila por seleccionado o, si no hay ninguno, el cargo declarado vacante (RN-020).
     */
    private function ejecutarRegistroSeleccion(ConvocatoriaCargo $convocatoriaCargo, array $elegidos, object $calculo, bool $esAjuste, string $justificacion): void
    {
        $comun = [
            'convocatoria_cargo_id' => $convocatoriaCargo->id,
            'estado' => $esAjuste ? 'ajustada' : 'validada',
            'justificacion' => $justificacion ?: null,
            'validada_por' => auth()->user()->person_id,
            'validada_con' => AccesoTH::designacionValidadora(auth()->user()),
            'fecha_validacion' => now(),
        ];

        DB::transaction(function () use ($convocatoriaCargo, $elegidos, $calculo, $comun) {
            Seleccion::where('convocatoria_cargo_id', $convocatoriaCargo->id)->delete();
            foreach ($elegidos ?: [null] as $postulacionId) {
                Seleccion::create($comun + [
                    'postulacion_id' => $postulacionId,
                    'puntaje_total' => $postulacionId ? $calculo->totales[$postulacionId]->total : null,
                ]);
            }
        });
    }
}
