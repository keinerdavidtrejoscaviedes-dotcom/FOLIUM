<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Support\CondicionesMinimas;

/**
 * Submódulo "Instructor Líder de TH" (HU-037): tablero de la fase, aprobaciones, supervisión y gestión.
 */
class InstructorLiderController extends Controller
{
    /**
     * Secciones del submódulo. Las que tienen 'ruta' ya están construidas; las demás muestran "En construcción".
     */
    public const SECCIONES = [
        'aprobaciones' => [
            'titulo' => 'Aprobaciones pendientes', 'icono' => 'fa-clipboard-check', 'hu' => 'HU-034, HU-035, HU-036', 'ruta' => 'talentohumano.lider.aprobaciones.index',
            'texto' => 'Lo que espera tu decisión como Instructor Líder.',
            'items' => ['Aprobar o rechazar el cronograma del proceso de selección.', 'Validar o ajustar la propuesta de selección de cada cargo.', 'Registrar la reubicación de los aprendices no seleccionados.'],
        ],
        'supervision' => [
            'titulo' => 'Supervisión', 'icono' => 'fa-eye', 'hu' => 'HU-014, HU-037',
            'texto' => 'Consulta del proceso en modo de solo lectura, sin intervenir en su contenido.',
            'items' => ['Convocatoria y cronograma de la fase.', 'Acta de seleccionados.', 'Seguimiento de empalmes y actas de recibimiento.', 'Historial de fases anteriores.'],
        ],
        'asignaciones' => [
            'titulo' => 'Asignaciones', 'icono' => 'fa-id-card-clip', 'hu' => 'HU-012, HU-013, HU-016', 'ruta' => 'talentohumano.lider.asignaciones.index',
            'texto' => 'Quién ocupa cada cargo, con su estado y su origen.',
            'items' => ['Consultar las asignaciones de cada fase y su historial.', 'Suspender y reactivar asignaciones vigentes con justificación.', 'Cargar las asignaciones iniciales de quienes ya ocupan cargos.'],
        ],
        'catalogo' => [
            'titulo' => 'Catálogo de cargos', 'icono' => 'fa-sitemap', 'hu' => 'HU-003 a HU-006', 'ruta' => 'talentohumano.lider.catalogo.index',
            'texto' => 'Los cargos del Manual de Roles y Responsabilidades (GTH-M-001).',
            'items' => ['Registrar y crear cargos desde el nivel de Gerente hacia abajo.', 'Definir los tecnólogos compatibles y las restricciones por ficha.', 'Editar, inactivar, reactivar o eliminar cargos sin perder el historial.'],
        ],
        'designaciones' => [
            'titulo' => 'Designaciones', 'icono' => 'fa-user-tag', 'hu' => 'HU-009, HU-010', 'ruta' => 'talentohumano.lider.designaciones.index',
            'texto' => 'Las funciones de los instructores en el proceso.',
            'items' => ['Designar al Líder de la estrategia, a los Instructores de Talento Humano y a los instructores técnicos.', 'Retirar o reasignar designaciones conservando el historial.'],
        ],
        'plantillas' => [
            'titulo' => 'Plantillas de acta', 'icono' => 'fa-file-signature', 'hu' => 'HU-007, HU-008', 'ruta' => 'talentohumano.lider.plantillas.index',
            'texto' => 'Las actas de recibimiento del cargo por área.',
            'items' => ['Crear la plantilla de cada área con su inventario y sus firmantes.', 'Modificar plantillas usadas creando una nueva versión.', 'Consultar las versiones anteriores.'],
        ],
        'alertas' => [
            'titulo' => 'Alertas y servicios', 'icono' => 'fa-bell', 'hu' => 'HU-011, HU-015', 'ruta' => 'talentohumano.lider.alertas.index', 'solo_admin' => true,
            'texto' => 'Condiciones mínimas del proceso y estado de los servicios externos.',
            'items' => ['Alertas cuando falta un Líder de la estrategia o un psicólogo activo.', 'Avisos cuando fallan los servicios externos (SICEFA, correo).', 'Historial de alertas de la fase.'],
        ],
        'auditoria' => [
            'titulo' => 'Auditoría', 'icono' => 'fa-clock-rotate-left', 'hu' => 'HU-002', 'ruta' => 'talentohumano.lider.auditoria.index',
            'texto' => 'Registro inmodificable de las acciones de FOLIUM con el origen del permiso.',
            'items' => ['Consultar quién hizo qué, cuándo y con qué rol o designación.', 'Ver el valor anterior y el nuevo de cada cambio.', 'Filtrar por usuario, acción y fecha.'],
        ],
    ];

    /**
     * Claves de las secciones que todavía no están construidas.
     */
    public static function enConstruccion(): array
    {
        return array_keys(array_filter(self::SECCIONES, fn ($seccion) => !isset($seccion['ruta'])));
    }

    /**
     * Dirección de una sección del submódulo.
     */
    public static function url(string $clave): string
    {
        return isset(self::SECCIONES[$clave]['ruta'])
            ? route(self::SECCIONES[$clave]['ruta'])
            : route('talentohumano.lider.seccion', $clave);
    }

    /**
     * Tablero de la fase (HU-037, esc. 1).
     */
    public function tablero()
    {
        $fase = DB::table('th_fases')->whereNull('deleted_at')->where('estado', '!=', 'cerrada')->orderByDesc('numero')->first();

        return view('talentohumano::lider.tablero', [
            'fase' => $fase,
            'etapas' => $fase ? $this->etapas($fase) : [],
            'aprobaciones' => $this->aprobaciones($fase),
            'alertas' => $this->alertas($fase),
            'resumen' => $this->resumen(),
        ]);
    }

    /**
     * Página provisional de las secciones que todavía no están construidas.
     */
    public function seccion(string $seccion)
    {
        abort_unless(in_array($seccion, self::enConstruccion()), 404);

        return view('talentohumano::lider.seccion', ['clave' => $seccion, 'seccion' => self::SECCIONES[$seccion]]);
    }

    /**
     * Avance de cada etapa del proceso en la fase. Estado: completa, en_curso o pendiente.
     */
    private function etapas(object $fase): array
    {
        $cronograma = DB::table('th_cronogramas')->where('fase_id', $fase->id)->latest('id')->first();
        $convocatoria = DB::table('th_convocatorias')->whereNull('deleted_at')->where('fase_id', $fase->id)->first();
        $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria->id ?? 0);
        $pruebas = DB::table('th_aplicaciones_prueba')->whereIn('postulacion_id', (clone $postulaciones)->select('id'));
        $selecciones = DB::table('th_selecciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'));
        $empalmes = DB::table('th_empalmes')->where('fase_id', $fase->id);

        $fichas = DB::table('th_fase_fichas')->where('fase_id', $fase->id)->count();
        $cargos = DB::table('th_cargos_activados')->where('fase_id', $fase->id);
        $numCargos = (clone $cargos)->count();
        $numPostulados = (clone $postulaciones)->count();
        $presentadas = (clone $pruebas)->where('estado', 'presentada')->count();
        $totalPruebas = (clone $pruebas)->count();
        $entrevistas = DB::table('th_entrevistas')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria->id ?? 0)->count();
        $validadas = (clone $selecciones)->whereIn('estado', ['validada', 'ajustada'])->count();
        $totalSelecciones = (clone $selecciones)->count();
        $reubicaciones = DB::table('th_reubicaciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->count();
        $sinReubicar = (clone $postulaciones)->where('estado', 'no_seleccionado')->count();
        $cerrados = (clone $empalmes)->where('estado', 'cerrado')->count();
        $totalEmpalmes = (clone $empalmes)->count();
        $vinculados = DB::table('th_asignaciones')->where('fase_id', $fase->id)->where('estado', 'vigente')->count();

        $avance = fn (int $hechos, int $total) => $total === 0 ? 'pendiente' : ($hechos >= $total ? 'completa' : 'en_curso');

        return [
            ['icono' => 'fa-users-rectangle', 'titulo' => 'Fichas de la fase', 'valor' => $fichas, 'detalle' => 'fichas registradas', 'estado' => $fichas ? 'completa' : 'pendiente'],
            ['icono' => 'fa-toggle-on', 'titulo' => 'Cargos activados', 'valor' => $numCargos, 'detalle' => (int) (clone $cargos)->sum('numero_aprendices') . ' aprendices requeridos', 'estado' => $numCargos ? 'completa' : 'pendiente'],
            ['icono' => 'fa-calendar-days', 'titulo' => 'Cronograma', 'valor' => $cronograma ? ucfirst($cronograma->estado) : 'Sin crear', 'detalle' => 'aprobación del Instructor Líder',
                'estado' => match ($cronograma->estado ?? null) { 'aprobado' => 'completa', null => 'pendiente', default => 'en_curso' }],
            ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria', 'valor' => $convocatoria ? ucfirst($convocatoria->estado) : 'Sin crear', 'detalle' => 'publicación del Gestor',
                'estado' => match ($convocatoria->estado ?? null) { 'publicada', 'cerrada', 'finalizada' => 'completa', null => 'pendiente', default => 'en_curso' }],
            ['icono' => 'fa-file-signature', 'titulo' => 'Postulados', 'valor' => $numPostulados, 'detalle' => 'aprendices postulados', 'estado' => $numPostulados ? 'completa' : 'pendiente'],
            ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'valor' => "$presentadas / $totalPruebas", 'detalle' => 'pruebas presentadas', 'estado' => $avance($presentadas, $totalPruebas)],
            ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'valor' => $entrevistas, 'detalle' => 'entrevistas programadas', 'estado' => $entrevistas ? 'completa' : 'pendiente'],
            ['icono' => 'fa-user-check', 'titulo' => 'Selección', 'valor' => "$validadas / $totalSelecciones", 'detalle' => 'propuestas validadas', 'estado' => $avance($validadas, $totalSelecciones)],
            ['icono' => 'fa-arrows-rotate', 'titulo' => 'Reubicaciones', 'valor' => $reubicaciones, 'detalle' => $sinReubicar . ' sin seleccionar', 'estado' => $sinReubicar ? 'en_curso' : ($reubicaciones ? 'completa' : 'pendiente')],
            ['icono' => 'fa-handshake', 'titulo' => 'Empalmes', 'valor' => "$cerrados / $totalEmpalmes", 'detalle' => 'empalmes cerrados', 'estado' => $avance($cerrados, $totalEmpalmes)],
            ['icono' => 'fa-link', 'titulo' => 'Vinculación', 'valor' => $vinculados, 'detalle' => 'asignaciones vigentes', 'estado' => $vinculados ? 'completa' : 'pendiente'],
        ];
    }

    /**
     * Pendientes de aprobación del Instructor Líder.
     */
    private function aprobaciones(?object $fase): array
    {
        if (!$fase) {
            return [];
        }

        $convocatoria = DB::table('th_convocatorias')->whereNull('deleted_at')->where('fase_id', $fase->id)->value('id');
        $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->where('convocatoria_id', $convocatoria ?? 0);

        return array_values(array_filter([
            ['icono' => 'fa-calendar-days', 'texto' => 'Cronogramas por aprobar', 'cantidad' => DB::table('th_cronogramas')->where('fase_id', $fase->id)->where('estado', 'enviado')->count()],
            ['icono' => 'fa-user-check', 'texto' => 'Propuestas de selección por validar', 'cantidad' => DB::table('th_selecciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->where('estado', 'propuesta')->count()],
            ['icono' => 'fa-arrows-rotate', 'texto' => 'Aprendices por reubicar', 'cantidad' => (clone $postulaciones)->where('estado', 'no_seleccionado')->count()],
        ], fn ($a) => $a['cantidad'] > 0));
    }

    /**
     * Condiciones mínimas que no se cumplen (HU-011). La de la fase y la de plantillas solo cuentan si hay una fase en curso.
     */
    private function alertas(?object $fase): array
    {
        return collect(CondicionesMinimas::fallas())
            ->reject(fn ($c, $clave) => $clave === 'fase' || ($clave === 'plantilla' && !$fase))
            ->map(fn ($c) => ['texto' => $c['falla'], 'accion' => $c['accion'], 'ruta' => $c['ruta']])
            ->values()->all();
    }

    /**
     * Resumen de la configuración que mantiene el Administrador.
     */
    private function resumen(): array
    {
        $cargos = DB::table('th_cargos')->whereNull('deleted_at');

        return [
            'cargos_activos' => (clone $cargos)->where('estado', 'activo')->count(),
            'cargos_por_nivel' => (clone $cargos)->where('estado', 'activo')->selectRaw('nivel, count(*) as total')->groupBy('nivel')->pluck('total', 'nivel'),
            'designaciones' => DB::table('th_designaciones')->whereNull('fecha_retiro')->whereNull('deleted_at')->count(),
            'plantillas' => DB::table('th_plantillas_acta')->whereNull('deleted_at')->where('estado', 'vigente')->count(),
            'asignaciones_vigentes' => DB::table('th_asignaciones')->where('estado', 'vigente')->count(),
            'asignaciones_suspendidas' => DB::table('th_asignaciones')->where('estado', 'suspendida')->count(),
        ];
    }
}
