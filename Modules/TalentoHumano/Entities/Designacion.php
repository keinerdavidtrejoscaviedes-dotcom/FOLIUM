<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\SICA\Entities\Person;
use Modules\SICA\Entities\Program;

/**
 * Función asignada a un instructor (RF-016). Se asigna a la función, no a la persona.
 */
class Designacion extends Model
{
    use SoftDeletes;

    public const TIPOS = [
        'lider_estrategia' => 'Líder de la estrategia',
        'instructor_th' => 'Instructor de Talento Humano',
        'instructor_tecnico' => 'Instructor técnico',
    ];

    public const DESCRIPCIONES = [
        'lider_estrategia' => 'Aprueba el cronograma, valida la selección y usa el submódulo del Instructor Líder.',
        'instructor_th' => 'Activa cargos con el Gestor, analiza hojas de vida y valida la selección.',
        'instructor_tecnico' => 'Realiza la entrevista técnica de su programa.',
    ];

    protected $table = 'th_designaciones';

    protected $fillable = ['person_id', 'tipo', 'program_id', 'fase_id', 'asignada_por', 'fecha_retiro', 'retirada_por', 'motivo_retiro'];

    protected $casts = ['fecha_retiro' => 'date'];

    public function persona()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function programa()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function asignadaPor()
    {
        return $this->belongsTo(Person::class, 'asignada_por');
    }

    public function retiradaPor()
    {
        return $this->belongsTo(Person::class, 'retirada_por');
    }

    public function scopeVigentes($query)
    {
        return $query->whereNull('fecha_retiro');
    }

    public function scopeRetiradas($query)
    {
        return $query->whereNotNull('fecha_retiro');
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo] . ($this->programa ? ' · ' . $this->programa->name : '');
    }

    /**
     * Pendientes del instructor en procesos de selección en curso que impiden retirar la designación (RN-034).
     *
     * @return array<int, array{texto: string, cantidad: int}>
     */
    public function pendientes(): array
    {
        $convocatorias = DB::table('th_convocatorias')
            ->join('th_fases', 'th_fases.id', '=', 'th_convocatorias.fase_id')
            ->whereNull('th_convocatorias.deleted_at')
            ->where('th_fases.estado', '!=', 'cerrada')
            ->where('th_convocatorias.estado', '!=', 'finalizada')
            ->pluck('th_convocatorias.id');
        $postulaciones = DB::table('th_postulaciones')->whereNull('deleted_at')->whereIn('convocatoria_id', $convocatorias);

        $pendientes = match ($this->tipo) {
            'lider_estrategia' => [
                'Cronogramas por aprobar' => DB::table('th_cronogramas')->join('th_fases', 'th_fases.id', '=', 'th_cronogramas.fase_id')
                    ->where('th_fases.estado', '!=', 'cerrada')->where('th_cronogramas.estado', 'enviado')->count(),
                'Propuestas de selección por validar' => DB::table('th_selecciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->where('estado', 'propuesta')->count(),
                'Comité de reubicación en curso (aprendices por reubicar)' => (clone $postulaciones)->where('estado', 'no_seleccionado')->count(),
            ],
            'instructor_th' => [
                'Hojas de vida sin analizar' => (clone $postulaciones)->whereNotExists(fn ($q) => $q->from('th_analisis_hv')->whereColumn('th_analisis_hv.postulacion_id', 'th_postulaciones.id'))->count(),
                'Propuestas de selección por validar' => DB::table('th_selecciones')->whereIn('postulacion_id', (clone $postulaciones)->select('id'))->where('estado', 'propuesta')->count(),
            ],
            default => [],
        };

        // Entrevistas asignadas al instructor que aún no ha calificado
        $pendientes['Entrevistas sin calificar'] = DB::table('th_entrevista_evaluadores as ev')
            ->join('th_entrevistas as e', 'e.id', '=', 'ev.entrevista_id')
            ->join('th_entrevista_aspirantes as a', 'a.entrevista_id', '=', 'e.id')
            ->leftJoin('th_calificaciones as c', fn ($j) => $j->on('c.entrevista_id', '=', 'e.id')->on('c.postulacion_id', '=', 'a.postulacion_id')->on('c.evaluador_id', '=', 'ev.person_id'))
            ->where('ev.person_id', $this->person_id)
            ->whereNull('e.deleted_at')
            ->whereIn('e.convocatoria_id', $convocatorias)
            ->when($this->tipo === 'instructor_tecnico', fn ($q) => $q->where('e.tipo', 'tecnica'))
            ->whereNull('c.id')
            ->count();

        return collect($pendientes)->filter()->map(fn ($cantidad, $texto) => ['texto' => $texto, 'cantidad' => $cantidad])->values()->all();
    }
}
