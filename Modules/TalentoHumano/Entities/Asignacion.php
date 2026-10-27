<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;
use Modules\SICA\Entities\ProductiveUnit;

/**
 * Asignación de un aprendiz a un cargo durante una fase (RF-015). Nunca se elimina: se finaliza o se suspende.
 */
class Asignacion extends Model
{
    public const ESTADOS = ['asignada' => 'Asignada', 'vigente' => 'Vigente', 'suspendida' => 'Suspendida', 'finalizada' => 'Finalizada'];

    public const ORIGENES = ['acta' => 'Acta de seleccionados', 'reubicacion' => 'Reubicación', 'carga_inicial' => 'Carga inicial'];

    /** Estados que ocupan el cargo: un aprendiz no puede tener dos de estos en la misma fase (RN-029). */
    public const OCUPAN = ['asignada', 'vigente', 'suspendida'];

    /** Cargo cuya asignación vigente otorga el rol Gestor de Talento Humano (RN-067). */
    public const CARGO_GESTOR_TH = 'Gestor de Talento Humano';

    protected $table = 'th_asignaciones';

    protected $fillable = ['person_id', 'cargo_id', 'fase_id', 'estado', 'origen', 'carga_inicial_id', 'fecha_inicio', 'fecha_fin', 'justificacion'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function persona()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class);
    }

    public function cargaInicial()
    {
        return $this->belongsTo(CargaInicial::class);
    }

    public function unidades()
    {
        return $this->belongsToMany(ProductiveUnit::class, 'th_asignacion_unidades', 'asignacion_id', 'productive_unit_id')->withTimestamps();
    }

    public function esGestorTH(): bool
    {
        return $this->cargo?->nombre === self::CARGO_GESTOR_TH;
    }

    // Nombres que usa el código del proceso del Gestor (rama Maicol)

    public function person()
    {
        return $this->persona();
    }
}
