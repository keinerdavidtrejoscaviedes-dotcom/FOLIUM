<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Course;
use Modules\SICA\Entities\Person;

class FaseFicha extends Model
{
    protected $table = 'th_fase_fichas';

    protected $fillable = [
        'fase_id',
        'course_id',
        'registrada_por',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'fase_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function registradaPor()
    {
        return $this->belongsTo(Person::class, 'registrada_por');
    }
}
