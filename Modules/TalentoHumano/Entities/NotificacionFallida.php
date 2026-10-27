<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Correo que no se pudo entregar después de los reintentos (HU-015, esc. 2; RNF-013).
 */
class NotificacionFallida extends Model
{
    protected $table = 'th_notificaciones_fallidas';

    protected $fillable = ['destinatarios', 'asunto', 'cuerpo', 'error', 'intentos', 'entregada_en'];

    protected $casts = ['destinatarios' => 'array', 'entregada_en' => 'datetime'];

    public function scopePendientes($query)
    {
        return $query->whereNull('entregada_en');
    }
}
