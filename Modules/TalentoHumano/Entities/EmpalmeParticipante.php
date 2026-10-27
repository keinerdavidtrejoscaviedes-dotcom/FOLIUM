<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class EmpalmeParticipante extends Model
{
    protected $table = 'th_empalme_participantes';

    protected $fillable = [
        'empalme_id',
        'asignacion_id',
        'papel',
    ];

    public function empalme()
    {
        return $this->belongsTo(Empalme::class, 'empalme_id');
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'asignacion_id');
    }
}
