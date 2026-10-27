<?php

namespace Modules\TalentoHumano\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acciones de configuración reservadas al Administrador de FOLIUM (HU-003, esc. 6; HU-037, esc. 8).
 */
class AdministradorTH
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!AccesoTH::esAdministrador($request->user())) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        return $next($request);
    }
}
