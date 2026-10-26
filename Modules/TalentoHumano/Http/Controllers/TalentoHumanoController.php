<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class TalentoHumanoController extends Controller
{
    /**
     * Secciones del módulo y los roles (sufijo del slug talentohumano.*) que las usan.
     */
    private const SECCIONES = [
        ['icono' => 'fa-layer-group', 'titulo' => 'Fases', 'texto' => 'Crea la fase, elige las fichas participantes y activa los cargos.', 'roles' => ['admin']],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria y cronograma', 'texto' => 'Publica la convocatoria y define las fechas de cada etapa.', 'roles' => ['admin', 'instructor'], 'ruta' => 'talentohumano.activacion.index'],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postulaciones', 'texto' => 'Revisa a los aspirantes y analiza sus hojas de vida.', 'roles' => ['admin', 'instructor', 'gestor'], 'ruta' => 'talentohumano.postulaciones.index'],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'texto' => 'Administra el cuestionario y consulta los resultados.', 'roles' => ['admin', 'psicologo']],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'texto' => 'Programa entrevistas, asigna evaluadores y registra calificaciones.', 'roles' => ['admin', 'instructor', 'psicologo', 'gestor'], 'ruta' => 'talentohumano.entrevistas.index'],
        ['icono' => 'fa-user-check', 'titulo' => 'Selección y actas', 'texto' => 'Valida a los seleccionados, haz reubicaciones y genera el acta.', 'roles' => ['admin', 'instructor'], 'ruta' => 'talentohumano.seleccion.index'],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalmes', 'texto' => 'Acta de recibimiento, inventario del cargo y firmas digitales.', 'roles' => ['admin', 'instructor', 'gestor'], 'ruta' => 'talentohumano.empalmes.index'],
        ['icono' => 'fa-book-open', 'titulo' => 'Catálogo de cargos', 'texto' => 'Rol, actividades, responsabilidades y requisitos de cada cargo.', 'roles' => ['admin', 'instructor', 'psicologo', 'gestor', 'aprendiz'], 'ruta' => 'talentohumano.catalogo.index'],
        ['icono' => 'fa-id-card', 'titulo' => 'Mi postulación', 'texto' => 'Postúlate a los cargos por prioridad y sigue el estado de tu proceso.', 'roles' => ['aprendiz']],
        ['icono' => 'fa-clipboard-check', 'titulo' => 'Mi prueba psicotécnica', 'texto' => 'Presenta la prueba cuando esté habilitada en el cronograma.', 'roles' => ['aprendiz']],
        ['icono' => 'fa-signature', 'titulo' => 'Mi empalme', 'texto' => 'Recibe tu cargo, revisa el inventario y firma el acta.', 'roles' => ['aprendiz']],
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

        $roles = $user->roles->filter(fn ($role) => Str::startsWith($role->slug, 'talentohumano.'))->values();
        $esSuperadmin = $user->hasSuperAdmin();

        $tipos = $esSuperadmin
            ? ['admin']
            : $roles->map(fn ($role) => Str::after($role->slug, 'talentohumano.'))->all();

        $esInstructor = in_array('instructor', $tipos) || in_array('admin', $tipos) || $esSuperadmin;

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
            ->filter(fn ($seccion) => array_intersect($seccion['roles'], $tipos))
            ->values();

        return view('talentohumano::panel', [
            'user' => $user,
            'roles' => $esSuperadmin ? collect(['Super Administrador']) : $roles->pluck('name'),
            'designaciones' => $designacionesNombres,
            'esInstructor' => $esInstructor,
            'faseActiva' => $faseActiva,
            'secciones' => $secciones,
        ]);
    }
}
