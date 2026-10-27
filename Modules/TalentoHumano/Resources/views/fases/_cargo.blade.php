{{--
    Fila de un cargo en la activación (HU-019). Recibe $cargo y $esOtro (true en "Agregar otros cargos").
    Usa también $hayOld, $subordinados y $bloqueada de la vista cargos.blade.php.
--}}
@php
    $activo = $hayOld ? (bool) old("cargos.{$cargo->id}.activo") : $cargo->activo;
    $numero = $hayOld ? old("cargos.{$cargo->id}.numero") : $cargo->numero;
    $sinArea = !$cargo->area_id;
    $error = $errors->first("cargos.{$cargo->id}.numero") ?: $errors->first("cargos.{$cargo->id}.activo");
    $niveles = ['Gerente' => 'Gerente', 'Gestor' => 'Gestor', 'Lider' => 'Líder', 'Equipo' => 'Equipo'];
@endphp
<div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 {{ $error ? 'bg-red-50' : '' }}"
     data-cargo data-nombre="{{ mb_strtolower($cargo->nombre) }}" data-titulo="{{ $cargo->nombre }}"
     data-gestor="{{ $cargo->nivel === 'Gestor' ? 1 : 0 }}" data-subordinados="{{ json_encode($subordinados[$cargo->id] ?? []) }}">
    <label class="flex min-w-0 flex-1 items-start gap-3">
        <input type="checkbox" name="cargos[{{ $cargo->id }}][activo]" value="1" data-activo
               class="mt-1 h-4 w-4 rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D]"
               @checked($activo) @disabled($bloqueada || $sinArea)>
        <span class="min-w-0">
            <span class="font-semibold text-slate-900">{{ $cargo->nombre }}</span>
            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $niveles[$cargo->nivel] ?? $cargo->nivel }}</span>
            @if ($esOtro)
                <span class="ml-1 text-xs font-semibold text-[#064C3D]">Agregar</span>
            @endif
            <span class="block text-xs text-slate-500">
                @if ($sinArea)
                    <span class="text-amber-700">El cargo no tiene nivel jerárquico o área funcional definidos. Solicite al Administrador completarlos.</span>
                @elseif ($cargo->programas)
                    Compatible con: {{ implode(', ', $cargo->programas) }}
                @elseif ($cargo->nivel === 'Gestor')
                    Nivel Gestor: se propone en todas las fases porque recibe los empalmes de su área.
                @elseif (!$esOtro)
                    Agregado manualmente: en la convocatoria se podrán habilitar otros programas para este cargo.
                @else
                    {{ $cargo->area ?? 'Sin área' }}
                @endif
            </span>
            @if ($error)
                <span class="mt-1 block text-xs font-semibold text-red-700">{{ $error }}</span>
            @endif
        </span>
    </label>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        Aprendices
        <input type="number" name="cargos[{{ $cargo->id }}][numero]" value="{{ $numero }}" min="1" max="255" step="1" data-numero
               @class(['w-20 rounded-xl border-0 px-3 py-1.5 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D]', 'ring-red-400' => $errors->has("cargos.{$cargo->id}.numero"), 'ring-slate-300' => !$errors->has("cargos.{$cargo->id}.numero")])
               @disabled($bloqueada || $sinArea)>
    </label>
</div>
