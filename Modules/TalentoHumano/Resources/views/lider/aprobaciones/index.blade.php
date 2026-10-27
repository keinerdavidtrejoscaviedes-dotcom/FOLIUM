@php
    $persona = fn ($p) => $p ? trim($p->first_name . ' ' . $p->first_last_name) : 'Sin registro';
    $chip = ['aprobado' => ['Aprobado', 'bg-emerald-100 text-emerald-800'], 'rechazado' => ['Rechazado', 'bg-amber-100 text-amber-800']];
    $tarjetas = [
        ['#cronogramas', 'fa-calendar-days', 'Cronogramas por aprobar', $cronogramas->count()],
        [route('talentohumano.seleccion.index'), 'fa-user-check', 'Propuestas de selección por validar', $selecciones],
        [route('talentohumano.seleccion.reubicaciones'), 'fa-arrows-rotate', 'Aprendices por reubicar', $reubicar],
    ];
    $sinPendientes = $cronogramas->isEmpty() && !$selecciones && !$reubicar;
@endphp

<x-talentohumano::layouts.lider title="Aprobaciones pendientes" activo="aprobaciones">
    <div class="flex items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-xl text-[#34D399] shadow-lg shadow-[#064C3D]/20"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Aprobaciones pendientes</h1>
            <p class="mt-0.5 text-sm text-slate-600">Lo que espera tu decisión como Instructor Líder.</p>
        </div>
    </div>

    @if ($sinPendientes)
        {{-- HU-034, esc. 7 --}}
        <div class="mt-6 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
            <i class="fas fa-circle-check mb-2 block text-3xl text-emerald-500" aria-hidden="true"></i>
            <p class="font-semibold text-slate-700">No tiene aprobaciones pendientes</p>
            <p class="mt-1 text-sm text-slate-500">Te avisaremos cuando el Gestor envíe un cronograma o el sistema genere la propuesta de selección.</p>
        </div>
    @else
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            @foreach ($tarjetas as [$enlace, $icono, $texto, $cantidad])
                <a href="{{ $enlace }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:ring-[#34D399]">
                    <p class="text-xs text-slate-500"><i class="fas {{ $icono }} mr-1" aria-hidden="true"></i>{{ $texto }}</p>
                    <p class="text-2xl font-extrabold text-[#064C3D]">{{ $cantidad }}</p>
                </a>
            @endforeach
        </div>

        @if ($cronogramas->isNotEmpty())
            <section id="cronogramas" class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D]"><i class="fas fa-calendar-days mr-2 text-emerald-600" aria-hidden="true"></i>Cronograma del proceso de selección</h2>
                @foreach ($cronogramas as $cronograma)
                    <a href="{{ route('talentohumano.lider.aprobaciones.cronograma', $cronograma) }}" class="mt-4 flex flex-wrap items-center gap-4 rounded-2xl bg-[#E8F3EF]/60 p-4 transition hover:bg-[#E8F3EF]">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#064C3D] text-[#34D399]"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-800">Convocatoria {{ $cronograma->fase->nombre_completo }} · {{ $cronograma->etapas_count }} etapas</p>
                            <p class="text-xs text-slate-500">Enviado el {{ $cronograma->updated_at->format('d/m/Y') }} por {{ $persona($cronograma->enviadoPor) }} (Gestor de Talento Humano)</p>
                        </div>
                        <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800">En aprobación</span>
                        <span class="rounded-full bg-[#064C3D] px-4 py-1.5 text-xs font-semibold text-white">Revisar</span>
                    </a>
                @endforeach
            </section>
        @endif
    @endif

    @if ($decididos->isNotEmpty())
        <section class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]"><i class="fas fa-clock-rotate-left mr-2 text-emerald-600" aria-hidden="true"></i>Cronogramas decididos</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($decididos as $cronograma)
                    <li>
                        <a href="{{ route('talentohumano.lider.aprobaciones.cronograma', $cronograma) }}" class="flex flex-wrap items-center gap-3 py-3 hover:text-[#064C3D]">
                            <span class="min-w-0 flex-1 font-semibold text-slate-700">{{ $cronograma->fase->nombre_completo }}</span>
                            <span class="text-xs text-slate-500">
                                {{ $cronograma->estado === 'aprobado' ? 'Aprobado por ' . $persona($cronograma->aprobadoPor) : 'Devuelto al Gestor' }} · {{ $cronograma->updated_at->format('d/m/Y') }}
                            </span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chip[$cronograma->estado][1] }}">{{ $chip[$cronograma->estado][0] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-talentohumano::layouts.lider>
