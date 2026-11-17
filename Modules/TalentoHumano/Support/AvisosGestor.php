<?php

namespace Modules\TalentoHumano\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Http\Controllers\CronogramaController;

/**
 * Avisos al Gestor de TH: paso bloqueado por un prerrequisito (HU-021 esc. 1), etapas del cronograma por vencer con
 * pendientes (HU-021 esc. 4) y aprendices sin vincular al iniciar la fase (HU-031A esc. 2).
 * Las alertas no se repiten mientras sigan iguales y sin leer (Alerta::prerrequisito).
 */
class AvisosGestor
{
    /** Días antes del fin de una etapa en que se avisa (HU-021 esc. 4). */
    public const DIAS_AVISO = 2;

    /**
     * Paso bloqueado: queda en el historial de alertas de la fase y se avisa por correo al Gestor, con copia al Administrador.
     */
    public static function prerrequisito(Fase $fase, string $etapa, string $faltante): void
    {
        $alerta = Alerta::prerrequisito($fase->id, $etapa, [rtrim($faltante, '.')], route('talentohumano.fases.show', $fase->id));

        if ($alerta) {
            Correo::enviar(array_merge(self::correosGestores(), AccesoTH::correosAdministradores()->all()),
                "FOLIUM: {$etapa} de la {$fase->nombre()} tiene un paso pendiente",
                "FOLIUM · Talento Humano\n\nNo se pudo avanzar en «{$etapa}» de la {$fase->nombre()} porque: {$faltante}\n\n"
                . 'Revise el estado de la fase en ' . route('talentohumano.fases.show', $fase->id));
        }
    }

    /**
     * Avisos del panel del Gestor para la fase actual: [titulo, texto, enlace].
     */
    public static function delPanel(): array
    {
        $fase = Fase::actual();
        if (!$fase) {
            return [];
        }

        $avisos = [];

        // HU-047 esc. 3 y HU-025B: postulantes que dejaron vencer la prueba quedan «No evaluado» sin esperar a que entren
        if ($convocatoria = ProcesoSeleccion::convocatoria($fase)) {
            PruebaVencida::sincronizar($convocatoria);
            $noEvaluados = PruebaVencida::noEvaluados($convocatoria);
            if ($noEvaluados->isNotEmpty()) {
                $avisos[] = ["Postulantes «No evaluado» ({$noEvaluados->count()})",
                    'No presentaron la prueba psicotécnica dentro del plazo y su entrevista queda bloqueada: ' . $noEvaluados->take(8)->implode(', ') . ($noEvaluados->count() > 8 ? '…' : ''),
                    route('talentohumano.fases.prueba.index', $fase->id)];
            }
        }

        foreach (self::plazosPorVencer($fase) as [$etapa, $fin, $pendientes]) {
            $titulo = "Plazo de «{$etapa}»: vence el " . $fin->format('d/m/Y');
            $texto = count($pendientes) . ' pendientes: ' . implode(', ', array_slice($pendientes, 0, 8)) . (count($pendientes) > 8 ? '…' : '');
            $avisos[] = [$titulo, $texto, route('talentohumano.fases.show', $fase->id)];

            // Se envía también por correo, una sola vez mientras la alerta siga igual y sin leer
            if (Alerta::prerrequisito($fase->id, "Plazo: {$etapa}", $pendientes, route('talentohumano.fases.show', $fase->id))) {
                Correo::enviar(self::correosGestores(), "FOLIUM: {$titulo}", "FOLIUM · Talento Humano\n\n{$titulo}\n\n- " . implode("\n- ", $pendientes));
            }
        }

        // HU-031A esc. 2: llegó la fecha de inicio de la fase y hay aprendices sin empalme cerrado
        if ($fase->fecha_inicio && !$fase->fecha_inicio->isFuture()) {
            $sinVincular = DB::table('th_asignaciones as a')->join('people as p', 'p.id', '=', 'a.person_id')->join('th_cargos as c', 'c.id', '=', 'a.cargo_id')
                ->where('a.fase_id', $fase->id)->where('a.estado', 'asignada')->whereIn('a.origen', ['acta', 'reubicacion'])
                ->selectRaw("CONCAT(TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name)), ' (', c.nombre, ')') as nombre")->pluck('nombre')->all();
            if ($sinVincular) {
                $avisos[] = ['Aprendices pendientes de vinculación (' . count($sinVincular) . ')',
                    'La fase ya inició y no han cerrado su empalme: ' . implode(', ', array_slice($sinVincular, 0, 8)) . (count($sinVincular) > 8 ? '…' : ''),
                    route('talentohumano.fases.empalmes.index', $fase->id)];
            }
        }

        return $avisos;
    }

    /**
     * Etapas del cronograma aprobado que terminan en los próximos días y tienen pendientes: [nombre, fin, pendientes].
     */
    private static function plazosPorVencer(Fase $fase): array
    {
        $convocatoria = ProcesoSeleccion::convocatoria($fase);
        $cronograma = DB::table('th_cronogramas')->where('fase_id', $fase->id)->where('estado', 'aprobado')->latest('id')->first();
        if (!$cronograma || !$convocatoria) {
            return [];
        }

        $resultado = [];
        $etapas = DB::table('th_cronograma_etapas')->where('cronograma_id', $cronograma->id)->get();
        foreach ($etapas as $etapa) {
            $fin = Carbon::parse($etapa->fecha_fin ?? $etapa->fecha_inicio);
            if ($fin->lt(today()) || $fin->gt(today()->addDays(self::DIAS_AVISO))) {
                continue;
            }

            $pendientes = self::pendientes($etapa->etapa, $convocatoria, $fase);
            if ($pendientes) {
                $resultado[] = [CronogramaController::ETAPAS[$etapa->etapa]['nombre'] ?? $etapa->etapa, $fin, $pendientes];
            }
        }

        return $resultado;
    }

    /**
     * Pendientes de cada etapa, por nombre (sin puntajes).
     */
    private static function pendientes(string $etapa, $convocatoria, Fase $fase): array
    {
        $postulantes = ProcesoSeleccion::postulantes($convocatoria);

        return match ($etapa) {
            'hojas_vida' => $postulantes->where('analizada', 0)->map(fn ($p) => "{$p->nombre}: sin análisis de hoja de vida")->values()->all(),
            'prueba_psicotecnica' => $postulantes->whereIn('prueba', ['habilitada', 'en_curso'])->map(fn ($p) => "{$p->nombre}: no ha presentado la prueba")->values()->all(),
            'entrevistas' => $postulantes->where('prueba', 'presentada')
                ->reject(fn ($p) => DB::table('th_entrevista_aspirantes as ea')->join('th_entrevistas as e', 'e.id', '=', 'ea.entrevista_id')
                    ->where('ea.postulacion_id', $p->id)->whereNull('e.deleted_at')->distinct()->count('e.tipo') >= 2)
                ->map(fn ($p) => "{$p->nombre}: le falta alguna entrevista")->values()->all(),
            'empalme' => DB::table('th_empalmes as e')->join('th_cargos as c', 'c.id', '=', 'e.cargo_id')
                ->where('e.fase_id', $fase->id)->where('e.estado', '!=', 'cerrado')->pluck('c.nombre')
                ->map(fn ($c) => "Empalme de {$c}: sin visto bueno")->all(),
            default => [],
        };
    }

    private static function correosGestores(): array
    {
        return User::whereHas('roles', fn ($q) => $q->where('slug', 'talentohumano.gestor'))->pluck('email')->filter()->unique()->values()->all();
    }
}
