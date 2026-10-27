<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ConvocatoriaCargo extends Model
{
    protected $table = 'th_convocatoria_cargos';

    protected $fillable = [
        'convocatoria_id',
        'cargo_activado_id',
        'cupos',
        'requisitos',
        'documentos',
        'programas_extra',
        'justificacion_excepcion',
    ];

    protected $casts = [
        'cupos' => 'integer',
        'documentos' => 'array',
        'programas_extra' => 'array',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class, 'convocatoria_id');
    }

    public function cargoActivado()
    {
        return $this->belongsTo(CargoActivado::class, 'cargo_activado_id');
    }
}
