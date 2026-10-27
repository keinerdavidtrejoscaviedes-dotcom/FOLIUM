<?php

namespace Modules\TalentoHumano\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el submódulo "Instructor Líder de TH" al Administrador y al Líder de la estrategia (HU-037, esc. 3).
 */
class SubmoduloLider
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!AccesoTH::veSubmoduloLider($request->user())) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        return $next($request);
    }
}
