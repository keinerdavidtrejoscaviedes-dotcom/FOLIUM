<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\ActaRecibimiento;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\PortalAprendiz;

/**
 * Actas de recibimiento por firmar (HU-057C): el instructor del área y el jefe inmediato del cargo firman cada uno
 * desde su propia cuenta. Las ve quien está designado como firmante de un empalme abierto, tenga el rol que tenga;
 * el aprendiz que es jefe inmediato las ve dentro de su portal.
 */
class FirmasActaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $actas = ActaRecibimiento::porFirmar($user->person_id);
        $esAprendiz = in_array('aprendiz', AccesoTH::funciones($user));

        return view($esAprendiz ? 'talentohumano::aprendiz.actas-firma' : 'talentohumano::actas-firma.index', [
            'actas' => $this->detalle($actas, (int) $user->person_id),
            'p' => $esAprendiz ? new PortalAprendiz($user) : null,
        ]);
    }

    public function firmar(Request $request, int $empalme)
    {
        $empalme = Empalme::with(['acta', 'cargo', 'participantes.asignacion'])->findOrFail($empalme);
        $resultado = ActaRecibimiento::firmarExterno($empalme, (int) auth()->user()->person_id);

        if (!$resultado) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        return redirect()->route('talentohumano.actas-firma.index')->with(...$resultado);
    }

    /**
     * Datos de cada acta para la lista: rol con el que firma, aprendices y si ya puede firmar.
     */
    private function detalle($actas, int $personId)
    {
        return $actas->map(function (Empalme $empalme) use ($personId) {
            $externos = ActaRecibimiento::firmantesExternos($empalme);
            $clave = ActaRecibimiento::rolExterno($empalme, $personId);
            $aprendices = ActaRecibimiento::aprendicesFirmantes($empalme);
            $firmaron = $empalme->acta ? $empalme->acta->firmas()->pluck('person_id')->all() : [];

            return (object) [
                'empalme' => $empalme,
                'rol' => $externos[$clave]['rol'],
                'firmada' => (bool) $externos[$clave]['firma'],
                'faltanAprendices' => count(array_diff(array_keys($aprendices), $firmaron)),
                'aprendices' => collect($aprendices)->map(fn ($rol, $id) => [
                    'rol' => $rol,
                    'nombre' => $empalme->participantes->first(fn ($p) => $p->asignacion?->person_id == $id)?->asignacion?->person?->full_name ?? 'Aprendiz',
                    'firmo' => in_array($id, $firmaron),
                ])->values(),
                'inventario' => [$empalme->inventarios->where('verificado', true)->count(), $empalme->inventarios->count()],
            ];
        });
    }
}
