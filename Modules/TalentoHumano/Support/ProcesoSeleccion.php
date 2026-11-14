<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\ConvocatoriaEvaluador;
use Modules\TalentoHumano\Entities\Cronograma;
use Modules\TalentoHumano\Entities\Fase;

/**
 * Reglas compartidas del proceso de selección que coordina el Gestor de TH después de publicar la convocatoria:
 * habilitar la prueba (HU-025), evaluadores (HU-026), entrevistas (HU-027) y acta de seleccionados (HU-028).
 */
class ProcesoSeleccion
{
    /**
     * Convocatoria publicada de la fase (también cerrada o finalizada), o null si aún no se publica.
     */
    public static function convocatoria(Fase $fase): ?Convocatoria
    {
        return Convocatoria::where('fase_id', $fase->id)->whereIn('estado', ['publicada', 'cerrada', 'finalizada'])->first();
    }

    /**
     * Fechas de una etapa del cronograma aprobado de la fase: [inicio, fin] (fin = inicio si la etapa es de un día).
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function etapa(Fase $fase, string $etapa): array
    {
        $cronograma = Cronograma::where('fase_id', $fase->id)->where('estado', 'aprobado')->latest('id')->first();
        $fila = $cronograma?->etapas()->where('etapa', $etapa)->first();

        return [$fila?->fecha_inicio, $fila?->fecha_fin ?? $fila?->fecha_inicio];
    }

    /**
     * Incluye en la convocatoria al Instructor Líder y a los instructores que lideran las fichas de la fase
     * (HU-026A esc. 1, RN-042). En SICEFA (courses.person_id) cada ficha tiene su instructor líder.
     * Si alguno se retiró (por ejemplo, no está en el centro), su fila sigue con retirado_en y no se vuelve a incluir.
     */
    public static function incluirEvaluadoresObligatorios(Convocatoria $convocatoria): void
    {
        $lideres = DB::table('th_designaciones')
            ->where('tipo', 'lider_estrategia')->whereNull('fecha_retiro')->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('fase_id')->orWhere('fase_id', $convocatoria->fase_id))
            ->pluck('person_id');
        $deFichas = DB::table('th_fase_fichas as ff')->join('courses as c', 'c.id', '=', 'ff.course_id')
            ->where('ff.fase_id', $convocatoria->fase_id)->whereNotNull('c.person_id')
            ->pluck('c.person_id');

        foreach ([['lider', $lideres], ['ficha', $deFichas]] as [$motivo, $personas]) {
            foreach ($personas->unique() as $personId) {
                ConvocatoriaEvaluador::firstOrCreate(
                    ['convocatoria_id' => $convocatoria->id, 'person_id' => $personId],
                    ['tipo' => 'tecnico', 'motivo' => $motivo]
                );
            }
        }
    }

    /**
     * Postulantes de la convocatoria con su ficha, si tienen análisis de hoja de vida y el estado de su prueba.
     */
    public static function postulantes(Convocatoria $convocatoria): Collection
    {
        return DB::table('th_postulaciones as po')
            ->join('people as p', 'p.id', '=', 'po.person_id')
            ->leftJoin('courses as c', 'c.id', '=', 'po.course_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'c.program_id')
            ->where('po.convocatoria_id', $convocatoria->id)
            ->whereNull('po.deleted_at')
            ->selectRaw("po.id, po.person_id, po.estado, c.code as ficha, pr.name as programa,
                TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name, p.second_last_name)) as nombre")
            ->addSelect([
                'analizada' => DB::table('th_analisis_hv')->selectRaw('count(*) > 0')->whereColumn('postulacion_id', 'po.id'),
                // Solo el estado de la prueba: el Gestor no ve respuestas ni puntajes (RN-063)
                'prueba' => DB::table('th_aplicaciones_prueba')->select('estado')->whereColumn('postulacion_id', 'po.id')->latest('id')->limit(1),
                'limite_prueba' => DB::table('th_aplicaciones_prueba')->select('fecha_limite')->whereColumn('postulacion_id', 'po.id')->latest('id')->limit(1),
            ])
            ->orderBy('p.first_name')->orderBy('p.first_last_name')
            ->get();
    }

    /**
     * Correos de un grupo de personas (el de su usuario del ERP o el de su ficha en people).
     */
    public static function correos(iterable $personIds): array
    {
        return collect($personIds)->map(fn ($id) => Correo::de($id))->filter()->unique()->values()->all();
    }
}
