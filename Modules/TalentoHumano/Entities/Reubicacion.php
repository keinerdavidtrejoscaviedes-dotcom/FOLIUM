<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Reubicacion extends Model
{
    protected $table = 'th_reubicaciones';

    protected $fillable = [
        'postulacion_id',
        'cargo_activado_id',
        'participantes',
        'justificacion',
        'registrada_por',
    ];

    protected $casts = [
        'participantes' => 'array',
    ];

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function cargoActivado()
    {
        return $this->belongsTo(CargoActivado::class, 'cargo_activado_id');
    }

    public function registradaPor()
    {
        return $this->belongsTo(Person::class, 'registrada_por');
    }
}
