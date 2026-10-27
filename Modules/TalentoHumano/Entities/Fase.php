<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Course;

/**
 * Fase de SENA Empresa, identificada por su número.
 */
class Fase extends Model
{
    use SoftDeletes;

    protected $table = 'th_fases';

    protected $fillable = ['numero', 'fecha_inicio', 'fecha_fin', 'estado'];

    protected $casts = ['numero' => 'integer', 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    /**
     * Fase en curso: la más reciente que no está cerrada.
     */
    public static function actual(): ?self
    {
        return self::where('estado', '!=', 'cerrada')->orderByDesc('numero')->first();
    }

    public function nombre(): string
    {
        return 'Fase ' . $this->numero;
    }

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

    /**
     * Número de la fase en romanos (Fase XXXVI).
     */
    public function getRomanoAttribute(): string
    {
        $mapa = [100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $romano = '';
        $n = $this->numero;
        foreach ($mapa as $valor => $letras) {
            while ($n >= $valor) {
                $romano .= $letras;
                $n -= $valor;
            }
        }

        return $romano ?: (string) $this->numero;
    }

    public function getNombreCompletoAttribute(): string
    {
        return "Fase {$this->romano} (Fase {$this->numero})";
    }
}
