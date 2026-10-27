<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Person;

/**
 * Carga masiva de asignaciones iniciales desde Excel (HU-013, RN-032).
 */
class CargaInicial extends Model
{
    protected $table = 'th_cargas_iniciales';

    protected $fillable = ['fase_id', 'archivo', 'filas_correctas', 'filas_con_error', 'errores', 'justificacion', 'cargada_por'];

    protected $casts = ['errores' => 'array'];

    public function fase()
    {
        return $this->belongsTo(Fase::class);
    }

    public function cargadaPor()
    {
        return $this->belongsTo(Person::class, 'cargada_por');
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class);
    }
}
