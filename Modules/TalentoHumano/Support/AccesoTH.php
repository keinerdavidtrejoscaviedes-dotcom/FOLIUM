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
}
