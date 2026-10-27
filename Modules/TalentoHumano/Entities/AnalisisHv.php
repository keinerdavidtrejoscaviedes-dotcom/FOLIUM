<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class AnalisisHv extends Model
{
    protected $table = 'th_analisis_hv';

    protected $fillable = [
        'postulacion_id',
        'evaluador_id',
        'formacion',
        'habilidades',
        'afinidad',
        'observaciones',
    ];

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(Person::class, 'evaluador_id');
    }
}
