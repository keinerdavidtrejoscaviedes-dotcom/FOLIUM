<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\ThFase;
use Modules\TalentoHumano\Entities\ThCargo;
use Modules\TalentoHumano\Entities\ThCronograma;
use Modules\TalentoHumano\Entities\ThConvocatoria;
use Modules\TalentoHumano\Entities\ThPostulacion;
use Modules\TalentoHumano\Entities\ThEmpalme;
use Modules\TalentoHumano\Entities\ThAsignacion;

class AprendizController extends Controller
{
    /**
     * Datos simulados por defecto del aprendiz autenticado (Santiago Rojas Polanía)
     */
    private function getDatosAprendiz()
    {
        $user = auth()->user();
        return [
            'nombre' => $user->person->first_name ?? ($user->name ?? 'Santiago Rojas Polanía'),
            'documento' => 'CC 1.075.330.912',
            'correo' => $user->email ?? 'srojas@soy.sena.edu.co',
            'telefono' => '3124567890',
            'programa' => 'Gestión de la Producción Agrícola',
            'ficha' => '2902187',
            'municipio' => 'Campoalegre',
            'vereda' => 'La Vega',
            'fase' => 'Fase XXXVI',
        ];
    }

    /**
     * APR-01: Inicio / Panel del Aprendiz (HU-043, HU-044, HU-055, HU-056)
     */
    public function index(Request $request)
    {
        $estado = $request->get('estado', 'postulante');
        $estados = [
            'postulante' => 'Convocatoria abierta',
            'seleccionado' => 'Seleccionado (empalme)',
            'sin-ficha' => 'Ficha no habilitada',
        ];

        $aprendiz = $this->getDatosAprendiz();

        return view('talentohumano::aprendiz.panel', compact('estado', 'estados', 'aprendiz'));
    }

    /**
     * APR-02: Cronograma del Proceso de Selección (HU-043)
     */
    public function cronograma(Request $request)
    {
        $estado = $request->get('estado', 'publicado');
        $estados = [
            'publicado' => 'Publicado',
            'no-publicado' => 'Aún no publicado',
        ];

        $etapas = [
            ['titulo' => 'Apertura y cierre de la convocatoria', 'fechas' => '01/09 — 12/09/2026', 'estado' => 'hecho'],
            ['titulo' => 'Entrega de hoja de vida y propuestas de trabajo', 'fechas' => '01/09 — 12/09/2026', 'estado' => 'hecho'],
            ['titulo' => 'Prueba psicotécnica', 'fechas' => '22/09 — 29/09/2026', 'estado' => 'hecho'],
            ['titulo' => 'Entrevistas técnica y psicológica', 'fechas' => '01/10 — 10/10/2026', 'estado' => 'hecho'],
            ['titulo' => 'Validación de la selección', 'fechas' => '13/10 — 24/10/2026', 'estado' => 'actual'],
            ['titulo' => 'Publicación del acta de seleccionados', 'fechas' => '28/10/2026', 'estado' => 'pendiente'],
            ['titulo' => 'Empalme de cargos', 'fechas' => '11/01 — 22/01/2027', 'estado' => 'pendiente'],
        ];

        return view('talentohumano::aprendiz.cronograma', compact('estado', 'estados', 'etapas'));
    }

    /**
     * APR-03: Convocatoria y Cargos Disponibles para su Ficha (HU-044)
     */
    public function convocatoria(Request $request)
    {
        $estado = $request->get('estado', 'abierta');
        $estados = [
            'abierta' => 'Convocatoria abierta',
            'por-abrir' => 'Aún no abre',
            'cerrada' => 'Convocatoria cerrada',
        ];

        $cargos = [
            [
                'id' => 1,
                'nombre' => 'Líder de Unidad de Formación Productiva Cacao',
                'nivel' => 'Líder',
                'area' => 'Agrícola',
                'cupos' => 1,
                'proposito' => 'Coordinar las labores agronómicas de la unidad de cacao, sus registros técnicos y el equipo de trabajo.',
                'documentos' => ['Hoja de vida con soportes', 'Certificados académicos', 'Propuesta de trabajo'],
                'es_alterno' => false,
            ],
            [
                'id' => 2,
                'nombre' => 'Gestor Agrícola',
                'nivel' => 'Gestor',
                'area' => 'Agrícola',
                'cupos' => 1,
                'proposito' => 'Gestionar la producción general de las unidades agrícolas, reportar indicadores y liderar las Buenas Prácticas.',
                'documentos' => ['Hoja de vida con soportes', 'Certificados académicos', 'Propuesta de trabajo'],
                'es_alterno' => false,
            ],
            [
                'id' => 3,
                'nombre' => 'Líder de herramientas e insumos agrícolas',
                'nivel' => 'Líder',
                'area' => 'Agrícola',
                'cupos' => 1,
                'proposito' => 'Controlar el inventario, préstamo, mantenimiento y desinfección de herramientas de campo.',
                'documentos' => ['Hoja de vida con soportes', 'Propuesta de trabajo'],
                'es_alterno' => false,
            ],
            [
                'id' => 4,
                'nombre' => 'Gestor de Talento Humano',
                'nivel' => 'Gestor',
                'area' => 'Administrativa',
                'cupos' => 2,
                'proposito' => 'Gestionar la selección, coordinación, turnos y bienestar del personal de SENA Empresa.',
                'documentos' => ['Hoja de vida con soportes', 'Certificados', 'Propuesta de trabajo', 'Soporte del curso virtual de talento humano'],
                'es_alterno' => true,
                'nota' => 'Tu programa es alterno para este cargo administrativo: puedes postularte previa confirmación.',
            ],
            [
                'id' => 5,
                'nombre' => 'Líder de Sistema de Riego',
                'nivel' => 'Líder',
                'area' => 'Agrícola',
                'cupos' => 1,
                'proposito' => 'Supervisar las motobombas, tuberías y distribución de agua en los lotes agrícolas del centro.',
                'documentos' => ['Hoja de vida con soportes', 'Propuesta de trabajo'],
                'es_alterno' => false,
            ],
            [
                'id' => 6,
                'nombre' => 'Equipo Ambiental',
                'nivel' => 'Equipo',
                'area' => 'Ambiental',
                'cupos' => 3,
                'proposito' => 'Apoyar el manejo integral de residuos, compostaje y senderos ecológicos.',
                'documentos' => ['Hoja de vida con soportes', 'Propuesta de trabajo'],
                'es_alterno' => false,
            ],
        ];

        return view('talentohumano::aprendiz.convocatoria', compact('estado', 'estados', 'cargos'));
    }

    /**
     * APR-04: Postulación a la Convocatoria (HU-045, HU-046, HU-056)
     */
    public function postularme(Request $request)
    {
        $estado = $request->get('estado', 'diligenciar');
        $estados = [
            'diligenciar' => 'Formulario activo',
            'errores' => 'Con errores de validación',
            'cerrada' => 'Convocatoria cerrada',
            'incompatible' => 'Alerta cargo alterno',
        ];

        $aprendiz = $this->getDatosAprendiz();

        $cargosDisponibles = [
            'Líder de Unidad de Formación Productiva Cacao',
            'Gestor Agrícola',
            'Líder de herramientas e insumos agrícolas',
            'Gestor de Talento Humano',
            'Líder de Sistema de Riego',
            'Equipo Ambiental',
        ];

        $municipios = ['Campoalegre', 'Neiva', 'Rivera', 'Hobo', 'Algeciras', 'Palermo', 'Gigante'];

        return view('talentohumano::aprendiz.postularme', compact('estado', 'estados', 'aprendiz', 'cargosDisponibles', 'municipios'));
    }

    /**
     * Procesar la Postulación (HU-045, HU-046)
     */
    public function guardarPostulacion(Request $request)
    {
        $validated = $request->validate([
            'telefono' => 'required|string|max:20',
            'municipio' => 'required|string',
            'opcion_1' => 'required|string',
            'opcion_2' => 'required|string|different:opcion_1',
            'opcion_3' => 'required|string|different:opcion_1|different:opcion_2',
            'archivo_hv' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'archivo_certificados' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'archivo_propuesta_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'archivo_propuesta_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'archivo_propuesta_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'opcion_2.different' => 'No puede seleccionar el mismo cargo en más de una opción.',
            'opcion_3.different' => 'No puede seleccionar el mismo cargo en más de una opción.',
            'max' => 'Solo se permiten archivos PDF o imagen de hasta 5 MB.',
            'mimes' => 'Solo se permiten archivos en formato PDF o imagen.',
        ]);

        return redirect()->route('talentohumano.aprendiz.postulacion', ['estado' => 'postulado'])
            ->with('success', '¡Postulación registrada correctamente! Recibirás notificaciones del avance de tu proceso.');
    }

    /**
     * APR-05: Mi Postulación y Seguimiento (HU-048, HU-049, HU-050)
     */
    public function miPostulacion(Request $request)
    {
        $estado = $request->get('estado', 'postulado');
        $estados = [
            'postulado' => 'Postulado',
            'entrevistas' => 'Con entrevistas programadas',
            'seleccionado' => 'Seleccionado en cargo',
            'reubicado' => 'Reubicado por comité',
            'sin-cargo' => 'Sin cargo asignado',
            'sin-postulacion' => 'Sin postulación',
        ];

        $aprendiz = $this->getDatosAprendiz();

        $opciones = [
            ['preferencia' => 1, 'cargo' => 'Líder de Unidad de Formación Productiva Cacao', 'area' => 'Agrícola'],
            ['preferencia' => 2, 'cargo' => 'Gestor Agrícola', 'area' => 'Agrícola'],
            ['preferencia' => 3, 'cargo' => 'Líder de herramientas e insumos agrícolas', 'area' => 'Agrícola'],
        ];

        $entrevistas = [
            [
                'tipo' => 'Técnica — individual',
                'fecha' => 'Jueves 2 de octubre de 2026',
                'hora' => '10:00 a. m.',
                'lugar' => 'Sala de Talento Humano',
                'icono' => 'fa-screwdriver-wrench',
                'color' => 'text-[#064C3D]',
            ],
            [
                'tipo' => 'Psicológica — grupal',
                'fecha' => 'Viernes 3 de octubre de 2026',
                'hora' => '8:00 a. m.',
                'lugar' => 'Oficina de Bienestar al Aprendiz',
                'icono' => 'fa-brain',
                'color' => 'text-sky-700',
            ],
        ];

        return view('talentohumano::aprendiz.mi-postulacion', compact('estado', 'estados', 'aprendiz', 'opciones', 'entrevistas'));
    }

    /**
     * APR-06: Prueba Psicotécnica (HU-047)
     */
    public function prueba(Request $request)
    {
        $estado = $request->get('estado', 'curso');
        $estados = [
            'intro' => 'Instrucciones iniciales',
            'curso' => 'En curso (pregunta 9)',
            'pendientes' => 'Con preguntas pendientes',
            'enviada' => 'Enviada exitosamente',
            'ya-presentada' => 'Ya presentada antes',
            'vencida' => 'Plazo vencido',
            'no-postulado' => 'No postulado',
        ];

        $preguntas = [
            [
                'id' => 9,
                'categoria' => 'Trabajo en equipo y liderazgo',
                'enunciado' => 'Cuando un compañero de equipo no cumple su parte del trabajo, hablo con él directamente para buscar una solución conjunta.',
                'tipo' => 'escala',
            ],
            [
                'id' => 14,
                'categoria' => 'Manejo de la presión',
                'enunciado' => 'Faltan dos horas para entregar el informe de producción de la unidad y se daña el equipo. ¿Qué haces primero?',
                'tipo' => 'multiple',
                'opciones' => [
                    'Aviso a mi jefe inmediato y propongo una alternativa viable',
                    'Espero a que soporte técnico arregle el computador',
                    'Elaboro el reporte a mano y lo transcribo después',
                ],
            ],
        ];

        return view('talentohumano::aprendiz.prueba', compact('estado', 'estados', 'preguntas'));
    }

    /**
     * Guardar progreso en tiempo real de la prueba (AJAX)
     */
    public function guardarProgresoPrueba(Request $request)
    {
        return response()->json([
            'ok' => true,
            'mensaje' => 'Respuesta guardada progresivamente en el servidor.',
            'timestamp' => now()->toTimeString(),
        ]);
    }

    /**
     * Enviar prueba psicotécnica completa (HU-047)
     */
    public function enviarPrueba(Request $request)
    {
        return redirect()->route('talentohumano.aprendiz.prueba', ['estado' => 'enviada'])
            ->with('success', 'Prueba enviada correctamente. Ya puedes ser citado a entrevista.');
    }

    /**
     * APR-07: Acta de Seleccionados (HU-051)
     */
    public function acta(Request $request)
    {
        $estado = $request->get('estado', 'publicada');
        $estados = [
            'publicada' => 'Acta publicada',
            'no-publicada' => 'Aún no publicada',
            'no-postulante' => 'Acceso no postulante',
        ];

        $seleccionados = [
            ['cargo' => 'Gerente Administrativo', 'aprendiz' => 'Camilo Andrés Vargas', 'reubicado' => false],
            ['cargo' => 'Gestor de Talento Humano', 'aprendiz' => 'Paula Díaz Rivera — Sergio Medina Ortiz', 'reubicado' => false],
            ['cargo' => 'Líder de Unidad de Formación Productiva Cacao', 'aprendiz' => 'Santiago Rojas Polanía (tú)', 'es_tu_cargo' => true, 'reubicado' => false],
            ['cargo' => 'Líder de Sistema de Riego', 'aprendiz' => 'Óscar David Lara', 'reubicado' => true],
            ['cargo' => 'Gestor ASIG', 'aprendiz' => 'Valentina Castro Morales', 'reubicado' => false],
            ['cargo' => 'Líder Subsistema Ambiental', 'aprendiz' => 'Andrés Felipe Perdomo', 'reubicado' => false],
            ['cargo' => 'Equipo Ambiental', 'aprendiz' => 'Laura Gómez, Diego Fierro, Cristian Tovar', 'reubicado' => false],
        ];

        return view('talentohumano::aprendiz.acta', compact('estado', 'estados', 'seleccionados'));
    }

    /**
     * APR-08: Mi Empalme, Inventario y Firma Digital (HU-052, HU-053, HU-057)
     */
    public function empalme(Request $request)
    {
        $estado = $request->get('estado', 'entrante');
        $estados = [
            'entrante' => 'Entrante: verificar inventario',
            'saliente' => 'Saliente: registrar inventario',
            'saliente-error' => 'Error: ítem incompleto',
            'visita' => 'Confirmar visita a la unidad',
            'firmar' => 'Firma digital del acta',
            'instructor' => 'Esperando firma del instructor',
            'grupal' => 'Empalme grupal',
        ];

        $inventario = [
            ['item' => 'Machete Collins 22"', 'cantidad' => 4, 'estado' => 'Bueno', 'novedad' => null],
            ['item' => 'Tijeras de podar Felco', 'cantidad' => 4, 'estado' => 'Bueno', 'novedad' => null],
            ['item' => 'Bomba de espalda 20 L', 'cantidad' => 2, 'estado' => 'Regular', 'novedad' => 'Una lanza presenta fuga leve en la boquilla'],
            ['item' => 'Botas de caucho (pares)', 'cantidad' => 4, 'estado' => 'Bueno', 'novedad' => null],
            ['item' => 'Carretilla de obra', 'cantidad' => 1, 'estado' => 'Bueno', 'novedad' => null],
        ];

        $participantes = [
            ['nombre' => 'María José Ospina', 'rol' => 'Aprendiz saliente', 'marcado' => true],
            ['nombre' => 'Santiago Rojas Polanía', 'rol' => 'Aprendiz entrante', 'marcado' => true],
            ['nombre' => 'Jorge Iván Cuéllar', 'rol' => 'Instructor del área agrícola', 'marcado' => true],
            ['nombre' => 'Laura Valentina Cuéllar', 'rol' => 'Gestor de Talento Humano', 'marcado' => false],
        ];

        $firmas = [
            ['nombre' => 'María José Ospina', 'rol' => 'Aprendiz saliente', 'firmado' => true, 'fecha' => '14/01/2027 09:30 a. m.'],
            ['nombre' => 'Santiago Rojas Polanía', 'rol' => 'Aprendiz entrante (tú)', 'firmado' => false, 'fecha' => null],
            ['nombre' => 'Jorge Iván Cuéllar', 'rol' => 'Instructor de área', 'firmado' => false, 'fecha' => null],
        ];

        return view('talentohumano::aprendiz.empalme', compact('estado', 'estados', 'inventario', 'participantes', 'firmas'));
    }

    /**
     * Guardar inventario del empalme (HU-052, HU-053)
     */
    public function guardarInventarioEmpalme(Request $request)
    {
        return redirect()->route('talentohumano.aprendiz.empalme', ['estado' => 'visita'])
            ->with('success', 'Inventario verificado y guardado correctamente. Procede a confirmar la visita a la unidad productiva.');
    }

    /**
     * Confirmar visita a la unidad productiva (HU-057)
     */
    public function confirmarVisitaEmpalme(Request $request)
    {
        $request->validate([
            'fecha_visita' => 'required|date',
            'participantes' => 'required|array|min:2',
        ], [
            'participantes.required' => 'Registre los participantes de la visita.',
            'participantes.min' => 'Debe registrar al menos 2 participantes para la visita.',
        ]);

        return redirect()->route('talentohumano.aprendiz.empalme', ['estado' => 'firmar'])
            ->with('success', 'Visita confirmada exitosamente. Ya pueden realizar la firma digital del acta.');
    }

    /**
     * Firmar acta de empalme digitalmente (HU-052, HU-057)
     */
    public function firmarActaEmpalme(Request $request)
    {
        return redirect()->route('talentohumano.aprendiz.empalme', ['estado' => 'instructor'])
            ->with('success', 'Firma registrada con fecha y hora. Pendiente la firma del instructor de área.');
    }

    /**
     * APR-09: Formulario de Satisfacción del Empalme (HU-054)
     */
    public function satisfaccion(Request $request)
    {
        $estado = $request->get('estado', 'formulario');
        $estados = [
            'formulario' => 'Formulario habilitado',
            'faltantes' => 'Preguntas incompletas',
            'enviado' => 'Formulario enviado',
            'bloqueado' => 'Bloqueado (acta sin firmas)',
        ];

        $preguntas = [
            'La información que recibí sobre el cargo fue clara y actualizada.',
            'El inventario entregado coincide con lo que encontré en la unidad.',
            'Conocí las fechas de presentación de evidencias e informes del cargo.',
            'El tiempo dedicado al empalme fue suficiente.',
            'El Gestor de Talento Humano acompañó y orientó el proceso adecuadamente.',
        ];

        return view('talentohumano::aprendiz.satisfaccion', compact('estado', 'estados', 'preguntas'));
    }

    /**
     * Enviar Formulario de Satisfacción (HU-054)
     */
    public function enviarSatisfaccion(Request $request)
    {
        return redirect()->route('talentohumano.aprendiz.satisfaccion', ['estado' => 'enviado'])
            ->with('success', '¡Gracias! Formulario de satisfacción enviado correctamente. Una vez el Gestor de TH dé el visto bueno, tu asignación pasará a vigente.');
    }

    /**
     * APR-10: Mi Cargo e Historial (HU-055, HU-060)
     */
    public function miCargo(Request $request)
    {
        $estado = $request->get('estado', 'vigente');
        $estados = [
            'asignada' => 'Asignada (empalme pendiente)',
            'vigente' => 'Vigente (vinculado)',
            'finalizada' => 'Fase cerrada (historial)',
        ];

        $historial = [
            [
                'cargo' => 'Líder de Unidad de Formación Productiva Cacao',
                'fase' => 'Fase XXXVI',
                'fechas' => '15/01/2027 — 18/06/2027',
                'estado' => $estado === 'finalizada' ? 'Finalizada' : ($estado === 'vigente' ? 'Vigente' : 'Asignada'),
                'detalle' => 'Seleccionado en tu 1.ª opción · Empalme con María José Ospina completado.',
            ],
            [
                'cargo' => 'Turnos rutinarios en Unidades Agropecuarias',
                'fase' => 'Fase XXXV',
                'fechas' => 'Julio — Noviembre 2026',
                'estado' => 'Finalizada',
                'detalle' => 'Sin cargo asignado en FOLIUM · Cumplimiento de turnos prácticos.',
            ],
        ];

        return view('talentohumano::aprendiz.cargo', compact('estado', 'estados', 'historial'));
    }
}
