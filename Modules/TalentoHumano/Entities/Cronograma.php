<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

class Cronograma extends Model
{
    protected $table = 'th_cronogramas';

    protected $fillable = [
        'fase_id',
        'estado',
        'motivo_rechazo',
        'enviado_por',
        'aprobado_por',
        'fecha_aprobacion',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function etapas()
    {
        return $this->hasMany(CronogramaEtapa::class, 'cronograma_id');
    }

    public function enviadoPor()
    {
        return $this->belongsTo(Person::class, 'enviado_por');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(Person::class, 'aprobado_por');
    }
}
