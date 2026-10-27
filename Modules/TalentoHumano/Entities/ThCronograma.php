<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThCronograma extends Model
{
    protected $table = 'th_cronogramas';

    protected $fillable = [
        'fase_id',
        'estado', // borrador, en_aprobacion, aprobado, rechazado
        'fecha_aprobacion',
        'aprobado_por',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    public function fase()
    {
        return $this->belongsTo(ThFase::class, 'fase_id');
    }

    public function etapas()
    {
        return $this->hasMany(ThCronogramaEtapa::class, 'cronograma_id')->orderBy('orden');
    }
}
