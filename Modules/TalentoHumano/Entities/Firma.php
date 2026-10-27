<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Firma extends Model
{
    protected $table = 'th_firmas';

    protected $fillable = [
        'acta_id',
        'person_id',
        'rol_firmante',
        'fecha_firma',
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
    ];

    public function acta()
    {
        return $this->belongsTo(Acta::class, 'acta_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
