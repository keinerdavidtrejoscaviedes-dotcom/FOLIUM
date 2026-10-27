<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThFase extends Model
{
    use SoftDeletes;

    protected $table = 'th_fases';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'estado', // planificada, activa, cerrada
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function fichas()
    {
        return $this->hasMany(ThFaseFicha::class, 'fase_id');
    }

    public function cargosActivados()
    {
        return $this->hasMany(ThCargo::class, 'fase_id');
    }

    public function convocatorias()
    {
        return $this->hasMany(ThConvocatoria::class, 'fase_id');
    }

    public function empalmes()
    {
        return $this->hasMany(ThEmpalme::class, 'fase_id');
    }
}
