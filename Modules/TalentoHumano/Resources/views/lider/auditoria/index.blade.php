@use('Modules\TalentoHumano\Support\Auditoria')
@use('Modules\TalentoHumano\Support\DescripcionAuditoria')
@use('Modules\TalentoHumano\Http\Controllers\AuditoriaController')
@php
    $campo = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $chipOrigen = ['Rol' => 'bg-[#064C3D] text-white', 'Designación' => 'bg-emerald-100 text-emerald-800', 'Asignación' => 'bg-sky-100 text-sky-800', 'Sistema' => 'bg-slate-100 text-slate-600'];
    $filtrando = request()->hasAny(['q', 'seccion', 'origen', 'desde', 'hasta']);
@endphp

<x-talentohumano::layouts.lider title="Auditoría" activo="auditoria">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Auditoría</h1>
            <p class="mt-1 text-sm text-slate-600">Quién hizo qué, cuándo y con qué permiso. Nadie puede modificar ni borrar este registro.</p>
        </div>
        <a href="{{ route('talentohumano.lider.auditoria.exportar', request()->query()) }}" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF]">
            <i class="fas fa-file-excel" aria-hidden="true"></i> Exportar
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="mt-6 grid gap-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_12rem_11rem_9.5rem_9.5rem_auto]">
        <label class="relative"><span class="sr-only">Buscar</span>
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Usuario, cargo, aprendiz…" class="{{ $campo }} pl-8">
        </label>
        <select name="seccion" aria-label="Sección" class="{{ $campo }}">
            <option value="">Todas las secciones</option>
            @foreach (DescripcionAuditoria::SECCIONES as $clave => [$titulo]) <option value="{{ $clave }}" @selected(request('seccion') === $clave)>{{ $titulo }}</option> @endforeach
        </select>
        <select name="origen" aria-label="Origen del permiso" class="{{ $campo }}">
            <option value="">Cualquier origen</option>
            @foreach (AuditoriaController::ORIGENES as $clave => $texto) <option value="{{ $clave }}" @selected(request('origen') === $clave)>{{ $texto }}</option> @endforeach
        </select>
        <label><span class="sr-only">Desde</span><input type="date" name="desde" value="{{ request('desde') }}" title="Desde" class="{{ $campo }}"></label>
        <label><span class="sr-only">Hasta</span><input type="date" name="hasta" value="{{ request('hasta') }}" title="Hasta" class="{{ $campo }}"></label>
        <div class="flex gap-2">
            <button class="rounded-xl bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Filtrar</button>
            @if ($filtrando) <a href="{{ route('talentohumano.lider.auditoria.index') }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Limpiar</a> @endif
        </div>
    </form>

    {{-- Registro --}}
    <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
        @if ($registros->isEmpty())
            <div class="px-6 py-14 text-center text-sm text-slate-500">
                <i class="fas fa-clock-rotate-left mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                {{ $filtrando ? 'No hay registros que coincidan con los filtros.' : 'Todavía no hay acciones registradas en FOLIUM.' }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Fecha y hora</th>
                            <th class="px-4 py-3 font-semibold">Usuario</th>
                            <th class="px-4 py-3 font-semibold">Acción</th>
                            <th class="hidden px-4 py-3 font-semibold lg:table-cell">Origen del permiso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 align-top">
                        @foreach ($registros as $registro)
                            @php
                                [$tipo, $fuente] = Auditoria::origenDe($registro->tags);
                                $cambios = $descripcion->cambios($registro);
                                $fecha = \Illuminate\Support\Carbon::parse($registro->created_at);
                            @endphp
                            <tr @class(['transition hover:bg-[#E8F3EF]/30', 'bg-red-50/40' => $registro->event === 'acceso_denegado'])>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $fecha->format('d/m/Y') }}<span class="block text-xs text-slate-400">{{ $fecha->format('g:i a') }}</span></td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $registro->usuario ?? $registro->nickname ?? 'Sistema' }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $descripcion->seccion($registro) }}</p>
                                    <p class="font-medium text-slate-700">{{ $descripcion->accion($registro) }}</p>
                                    <p class="mt-1 lg:hidden"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $chipOrigen[$tipo] ?? $chipOrigen['Sistema'] }}">{{ $tipo }}</span> <span class="text-xs text-slate-500">{{ $fuente }}</span></p>
                                    @if ($cambios)
                                        <details class="group mt-2">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-emerald-700 hover:underline">
                                                <i class="fas fa-chevron-right mr-1 transition group-open:rotate-90" aria-hidden="true"></i>Ver detalle ({{ count($cambios) }})
                                            </summary>
                                            <div class="mt-2 overflow-x-auto rounded-xl ring-1 ring-slate-200">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-slate-50 text-slate-500"><tr><th class="px-3 py-1.5 text-left font-semibold">Campo</th><th class="px-3 py-1.5 text-left font-semibold">Valor anterior</th><th class="px-3 py-1.5 text-left font-semibold">Valor nuevo</th></tr></thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @foreach ($cambios as [$nombreCampo, $anterior, $nuevo])
                                                            <tr>
                                                                <td class="px-3 py-1.5 font-semibold text-slate-600">{{ $nombreCampo }}</td>
                                                                <td class="max-w-xs px-3 py-1.5 text-slate-500"><span class="line-clamp-3" title="{{ $anterior }}">{{ $anterior }}</span></td>
                                                                <td class="max-w-xs px-3 py-1.5 text-slate-800"><span class="line-clamp-3" title="{{ $nuevo }}">{{ $nuevo }}</span></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chipOrigen[$tipo] ?? $chipOrigen['Sistema'] }}">{{ $tipo }}</span>
                                    <p class="mt-1 text-xs text-slate-500">{{ $fuente }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                <span>{{ $registros->firstItem() }}–{{ $registros->lastItem() }} de {{ $registros->total() }} registros</span>
                @if ($registros->hasPages())
                    <div class="flex gap-1">
                        <a href="{{ $registros->previousPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => $registros->onFirstPage(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => !$registros->onFirstPage()])><i class="fas fa-chevron-left mr-1" aria-hidden="true"></i>Anterior</a>
                        <a href="{{ $registros->nextPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => !$registros->hasMorePages(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => $registros->hasMorePages()])>Siguiente<i class="fas fa-chevron-right ml-1" aria-hidden="true"></i></a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <p class="mt-4 flex items-start gap-2 text-xs text-slate-500">
        <i class="fas fa-lock mt-0.5" aria-hidden="true"></i>
        El registro no tiene opciones de edición ni de borrado y se conserva como mínimo 5 años (RNF-005).
    </p>
</x-talentohumano::layouts.lider>
