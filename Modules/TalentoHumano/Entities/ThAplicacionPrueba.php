<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThAplicacionPrueba extends Model
{
    protected $table = 'th_aplicaciones_prueba';

    protected $fillable = [
        'postulacion_id',
        'cuestionario_id',
        'estado', // habilitada, en_curso, presentada, no_evaluado
        'fecha_limite',
        'puntaje',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_limite' => 'datetime',
        'fecha_presentacion' => 'datetime',
        'puntaje' => 'decimal:2',
    ];

    public function postulacion()
    {
        return $this->belongsTo(ThPostulacion::class, 'postulacion_id');
    }

    public function respuestas()
    {
        return $this->hasMany(ThRespuestaPrueba::class, 'aplicacion_id');
    }
}
