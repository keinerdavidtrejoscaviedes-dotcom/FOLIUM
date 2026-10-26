<x-talentohumano::layouts.master title="Postulaciones">
    {{-- Breadcrumbs y Título --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Postulaciones</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-file-signature"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Postulaciones y aspirantes
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Revisa a los aspirantes y registra el concepto de análisis de hoja de vida (HU-024 · RF-006).
                    </p>
                </div>
            </div>
        </div>

        @if ($convocatoria)
            <div class="rounded-2xl bg-white px-4 py-2 shadow-sm ring-1 ring-slate-200 text-xs">
                <p class="font-bold text-slate-700">{{ $convocatoria->titulo }}</p>
                <p class="text-slate-500">Umbral mínimo: <span class="font-extrabold text-[#064C3D]">{{ (int)$convocatoria->umbral }} pts</span></p>
            </div>
        @endif
    </div>

    {{-- Métricas rápidas --}}
    <div class="folium-kpi-grid">
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Total postulados</span>
                <span class="folium-kpi-icon"><i class="fas fa-users"></i></span>
            </div>
            <p class="folium-kpi-value">{{ $totalPostulados }}</p>
            <p class="folium-kpi-meta">Aspirantes en la convocatoria</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-success-text)">HV Analizadas</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-success-bg); color: var(--color-success-text)"><i class="fas fa-check-double"></i></span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-success-text)">{{ $totalAnalizadas }}</p>
            <p class="folium-kpi-meta">Con concepto registrado (HU-024)</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-warning-text)">Pendientes</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-warning-bg); color: var(--color-warning-text)"><i class="fas fa-clock"></i></span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-warning-text)">{{ $totalPendientes }}</p>
            <p class="folium-kpi-meta">Requieren concepto de instructor</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Puntaje promedio</span>
                <span class="folium-kpi-icon"><i class="fas fa-chart-line"></i></span>
            </div>
            <p class="folium-kpi-value">{{ round($promedioPuntaje, 1) }} <span class="text-xs font-normal text-slate-400">/ 200</span></p>
            <p class="folium-kpi-meta">Prueba + Entrevistas</p>
        </div>
    </div>

    {{-- Filtros y Buscador --}}
    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <form method="GET" action="{{ route('talentohumano.postulaciones.index') }}" class="grid gap-3 lg:grid-cols-12">
            {{-- Buscador --}}
            <div class="lg:col-span-5 relative">
                <i class="fas fa-search absolute left-3.5 top-3 text-xs text-slate-400"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o número de documento..."
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
            </div>

            {{-- Filtro Estado de Análisis --}}
            <div class="lg:col-span-3">
                <select name="estado_analisis" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                    <option value="">Todos los estados</option>
                    <option value="pendientes" {{ request('estado_analisis') === 'pendientes' ? 'selected' : '' }}>Pendientes de análisis</option>
                    <option value="analizadas" {{ request('estado_analisis') === 'analizadas' ? 'selected' : '' }}>Con concepto registrado</option>
                </select>
            </div>

            {{-- Filtro Cargo --}}
            <div class="lg:col-span-3">
                <select name="cargo_id" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                    <option value="">Todos los cargos postulados</option>
                    @foreach ($cargos as $c)
                        <option value="{{ $c->id }}" {{ request('cargo_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Botón Filtrar y Limpiar --}}
            <div class="lg:col-span-1 flex items-center gap-1.5">
                <button type="submit" class="w-full rounded-2xl bg-[#064C3D] py-2 text-xs font-semibold text-white hover:bg-[#043A2F]" title="Filtrar">
                    <i class="fas fa-filter"></i>
                </button>
                @if (request()->hasAny(['buscar', 'estado_analisis', 'cargo_id', 'course_id']))
                    <a href="{{ route('talentohumano.postulaciones.index') }}"
                       class="rounded-2xl px-2.5 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100" title="Limpiar">
                        <i class="fas fa-xmark"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Lista de Aspirantes --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between px-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Mostrando {{ $postulaciones->count() }} aspirantes
            </p>
        </div>

        @forelse ($postulaciones as $post)
            @php
                $persona = $post->person;
                $analisis = $post->analisisHv;
            @endphp
            <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 hover:shadow-md transition">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    {{-- Información personal y académica --}}
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-sm font-bold text-[#34D399]">
                            {{ $post->iniciales }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('talentohumano.postulaciones.show', $post->id) }}"
                                   class="text-base font-extrabold text-slate-900 hover:text-[#064C3D] hover:underline">
                                    {{ $post->nombre_completo }}
                                </a>
                                @if ($analisis)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Analizada
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 flex items-center gap-1">
                                        <i class="fas fa-clock"></i> Pendiente concepto
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-500 mt-0.5">
                                Ficha {{ $post->course->code ?? 'N/A' }} · {{ $post->course->program->name ?? 'Programa' }} · {{ $persona->document_type ?? 'CC' }} {{ $persona->document_number ?? '' }}
                            </p>

                            {{-- Opciones de cargo elegidas --}}
                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="font-semibold text-slate-400">Opciones:</span>
                                @foreach ($post->opciones as $op)
                                    <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 {{ $op->preferencia == 1 ? 'bg-[#E8F3EF] text-[#064C3D] font-bold ring-1 ring-emerald-300' : 'bg-slate-50 text-slate-600 ring-1 ring-slate-200' }}">
                                        <span class="text-[10px] text-slate-400">{{ $op->preferencia }}.</span>
                                        {{ $op->cargo_nombre }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Puntaje y Acciones --}}
                    <div class="flex items-center gap-4 shrink-0 self-end md:self-center">
                        @if ($post->puntaje_total)
                            <div class="text-right">
                                <p class="text-[11px] font-semibold text-slate-400 uppercase">Puntaje</p>
                                <p class="text-xl font-extrabold text-[#064C3D]">
                                    {{ (int)$post->puntaje_total }} <span class="text-xs font-normal text-slate-400">/ 200</span>
                                </p>
                            </div>
                        @endif

                        <a href="{{ route('talentohumano.postulaciones.show', $post->id) }}"
                           class="rounded-full {{ $analisis ? 'ring-1 ring-slate-300 text-slate-700 hover:bg-slate-50' : 'bg-[#064C3D] text-white hover:bg-[#043A2F]' }} px-5 py-2 text-xs font-bold shadow-sm flex items-center gap-1.5 transition">
                            <i class="fas {{ $analisis ? 'fa-eye' : 'fa-pen-to-square' }}"></i>
                            <span>{{ $analisis ? 'Ver ficha' : 'Analizar HV' }}</span>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                    <i class="fas fa-user-xmark"></i>
                </span>
                <p class="mt-3 font-semibold text-slate-700">No se encontraron aspirantes con los filtros seleccionados</p>
                <p class="mt-1 text-xs text-slate-400">Intenta restablecer los filtros de búsqueda.</p>
                <a href="{{ route('talentohumano.postulaciones.index') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-xs font-semibold text-white">
                    Ver todos los postulantes
                </a>
            </div>
        @endforelse
    </div>
</x-talentohumano::layouts.master>
