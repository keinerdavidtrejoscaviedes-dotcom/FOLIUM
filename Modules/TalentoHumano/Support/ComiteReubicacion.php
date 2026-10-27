<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\CargoActivado;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Postulacion;
use Modules\TalentoHumano\Entities\Reubicacion;

/**
 * Datos del comité de reubicación de aprendices no seleccionados (RF-011, HU-036, RN-022, RN-049).
 */
class ComiteReubicacion
{
    /**
     * Convocatoria en curso: la de la fase actual que no esté finalizada (o la más reciente).
     */
    public static function convocatoria(): ?Convocatoria
    {
        $fase = Fase::actual();

        return Convocatoria::query()
            ->when($fase, fn ($q) => $q->where('fase_id', $fase->id))
            ->orderByRaw("estado = 'finalizada'")
            ->latest('id')
            ->first();
    }

    /**
     * Aprendices no seleccionados en ninguna de sus 3 opciones que aún no tienen decisión del comité (HU-036, esc. 1 y 8).
     */
    public static function pendientes(Convocatoria $convocatoria): Collection
    {
        return Postulacion::with(['person', 'course.program'])
            ->where('convocatoria_id', $convocatoria->id)
            ->where('estado', 'no_seleccionado')
            ->whereNotExists(fn ($q) => $q->from('th_reubicaciones')->whereColumn('th_reubicaciones.postulacion_id', 'th_postulaciones.id'))
            ->orderByDesc('puntaje_total')
            ->get();
    }

    /**
     * Decisiones ya registradas en la convocatoria.
     */
    public static function decididas(Convocatoria $convocatoria): Collection
    {
        return Reubicacion::with(['postulacion.person', 'cargoActivado.cargo'])
            ->whereHas('postulacion', fn ($q) => $q->where('convocatoria_id', $convocatoria->id))
            ->latest('id')
            ->get();
    }

    /**
     * Cargos activados de la fase con cupos vacantes: cupos del cargo menos las selecciones y reubicaciones (HU-036, esc. 5).
     *
     * @return Collection<int, array{cargo_activado: CargoActivado, vacantes: int, programas: array}>
     */
    public static function vacantes(Convocatoria $convocatoria): Collection
    {
        $selecciones = DB::table('th_selecciones as s')
            ->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
            ->where('cc.convocatoria_id', $convocatoria->id)
            ->selectRaw('cc.cargo_activado_id, count(*) as total')->groupBy('cc.cargo_activado_id')->pluck('total', 'cargo_activado_id');
        $reubicaciones = DB::table('th_reubicaciones')->whereNotNull('cargo_activado_id')
            ->selectRaw('cargo_activado_id, count(*) as total')->groupBy('cargo_activado_id')->pluck('total', 'cargo_activado_id');
        $programas = DB::table('th_cargo_programas')->get()->groupBy('cargo_id')->map(fn ($p) => $p->pluck('program_id')->all());

        return CargoActivado::with('cargo')->where('fase_id', $convocatoria->fase_id)->get()
            ->map(fn ($ca) => [
                'cargo_activado' => $ca,
                'vacantes' => $ca->numero_aprendices - ($selecciones[$ca->id] ?? 0) - ($reubicaciones[$ca->id] ?? 0),
                'programas' => $programas[$ca->cargo_id] ?? [],
            ])
            ->filter(fn ($v) => $v['vacantes'] > 0 && $v['cargo_activado']->cargo)
            ->sortBy(fn ($v) => $v['cargo_activado']->cargo->nombre)
            ->values();
    }

    /**
     * El cargo es compatible si el programa de la ficha del aprendiz está entre sus tecnólogos (RN-049).
     */
    public static function esCompatible(array $vacante, Postulacion $postulacion): bool
    {
        return in_array($postulacion->course?->program_id, $vacante['programas']);
    }

    /**
     * Integrantes del comité: el Líder de la estrategia y los instructores y psicólogos que evaluaron en la convocatoria (RN-022).
     *
     * @return Collection<int, object{id: int, nombre: string, funcion: string}>
     */
    public static function integrantes(Convocatoria $convocatoria): Collection
    {
        $nombre = "TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name))";
        $lideres = DB::table('th_designaciones as d')->join('people as p', 'p.id', '=', 'd.person_id')
            ->where('d.tipo', 'lider_estrategia')->whereNull('d.fecha_retiro')->whereNull('d.deleted_at')
            ->selectRaw("p.id, {$nombre} as nombre, 'Líder de la estrategia' as funcion");
        $evaluadores = DB::table('th_entrevista_evaluadores as ev')->join('th_entrevistas as e', 'e.id', '=', 'ev.entrevista_id')
            ->join('people as p', 'p.id', '=', 'ev.person_id')
            ->where('e.convocatoria_id', $convocatoria->id)->whereNull('e.deleted_at')
            ->selectRaw("p.id, {$nombre} as nombre, IF(e.tipo = 'psicologica', 'Psicólogo', 'Instructor evaluador') as funcion");

        return $lideres->union($evaluadores)->get()->unique('id')->values();
    }
}
