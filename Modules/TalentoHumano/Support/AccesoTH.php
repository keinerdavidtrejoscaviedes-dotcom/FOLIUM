<?php

namespace Modules\TalentoHumano\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de acceso de FOLIUM que dependen del rol o de las designaciones del usuario.
 */
class AccesoTH
{
    /**
     * Matriz de permisos por sección del proceso (SRS 2.5, HU-002 esc. 4): roles talentohumano.* (sin el prefijo)
     * y designaciones vigentes que pueden entrar. El Administrador entra a todas.
     */
    public const PERMISOS = [
        'activacion' => ['gestor', 'instructor_th', 'lider_estrategia'],
        'postulaciones' => ['gestor', 'instructor_th', 'lider_estrategia'],
        'entrevistas' => ['gestor', 'psicologo', 'instructor_th', 'instructor_tecnico', 'lider_estrategia'],
        'seleccion' => ['instructor_th', 'lider_estrategia'],
        'reubicaciones' => ['lider_estrategia'],
        'empalmes' => ['gestor', 'instructor_th'],
        'catalogo' => ['gestor', 'instructor', 'psicologo', 'aprendiz', 'instructor_th', 'instructor_tecnico', 'lider_estrategia'],
    ];

    /**
     * Administrador de FOLIUM: superadmin del ERP o rol talentohumano.admin.
     */
    public static function esAdministrador(User $user): bool
    {
        return $user->hasSuperAdmin() || $user->roles->contains('slug', 'talentohumano.admin');
    }

    /**
     * Instructor con la designación vigente de Líder de la estrategia.
     */
    public static function esLiderEstrategia(User $user): bool
    {
        return in_array('lider_estrategia', self::designaciones($user));
    }

    /**
     * Tipos de designación vigentes del usuario (lider_estrategia, instructor_th, instructor_tecnico).
     */
    public static function designaciones(User $user): array
    {
        return DB::table('th_designaciones')
            ->where('person_id', $user->person_id)
            ->whereNull('fecha_retiro')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('tipo')
            ->all();
    }

    /**
     * Submódulo "Instructor Líder de TH" (HU-037): lo ven el Administrador y el Líder de la estrategia.
     */
    public static function veSubmoduloLider(User $user): bool
    {
        return self::esAdministrador($user) || self::esLiderEstrategia($user);
    }

    /**
     * Correos de los administradores de FOLIUM (rol talentohumano.admin), para los avisos de HU-015 y RN-031.
     */
    public static function correosAdministradores(): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.admin'))->pluck('email')->filter()->unique()->values();
    }

    /**
     * Roles de Talento Humano (sin el prefijo talentohumano.) y designaciones vigentes del usuario.
     */
    public static function funciones(User $user): array
    {
        $roles = $user->roles->pluck('slug')
            ->filter(fn ($slug) => str_starts_with($slug, 'talentohumano.'))
            ->map(fn ($slug) => substr($slug, strlen('talentohumano.')));

        return $roles->merge(self::designaciones($user))->unique()->values()->all();
    }

    /**
     * ¿El usuario puede entrar a esta sección del proceso? Negar por defecto: sin persona asociada, nadie más que el Administrador.
     */
    public static function puede(User $user, string $seccion): bool
    {
        if (self::esAdministrador($user)) {
            return true;
        }

        return $user->person_id && array_intersect(self::funciones($user), self::PERMISOS[$seccion] ?? []);
    }

    /**
     * Designación con la que el usuario valida una selección (th_selecciones.validada_con; RN-021, RN-036).
     */
    public static function designacionValidadora(User $user): string
    {
        return in_array('instructor_th', self::designaciones($user)) ? 'instructor_th' : 'lider_estrategia';
    }
}
