<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\Acta;
use Modules\TalentoHumano\Entities\Asignacion;
use Modules\TalentoHumano\Entities\Convocatoria;
use Modules\TalentoHumano\Entities\Fase;
use Modules\TalentoHumano\Support\AccesoTH;
use Modules\TalentoHumano\Support\ActaSeleccionados;
use Modules\TalentoHumano\Support\Auditoria;
use Modules\TalentoHumano\Support\AvisosGestor;
use Modules\TalentoHumano\Support\ComiteReubicacion;
use Modules\TalentoHumano\Support\Correo;
use Modules\TalentoHumano\Support\GestionAsignaciones;
use Modules\TalentoHumano\Support\ProcesoSeleccion;

/**
 * Acta de seleccionados: el sistema la genera con las selecciones validadas, las reubicaciones y los cargos vacantes;
 * el Gestor de TH la revisa y la publica, y así se crean las asignaciones "asignada" (HU-028A, HU-028B, RF-012, RN-023).
 */
class ActaSeleccionadosController extends Controller
{
    public function show(int $fase)
    {
        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase);

        if (!$convocatoria) {
            return view('talentohumano::proceso.acta', ['fase' => $fase, 'convocatoria' => null]);
        }

        $publicada = ActaSeleccionados::publicada($fase->id);

        return view('talentohumano::proceso.acta', [
            'fase' => $fase,
            'convocatoria' => $convocatoria,
            'publicada' => $publicada,
            // Publicada, se muestra tal como quedó; antes, se genera con los datos actuales (escenario 1)
            'filas' => $publicada ? collect($publicada->contenido['filas'] ?? [])->map(fn ($f) => (object) $f) : ActaSeleccionados::filas($convocatoria->id),
            'vacantes' => $publicada ? collect($publicada->contenido['vacantes'] ?? []) : $this->vacantes($convocatoria),
            'pendientes' => $publicada ? [] : $this->pendientes($convocatoria),
            // HU-037D: apoyo a la reunión del comité, sin puntajes ni resultados de evaluación
            'porReubicar' => $publicada ? collect() : ComiteReubicacion::pendientes($convocatoria),
            'cuposVacantes' => $publicada ? collect() : ComiteReubicacion::vacantes($convocatoria),
            'soloConsulta' => !AccesoTH::puedeModificar(auth()->user(), 'programacion'),
        ]);
    }

    /**
     * 'Confirmar publicación' (HU-028B esc. 1, 2 y 4).
     */
    public function publicar(Request $request, int $fase)
    {
        if (!AccesoTH::puedeModificar(auth()->user(), 'programacion')) {
            Auditoria::registrarIntentoDenegado($request);
            abort(403, 'No tiene permisos para realizar esta acción');
        }

        $fase = Fase::findOrFail($fase);
        $convocatoria = ProcesoSeleccion::convocatoria($fase) ?? abort(404);

        if (ActaSeleccionados::publicada($fase->id)) {
            return back()->with('error', 'El acta de seleccionados de esta fase ya fue publicada.');
        }
        // Escenario 2: la vista lista lo que falta
        if ($pendientes = $this->pendientes($convocatoria)) {
            AvisosGestor::prerrequisito($fase, 'Acta de seleccionados', implode('; ', $pendientes));

            return back()->with('error', 'No se puede publicar el acta: hay cargos o aprendices pendientes.');
        }

        $asignaciones = $this->asignacionesPorCrear($convocatoria);

        // RN-029: un aprendiz no puede quedar con dos cargos en la misma fase
        $ocupados = $asignaciones->filter(fn ($a) => GestionAsignaciones::ocupada($a->person_id, $fase->id))->pluck('aprendiz');
        if ($ocupados->isNotEmpty()) {
            return back()->with('error', 'Estos aprendices ya tienen un cargo en la fase y no pueden recibir otro: ' . $ocupados->implode(', ') . '.');
        }

        $filas = ActaSeleccionados::filas($convocatoria->id);
        $vacantes = $this->vacantes($convocatoria);

        $acta = DB::transaction(function () use ($fase, $convocatoria, $asignaciones, $filas, $vacantes) {
            $acta = Acta::create([
                'tipo' => 'seleccionados',
                'fase_id' => $fase->id,
                'contenido' => ['convocatoria' => $convocatoria->titulo, 'filas' => $filas->all(), 'vacantes' => $vacantes->all()],
                'estado' => 'publicada',
                'publicada_por' => auth()->user()->person_id,
                'fecha_publicacion' => now(),
            ]);

            foreach ($asignaciones as $fila) {
                $asignacion = Asignacion::create([
                    'person_id' => $fila->person_id,
                    'cargo_id' => $fila->cargo_id,
                    'fase_id' => $fase->id,
                    'estado' => 'asignada',
                    'origen' => $fila->origen,
                ]);
                Auditoria::registrar('asignacion_creada', $asignacion, [], GestionAsignaciones::instantanea($asignacion));
            }

            // Quien no quedó en el acta termina su postulación como no seleccionado
            DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria->id)->where('estado', 'postulado')
                ->update(['estado' => 'no_seleccionado', 'updated_at' => now()]);
            $convocatoria->update(['estado' => 'finalizada']);
            $fase->update(['estado' => 'empalme']);

            return $acta;
        });

        Auditoria::registrar('acta_publicada', $acta, [], ['seleccionados' => $filas->count(), 'vacantes' => $vacantes->count()]);
        $this->enviarActa($convocatoria, $filas);

        return redirect()->route('talentohumano.fases.acta.show', $fase->id)
            ->with('success', "Acta de seleccionados publicada. Se crearon {$asignaciones->count()} asignaciones de cargo y se envió el acta a todos los postulantes.");
    }

    /**
     * Lo que impide publicar: cargos sin selección validada (escenario 2) y aprendices sin decisión del comité.
     */
    private function pendientes(Convocatoria $convocatoria): array
    {
        $sinValidar = DB::table('th_convocatoria_cargos as cc')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->where('cc.convocatoria_id', $convocatoria->id)
            ->whereNotExists(fn ($q) => $q->from('th_selecciones as s')->whereColumn('s.convocatoria_cargo_id', 'cc.id')->whereIn('s.estado', ['validada', 'ajustada']))
            ->orderBy('c.nombre')->pluck('c.nombre')
            ->map(fn ($cargo) => "{$cargo}: sin selección validada ni declarado vacante");

        $porReubicar = ComiteReubicacion::pendientes($convocatoria)
            ->map(fn ($p) => "{$p->nombre_completo}: pendiente de la decisión del comité de reubicación");

        return $sinValidar->merge($porReubicar)->values()->all();
    }

    /**
     * Cargos declarados vacantes: selección validada sin postulante (RN-020).
     */
    private function vacantes(Convocatoria $convocatoria): Collection
    {
        return DB::table('th_selecciones as s')
            ->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->where('cc.convocatoria_id', $convocatoria->id)
            ->whereIn('s.estado', ['validada', 'ajustada'])->whereNull('s.postulacion_id')
            ->orderBy('c.nombre')->pluck('c.nombre');
    }

    /**
     * Seleccionados (origen "acta") y reubicados con cargo (origen "reubicacion"), con el cargo del catálogo.
     */
    private function asignacionesPorCrear(Convocatoria $convocatoria): Collection
    {
        $nombre = "TRIM(CONCAT_WS(' ', p.first_name, p.first_last_name))";
        $seleccionados = DB::table('th_selecciones as s')
            ->join('th_convocatoria_cargos as cc', 'cc.id', '=', 's.convocatoria_cargo_id')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'cc.cargo_activado_id')
            ->join('th_postulaciones as po', 'po.id', '=', 's.postulacion_id')
            ->join('people as p', 'p.id', '=', 'po.person_id')
            ->where('cc.convocatoria_id', $convocatoria->id)->whereIn('s.estado', ['validada', 'ajustada'])
            ->selectRaw("po.person_id, ca.cargo_id, 'acta' as origen, {$nombre} as aprendiz");
        $reubicados = DB::table('th_reubicaciones as r')
            ->join('th_cargos_activados as ca', 'ca.id', '=', 'r.cargo_activado_id')
            ->join('th_postulaciones as po', 'po.id', '=', 'r.postulacion_id')
            ->join('people as p', 'p.id', '=', 'po.person_id')
            ->where('po.convocatoria_id', $convocatoria->id)
            ->selectRaw("po.person_id, ca.cargo_id, 'reubicacion' as origen, {$nombre} as aprendiz");

        return $seleccionados->unionAll($reubicados)->get();
    }

    /**
     * El acta llega por correo a todos los postulantes de la convocatoria (HU-028B esc. 1, HU-051).
     */
    private function enviarActa(Convocatoria $convocatoria, Collection $filas): void
    {
        $lista = $filas->map(fn ($f) => "- {$f->cargo}: {$f->aprendiz} (ficha {$f->ficha})" . ($f->observacion ? " · {$f->observacion}" : ''))->implode("\n");
        $postulantes = DB::table('th_postulaciones')->where('convocatoria_id', $convocatoria->id)->whereNull('deleted_at')->pluck('person_id');

        Correo::enviar(ProcesoSeleccion::correos($postulantes), 'FOLIUM: acta de seleccionados publicada',
            "FOLIUM · Talento Humano\n\nSe publicó el acta de seleccionados de la convocatoria «{$convocatoria->titulo}».\n\n{$lista}\n\n"
            . 'Consulte el acta y su resultado en ' . route('talentohumano.aprendiz.acta'));
    }
}
