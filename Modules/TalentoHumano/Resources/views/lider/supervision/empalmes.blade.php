@use('Modules\TalentoHumano\Http\Controllers\SupervisionController')
@php
    $chip = ['Cerrado' => 'bg-emerald-100 text-emerald-800', 'En curso' => 'bg-amber-100 text-amber-800', 'Con novedad' => 'bg-red-100 text-red-700', 'Sin iniciar' => 'bg-slate-100 text-slate-500'];
    $icono = fn ($v) => $v === 1 ? 'fas fa-circle-check text-emerald-600' : ($v ? 'fas fa-circle-exclamation text-amber-500' : 'far fa-circle text-slate-300');
    $resumen = [
        ['Empalmes de la fase', $todos->count(), 'text-[#064C3D]'],
        ['Cerrados con visto bueno', $todos->where('situacion', 'Cerrado')->count(), 'text-[#064C3D]'],
        ['En curso', $todos->where('situacion', 'En curso')->count(), 'text-amber-600'],
        ['Sin iniciar', $todos->where('situacion', 'Sin iniciar')->count(), 'text-slate-500'],
        ['Con novedades', $todos->where('situacion', 'Con novedad')->count(), 'text-red-600'],
    ];
    $campo = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
@endphp

<x-talentohumano::layouts.lider title="Supervisión · Empalmes" activo="supervision">
    @include('talentohumano::lider.supervision._encabezado', ['actual' => 'empalmes'])

    @if ($todos->isEmpty())
        <div class="relative isolate mt-4 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white sm:p-10">
            <i class="fas fa-handshake absolute -bottom-6 -right-2 -z-10 text-[9rem] text-white/5" aria-hidden="true"></i>
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20"><i class="fas fa-hourglass-start" aria-hidden="true"></i></span>
            <h2 class="mt-5 text-xl font-extrabold sm:text-2xl">Los empalmes todavía no han empezado</h2>
            <p class="mt-2 max-w-xl text-emerald-50/85">
                Se habilitan cuando el Gestor de Talento Humano publique el acta de seleccionados.
                @if ($etapaEmpalme)
                    Según el cronograma, el empalme va del {{ $etapaEmpalme->fecha_inicio->format('d/m/Y') }} al {{ ($etapaEmpalme->fecha_fin ?? $etapaEmpalme->fecha_inicio)->format('d/m/Y') }}.
                @endif
            </p>
        </div>
    @else
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($resumen as [$texto, $valor, $color])
                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">{{ $texto }}</p><p class="text-2xl font-extrabold {{ $color }}">{{ $valor }}</p></div>
            @endforeach
        </div>

        @if ($pendientesEntrega)
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
                <span class="flex-1"><strong>{{ $pendientesEntrega }} {{ $pendientesEntrega === 1 ? 'aprendiz saliente aún no entrega' : 'aprendices salientes aún no entregan' }} su cargo.</strong> La fase no se puede cerrar con entregas pendientes.</span>
            </div>
        @endif

        <form method="GET" class="mt-4 flex flex-wrap gap-2">
            @if (request()->filled('fase')) <input type="hidden" name="fase" value="{{ request('fase') }}"> @endif
            <select name="estado" aria-label="Estado del empalme" class="{{ $campo }}" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (array_keys($chip) as $estado) <option @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option> @endforeach
            </select>
            <select name="area" aria-label="Área" class="{{ $campo }}" onchange="this.form.submit()">
                <option value="">Todas las áreas</option>
                @foreach ($areas as $area) <option value="{{ $area->id }}" @selected(($filtros['area'] ?? '') == $area->id)>{{ $area->nombre }}</option> @endforeach
            </select>
            <select name="tipo" aria-label="Tipo" class="{{ $campo }}" onchange="this.form.submit()">
                <option value="">Individuales y grupales</option>
                <option value="individual" @selected(($filtros['tipo'] ?? '') === 'individual')>Individuales</option>
                <option value="grupal" @selected(($filtros['tipo'] ?? '') === 'grupal')>Grupales</option>
            </select>
            <noscript><button class="rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white">Filtrar</button></noscript>
        </form>

        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-4 py-3 font-semibold">Cargo</th><th class="px-4 py-3 font-semibold">Entrega → Recibe</th><th class="px-4 py-3 font-semibold">Avance</th><th class="px-4 py-3 font-semibold">Estado</th><th class="px-4 py-3"><span class="sr-only">Ver</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($empalmes as $empalme)
                            <tr class="transition hover:bg-[#E8F3EF]/40">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800">{{ $empalme->cargo?->nombre }}</p>
                                    <p class="text-xs text-slate-500">{{ ucfirst($empalme->tipo) }}{{ $empalme->tipo === 'grupal' ? ' · ' . $empalme->salientes->count() . ' → ' . max(1, $empalme->receptores->count()) : '' }}</p>
                                    @if ($empalme->cargo_receptor_id && $empalme->cargo_receptor_id !== $empalme->cargo_id)
                                        <p class="mt-1 text-xs text-sky-700"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Recibe el cargo {{ $empalme->cargoReceptor?->nombre }}.</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $empalme->salientes->implode(' · ') ?: '—' }} <i class="fas fa-arrow-right mx-1 text-xs text-[#064C3D]" aria-hidden="true"></i> {{ $empalme->receptores->implode(' · ') ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        @foreach ($empalme->pasos as $i => $paso)
                                            <span title="{{ SupervisionController::PASOS_EMPALME[$i] }}" class="grid h-6 w-6 place-items-center rounded-full text-[10px] font-bold {{ $paso === 1 ? 'bg-[#064C3D] text-[#34D399]' : ($paso ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400') }}">
                                                @if ($paso === 1) <i class="fas fa-check" aria-hidden="true"></i> @else {{ $paso ? '!' : $i + 1 }} @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chip[$empalme->situacion] }}">{{ $empalme->situacion }}</span></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" data-abrir="#empalme-{{ $empalme->id }}" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" aria-label="Ver el empalme de {{ $empalme->cargo?->nombre }}"><i class="fas fa-eye" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Ningún empalme coincide con los filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
                <span class="font-semibold">Pasos:</span>
                @foreach (SupervisionController::PASOS_EMPALME as $i => $paso) <span>{{ $i + 1 }} {{ $paso }}</span> @endforeach
            </div>
        </div>

        {{-- Detalle de cada empalme, solo lectura (HU-014, esc. 3) --}}
        @foreach ($empalmes as $empalme)
            <dialog id="empalme-{{ $empalme->id }}" aria-labelledby="titulo-empalme-{{ $empalme->id }}" class="m-auto w-[min(94vw,40rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Empalme {{ $empalme->tipo }} · {{ $empalme->cargo?->area?->nombre ?? 'Sin área' }}</p>
                            <h2 id="titulo-empalme-{{ $empalme->id }}" class="mt-1 text-lg font-bold text-slate-900">{{ $empalme->cargo?->nombre }}{{ $empalme->cargo_receptor_id && $empalme->cargo_receptor_id !== $empalme->cargo_id ? ' → ' . $empalme->cargoReceptor?->nombre : '' }}</h2>
                        </div>
                        <button type="button" data-cerrar class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-3 text-sm"><p class="text-xs font-semibold text-slate-400">Entregan ({{ $empalme->salientes->count() }})</p><p class="mt-1 text-slate-700">{{ $empalme->salientes->implode(' · ') ?: '—' }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3 text-sm"><p class="text-xs font-semibold text-slate-400">Reciben ({{ $empalme->receptores->count() }})</p><p class="mt-1 text-slate-700">{{ $empalme->receptores->implode(' · ') ?: '—' }}</p></div>
                    </div>
                    <ol class="mt-5 space-y-2 text-sm">
                        @foreach ($empalme->pasos as $i => $paso)
                            <li class="flex items-start gap-3"><i class="{{ $icono($paso) }} mt-0.5" aria-hidden="true"></i><span class="flex-1 {{ $paso ? 'text-slate-700' : 'text-slate-500' }}">{{ $empalme->detalle[$i] }}</span></li>
                        @endforeach
                    </ol>
                    <div class="mt-6 flex justify-end">
                        <button type="button" data-cerrar class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Cerrar</button>
                    </div>
                </div>
            </dialog>
        @endforeach
    @endif
</x-talentohumano::layouts.lider>
