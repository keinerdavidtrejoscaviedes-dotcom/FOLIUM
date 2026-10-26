<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class CuestionarioPregunta extends Model
{
    protected $table = 'th_cuestionario_preguntas';

    protected $fillable = [
        'cuestionario_id',
        'orden',
        'enunciado',
        'tipo',
        'opciones',
        'imagen',
        'puntaje_maximo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'opciones' => 'array',
        'puntaje_maximo' => 'decimal:2',
    ];

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class, 'cuestionario_id');
    }
}
