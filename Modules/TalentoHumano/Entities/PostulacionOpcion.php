<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class PostulacionOpcion extends Model
{
    protected $table = 'th_postulacion_opciones';

    protected $fillable = [
        'postulacion_id',
        'convocatoria_cargo_id',
        'preferencia',
        'propuesta_trabajo',
    ];

    protected $casts = [
        'preferencia' => 'integer',
    ];

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function convocatoriaCargo()
    {
        return $this->belongsTo(ConvocatoriaCargo::class, 'convocatoria_cargo_id');
    }

    public function getCargoNombreAttribute(): string
    {
        return $this->convocatoriaCargo?->cargoActivado?->cargo?->nombre ?? 'Cargo';
    }
}
