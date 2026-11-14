<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Person;

class Convocatoria extends Model
{
    use SoftDeletes;

    protected $table = 'th_convocatorias';

    protected $fillable = [
        'fase_id',
        'cronograma_id',
        'titulo',
        'fecha_apertura',
        'fecha_cierre',
        'umbral',
        'politica_sin_aptos',
        'estado',
        'publicada_por',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'umbral' => 'decimal:2',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function cronograma()
    {
        return $this->belongsTo(Cronograma::class, 'cronograma_id');
    }

    public function publicadaPor()
    {
        return $this->belongsTo(Person::class, 'publicada_por');
    }

    public function convocatoriaCargos()
    {
        return $this->hasMany(ConvocatoriaCargo::class, 'convocatoria_id');
    }

    public function evaluadores()
    {
        return $this->hasMany(ConvocatoriaEvaluador::class, 'convocatoria_id');
    }

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class, 'convocatoria_id');
    }

    /**
     * La postulación terminó: pasó la fecha de cierre (nadie cambia el estado a "cerrada" a mano; RN-004).
     */
    public function estaCerrada(): bool
    {
        return $this->estado !== 'borrador' && $this->fecha_cierre && today()->gt($this->fecha_cierre);
    }
}
