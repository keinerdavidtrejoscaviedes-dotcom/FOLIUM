<?php

namespace Modules\TalentoHumano\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\Auditoria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Negar por defecto (HU-002, esc. 4): solo entran a la sección quienes la matriz AccesoTH::PERMISOS autoriza.
 * Uso en las rutas: ->middleware(PermisoTH::class . ':seleccion')
 */
class PermisoTH
{
    public function handle(Request $request, Closure $next, string $seccion): Response
    {
        if (!AccesoTH::puede($request->user(), $seccion)) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        return $next($request);
    }
}
