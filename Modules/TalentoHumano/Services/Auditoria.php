<?php

namespace Modules\TalentoHumano\Services;

use Illuminate\Support\Facades\Log;

/**
 * Registro de las acciones del módulo (RNF-005).
 *
 * Provisional: escribe en storage/logs/talentohumano.log, porque la tabla de auditoría
 * se definirá con los demás equipos de FOLIUM (SRS v1.4, sección 8: base de datos común).
 */
class Auditoria
{
    public static function registrar(string $accion, string $tabla, ?int $registroId, array $datos = []): void
    {
        $usuario = auth()->user();

        Log::build(['driver' => 'single', 'path' => storage_path('logs/talentohumano.log')])
            ->info($accion, [
                'usuario_id' => $usuario?->id,
                'persona_id' => $usuario?->person_id,
                'tabla' => $tabla,
                'registro_id' => $registroId,
                'datos' => $datos,
            ]);
    }
}
