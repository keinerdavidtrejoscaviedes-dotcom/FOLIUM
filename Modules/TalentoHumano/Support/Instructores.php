<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Personas que pueden recibir designaciones (instructores) y psicólogos, según los datos del ERP.
 *
 * Instructores: funcionarios o contratistas activos con tipo de empleado "Instructor" (igual que SICA/InstructorController)
 * y usuarios con rol talentohumano.instructor o talentohumano.admin. Los psicólogos no reciben designación (HU-009, esc. 6).
 */
class Instructores
{
    /**
     * @return Collection<int, object{id: int, nombre: string, documento: string, correo: ?string}>
     */
    public static function designables(): Collection
    {
        $sicefa = DB::table('employees')
            ->join('employee_types', 'employee_types.id', '=', 'employees.employee_type_id')
            ->where('employee_types.name', 'Instructor')->where('employees.state', 'Activo')->whereNull('employees.deleted_at')
            ->select('employees.person_id')
            ->union(DB::table('contractors')
                ->join('employee_types', 'employee_types.id', '=', 'contractors.employee_type_id')
                ->where('employee_types.name', 'Instructor')->where('contractors.state', 'Activo')->whereNull('contractors.deleted_at')
                ->select('contractors.person_id'))
            ->union(self::conRoles(['talentohumano.instructor', 'talentohumano.admin']));

        return self::personas(DB::query()->fromSub($sicefa, 'i')->pluck('person_id'))
            ->whereNotIn('id', self::psicologos()->pluck('id'))
            ->values();
    }

    /**
     * Usuarios activos con rol Psicólogo (evaluadores psicológicos).
     */
    public static function psicologos(): Collection
    {
        return self::personas(self::conRoles(['talentohumano.psicologo'])->pluck('users.person_id'));
    }

    public static function esDesignable(int $personId): bool
    {
        return self::designables()->contains('id', $personId);
    }

    private static function conRoles(array $slugs)
    {
        return DB::table('users')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->whereIn('roles.slug', $slugs)
            ->whereNull('users.deleted_at')
            ->select('users.person_id');
    }

    private static function personas($ids): Collection
    {
        return DB::table('people')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->selectRaw("id, TRIM(CONCAT_WS(' ', first_name, first_last_name, second_last_name)) as nombre, document_number as documento,
                COALESCE((select u.email from users u where u.person_id = people.id and u.deleted_at is null order by u.id limit 1),
                         misena_email, sena_email, personal_email) as correo")
            ->orderBy('first_name')->orderBy('first_last_name')
            ->get();
    }
}
