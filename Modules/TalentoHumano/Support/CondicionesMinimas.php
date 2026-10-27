<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Http\Controllers\InstructorLiderController;

/**
 * Condiciones mínimas para operar el proceso de Talento Humano (HU-011, RN-033, RN-053).
 */
class CondicionesMinimas
{
    /**
     * @return array<string, array{ok: bool, texto: string, falla: string, accion: ?string, ruta: ?string}>
     */
    public static function evaluar(): array
    {
        $hayPsicologo = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('users', 'users.id', '=', 'role_user.user_id')
            ->where('roles.slug', 'talentohumano.psicologo')
            ->whereNull('users.deleted_at')
            ->exists();

        return [
            'fase' => [
                'ok' => (bool) Fase::actual(),
                'texto' => 'Hay una fase en curso registrada.',
                'falla' => 'No hay una fase en curso registrada.',
                'accion' => 'Registrar fase', 'ruta' => InstructorLiderController::url('asignaciones'),
            ],
            'lider' => [
                'ok' => DB::table('th_designaciones')->where('tipo', 'lider_estrategia')->whereNull('fecha_retiro')->whereNull('deleted_at')->exists(),
                'texto' => 'Hay un Líder de la estrategia vigente para aprobar el cronograma.',
                'falla' => 'No hay un Instructor Líder designado para aprobar el cronograma.',
                'accion' => 'Asignar designación', 'ruta' => InstructorLiderController::url('designaciones'),
            ],
            'psicologo' => [
                'ok' => $hayPsicologo,
                'texto' => 'Hay al menos un psicólogo activo para la prueba y las entrevistas.',
                'falla' => 'No hay ningún psicólogo activo para la prueba y las entrevistas.',
                'accion' => null, 'ruta' => null,
            ],
            'plantilla' => [
                'ok' => DB::table('th_plantillas_acta')->whereNull('deleted_at')->whereNull('area_id')->where('estado', 'vigente')->exists(),
                'texto' => 'Hay una plantilla general vigente para los empalmes.',
                'falla' => 'No hay plantilla general vigente para los empalmes.',
                'accion' => 'Crear plantilla', 'ruta' => InstructorLiderController::url('plantillas'),
            ],
        ];
    }

    /**
     * Las que no se cumplen.
     */
    public static function fallas(): array
    {
        return array_filter(self::evaluar(), fn ($c) => !$c['ok']);
    }
}
