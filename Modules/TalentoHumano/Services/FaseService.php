<?php

namespace Modules\TalentoHumano\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas compartidas de la preparación de una fase: fichas (RF-018), cargos activados (RF-001)
 * y cronograma (RF-003).
 *
 * Mientras no exista la API de SICEFA (SRS 5.1), las fichas y los aprendices se leen de las tablas
 * del ERP (courses, programs, apprentices), como lo permite la sección 6 del SRS.
 */
class FaseService
{
    /** Estados de SICEFA de un aprendiz que sigue vinculado a su ficha. */
    public const ESTADOS_APRENDIZ_ACTIVO = ['EN FORMACIÓN', 'INDUCCIÓN', 'CONDICIONADO'];

    public function buscar(int $id): object
    {
        $fase = DB::table('th_fases')->whereNull('deleted_at')->find($id);

        abort_unless($fase, 404);

        return $fase;
    }

    /**
     * Con la convocatoria publicada ya no se pueden cambiar las fichas ni los cargos (HU-017 y HU-019).
     */
    public function convocatoriaPublicada(int $faseId): bool
    {
        return DB::table('th_convocatorias')
            ->where('fase_id', $faseId)
            ->whereIn('estado', ['publicada', 'cerrada', 'finalizada'])
            ->exists();
    }

    /**
     * Fichas que se pueden seleccionar para una fase: nivel tecnólogo, modalidad presencial y activas (RN-038).
     */
    public function fichasDisponibles(): Collection
    {
        return $this->consultaFichas()
            ->where('p.program_type', 'Tecnólogo')
            ->where('p.modality', 'Presencial')
            ->where('c.status', 'Activo')
            ->orderBy('p.name')
            ->orderBy('c.code')
            ->get()
            ->map(fn ($ficha) => $this->conTrimestre($ficha));
    }

    /**
     * Fichas asociadas a la fase.
     */
    public function fichasDeLaFase(int $faseId): Collection
    {
        return $this->consultaFichas()
            ->join('th_fase_fichas as ff', 'ff.course_id', '=', 'c.id')
            ->where('ff.fase_id', $faseId)
            ->addSelect('ff.id as ficha_fase_id')
            ->orderBy('p.name')
            ->get()
            ->map(fn ($ficha) => $this->conTrimestre($ficha));
    }

    /**
     * Trimestre en curso de una ficha, calculado desde su fecha de inicio (3 meses por trimestre),
     * sin pasar del número de trimestres del programa. 0 si la ficha aún no ha iniciado.
     */
    public function trimestre(?string $fechaInicio, ?int $trimestresPrograma): int
    {
        if (!$fechaInicio || Carbon::parse($fechaInicio)->isFuture()) {
            return 0;
        }

        $trimestre = intdiv((int) Carbon::parse($fechaInicio)->diffInMonths(now()), 3) + 1;

        return $trimestresPrograma ? min($trimestre, $trimestresPrograma) : $trimestre;
    }

    /**
     * Avance de la preparación de la fase, para la página de la fase y para bloquear pasos (HU-021).
     */
    public function resumen(int $faseId): array
    {
        $fichas = $this->fichasDeLaFase($faseId);
        $cargos = DB::table('th_cargos_activados')->where('fase_id', $faseId);
        $cronograma = $this->cronograma($faseId);

        return [
            'fichas' => $fichas->count(),
            'aprendices' => $fichas->sum('aprendices'),
            'cargos' => (clone $cargos)->count(),
            'cupos' => (int) (clone $cargos)->sum('numero_aprendices'),
            'cronograma' => $cronograma?->estado,
            'convocatoria_publicada' => $this->convocatoriaPublicada($faseId),
        ];
    }

    /**
     * Último cronograma de la fase (puede haber varios si uno fue rechazado y se rehízo).
     */
    public function cronograma(int $faseId): ?object
    {
        return DB::table('th_cronogramas')
            ->where('fase_id', $faseId)
            ->orderByDesc('id')
            ->first();
    }

    private function consultaFichas()
    {
        return DB::table('courses as c')
            ->join('programs as p', 'p.id', '=', 'c.program_id')
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'c.code', 'c.start_date', 'c.end_date', 'c.status',
                'p.id as program_id', 'p.name as programa', 'p.sofia_code', 'p.quarter_number',
                'p.program_type', 'p.modality'
            )
            ->addSelect(['aprendices' => DB::table('apprentices')
                ->selectRaw('count(*)')
                ->whereColumn('course_id', 'c.id')
                ->whereNull('deleted_at')
                ->whereIn('apprentice_status', self::ESTADOS_APRENDIZ_ACTIVO)]);
    }

    private function conTrimestre(object $ficha): object
    {
        $ficha->trimestre = $this->trimestre($ficha->start_date, $ficha->quarter_number);

        return $ficha;
    }
}
