<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThEmpalme extends Model
{
    protected $table = 'th_empalmes';

    protected $fillable = [
        'fase_id',
        'cargo_id',
        'cargo_receptor_id',
        'tipo', // individual, grupal
        'estado', // pendiente, en_curso, firmado, cerrado
        'visita_confirmada',
        'fecha_visita',
        'visto_bueno_por',
        'fecha_visto_bueno',
    ];

    protected $casts = [
        'visita_confirmada' => 'boolean',
        'fecha_visita' => 'date',
        'fecha_visto_bueno' => 'datetime',
    ];

    public function fase()
    {
        return $this->belongsTo(ThFase::class, 'fase_id');
    }

    public function cargo()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_id');
    }

    public function cargoReceptor()
    {
        return $this->belongsTo(ThCargo::class, 'cargo_receptor_id');
    }

    public function inventario()
    {
        return $this->hasMany(ThInventarioEmpalme::class, 'empalme_id');
    }

    public function encuestaSatisfaccion()
    {
        return $this->hasMany(ThSatisfaccionEmpalme::class, 'empalme_id');
    }
}
