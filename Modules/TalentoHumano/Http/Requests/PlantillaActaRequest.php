<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\TalentoHumano\Entities\Area;
use Modules\TalentoHumano\Entities\PlantillaActa;

/**
 * Datos de una plantilla de acta de recibimiento del cargo (HU-007, HU-008).
 * El editor envía secciones, columnas, lista base y firmantes como JSON.
 */
class PlantillaActaRequest extends FormRequest
{
    private const ESTRUCTURA = ['secciones', 'columnas_inventario', 'items_base', 'firmantes'];

    public const MENSAJE_INCOMPLETA = 'Debe definir al menos una columna de inventario y los firmantes requeridos';

    public function authorize(): bool
    {
        return true; // El permiso lo controla el middleware AdministradorTH
    }

    protected function prepareForValidation(): void
    {
        $datos = ['nombre' => trim((string) $this->input('nombre'))];
        foreach (self::ESTRUCTURA as $campo) {
            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (json_decode($valor, true) ?? []) : (array) $valor;
        }
        $this->merge($datos);
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'string', function ($campo, $valor, $falla) {
                if ($valor !== 'general' && !Area::whereKey($valor)->exists()) {
                    $falla('Seleccione un área de la lista');
                }
            }],
            'nombre' => ['required', 'string', 'max:150'],
            'indicaciones' => ['nullable', 'string', 'max:2000'],
            'secciones' => ['required', 'array', 'min:1'],
            'secciones.*.titulo' => ['required', 'string', 'max:120'],
            'secciones.*.fija' => ['boolean'],
            'secciones.*.campos' => ['array'],
            'secciones.*.campos.*.etiqueta' => ['required', 'string', 'max:120'],
            'secciones.*.campos.*.tipo' => ['required', Rule::in(array_keys(PlantillaActa::TIPOS_CAMPO))],
            'columnas_inventario' => ['required', 'array', 'min:1'],
            'columnas_inventario.*.nombre' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'columnas_inventario.*.tipo' => ['required', Rule::in(array_keys(PlantillaActa::TIPOS_COLUMNA))],
            'columnas_inventario.*.opciones' => ['array', 'required_if:columnas_inventario.*.tipo,lista'],
            'columnas_inventario.*.opciones.*' => ['string', 'max:60'],
            'items_base' => ['array'],
            'items_base.*.item' => ['required', 'string', 'max:255'],
            'items_base.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'items_base.*.unidad' => ['nullable', 'string', 'max:30'],
            'firmantes' => ['required', 'array', 'min:1'],
            'firmantes.*' => [Rule::in(array_keys(PlantillaActa::FIRMANTES))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio',
            'columnas_inventario.required' => self::MENSAJE_INCOMPLETA,
            'columnas_inventario.min' => self::MENSAJE_INCOMPLETA,
            'firmantes.required' => self::MENSAJE_INCOMPLETA,
            'firmantes.min' => self::MENSAJE_INCOMPLETA,
            'secciones.required' => 'El acta debe tener al menos una sección',
            'secciones.*.titulo.required' => 'Todas las secciones necesitan un título',
            'secciones.*.campos.*.etiqueta.required' => 'Todos los campos necesitan un nombre',
            'columnas_inventario.*.nombre.required' => 'Todas las columnas necesitan un nombre',
            'columnas_inventario.*.nombre.distinct' => 'Hay columnas del inventario con el mismo nombre',
            'columnas_inventario.*.opciones.required_if' => 'Las columnas de lista necesitan sus opciones',
            'items_base.*.item.required' => 'Todos los implementos necesitan un nombre',
            'items_base.*.cantidad.*' => 'La cantidad de cada implemento debe ser un número mayor que cero',
        ];
    }

    public function areaId(): ?int
    {
        return $this->input('area_id') === 'general' ? null : (int) $this->input('area_id');
    }

    /**
     * Datos normalizados de la plantilla, en el formato en que se guardan.
     */
    public function datos(): array
    {
        $v = $this->validated();

        return [
            'area_id' => $this->areaId(),
            'nombre' => $v['nombre'],
            'indicaciones' => trim((string) ($v['indicaciones'] ?? '')) ?: null,
            'secciones' => collect($v['secciones'])->map(fn ($s) => [
                'titulo' => trim($s['titulo']),
                'fija' => (bool) ($s['fija'] ?? false),
                'campos' => collect($s['campos'] ?? [])->map(fn ($c) => ['etiqueta' => trim($c['etiqueta']), 'tipo' => $c['tipo']])->values()->all(),
            ])->values()->all(),
            'columnas_inventario' => collect($v['columnas_inventario'])->map(fn ($c) => [
                'nombre' => trim($c['nombre']),
                'tipo' => $c['tipo'],
                'opciones' => $c['tipo'] === 'lista' ? collect($c['opciones'] ?? [])->map(fn ($o) => trim($o))->filter()->values()->all() : [],
            ])->values()->all(),
            'items_base' => collect($v['items_base'] ?? [])->map(fn ($i) => [
                'item' => trim($i['item']), 'cantidad' => (int) $i['cantidad'], 'unidad' => trim((string) ($i['unidad'] ?? '')) ?: null,
            ])->values()->all(),
            'firmantes' => array_values(array_intersect(array_keys(PlantillaActa::FIRMANTES), $v['firmantes'])),
        ];
    }
}
