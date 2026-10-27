@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $niveles = \Modules\TalentoHumano\Entities\Cargo::NIVELES;
    $colorNivel = ['Gerente' => 'bg-[#043A2F] text-white', 'Gestor' => 'bg-[#064C3D] text-white', 'Lider' => 'bg-emerald-100 text-emerald-800', 'Equipo' => 'bg-slate-100 text-slate-600'];
    $pestanas = ['activo' => 'Activos', 'inactivo' => 'Inactivos', 'todos' => 'Todos'];
    $campo = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
@endphp

<x-talentohumano::layouts.lider title="Catálogo de cargos" activo="catalogo">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Catálogo de cargos</h1>
            <p class="mt-1 text-sm text-slate-600">Los cargos del Manual de Roles y Responsabilidades (GTH-M-001) que se activan en cada fase.</p>
        </div>
        @if ($esAdministrador)
            <a href="{{ route('talentohumano.lider.catalogo.create') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 transition hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Nuevo cargo
            </a>
        @endif
    </div>

    @if (session('ofrecer_inactivar') && $esAdministrador)
        <form method="POST" action="{{ route('talentohumano.lider.catalogo.inactivar', session('ofrecer_inactivar')) }}"
              data-confirmar="El cargo dejará de ofrecerse en la activación de cargos, pero conservará todo su historial." data-titulo="¿Inactivar el cargo?"
              class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm shadow-sm ring-1 ring-slate-200">
            @csrf @method('PATCH')
            <span class="flex-1 text-slate-600">Puedes inactivarlo en lugar de eliminarlo.</span>
            <button class="rounded-full bg-amber-500 px-4 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">Inactivar</button>
        </form>
    @endif

    {{-- Estado y filtros --}}
    <div class="mt-6 flex flex-wrap gap-2" role="tablist" aria-label="Estado de los cargos">
        @foreach ($pestanas as $clave => $etiqueta)
            <a href="{{ request()->fullUrlWithQuery(['estado' => $clave, 'page' => null]) }}" role="tab" aria-selected="{{ $estado === $clave ? 'true' : 'false' }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $estado === $clave ? 'bg-[#064C3D] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-[#064C3D]' }}">
                {{ $etiqueta }}
                <span class="ml-1 text-xs opacity-70">{{ $clave === 'todos' ? $conteo->sum() : ($conteo[$clave] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mt-4 grid gap-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:grid-cols-[minmax(0,1fr)_10rem_12rem_auto]">
        <input type="hidden" name="estado" value="{{ $estado }}">
        <label class="relative">
            <span class="sr-only">Buscar por nombre</span>
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar cargo…" class="{{ $campo }} pl-8">
        </label>
        <label>
            <span class="sr-only">Nivel</span>
            <select name="nivel" class="{{ $campo }}">
                <option value="">Todos los niveles</option>
                @foreach ($niveles as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('nivel') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="sr-only">Área</span>
            <select name="area" class="{{ $campo }}">
                <option value="">Todas las áreas</option>
                @foreach ($areas as $id => $nombre)
                    <option value="{{ $id }}" @selected((string) request('area') === (string) $id)>{{ $nombre }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2">
            <button class="rounded-xl bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Filtrar</button>
            @if (request()->hasAny(['q', 'nivel', 'area']))
                <a href="{{ route('talentohumano.lider.catalogo.index', ['estado' => $estado]) }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Limpiar</a>
            @endif
        </div>
    </form>

    {{-- Listado --}}
    <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
        @if ($cargos->isEmpty())
            <div class="px-6 py-14 text-center text-sm text-slate-500">
                <i class="fas fa-folder-open mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                No hay cargos que coincidan con la búsqueda.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Cargo</th>
                            <th class="px-4 py-3 font-semibold">Nivel</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Área</th>
                            <th class="hidden px-4 py-3 font-semibold xl:table-cell">Jefe inmediato</th>
                            <th class="px-4 py-3 text-center font-semibold" title="Número de aprendices">Apr.</th>
                            <th class="hidden px-4 py-3 text-center font-semibold sm:table-cell">Tecnólogos</th>
                            <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cargos as $cargo)
                            <tr class="transition hover:bg-[#E8F3EF]/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('talentohumano.lider.catalogo.show', $cargo) }}" class="font-semibold text-slate-800 hover:text-[#064C3D] hover:underline">{{ $cargo->nombre }}</a>
                                    @if ($cargo->estado === 'inactivo')
                                        <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colorNivel[$cargo->nivel] }}">{{ $niveles[$cargo->nivel] }}</span></td>
                                <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ $cargo->area?->nombre }}</td>
                                <td class="hidden max-w-56 truncate px-4 py-3 text-slate-600 xl:table-cell" title="{{ $cargo->nombreJefe() }}">{{ $cargo->nombreJefe() }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ $cargo->numero_aprendices }}</td>
                                <td class="hidden px-4 py-3 text-center sm:table-cell">
                                    @if ($cargo->programas_count)
                                        <span class="font-semibold text-slate-700">{{ $cargo->programas_count }}</span>
                                    @else
                                        <i class="fas fa-triangle-exclamation text-amber-500" title="Sin tecnólogos compatibles" aria-label="Sin tecnólogos compatibles"></i>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('talentohumano.lider.catalogo.show', $cargo) }}" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" title="Ver" aria-label="Ver {{ $cargo->nombre }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                        @if ($esAdministrador)
                                            <a href="{{ route('talentohumano.lider.catalogo.edit', $cargo) }}" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" title="Editar" aria-label="Editar {{ $cargo->nombre }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($cargos->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span>{{ $cargos->firstItem() }}–{{ $cargos->lastItem() }} de {{ $cargos->total() }} cargos</span>
                    <div class="flex gap-1">
                        <a href="{{ $cargos->previousPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => $cargos->onFirstPage(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => !$cargos->onFirstPage()])>
                            <i class="fas fa-chevron-left mr-1" aria-hidden="true"></i>Anterior</a>
                        <a href="{{ $cargos->nextPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => !$cargos->hasMorePages(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => $cargos->hasMorePages()])>
                            Siguiente<i class="fas fa-chevron-right ml-1" aria-hidden="true"></i></a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-talentohumano::layouts.lider>
