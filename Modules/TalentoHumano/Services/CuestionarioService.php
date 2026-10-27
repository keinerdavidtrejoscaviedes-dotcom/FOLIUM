<?php

namespace Modules\TalentoHumano\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Lógica compartida de los cuestionarios (tabla th_cuestionarios):
 * la prueba psicotécnica (HU-039) y el formato de entrevista de cada evaluador (HU-040).
 *
 * Versiones (RN-041 y RN-016):
 * - si no hay cambios, no se guarda nada;
 * - si el cuestionario ya se usó (respuestas o calificaciones), los cambios crean una versión nueva
 *   y la anterior se conserva intacta;
 * - si no se ha usado, se actualiza directamente.
 */
class CuestionarioService
{
    public const TIPOS_PREGUNTA = [
        'seleccion' => 'Selección',
        'escala' => 'Escala',
        'abierta' => 'Respuesta abierta',
    ];

    /** Carpeta del disco privado (storage/app/private, RNF-007) para las imágenes de las preguntas. */
    private const CARPETA_IMAGENES = 'talentohumano/preguntas';

    /**
     * Valida el formulario y devuelve las preguntas normalizadas.
     *
     * @param  string  $modo  'prueba' (selección, escala, abierta e imágenes) o 'formato' (enunciado y puntaje)
     * @return array{titulo: ?string, preguntas: array<int, array>}
     */
    public function validar(Request $request, string $modo): array
    {
        $reglas = [
            'preguntas' => 'required|array|min:1',
            'preguntas.*.enunciado' => 'required|string|max:2000',
        ];

        if ($modo === 'prueba') {
            $reglas += [
                'titulo' => 'required|string|max:255',
                'preguntas.*.tipo' => 'required|in:' . implode(',', array_keys(self::TIPOS_PREGUNTA)),
                'preguntas.*.imagen' => 'nullable|image|max:5120',
            ];
        }

        $mensajes = [
            'titulo.required' => 'Escriba el título de la prueba.',
            'preguntas.required' => 'Agregue al menos una pregunta.',
            'preguntas.*.enunciado.required' => 'Escriba el enunciado de cada pregunta.',
            'preguntas.*.imagen.image' => 'Solo se permiten imágenes de hasta 5 MB.',
            'preguntas.*.imagen.max' => 'Solo se permiten imágenes de hasta 5 MB.',
            'preguntas.*.imagen.uploaded' => 'Solo se permiten imágenes de hasta 5 MB.',
        ];

        $validator = Validator::make($request->all(), $reglas, $mensajes);

        // Reglas que dependen del tipo de pregunta (HU-039 escenario 2 y HU-040 escenario 3)
        // Las claves pueden tener huecos (0, 2, 5...) cuando se eliminan preguntas en el editor
        $validator->after(function ($validator) use ($request, $modo) {
            $numero = 0;
            foreach ((array) $request->input('preguntas', []) as $i => $pregunta) {
                $numero++;

                if ($modo === 'formato') {
                    if (!$this->esPuntaje($pregunta['puntaje_maximo'] ?? null, true)) {
                        $validator->errors()->add("preguntas.$i.puntaje_maximo", "Pregunta $numero: asigne un puntaje numérico a la pregunta.");
                    }
                    continue;
                }

                $tipo = $pregunta['tipo'] ?? null;
                $completa = match ($tipo) {
                    'seleccion' => $this->opcionesCompletas($pregunta['opciones'] ?? []),
                    'escala' => $this->escalaCompleta($pregunta),
                    'abierta' => blank($pregunta['puntaje_maximo'] ?? null) || $this->esPuntaje($pregunta['puntaje_maximo'], false),
                    default => true,
                };

                if (!$completa) {
                    $validator->errors()->add("preguntas.$i.opciones", "Pregunta $numero: complete las opciones y el puntaje de la pregunta.");
                }
            }
        });

        $validator->validate();

        $preguntas = [];
        foreach ((array) $request->input('preguntas') as $clave => $pregunta) {
            $orden = count($preguntas) + 1;
            $preguntas[] = $modo === 'formato'
                ? $this->normalizarFormato($pregunta, $orden)
                : $this->normalizarPrueba($pregunta, $orden, $request->file("preguntas.$clave.imagen"));
        }

        return [
            'titulo' => $modo === 'prueba' ? trim($request->input('titulo')) : null,
            'preguntas' => $preguntas,
        ];
    }

    /**
     * Guarda el cuestionario aplicando las reglas de versiones.
     *
     * @return array{accion: string, id: int}  accion: creada | actualizada | nueva_version | sin_cambios
     */
    public function guardar(string $tipo, string $titulo, array $preguntas, ?object $actual, int $personaId): array
    {
        if ($actual && !$this->hayCambios($actual, $titulo, $preguntas)) {
            return ['accion' => 'sin_cambios', 'id' => $actual->id];
        }

        return DB::transaction(function () use ($tipo, $titulo, $preguntas, $actual, $personaId) {
            $preguntas = $this->guardarImagenes($preguntas);

            // Cuestionario nuevo: la prueba empieza en borrador; el formato de entrevista queda vigente de una vez
            if (!$actual) {
                $id = DB::table('th_cuestionarios')->insertGetId([
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'autor_id' => $personaId,
                    'version' => 1,
                    'estado' => $tipo === 'prueba_psicotecnica' ? 'borrador' : 'vigente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->insertarPreguntas($id, $preguntas);

                return ['accion' => 'creada', 'id' => $id];
            }

            // Ya se usó: versión nueva con el mismo estado; la anterior queda inactiva y sin cambios
            if ($this->enUso($actual)) {
                $id = DB::table('th_cuestionarios')->insertGetId([
                    'tipo' => $actual->tipo,
                    'titulo' => $titulo,
                    'autor_id' => $actual->autor_id,
                    'version' => $actual->version + 1,
                    'version_anterior_id' => $actual->id,
                    'estado' => $actual->estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->insertarPreguntas($id, $preguntas);

                DB::table('th_cuestionarios')->where('id', $actual->id)
                    ->update(['estado' => 'inactivo', 'updated_at' => now()]);

                return ['accion' => 'nueva_version', 'id' => $id];
            }

            // Sin uso: se actualiza directamente
            $imagenesAnteriores = DB::table('th_cuestionario_preguntas')->where('cuestionario_id', $actual->id)
                ->whereNotNull('imagen')->pluck('imagen')->all();

            DB::table('th_cuestionarios')->where('id', $actual->id)
                ->update(['titulo' => $titulo, 'updated_at' => now()]);
            DB::table('th_cuestionario_preguntas')->where('cuestionario_id', $actual->id)->delete();
            $this->insertarPreguntas($actual->id, $preguntas);

            $this->borrarImagenesHuerfanas(array_diff($imagenesAnteriores, array_column($preguntas, 'imagen')));

            return ['accion' => 'actualizada', 'id' => $actual->id];
        });
    }

    /**
     * Indica si el cuestionario ya se usó: la prueba, con respuestas de aprendices;
     * el formato de entrevista, con calificaciones.
     */
    public function enUso(object $cuestionario): bool
    {
        if ($cuestionario->tipo === 'prueba_psicotecnica') {
            return DB::table('th_respuestas_prueba as r')
                ->join('th_cuestionario_preguntas as p', 'p.id', '=', 'r.pregunta_id')
                ->where('p.cuestionario_id', $cuestionario->id)
                ->exists()
                || DB::table('th_aplicaciones_prueba')
                    ->where('cuestionario_id', $cuestionario->id)
                    ->whereIn('estado', ['en_curso', 'presentada'])
                    ->exists();
        }

        return DB::table('th_calificaciones')->where('cuestionario_id', $cuestionario->id)->exists();
    }

    /**
     * Preguntas guardadas de un cuestionario, con las opciones decodificadas.
     */
    public function preguntas(int $cuestionarioId): array
    {
        return DB::table('th_cuestionario_preguntas')
            ->where('cuestionario_id', $cuestionarioId)
            ->orderBy('orden')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'orden' => $p->orden,
                'enunciado' => $p->enunciado,
                'tipo' => $p->tipo,
                'opciones' => $p->opciones ? json_decode($p->opciones, true) : null,
                'puntaje_maximo' => (float) $p->puntaje_maximo,
                'imagen' => $p->imagen,
            ])
            ->all();
    }

    /**
     * Convierte las preguntas guardadas a la forma que usa el editor (los mismos campos del formulario).
     */
    public function paraEditor(array $preguntas): array
    {
        return array_map(fn ($p) => [
            'enunciado' => $p['enunciado'],
            'tipo' => $p['tipo'],
            'opciones' => $p['tipo'] === 'seleccion' ? $p['opciones'] : [],
            'escala_min' => $p['tipo'] === 'escala' ? ($p['opciones']['min'] ?? 1) : 1,
            'escala_max' => $p['tipo'] === 'escala' ? ($p['opciones']['max'] ?? 5) : 5,
            'puntaje_maximo' => $p['tipo'] === 'seleccion' ? null : $p['puntaje_maximo'],
            'imagen_actual' => $p['imagen'],
            'pregunta_id' => $p['id'],
        ], $preguntas);
    }

    /**
     * Versiones de un cuestionario, de la más reciente a la más antigua.
     */
    public function historial(object $cuestionario): array
    {
        // Primera versión: se recorre hacia atrás por version_anterior_id
        $primera = $cuestionario;
        while ($primera->version_anterior_id) {
            $primera = DB::table('th_cuestionarios')->find($primera->version_anterior_id);
        }

        // Luego hacia adelante, desde la primera
        $versiones = [$primera];
        $actual = $primera;
        while ($siguiente = DB::table('th_cuestionarios')->where('version_anterior_id', $actual->id)->first()) {
            $versiones[] = $siguiente;
            $actual = $siguiente;
        }

        return array_reverse($versiones);
    }

    /**
     * Ítems base de la entrevista (RF-009), para el botón 'Cargar ítems base' (HU-040 escenario 2).
     */
    public function itemsBase(string $tipo): array
    {
        $items = [
            'Presentación personal',
            'Tipo de comunicación (pasiva, agresiva o asertiva)',
            'Actitud',
            'Proyección personal y laboral',
        ];

        if ($tipo === 'entrevista_tecnica') {
            $items[] = 'Conocimientos técnicos del cargo';
        }

        $puntaje = round(100 / count($items), 2);

        return array_map(fn ($item) => ['enunciado' => $item, 'puntaje_maximo' => $puntaje], $items);
    }

    // ----------------------------------------------------------------------
    // Validación y normalización
    // ----------------------------------------------------------------------

    private function esPuntaje(mixed $valor, bool $mayorQueCero): bool
    {
        if (!is_numeric($valor) || $valor > 999.99) {
            return false;
        }

        return $mayorQueCero ? $valor > 0 : $valor >= 0;
    }

    /** Selección: al menos dos opciones, cada una con texto y puntaje. */
    private function opcionesCompletas(array $opciones): bool
    {
        $opciones = array_filter($opciones, fn ($o) => filled($o['texto'] ?? null) || filled($o['puntaje'] ?? null));

        if (count($opciones) < 2) {
            return false;
        }

        foreach ($opciones as $opcion) {
            if (blank($opcion['texto'] ?? null) || !$this->esPuntaje($opcion['puntaje'] ?? null, false)) {
                return false;
            }
        }

        return max(array_column($opciones, 'puntaje')) > 0;
    }

    /** Escala: valores mínimo y máximo enteros (0 a 10, mínimo menor que máximo) y puntaje mayor que cero. */
    private function escalaCompleta(array $pregunta): bool
    {
        $min = $pregunta['escala_min'] ?? null;
        $max = $pregunta['escala_max'] ?? null;

        return ctype_digit((string) $min) && ctype_digit((string) $max)
            && (int) $min < (int) $max && (int) $max <= 10
            && $this->esPuntaje($pregunta['puntaje_maximo'] ?? null, true);
    }

    private function normalizarPrueba(array $pregunta, int $orden, ?UploadedFile $imagen): array
    {
        $tipo = $pregunta['tipo'];

        $opciones = null;
        $puntaje = 0.0;

        if ($tipo === 'seleccion') {
            $opciones = collect($pregunta['opciones'] ?? [])
                ->filter(fn ($o) => filled($o['texto'] ?? null))
                ->map(fn ($o) => ['texto' => trim($o['texto']), 'puntaje' => round((float) $o['puntaje'], 2)])
                ->values()
                ->all();
            $puntaje = max(array_column($opciones, 'puntaje'));
        } elseif ($tipo === 'escala') {
            $opciones = ['min' => (int) $pregunta['escala_min'], 'max' => (int) $pregunta['escala_max']];
            $puntaje = round((float) $pregunta['puntaje_maximo'], 2);
        } elseif (filled($pregunta['puntaje_maximo'] ?? null)) {
            $puntaje = round((float) $pregunta['puntaje_maximo'], 2);
        }

        // Imagen: la nueva reemplaza a la actual; 'quitar_imagen' la elimina de la pregunta
        $imagenActual = $pregunta['imagen_actual'] ?? null;
        if ($imagenActual && !$this->esRutaDeImagen($imagenActual)) {
            $imagenActual = null;
        }

        return [
            'orden' => $orden,
            'enunciado' => trim($pregunta['enunciado']),
            'tipo' => $tipo,
            'opciones' => $opciones,
            'puntaje_maximo' => $puntaje,
            'imagen' => $imagen ?? (!empty($pregunta['quitar_imagen']) ? null : $imagenActual),
        ];
    }

    private function normalizarFormato(array $pregunta, int $orden): array
    {
        return [
            'orden' => $orden,
            'enunciado' => trim($pregunta['enunciado']),
            'tipo' => 'escala',
            'opciones' => null,
            'puntaje_maximo' => round((float) $pregunta['puntaje_maximo'], 2),
            'imagen' => null,
        ];
    }

    /** Evita que el formulario apunte a archivos fuera de la carpeta de imágenes. */
    private function esRutaDeImagen(string $ruta): bool
    {
        return str_starts_with($ruta, self::CARPETA_IMAGENES . '/') && !str_contains($ruta, '..');
    }

    // ----------------------------------------------------------------------
    // Guardado
    // ----------------------------------------------------------------------

    private function hayCambios(object $actual, string $titulo, array $preguntas): bool
    {
        if ($actual->titulo !== $titulo) {
            return true;
        }

        $comparables = fn (array $lista) => array_map(fn ($p) => [
            $p['enunciado'],
            $p['tipo'],
            $p['opciones'],
            round((float) $p['puntaje_maximo'], 2),
            $p['imagen'] instanceof UploadedFile ? 'nueva' : $p['imagen'],
        ], $lista);

        return $comparables($this->preguntas($actual->id)) != $comparables($preguntas);
    }

    /** Guarda en el disco privado las imágenes nuevas y deja su ruta en la pregunta. */
    private function guardarImagenes(array $preguntas): array
    {
        foreach ($preguntas as &$pregunta) {
            if ($pregunta['imagen'] instanceof UploadedFile) {
                $pregunta['imagen'] = $pregunta['imagen']->store(self::CARPETA_IMAGENES, 'local');
            }
        }

        return $preguntas;
    }

    private function insertarPreguntas(int $cuestionarioId, array $preguntas): void
    {
        DB::table('th_cuestionario_preguntas')->insert(array_map(fn ($p) => [
            'cuestionario_id' => $cuestionarioId,
            'orden' => $p['orden'],
            'enunciado' => $p['enunciado'],
            'tipo' => $p['tipo'],
            'opciones' => $p['opciones'] === null ? null : json_encode($p['opciones'], JSON_UNESCAPED_UNICODE),
            'imagen' => $p['imagen'],
            'puntaje_maximo' => $p['puntaje_maximo'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $preguntas));
    }

    /** Borra las imágenes que ya ninguna pregunta usa (las versiones anteriores conservan las suyas). */
    private function borrarImagenesHuerfanas(array $rutas): void
    {
        foreach (array_filter($rutas) as $ruta) {
            if (!DB::table('th_cuestionario_preguntas')->where('imagen', $ruta)->exists()) {
                Storage::disk('local')->delete($ruta);
            }
        }
    }
}
