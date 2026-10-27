<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\CuestionarioService;

/**
 * Pruebas psicotécnicas: el psicólogo las diseña y el Gestor de TH puede apoyarlo (HU-039, RF-007).
 */
class PruebaPsicotecnicaController extends Controller
{
    private const TIPO = 'prueba_psicotecnica';

    public function __construct(private CuestionarioService $cuestionarios)
    {
    }

    /**
     * Listado de pruebas psicotécnicas (solo la última versión de cada una).
     */
    public function index()
    {
        $pruebas = DB::table('th_cuestionarios as c')
            ->join('people as p', 'p.id', '=', 'c.autor_id')
            ->where('c.tipo', self::TIPO)
            ->whereNull('c.deleted_at')
            ->whereNotExists(fn ($q) => $q->from('th_cuestionarios as s')->whereColumn('s.version_anterior_id', 'c.id'))
            ->select('c.id', 'c.titulo', 'c.version', 'c.estado', 'c.updated_at', 'p.first_name', 'p.first_last_name')
            ->addSelect([
                'preguntas' => DB::table('th_cuestionario_preguntas')->selectRaw('count(*)')->whereColumn('cuestionario_id', 'c.id'),
                'puntaje' => DB::table('th_cuestionario_preguntas')->selectRaw('coalesce(sum(puntaje_maximo), 0)')->whereColumn('cuestionario_id', 'c.id'),
            ])
            ->orderByDesc('c.updated_at')
            ->get();

        return view('talentohumano::pruebas.index', compact('pruebas'));
    }

    public function create()
    {
        return view('talentohumano::pruebas.form', [
            'prueba' => null,
            'preguntas' => [],
            'enUso' => false,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->cuestionarios->validar($request, 'prueba');
        $resultado = $this->cuestionarios->guardar(self::TIPO, $datos['titulo'], $datos['preguntas'], null, $this->personaId());

        Auditoria::registrar('Crear prueba psicotécnica', 'th_cuestionarios', $resultado['id']);

        return redirect()->route('talentohumano.pruebas.show', $resultado['id'])
            ->with('success', 'Prueba guardada correctamente.');
    }

    public function show(int $prueba)
    {
        $prueba = $this->buscar($prueba);

        return view('talentohumano::pruebas.show', [
            'prueba' => $prueba,
            'autor' => DB::table('people')->find($prueba->autor_id),
            'preguntas' => $this->cuestionarios->preguntas($prueba->id),
            'versiones' => $this->cuestionarios->historial($prueba),
            'enUso' => $this->cuestionarios->enUso($prueba),
            'esUltima' => $this->esUltimaVersion($prueba->id),
        ]);
    }

    public function edit(int $prueba)
    {
        $prueba = $this->buscar($prueba);

        if (!$this->esUltimaVersion($prueba->id)) {
            return redirect()->route('talentohumano.pruebas.show', $prueba->id)
                ->with('error', 'Esta es una versión anterior de la prueba: solo se puede editar la versión más reciente.');
        }

        return view('talentohumano::pruebas.form', [
            'prueba' => $prueba,
            'preguntas' => $this->cuestionarios->paraEditor($this->cuestionarios->preguntas($prueba->id)),
            'enUso' => $this->cuestionarios->enUso($prueba),
        ]);
    }

    public function update(Request $request, int $prueba)
    {
        $prueba = $this->buscar($prueba);

        if (!$this->esUltimaVersion($prueba->id)) {
            return redirect()->route('talentohumano.pruebas.show', $prueba->id)
                ->with('error', 'Esta es una versión anterior de la prueba: solo se puede editar la versión más reciente.');
        }

        $datos = $this->cuestionarios->validar($request, 'prueba');
        $resultado = $this->cuestionarios->guardar(self::TIPO, $datos['titulo'], $datos['preguntas'], $prueba, $this->personaId());

        // HU-039 escenarios 4, 5 y 8
        $mensaje = match ($resultado['accion']) {
            'sin_cambios' => 'No se detectaron cambios.',
            'nueva_version' => 'La prueba ya tenía respuestas de aprendices: se creó la versión ' . ($prueba->version + 1) . ' y los resultados anteriores se conservan con la versión ' . $prueba->version . '.',
            default => 'Prueba guardada correctamente.',
        };

        if ($resultado['accion'] !== 'sin_cambios') {
            Auditoria::registrar('Editar prueba psicotécnica (' . $resultado['accion'] . ')', 'th_cuestionarios', $resultado['id'], ['version_anterior' => $prueba->id]);
        }

        return redirect()->route('talentohumano.pruebas.show', $resultado['id'])
            ->with($resultado['accion'] === 'sin_cambios' ? 'info' : 'success', $mensaje);
    }

    /**
     * Publica la prueba (vigente: ya puede habilitarse a los postulantes) o la inactiva.
     */
    public function estado(Request $request, int $prueba)
    {
        $prueba = $this->buscar($prueba);
        $request->validate(['estado' => 'required|in:vigente,inactivo']);

        if (!$this->esUltimaVersion($prueba->id)) {
            return back()->with('error', 'Solo se puede cambiar el estado de la versión más reciente de la prueba.');
        }

        DB::table('th_cuestionarios')->where('id', $prueba->id)
            ->update(['estado' => $request->estado, 'updated_at' => now()]);

        Auditoria::registrar('Cambiar estado de prueba psicotécnica', 'th_cuestionarios', $prueba->id, [
            'anterior' => $prueba->estado,
            'nuevo' => $request->estado,
        ]);

        return back()->with('success', $request->estado === 'vigente'
            ? 'Prueba publicada: ya puede habilitarse a los postulantes.'
            : 'Prueba inactivada: no se podrá habilitar a nuevos postulantes.');
    }

    /**
     * Imagen de una pregunta. Se sirve desde el disco privado, nunca por una URL pública (RNF-007).
     */
    public function imagen(int $pregunta)
    {
        $ruta = DB::table('th_cuestionario_preguntas')->where('id', $pregunta)->value('imagen');

        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta);
    }

    private function buscar(int $id): object
    {
        $prueba = DB::table('th_cuestionarios')
            ->where('tipo', self::TIPO)
            ->whereNull('deleted_at')
            ->find($id);

        abort_unless($prueba, 404);

        return $prueba;
    }

    private function esUltimaVersion(int $id): bool
    {
        return !DB::table('th_cuestionarios')->where('version_anterior_id', $id)->exists();
    }

    private function personaId(): int
    {
        $personaId = auth()->user()->person_id;

        abort_unless($personaId, 403, 'Su usuario no tiene una persona asociada en el sistema.');

        return $personaId;
    }
}
