<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\CuestionarioService;

/**
 * Formato de entrevista propio de cada evaluador: sus preguntas y el puntaje de cada una (HU-040, RF-009).
 * El psicólogo arma la entrevista psicológica; los instructores, la técnica.
 */
class FormatoEntrevistaController extends Controller
{
    public function __construct(private CuestionarioService $cuestionarios)
    {
    }

    public function edit(Request $request)
    {
        $tipo = $this->tipoDelEvaluador();
        $formato = $this->formatoActual($tipo);

        // 'Cargar ítems base' (escenario 2) reemplaza las preguntas del editor, sin guardar todavía
        if ($request->boolean('base')) {
            $preguntas = $this->cuestionarios->itemsBase($tipo);
        } else {
            $preguntas = $formato ? $this->cuestionarios->preguntas($formato->id) : [];
        }

        return view('talentohumano::formatos.edit', [
            'tipo' => $tipo,
            'formato' => $formato,
            'preguntas' => array_map(fn ($p) => [
                'enunciado' => $p['enunciado'],
                'puntaje_maximo' => $p['puntaje_maximo'],
            ], $preguntas),
            'enUso' => $formato && $this->cuestionarios->enUso($formato),
            'cargoBase' => $request->boolean('base'),
        ]);
    }

    public function update(Request $request)
    {
        $tipo = $this->tipoDelEvaluador();
        $formato = $this->formatoActual($tipo);

        $datos = $this->cuestionarios->validar($request, 'formato');
        $titulo = ($tipo === 'entrevista_psicologica' ? 'Entrevista psicológica' : 'Entrevista técnica')
            . ' · ' . auth()->user()->full_name;

        $resultado = $this->cuestionarios->guardar($tipo, $titulo, $datos['preguntas'], $formato, $this->personaId());

        // HU-040 escenarios 1, 4 y 5
        $mensaje = match ($resultado['accion']) {
            'sin_cambios' => 'No se detectaron cambios.',
            'nueva_version' => 'Su formato ya tenía calificaciones: se creó la versión ' . ($formato->version + 1) . ' como vigente y las calificaciones anteriores se conservan con la versión ' . $formato->version . '.',
            default => 'Formato de entrevista guardado correctamente.',
        };

        if ($resultado['accion'] !== 'sin_cambios') {
            Auditoria::registrar('Guardar formato de entrevista (' . $resultado['accion'] . ')', 'th_cuestionarios', $resultado['id']);
        }

        return redirect()->route('talentohumano.formato.edit')
            ->with($resultado['accion'] === 'sin_cambios' ? 'info' : 'success', $mensaje);
    }

    /**
     * El psicólogo hace la entrevista psicológica; los demás evaluadores, la técnica (RN-018).
     */
    private function tipoDelEvaluador(): string
    {
        $esPsicologo = auth()->user()->roles->contains('slug', 'talentohumano.psicologo');

        return $esPsicologo ? 'entrevista_psicologica' : 'entrevista_tecnica';
    }

    /**
     * Formato vigente del evaluador para ese tipo de entrevista (la última versión).
     */
    private function formatoActual(string $tipo): ?object
    {
        return DB::table('th_cuestionarios as c')
            ->where('c.tipo', $tipo)
            ->where('c.autor_id', $this->personaId())
            ->where('c.estado', 'vigente')
            ->whereNull('c.deleted_at')
            ->whereNotExists(fn ($q) => $q->from('th_cuestionarios as s')->whereColumn('s.version_anterior_id', 'c.id'))
            ->orderByDesc('c.version')
            ->select('c.*')
            ->first();
    }

    private function personaId(): int
    {
        $personaId = auth()->user()->person_id;

        abort_unless($personaId, 403, 'Su usuario no tiene una persona asociada en el sistema.');

        return $personaId;
    }
}
