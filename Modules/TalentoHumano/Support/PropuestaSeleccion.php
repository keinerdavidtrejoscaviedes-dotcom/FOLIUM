<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Convocatoria;

/**
 * Propuesta de selección de una convocatoria (RF-010, RN-019, RN-020).
 *
 * Puntaje total = prueba psicotécnica + entrevista (promedio de todos los evaluadores). Para cada cargo se toman primero
 * los candidatos de 1.ª opción por puntaje total, luego los de 2.ª y los de 3.ª, solo si alcanzan el umbral. Si nadie lo
 * alcanza, se aplica la política de la convocatoria. Los cargos ya validados quedan en firme y sus seleccionados no
 * compiten por otros cargos.
 */
class PropuestaSeleccion
{
    /**
     * Calcula la propuesta sin guardar nada.
     *
     * @return object{umbral: float, politica: string, totales: Collection, cargos: Collection}
     */
    public static function calcular(Convocatoria $convocatoria): object
    {
        $umbral = (float) ($convocatoria->umbral ?? 0);
        $politica = $convocatoria->politica_sin_aptos ?? 'mayor_puntaje';
        $totales = self::totales($convocatoria);

        $cargos = DB::table('th_convocatoria_cargos as cc')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->where('cc.convocatoria_id', $convocatoria->id)
            ->select('cc.id', 'cc.cupos', 'c.nombre')
            ->get()->keyBy('id');

        $selecciones = DB::table('th_selecciones')->whereIn('convocatoria_cargo_id', $cargos->keys())
            ->whereIn('estado', ['validada', 'ajustada'])->get()->groupBy('convocatoria_cargo_id');
        $opciones = DB::table('th_postulacion_opciones')->whereIn('convocatoria_cargo_id', $cargos->keys())->get()->groupBy('convocatoria_cargo_id');

        // Los ya seleccionados en un cargo validado y los reubicados no compiten por otro cargo
        $asignados = $selecciones->flatten()->pluck('postulacion_id')->filter()
            ->merge(DB::table('th_reubicaciones')->whereIn('postulacion_id', $totales->keys())->pluck('postulacion_id'))
            ->flip()->all();

        $resultado = $cargos->map(function ($cargo) use ($selecciones, $opciones, $totales) {
            $validadas = $selecciones[$cargo->id] ?? collect();

            return (object) [
                'id' => $cargo->id,
                'nombre' => $cargo->nombre,
                'cupos' => (int) $cargo->cupos,
                'validado' => $validadas->isNotEmpty(),
                'validados' => $validadas->pluck('postulacion_id')->filter()->values()->all(),
                'restantes' => $validadas->isNotEmpty() ? 0 : (int) $cargo->cupos,
                'candidatos' => collect($opciones[$cargo->id] ?? [])->map(fn ($op) => (object) ([
                    'postulacion_id' => $op->postulacion_id,
                    'preferencia' => (int) $op->preferencia,
                ] + (array) ($totales[$op->postulacion_id] ?? ['prueba' => null, 'entrevista' => null, 'evaluadores' => 0, 'total' => null, 'elegible' => false])))->values(),
                'propuestos' => [],
                'empatados' => [],
                'sin_aptos' => false,
                'vacante' => false,
            ];
        });

        // Jerarquía de preferencia: 1.ª opción en todos los cargos, luego 2.ª y luego 3.ª (RF-010)
        foreach ([1, 2, 3] as $nivel) {
            foreach ($resultado as $cargo) {
                if ($cargo->restantes <= 0) {
                    continue;
                }
                $aptos = $cargo->candidatos
                    ->filter(fn ($c) => $c->preferencia === $nivel && $c->elegible && !isset($asignados[$c->postulacion_id]) && $c->total >= $umbral)
                    ->sortByDesc('total')->values();
                $tomados = $aptos->take($cargo->restantes);

                // Empate en el último cupo: mismo puntaje y misma opción (HU-035, esc. 7)
                if ($aptos->count() > $cargo->restantes && (float) $aptos[$cargo->restantes]->total === (float) $tomados->last()->total) {
                    $cargo->empatados = $aptos->where('total', $tomados->last()->total)->pluck('postulacion_id')->all();
                }

                foreach ($tomados as $c) {
                    $cargo->propuestos[] = $c->postulacion_id;
                    $asignados[$c->postulacion_id] = true;
                }
                $cargo->restantes -= $tomados->count();
            }
        }

        // Nadie alcanzó el umbral: mayor puntaje o vacante, según la convocatoria (RN-020)
        foreach ($resultado as $cargo) {
            if ($cargo->validado || $cargo->propuestos) {
                continue;
            }
            $disponibles = $cargo->candidatos->filter(fn ($c) => $c->elegible && !isset($asignados[$c->postulacion_id]))
                ->sortBy([['total', 'desc'], ['preferencia', 'asc']])->values();
            if ($politica === 'mayor_puntaje' && $disponibles->isNotEmpty()) {
                $cargo->sin_aptos = true;
                foreach ($disponibles->take($cargo->cupos) as $c) {
                    $cargo->propuestos[] = $c->postulacion_id;
                    $asignados[$c->postulacion_id] = true;
                }
            } else {
                $cargo->vacante = true;
            }
        }

        return (object) ['umbral' => $umbral, 'politica' => $politica, 'totales' => $totales, 'cargos' => $resultado];
    }

    /**
     * Guarda la propuesta (th_selecciones 'propuesta'), el puntaje total de cada postulación y su estado.
     */
    public static function sincronizar(Convocatoria $convocatoria): object
    {
        $calculo = self::calcular($convocatoria);

        DB::transaction(function () use ($convocatoria, $calculo) {
            foreach ($calculo->totales as $id => $t) {
                DB::table('th_postulaciones')->where('id', $id)->update(['puntaje_total' => $t->elegible ? $t->total : null]);
            }

            DB::table('th_selecciones')->whereIn('convocatoria_cargo_id', $calculo->cargos->keys())->where('estado', 'propuesta')->delete();
            foreach ($calculo->cargos as $cargo) {
                foreach ($cargo->validado ? [] : $cargo->propuestos as $id) {
                    DB::table('th_selecciones')->insert([
                        'convocatoria_cargo_id' => $cargo->id, 'postulacion_id' => $id, 'puntaje_total' => $calculo->totales[$id]->total,
                        'estado' => 'propuesta', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            // Estado de cada postulación: seleccionado si está en un cargo validado; no seleccionado si sus 3 cargos ya
            // se validaron sin él (pasa al comité de reubicación, HU-036)
            $validados = $calculo->cargos->where('validado', true);
            $seleccionados = $validados->pluck('validados')->flatten()->flip();
            $cargosValidados = $validados->keys()->flip();
            $opciones = DB::table('th_postulacion_opciones')->whereIn('postulacion_id', $calculo->totales->keys())->get()->groupBy('postulacion_id');

            DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria->id)->whereIn('estado', ['postulado', 'seleccionado', 'no_seleccionado'])
                ->get(['id', 'estado'])->each(function ($postulacion) use ($seleccionados, $cargosValidados, $opciones) {
                    $suyas = collect($opciones[$postulacion->id] ?? []);
                    $estado = match (true) {
                        isset($seleccionados[$postulacion->id]) => 'seleccionado',
                        $suyas->isNotEmpty() && $suyas->every(fn ($op) => isset($cargosValidados[$op->convocatoria_cargo_id])) => 'no_seleccionado',
                        default => 'postulado',
                    };
                    if ($estado !== $postulacion->estado) {
                        DB::table('th_postulaciones')->where('id', $postulacion->id)->update(['estado' => $estado, 'updated_at' => now()]);
                    }
                });
        });

        return $calculo;
    }

    /**
     * Puntajes de cada postulación: prueba presentada + promedio de las calificaciones de entrevista.
     * Es elegible quien presentó la prueba y tiene al menos una entrevista calificada (RN-013, RN-015).
     */
    private static function totales(Convocatoria $convocatoria): Collection
    {
        $postulaciones = DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria->id)->whereNull('deleted_at')->get(['id', 'estado']);
        $ids = $postulaciones->pluck('id');
        $pruebas = DB::table('th_aplicaciones_prueba')->whereIn('postulacion_id', $ids)->where('estado', 'presentada')->pluck('puntaje', 'postulacion_id');
        $entrevistas = DB::table('th_calificaciones')->whereIn('postulacion_id', $ids)
            ->selectRaw('postulacion_id, avg(puntaje) as promedio, count(*) as evaluadores')->groupBy('postulacion_id')->get()->keyBy('postulacion_id');

        return $postulaciones->mapWithKeys(function ($p) use ($pruebas, $entrevistas) {
            $prueba = isset($pruebas[$p->id]) ? (float) $pruebas[$p->id] : null;
            $entrevista = isset($entrevistas[$p->id]) ? round((float) $entrevistas[$p->id]->promedio, 2) : null;
            $elegible = $p->estado !== 'no_evaluado' && $prueba !== null && $entrevista !== null;

            return [$p->id => (object) [
                'prueba' => $prueba,
                'entrevista' => $entrevista,
                'evaluadores' => (int) ($entrevistas[$p->id]->evaluadores ?? 0),
                'total' => $elegible ? round($prueba + $entrevista, 2) : null,
                'elegible' => $elegible,
            ]];
        });
    }
}
