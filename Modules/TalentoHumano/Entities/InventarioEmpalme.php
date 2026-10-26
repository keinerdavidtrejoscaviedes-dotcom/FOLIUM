<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class InventarioEmpalme extends Model
{
    protected $table = 'th_inventario_empalme';

    protected $fillable = [
        'empalme_id',
        'item',
        'cantidad',
        'estado',
        'verificado',
        'novedad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'verificado' => 'boolean',
    ];

    public function empalme()
    {
        return $this->belongsTo(Empalme::class, 'empalme_id');
    }
}
