<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Modules\TalentoHumano\Support\AccesoTH;

class TalentoHumanoController extends Controller
{
    /**
     * Secciones del módulo y los roles (sufijo del slug talentohumano.*) que las usan.
     */
    private const SECCIONES = [
        ['icono' => 'fa-layer-group', 'titulo' => 'Fichas y cargos de la fase', 'texto' => 'Registra las fichas que ingresan y activa los cargos de la fase.', 'roles' => ['gestor']],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria y cronograma', 'texto' => 'Elabora el cronograma y publica la convocatoria.', 'roles' => ['gestor']],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postulaciones', 'texto' => 'Revisa a los aspirantes y analiza sus hojas de vida.', 'roles' => ['instructor', 'gestor']],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'texto' => 'Administra el cuestionario y consulta los resultados.', 'roles' => ['psicologo']],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'texto' => 'Programa entrevistas, asigna evaluadores y registra calificaciones.', 'roles' => ['instructor', 'psicologo', 'gestor']],
        ['icono' => 'fa-user-check', 'titulo' => 'Selección y actas', 'texto' => 'Valida a los seleccionados, haz reubicaciones y genera el acta.', 'roles' => ['instructor']],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalmes', 'texto' => 'Acta de recibimiento, inventario del cargo y firmas digitales.', 'roles' => ['instructor', 'gestor']],
        ['icono' => 'fa-book-open', 'titulo' => 'Catálogo de cargos', 'texto' => 'Rol, actividades, responsabilidades y requisitos de cada cargo.', 'roles' => ['instructor', 'psicologo', 'gestor', 'aprendiz']],
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

        // El Administrador y el Líder de la estrategia trabajan desde su submódulo (HU-037)
        if (AccesoTH::veSubmoduloLider($user)) {
            return redirect()->route('talentohumano.lider.tablero');
        }

        $roles = $user->roles->filter(fn ($role) => Str::startsWith($role->slug, 'talentohumano.'))->values();
        $tipos = $roles->map(fn ($role) => Str::after($role->slug, 'talentohumano.'))->all();

        $secciones = collect(self::SECCIONES)
            ->filter(fn ($seccion) => array_intersect($seccion['roles'], $tipos))
            ->values();

        return view('talentohumano::panel', [
            'user' => $user,
            'roles' => $roles->pluck('name'),
            'secciones' => $secciones,
        ]);
    }
}
