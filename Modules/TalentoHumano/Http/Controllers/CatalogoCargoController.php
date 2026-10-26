<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\CargoPrograma;
use Modules\SICA\Entities\Program;

class CatalogoCargoController extends Controller
{
    /**
     * Catálogo interactivo de cargos de SENA Empresa (RF-019).
     */
    public function index(Request $request)
    {
        // 1. Filtrar cargos según criterios de búsqueda
        $cargos = $this->filtrarCargos($request);

        // 2. Obtener métricas y contadores de niveles
        $metricas = $this->calcularMetricasNiveles();

        // 3. Obtener listas de opciones para los filtros
        $opciones = $this->obtenerOpcionesFiltros();

        return view('talentohumano::catalogo.index', array_merge(
            compact('cargos'),
            $metricas,
            $opciones
        ));
    }

    /**
     * Perfil detallado y manual de funciones de un cargo (RF-019).
     */
    public function show($id)
    {
        $cargo = Cargo::with([
            'area',
            'cargoSuperior',
            'subordinados.area',
            'programs',
            'cargosActivados.fase',
        ])->findOrFail($id);

        return view('talentohumano::catalogo.show', compact('cargo'));
    }

    /* =========================================================================
     * BLOQUES DE LÓGICA Y FUNCIONES AUXILIARES (MODULARES)
     * ========================================================================= */

    /**
     * Bloque: Filtrar cargos activos por texto, área, nivel y programa.
     */
    private function filtrarCargos(Request $request)
    {
        $query = Cargo::with(['area', 'programs'])->where('estado', 'activo');

        // Búsqueda por texto (nombre, rol o actividades)
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('rol_principal', 'like', "%{$buscar}%")
                  ->orWhere('actividades', 'like', "%{$buscar}%");
            });
        }

        // Filtro por área funcional
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // Filtro por nivel jerárquico
        if ($request->filled('nivel') && $request->nivel !== 'todos') {
            $query->where('nivel', $request->nivel);
        }

        // Filtro por programa de formación afín
        if ($request->filled('program_id')) {
            $programId = $request->program_id;
            $query->whereHas('programs', fn ($p) => $p->where('programs.id', $programId));
        }

        return $query->orderByRaw("FIELD(nivel, 'Gerente', 'Gestor', 'Lider', 'Equipo')")
                     ->orderBy('nombre')
                     ->paginate(15)
                     ->withQueryString();
    }

    /**
     * Bloque: Calcular métricas y distribución por nivel jerárquico.
     */
    private function calcularMetricasNiveles(): array
    {
        return [
            'totalCargos' => Cargo::where('estado', 'activo')->count(),
            'gerentesCount' => Cargo::where('estado', 'activo')->where('nivel', 'Gerente')->count(),
            'gestoresCount' => Cargo::where('estado', 'activo')->where('nivel', 'Gestor')->count(),
            'lideresCount' => Cargo::where('estado', 'activo')->where('nivel', 'Lider')->count(),
            'equiposCount' => Cargo::where('estado', 'activo')->where('nivel', 'Equipo')->count(),
        ];
    }

    /**
     * Bloque: Obtener áreas y programas disponibles para los desplegables de filtro.
     */
    private function obtenerOpcionesFiltros(): array
    {
        $programaIds = CargoPrograma::distinct()->pluck('program_id');

        return [
            'areas' => Area::orderBy('nombre')->get(),
            'programas' => Program::whereIn('id', $programaIds)->orderBy('name')->get(),
        ];
    }
}
