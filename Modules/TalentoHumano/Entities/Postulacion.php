<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Person;
use Modules\SICA\Entities\Course;
use Modules\SICA\Entities\Municipality;

class Postulacion extends Model
{
    use SoftDeletes;

    protected $table = 'th_postulaciones';

    protected $fillable = [
        'convocatoria_id',
        'person_id',
        'course_id',
        'municipality_id',
        'vereda_ruta',
        'estado',
        'puntaje_total',
    ];

    protected $casts = [
        'puntaje_total' => 'decimal:2',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class, 'convocatoria_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

    public function opciones()
    {
        return $this->hasMany(PostulacionOpcion::class, 'postulacion_id')->orderBy('preferencia');
    }

    public function documentos()
    {
        return $this->hasMany(PostulacionDocumento::class, 'postulacion_id');
    }

    public function analisisHv()
    {
        return $this->hasOne(AnalisisHv::class, 'postulacion_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(\Modules\TalentoHumano\Entities\Calificacion::class, 'postulacion_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        if (!$this->person) {
            return 'Aspirante';
        }
        $nombres = trim("{$this->person->first_name} {$this->person->first_last_name} {$this->person->second_last_name}");
        return mb_convert_case($nombres, MB_CASE_TITLE, 'UTF-8');
    }

    public function getInicialesAttribute(): string
    {
        if (!$this->person) {
            return 'AS';
        }
        $i1 = mb_substr(trim($this->person->first_name), 0, 1);
        $i2 = mb_substr(trim($this->person->first_last_name), 0, 1);
        return mb_strtoupper($i1 . $i2);
    }
}
