<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThConvocatoriaCargo extends Model
{
    protected $table = 'th_convocatoria_cargos';

    protected $fillable = [
        'convocatoria_id',
        'cargo_id',
        'cupos',
        'documentos_obligatorios',
    ];

    protected $casts = [
        'cupos' => 'integer',
        'documentos_obligatorios' => 'array',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(ThConvocatoria::class, 'convocatoria_id');
    }

    public function cargo()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_id');
    }

    public function opcionesPostuladas()
    {
        return $this->hasMany(ThPostulacionOpcion::class, 'convocatoria_cargo_id');
    }
}
