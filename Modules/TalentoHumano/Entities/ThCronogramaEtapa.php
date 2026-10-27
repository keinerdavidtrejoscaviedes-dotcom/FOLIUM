<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThCronogramaEtapa extends Model
{
    protected $table = 'th_cronograma_etapas';

    protected $fillable = [
        'cronograma_id',
        'etapa',
        'fecha_inicio',
        'fecha_fin',
        'orden',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'orden' => 'integer',
    ];

    public function cronograma()
    {
        return $this->belongsTo(ThCronograma::class, 'cronograma_id');
    }
}
