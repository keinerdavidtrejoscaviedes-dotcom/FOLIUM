<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Modules\TalentoHumano\Support\AccesoTH;

class TalentoHumanoController extends Controller
{
    /**
     * Secciones del módulo. Las que tienen 'seccion' se muestran según la matriz AccesoTH::PERMISOS (la misma que protege
     * sus rutas); las demás, según los roles (sufijo del slug talentohumano.*).
     */
    private const SECCIONES = [
        ['icono' => 'fa-calendar-days', 'titulo' => 'Fases', 'texto' => 'Registra la fase, elige las fichas participantes, activa los cargos y elabora el cronograma.', 'roles' => ['gestor'], 'ruta' => 'talentohumano.fases.index', 'seccion' => 'activacion'],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria y cronograma', 'texto' => 'Define las fechas de cada etapa y publica la convocatoria, desde la página de cada fase.', 'roles' => ['gestor'], 'ruta' => 'talentohumano.fases.index', 'seccion' => 'activacion'],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postulaciones', 'texto' => 'Revisa a los aspirantes y analiza sus hojas de vida.', 'roles' => ['instructor', 'gestor'], 'ruta' => 'talentohumano.postulaciones.index', 'seccion' => 'postulaciones'],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'texto' => 'Diseña las pruebas que presentan los postulantes, con sus preguntas y puntajes.', 'roles' => ['psicologo', 'gestor'], 'ruta' => 'talentohumano.pruebas.index', 'seccion' => 'pruebas'],
        ['icono' => 'fa-list-check', 'titulo' => 'Mi formato de entrevista psicológica', 'texto' => 'Tus preguntas de la entrevista psicológica con su puntaje, para calificar a los aspirantes.', 'roles' => ['psicologo'], 'ruta' => 'talentohumano.formato.edit', 'seccion' => 'formato'],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'texto' => 'Programa entrevistas, asigna evaluadores y registra calificaciones.', 'roles' => ['instructor', 'psicologo', 'gestor'], 'ruta' => 'talentohumano.entrevistas.index', 'seccion' => 'entrevistas'],
        ['icono' => 'fa-user-check', 'titulo' => 'Selección y actas', 'texto' => 'Valida a los seleccionados, haz reubicaciones y genera el acta.', 'roles' => ['instructor'], 'ruta' => 'talentohumano.seleccion.index', 'seccion' => 'seleccion'],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalmes', 'texto' => 'Acta de recibimiento, inventario del cargo y firmas digitales.', 'roles' => ['instructor', 'gestor'], 'ruta' => 'talentohumano.empalmes.index', 'seccion' => 'empalmes'],
        ['icono' => 'fa-book-open', 'titulo' => 'Catálogo de cargos', 'texto' => 'Rol, actividades, responsabilidades y requisitos de cada cargo.', 'roles' => ['instructor', 'psicologo', 'gestor', 'aprendiz'], 'ruta' => 'talentohumano.catalogo.index', 'seccion' => 'catalogo'],
        ['icono' => 'fa-id-card', 'titulo' => 'Mi postulación', 'texto' => 'Postúlate a los cargos por prioridad y sigue el estado de tu proceso.', 'roles' => ['aprendiz'], 'ruta' => 'talentohumano.aprendiz.postulacion'],
        ['icono' => 'fa-clipboard-check', 'titulo' => 'Mi prueba psicotécnica', 'texto' => 'Presenta la prueba cuando esté habilitada en el cronograma.', 'roles' => ['aprendiz'], 'ruta' => 'talentohumano.aprendiz.prueba'],
        ['icono' => 'fa-signature', 'titulo' => 'Mi empalme', 'texto' => 'Recibe tu cargo, revisa el inventario y firma el acta.', 'roles' => ['aprendiz'], 'ruta' => 'talentohumano.aprendiz.empalme'],
        ['icono' => 'fa-calendar-days', 'titulo' => 'Cronograma de selección', 'texto' => 'Consulta las fechas aprobadas de cada etapa del proceso.', 'roles' => ['aprendiz'], 'ruta' => 'talentohumano.aprendiz.cronograma'],
        ['icono' => 'fa-id-card-clip', 'titulo' => 'Mi cargo e historial', 'texto' => 'Consulta tu cargo vigente o el histórico en SENA Empresa.', 'roles' => ['aprendiz'], 'ruta' => 'talentohumano.aprendiz.cargo'],
    ];

    /**
     * Página de presentación de FOLIUM (pública).
     */
    public function index()
    {
        return view('talentohumano::index');
    }

    /**
     * Panel del módulo: punto de partida de FOLIUM después de iniciar sesión.
     */
    public function panel()
    {
        $user = auth()->user();

        // El Administrador y el Líder de la estrategia trabajan desde su submódulo (HU-037)
        if (AccesoTH::veSubmoduloLider($user)) {
            return redirect()->route('talentohumano.lider.tablero');
        }

        $roles = $user->roles->filter(fn ($role) => Str::startsWith($role->slug, 'talentohumano.'))->values();
        $tipos = $roles->map(fn ($role) => Str::after($role->slug, 'talentohumano.'))->all();

        $esInstructor = in_array('instructor', $tipos);

        // Cargar designaciones del usuario (RF-016)
        $designaciones = $user->person_id
            ? \Modules\TalentoHumano\Entities\Designacion::where('person_id', $user->person_id)
                ->whereNull('fecha_retiro')
                ->with('program')
                ->get()
            : collect();

        $designacionesNombres = $designaciones->map(function ($d) {
            return match ($d->tipo) {
                'instructor_th' => 'Instructor de Talento Humano',
                'lider_estrategia' => 'Líder de la estrategia',
                'instructor_tecnico' => 'Instructor técnico' . ($d->program ? " · {$d->program->name}" : ''),
                default => 'Instructor',
            };
        });

        // Fase activa para contexto del dashboard
        $faseActiva = \Modules\TalentoHumano\Entities\Fase::whereIn('estado', ['planeacion', 'seleccion', 'en_curso'])
            ->orderByDesc('numero')
            ->first();

        // Secciones por defecto
        $secciones = collect(self::SECCIONES)
            ->filter(fn ($seccion) => isset($seccion['seccion']) ? AccesoTH::puede($user, $seccion['seccion']) : array_intersect($seccion['roles'], $tipos))
            ->values();

        return view('talentohumano::panel', [
            'user' => $user,
            'roles' => $roles->pluck('name'),
            'designaciones' => $designacionesNombres,
            'esInstructor' => $esInstructor,
            'faseActiva' => $faseActiva,
            'secciones' => $secciones,
        ]);
    }
}
