<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThPostulacionOpcion extends Model
{
    protected $table = 'th_postulacion_opciones';

    protected $fillable = [
        'postulacion_id',
        'convocatoria_cargo_id',
        'preferencia', // 1, 2, 3
        'propuesta_trabajo',
    ];

    protected $casts = [
        'preferencia' => 'integer',
    ];

    public function postulacion()
    {
        return $this->belongsTo(ThPostulacion::class, 'postulacion_id');
    }

    public function convocatoriaCargo()
    {
        return $this->belongsTo(ThConvocatoriaCargo::class, 'convocatoria_cargo_id');
    }
}
