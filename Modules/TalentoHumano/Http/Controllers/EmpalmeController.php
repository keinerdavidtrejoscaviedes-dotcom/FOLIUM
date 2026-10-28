<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Empalme;
use Modules\TalentoHumano\Entities\InventarioEmpalme;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Firma;
use Modules\TalentoHumano\Entities\Fase;
use Modules\SICA\Entities\Person;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\ActaRecibimiento;
use Modules\TalentoHumano\Support\Auditoria;

class EmpalmeController extends Controller
{
    /**
     * Bandeja principal de empalmes (RF-013).
     */
    public function index(Request $request)
    {
        $fase = $this->resolverFaseActiva();
        $faseId = $fase ? $fase->id : null;

        // Bloque 1: Filtrar empalmes según criterios
        $empalmes = $this->filtrarEmpalmes($request, $faseId);

        // Bloque 2: Calcular métricas e indicadores de la fase
        $metricas = $this->calcularMetricasEmpalmes($faseId);

        return view('talentohumano::empalmes.index', array_merge([
            'empalmes' => $empalmes,
            'fase' => $fase,
        ], $metricas));
    }

    /**
     * Ficha de detalle del empalme, inventario y firmas (RF-013 / RN-061).
     */
    public function show($id)
    {
        $empalme = Empalme::with([
            'fase',
            'cargo',
            'cargoReceptor',
            'participantes.asignacion.person',
            'inventarios',
            'acta.firmas.person',
            'vistoBuenoPor',
        ])->findOrFail($id);

        $saliente = $empalme->participantes->firstWhere('papel', 'saliente');
        $receptor = $empalme->participantes->firstWhere('papel', 'receptor');

        $acta = $empalme->acta;
        $firmas = $acta ? $acta->firmas : collect();

        $user = auth()->user();
        $personId = $user->person_id ?? 1;

        $yaFirmoInstructor = $firmas->contains(fn ($f) => $f->rol_firmante === 'Instructor de Talento Humano' || $f->person_id == $personId);

        return view('talentohumano::empalmes.show', compact(
            'empalme',
            'saliente',
            'receptor',
            'acta',
            'firmas',
            'yaFirmoInstructor'
        ));
    }

    /**
     * Alterna la verificación de un ítem de inventario.
     */
    public function toggleItem($empalmeId, $itemId)
    {
        $item = InventarioEmpalme::where('empalme_id', $empalmeId)->findOrFail($itemId);
        $item->verificado = !$item->verificado;
        $item->save();
        Auditoria::registrar('inventario_verificado', $item, ['verificado' => !$item->verificado], ['verificado' => $item->verificado, 'item' => $item->item]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'verificado' => $item->verificado,
            ]);
        }

        return back()->with('success', "Ítem «{$item->item}» actualizado.");
    }

    /**
     * Agrega un nuevo ítem al inventario del empalme.
     */
    public function agregarItem(Request $request, $empalmeId)
    {
        $request->validate([
            'item' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'estado' => 'required|string',
            'novedad' => 'nullable|string',
        ]);

        $item = InventarioEmpalme::create([
            'empalme_id' => $empalmeId,
            'item' => $request->item,
            'cantidad' => $request->cantidad,
            'estado' => $request->estado,
            'verificado' => true,
            'novedad' => $request->novedad,
        ]);

        Auditoria::registrar('inventario_agregado', $item, [], $item->only(['item', 'cantidad', 'estado', 'novedad']) + ['empalme' => (int) $empalmeId]);

        return back()->with('success', 'Elemento agregado al inventario de entrega.');
    }

    /**
     * Registra la firma digital del acta de recibimiento (RF-013).
     */
    public function firmarActa(Request $request, $empalmeId)
    {
        $empalme = Empalme::with(['acta', 'cargo'])->findOrFail($empalmeId);
        $personId = auth()->user()->person_id ?? 1;

        // La firma queda como Instructor de Talento Humano: solo la registra quien tiene esa designación
        if (!in_array('instructor_th', AccesoTH::designaciones(auth()->user()))) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        // Bloque 1: Asegurar que el acta de recibimiento exista
        $acta = $this->asegurarActaRecibimiento($empalme);

        // Bloque 2: Registrar firma digital y actualizar estados correspondientes
        $this->procesarFirmaDigital($empalme, $acta, $personId);
        Auditoria::registrar('acta_firmada', $acta, [], ['empalme' => $empalme->id, 'cargo' => optional($empalme->cargo)->nombre, 'rol_firmante' => 'Instructor de Talento Humano']);

        return back()->with('success', '¡Has firmado digitalmente el acta de recibimiento con tu designación de Instructor de Talento Humano!');
    }

    /**
     * Otorga el visto bueno oficial del instructor (RF-013).
     */
    public function vistoBueno(Request $request, $empalmeId)
    {
        $empalme = Empalme::with(['acta', 'participantes.asignacion'])->findOrFail($empalmeId);
        $personId = auth()->user()->person_id ?? 1;

        // RN-024 y RN-027: el empalme cierra con el acta firmada y el Formulario de Satisfacción de ambos aprendices
        if ($empalme->acta?->estado !== 'firmada') {
            return back()->with('error', 'El acta de recibimiento aún no tiene todas las firmas.');
        }
        if (!ActaRecibimiento::satisfaccionCompleta($empalme)) {
            return back()->with('error', 'Los aprendices aún no diligencian el Formulario de Satisfacción del Empalme.');
        }

        DB::transaction(function () use ($empalme, $personId) {
            $empalme->update([
                'estado' => 'cerrado',
                'visto_bueno_por' => $personId,
                'fecha_visto_bueno' => now(),
            ]);

            // HU-055, esc. 2: el receptor queda vinculado y su asignación pasa a vigente (RN-028)
            $empalme->participantes->where('papel', 'receptor')->pluck('asignacion')->filter()
                ->where('estado', 'asignada')
                ->each(fn ($asignacion) => $asignacion->update(['estado' => 'vigente']));
        });
        Auditoria::registrar('visto_bueno_empalme', $empalme, [], ['empalme' => $empalme->id, 'cargo' => optional($empalme->cargo)->nombre]);

        return back()->with('success', 'Visto bueno registrado. El empalme cuenta con la aprobación del Instructor de Talento Humano.');
    }

    // =========================================================================
    // FUNCIONES DE APOYO Y MODULARIZACIÓN POR BLOQUES
    // =========================================================================

    /**
     * Resuelve la fase activa o más reciente para empalmes.
     */
    private function resolverFaseActiva(): ?Fase
    {
        return Fase::whereIn('estado', ['seleccion', 'empalme', 'en_curso'])->orderByDesc('numero')->first()
            ?? Fase::orderByDesc('id')->first();
    }

    /**
     * Filtra los empalmes aplicando criterios de búsqueda de texto, cargo y estado.
     */
    private function filtrarEmpalmes(Request $request, ?int $faseId)
    {
        $query = Empalme::with([
            'cargo',
            'participantes.asignacion.person',
            'inventarios',
            'acta.firmas',
            'vistoBuenoPor'
        ]);

        if ($faseId) {
            $query->where('fase_id', $faseId);
        }

        // Filtro por estado
        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        // Búsqueda por cargo o aprendiz
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('cargo', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%"))
                  ->orWhereHas('participantes.asignacion.person', function ($p) use ($buscar) {
                      $p->where('first_name', 'like', "%{$buscar}%")
                        ->orWhere('first_last_name', 'like', "%{$buscar}%");
                  });
            });
        }

        return $query->orderByDesc('id')->get();
    }

    /**
     * Calcula métricas e indicadores de seguimiento de empalmes.
     */
    private function calcularMetricasEmpalmes(?int $faseId): array
    {
        $faseQuery = Empalme::query();
        if ($faseId) {
            $faseQuery->where('fase_id', $faseId);
        }

        $todosEmpalmes = $faseQuery->get();
        $total = $todosEmpalmes->count();
        $enCurso = $todosEmpalmes->where('estado', 'en_curso')->count();
        $firmados = $todosEmpalmes->where('estado', 'firmado')->count();
        $pendientes = $todosEmpalmes->where('estado', 'pendiente')->count();
        $porcentajeFirmas = $total > 0 ? round(($firmados / $total) * 100) : 0;

        return [
            'total' => $total,
            'enCurso' => $enCurso,
            'firmados' => $firmados,
            'pendientes' => $pendientes,
            'porcentajeFirmas' => $porcentajeFirmas,
        ];
    }

    /**
     * Asegura la creación del acta de recibimiento si no existe.
     */
    private function asegurarActaRecibimiento(Empalme $empalme): Acta
    {
        return ActaRecibimiento::asegurar($empalme);
    }

    /**
     * Procesa la firma digital del instructor y actualiza estados de cierre.
     */
    private function procesarFirmaDigital(Empalme $empalme, Acta $acta, int $personId): void
    {
        Firma::updateOrCreate(
            ['acta_id' => $acta->id, 'person_id' => $personId],
            [
                'rol_firmante' => 'Instructor de Talento Humano',
                'fecha_firma' => now(),
            ]
        );

        // El acta queda firmada cuando firman los aprendices y los firmantes que exige la plantilla del área (HU-057)
        ActaRecibimiento::actualizarEstado($empalme, $acta);
    }
}
