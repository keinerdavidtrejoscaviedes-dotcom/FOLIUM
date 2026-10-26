<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SICA\Entities\Program;

class Cargo extends Model
{
    use SoftDeletes;

    protected $table = 'th_cargos';

    protected $fillable = [
        'nombre',
        'nivel',
        'area_id',
        'cargo_superior_id',
        'jefe_inmediato_texto',
        'macroproceso',
        'proceso',
        'subproceso',
        'numero_aprendices',
        'rol_principal',
        'actividades',
        'responsabilidades_sig',
        'documentacion',
        'requisitos',
        'estado',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function cargoSuperior()
    {
        return $this->belongsTo(self::class, 'cargo_superior_id');
    }

    public function subordinados()
    {
        return $this->hasMany(self::class, 'cargo_superior_id');
    }

    public function cargoProgramas()
    {
        return $this->hasMany(CargoPrograma::class, 'cargo_id');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'th_cargo_programas', 'cargo_id', 'program_id')
            ->withPivot('tipo')
            ->withTimestamps();
    }

    public function cargosActivados()
    {
        return $this->hasMany(CargoActivado::class, 'cargo_id');
    }
}
