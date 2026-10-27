<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\Mail;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\NotificacionFallida;
use Modules\TalentoHumano\Http\Controllers\InstructorLiderController;

/**
 * Envío de las notificaciones de FOLIUM (IE-007) con reintentos; lo que no se entrega queda registrado (RNF-013).
 */
class Correo
{
    public const REINTENTOS = 3;

    /**
     * Envía un correo de texto. Devuelve false si no se pudo entregar después de los reintentos.
     */
    public static function enviar(array $destinatarios, string $asunto, string $cuerpo): bool
    {
        $destinatarios = array_values(array_unique(array_filter($destinatarios)));
        if (!$destinatarios) {
            return true;
        }

        $error = null;
        for ($intento = 1; $intento <= self::REINTENTOS; $intento++) {
            if (($error = self::intentar($destinatarios, $asunto, $cuerpo)) === null) {
                return true;
            }
        }

        NotificacionFallida::create([
            'destinatarios' => $destinatarios, 'asunto' => $asunto, 'cuerpo' => $cuerpo, 'error' => $error, 'intentos' => self::REINTENTOS,
        ]);
        Alerta::crear('correo_no_entregado', "No se entregó el correo «{$asunto}»",
            'Para: ' . implode(', ', $destinatarios) . '. Causa: ' . $error, InstructorLiderController::url('alertas'));

        return false;
    }

    /**
     * Nuevo intento manual desde «Alertas y servicios».
     */
    public static function reintentar(NotificacionFallida $notificacion): bool
    {
        $error = self::intentar($notificacion->destinatarios, $notificacion->asunto, $notificacion->cuerpo);
        $notificacion->update([
            'intentos' => min(255, $notificacion->intentos + 1),
            'error' => $error ?? $notificacion->error,
            'entregada_en' => $error === null ? now() : null,
        ]);

        return $error === null;
    }

    /**
     * Error del envío, o null si salió bien.
     */
    private static function intentar(array $destinatarios, string $asunto, string $cuerpo): ?string
    {
        try {
            Mail::raw($cuerpo, fn ($m) => $m->to($destinatarios)->subject($asunto));

            return null;
        } catch (\Throwable $e) {
            $mensaje = $e->getMessage();

            return match (true) {
                str_contains($mensaje, 'Connection could not be established'), str_contains($mensaje, 'Connection refused') => 'No se pudo conectar con el servidor de correo',
                str_contains($mensaje, 'timed out') => 'Tiempo de espera agotado con el servidor de correo',
                str_contains($mensaje, 'Expected response code 250') || str_contains($mensaje, '550') => 'El servidor de correo rechazó la dirección del destinatario',
                str_contains($mensaje, 'authenticate') => 'El servidor de correo rechazó el usuario o la contraseña de la cuenta institucional',
                default => mb_substr($mensaje, 0, 200) ?: get_class($e),
            };
        }
    }
}
