<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\SICA\Entities\Program;

class CargoPrograma extends Model
{
    protected $table = 'th_cargo_programas';

    protected $fillable = [
        'cargo_id',
        'program_id',
        'tipo',
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
