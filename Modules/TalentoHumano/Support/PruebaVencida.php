<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Entities\Postulacion;

/**
 * Postulantes que no presentaron la prueba psicotécnica a tiempo (HU-047 esc. 3, HU-025B, RN-013).
 *
 * Venció su plazo (o terminó la etapa de la prueba del cronograma) sin enviarla: quedan «No evaluado», su entrevista
 * queda bloqueada y se avisa al Gestor de TH en su panel, en el historial de alertas y por correo. No depende de que el
 * aprendiz vuelva a entrar: se revisa al abrir el panel o las pantallas del proceso y con la tarea programada
 * `talentohumano:vencer-pruebas`.
 */
class PruebaVencida
{
    /**
     * Marca los vencidos de la convocatoria (todas las abiertas si no se indica). Devuelve los nombres marcados ahora.
     */
    public static function sincronizar(?Convocatoria $convocatoria = null): Collection
    {
        // HU-024A: primero, las convocatorias con la fecha de cierre vencida pasan a «Cerrada»
        Convocatoria::cerrarVencidas();
        $convocatoria?->refresh();

        // Sin convocatoria indicada (tarea programada): todas las publicadas de las fases que no están cerradas
        if (!$convocatoria) {
            return Convocatoria::whereIn('estado', ['publicada', 'cerrada'])
                ->whereHas('fase', fn ($q) => $q->where('estado', '!=', 'cerrada'))
                ->get()->flatMap(fn ($c) => self::sincronizar($c))->values();
        }

        $fase = $convocatoria->fase ?? Fase::find($convocatoria->fase_id);
        [, $finEtapa] = $fase ? ProcesoSeleccion::etapa($fase, 'prueba_psicotecnica') : [null, null];
        $etapaTerminada = $finEtapa && today()->gt($finEtapa);

        $vencidos = ProcesoSeleccion::postulantes($convocatoria)
            ->where('estado', 'postulado')
            ->filter(fn ($p) => $p->prueba !== 'presentada' && (
                (in_array($p->prueba, ['habilitada', 'en_curso']) && $p->limite_prueba && now()->gt($p->limite_prueba))
                || $etapaTerminada
            ));

        if ($vencidos->isEmpty()) {
            return collect();
        }

        foreach ($vencidos as $vencido) {
            $postulacion = Postulacion::find($vencido->id);
            DB::transaction(function () use ($postulacion) {
                $postulacion->update(['estado' => 'no_evaluado']);
                DB::table('th_aplicaciones_prueba')->where('postulacion_id', $postulacion->id)
                    ->whereIn('estado', ['habilitada', 'en_curso'])->update(['estado' => 'no_evaluado', 'updated_at' => now()]);
            });
            Auditoria::registrar('prueba_no_evaluado', $postulacion, ['estado' => 'postulado'], ['estado' => 'no_evaluado']);
        }

        $nombres = $vencidos->pluck('nombre')->values();
        $enlace = $fase ? route('talentohumano.fases.prueba.index', $fase->id) : null;
        $titulo = $nombres->count() === 1
            ? "{$nombres[0]} no presentó la prueba psicotécnica"
            : "{$nombres->count()} postulantes no presentaron la prueba psicotécnica";
        $texto = 'Quedaron como «No evaluado» y no se les puede programar entrevista (RN-013): ' . $nombres->implode(', ') . '.';

        Alerta::create(['fase_id' => $fase?->id, 'etapa' => 'Prueba psicotécnica', 'tipo' => 'prueba_no_presentada',
            'titulo' => $titulo, 'texto' => $texto, 'enlace' => $enlace]);
        Correo::enviar(self::correosGestores(), "FOLIUM: {$titulo}",
            "FOLIUM · Talento Humano\n\nVenció el plazo de la prueba psicotécnica sin que la presentaran:\n\n- " . $nombres->implode("\n- ")
            . "\n\nQuedaron como «No evaluado» y su entrevista queda bloqueada." . ($enlace ? "\n\nSeguimiento de la prueba: {$enlace}" : ''));

        return $nombres;
    }

    /**
     * Postulantes «No evaluado» de la convocatoria, por nombre (para el aviso del panel del Gestor).
     */
    public static function noEvaluados(Convocatoria $convocatoria): Collection
    {
        return ProcesoSeleccion::postulantes($convocatoria)->where('estado', 'no_evaluado')->pluck('nombre')->values();
    }

    private static function correosGestores(): array
    {
        return DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', 'talentohumano.gestor')->whereNull('users.deleted_at')
            ->pluck('users.email')->filter()->unique()->values()->all();
    }
}
