<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class CronogramaEtapa extends Model
{
    protected $table = 'th_cronograma_etapas';

    protected $fillable = [
        'cronograma_id',
        'etapa',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function cronograma()
    {
        return $this->belongsTo(Cronograma::class, 'cronograma_id');
    }
}
