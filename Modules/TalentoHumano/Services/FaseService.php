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
        // Una ficha sin programa también se lista, para que el Gestor lo registre antes de seleccionarla (HU-018 esc. 3)
        return $this->consultaFichas()
            ->where(fn ($q) => $q->whereNull('p.id')->orWhere(fn ($t) => $t->where('p.program_type', 'Tecnólogo')->where('p.modality', 'Presencial')))
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
     * Fichas de la fase a las que les falta el programa o el trimestre: impiden la activación de cargos (HU-018 esc. 4 y 5).
     */
    public function fichasIncompletas(int $faseId): Collection
    {
        return $this->fichasDeLaFase($faseId)->filter(fn ($f) => $f->sin_programa || $f->sin_trimestre)->values();
    }

    /**
     * Programas tecnólogo presenciales del centro, para registrar el de una ficha que llegó sin él (HU-018 esc. 3).
     */
    public function programasTecnologo(): Collection
    {
        return DB::table('programs')->where('program_type', 'Tecnólogo')->where('modality', 'Presencial')
            ->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'quarter_number']);
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
        $convocatoria = $this->convocatoria($faseId);
        $postulaciones = DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria?->id)->whereNull('deleted_at');

        return [
            'fichas' => $fichas->count(),
            'aprendices' => $fichas->sum('aprendices'),
            'cargos' => (clone $cargos)->count(),
            'cupos' => (int) (clone $cargos)->sum('numero_aprendices'),
            'cronograma' => $cronograma?->estado,
            // Propuesta de modificación de un cronograma aprobado (enviado, borrador o rechazado)
            'cronograma_modificacion' => ($ultimo = $this->ultimoCronograma($faseId)) && $cronograma && $ultimo->id !== $cronograma->id ? $ultimo->estado : null,
            'convocatoria' => $convocatoria?->estado,
            'convocatoria_publicada' => $this->convocatoriaPublicada($faseId),
            // Proceso de selección después de publicar la convocatoria (HU-025 a HU-028)
            'convocatoria_cerrada' => $convocatoria && $convocatoria->estado !== 'borrador' && today()->gt($convocatoria->fecha_cierre),
            'postulantes' => (clone $postulaciones)->count(),
            'pruebas_habilitadas' => DB::table('th_aplicaciones_prueba')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->count(),
            'pruebas_presentadas' => DB::table('th_aplicaciones_prueba')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->where('estado', 'presentada')->count(),
            'evaluadores' => DB::table('th_convocatoria_evaluadores')->where('convocatoria_id', $convocatoria?->id)->whereNull('retirado_en')->count(),
            'entrevistas' => DB::table('th_entrevistas')->where('convocatoria_id', $convocatoria?->id)->whereNull('deleted_at')->count(),
            'acta_publicada' => DB::table('th_actas')->where('fase_id', $faseId)->where('tipo', 'seleccionados')->where('estado', 'publicada')->exists(),
            'empalmes' => DB::table('th_empalmes')->where('fase_id', $faseId)->count(),
            'empalmes_cerrados' => DB::table('th_empalmes')->where('fase_id', $faseId)->where('estado', 'cerrado')->count(),
        ];
    }

    /**
     * Convocatoria de la fase (una por fase).
     */
    public function convocatoria(int $faseId): ?object
    {
        return DB::table('th_convocatorias')
            ->where('fase_id', $faseId)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Cronograma vigente de la fase: el último aprobado; si aún no hay uno aprobado, el último registrado.
     * Mientras una modificación está en aprobación siguen vigentes las fechas aprobadas (HU-020B esc. 8).
     */
    public function cronograma(int $faseId): ?object
    {
        return $this->cronogramaAprobado($faseId) ?? $this->ultimoCronograma($faseId);
    }

    public function cronogramaAprobado(int $faseId): ?object
    {
        return DB::table('th_cronogramas')
            ->where('fase_id', $faseId)
            ->where('estado', 'aprobado')
            ->orderByDesc('fecha_aprobacion')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Último cronograma registrado (puede ser un borrador, una propuesta de modificación o uno rechazado).
     */
    public function ultimoCronograma(int $faseId): ?object
    {
        return DB::table('th_cronogramas')
            ->where('fase_id', $faseId)
            ->orderByDesc('id')
            ->first();
    }

    private function consultaFichas()
    {
        // El programa y el trimestre registrados a mano por el Gestor completan los que faltan en SICEFA (HU-018)
        return DB::table('courses as c')
            ->leftJoin('th_fichas_datos as fd', 'fd.course_id', '=', 'c.id')
            ->leftJoin('programs as p', 'p.id', '=', DB::raw('COALESCE(c.program_id, fd.program_id)'))
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'c.code', 'c.start_date', 'c.end_date', 'c.status', 'fd.trimestre as trimestre_manual',
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
        // Sin fecha de inicio no se puede calcular: vale el trimestre que registró el Gestor (HU-018 esc. 1)
        $ficha->trimestre = $ficha->start_date ? $this->trimestre($ficha->start_date, $ficha->quarter_number) : (int) $ficha->trimestre_manual;
        $ficha->sin_trimestre = !$ficha->start_date && !$ficha->trimestre_manual;
        $ficha->sin_programa = !$ficha->program_id;

        return $ficha;
    }
}
