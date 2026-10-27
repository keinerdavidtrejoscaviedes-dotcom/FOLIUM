<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class ThInventarioEmpalme extends Model
{
    protected $table = 'th_inventario_empalme';

    protected $fillable = [
        'empalme_id',
        'item',
        'cantidad',
        'estado', // Bueno, Regular, Malo
        'verificado',
        'novedad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'verificado' => 'boolean',
    ];

    public function empalme()
    {
        return $this->belongsTo(ThEmpalme::class, 'empalme_id');
    }
}
