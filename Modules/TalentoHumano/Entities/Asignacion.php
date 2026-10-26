<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Asignacion extends Model
{
    protected $table = 'th_asignaciones';

    protected $fillable = [
        'person_id',
        'cargo_id',
        'fase_id',
        'estado',
        'origen',
        'carga_inicial_id',
        'fecha_inicio',
        'fecha_fin',
        'justificacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }
}
