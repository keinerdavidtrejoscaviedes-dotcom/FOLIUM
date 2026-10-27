<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fase de SENA Empresa, identificada por su número.
 */
class Fase extends Model
{
    use SoftDeletes;

    protected $table = 'th_fases';

    protected $fillable = ['numero', 'fecha_inicio', 'fecha_fin', 'estado'];

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
}
