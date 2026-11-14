@php
    $estados = [
        'validado' => ['Validado', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'propuesta' => ['Propuesta lista para validar', 'bg-sky-50 text-sky-800 ring-sky-200'],
        'empate' => ['Empate por resolver', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'vacante' => ['Vacante: nadie alcanza el umbral', 'bg-slate-100 text-slate-600 ring-slate-200'],
        'sin_candidatos' => ['Sin candidatos evaluados', 'bg-slate-100 text-slate-500 ring-slate-200'],
    ];
@endphp
<x-talentohumano::layouts.master title="Propuesta de selección · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Propuesta de selección · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-034 · RF-010 · RN-019 · RN-020"
        subtitulo="Cuando todas las entrevistas estén calificadas, calcule la propuesta: el sistema suma la prueba y el promedio de las entrevistas, respeta el orden de preferencia y la envía a validación. Usted no ve los puntajes." />

    @if (!$convocatoria)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> Primero publique la convocatoria de la fase.
        </div>
    @else
        @if ($pendientes->isNotEmpty())
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200" role="alert">
                <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i> Calificaciones pendientes ({{ $pendientes->count() }}): no se puede calcular todavía</p>
                <ul class="mt-2 list-disc space-y-0.5 pl-5">
                    @foreach ($pendientes as $pendiente)
                        <li>{{ $pendiente }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @unless ($soloConsulta)
            <form method="POST" action="{{ route('talentohumano.fases.seleccion.calcular', $fase->id) }}" class="mb-6">
                @csrf
                <button type="submit" @disabled($pendientes->isNotEmpty())
                        class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F] disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-calculator" aria-hidden="true"></i> {{ $propuestaGuardada ? 'Volver a calcular selección' : 'Calcular selección' }}
                </button>
            </form>
        @endunless

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Cargo</th><th class="px-4 py-3">Cupos</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Aprendices propuestos</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($cargos as $cargo)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $cargo->nombre }}</td>
                            <td class="px-4 py-3">{{ $cargo->cupos }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $estados[$cargo->estado][1] }}">{{ $estados[$cargo->estado][0] }}</span>
                                @if ($cargo->sin_aptos)
                                    <span class="block text-xs text-slate-500">Nadie alcanzó el umbral: se propone al de mayor puntaje.</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ implode(', ', $cargo->propuestos) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($noEvaluados->isNotEmpty())
            <div class="mt-6 rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-[#064C3D]">No evaluados ({{ $noEvaluados->count() }})</h2>
                <p class="text-xs text-slate-500">No presentaron la prueba psicotécnica a tiempo: quedan por fuera de la selección.</p>
                <p class="mt-2 text-slate-700">{{ $noEvaluados->implode(', ') }}</p>
            </div>
        @endif
    @endif
</x-talentohumano::layouts.master>
