<x-talentohumano::layouts.master title="Activación de cargos y fichas">
    @push('styles')
        <style>
            .cargo-card { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
            .cargo-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -10px rgba(6, 76, 61, 0.12); }
            .chip-compat-active { background-color: #E8F3EF; color: #064C3D; border: 1px solid #34D399; }
            .chip-compat-inactive { background-color: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }
        </style>
    @endpush

    {{-- Navegación superior / Breadcrumbs --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-slate-400">Convocatoria y cronograma</span>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Activación de cargos y fichas</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-toggle-on"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Activación de cargos y fichas
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Define los cargos que operarán en la convocatoria y las fichas participantes (HU-018 y HU-019).
                    </p>
                </div>
            </div>
        </div>

        {{-- Selector de Fase --}}
        <div class="flex items-center gap-2 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
            <span class="text-xs font-semibold text-slate-500 pl-2">Fase:</span>
            <form method="GET" action="{{ route('talentohumano.activacion.index') }}" id="formFase">
                <select name="fase_id" onchange="this.form.submit()"
                        class="rounded-xl border-0 bg-[#E8F3EF] py-1.5 pl-3 pr-8 text-xs font-bold text-[#064C3D] focus:ring-2 focus:ring-[#34D399]">
                    @foreach ($todasFases as $f)
                        <option value="{{ $f->id }}" {{ $fase && $fase->id === $f->id ? 'selected' : '' }}>
                            Fase {{ $f->romano }} (Fase {{ $f->numero }}) · {{ ucfirst($f->estado) }}
                        </option>
                    @endforeach
                </select>
            </form>
            <button type="button" onclick="document.getElementById('modal-fichas').showModal()"
                    class="rounded-xl bg-[#064C3D] px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:bg-[#043A2F] flex items-center gap-1.5">
                <i class="fas fa-users-rectangle text-[#34D399]"></i>
                <span>Fichas ({{ $totalFichas }})</span>
            </button>
        </div>
    </div>

    {{-- Métricas destacadas --}}
    <div class="folium-kpi-grid">
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Cargos activados</span>
                <span class="folium-kpi-icon"><i class="fas fa-toggle-on"></i></span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <p class="folium-kpi-value" id="stat-activados">{{ $totalActivados }}</p>
                <span class="text-xs font-semibold text-slate-500">de {{ $totalCargos }}</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full bg-[#064C3D] transition-all duration-500" style="width: {{ $totalCargos > 0 ? ($totalActivados / $totalCargos) * 100 : 0 }}%"></div>
            </div>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Cupos requeridos</span>
                <span class="folium-kpi-icon"><i class="fas fa-users"></i></span>
            </div>
            <p class="folium-kpi-value" id="stat-cupos">{{ $totalCupos }}</p>
            <p class="folium-kpi-meta">Definidos para convocatoria</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Fichas en fase</span>
                <span class="folium-kpi-icon"><i class="fas fa-graduation-cap"></i></span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <p class="folium-kpi-value">{{ $totalFichas }}</p>
                <button type="button" onclick="document.getElementById('modal-fichas').showModal()"
                        class="text-xs font-bold text-emerald-700 hover:underline">
                    Gestionar
                </button>
            </div>
            <p class="folium-kpi-meta">{{ $totalProgramas }} programas afines</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Estado de la fase</span>
                <span class="folium-kpi-icon"><i class="fas fa-clock"></i></span>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <p class="font-extrabold text-slate-800 uppercase text-base mb-0">{{ $fase->estado ?? 'Planeación' }}</p>
            </div>
            <p class="folium-kpi-meta">
                Inicio: {{ $fase && $fase->fecha_inicio ? $fase->fecha_inicio->format('d/m/Y') : 'Por definir' }}
            </p>
        </div>
    </div>

    {{-- Filtros y Barra de Control --}}
    <div class="mb-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <form method="GET" action="{{ route('talentohumano.activacion.index') }}" class="grid gap-3 lg:grid-cols-12">
            <input type="hidden" name="fase_id" value="{{ $fase->id ?? '' }}">

            {{-- Buscador --}}
            <div class="lg:col-span-4 relative">
                <i class="fas fa-search absolute left-3.5 top-3 text-xs text-slate-400"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por cargo, rol o requisito..."
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
            </div>

            {{-- Filtro Área --}}
            <div class="lg:col-span-3">
                <select name="area_id" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                    <option value="">Todas las áreas ({{ $areas->count() }})</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtro Nivel --}}
            <div class="lg:col-span-2">
                <select name="nivel" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                    <option value="">Todos los niveles</option>
                    <option value="Gerente" {{ request('nivel') === 'Gerente' ? 'selected' : '' }}>Gerente</option>
                    <option value="Gestor" {{ request('nivel') === 'Gestor' ? 'selected' : '' }}>Gestor</option>
                    <option value="Lider" {{ request('nivel') === 'Lider' ? 'selected' : '' }}>Líder</option>
                    <option value="Equipo" {{ request('nivel') === 'Equipo' ? 'selected' : '' }}>Equipo</option>
                </select>
            </div>

            {{-- Filtro Estado Activación --}}
            <div class="lg:col-span-3 flex items-center gap-2">
                <select name="estado_activacion" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-[#064C3D] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                    <option value="">Todos los estados</option>
                    <option value="activados" {{ request('estado_activacion') === 'activados' ? 'selected' : '' }}>Solo activados</option>
                    <option value="pendientes" {{ request('estado_activacion') === 'pendientes' ? 'selected' : '' }}>Solo pendientes</option>
                </select>
                <button type="submit" class="rounded-2xl bg-[#064C3D] px-3 py-2 text-xs font-semibold text-white hover:bg-[#043A2F]" title="Filtrar">
                    <i class="fas fa-filter"></i>
                </button>
                @if (request()->hasAny(['buscar', 'area_id', 'nivel', 'estado_activacion', 'solo_compatibles']))
                    <a href="{{ route('talentohumano.activacion.index', ['fase_id' => $fase->id ?? '']) }}"
                       class="rounded-2xl px-2.5 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-800" title="Limpiar filtros">
                        <i class="fas fa-xmark"></i>
                    </a>
                @endif
            </div>
        </form>

        {{-- Switch de Compatibilidad y Activación Masiva --}}
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
            <label class="flex items-center gap-2 font-medium text-slate-600 cursor-pointer">
                <input type="checkbox" name="solo_compatibles" value="1"
                       {{ request('solo_compatibles') === '1' ? 'checked' : '' }}
                       onchange="const url = new URL(window.location.href); url.searchParams.set('solo_compatibles', this.checked ? '1' : '0'); window.location.href = url.toString();"
                       class="h-4 w-4 rounded accent-[#064C3D]">
                <span>Mostrar únicamente cargos con <strong class="text-[#064C3D]">fichas compatibles en la fase</strong></span>
            </label>

            @if (request('area_id'))
                @php
                    $areaSeleccionada = $areas->firstWhere('id', request('area_id'));
                @endphp
                @if ($areaSeleccionada)
                    <form method="POST" action="{{ route('talentohumano.activacion.area') }}" class="inline">
                        @csrf
                        <input type="hidden" name="fase_id" value="{{ $fase->id }}">
                        <input type="hidden" name="area_id" value="{{ $areaSeleccionada->id }}">
                        <button type="submit" class="rounded-full bg-[#E8F3EF] px-3 py-1 font-semibold text-[#064C3D] hover:bg-emerald-100 transition">
                            <i class="fas fa-check-double mr-1 text-emerald-600"></i> Activar todos en «{{ $areaSeleccionada->nombre }}»
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Listado de Cargos del Catálogo --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between px-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Mostrando {{ $cargos->count() }} cargos
            </p>
            <p class="text-xs text-slate-500">
                <i class="fas fa-circle-info mr-1 text-[#34D399]"></i> Haz clic en «Activar» o «Desactivar» para actualizar en tiempo real.
            </p>
        </div>

        @forelse ($cargos as $cargo)
            <article class="cargo-card rounded-3xl bg-white p-5 shadow-sm ring-1 {{ $cargo->is_activado ? 'ring-[#064C3D]/30 border-l-4 border-l-[#064C3D]' : 'ring-slate-200/70' }}">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    {{-- Información del Cargo --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-extrabold text-slate-900">
                                {{ $cargo->nombre }}
                            </h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 uppercase">
                                {{ $cargo->nivel }}
                            </span>
                            @if ($cargo->area)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-[#064C3D]">
                                    <i class="fas fa-briefcase mr-1 text-[#34D399]"></i> {{ $cargo->area->nombre }}
                                </span>
                            @endif
                            @if ($cargo->es_compatible)
                                <span class="chip-compat-active rounded-full px-2.5 py-0.5 text-[11px] font-semibold flex items-center gap-1">
                                    <i class="fas fa-check-circle text-emerald-600"></i> Compatible con fichas de la fase
                                </span>
                            @else
                                <span class="chip-compat-inactive rounded-full px-2.5 py-0.5 text-[11px] font-medium flex items-center gap-1">
                                    <i class="fas fa-triangle-exclamation text-amber-500"></i> Sin ficha compatible
                                </span>
                            @endif
                        </div>

                        {{-- Rol principal y programas afines --}}
                        <p class="mt-1.5 text-xs text-slate-600 line-clamp-2">
                            {{ $cargo->rol_principal ?: ($cargo->requisitos ?: 'Sin descripción detallada registrada en el catálogo.') }}
                        </p>

                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
                            <span class="font-semibold text-slate-400">Programas compatibles:</span>
                            @forelse ($cargo->cargoProgramas->take(3) as $cp)
                                @php
                                    $estaEnFase = in_array($cp->program_id, $programasFaseIds);
                                @endphp
                                <span class="rounded-lg px-2 py-0.5 {{ $estaEnFase ? 'bg-[#E8F3EF] text-[#064C3D] font-bold ring-1 ring-emerald-300' : 'bg-slate-50 text-slate-600 ring-1 ring-slate-200' }}">
                                    {{ Str::limit($cp->program->name ?? 'Programa', 30) }}
                                    @if ($cp->tipo === 'exclusivo_si_presente')
                                        <span class="text-[9px] uppercase text-amber-700">(Exclusivo)</span>
                                    @endif
                                </span>
                            @empty
                                <span class="italic text-slate-400">Todos los programas afines al área</span>
                            @endforelse
                            @if ($cargo->cargoProgramas->count() > 3)
                                <span class="text-slate-400">+{{ $cargo->cargoProgramas->count() - 3 }} más</span>
                            @endif
                        </div>
                    </div>

                    {{-- Controles de activación y cupos --}}
                    <div class="flex flex-wrap items-center gap-3 shrink-0 self-end md:self-center">
                        {{-- Control de cupos --}}
                        <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-1.5 ring-1 ring-slate-200">
                            <label for="cupos-{{ $cargo->id }}" class="text-xs font-semibold text-slate-500">Cupos:</label>
                            <input type="number" id="cupos-{{ $cargo->id }}" min="1" max="50"
                                   value="{{ $cargo->cupos_activados }}"
                                   onchange="actualizarCupos({{ $fase->id }}, {{ $cargo->id }}, this.value)"
                                   class="w-14 rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-xs font-extrabold text-[#064C3D] focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                        </div>

                        {{-- Botón Activar / Desactivar --}}
                        <form method="POST" action="{{ route('talentohumano.activacion.toggle') }}" class="inline"
                              onsubmit="return handleToggle(event, {{ $fase->id }}, {{ $cargo->id }})">
                            @csrf
                            <input type="hidden" name="fase_id" value="{{ $fase->id }}">
                            <input type="hidden" name="cargo_id" value="{{ $cargo->id }}">
                            <input type="hidden" name="numero_aprendices" id="hidden-cupos-{{ $cargo->id }}" value="{{ $cargo->cupos_activados }}">

                            @if ($cargo->is_activado)
                                <button type="submit" id="btn-toggle-{{ $cargo->id }}"
                                        class="rounded-full bg-[#064C3D] px-5 py-2 text-xs font-bold text-white shadow-md shadow-[#064C3D]/20 hover:bg-[#043A2F] flex items-center gap-1.5 transition">
                                    <i class="fas fa-check text-[#34D399]"></i>
                                    <span>Activado</span>
                                </button>
                            @else
                                <button type="submit" id="btn-toggle-{{ $cargo->id }}"
                                        class="rounded-full px-5 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-300 hover:bg-[#E8F3EF] hover:text-[#064C3D] hover:ring-[#34D399] flex items-center gap-1.5 transition">
                                    <i class="fas fa-plus text-slate-400"></i>
                                    <span>Activar cargo</span>
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                    <i class="fas fa-filter"></i>
                </span>
                <p class="mt-3 font-semibold text-slate-700">No se encontraron cargos con los filtros seleccionados</p>
                <p class="mt-1 text-xs text-slate-400">Intenta cambiar el área, el nivel o el término de búsqueda.</p>
                <a href="{{ route('talentohumano.activacion.index', ['fase_id' => $fase->id ?? '']) }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-xs font-semibold text-white">
                    Ver todos los cargos
                </a>
            </div>
        @endforelse
    </div>

    {{-- MODAL: GESTIÓN DE FICHAS Y PROGRAMAS (HU-018) --}}
    <dialog id="modal-fichas" aria-labelledby="titulo-modal-fichas" class="m-auto w-[min(94vw,44rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/40">
        <div class="p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D]">
                        <i class="fas fa-users-rectangle"></i>
                    </span>
                    <div>
                        <h2 id="titulo-modal-fichas" class="text-lg font-bold text-slate-900">
                            Fichas participantes de la {{ $fase->romano }}
                        </h2>
                        <p class="text-xs text-slate-500">Gestión de fichas y asignación de programas (HU-018).</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-fichas').close()"
                        class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <i class="fas fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Fichas actuales en la fase --}}
            <div class="mt-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                    Fichas habilitadas para la convocatoria ({{ $fichasFase->count() }})
                </h3>

                @if ($fichasFase->isEmpty())
                    <p class="rounded-2xl bg-amber-50 p-4 text-xs font-medium text-amber-800">
                        <i class="fas fa-circle-exclamation mr-1"></i> Aún no has asociado fichas a esta fase. Agrega al menos una ficha abajo para que sus aprendices puedan postularse.
                    </p>
                @else
                    <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 rounded-2xl ring-1 ring-slate-200/80">
                        @foreach ($fichasFase as $ff)
                            <div class="flex flex-wrap items-center justify-between gap-2 p-3 text-xs hover:bg-slate-50">
                                <div>
                                    <p class="font-bold text-slate-800">
                                        Ficha {{ $ff->course->code ?? 'N/A' }}
                                    </p>
                                    @if ($ff->course && $ff->course->program)
                                        <p class="text-slate-500">
                                            <i class="fas fa-graduation-cap text-[#34D399] mr-1"></i> {{ $ff->course->program->name }}
                                        </p>
                                    @else
                                        <p class="font-bold text-amber-700 flex items-center gap-1">
                                            <i class="fas fa-triangle-exclamation"></i> Sin programa registrado
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    {{-- Asignar programa si falta (HU-018) --}}
                                    @if (!$ff->course || !$ff->course->program_id)
                                        <button type="button" onclick="abrirAsignarPrograma({{ $ff->course_id }}, '{{ $ff->course->code }}')"
                                                class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-900 hover:bg-amber-200">
                                            Asignar programa
                                        </button>
                                    @endif
                                    <form method="POST" action="{{ route('talentohumano.activacion.fichas.eliminar', $ff->id) }}" onsubmit="return confirm('¿Quitar ficha de la fase?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Quitar de la fase">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Agregar nueva ficha a la fase --}}
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    <i class="fas fa-plus-circle text-[#064C3D] mr-1"></i> Agregar ficha a la fase
                </h3>
                <form method="POST" action="{{ route('talentohumano.activacion.fichas.agregar') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="fase_id" value="{{ $fase->id }}">

                    <div>
                        <label for="select-course" class="block text-xs font-semibold text-slate-600 mb-1">
                            Seleccionar ficha de SICEFA:
                        </label>
                        <select id="select-course" name="course_id" required
                                class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                            <option value="">Selecciona una ficha disponible...</option>
                            @foreach ($cursosDisponibles as $c)
                                <option value="{{ $c->id }}">
                                    Ficha {{ $c->code }} - {{ $c->program->name ?? '⚠️ (Sin programa asignado en SICEFA)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Asignar programa manualmente si no lo tiene (HU-018) --}}
                    <div id="campo-asignar-programa" class="rounded-2xl bg-amber-50/70 p-3 ring-1 ring-amber-200/60">
                        <label for="select-programa-hu018" class="block text-xs font-bold text-amber-900 mb-1">
                            <i class="fas fa-link mr-1"></i> Asignar programa a la ficha (HU-018):
                        </label>
                        <p class="text-[11px] text-amber-800/80 mb-2">
                            Si SICEFA no entregó el programa de la ficha, selecciónalo de la lista institucional para que sus aprendices puedan optar a los cargos afines.
                        </p>
                        <select id="select-programa-hu018" name="program_id"
                                class="w-full rounded-xl border border-amber-300 bg-white p-2 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                            <option value="">(Dejar programa actual de SICEFA o seleccionar si falta)...</option>
                            @foreach ($programas as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sofia_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('modal-fichas').close()"
                                class="rounded-full px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                            Cerrar
                        </button>
                        <button type="submit"
                                class="rounded-full bg-[#064C3D] px-5 py-2 text-xs font-bold text-white shadow hover:bg-[#043A2F]">
                            <i class="fas fa-plus mr-1"></i> Vincular ficha a la fase
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    {{-- Script AJAX para toggle y actualización interactiva --}}
    @push('scripts')
        <script>
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Actualizar cupos en tiempo real al cambiar el número
            async function actualizarCupos(faseId, cargoId, valor) {
                const cupos = parseInt(valor) || 1;
                document.getElementById(`hidden-cupos-${cargoId}`).value = cupos;

                try {
                    const res = await fetch("{{ route('talentohumano.activacion.cupos') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            fase_id: faseId,
                            cargo_id: cargoId,
                            numero_aprendices: cupos
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const statCupos = document.getElementById('stat-cupos');
                        if (statCupos && data.total_cupos !== undefined) {
                            statCupos.textContent = data.total_cupos;
                        }
                    }
                } catch (e) {
                    console.error('Error al actualizar cupos:', e);
                }
            }

            // Manejar toggle asíncrono
            async function handleToggle(event, faseId, cargoId) {
                event.preventDefault();
                const btn = document.getElementById(`btn-toggle-${cargoId}`);
                const cupos = document.getElementById(`cupos-${cargoId}`).value;

                try {
                    const res = await fetch("{{ route('talentohumano.activacion.toggle') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            fase_id: faseId,
                            cargo_id: cargoId,
                            numero_aprendices: cupos
                        })
                    });
                    const data = await res.json();

                    if (data.success) {
                        const card = btn.closest('article');
                        if (data.estado === 'activado') {
                            btn.className = "rounded-full bg-[#064C3D] px-5 py-2 text-xs font-bold text-white shadow-md shadow-[#064C3D]/20 hover:bg-[#043A2F] flex items-center gap-1.5 transition";
                            btn.innerHTML = `<i class="fas fa-check text-[#34D399]"></i> <span>Activado</span>`;
                            card.classList.add('ring-[#064C3D]/30', 'border-l-4', 'border-l-[#064C3D]');
                            card.classList.remove('ring-slate-200/70');
                        } else {
                            btn.className = "rounded-full px-5 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-300 hover:bg-[#E8F3EF] hover:text-[#064C3D] hover:ring-[#34D399] flex items-center gap-1.5 transition";
                            btn.innerHTML = `<i class="fas fa-plus text-slate-400"></i> <span>Activar cargo</span>`;
                            card.classList.remove('ring-[#064C3D]/30', 'border-l-4', 'border-l-[#064C3D]');
                            card.classList.add('ring-slate-200/70');
                        }

                        // Actualizar contadores superiores
                        if (data.total_activados !== undefined) {
                            const statAct = document.getElementById('stat-activados');
                            if (statAct) statAct.textContent = data.total_activados;
                        }
                        if (data.total_cupos !== undefined) {
                            const statCupos = document.getElementById('stat-cupos');
                            if (statCupos) statCupos.textContent = data.total_cupos;
                        }
                    }
                } catch (e) {
                    console.error('Error en toggle:', e);
                    event.target.submit(); // fallback formulario si falla JS
                }
                return false;
            }
        </script>
    @endpush
</x-talentohumano::layouts.master>
