<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThSatisfaccionEmpalme extends Model
{
    protected $table = 'th_satisfaccion_empalme';

    protected $fillable = [
        'empalme_id',
        'person_id',
        'respuestas', // JSON con las 5 preguntas y observaciones
    ];

    protected $casts = [
        'respuestas' => 'array',
    ];

    public function empalme()
    {
        return $this->belongsTo(ThEmpalme::class, 'empalme_id');
    }
}
