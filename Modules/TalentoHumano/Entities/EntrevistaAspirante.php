<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class EntrevistaAspirante extends Model
{
    protected $table = 'th_entrevista_aspirantes';

    protected $fillable = [
        'entrevista_id',
        'postulacion_id',
    ];

    public function entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'entrevista_id');
    }

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }
}
