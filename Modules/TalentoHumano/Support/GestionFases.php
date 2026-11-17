<?php

namespace Modules\TalentoHumano\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\TalentoHumano\Entities\Fase;

/**
 * Registro, modificación y eliminación de fases (HU-011). Lo hacen el Administrador y el Gestor de TH con las mismas
 * reglas: número único, fechas de inicio y fin obligatorias, fin posterior al inicio y sin cruce con otra fase.
 */
class GestionFases
{
    /** Datos de la fase que impiden eliminarla, con el texto que se muestra al usuario. */
    private const DEPENDENCIAS = [
        'th_fase_fichas' => 'fichas',
        'th_cargos_activados' => 'cargos activados',
        'th_cronogramas' => 'cronograma',
        'th_convocatorias' => 'convocatoria',
        'th_asignaciones' => 'asignaciones de cargo',
        'th_cargas_iniciales' => 'cargas iniciales',
        'th_empalmes' => 'empalmes',
        'th_actas' => 'actas',
    ];

    /**
     * Valida el número y las fechas; $actual es la fase que se modifica (se excluye del número único y del cruce).
     *
     * @throws ValidationException
     */
    public static function validar(array $entrada, ?Fase $actual = null, string $bolsa = 'default'): array
    {
        $validador = Validator::make($entrada, [
            // El número es único también frente a fases eliminadas, porque la tabla lo exige
            'numero' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('th_fases', 'numero')->ignore($actual?->id)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ], [
            'required' => 'Este campo es obligatorio',
            'integer' => 'El número de la fase debe ser un número entero',
            'min' => 'El número de la fase debe ser mayor que cero',
            'date' => 'Escriba una fecha válida',
            'numero.unique' => 'Ya existe una fase con ese número',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la de inicio',
        ]);

        $validador->after(function ($v) use ($entrada, $actual) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            if ($otra = self::faseQueSeCruza($entrada['fecha_inicio'], $entrada['fecha_fin'], $actual?->id)) {
                $v->errors()->add('fecha_inicio', "Las fechas se cruzan con otra fase (Fase {$otra->numero})");
            }
        });

        if ($validador->fails()) {
            throw (new ValidationException($validador))->errorBag($bolsa);
        }

        return $validador->validated();
    }

    /**
     * Otra fase cuyas fechas se cruzan con [inicio, fin]. Una fase antigua sin fecha de fin ocupa solo su día de inicio.
     */
    public static function faseQueSeCruza(string $inicio, string $fin, ?int $excluir = null): ?Fase
    {
        return Fase::query()
            ->when($excluir, fn ($q) => $q->where('id', '!=', $excluir))
            ->where('fecha_inicio', '<=', $fin)
            ->where(fn ($q) => $q->where('fecha_fin', '>=', $inicio)->orWhere(fn ($s) => $s->whereNull('fecha_fin')->where('fecha_inicio', '>=', $inicio)))
            ->orderBy('numero')
            ->first();
    }

    public static function registrar(array $datos): Fase
    {
        $fase = Fase::create($datos + ['estado' => 'planeacion']);
        Auditoria::registrar('fase_registrada', $fase, [], self::datos($fase));

        return $fase;
    }

    public static function modificar(Fase $fase, array $datos): Fase
    {
        $antes = self::datos($fase);
        $fase->update($datos);
        Auditoria::registrar('fase_modificada', $fase, $antes, self::datos($fase->fresh()));

        return $fase;
    }

    /**
     * Lo que impide eliminar la fase (vacío si se puede eliminar).
     */
    public static function bloqueos(Fase $fase): array
    {
        return collect(self::DEPENDENCIAS)
            ->filter(fn ($texto, $tabla) => DB::table($tabla)->where('fase_id', $fase->id)->exists())
            ->values()
            ->all();
    }

    /**
     * Elimina la fase si aún no tiene datos del proceso. Se borra del todo para que su número se pueda volver a usar.
     *
     * @throws ValidationException
     */
    public static function eliminar(Fase $fase): void
    {
        if ($bloqueos = self::bloqueos($fase)) {
            throw ValidationException::withMessages([
                'fase' => "No se puede eliminar la Fase {$fase->numero} porque ya tiene " . self::enumerar($bloqueos) . '.',
            ]);
        }

        DB::transaction(function () use ($fase) {
            Auditoria::registrar('fase_eliminada', $fase, self::datos($fase), []);
            DB::table('th_alertas')->where('fase_id', $fase->id)->update(['fase_id' => null]);
            DB::table('th_designaciones')->where('fase_id', $fase->id)->update(['fase_id' => null]);
            $fase->forceDelete();
        });
    }

    private static function datos(Fase $fase): array
    {
        return [
            'numero' => $fase->numero,
            'fecha_inicio' => $fase->fecha_inicio?->toDateString(),
            'fecha_fin' => $fase->fecha_fin?->toDateString(),
        ];
    }

    private static function enumerar(array $partes): string
    {
        $ultima = array_pop($partes);

        return $partes ? implode(', ', $partes) . ' y ' . $ultima : $ultima;
    }
}
