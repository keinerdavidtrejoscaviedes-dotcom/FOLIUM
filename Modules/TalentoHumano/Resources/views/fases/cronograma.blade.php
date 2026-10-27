@use('Modules\TalentoHumano\Http\Controllers\CronogramaController')
@php
    $claseCampo = 'w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D] disabled:bg-slate-50 disabled:text-slate-500';
    $fecha = fn ($etapa, $campo) => old("etapas.$etapa.$campo", $etapas[$etapa]->{$campo === 'inicio' ? 'fecha_inicio' : 'fecha_fin'} ?? '');
@endphp
<x-talentohumano::layouts.master title="Cronograma · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Cronograma del proceso de selección · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-020 · RF-003 · RN-006"
        subtitulo="Defina la fecha de cada etapa. Una vez aprobado por el Instructor Líder, el cronograma será visible para los aprendices de las fichas habilitadas y sus fechas marcarán los plazos de cada etapa.">
        @if ($cronograma)
            <x-talentohumano::estado :valor="$cronograma->estado" :etiqueta="CronogramaController::ESTADOS[$cronograma->estado]" class="text-sm" />
        @endif
    </x-talentohumano::encabezado>

    @if ($resumen['cargos'] === 0)
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-lock mr-1" aria-hidden="true"></i>
            La activación de cargos es prerrequisito del cronograma. Puede guardar un borrador, pero no enviarlo a aprobación.
            <a href="{{ route('talentohumano.fases.cargos.edit', $fase->id) }}" class="font-semibold underline">Ir a Activación de cargos</a>
        </div>
    @endif

    @if ($cronograma?->estado === 'enviado')
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-hourglass-half mr-1" aria-hidden="true"></i>
            El cronograma está en aprobación del Instructor Líder. No se puede modificar mientras tanto.
        </div>
    @elseif ($cronograma?->estado === 'aprobado')
        <div class="mb-6 rounded-2xl bg-[#E8F3EF] p-4 text-sm text-[#064C3D]">
            <i class="fas fa-circle-check mr-1" aria-hidden="true"></i>
            Cronograma aprobado{{ $cronograma->fecha_aprobacion ? ' el ' . \Illuminate\Support\Carbon::parse($cronograma->fecha_aprobacion)->format('d/m/Y') : '' }}.
            Ya se puede preparar la convocatoria.
        </div>
    @elseif ($cronograma?->estado === 'rechazado')
        <div class="mb-6 rounded-2xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            <p class="font-semibold"><i class="fas fa-rotate-left mr-1" aria-hidden="true"></i> El Instructor Líder devolvió el cronograma para ajustes.</p>
            <p class="mt-1">Motivo: {{ $cronograma->motivo_rechazo }}</p>
        </div>
    @endif

    <x-talentohumano::errores />

    <form method="POST" action="{{ route('talentohumano.fases.cronograma.update', $fase->id) }}">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="hidden grid-cols-[1fr_11rem_11rem] gap-4 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:grid">
                <span>Etapa</span><span>Desde</span><span>Hasta</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach (CronogramaController::ETAPAS as $clave => $etapa)
                    @php $errorEtapa = $errors->get("etapas.$clave"); @endphp
                    <div @class(['grid gap-3 px-5 py-4 md:grid-cols-[1fr_11rem_11rem] md:items-center md:gap-4', 'bg-red-50' => $errorEtapa])>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $etapa['nombre'] }}</p>
                            @foreach ($errorEtapa as $mensaje)
                                <p class="text-xs font-semibold text-red-700">{{ $mensaje }}</p>
                            @endforeach
                        </div>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-500 md:sr-only">{{ $etapa['rango'] ? 'Desde' : 'Fecha' }}</span>
                            <input type="date" name="etapas[{{ $clave }}][inicio]" value="{{ $fecha($clave, 'inicio') }}"
                                   @class([$claseCampo, 'ring-red-400' => $errorEtapa, 'ring-slate-300' => !$errorEtapa]) @disabled(!$editable)>
                        </label>
                        @if ($etapa['rango'])
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-500 md:sr-only">Hasta</span>
                                <input type="date" name="etapas[{{ $clave }}][fin]" value="{{ $fecha($clave, 'fin') }}"
                                       @class([$claseCampo, 'ring-red-400' => $errorEtapa, 'ring-slate-300' => !$errorEtapa]) @disabled(!$editable)>
                            </label>
                        @else
                            <span class="hidden text-sm text-slate-400 md:block">Una sola fecha</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if ($editable)
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="submit" name="accion" value="borrador" formnovalidate
                        class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
                    <i class="fas fa-floppy-disk" aria-hidden="true"></i> Guardar borrador
                </button>
                <button type="submit" name="accion" value="enviar"
                        class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar a aprobación
                </button>
            </div>
        @endif
    </form>
</x-talentohumano::layouts.master>
