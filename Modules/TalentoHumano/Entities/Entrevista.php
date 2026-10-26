<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Person;

class Entrevista extends Model
{
    use SoftDeletes;

    protected $table = 'th_entrevistas';

    protected $fillable = [
        'convocatoria_id',
        'tipo',
        'modalidad',
        'fecha_hora',
        'lugar',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class, 'convocatoria_id');
    }

    public function aspirantes()
    {
        return $this->hasMany(EntrevistaAspirante::class, 'entrevista_id');
    }

    public function postulaciones()
    {
        return $this->belongsToMany(Postulacion::class, 'th_entrevista_aspirantes', 'entrevista_id', 'postulacion_id');
    }

    public function evaluadores()
    {
        return $this->hasMany(EntrevistaEvaluador::class, 'entrevista_id');
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'th_entrevista_evaluadores', 'entrevista_id', 'person_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'entrevista_id');
    }
}
