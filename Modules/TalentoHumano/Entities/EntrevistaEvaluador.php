<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class EntrevistaEvaluador extends Model
{
    protected $table = 'th_entrevista_evaluadores';

    protected $fillable = [
        'entrevista_id',
        'person_id',
    ];

    public function entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'entrevista_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
