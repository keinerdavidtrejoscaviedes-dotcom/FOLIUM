<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\TalentoHumano\Entities\Cargo;

/**
 * Datos de un cargo del catálogo (HU-003 a HU-006).
 */
class CargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El permiso lo controla el middleware AdministradorTH
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim(preg_replace('/\s+/', ' ', (string) $this->input('nombre'))),
            'programas' => array_values(array_filter((array) $this->input('programas', []), fn ($p) => !empty($p['program_id']))),
            'requisitos_adicionales' => array_values(array_filter((array) $this->input('requisitos_adicionales', []), fn ($r) => trim($r['descripcion'] ?? '') !== '')),
        ]);
    }

    public function rules(): array
    {
        $cargo = $this->route('cargo');
        $esGerente = $this->input('nivel') === 'Gerente';

        return [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('th_cargos', 'nombre')->ignore($cargo?->id)],
            'nivel' => ['required', Rule::in(array_keys(Cargo::NIVELES))],
            'area_id' => ['required', Rule::exists('th_areas', 'id')->whereNull('deleted_at')],
            'jefe_tipo' => [Rule::requiredIf(!$esGerente), 'nullable', Rule::in(['cargo', 'alta_direccion'])],
            'cargo_superior_id' => [Rule::requiredIf(!$esGerente && $this->input('jefe_tipo') === 'cargo'), 'nullable',
                Rule::exists('th_cargos', 'id')->whereNull('deleted_at'), Rule::notIn([$cargo?->id])],
            'jefe_inmediato_texto' => [Rule::requiredIf(!$esGerente && $this->input('jefe_tipo') === 'alta_direccion'), 'nullable', 'string', 'max:150'],
            'numero_aprendices' => ['required', 'integer', 'min:1', 'max:50'],
            'macroproceso' => ['nullable', 'string', 'max:100'],
            'proceso' => ['nullable', 'string', 'max:150'],
            'subproceso' => ['nullable', 'string', 'max:150'],
            'rol_principal' => ['nullable', 'string', 'max:5000'],
            'actividades' => ['nullable', 'string', 'max:10000'],
            'responsabilidades_sig' => ['nullable', 'string', 'max:10000'],
            'documentacion' => ['nullable', 'string', 'max:5000'],
            'requisitos' => ['nullable', 'string', 'max:5000'],
            'programas' => ['array'],
            'programas.*.program_id' => ['distinct', Rule::exists('programs', 'id')->whereNull('deleted_at')],
            'programas.*.tipo' => ['required', Rule::in(array_keys(Cargo::TIPOS_PROGRAMA))],
            'requisitos_adicionales' => ['array'],
            'requisitos_adicionales.*.descripcion' => ['required', 'string', 'max:255'],
            'requisitos_adicionales.*.exige_soporte' => ['nullable', 'boolean'],
        ];
    }

    /**
     * El jefe inmediato debe ser un cargo de nivel superior (HU-004, esc. 2; HU-006, esc. 5).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('nivel') === 'Gerente' || $this->input('jefe_tipo') !== 'cargo' || !$this->input('cargo_superior_id')) {
                    return;
                }
                $jefe = Cargo::find($this->input('cargo_superior_id'));
                if ($jefe && isset(Cargo::NIVELES[$this->input('nivel')]) && Cargo::rango($jefe->nivel) >= Cargo::rango($this->input('nivel'))) {
                    $validator->errors()->add('cargo_superior_id', 'El jefe inmediato debe ser un cargo de nivel superior');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio',
            'nombre.unique' => 'Ya existe un cargo con ese nombre en el catálogo',
            'cargo_superior_id.not_in' => 'Un cargo no puede ser su propio jefe inmediato',
            'programas.*.program_id.distinct' => 'Este tecnólogo ya está en la lista',
            'numero_aprendices.min' => 'Debe ser al menos 1',
            'numero_aprendices.max' => 'No puede ser mayor que 50',
        ];
    }

    /**
     * Datos del cargo normalizados para guardar y para comparar con lo almacenado.
     */
    public function datosCargo(): array
    {
        $datos = $this->safe()->only([
            'nombre', 'nivel', 'area_id', 'numero_aprendices', 'macroproceso', 'proceso', 'subproceso',
            'rol_principal', 'actividades', 'responsabilidades_sig', 'documentacion', 'requisitos',
        ]);
        $datos['area_id'] = (int) $datos['area_id'];
        $datos['numero_aprendices'] = (int) $datos['numero_aprendices'];

        if ($datos['nivel'] === 'Gerente') {
            $datos['cargo_superior_id'] = null;
            $datos['jefe_inmediato_texto'] = Cargo::JEFE_GERENTE;
        } elseif ($this->input('jefe_tipo') === 'cargo') {
            $datos['cargo_superior_id'] = (int) $this->input('cargo_superior_id');
            $datos['jefe_inmediato_texto'] = null;
        } else {
            $datos['cargo_superior_id'] = null;
            $datos['jefe_inmediato_texto'] = trim($this->input('jefe_inmediato_texto'));
        }

        return array_map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v, $datos);
    }

    /**
     * Tecnólogos compatibles como [program_id => tipo].
     */
    public function datosProgramas(): array
    {
        return collect($this->validated('programas', []))->mapWithKeys(fn ($p) => [(int) $p['program_id'] => $p['tipo']])->sortKeys()->all();
    }

    /**
     * Requisitos no verificables como lista de [descripcion, exige_soporte].
     */
    public function datosRequisitos(): array
    {
        return collect($this->validated('requisitos_adicionales', []))
            ->map(fn ($r) => ['descripcion' => trim($r['descripcion']), 'exige_soporte' => (bool) ($r['exige_soporte'] ?? false)])
            ->all();
    }
}
