<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\Firma;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\PostulacionDocumento;
use Modules\TalentoHumano\Entities\PostulacionOpcion;
use Modules\TalentoHumano\Services\CuestionarioService;
use Modules\TalentoHumano\Support\ActaRecibimiento;
use Modules\TalentoHumano\Support\ActaSeleccionados;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\PortalAprendiz;

/**
 * Portal del Aprendiz (HU-043 a HU-057). Cada acción trabaja solo con los datos del Aprendiz que inició sesión (RN-066).
 */
class AprendizController extends Controller
{
    /** Preguntas del Formulario de Satisfacción del Empalme (HU-054), en escala de 1 a 5. */
    public const PREGUNTAS_SATISFACCION = [
        'La información que recibí o entregué sobre el cargo fue clara y estaba actualizada.',
        'El inventario del acta coincide con lo que hay en la unidad.',
        'Quedaron claras las fechas de presentación de evidencias e informes del cargo.',
        'El tiempo dedicado al empalme fue suficiente.',
        'El Gestor de Talento Humano acompañó y orientó el empalme.',
    ];

    public const ESCALA_SATISFACCION = [1 => 'Muy en desacuerdo', 2 => 'En desacuerdo', 3 => 'Neutral', 4 => 'De acuerdo', 5 => 'Muy de acuerdo'];

    /** Documentos de la postulación (th_postulacion_documentos.tipo). */
    public const DOCUMENTOS = ConvocatoriaController::DOCUMENTOS;

    private const ARCHIVO = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
    private const MENSAJE_ARCHIVO = 'Solo se permiten archivos PDF o imagen de hasta 5 MB';

    /**
     * Inicio: siguiente paso del Aprendiz según su situación en el proceso.
     */
    public function index()
    {
        $p = $this->portal();

        return view('talentohumano::aprendiz.panel', [
            'p' => $p,
            'paso' => $this->siguientePaso($p),
            'aplicacion' => $p->postulacion ? $this->aplicacion($p->postulacion) : null,
        ]);
    }

    /**
     * Cronograma aprobado del proceso de selección (HU-043).
     */
    public function cronograma()
    {
        $p = $this->portal();

        return view('talentohumano::aprendiz.cronograma', ['p' => $p, 'etapas' => $p->etapas()]);
    }

    /**
     * Convocatoria y cargos compatibles con la ficha y el programa del Aprendiz (HU-044).
     */
    public function convocatoria()
    {
        $p = $this->portal();

        return view('talentohumano::aprendiz.convocatoria', [
            'p' => $p,
            'cargos' => $p->fichaHabilitada ? $p->cargosCompatibles() : collect(),
            'estadoPostulacion' => $p->estadoPostulacion(),
            'fechas' => $p->fechasPostulacion(),
        ]);
    }

    /**
     * Formulario de postulación con los datos del ERP precargados (HU-045, HU-046).
     */
    public function postularme()
    {
        $p = $this->portal();
        if ($p->postulacion) {
            return redirect()->route('talentohumano.aprendiz.postulacion')->with('info', 'Ya está postulado en esta convocatoria');
        }

        $cargos = $p->fichaHabilitada ? $p->cargosCompatibles() : collect();

        return view('talentohumano::aprendiz.postularme', [
            'p' => $p,
            'cargos' => $cargos,
            'estadoPostulacion' => $p->estadoPostulacion(),
            'fechas' => $p->fechasPostulacion(),
            'municipios' => DB::table('municipalities as m')->join('departments as d', 'd.id', '=', 'm.department_id')
                ->where('d.name', 'Huila')->whereNull('m.deleted_at')->orderBy('m.name')->pluck('m.name', 'm.id'),
            'documentos' => $this->documentosExigidos($cargos),
        ]);
    }

    /**
     * Registra la postulación con sus 3 opciones, la propuesta de cada cargo y los documentos (HU-045, HU-046).
     */
    public function guardarPostulacion(Request $request)
    {
        $p = $this->portal();
        abort_unless($p->fichaHabilitada && $p->convocatoria, 403, 'No tiene permisos para realizar esta acción');

        if ($p->postulacion) {
            return redirect()->route('talentohumano.aprendiz.postulacion')->with('info', 'Ya está postulado en esta convocatoria');
        }
        if ($p->estadoPostulacion() !== 'abierta') {
            return back()->withInput()->with('error', 'La convocatoria está cerrada');
        }

        $cargos = $p->cargosCompatibles()->keyBy('id');
        $datos = $request->validate([
            'telefono' => ['required', 'regex:/^[0-9 +()-]{7,20}$/'],
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
            'vereda_ruta' => ['nullable', 'string', 'max:150'],
            'opciones' => ['array'],
            'opciones.*' => ['nullable', 'integer'],
            'confirmar_alterno' => ['array'],
            'documentos' => ['array'],
            'documentos.*' => self::ARCHIVO,
            'propuestas' => ['array'],
            'propuestas.*' => self::ARCHIVO,
        ], [
            'required' => 'Este campo es obligatorio',
            'telefono.regex' => 'Escriba un número de teléfono válido',
            'mimes' => self::MENSAJE_ARCHIVO,
            'max' => self::MENSAJE_ARCHIVO,
            'file' => self::MENSAJE_ARCHIVO,
        ]);

        // Exactamente 3 cargos distintos, en orden de preferencia, entre los compatibles (RN-009, RN-048)
        $opciones = collect([1, 2, 3])->mapWithKeys(fn ($i) => [$i => (int) ($datos['opciones'][$i] ?? 0)]);
        $elegidas = $opciones->filter();
        $errores = [];
        if ($elegidas->count() !== 3 || count(array_filter($datos['opciones'] ?? [])) !== 3) {
            $errores['opciones'] = 'Debe seleccionar exactamente 3 cargos en orden de preferencia';
        } elseif ($elegidas->unique()->count() !== 3) {
            $errores['opciones'] = 'No puede seleccionar el mismo cargo en más de una opción';
        } elseif ($elegidas->contains(fn ($id) => !$cargos->has($id))) {
            $errores['opciones'] = 'Seleccione cargos de la lista de cargos disponibles para su ficha';
        } else {
            // Perfil alterno: el Aprendiz debe confirmarlo de forma explícita (HU-044, esc. 3)
            $sinConfirmar = $elegidas->filter(fn ($id) => $cargos[$id]->alterno && empty($datos['confirmar_alterno'][$id]));
            if ($sinConfirmar->isNotEmpty()) {
                $errores['opciones'] = 'Confirme que desea postularse a: ' . $sinConfirmar->map(fn ($id) => $cargos[$id]->nombre)->implode(', ') . ' (su programa es alterno para ese cargo)';
            }
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        // Documentos obligatorios de los cargos elegidos y propuesta de trabajo por cargo (RN-010, RN-011)
        $exigidos = $this->documentosExigidos($elegidas->map(fn ($id) => $cargos[$id]));
        $faltantes = collect($exigidos)->reject(fn ($doc, $tipo) => $request->file("documentos.{$tipo}"))->map(fn ($doc) => $doc['nombre']);
        $sinPropuesta = $opciones->reject(fn ($id, $i) => $request->file("propuestas.{$i}"))->map(fn ($id) => $cargos[$id]->nombre);
        if ($faltantes->isNotEmpty() || $sinPropuesta->isNotEmpty()) {
            throw ValidationException::withMessages(array_filter([
                'documentos' => $faltantes->isNotEmpty() ? 'Faltan documentos obligatorios: ' . $faltantes->implode('; ') : null,
                'propuestas' => $sinPropuesta->isNotEmpty() ? 'Falta la propuesta de trabajo de: ' . $sinPropuesta->implode('; ') : null,
            ]));
        }

        $rutas = [];
        try {
            $postulacion = DB::transaction(function () use ($p, $datos, $opciones, $exigidos, $request, &$rutas) {
                $postulacion = Postulacion::create([
                    'convocatoria_id' => $p->convocatoria->id,
                    'person_id' => $p->user->person_id,
                    'course_id' => $p->persona->course_id,
                    'municipality_id' => $datos['municipality_id'],
                    'vereda_ruta' => $datos['vereda_ruta'] ?? null,
                    'telefono' => trim($datos['telefono']),
                    'estado' => 'postulado',
                ]);
                $carpeta = "talentohumano/postulaciones/{$postulacion->id}";

                foreach ($opciones as $preferencia => $cargoId) {
                    $rutas[] = $ruta = $this->guardarArchivo($request->file("propuestas.{$preferencia}"), $carpeta, "propuesta-{$preferencia}");
                    PostulacionOpcion::create([
                        'postulacion_id' => $postulacion->id,
                        'convocatoria_cargo_id' => $cargoId,
                        'preferencia' => $preferencia,
                        'propuesta_trabajo' => $ruta,
                    ]);
                }

                foreach (array_keys($exigidos) as $tipo) {
                    $archivo = $request->file("documentos.{$tipo}");
                    $rutas[] = $ruta = $this->guardarArchivo($archivo, $carpeta, $tipo);
                    PostulacionDocumento::create([
                        'postulacion_id' => $postulacion->id,
                        'tipo' => $tipo,
                        'ruta_archivo' => $ruta,
                        'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
                    ]);
                }

                return $postulacion;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($rutas);
            throw $e;
        }

        $cargos = $postulacion->opciones()->orderBy('preferencia')->get()->map(fn ($o) => $o->cargo_nombre)->implode(', ');
        Auditoria::registrar('postulacion_registrada', $postulacion, [], ['opciones' => $cargos]);

        return redirect()->route('talentohumano.aprendiz.postulacion')->with('success', 'Postulación registrada correctamente');
    }

    /**
     * Estado del proceso del Aprendiz: postulación, prueba, entrevistas y resultado (HU-048 a HU-050).
     */
    public function miPostulacion()
    {
        $p = $this->portal();
        $postulacion = $p->postulacion;

        return view('talentohumano::aprendiz.mi-postulacion', [
            'p' => $p,
            'aplicacion' => $postulacion ? $this->aplicacion($postulacion) : null,
            'entrevistas' => $postulacion ? DB::table('th_entrevista_aspirantes as ea')
                ->join('th_entrevistas as e', 'e.id', '=', 'ea.entrevista_id')
                ->where('ea.postulacion_id', $postulacion->id)->whereNull('e.deleted_at')
                ->orderBy('e.fecha_hora')->get(['e.tipo', 'e.modalidad', 'e.fecha_hora', 'e.lugar']) : collect(),
            'resultado' => $postulacion ? $this->resultado($p) : null,
            'estadoPostulacion' => $p->estadoPostulacion(),
        ]);
    }

    /**
     * Descarga de un documento propio de la postulación; el de otro aprendiz se rechaza en el servidor (HU-049, esc. 3).
     */
    public function documento(Request $request, int $documento)
    {
        $tipo = $request->query('tipo') === 'propuesta' ? 'propuesta' : 'documento';
        $registro = $tipo === 'propuesta'
            ? PostulacionOpcion::with('postulacion')->find($documento)
            : PostulacionDocumento::with('postulacion')->find($documento);

        if (!$registro || $registro->postulacion?->person_id !== auth()->user()->person_id) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        $ruta = $tipo === 'propuesta' ? $registro->propuesta_trabajo : $registro->ruta_archivo;
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta, $tipo === 'propuesta' ? 'propuesta-' . $registro->preferencia . '.' . pathinfo($ruta, PATHINFO_EXTENSION) : $registro->nombre_original);
    }

    /**
     * Prueba psicotécnica en FOLIUM, con guardado progresivo de las respuestas (HU-047).
     */
    public function prueba()
    {
        $p = $this->portal();
        $situacion = $this->situacionPrueba($p);

        $datos = ['p' => $p, 'situacion' => $situacion, 'etapa' => $p->etapa('prueba_psicotecnica')];
        if ($situacion === 'disponible') {
            $aplicacion = $this->abrirAplicacion($p);
            $preguntas = app(CuestionarioService::class)->preguntas($aplicacion->cuestionario_id);
            $respuestas = DB::table('th_respuestas_prueba')->where('aplicacion_id', $aplicacion->id)->pluck('respuesta', 'pregunta_id');
            $pendiente = collect($preguntas)->first(fn ($pr) => !filled($respuestas[$pr['id']] ?? null));

            $datos += [
                'aplicacion' => $aplicacion,
                'titulo' => DB::table('th_cuestionarios')->where('id', $aplicacion->cuestionario_id)->value('titulo'),
                'preguntas' => $preguntas,
                'respuestas' => $respuestas,
                'primeraPendiente' => $pendiente['id'] ?? null,
            ];
        }

        return view('talentohumano::aprendiz.prueba', $datos);
    }

    /**
     * Imagen de una pregunta de la prueba que el Aprendiz está presentando.
     */
    public function imagenPregunta(int $pregunta)
    {
        $p = $this->portal();
        abort_unless($this->situacionPrueba($p) === 'disponible', 404);

        $ruta = DB::table('th_cuestionario_preguntas')->where('cuestionario_id', $this->abrirAplicacion($p)->cuestionario_id)->where('id', $pregunta)->value('imagen');
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta);
    }

    /**
     * Guarda una respuesta mientras el Aprendiz presenta la prueba (HU-047, esc. 2).
     */
    public function guardarProgresoPrueba(Request $request)
    {
        $p = $this->portal();
        if ($this->situacionPrueba($p) !== 'disponible') {
            return response()->json(['ok' => false, 'mensaje' => 'La prueba no está disponible.'], 422);
        }

        $aplicacion = $this->abrirAplicacion($p);
        $datos = $request->validate(['pregunta_id' => ['required', 'integer'], 'respuesta' => ['nullable', 'string', 'max:5000']]);
        $pregunta = DB::table('th_cuestionario_preguntas')->where('cuestionario_id', $aplicacion->cuestionario_id)->find($datos['pregunta_id']);
        abort_unless($pregunta, 422);

        $this->guardarRespuesta($aplicacion->id, $pregunta->id, $datos['respuesta'] ?? null);

        return response()->json(['ok' => true, 'guardada' => now()->format('H:i')]);
    }

    /**
     * Envía la prueba, calcula el resultado y lo deja vinculado a la postulación sin mostrarlo (HU-047, esc. 1 y 5).
     */
    public function enviarPrueba(Request $request)
    {
        $p = $this->portal();
        $situacion = $this->situacionPrueba($p);
        if ($situacion === 'presentada') {
            return redirect()->route('talentohumano.aprendiz.prueba')->with('info', 'Ya presentó la prueba psicotécnica');
        }
        if ($situacion !== 'disponible') {
            return redirect()->route('talentohumano.aprendiz.prueba');
        }

        $aplicacion = $this->abrirAplicacion($p);
        $preguntas = collect(app(CuestionarioService::class)->preguntas($aplicacion->cuestionario_id))->keyBy('id');
        foreach ((array) $request->input('respuestas', []) as $preguntaId => $respuesta) {
            if ($preguntas->has((int) $preguntaId)) {
                $this->guardarRespuesta($aplicacion->id, (int) $preguntaId, is_scalar($respuesta) ? (string) $respuesta : null);
            }
        }

        $respuestas = DB::table('th_respuestas_prueba')->where('aplicacion_id', $aplicacion->id)->pluck('respuesta', 'pregunta_id');
        $pendiente = $preguntas->first(fn ($pr) => !filled($respuestas[$pr['id']] ?? null));
        if ($pendiente) {
            return redirect()->to(route('talentohumano.aprendiz.prueba') . '#pregunta-' . $pendiente['id'])
                ->with('error', 'Responda todas las preguntas antes de enviar');
        }

        DB::transaction(function () use ($aplicacion, $preguntas, $respuestas) {
            $total = 0;
            foreach ($preguntas as $pregunta) {
                $puntaje = $this->puntaje($pregunta, $respuestas[$pregunta['id']]);
                $total += $puntaje ?? 0;
                DB::table('th_respuestas_prueba')->where('aplicacion_id', $aplicacion->id)->where('pregunta_id', $pregunta['id'])
                    ->update(['puntaje' => $puntaje, 'updated_at' => now()]);
            }
            DB::table('th_aplicaciones_prueba')->where('id', $aplicacion->id)->update([
                'estado' => 'presentada', 'puntaje' => round($total, 2), 'fecha_presentacion' => now(), 'updated_at' => now(),
            ]);
        });
        Auditoria::registrar('prueba_presentada', $p->postulacion);

        return redirect()->route('talentohumano.aprendiz.prueba')->with('success', 'Prueba enviada correctamente');
    }

    /**
     * Acta de seleccionados de la convocatoria, solo para sus postulantes (HU-051).
     */
    public function acta()
    {
        $p = $this->portal();
        $acta = $p->postulacion ? ActaSeleccionados::publicada($p->fase->id) : null;

        return view('talentohumano::aprendiz.acta', [
            'p' => $p,
            'acta' => $acta,
            'filas' => $acta ? ActaSeleccionados::filas($p->convocatoria->id) : collect(),
            'etapa' => $p->etapa('acta_seleccionados'),
        ]);
    }

    /**
     * Empalme del Aprendiz: inventario, visita a la unidad y firma del acta de recibimiento (HU-052, HU-053, HU-057).
     */
    public function empalme()
    {
        $p = $this->portal();
        $empalme = $p->empalme;
        if (!$empalme) {
            return view('talentohumano::aprendiz.empalme', ['p' => $p, 'empalme' => null]);
        }

        // El acta se genera con la plantilla del área y el inventario se precarga con el último del cargo (RN-026, RN-061)
        if ($empalme->estado !== 'cerrado') {
            ActaRecibimiento::asegurar($empalme);
            ActaRecibimiento::precargarInventario($empalme);
        }
        $empalme->load(['inventarios', 'acta.firmas.person']);
        $plantilla = ActaRecibimiento::plantilla($empalme);

        return view('talentohumano::aprendiz.empalme', [
            'p' => $p,
            'empalme' => $empalme,
            'papel' => $p->papel(),
            'pasos' => $this->pasosEmpalme($empalme),
            'firmantes' => ActaRecibimiento::firmantes($empalme),
            'aprendices' => ActaRecibimiento::aprendicesFirmantes($empalme),
            'estados' => collect($plantilla?->columnas_inventario ?? [])->firstWhere('tipo', 'lista')['opciones'] ?? ['Bueno', 'Regular', 'Malo'],
            'yaFirme' => $empalme->acta?->firmas->contains('person_id', $p->user->person_id),
        ]);
    }

    /**
     * El saliente (o el entrante si el cargo no tiene saliente) registra el inventario real que entrega (HU-053, HU-052 esc. 4).
     */
    public function guardarInventarioEmpalme(Request $request)
    {
        [$p, $empalme] = $this->empalmeEditable();
        $papel = $p->papel();
        $sinSaliente = $empalme->participantes->where('papel', 'saliente')->isEmpty();
        abort_unless($papel === 'saliente' || ($papel === 'receptor' && $sinSaliente), 403, 'No tiene permisos para realizar esta acción');
        if ($empalme->visita_confirmada) {
            return back()->with('error', 'La visita a la unidad ya se confirmó: el inventario no se puede cambiar.');
        }

        // Ítems sin cantidad o sin estado no se guardan; los demás sí (HU-053, esc. 4)
        $validos = [];
        $invalidos = [];
        foreach ((array) $request->input('items', []) as $i => $fila) {
            $item = trim((string) ($fila['item'] ?? ''));
            if ($item === '') {
                continue;
            }
            $cantidad = filter_var($fila['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $estado = trim((string) ($fila['estado'] ?? ''));
            if ($cantidad === false || $estado === '') {
                $invalidos[] = ['item' => $item, 'cantidad' => $fila['cantidad'] ?? '', 'estado' => $estado, 'falta_cantidad' => $cantidad === false, 'falta_estado' => $estado === ''];
                continue;
            }
            $validos[] = ['item' => mb_substr($item, 0, 255), 'cantidad' => $cantidad, 'estado' => mb_substr($estado, 0, 80), 'novedad' => trim((string) ($fila['observacion'] ?? '')) ?: null];
        }

        DB::transaction(function () use ($empalme, $validos) {
            $empalme->inventarios()->delete();
            foreach ($validos as $fila) {
                InventarioEmpalme::create($fila + ['empalme_id' => $empalme->id, 'verificado' => false]);
            }

            // El ajuste también actualiza el inventario vigente del cargo, nunca la plantilla del área (RN-055)
            DB::table('th_inventario_cargo')->where('cargo_id', $empalme->cargo_id)->delete();
            foreach ($validos as $fila) {
                DB::table('th_inventario_cargo')->insert([
                    'cargo_id' => $empalme->cargo_id,
                    'custodio_cargo_id' => $empalme->cargo_receptor_id ?? $empalme->cargo_id,
                    'item' => $fila['item'], 'cantidad' => $fila['cantidad'], 'estado' => $fila['estado'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $acta = ActaRecibimiento::asegurar($empalme);
            $acta->update(['contenido' => array_merge($acta->contenido ?? [], ['inventario_registrado' => now()->toDateTimeString(), 'inventario_verificado' => null])]);
            if ($empalme->estado === 'pendiente') {
                $empalme->update(['estado' => 'en_curso']);
            }
        });
        Auditoria::registrar('inventario_registrado', $empalme, [], ['cargo' => $empalme->cargo?->nombre, 'items' => count($validos)]);

        $respuesta = back()->with('success', $p->papel() === 'receptor'
            ? 'Inventario inicial guardado. Queda pendiente de validación por el Gestor de Talento Humano.'
            : 'Inventario guardado. El aprendiz que recibe ya puede verificarlo.');

        return $invalidos
            ? $respuesta->with('itemsInvalidos', $invalidos)->with('error', 'Algunos ítems no se guardaron: indique la cantidad y el estado de cada uno.')
            : $respuesta;
    }

    /**
     * El receptor verifica cada ítem recibido o registra la novedad (HU-052, esc. 2).
     */
    public function verificarInventarioEmpalme(Request $request)
    {
        [$p, $empalme] = $this->empalmeEditable();
        abort_unless($p->papel() === 'receptor', 403, 'No tiene permisos para realizar esta acción');
        if (empty($empalme->acta?->contenido['inventario_registrado'])) {
            return back()->with('error', 'El aprendiz saliente aún no registra el inventario que entrega.');
        }

        $items = $empalme->inventarios()->get();
        $marcas = (array) $request->input('verificacion', []);
        $sinRevisar = $items->filter(fn ($item) => empty($marcas[$item->id]['coincide']) && !filled($marcas[$item->id]['novedad'] ?? null));
        if ($sinRevisar->isNotEmpty()) {
            return back()->withInput()->with('error', 'Marque cada ítem como recibido o registre la diferencia: ' . $sinRevisar->pluck('item')->implode(', '));
        }

        $novedades = [];
        DB::transaction(function () use ($items, $marcas, $empalme, &$novedades) {
            foreach ($items as $item) {
                $novedad = trim((string) ($marcas[$item->id]['novedad'] ?? '')) ?: null;
                $item->update(['verificado' => true, 'novedad' => $novedad ? mb_substr($novedad, 0, 500) : null]);
                if ($novedad) {
                    $novedades[] = "{$item->item}: {$novedad}";
                }
            }
            $acta = $empalme->acta;
            $acta->update(['contenido' => array_merge($acta->contenido ?? [], ['inventario_verificado' => now()->toDateTimeString()])]);
        });
        Auditoria::registrar('inventario_recibido', $empalme, [], ['cargo' => $empalme->cargo?->nombre, 'novedades' => count($novedades)]);

        // La novedad queda documentada y se avisa al Gestor de Talento Humano
        if ($novedades) {
            $cargo = $empalme->cargo?->nombre;
            Alerta::crear('novedad_inventario', "Novedad en el inventario del cargo «{$cargo}»", implode(' · ', $novedades), route('talentohumano.empalmes.show', $empalme->id));
            Correo::enviar($this->correosGestores(), "FOLIUM: novedad en el inventario del cargo {$cargo}",
                "{$p->persona->nombre} registró diferencias en el inventario que recibe:\n\n- " . implode("\n- ", $novedades)
                . "\n\nPuede revisar el empalme en " . route('talentohumano.empalmes.show', $empalme->id));
        }

        return back()->with('success', $novedades
            ? 'Verificación guardada. Las novedades quedaron documentadas y se avisó al Gestor de Talento Humano.'
            : 'Verificación guardada: el inventario coincide con lo que recibes.');
    }

    /**
     * Cualquiera de las partes confirma la visita a la unidad productiva con su fecha y participantes (HU-057, esc. 1 y 5).
     */
    public function confirmarVisitaEmpalme(Request $request)
    {
        [$p, $empalme] = $this->empalmeEditable();
        if (!$this->inventarioVerificado($empalme)) {
            return back()->with('error', 'Primero debe verificarse el inventario del empalme.');
        }

        $datos = $request->validate([
            'fecha_visita' => ['required', 'date', 'before_or_equal:today'],
            'participantes' => ['array'],
            'participantes.*' => ['string', 'max:120'],
            'otros' => ['nullable', 'string', 'max:500'],
        ], [
            'fecha_visita.required' => 'Este campo es obligatorio',
            'fecha_visita.before_or_equal' => 'La fecha de la visita no puede ser futura',
        ]);

        $participantes = collect($datos['participantes'] ?? [])
            ->merge(preg_split('/[,;\n]+/', (string) ($datos['otros'] ?? '')))
            ->map(fn ($nombre) => trim($nombre))->filter()->unique()->values()->all();
        if (!$participantes) {
            throw ValidationException::withMessages(['participantes' => 'Registre los participantes de la visita']);
        }

        $empalme->update(['visita_confirmada' => true, 'fecha_visita' => $datos['fecha_visita'], 'participantes_visita' => $participantes]);
        Auditoria::registrar('visita_confirmada', $empalme, [], ['cargo' => $empalme->cargo?->nombre, 'fecha' => $datos['fecha_visita'], 'participantes' => implode(', ', $participantes)]);

        return back()->with('success', 'Visita confirmada. Ya se puede firmar el acta de recibimiento.');
    }

    /**
     * Firma digital del acta de recibimiento con fecha y hora (HU-052 esc. 1 y 5, HU-053 esc. 2, HU-057).
     */
    public function firmarActaEmpalme(Request $request)
    {
        [$p, $empalme] = $this->empalmeEditable();
        $rol = ActaRecibimiento::aprendicesFirmantes($empalme)[$p->user->person_id] ?? null;
        abort_unless($rol, 403, 'No tiene permisos para realizar esta acción');

        // Prerrequisitos de la firma (RN-026)
        $error = match (true) {
            empty($empalme->acta?->contenido['inventario_registrado']) => 'No se puede firmar: el registro del inventario es prerrequisito.',
            !$this->inventarioVerificado($empalme) => 'No se puede firmar: la verificación del inventario es prerrequisito.',
            !$empalme->visita_confirmada => 'No se puede firmar: la confirmación de la visita a la unidad productiva es prerrequisito.',
            default => null,
        };
        if ($error) {
            return back()->with('error', $error);
        }

        $acta = ActaRecibimiento::asegurar($empalme);
        Firma::firstOrCreate(['acta_id' => $acta->id, 'person_id' => $p->user->person_id], ['rol_firmante' => $rol, 'fecha_firma' => now()]);
        Auditoria::registrar('acta_firmada', $acta, [], ['empalme' => $empalme->id, 'cargo' => $empalme->cargo?->nombre, 'rol_firmante' => $rol]);

        $estado = ActaRecibimiento::actualizarEstado($empalme, $acta);
        if ($estado === 'faltan_instructores') {
            // HU-057, esc. 3: el acta queda pendiente de la firma de los instructores que exige la plantilla
            Correo::enviar($this->correosInstructoresTH(), "FOLIUM: acta de recibimiento pendiente de su firma ({$empalme->cargo?->nombre})",
                "Los aprendices ya firmaron el acta de recibimiento del cargo {$empalme->cargo?->nombre}. Falta la firma del instructor.\n\n"
                . route('talentohumano.empalmes.show', $empalme->id));
        }

        return back()->with('success', match ($estado) {
            'firmada' => 'Firma registrada. El acta quedó firmada por todas las partes: ya puedes diligenciar el Formulario de Satisfacción.',
            'faltan_instructores' => 'Firma registrada. El acta queda «Pendiente de firmas» hasta que firme el instructor.',
            default => 'Firma registrada con fecha y hora. Falta la firma de las demás partes del empalme.',
        });
    }

    /**
     * Formulario de Satisfacción del Empalme, habilitado con el acta firmada (HU-054).
     */
    public function satisfaccion()
    {
        $p = $this->portal();
        $empalme = $p->empalme;

        return view('talentohumano::aprendiz.satisfaccion', [
            'p' => $p,
            'empalme' => $empalme,
            'respuesta' => $empalme ? DB::table('th_satisfaccion_empalme')->where('empalme_id', $empalme->id)->where('person_id', $p->user->person_id)->first() : null,
        ]);
    }

    public function enviarSatisfaccion(Request $request)
    {
        $p = $this->portal();
        $empalme = $p->empalme;
        abort_unless($empalme && $p->papel(), 403, 'No tiene permisos para realizar esta acción');
        if ($empalme->acta?->estado !== 'firmada') {
            return redirect()->route('talentohumano.aprendiz.satisfaccion')->with('error', 'Primero debe firmarse el acta de recibimiento.');
        }
        if (DB::table('th_satisfaccion_empalme')->where('empalme_id', $empalme->id)->where('person_id', $p->user->person_id)->exists()) {
            return redirect()->route('talentohumano.aprendiz.satisfaccion')->with('info', 'Ya diligenciaste el Formulario de Satisfacción.');
        }

        $reglas = ['comentario' => ['nullable', 'string', 'max:1000']];
        foreach (array_keys(self::PREGUNTAS_SATISFACCION) as $i) {
            $reglas["respuestas.{$i}"] = ['required', 'integer', 'between:1,5'];
        }
        $datos = $request->validate($reglas, ['required' => 'Responda esta pregunta', 'between' => 'Responda esta pregunta']);

        DB::table('th_satisfaccion_empalme')->insert([
            'empalme_id' => $empalme->id,
            'person_id' => $p->user->person_id,
            'respuestas' => json_encode([
                'papel' => $p->papel(),
                'respuestas' => collect(self::PREGUNTAS_SATISFACCION)->map(fn ($pregunta, $i) => ['pregunta' => $pregunta, 'valor' => (int) $datos['respuestas'][$i]])->all(),
                'comentario' => $datos['comentario'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Auditoria::registrar('satisfaccion_registrada', $empalme, [], ['cargo' => $empalme->cargo?->nombre]);

        return redirect()->route('talentohumano.aprendiz.satisfaccion')->with('success', 'Formulario enviado. Cuando el Gestor de Talento Humano dé el visto bueno, tu asignación pasará a vigente.');
    }

    /**
     * Cargo vigente o asignado del Aprendiz y su historial en SENA Empresa (HU-055).
     */
    public function miCargo()
    {
        $p = $this->portal();

        return view('talentohumano::aprendiz.cargo', [
            'p' => $p,
            'historial' => Asignacion::with(['cargo.area', 'fase'])->where('person_id', $p->user->person_id)->latest('id')->get(),
        ]);
    }

    // ---------------------------------------------------------------------------------------------------------

    private function portal(): PortalAprendiz
    {
        return new PortalAprendiz(auth()->user());
    }

    /**
     * Texto y enlace del siguiente paso del Aprendiz para el inicio.
     */
    private function siguientePaso(PortalAprendiz $p): ?array
    {
        if ($p->empalme && $p->empalme->estado !== 'cerrado') {
            return ['Completa tu empalme', 'Verifica el inventario, confirma la visita a la unidad y firma el acta de recibimiento.', 'talentohumano.aprendiz.empalme', 'fa-handshake'];
        }
        if ($p->asignacion) {
            return null;
        }
        if (!$p->fichaHabilitada) {
            return null;
        }
        if (!$p->postulacion) {
            [$apertura, $cierre] = $p->fechasPostulacion();

            return match ($p->estadoPostulacion()) {
                'abierta' => ['Postúlate' . ($cierre ? ' antes del ' . $cierre->format('d/m/Y') : ''), 'Elige 3 cargos en orden de preferencia y carga tu hoja de vida y tus propuestas de trabajo.', 'talentohumano.aprendiz.postularme', 'fa-file-signature'],
                'por_abrir' => ['La postulación abre el ' . $apertura?->format('d/m/Y'), 'Mientras tanto, revisa los cargos disponibles para tu ficha.', 'talentohumano.aprendiz.convocatoria', 'fa-bullhorn'],
                default => $p->cronograma ? null : ['Pendiente el cronograma', 'El cronograma del proceso de selección aún no ha sido publicado.', 'talentohumano.aprendiz.cronograma', 'fa-calendar-days'],
            };
        }
        if ($this->situacionPrueba($p) === 'disponible') {
            return ['Presenta la prueba psicotécnica', 'Es obligatoria para ser citado a entrevista. Tus respuestas se guardan mientras avanzas.', 'talentohumano.aprendiz.prueba', 'fa-brain'];
        }

        return ['Sigue tu proceso', 'Consulta tus entrevistas programadas y el resultado de tu postulación.', 'talentohumano.aprendiz.postulacion', 'fa-id-card'];
    }

    /**
     * Situación de la prueba del Aprendiz: sin_postulacion, sin_prueba, por_abrir, disponible, presentada o vencida.
     */
    private function situacionPrueba(PortalAprendiz $p): string
    {
        if (!$p->postulacion) {
            return 'sin_postulacion';
        }

        $aplicacion = $this->aplicacion($p->postulacion);
        if ($aplicacion?->estado === 'presentada') {
            return 'presentada';
        }
        if ($aplicacion?->estado === 'no_evaluado' || $p->postulacion->estado === 'no_evaluado') {
            return 'vencida';
        }

        $etapa = $p->etapa('prueba_psicotecnica');
        if (!$etapa?->inicio || today()->lt($etapa->inicio)) {
            return 'por_abrir';
        }
        if (today()->gt($etapa->fin ?? $etapa->inicio)) {
            $this->marcarNoEvaluado($p, $aplicacion);

            return 'vencida';
        }

        return $aplicacion || $this->cuestionarioVigente() ? 'disponible' : 'sin_prueba';
    }

    private function aplicacion(Postulacion $postulacion): ?object
    {
        return DB::table('th_aplicaciones_prueba')->where('postulacion_id', $postulacion->id)->latest('id')->first();
    }

    private function cuestionarioVigente(): ?int
    {
        return DB::table('th_cuestionarios')->where('tipo', 'prueba_psicotecnica')->where('estado', 'vigente')
            ->whereNull('deleted_at')->latest('id')->value('id');
    }

    /**
     * Aplicación de la prueba del Aprendiz; se crea al abrirla por primera vez dentro del plazo del cronograma.
     */
    private function abrirAplicacion(PortalAprendiz $p): object
    {
        $aplicacion = $this->aplicacion($p->postulacion);
        if ($aplicacion) {
            return $aplicacion;
        }

        $etapa = $p->etapa('prueba_psicotecnica');
        $id = DB::table('th_aplicaciones_prueba')->insertGetId([
            'postulacion_id' => $p->postulacion->id,
            'cuestionario_id' => $this->cuestionarioVigente(),
            'estado' => 'en_curso',
            'fecha_limite' => ($etapa->fin ?? $etapa->inicio)->copy()->endOfDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('th_aplicaciones_prueba')->find($id);
    }

    private function guardarRespuesta(int $aplicacionId, int $preguntaId, ?string $respuesta): void
    {
        DB::table('th_respuestas_prueba')->updateOrInsert(
            ['aplicacion_id' => $aplicacionId, 'pregunta_id' => $preguntaId],
            ['respuesta' => $respuesta !== null ? mb_substr(trim($respuesta), 0, 5000) : null, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Puntaje de una respuesta según la configuración de la pregunta. Las abiertas las califica el psicólogo (null).
     */
    private function puntaje(array $pregunta, ?string $respuesta): ?float
    {
        return match ($pregunta['tipo']) {
            'seleccion' => (float) ($pregunta['opciones'][(int) $respuesta]['puntaje'] ?? 0),
            'escala' => (function () use ($pregunta, $respuesta) {
                $min = (int) ($pregunta['opciones']['min'] ?? 1);
                $max = (int) ($pregunta['opciones']['max'] ?? 5);
                $valor = max($min, min($max, (int) $respuesta));

                return $max > $min ? round($pregunta['puntaje_maximo'] * ($valor - $min) / ($max - $min), 2) : 0.0;
            })(),
            default => null,
        };
    }

    /**
     * Venció el plazo sin enviar la prueba: el Aprendiz queda "no evaluado" y se avisa al Gestor (HU-047 esc. 3, RN-013).
     */
    private function marcarNoEvaluado(PortalAprendiz $p, ?object $aplicacion): void
    {
        $postulacion = $p->postulacion;
        if ($postulacion->estado !== 'postulado') {
            return;
        }

        DB::transaction(function () use ($postulacion, $aplicacion) {
            $postulacion->update(['estado' => 'no_evaluado']);
            if ($aplicacion) {
                DB::table('th_aplicaciones_prueba')->where('id', $aplicacion->id)->update(['estado' => 'no_evaluado', 'updated_at' => now()]);
            }
        });
        Auditoria::registrar('prueba_no_evaluado', $postulacion, ['estado' => 'postulado'], ['estado' => 'no_evaluado']);

        $nombre = $p->persona->nombre;
        Alerta::crear('prueba_no_presentada', "{$nombre} no presentó la prueba psicotécnica", 'Quedó como no evaluado y su entrevista queda bloqueada (RN-013).');
        Correo::enviar($this->correosGestores(), "FOLIUM: {$nombre} no presentó la prueba psicotécnica",
            "{$nombre} (ficha {$p->persona->ficha}) no presentó la prueba psicotécnica dentro del plazo. Quedó como no evaluado y no se le puede programar entrevista.");
    }

    /**
     * Resultado del Aprendiz, visible solo con el acta de seleccionados publicada (HU-049 esc. 2, HU-050).
     */
    private function resultado(PortalAprendiz $p): ?object
    {
        if (!ActaSeleccionados::publicada($p->fase->id)) {
            return null;
        }

        $postulacion = $p->postulacion;
        $seleccion = DB::table('th_selecciones as s')
            ->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->where('s.postulacion_id', $postulacion->id)->whereIn('s.estado', ['validada', 'ajustada'])
            ->select('c.nombre', 'cc.id as convocatoria_cargo_id')->first();
        if ($seleccion) {
            $preferencia = $postulacion->opciones->firstWhere('convocatoria_cargo_id', $seleccion->convocatoria_cargo_id)?->preferencia;

            return (object) ['tipo' => 'seleccionado', 'cargo' => $seleccion->nombre, 'preferencia' => $preferencia];
        }

        $reubicacion = DB::table('th_reubicaciones as r')
            ->leftJoin('th_cargos_activados as ca', 'ca.id', '=', 'r.cargo_activado_id')
            ->leftJoin('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->where('r.postulacion_id', $postulacion->id)->select('c.nombre')->first();
        if ($reubicacion) {
            return (object) ['tipo' => $reubicacion->nombre ? 'reubicado' : 'sin_cargo', 'cargo' => $reubicacion->nombre];
        }

        return (object) ['tipo' => 'no_seleccionado', 'cargo' => null];
    }

    /**
     * Documentos que exigen los cargos (la hoja de vida siempre), con los cargos que exigen cada uno.
     */
    private function documentosExigidos($cargos): array
    {
        $exigidos = ['hoja_vida' => ['nombre' => self::DOCUMENTOS['hoja_vida'], 'cargos' => []]];
        foreach ($cargos as $cargo) {
            foreach ($cargo->documentos as $doc) {
                $tipo = $doc['tipo'] ?? null;
                if (!isset(self::DOCUMENTOS[$tipo])) {
                    continue;
                }
                $exigidos[$tipo] ??= ['nombre' => $tipo === 'otro' ? ($doc['descripcion'] ?: self::DOCUMENTOS['otro']) : self::DOCUMENTOS[$tipo], 'cargos' => []];
                $exigidos[$tipo]['cargos'][] = $cargo->nombre;
            }
        }

        return $exigidos;
    }

    private function guardarArchivo(UploadedFile $archivo, string $carpeta, string $nombre): string
    {
        return $archivo->storeAs($carpeta, $nombre . '.' . strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension()), 'local');
    }

    /**
     * Empalme del Aprendiz que aún se puede trabajar (no cerrado), con sus relaciones.
     *
     * @return array{0: PortalAprendiz, 1: Empalme}
     */
    private function empalmeEditable(): array
    {
        $p = $this->portal();
        $empalme = $p->empalme;
        abort_unless($empalme && $p->papel(), 403, 'No tiene permisos para realizar esta acción');
        abort_if($empalme->estado === 'cerrado', 403, 'El empalme ya está cerrado');

        return [$p, $empalme];
    }

    private function inventarioVerificado(Empalme $empalme): bool
    {
        $items = $empalme->inventarios()->get();

        return $items->isNotEmpty() && $items->every(fn ($item) => $item->verificado);
    }

    /**
     * Pasos del empalme con su estado, para la línea de avance del Aprendiz.
     */
    private function pasosEmpalme(Empalme $empalme): array
    {
        $contenido = $empalme->acta?->contenido ?? [];

        return [
            ['Inventario registrado', !empty($contenido['inventario_registrado'])],
            ['Inventario verificado', $this->inventarioVerificado($empalme)],
            ['Visita a la unidad', (bool) $empalme->visita_confirmada],
            ['Acta firmada', $empalme->acta?->estado === 'firmada'],
            ['Formulario de satisfacción', ActaRecibimiento::satisfaccionCompleta($empalme)],
            ['Visto bueno', $empalme->estado === 'cerrado'],
        ];
    }

    private function correosGestores(): array
    {
        return DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', 'talentohumano.gestor')->whereNull('users.deleted_at')
            ->pluck('users.email')->filter()->unique()->values()->all();
    }

    private function correosInstructoresTH(): array
    {
        return DB::table('th_designaciones')->where('tipo', 'instructor_th')->whereNull('fecha_retiro')->whereNull('deleted_at')
            ->pluck('person_id')->map(fn ($id) => Correo::de($id))->filter()->unique()->values()->all();
    }
}
