<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\PlantillaActa;
use Modules\TalentoHumano\Http\Requests\PlantillaActaRequest;
use Modules\TalentoHumano\Support\Auditoria;

/**
 * Plantillas del acta de recibimiento del cargo por área (RF-021; HU-007 y HU-008).
 */
class PlantillasActaController extends Controller
{
    public function index()
    {
        $vigentes = PlantillaActa::with('area')->where('estado', 'vigente')->get()
            ->sortBy(fn ($p) => [$p->area_id ? 1 : 0, $p->nombreArea()])->values();
        $actas = DB::table('th_actas')->join('th_plantillas_acta as p', 'p.id', '=', 'th_actas.plantilla_id')
            ->selectRaw("COALESCE(p.area_id, 0) as area, count(*) as total")->groupBy('area')->pluck('total', 'area');

        return view('talentohumano::lider.plantillas.index', [
            'vigentes' => $vigentes,
            'actasPorArea' => $actas,
            'totalActas' => $actas->sum(),
            'areasSinPlantilla' => Area::whereNotIn('id', $vigentes->pluck('area_id')->filter())->orderBy('nombre')->pluck('nombre'),
            'totalAreas' => Area::count(),
            'hayGeneral' => $vigentes->contains(fn ($p) => !$p->area_id),
        ]);
    }

    public function create(Request $request)
    {
        $plantilla = new PlantillaActa(PlantillaActa::BASE + ['nombre' => '', 'area_id' => null]);

        return view('talentohumano::lider.plantillas.form', [
            'plantilla' => $plantilla,
            'areaInicial' => $request->query('area', ''),
            'areas' => Area::orderBy('nombre')->pluck('nombre', 'id'),
            'actas' => 0,
            'siguienteVersion' => null,
        ]);
    }

    public function store(PlantillaActaRequest $request)
    {
        $areaId = $request->areaId();
        $this->verificarSinOtraVigente($areaId);

        $plantilla = PlantillaActa::create($request->datos() + [
            'version' => (PlantillaActa::deLaMismaArea($areaId)->withTrashed()->max('version') ?? 0) + 1,
            'estado' => 'vigente',
            'creada_por' => auth()->user()->person_id,
        ]);
        Auditoria::registrar('plantilla_creada', $plantilla, [], $this->instantanea($plantilla));

        return redirect()->route('talentohumano.lider.plantillas.show', $plantilla)->with('success', 'Plantilla guardada correctamente');
    }

    public function show(Request $request, PlantillaActa $plantilla)
    {
        $versiones = PlantillaActa::with('creadaPor')->deLaMismaArea($plantilla->area_id)->orderByDesc('version')->get();
        $seleccionada = $versiones->firstWhere('id', (int) $request->query('version')) ?? $plantilla;

        return view('talentohumano::lider.plantillas.show', [
            'plantilla' => $plantilla->load('area'),
            'versiones' => $versiones,
            'seleccionada' => $seleccionada,
            'actas' => DB::table('th_actas')->whereIn('plantilla_id', $versiones->pluck('id'))->selectRaw('plantilla_id, count(*) as total')->groupBy('plantilla_id')->pluck('total', 'plantilla_id'),
        ]);
    }

    public function edit(PlantillaActa $plantilla)
    {
        if ($plantilla->estado !== 'vigente') {
            return redirect()->route('talentohumano.lider.plantillas.show', $plantilla)->with('info', 'Las versiones anteriores no se pueden modificar. Activa esta versión si quieres volver a usarla.');
        }
        $actas = $plantilla->actasGeneradas();

        return view('talentohumano::lider.plantillas.form', [
            'plantilla' => $plantilla,
            'areaInicial' => $plantilla->area_id ?? 'general',
            'areas' => Area::orderBy('nombre')->pluck('nombre', 'id'),
            'actas' => $actas,
            'siguienteVersion' => $actas ? PlantillaActa::deLaMismaArea($plantilla->area_id)->withTrashed()->max('version') + 1 : null,
        ]);
    }

    public function update(PlantillaActaRequest $request, PlantillaActa $plantilla)
    {
        abort_unless($plantilla->estado === 'vigente', 404);
        $datos = $request->datos();
        $actas = $plantilla->actasGeneradas();

        if ($datos['area_id'] !== $plantilla->area_id) {
            if ($actas) {
                throw ValidationException::withMessages(['area_id' => 'El área no se puede cambiar después de usar la plantilla']);
            }
            $this->verificarSinOtraVigente($datos['area_id']);
        }

        $actuales = $plantilla->only(['area_id', 'nombre', 'indicaciones']) + $plantilla->estructura();
        if ($datos == $actuales) {
            return redirect()->route('talentohumano.lider.plantillas.show', $plantilla)->with('info', 'No se detectaron cambios');
        }

        // HU-008, esc. 2: sin actas, los cambios se guardan sobre la misma versión
        if (!$actas) {
            $antes = $this->instantanea($plantilla);
            $plantilla->update($datos);
            Auditoria::registrar('plantilla_actualizada', $plantilla, $antes, $this->instantanea($plantilla));

            return redirect()->route('talentohumano.lider.plantillas.show', $plantilla)->with('success', 'Plantilla actualizada correctamente');
        }

        // HU-008, esc. 1: ya se usó en actas, así que se crea una nueva versión y la anterior se conserva
        $nueva = DB::transaction(function () use ($plantilla, $datos) {
            $plantilla->update(['estado' => 'inactiva']);
            $nueva = PlantillaActa::create($datos + [
                'version' => PlantillaActa::deLaMismaArea($plantilla->area_id)->withTrashed()->max('version') + 1,
                'estado' => 'vigente',
                'creada_por' => auth()->user()->person_id,
            ]);
            Auditoria::registrar('plantilla_nueva_version', $nueva, $this->instantanea($plantilla), $this->instantanea($nueva));

            return $nueva;
        });

        return redirect()->route('talentohumano.lider.plantillas.show', $nueva)->with('success',
            "Se creó la versión {$nueva->version} de la plantilla {$nueva->nombreArea()} y quedó vigente. Las {$actas} actas generadas con la versión {$plantilla->version} la conservan.");
    }

    /**
     * Deja vigente una versión y desactiva la anterior sin borrarla (HU-007, esc. 4).
     */
    public function activar(PlantillaActa $plantilla)
    {
        if ($plantilla->estado !== 'vigente') {
            DB::transaction(function () use ($plantilla) {
                $anterior = PlantillaActa::deLaMismaArea($plantilla->area_id)->where('estado', 'vigente')->first();
                $anterior?->update(['estado' => 'inactiva']);
                $plantilla->update(['estado' => 'vigente']);
                Auditoria::registrar('plantilla_activada', $plantilla,
                    ['version_vigente' => $anterior?->version], ['version_vigente' => $plantilla->version]);
            });
        }

        return redirect()->route('talentohumano.lider.plantillas.show', $plantilla)
            ->with('success', "La versión {$plantilla->version} quedó vigente para el área {$plantilla->nombreArea()}.");
    }

    /**
     * Cada área tiene como máximo una plantilla vigente (RN-053).
     */
    private function verificarSinOtraVigente(?int $areaId): void
    {
        $vigente = PlantillaActa::deLaMismaArea($areaId)->where('estado', 'vigente')->first();
        if ($vigente) {
            throw ValidationException::withMessages([
                'area_id' => "El área {$vigente->nombreArea()} ya tiene una plantilla vigente. Edita esa plantilla para crear una nueva versión.",
            ]);
        }
    }

    private function instantanea(PlantillaActa $plantilla): array
    {
        return ['area' => $plantilla->nombreArea(), 'version' => $plantilla->version, 'estado' => $plantilla->estado]
            + $plantilla->only(['nombre', 'indicaciones']) + $plantilla->estructura();
    }
}
