<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;

/**
 * Consulta de aprendices en los datos del ERP (SICEFA): persona, ficha y programa.
 */
class Aprendices
{
    /**
     * Persona con ese documento y, si es aprendiz, su ficha más reciente. Null si el documento no existe.
     */
    public static function porDocumento(string $documento): ?object
    {
        $documento = preg_replace('/\D/', '', $documento);
        if ($documento === '') {
            return null;
        }

        return self::consulta()->where('p.document_number', $documento)->first();
    }

    public static function porPersona(int $personId): ?object
    {
        return self::consulta()->where('p.id', $personId)->first();
    }

    private static function consulta()
    {
        $ficha = DB::table('apprentices as a')
            ->join('courses as c', 'c.id', '=', 'a.course_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'c.program_id')
            ->whereNull('a.deleted_at')
            ->whereColumn('a.person_id', 'p.id')
            ->orderByDesc('a.id')
            ->limit(1);

        return DB::table('people as p')
            ->whereNull('p.deleted_at')
            ->select('p.id', 'p.document_number as documento')
            ->selectRaw("TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name)) as nombre")
            ->selectRaw("COALESCE((select u.email from users u where u.person_id = p.id and u.deleted_at is null order by u.id limit 1), p.misena_email, p.sena_email, p.personal_email) as correo")
            ->selectSub((clone $ficha)->select('c.code'), 'ficha')
            ->selectSub((clone $ficha)->select('pr.name'), 'programa')
            ->selectSub((clone $ficha)->select('a.id'), 'aprendiz_id');
    }
}
