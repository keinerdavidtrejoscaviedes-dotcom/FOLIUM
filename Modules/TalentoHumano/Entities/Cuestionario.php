<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Person;

class Cuestionario extends Model
{
    use SoftDeletes;

    protected $table = 'th_cuestionarios';

    protected $fillable = [
        'tipo',
        'titulo',
        'autor_id',
        'version',
        'version_anterior_id',
        'estado',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function autor()
    {
        return $this->belongsTo(Person::class, 'autor_id');
    }

    public function versionAnterior()
    {
        return $this->belongsTo(self::class, 'version_anterior_id');
    }

    public function preguntas()
    {
        return $this->hasMany(CuestionarioPregunta::class, 'cuestionario_id')->orderBy('orden');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'cuestionario_id');
    }
}
