<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Person;
use Modules\SICA\Entities\Program;

class Designacion extends Model
{
    use SoftDeletes;

    protected $table = 'th_designaciones';

    protected $fillable = [
        'person_id',
        'tipo',
        'program_id',
        'fase_id',
        'asignada_por',
        'fecha_retiro',
    ];

    protected $casts = [
        'fecha_retiro' => 'date',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function asignadaPor()
    {
        return $this->belongsTo(Person::class, 'asignada_por');
    }
}
