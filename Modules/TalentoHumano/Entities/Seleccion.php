<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Seleccion extends Model
{
    protected $table = 'th_selecciones';

    protected $fillable = [
        'convocatoria_cargo_id',
        'postulacion_id',
        'puntaje_total',
        'estado',
        'justificacion',
        'validada_por',
        'validada_con',
        'fecha_validacion',
    ];

    protected $casts = [
        'puntaje_total' => 'decimal:2',
        'fecha_validacion' => 'datetime',
    ];

    public function convocatoriaCargo()
    {
        return $this->belongsTo(ConvocatoriaCargo::class, 'convocatoria_cargo_id');
    }

    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class, 'postulacion_id');
    }

    public function validadaPor()
    {
        return $this->belongsTo(Person::class, 'validada_por');
    }
}
