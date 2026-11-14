@use('Modules\TalentoHumano\Entities\ConvocatoriaEvaluador')
@php
    $claseCampo = 'w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master title="Evaluadores · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Evaluadores de la convocatoria · Fase {{ $fase->numero }}" hu="HU-026A · HU-026B · RN-042"
        :volver="auth()->user() && \Modules\TalentoHumano\Support\AccesoTH::puede(auth()->user(), 'activacion') ? route('talentohumano.fases.show', $fase->id) : route('talentohumano.panel')"
        subtitulo="Instructores y psicólogos que apoyarán las entrevistas. El sistema incluye al Instructor Líder y a los instructores que lideran las fichas de la fase; si alguno no está en el centro, se puede retirar indicando el motivo." />

    @if (!$convocatoria)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> Primero publique la convocatoria de la fase.
        </div>
    @else
        <x-talentohumano::errores />

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Evaluador</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Por qué está</th><th class="px-4 py-3">Entrevistas</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($evaluadores as $evaluador)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ trim("{$evaluador->persona?->first_name} {$evaluador->persona?->first_last_name} {$evaluador->persona?->second_last_name}") }}</td>
                                <td class="px-4 py-3">{{ ConvocatoriaEvaluador::TIPOS[$evaluador->tipo] }}</td>
                                <td class="px-4 py-3">
                                    @if ($evaluador->incluidoPorSistema())
                                        <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-xs font-semibold text-[#064C3D]">{{ ConvocatoriaEvaluador::MOTIVOS[$evaluador->motivo] }}</span>
                                    @else
                                        {{ ConvocatoriaEvaluador::MOTIVOS[$evaluador->motivo] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $entrevistas[$evaluador->person_id] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right">
                                    @unless ($soloConsulta)
                                        <form method="POST" action="{{ route('talentohumano.fases.evaluadores.destroy', [$fase->id, $evaluador->id]) }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            @method('DELETE')
                                            @if ($evaluador->incluidoPorSistema())
                                                {{-- Lo incluyó el sistema: se retira con motivo y queda en la lista de retirados --}}
                                                <input type="text" name="motivo_retiro" maxlength="255" placeholder="Motivo del retiro" aria-label="Motivo del retiro"
                                                       @class(['w-44 rounded-xl border-0 px-3 py-1.5 text-xs ring-1 focus:ring-2 focus:ring-[#064C3D]', 'ring-red-400' => $errors->has("motivo_retiro.{$evaluador->id}"), 'ring-slate-300' => !$errors->has("motivo_retiro.{$evaluador->id}")])>
                                            @endif
                                            <button type="submit" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600">Quitar</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                Aún no hay evaluadores. No hay un Instructor Líder designado ni instructores registrados en las fichas de la fase.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($retirados->isNotEmpty())
                    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <h2 class="text-sm font-bold text-slate-600">Retirados de la convocatoria</h2>
                        <ul class="mt-2 divide-y divide-slate-200 text-sm">
                            @foreach ($retirados as $retirado)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <span>
                                        <span class="font-semibold text-slate-700">{{ trim("{$retirado->persona?->first_name} {$retirado->persona?->first_last_name}") }}</span>
                                        <span class="text-xs text-slate-500">· {{ ConvocatoriaEvaluador::MOTIVOS[$retirado->motivo] }} · {{ $retirado->motivo_retiro }} · {{ $retirado->retirado_en->format('d/m/Y') }}</span>
                                    </span>
                                    @unless ($soloConsulta)
                                        <form method="POST" action="{{ route('talentohumano.fases.evaluadores.restaurar', [$fase->id, $retirado->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-full px-3 py-1 text-xs font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">Volver a incluir</button>
                                        </form>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @unless ($soloConsulta)
                <div class="h-fit space-y-4">
                    {{-- Escenarios 1 y 3: instructores técnicos, primero los del mismo programa --}}
                    <form method="POST" action="{{ route('talentohumano.fases.evaluadores.store', $fase->id) }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        @csrf
                        <input type="hidden" name="tipo" value="tecnico">
                        <h2 class="font-bold text-[#064C3D]">Agregar instructor técnico</h2>
                        <select name="person_id" required aria-label="Instructor técnico" class="{{ $claseCampo }} mt-3">
                            <option value="">Seleccione…</option>
                            @foreach ($tecnicos as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->nombre }}{{ $instructor->mismo_programa ? ' · programa de la fase' : '' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                            <i class="fas fa-plus" aria-hidden="true"></i> Agregar
                        </button>
                    </form>

                    {{-- Escenarios 4 y 5: psicólogos --}}
                    <form method="POST" action="{{ route('talentohumano.fases.evaluadores.store', $fase->id) }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        @csrf
                        <input type="hidden" name="tipo" value="psicologico">
                        <h2 class="font-bold text-[#064C3D]">Agregar evaluador psicológico</h2>
                        @if ($hayPsicologos)
                            <select name="person_id" required aria-label="Evaluador psicológico" class="{{ $claseCampo }} mt-3">
                                <option value="">Seleccione…</option>
                                @foreach ($psicologos as $psicologo)
                                    <option value="{{ $psicologo->id }}">{{ $psicologo->nombre }}</option>
                                @endforeach
                            </select>
                        @else
                            <p class="mt-2 text-sm text-amber-800">No hay psicólogos disponibles.</p>
                        @endif
                        <button type="submit" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-slate-300 hover:bg-slate-50">
                            <i class="fas fa-plus" aria-hidden="true"></i> Agregar evaluador psicológico
                        </button>
                    </form>
                </div>
            @endunless
        </div>
    @endif
</x-talentohumano::layouts.master>
