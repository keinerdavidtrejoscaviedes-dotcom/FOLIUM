<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Course;

class Fase extends Model
{
    use SoftDeletes;

    protected $table = 'th_fases';

    protected $fillable = [
        'numero',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'numero' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function faseFichas()
    {
        return $this->hasMany(FaseFicha::class, 'fase_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'th_fase_fichas', 'fase_id', 'course_id')
            ->withPivot('registrada_por')
            ->withTimestamps();
    }

    public function cargosActivados()
    {
        return $this->hasMany(CargoActivado::class, 'fase_id');
    }

    public function cronograma()
    {
        return $this->hasOne(Cronograma::class, 'fase_id');
    }

    public function convocatoria()
    {
        return $this->hasOne(Convocatoria::class, 'fase_id');
    }

    public function designaciones()
    {
        return $this->hasMany(Designacion::class, 'fase_id');
    }

    public function getRomanoAttribute(): string
    {
        $map = [
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'
        ];
        $res = '';
        $n = $this->numero;
        foreach ($map as $val => $rom) {
            while ($n >= $val) {
                $res .= $rom;
                $n -= $val;
            }
        }
        return $res ?: (string)$this->numero;
    }

    public function getNombreCompletoAttribute(): string
    {
        return "Fase {$this->romano} (Fase {$this->numero})";
    }
}
