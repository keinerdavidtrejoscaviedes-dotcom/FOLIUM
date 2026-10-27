<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\TalentoHumano\Entities\Cargo;
use Modules\TalentoHumano\Entities\Fase;
use RuntimeException;

/**
 * Plantilla de Excel y validación de la carga masiva de asignaciones iniciales (HU-013, esc. 4 a 6).
 */
class CargaMasiva
{
    public const COLUMNAS = ['documento', 'cargo', 'fecha_inicio', 'unidad_productiva'];

    public const MENSAJE_FORMATO = 'El archivo no corresponde a la plantilla de carga inicial';

    /**
     * Libro .xlsx para diligenciar: hoja de asignaciones vacía y hojas de consulta con los nombres exactos.
     */
    public static function plantilla(): string
    {
        $cargos = Cargo::with('area')->where('estado', 'activo')->orderBy('nombre')->get()
            ->map(fn ($c) => [$c->nombre, Cargo::NIVELES[$c->nivel], $c->area?->nombre ?? '']);

        return HojaExcel::escribir([
            'Asignaciones' => [self::COLUMNAS],
            'Cargos activos' => array_merge([['Nombre exacto del cargo', 'Nivel', 'Área']], $cargos->all()),
            'Unidades productivas' => array_merge([['Nombre exacto de la unidad']], DB::table('productive_units')->whereNull('deleted_at')->orderBy('name')->pluck('name')->map(fn ($n) => [$n])->all()),
            'Instrucciones' => [
                ['Cómo diligenciar la hoja Asignaciones'],
                ['Una fila por aprendiz que ya ocupa un cargo en la fase en curso.'],
                ['documento: número de documento del aprendiz, sin puntos (por ejemplo 1075276540).'],
                ['cargo: nombre exacto del cargo, como aparece en la hoja Cargos activos.'],
                ['fecha_inicio: fecha en que empezó en el cargo, en formato dd/mm/aaaa (por ejemplo 04/08/2026).'],
                ['unidad_productiva: opcional. Nombre exacto de la unidad; si son varias, sepárelas con coma.'],
                ['No cambie los nombres de las columnas ni el orden de las hojas.'],
            ],
        ]);
    }

    /**
     * Valida las filas del archivo. Devuelve las correctas (listas para crear) y las que tienen error con su motivo.
     *
     * @throws RuntimeException con MENSAJE_FORMATO si no tiene las columnas de la plantilla
     */
    public static function validar(array $filas, Fase $fase): array
    {
        $encabezado = array_map(fn ($v) => Str::lower(trim($v)), (array) reset($filas));
        $posicion = array_flip($encabezado);
        if (array_diff(['documento', 'cargo', 'fecha_inicio'], $encabezado)) {
            throw new RuntimeException(self::MENSAJE_FORMATO);
        }
        $numeroEncabezado = array_key_first($filas);

        $cargos = Cargo::all()->keyBy(fn ($c) => Str::lower($c->nombre));
        $unidades = DB::table('productive_units')->whereNull('deleted_at')->pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [Str::lower($n) => $id]);
        $correctas = $errores = $vistos = [];

        foreach ($filas as $numero => $fila) {
            if ($numero === $numeroEncabezado || !array_filter($fila, fn ($v) => trim($v) !== '')) {
                continue;
            }
            $valor = fn (string $columna) => trim($fila[$posicion[$columna] ?? -1] ?? '');
            $documento = preg_replace('/\D/', '', $valor('documento'));
            $nombreCargo = $valor('cargo');
            $aprendiz = $documento ? Aprendices::porDocumento($documento) : null;
            $cargo = $cargos[Str::lower($nombreCargo)] ?? null;
            $fecha = HojaExcel::fecha($valor('fecha_inicio'));
            $nombresUnidades = array_filter(array_map('trim', explode(',', $valor('unidad_productiva'))));
            $faltantes = array_filter($nombresUnidades, fn ($u) => !isset($unidades[Str::lower($u)]));
            $ocupada = $aprendiz ? GestionAsignaciones::ocupada($aprendiz->id, $fase->id) : null;

            $motivo = match (true) {
                $documento === '' => 'Falta el documento del aprendiz.',
                !$aprendiz => 'El documento no existe en SICEFA.',
                !$aprendiz->aprendiz_id => 'La persona no está registrada como aprendiz en SICEFA.',
                $nombreCargo === '' => 'Falta el cargo.',
                !$cargo => 'El cargo no existe en el catálogo.',
                $cargo->estado !== 'activo' => 'El cargo está inactivo en el catálogo.',
                !$fecha => 'La fecha de inicio no es válida (use dd/mm/aaaa).',
                (bool) $faltantes => 'La unidad productiva «' . reset($faltantes) . '» no existe.',
                (bool) $ocupada => "Ya tiene el cargo {$ocupada?->cargo->nombre} en la {$fase->nombre()}.",
                isset($vistos[$aprendiz->id]) => "El aprendiz ya aparece en la fila {$vistos[$aprendiz->id]} del archivo.",
                default => null,
            };
            if ($motivo) {
                $errores[] = ['fila' => $numero, 'documento' => $documento ?: $valor('documento'), 'cargo' => $nombreCargo, 'motivo' => $motivo];
                continue;
            }

            $vistos[$aprendiz->id] = $numero;
            $correctas[] = [
                'fila' => $numero, 'person_id' => $aprendiz->id, 'documento' => $aprendiz->documento, 'nombre' => $aprendiz->nombre,
                'cargo_id' => $cargo->id, 'cargo' => $cargo->nombre, 'fecha_inicio' => $fecha->toDateString(),
                'unidades' => array_values(array_map(fn ($u) => $unidades[Str::lower($u)], $nombresUnidades)),
            ];
        }

        return ['correctas' => $correctas, 'errores' => $errores];
    }
}
