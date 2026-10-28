@use('Modules\TalentoHumano\Entities\Asignacion')
@php
    $chip = ['asignada' => 'bg-amber-100 text-amber-800', 'vigente' => 'bg-emerald-100 text-emerald-800', 'suspendida' => 'bg-red-100 text-red-700', 'finalizada' => 'bg-slate-100 text-slate-600'];
    $actual = $p->asignacion;
@endphp

<x-talentohumano::layouts.aprendiz title="Mi cargo" activo="cargo" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi cargo e historial</h1>
        <p class="mt-1 text-sm text-slate-600">Tu cargo en SENA Empresa y los que has tenido en fases anteriores.</p>
    </div>

    @if ($actual)
        <section class="mb-5 rounded-3xl bg-linear-to-r from-[#064C3D] to-[#043A2F] p-6 text-white shadow-xl shadow-[#064C3D]/20">
            <div class="flex flex-wrap items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20"><i class="fas fa-id-card-clip" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <span class="rounded-full bg-[#34D399] px-2.5 py-0.5 text-xs font-bold uppercase text-[#043A2F]">{{ Asignacion::ESTADOS[$actual->estado] }}</span>
                    <h2 class="mt-2 text-xl font-extrabold sm:text-2xl">{{ $actual->cargo?->nombre }}</h2>
                    <p class="text-sm text-emerald-100/90">{{ $actual->cargo?->area?->nombre }} · {{ $actual->fase?->nombre_completo }}</p>
                </div>
            </div>
            @if ($actual->estado === 'asignada')
                {{-- HU-055, esc. 3 --}}
                <p class="mt-4 rounded-2xl bg-white/10 px-4 py-3 text-sm"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Debe completar el empalme para usar las funciones del cargo. <a href="{{ route('talentohumano.aprendiz.empalme') }}" class="font-semibold underline">Ir a mi empalme</a></p>
            @endif
        </section>
    @endif

    <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <h2 class="p-6 pb-0 font-bold text-[#064C3D]">Historial</h2>
        @if ($historial->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-slate-500">Aún no tienes cargos asignados en SENA Empresa.</p>
        @else
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($historial as $asignacion)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $asignacion->cargo?->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $asignacion->fase?->nombre_completo }} · {{ Asignacion::ORIGENES[$asignacion->origen] ?? $asignacion->origen }}{{ $asignacion->fecha_inicio ? ' · desde el ' . $asignacion->fecha_inicio->format('d/m/Y') : '' }}{{ $asignacion->fecha_fin ? ' hasta el ' . $asignacion->fecha_fin->format('d/m/Y') : '' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $chip[$asignacion->estado] ?? 'bg-slate-100 text-slate-600' }}">{{ Asignacion::ESTADOS[$asignacion->estado] ?? $asignacion->estado }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-talentohumano::layouts.aprendiz>
