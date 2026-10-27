<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Acta extends Model
{
    protected $table = 'th_actas';

    protected $fillable = [
        'tipo',
        'fase_id',
        'plantilla_id',
        'empalme_id',
        'contenido',
        'estado',
        'publicada_por',
        'fecha_publicacion',
    ];

    protected $casts = [
        'contenido' => 'array',
        'fecha_publicacion' => 'datetime',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function publicadaPor()
    {
        return $this->belongsTo(Person::class, 'publicada_por');
    }

    public function empalme()
    {
        return $this->belongsTo(Empalme::class, 'empalme_id');
    }

    public function firmas()
    {
        return $this->hasMany(Firma::class, 'acta_id');
    }
}
