<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Alerta;
use Modules\TalentoHumano\Entities\NotificacionFallida;
use Modules\TalentoHumano\Support\CondicionesMinimas;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\ServiciosExternos;

/**
 * Alertas del Administrador y estado de los servicios externos (HU-011, HU-015).
 */
class AlertasController extends Controller
{
    public function index(Request $request)
    {
        $vista = $request->query('vista') === 'sin-leer' ? 'sin-leer' : 'todas';

        return view('talentohumano::lider.alertas.index', [
            'vista' => $vista,
            'alertas' => Alerta::query()->when($vista === 'sin-leer', fn ($q) => $q->sinLeer())->latest('id')->paginate(20)->withQueryString(),
            'sinLeer' => Alerta::sinLeer()->count(),
            'condiciones' => CondicionesMinimas::evaluar(),
            'sicefa' => ServiciosExternos::sicefa(),
            'correo' => ServiciosExternos::correo(),
            'noEntregados' => NotificacionFallida::pendientes()->latest('id')->get(),
        ]);
    }

    public function leer(Alerta $alerta)
    {
        $alerta->update(['leida_en' => $alerta->leida_en ?? now()]);

        return back();
    }

    public function leerTodas()
    {
        Alerta::sinLeer()->update(['leida_en' => now()]);

        return back()->with('success', 'Todas las alertas quedaron como leídas.');
    }

    public function probarSicefa()
    {
        $estado = ServiciosExternos::probarSicefa();

        return back()->with($estado['estado'] === 'operativo' ? 'success' : 'error', match ($estado['estado']) {
            'operativo' => "SICEFA respondió en {$estado['respuesta_ms']} ms.",
            'sin_configurar' => 'La dirección de la API de SICEFA no está configurada (SICEFA_API_URL).',
            default => 'SICEFA no responde: ' . $estado['error'],
        });
    }

    /**
     * Nuevo intento de un correo no entregado (HU-015, esc. 2).
     */
    public function reintentar(NotificacionFallida $notificacion)
    {
        abort_if($notificacion->entregada_en, 409, 'El correo ya se entregó');

        return Correo::reintentar($notificacion)
            ? back()->with('success', "Se entregó el correo «{$notificacion->asunto}».")
            : back()->with('error', "El correo «{$notificacion->asunto}» sigue sin entregarse: {$notificacion->error}");
    }
}
