<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class CargoActivado extends Model
{
    protected $table = 'th_cargos_activados';

    protected $fillable = [
        'fase_id',
        'cargo_id',
        'numero_aprendices',
        'activado_por',
    ];

    protected $casts = [
        'numero_aprendices' => 'integer',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function activadoPor()
    {
        return $this->belongsTo(Person::class, 'activado_por');
    }
}
