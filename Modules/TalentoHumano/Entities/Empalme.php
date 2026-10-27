<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Empalme extends Model
{
    protected $table = 'th_empalmes';

    protected $fillable = [
        'fase_id',
        'cargo_id',
        'cargo_receptor_id',
        'tipo',
        'estado',
        'visita_confirmada',
        'visto_bueno_por',
        'fecha_visto_bueno',
    ];

    protected $casts = [
        'visita_confirmada' => 'boolean',
        'fecha_visto_bueno' => 'datetime',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function cargoReceptor()
    {
        return $this->belongsTo(Cargo::class, 'cargo_receptor_id');
    }

    public function vistoBuenoPor()
    {
        return $this->belongsTo(Person::class, 'visto_bueno_por');
    }

    public function participantes()
    {
        return $this->hasMany(EmpalmeParticipante::class, 'empalme_id');
    }

    public function inventarios()
    {
        return $this->hasMany(InventarioEmpalme::class, 'empalme_id');
    }

    public function acta()
    {
        return $this->hasOne(Acta::class, 'empalme_id');
    }

    public function salientes()
    {
        return $this->participantes()->where('papel', 'saliente');
    }

    public function receptores()
    {
        return $this->participantes()->where('papel', 'receptor');
    }
}
