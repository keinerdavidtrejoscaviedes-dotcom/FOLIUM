<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Calificacion extends Model
{
    protected $table = 'th_calificaciones';

    protected $fillable = [
        'entrevista_id',
        'postulacion_id',
        'evaluador_id',
        'cuestionario_id',
        'puntaje',
        'concepto',
        'observaciones',
        'detalle',
    ];

    protected $casts = [
        'puntaje' => 'decimal:2',
        'detalle' => 'array',
    ];

    public function entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'entrevista_id');
    }

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(Person::class, 'evaluador_id');
    }

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class, 'cuestionario_id');
    }
}
