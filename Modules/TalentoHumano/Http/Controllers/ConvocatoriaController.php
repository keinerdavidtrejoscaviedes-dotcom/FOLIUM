<?php

namespace Modules\TalentoHumano\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Modules\TalentoHumano\Services\Auditoria;
use Modules\TalentoHumano\Services\FaseService;

/**
 * Convocatoria de la fase: requisitos de cada cargo, umbral y política de selección (HU-022, RF-004),
 * y habilitación de otros programas con justificación (HU-023, RN-048).
 *
 * Mientras no exista la tabla convocatoria_fichas (SRS v1.4, 7.2), quedan habilitadas todas las fichas de la fase.
 * La regla para perfiles incompatibles es "impedir" (RN-048): no hay columna para configurarla.
 */
class ConvocatoriaController extends Controller
{
    /** Documentos que se pueden exigir (los tipos de th_postulacion_documentos). La hoja de vida siempre es obligatoria. */
    public const DOCUMENTOS = [
        'hoja_vida' => 'Hoja de vida en el modelo oficial, con sus soportes',
        'certificado' => 'Certificados de formación',
        'soporte' => 'Soportes adicionales (cursos, constancias)',
        'otro' => 'Otro documento',
    ];

    public const POLITICAS = [
        'mayor_puntaje' => 'Seleccionar al candidato con el puntaje más alto',
        'declarar_vacante' => 'Declarar el cargo vacante',
    ];

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'publicada' => 'Publicada',
        'cerrada' => 'Cerrada',
        'finalizada' => 'Finalizada',
    ];

    public function __construct(private FaseService $fases)
    {
    }

    public function edit(int $fase)
    {
        $fase = $this->fases->buscar($fase);

        return view('talentohumano::fases.convocatoria', ['fase' => $fase] + $this->contexto($fase));
    }

    public function update(Request $request, int $fase)
    {
        $fase = $this->fases->buscar($fase);
        $contexto = $this->contexto($fase);
        $publicar = $request->input('accion') === 'publicar';

        if ($contexto['bloqueo']) {
            return back()->with('error', $contexto['bloqueo']);
        }
        if (!$contexto['editable']) {
            return back()->with('error', 'La convocatoria ya fue publicada; su contenido no se puede modificar.');
        }

        $request->validate([
            'titulo' => 'required|string|max:255',
            'umbral' => 'nullable|numeric|min:0|max:999.99',
            'politica_sin_aptos' => 'nullable|in:' . implode(',', array_keys(self::POLITICAS)),
        ], [
            'titulo.required' => 'Escriba el título de la convocatoria.',
            'umbral.numeric' => 'El umbral debe ser un número.',
        ]);

        [$cargos, $errores] = $this->leerCargos($request, $contexto['cargos'], $publicar);

        if ($publicar) {
            // HU-021 escenario 3
            if ($contexto['cronograma']->estado !== 'aprobado') {
                $errores->add('convocatoria', 'El cronograma aprobado es prerrequisito para publicar la convocatoria.');
            }
            // HU-022 escenario 5
            if (blank($request->umbral) || blank($request->politica_sin_aptos)) {
                $mensaje = 'Configure el umbral mínimo y la política de selección (seleccionar al de mayor puntaje o declarar vacante).';
                $errores->add('umbral', $mensaje);
                $errores->add('politica_sin_aptos', $mensaje);
            }
        }

        if ($errores->isNotEmpty()) {
            return back()->withErrors($errores)->withInput();
        }

        $id = DB::transaction(function () use ($fase, $contexto, $request, $cargos, $publicar) {
            $datos = [
                'cronograma_id' => $contexto['cronograma']->id,
                'titulo' => trim($request->titulo),
                // Las fechas salen del cronograma: así siempre coinciden (HU-022 escenario 4)
                'fecha_apertura' => $contexto['fechas']['apertura'],
                'fecha_cierre' => $contexto['fechas']['cierre'],
                'umbral' => filled($request->umbral) ? $request->umbral : 0,
                'politica_sin_aptos' => filled($request->politica_sin_aptos) ? $request->politica_sin_aptos : 'mayor_puntaje',
                'estado' => $publicar ? 'publicada' : 'borrador',
                'publicada_por' => $publicar ? auth()->user()->person_id : null,
                'updated_at' => now(),
            ];

            if ($contexto['convocatoria']) {
                $id = $contexto['convocatoria']->id;
                DB::table('th_convocatorias')->where('id', $id)->update($datos);
            } else {
                $id = DB::table('th_convocatorias')->insertGetId($datos + ['fase_id' => $fase->id, 'created_at' => now()]);
            }

            foreach ($cargos as $cargoActivadoId => $cargo) {
                DB::table('th_convocatoria_cargos')->updateOrInsert(
                    ['convocatoria_id' => $id, 'cargo_activado_id' => $cargoActivadoId],
                    $cargo + ['updated_at' => now(), 'created_at' => now()]
                );
            }

            if ($publicar) {
                DB::table('th_fases')->where('id', $fase->id)->update(['estado' => 'seleccion', 'updated_at' => now()]);
            }

            return $id;
        });

        Auditoria::registrar($publicar ? 'Publicar convocatoria' : 'Guardar convocatoria', 'th_convocatorias', $id, [
            'excepciones' => collect($cargos)->filter(fn ($c) => $c['programas_extra'])
                ->map(fn ($c) => ['programas' => $c['programas_extra'], 'justificacion' => $c['justificacion_excepcion']])
                ->all(),
        ]);

        // Pendiente: correo a los aprendices de las fichas habilitadas con el enlace (IE-007)
        return redirect()->route('talentohumano.fases.convocatoria.edit', $fase->id)
            ->with('success', $publicar ? 'Convocatoria publicada correctamente.' : 'Convocatoria guardada como borrador.');
    }

    /**
     * Datos que necesitan la vista y el guardado: cronograma, convocatoria, fichas y cargos con su perfil compatible.
     */
    private function contexto(object $fase): array
    {
        $cronograma = $this->fases->cronograma($fase->id);
        $convocatoria = $this->fases->convocatoria($fase->id);

        $bloqueo = match (true) {
            !$cronograma || in_array($cronograma->estado, ['borrador', 'rechazado']) => 'Primero envíe el cronograma a aprobación del Instructor Líder.',
            !DB::table('th_cargos_activados')->where('fase_id', $fase->id)->exists() => 'Primero active los cargos de la fase.',
            default => null,
        };

        if ($bloqueo) {
            return ['bloqueo' => $bloqueo, 'cronograma' => $cronograma, 'convocatoria' => $convocatoria];
        }

        $etapa = DB::table('th_cronograma_etapas')->where('cronograma_id', $cronograma->id)->where('etapa', 'convocatoria')->first();
        $fichas = $this->fases->fichasDeLaFase($fase->id);
        $programasFase = $fichas->pluck('programa', 'program_id');

        $existentes = $convocatoria
            ? DB::table('th_convocatoria_cargos')->where('convocatoria_id', $convocatoria->id)->get()->keyBy('cargo_activado_id')
            : collect();

        $activados = DB::table('th_cargos_activados as ca')
            ->join('th_cargos as c', 'c.id', '=', 'ca.cargo_id')
            ->leftJoin('th_areas as a', 'a.id', '=', 'c.area_id')
            ->where('ca.fase_id', $fase->id)
            ->select('ca.id', 'ca.cargo_id', 'ca.numero_aprendices', 'c.nombre', 'c.nivel', 'c.requisitos', 'a.nombre as area')
            ->orderBy('a.nombre')->orderBy('c.nombre')
            ->get();

        $compatibles = DB::table('th_cargo_programas as cp')
            ->join('programs as p', 'p.id', '=', 'cp.program_id')
            ->whereIn('cp.cargo_id', $activados->pluck('cargo_id'))
            ->select('cp.cargo_id', 'p.id', 'p.name')
            ->get()
            ->groupBy('cargo_id');

        $cargos = $activados->map(function ($cargo) use ($compatibles, $fichas, $programasFase, $existentes) {
            $guardado = $existentes[$cargo->id] ?? null;

            $cargo->compatibles = ($compatibles[$cargo->cargo_id] ?? collect())->pluck('name', 'id')->all();
            $cargo->aprendices_compatibles = $fichas->whereIn('program_id', array_keys($cargo->compatibles))->sum('aprendices');
            // Programas de la fase que se pueden habilitar como excepción: los que no son compatibles (HU-023)
            $cargo->otros_programas = $programasFase->except(array_keys($cargo->compatibles))->all();
            $documentos = $guardado ? (json_decode($guardado->documentos ?? '[]', true) ?: []) : [];
            $cargo->guardado = $guardado ? [
                'requisitos' => $guardado->requisitos,
                'documentos' => array_column($documentos, 'tipo'),
                'otro' => collect($documentos)->firstWhere('tipo', 'otro')['descripcion'] ?? '',
                'programas_extra' => json_decode($guardado->programas_extra ?? '[]', true) ?: [],
                'justificacion' => $guardado->justificacion_excepcion,
            ] : null;

            return $cargo;
        });

        return [
            'bloqueo' => null,
            'cronograma' => $cronograma,
            'convocatoria' => $convocatoria,
            'editable' => !$convocatoria || $convocatoria->estado === 'borrador',
            'fechas' => ['apertura' => $etapa?->fecha_inicio, 'cierre' => $etapa?->fecha_fin],
            'fichas' => $fichas,
            'cargos' => $cargos,
        ];
    }

    /**
     * Lee los datos de cada cargo del formulario y los valida.
     *
     * @return array{0: array<int, array>, 1: MessageBag}
     */
    private function leerCargos(Request $request, $cargos, bool $publicar): array
    {
        $errores = new MessageBag();
        $filas = [];

        foreach ($cargos as $cargo) {
            $entrada = (array) $request->input("cargos.{$cargo->id}", []);

            $documentos = array_values(array_intersect(array_keys(self::DOCUMENTOS), (array) ($entrada['documentos'] ?? [])));
            // La hoja de vida siempre es obligatoria (RN-011)
            if (!in_array('hoja_vida', $documentos)) {
                array_unshift($documentos, 'hoja_vida');
            }
            $otro = trim((string) ($entrada['otro'] ?? ''));

            // Solo se aceptan programas de la fase que no sean ya compatibles
            $extra = array_values(array_map('intval', array_intersect(
                (array) ($entrada['programas_extra'] ?? []),
                array_map('strval', array_keys($cargo->otros_programas))
            )));
            $justificacion = trim((string) ($entrada['justificacion'] ?? ''));
            $requisitos = trim((string) ($entrada['requisitos'] ?? ''));

            // HU-023 escenario 2
            if ($extra && $justificacion === '') {
                $errores->add("cargos.{$cargo->id}.justificacion", "{$cargo->nombre}: la justificación es obligatoria para habilitar otros programas.");
            }
            if (in_array('otro', $documentos) && $otro === '') {
                $errores->add("cargos.{$cargo->id}.otro", "{$cargo->nombre}: indique cuál es el otro documento.");
            }

            // HU-022 escenario 3: cada cargo con requisitos y con algún programa de la fase que pueda postularse
            if ($publicar) {
                if ($requisitos === '') {
                    $errores->add("cargos.{$cargo->id}.requisitos", "{$cargo->nombre}: describa los requisitos y las competencias mínimas.");
                }
                if ($cargo->aprendices_compatibles == 0 && !$extra) {
                    $errores->add("cargos.{$cargo->id}.programas_extra", "{$cargo->nombre}: ninguna ficha de la fase tiene un programa compatible; habilite otro programa con su justificación.");
                }
            }

            $filas[$cargo->id] = [
                'cupos' => $cargo->numero_aprendices,
                'requisitos' => $requisitos ?: null,
                'documentos' => json_encode(array_map(
                    fn ($tipo) => ['tipo' => $tipo, 'descripcion' => $tipo === 'otro' ? $otro : self::DOCUMENTOS[$tipo]],
                    $documentos
                ), JSON_UNESCAPED_UNICODE),
                'programas_extra' => $extra ? json_encode($extra) : null,
                'justificacion_excepcion' => $extra ? $justificacion : null,
            ];
        }

        return [$filas, $errores];
    }
}
