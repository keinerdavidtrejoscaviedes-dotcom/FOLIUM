<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThAsignacion extends Model
{
    protected $table = 'th_asignaciones';

    protected $fillable = [
        'person_id',
        'cargo_id',
        'fase_id',
        'estado', // asignada, vigente, suspendida, finalizada
        'origen', // acta, reubicacion, carga_inicial
        'carga_inicial_id',
        'fecha_inicio',
        'fecha_fin',
        'justificacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function cargo()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_id');
    }

    public function fase()
    {
        return $this->belongsTo(ThFase::class, 'fase_id');
    }
}
