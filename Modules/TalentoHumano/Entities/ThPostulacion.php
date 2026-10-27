<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThPostulacion extends Model
{
    use SoftDeletes;

    protected $table = 'th_postulaciones';

    protected $fillable = [
        'convocatoria_id',
        'person_id',
        'course_id',
        'municipality_id',
        'vereda_ruta',
        'estado', // postulado, no_evaluado, seleccionado, reubicado, no_seleccionado
        'puntaje_total',
    ];

    protected $casts = [
        'puntaje_total' => 'decimal:2',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(ThConvocatoria::class, 'convocatoria_id');
    }

    public function opciones()
    {
        return $this->hasMany(ThPostulacionOpcion::class, 'postulacion_id')->orderBy('preferencia');
    }

    public function documentos()
    {
        return $this->hasMany(ThPostulacionDocumento::class, 'postulacion_id');
    }

    public function aplicacionPrueba()
    {
        return $this->hasOne(ThAplicacionPrueba::class, 'postulacion_id');
    }
}
