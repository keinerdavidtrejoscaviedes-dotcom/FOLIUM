<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThCargo extends Model
{
    use SoftDeletes;

    protected $table = 'th_cargos';

    protected $fillable = [
        'area_id',
        'cargo_padre_id',
        'nombre',
        'nivel', // Gerente, Gestor, Líder, Equipo
        'numero_aprendices',
        'proposito',
        'funciones',
        'responsabilidades',
        'requisitos',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'numero_aprendices' => 'integer',
    ];

    public function area()
    {
        return $this->belongsTo(ThArea::class, 'area_id');
    }

    public function jefeInmediato()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_padre_id');
    }

    public function subordinados()
    {
        return $this->hasMany(ThCargo::class, 'cargo_padre_id');
    }

    public function programasCompatibles()
    {
        return $this->hasMany(ThCargoPrograma::class, 'cargo_id');
    }
}
