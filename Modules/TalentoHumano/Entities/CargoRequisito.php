<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Requisito de un cargo que el sistema no puede verificar (HU-005, esc. 6).
 */
class CargoRequisito extends Model
{
    protected $table = 'th_cargo_requisitos';

    protected $fillable = ['cargo_id', 'descripcion', 'exige_soporte'];

    protected $casts = ['exige_soporte' => 'boolean'];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }
}
