<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Acta;

/**
 * Acta de seleccionados de una fase (RF-012): selecciones validadas más los reubicados por el comité.
 * La consultan el Instructor Líder (Supervisión) y los postulantes de la convocatoria (HU-051).
 */
class ActaSeleccionados
{
    /**
     * Acta publicada de la fase, o null si aún no se publica.
     */
    public static function publicada(int $faseId): ?Acta
    {
        return Acta::where('tipo', 'seleccionados')->where('fase_id', $faseId)->where('estado', 'publicada')->latest('id')->first();
    }

    /**
     * Filas del acta: aprendiz, ficha, cargo y observación ('Reubicado' para las decisiones del comité).
     */
    public static function filas(int $convocatoriaId): Collection
    {
        $nombre = "TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name))";
        $seleccionados = DB::table('th_selecciones as s')
            ->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->join('th_postulaciones as po', 'po.id', '=', 's.postulacion_id')
            ->join('people as p', 'p.id', '=', 'po.person_id')
            ->leftJoin('courses as co', 'co.id', '=', 'po.course_id')
            ->where('cc.convocatoria_id', $convocatoriaId)->whereIn('s.estado', ['validada', 'ajustada'])
            ->selectRaw("po.person_id, {$nombre} as aprendiz, co.code as ficha, c.nombre as cargo, NULL as observacion");
        $reubicados = DB::table('th_reubicaciones as r')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'r.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->join('th_postulaciones as po', 'po.id', '=', 'r.postulacion_id')
            ->join('people as p', 'p.id', '=', 'po.person_id')
            ->leftJoin('courses as co', 'co.id', '=', 'po.course_id')
            ->where('po.convocatoria_id', $convocatoriaId)
            ->selectRaw("po.person_id, {$nombre} as aprendiz, co.code as ficha, c.nombre as cargo, 'Reubicado' as observacion");

        return $seleccionados->unionAll($reubicados)->get()->sortBy('cargo')->values();
    }
}
