<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\DescripcionAuditoria;
use Modules\TalentoHumano\Support\HojaExcel;

/**
 * Consulta del registro de auditoría de FOLIUM (HU-002, RN-036, RNF-005). Es de solo lectura.
 */
class AuditoriaController extends Controller
{
    public const ORIGENES = ['Rol' => 'Rol', 'Designación' => 'Designación', 'Asignación' => 'Asignación de cargo', 'Sistema' => 'Sistema'];

    public function index(Request $request)
    {
        $registros = $this->consulta($request)->paginate(25)->withQueryString();

        return view('talentohumano::lider.auditoria.index', [
            'registros' => $registros,
            'descripcion' => new DescripcionAuditoria($registros->getCollection()),
        ]);
    }

    /**
     * Exporta a Excel los registros filtrados (máximo 10 000).
     */
    public function exportar(Request $request)
    {
        $registros = $this->consulta($request)->limit(10000)->get();
        $descripcion = new DescripcionAuditoria($registros);
        $filas = $registros->map(function ($r) use ($descripcion) {
            [$tipo, $fuente] = Auditoria::origenDe($r->tags);

            return [
                Carbon::parse($r->created_at)->format('d/m/Y H:i:s'), $r->usuario ?? 'Sistema', $descripcion->seccion($r), $descripcion->accion($r), $tipo, $fuente,
                collect($descripcion->cambios($r))->map(fn ($c) => "{$c[0]}: {$c[1]} → {$c[2]}")->implode(' | '),
                $r->ip_address,
            ];
        });

        return response(HojaExcel::escribir(['Auditoría' => array_merge([['Fecha y hora', 'Usuario', 'Sección', 'Acción', 'Origen del permiso', 'Fuente', 'Detalle', 'IP']], $filas->all())]), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="auditoria_folium_' . now()->format('Y-m-d') . '.xlsx"',
        ]);
    }

    /**
     * La auditoría no se puede modificar ni borrar, ni siquiera por el Administrador (HU-002, esc. 3).
     */
    public function rechazarCambio(Request $request)
    {
        Auditoria::registrarIntentoDenegado($request);
        abort(403, 'El registro de auditoría no se puede modificar ni borrar');
    }

    private function consulta(Request $request)
    {
        $tipos = DescripcionAuditoria::SECCIONES[$request->query('seccion')][1] ?? null;

        return DB::table('audits')
            ->leftJoin('users', 'users.id', '=', 'audits.user_id')
            ->leftJoin('people', 'people.id', '=', 'users.person_id')
            ->where('audits.tags', 'like', Auditoria::ETIQUETA . '%')
            ->when($tipos, fn ($q) => $q->whereIn('audits.auditable_type', $tipos))
            ->when(isset(self::ORIGENES[$request->query('origen')]), fn ($q) => $q->where('audits.tags', 'like', Auditoria::ETIQUETA . '|' . $request->query('origen') . '|%'))
            ->when($request->query('desde'), fn ($q, $fecha) => $q->whereDate('audits.created_at', '>=', $fecha))
            ->when($request->query('hasta'), fn ($q, $fecha) => $q->whereDate('audits.created_at', '<=', $fecha))
            ->when($request->query('q'), function ($q, $texto) {
                $q->where(fn ($w) => $w->where('audits.new_values', 'like', "%{$texto}%")->orWhere('audits.old_values', 'like', "%{$texto}%")
                    ->orWhere('users.nickname', 'like', "%{$texto}%")->orWhereRaw("CONCAT_WS(' ', people.first_name, people.first_last_name) like ?", ["%{$texto}%"]));
            })
            ->orderByDesc('audits.id')
            ->select('audits.*', 'users.nickname')
            ->selectRaw("NULLIF(TRIM(CONCAT_WS(' ', people.first_name, people.first_last_name)), '') as usuario");
    }
}
