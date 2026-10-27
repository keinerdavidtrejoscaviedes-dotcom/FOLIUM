<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThConvocatoria extends Model
{
    protected $table = 'th_convocatorias';

    protected $fillable = [
        'fase_id',
        'cronograma_id',
        'titulo',
        'fecha_apertura',
        'fecha_cierre',
        'estado', // borrador, publicada, cerrada
        'umbral_aprobacion',
        'politica_umbral', // mayor_puntaje, declarar_vacante
        'regla_perfil_incompatible', // advertir, impedir
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'umbral_aprobacion' => 'decimal:2',
    ];

    public function fase()
    {
        return $this->belongsTo(ThFase::class, 'fase_id');
    }

    public function cronograma()
    {
        return $this->belongsTo(ThCronograma::class, 'cronograma_id');
    }

    public function cargos()
    {
        return $this->hasMany(ThConvocatoriaCargo::class, 'convocatoria_id');
    }

    public function postulaciones()
    {
        return $this->hasMany(ThPostulacion::class, 'convocatoria_id');
    }
}
