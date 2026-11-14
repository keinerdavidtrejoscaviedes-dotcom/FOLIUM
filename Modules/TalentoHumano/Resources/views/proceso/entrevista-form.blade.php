@use('Modules\TalentoHumano\Entities\ConvocatoriaEvaluador')
@php
    [$inicio, $fin] = $etapa;
    $esTecnica = $tipo === 'tecnica';
    $titulo = $esTecnica ? 'Programar entrevista técnica' : 'Programar entrevista psicológica';
    $marcados = array_map('intval', old('postulaciones', []));
    $evaluadoresMarcados = array_map('intval', old('evaluadores', []));
    $anillo = fn ($campo) => $errors->has($campo) ? 'ring-red-400' : 'ring-slate-300';
    $claseCampo = 'mt-1 w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master :title="$titulo">
    <x-talentohumano::encabezado :titulo="$titulo" :volver="route('talentohumano.fases.entrevistas.index', $fase->id)"
        :hu="$esTecnica ? 'HU-027A · RN-015 · RN-018' : 'HU-027B · RN-015 · RN-018'"
        subtitulo="Solo se puede citar a quien ya presentó la prueba psicotécnica. La citación se envía por correo al aspirante y a los evaluadores." />

    <x-talentohumano::errores />

    <form method="POST" action="{{ route('talentohumano.fases.entrevistas.store', $fase->id) }}" class="space-y-5">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">

        {{-- Citación --}}
        <div class="grid gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Modalidad</span>
                <select name="modalidad" class="{{ $claseCampo }} {{ $anillo('modalidad') }}">
                    <option value="individual">Individual</option>
                    @unless ($esTecnica)
                        <option value="grupal" @selected(old('modalidad') === 'grupal')>Grupal</option>
                    @endunless
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Fecha</span>
                <input type="date" name="fecha" value="{{ old('fecha') }}" min="{{ $inicio?->format('Y-m-d') }}" max="{{ $fin?->format('Y-m-d') }}" class="{{ $claseCampo }} {{ $anillo('fecha') }}">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Hora</span>
                <input type="time" name="hora" value="{{ old('hora') }}" class="{{ $claseCampo }} {{ $anillo('hora') }}">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Lugar</span>
                <input type="text" name="lugar" value="{{ old('lugar') }}" maxlength="150" placeholder="Por ejemplo: Sala de juntas SENA Empresa" class="{{ $claseCampo }} {{ $anillo('lugar') }}">
            </label>
            <p class="text-xs text-slate-500 sm:col-span-2 lg:col-span-4">
                Rango de entrevistas del cronograma: {{ $inicio?->format('d/m/Y') ?? '—' }} al {{ $fin?->format('d/m/Y') ?? '—' }}.
                @unless ($esTecnica) La entrevista técnica y la psicológica pueden ser a la misma hora, en lugares distintos. @endunless
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            {{-- Aspirantes --}}
            <fieldset @class(['rounded-2xl bg-white p-5 shadow-sm ring-1', 'ring-red-300' => $errors->has('postulaciones'), 'ring-slate-200' => !$errors->has('postulaciones')])>
                <legend class="sr-only">Aspirantes</legend>
                <h2 class="font-bold text-[#064C3D]">{{ $esTecnica ? 'Aspirante' : 'Aspirantes' }}</h2>
                <p class="text-xs text-slate-500">{{ $esTecnica ? 'La entrevista técnica es individual.' : 'En la modalidad grupal elija varios aspirantes.' }}</p>
                <div class="mt-3 max-h-80 space-y-1.5 overflow-y-auto text-sm">
                    @forelse ($postulantes as $postulante)
                        <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                            <input type="{{ $esTecnica ? 'radio' : 'checkbox' }}" name="postulaciones[]" value="{{ $postulante->id }}" @checked(in_array($postulante->id, $marcados))
                                   class="{{ $esTecnica ? '' : 'rounded' }} border-slate-300 text-[#064C3D] focus:ring-[#064C3D]">
                            <span>{{ $postulante->nombre }} <span class="text-xs text-slate-500">· ficha {{ $postulante->ficha }} · {{ $postulante->programa }}</span></span>
                        </label>
                    @empty
                        <p class="text-slate-500">Ningún postulante ha presentado la prueba psicotécnica todavía.</p>
                    @endforelse
                </div>
            </fieldset>

            {{-- Evaluadores (de la lista de la convocatoria, HU-026A) --}}
            <fieldset @class(['rounded-2xl bg-white p-5 shadow-sm ring-1', 'ring-red-300' => $errors->has('evaluadores'), 'ring-slate-200' => !$errors->has('evaluadores')])>
                <legend class="sr-only">Evaluadores</legend>
                <h2 class="font-bold text-[#064C3D]">Evaluadores</h2>
                <p class="text-xs text-slate-500">
                    {{ $esTecnica ? 'Al menos un instructor técnico o el Instructor Líder.' : 'Al menos un Evaluador psicológico.' }}
                    <a href="{{ route('talentohumano.fases.evaluadores.index', $fase->id) }}" class="font-semibold text-[#064C3D] hover:underline">Evaluadores de la convocatoria</a>
                </p>
                <div class="mt-3 space-y-1.5 text-sm">
                    @forelse ($evaluadores as $evaluador)
                        <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                            <input type="checkbox" name="evaluadores[]" value="{{ $evaluador->person_id }}" @checked(in_array($evaluador->person_id, $evaluadoresMarcados))
                                   class="rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D]">
                            <span>
                                {{ trim("{$evaluador->persona?->first_name} {$evaluador->persona?->first_last_name}") }}
                                <span class="text-xs text-slate-500">· {{ ConvocatoriaEvaluador::MOTIVOS[$evaluador->motivo] }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-slate-500">
                            La convocatoria no tiene {{ $esTecnica ? 'instructores técnicos' : 'evaluadores psicológicos' }}.
                            Agréguelos en <a href="{{ route('talentohumano.fases.evaluadores.index', $fase->id) }}" class="font-semibold text-[#064C3D] hover:underline">Evaluadores de la convocatoria</a>.
                        </p>
                    @endforelse
                </div>
            </fieldset>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('talentohumano.fases.entrevistas.index', $fase->id) }}" class="inline-flex items-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-paper-plane" aria-hidden="true"></i> Guardar y enviar citación
            </button>
        </div>
    </form>
</x-talentohumano::layouts.master>
