<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes;

    protected $table = 'th_areas';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function cargos()
    {
        return $this->hasMany(Cargo::class, 'area_id');
    }
}
