<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class TalentoHumanoController extends Controller
{
    /**
     * Secciones del módulo y los roles (sufijo del slug talentohumano.*) que las usan.
     * 'ruta': nombre de la ruta de la sección; sin ella, la tarjeta se muestra como "Próximamente".
     */
    private const SECCIONES = [
        ['icono' => 'fa-layer-group', 'titulo' => 'Fases', 'texto' => 'Registra la fase, elige las fichas participantes, activa los cargos y elabora el cronograma.', 'roles' => ['admin', 'instructor', 'gestor'], 'ruta' => 'talentohumano.fases.index'],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria y cronograma', 'texto' => 'Define las fechas de cada etapa y publica la convocatoria, desde la página de cada fase.', 'roles' => ['admin', 'instructor', 'gestor'], 'ruta' => 'talentohumano.fases.index'],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postulaciones', 'texto' => 'Revisa a los aspirantes y analiza sus hojas de vida.', 'roles' => ['admin', 'instructor', 'gestor']],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'texto' => 'Diseña las pruebas que presentan los postulantes, con sus preguntas y puntajes.', 'roles' => ['admin', 'psicologo', 'gestor'], 'ruta' => 'talentohumano.pruebas.index'],
        ['icono' => 'fa-list-check', 'titulo' => 'Mi formato de entrevista', 'texto' => 'Tus preguntas de entrevista con su puntaje, para calificar a los aspirantes.', 'roles' => ['psicologo', 'instructor'], 'ruta' => 'talentohumano.formato.edit'],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'texto' => 'Programa entrevistas, asigna evaluadores y registra calificaciones.', 'roles' => ['admin', 'instructor', 'psicologo', 'gestor']],
        ['icono' => 'fa-user-check', 'titulo' => 'Selección y actas', 'texto' => 'Valida a los seleccionados, haz reubicaciones y genera el acta.', 'roles' => ['admin', 'instructor']],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalmes', 'texto' => 'Acta de recibimiento, inventario del cargo y firmas digitales.', 'roles' => ['admin', 'instructor', 'gestor']],
        ['icono' => 'fa-book-open', 'titulo' => 'Catálogo de cargos', 'texto' => 'Rol, actividades, responsabilidades y requisitos de cada cargo.', 'roles' => ['admin', 'instructor', 'psicologo', 'gestor', 'aprendiz']],
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

        $secciones = collect(self::SECCIONES)
            ->filter(fn ($seccion) => array_intersect($seccion['roles'], $tipos))
            ->values();

        return view('talentohumano::panel', [
            'user' => $user,
            'roles' => $esSuperadmin ? collect(['Super Administrador']) : $roles->pluck('name'),
            'secciones' => $secciones,
        ]);
    }
}
