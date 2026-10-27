<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Http\Controllers\InstructorLiderController;

/**
 * Estado de los servicios externos de FOLIUM: la API de SICEFA (IE-001 a IE-006) y el correo (IE-007). HU-015.
 */
class ServiciosExternos
{
    private const CLAVE_SICEFA = 'folium.servicios.sicefa';

    /**
     * Estado de la API de SICEFA: sin_configurar, sin_probar, operativo o sin_respuesta.
     */
    public static function sicefa(): array
    {
        $url = config('services.sicefa.url');
        if (!$url) {
            return ['estado' => 'sin_configurar', 'url' => null];
        }

        return Cache::get(self::CLAVE_SICEFA, []) + ['estado' => 'sin_probar', 'url' => $url];
    }

    /**
     * Prueba la conexión con SICEFA. Si deja de responder o vuelve a responder, avisa al Administrador (HU-015, esc. 1).
     */
    public static function probarSicefa(): array
    {
        $url = config('services.sicefa.url');
        if (!$url) {
            return self::sicefa();
        }

        $anterior = Cache::get(self::CLAVE_SICEFA, []);
        $inicio = microtime(true);
        try {
            $respuesta = Http::timeout(10)->acceptJson()->get($url);
            $error = $respuesta->serverError() ? 'El servicio respondió con el código ' . $respuesta->status() : null;
        } catch (\Throwable $e) {
            $error = $e instanceof ConnectionException ? 'Sin conexión o tiempo de espera agotado' : mb_substr($e->getMessage(), 0, 200);
        }
        $ahora = now();

        if ($error === null) {
            $estado = ['estado' => 'operativo', 'ultimo_intento' => $ahora->toIso8601String(), 'respuesta_ms' => (int) round((microtime(true) - $inicio) * 1000)];
            if (($anterior['estado'] ?? null) === 'sin_respuesta') {
                $minutos = (int) Carbon::parse($anterior['desde'])->diffInMinutes($ahora);
                Alerta::crear('sicefa_recuperado', 'SICEFA volvió a responder', "La interrupción duró {$minutos} minutos. No se perdió información registrada en FOLIUM.");
            }
        } else {
            $caido = ($anterior['estado'] ?? null) === 'sin_respuesta';
            $estado = [
                'estado' => 'sin_respuesta', 'error' => $error, 'ultimo_intento' => $ahora->toIso8601String(),
                'desde' => $caido ? $anterior['desde'] : $ahora->toIso8601String(), 'fallos' => ($caido ? $anterior['fallos'] : 0) + 1,
            ];
            if (!$caido) {
                $texto = 'Los usuarios no pueden iniciar sesión ni consultar fichas, aprendices o instructores. La información ya registrada en FOLIUM está a salvo. Causa: ' . $error;
                Alerta::crear('sicefa_caido', 'SICEFA no responde desde ' . $ahora->format('d/m/Y g:i a'), $texto, InstructorLiderController::url('alertas'));
                Correo::enviar(AccesoTH::correosAdministradores()->all(), 'FOLIUM: la API de SICEFA no responde',
                    "FOLIUM · Talento Humano\n\nLa API de SICEFA dejó de responder el " . $ahora->format('d/m/Y \a \l\a\s g:i a') . ".\n\n{$texto}");
            }
        }
        Cache::forever(self::CLAVE_SICEFA, $estado);

        return $estado + ['url' => $url];
    }

    /**
     * Configuración del correo: el modo log no envía nada, solo escribe los mensajes en el log de Laravel.
     */
    public static function correo(): array
    {
        $mailer = config('mail.default');

        return ['mailer' => $mailer, 'solo_log' => in_array($mailer, ['log', 'array']), 'remitente' => config('mail.from.address')];
    }
}
