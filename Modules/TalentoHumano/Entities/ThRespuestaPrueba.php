<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThRespuestaPrueba extends Model
{
    protected $table = 'th_respuestas_prueba';

    protected $fillable = [
        'aplicacion_id',
        'pregunta_id',
        'respuesta',
        'puntaje',
    ];

    protected $casts = [
        'puntaje' => 'decimal:2',
    ];

    public function aplicacion()
    {
        return $this->belongsTo(ThAplicacionPrueba::class, 'aplicacion_id');
    }
}
