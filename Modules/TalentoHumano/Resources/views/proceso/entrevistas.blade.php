@use('Modules\TalentoHumano\Http\Controllers\ProgramacionEntrevistasController')
<x-talentohumano::layouts.master title="Entrevistas · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Entrevistas · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-027A · HU-027B · HU-027C · RN-015"
        subtitulo="Programe las entrevistas técnica y psicológica de los postulantes que presentaron la prueba. Cada citación llega por correo al aspirante y a sus evaluadores.">
        @if (!$bloqueo && !$soloConsulta)
            <a href="{{ route('talentohumano.fases.entrevistas.create', [$fase->id, 'tipo' => 'tecnica']) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Entrevista técnica
            </a>
            <a href="{{ route('talentohumano.fases.entrevistas.create', [$fase->id, 'tipo' => 'psicologica']) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-slate-300 hover:bg-slate-50">
                <i class="fas fa-plus" aria-hidden="true"></i> Entrevista psicológica
            </a>
        @endif
    </x-talentohumano::encabezado>

    @if ($bloqueo)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> {{ $bloqueo }}
        </div>
    @else
        @php [$inicio, $fin] = $etapa; @endphp
        <p class="mb-4 text-sm text-slate-600">
            Rango de entrevistas del cronograma: <strong>{{ $inicio?->format('d/m/Y') ?? '—' }}</strong> al <strong>{{ $fin?->format('d/m/Y') ?? '—' }}</strong>.
            @if ($sinPrueba)
                · {{ $sinPrueba }} postulantes aún no tienen la prueba presentada y no se pueden citar.
            @endif
        </p>

        @if ($porCitar->isNotEmpty())
            <div class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-[#064C3D]">Por citar ({{ $porCitar->count() }})</h2>
                <ul class="mt-3 flex flex-wrap gap-2 text-sm">
                    @foreach ($porCitar as $postulante)
                        <li class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                            {{ $postulante->nombre }}
                            <span class="text-xs text-slate-500">· falta {{ collect(['técnica' => $postulante->falta_tecnica, 'psicológica' => $postulante->falta_psicologica])->filter()->keys()->implode(' y ') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-4">
            @forelse ($entrevistas as $entrevista)
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-slate-900">
                                Entrevista {{ mb_strtolower(ProgramacionEntrevistasController::TIPOS[$entrevista->tipo]) }}
                                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($entrevista->modalidad) }}</span>
                            </h2>
                            <p class="text-sm text-slate-600">
                                <i class="fas fa-calendar-day mr-1" aria-hidden="true"></i>{{ $entrevista->fecha_hora->translatedFormat('l j \d\e F, g:i a') }}
                                · <i class="fas fa-location-dot mr-1" aria-hidden="true"></i>{{ $entrevista->lugar }}
                            </p>
                        </div>
                        @if (!$soloConsulta && !isset($conCalificaciones[$entrevista->id]))
                            <form method="POST" action="{{ route('talentohumano.fases.entrevistas.destroy', [$fase->id, $entrevista->id]) }}"
                                  onsubmit="return confirm('¿Cancelar esta entrevista? Se avisará por correo al aspirante y a los evaluadores.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600">Cancelar</button>
                            </form>
                        @endif
                    </div>

                    {{-- Seguimiento (HU-027C): cuántos evaluadores calificaron y cuáles faltan, sin puntajes --}}
                    <div class="mt-3 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-100">
                        @foreach ($entrevista->postulaciones as $postulacion)
                            @php
                                $hechas = $calificaron[$entrevista->id . '-' . $postulacion->id] ?? [];
                                $faltan = $entrevista->evaluadores->whereNotIn('person_id', $hechas);
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                                <span class="font-semibold text-slate-800">{{ $postulacion->nombre_completo }}</span>
                                <span @class(['text-xs font-semibold', 'text-emerald-700' => $faltan->isEmpty(), 'text-amber-700' => $faltan->isNotEmpty()])>
                                    {{ count($hechas) }} de {{ $entrevista->evaluadores->count() }} evaluadores calificaron
                                    @if ($faltan->isNotEmpty())
                                        · faltan: {{ $faltan->map(fn ($e) => trim("{$e->person?->first_name} {$e->person?->first_last_name}"))->implode(', ') }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">Aún no hay entrevistas programadas.</div>
            @endforelse
        </div>
    @endif
</x-talentohumano::layouts.master>
