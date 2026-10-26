<x-talentohumano::layouts.master title="Catálogo de Cargos · SENA Empresa">
    {{-- Breadcrumbs y Cabecera --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Catálogo de cargos</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-book-open"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Catálogo de Cargos
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Consulta los 103 perfiles de cargo, sus responsabilidades en el SIG, actividades formativas y programas afines (RF-019).
                    </p>
                </div>
            </div>
        </div>

        <span class="rounded-full bg-[#E8F3EF] px-4 py-1.5 text-xs font-bold text-[#064C3D] ring-1 ring-[#34D399]/40 flex items-center gap-2 shadow-xs">
            <i class="fas fa-sitemap text-emerald-600"></i>
            <span>{{ $totalCargos }} Cargos registrados</span>
        </span>
    </div>

    {{-- Tarjetas KPI de Distribución por Nivel --}}
    <div class="folium-kpi-grid">
        <a href="{{ route('talentohumano.catalogo.index', ['nivel' => 'Gerente']) }}" class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-gerente-text)">Gerencias</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-gerente-bg); color: var(--color-gerente-text)">
                    <i class="fas fa-briefcase"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-gerente-text)">{{ $gerentesCount }}</p>
            <p class="folium-kpi-meta">Nivel directivo general</p>
        </a>

        <a href="{{ route('talentohumano.catalogo.index', ['nivel' => 'Gestor']) }}" class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-primary)">Gestorías</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-surface-mint); color: var(--color-primary)">
                    <i class="fas fa-network-wired"></i>
                </span>
            </div>
            <p class="folium-kpi-value">{{ $gestoresCount }}</p>
            <p class="folium-kpi-meta">Coordinación de área</p>
        </a>

        <a href="{{ route('talentohumano.catalogo.index', ['nivel' => 'Lider']) }}" class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-lider-text)">Líderes</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-lider-bg); color: var(--color-lider-text)">
                    <i class="fas fa-crown"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-lider-text)">{{ $lideresCount }}</p>
            <p class="folium-kpi-meta">Líderes de unidades productivas</p>
        </a>

        <a href="{{ route('talentohumano.catalogo.index', ['nivel' => 'Equipo']) }}" class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-equipo-text)">Equipos</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-equipo-bg); color: var(--color-equipo-text)">
                    <i class="fas fa-people-group"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-equipo-text)">{{ $equiposCount }}</p>
            <p class="folium-kpi-meta">Operación y apoyo técnico</p>
        </a>
    </div>

    {{-- Filtros y Búsqueda Avanzada --}}
    <form action="{{ route('talentohumano.catalogo.index') }}" method="GET" class="mb-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 space-y-4">
        <div class="grid gap-3 sm:grid-cols-12">
            {{-- Buscador de texto --}}
            <div class="sm:col-span-5 relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por cargo, actividades, palabras clave..."
                       class="w-full rounded-2xl border border-slate-200 bg-white pl-9 pr-4 py-2.5 text-xs focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40">
            </div>

            {{-- Filtro por Área --}}
            <div class="sm:col-span-4">
                <select name="area_id" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40 text-slate-700">
                    <option value="">Todas las áreas (9)</option>
                    @foreach ($areas as $ar)
                        <option value="{{ $ar->id }}" {{ request('area_id') == $ar->id ? 'selected' : '' }}>
                            {{ $ar->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtro por Programa --}}
            <div class="sm:col-span-3">
                <select name="program_id" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40 text-slate-700">
                    <option value="">Todos los programas afines</option>
                    @foreach ($programas as $prg)
                        <option value="{{ $prg->id }}" {{ request('program_id') == $prg->id ? 'selected' : '' }}>
                            {{ $prg->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Píldoras de Filtro por Nivel --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <span class="text-slate-400 mr-1 text-[11px] uppercase tracking-wider">Nivel:</span>
                <a href="{{ route('talentohumano.catalogo.index', array_merge(request()->except('nivel', 'page'), ['nivel' => 'todos'])) }}"
                   class="rounded-full px-3 py-1 transition {{ !request('nivel') || request('nivel') === 'todos' ? 'bg-[#064C3D] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Todos
                </a>
                <a href="{{ route('talentohumano.catalogo.index', array_merge(request()->except('nivel', 'page'), ['nivel' => 'Gerente'])) }}"
                   class="rounded-full px-3 py-1 transition {{ request('nivel') === 'Gerente' ? 'bg-purple-700 text-white shadow-xs' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                    Gerente (3)
                </a>
                <a href="{{ route('talentohumano.catalogo.index', array_merge(request()->except('nivel', 'page'), ['nivel' => 'Gestor'])) }}"
                   class="rounded-full px-3 py-1 transition {{ request('nivel') === 'Gestor' ? 'bg-[#064C3D] text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                    Gestor (10)
                </a>
                <a href="{{ route('talentohumano.catalogo.index', array_merge(request()->except('nivel', 'page'), ['nivel' => 'Lider'])) }}"
                   class="rounded-full px-3 py-1 transition {{ request('nivel') === 'Lider' ? 'bg-amber-700 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                    Líder (71)
                </a>
                <a href="{{ route('talentohumano.catalogo.index', array_merge(request()->except('nivel', 'page'), ['nivel' => 'Equipo'])) }}"
                   class="rounded-full px-3 py-1 transition {{ request('nivel') === 'Equipo' ? 'bg-sky-700 text-white shadow-xs' : 'bg-sky-50 text-sky-800 hover:bg-sky-100' }}">
                    Equipo (19)
                </a>
            </div>

            <button type="submit"
                    class="rounded-full bg-[#064C3D] hover:bg-[#043A2F] text-white px-5 py-1.5 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fas fa-filter text-xs"></i> Aplicar filtros
            </button>
        </div>
    </form>

    {{-- Grid de Tarjetas de Cargos --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
        @forelse ($cargos as $cg)
            @php
                $nivelBadge = match ($cg->nivel) {
                    'Gerente' => 'bg-purple-100 text-purple-800 ring-1 ring-purple-300',
                    'Gestor' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                    'Lider' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                    default => 'bg-sky-100 text-sky-800 ring-1 ring-sky-300',
                };
            @endphp
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 hover:shadow-md hover:ring-[#34D399]/60 transition flex flex-col justify-between">
                <div>
                    {{-- Badges de Nivel y Área --}}
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $nivelBadge }}">
                            {{ $cg->nivel }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 truncate max-w-[150px]">
                            {{ $cg->area->nombre ?? 'General' }}
                        </span>
                    </div>

                    {{-- Título del Cargo --}}
                    <h3 class="font-extrabold text-[#064C3D] text-base leading-snug">
                        <a href="{{ route('talentohumano.catalogo.show', $cg->id) }}" class="hover:underline">
                            {{ $cg->nombre }}
                        </a>
                    </h3>

                    {{-- Extracto del Rol Principal --}}
                    <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">
                        {{ $cg->rol_principal ?: 'Sin rol principal definido en la estructura del cargo.' }}
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 mb-3">
                        <span class="flex items-center gap-1 font-medium">
                            <i class="fas fa-users text-slate-400"></i>
                            <strong>{{ $cg->numero_aprendices }}</strong> {{ $cg->numero_aprendices == 1 ? 'cupo' : 'cupos' }}
                        </span>
                        <span class="flex items-center gap-1 font-medium">
                            <i class="fas fa-graduation-cap text-slate-400"></i>
                            <strong>{{ $cg->programs->count() }}</strong> programas
                        </span>
                    </div>

                    <a href="{{ route('talentohumano.catalogo.show', $cg->id) }}"
                       class="w-full rounded-full bg-[#E8F3EF] hover:bg-[#064C3D] hover:text-white text-[#064C3D] text-xs font-bold py-2 px-4 transition flex items-center justify-center gap-1.5 shadow-xs">
                        <span>Ver perfil y funciones</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200/70">
                <i class="fas fa-book-open text-4xl text-slate-300 mb-2 block"></i>
                <h3 class="font-bold text-slate-700 text-sm">No se encontraron cargos con los filtros indicados</h3>
                <p class="text-xs text-slate-400 mt-1">Prueba cambiando el término de búsqueda o seleccionando otra área.</p>
                <a href="{{ route('talentohumano.catalogo.index') }}"
                   class="inline-block mt-4 rounded-full bg-[#064C3D] text-white px-5 py-2 text-xs font-bold shadow-xs">
                    Restablecer filtros
                </a>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if ($cargos->hasPages())
        <div class="mt-6">
            {{ $cargos->links() }}
        </div>
    @endif
</x-talentohumano::layouts.master>
