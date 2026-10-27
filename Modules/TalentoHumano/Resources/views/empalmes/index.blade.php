<x-talentohumano::layouts.master title="Empalmes · Supervisión de entrega de cargos">
    {{-- Breadcrumbs y Cabecera --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Empalmes</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-handshake"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Gestión de Empalmes
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Supervisa la entrega de cargos entre aprendices salientes y receptores, inventarios y firmas digitales (RF-013).
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="rounded-full bg-emerald-100 px-3.5 py-1.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-300/50 flex items-center gap-1.5 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Fase {{ $fase->numero ?? '36' }} en curso
            </span>
        </div>
    </div>

    {{-- Tarjetas KPI / Métricas rápidas --}}
    <div class="folium-kpi-grid">
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Total empalmes</span>
                <span class="folium-kpi-icon">
                    <i class="fas fa-layer-group"></i>
                </span>
            </div>
            <p class="folium-kpi-value">{{ $total }}</p>
            <p class="folium-kpi-meta">Cargos en transición</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-warning-text)">En proceso</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-warning-bg); color: var(--color-warning-text)">
                    <i class="fas fa-spinner"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-warning-text)">{{ $enCurso }}</p>
            <p class="folium-kpi-meta">Inventario en verificación</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-success-text)">Firmados</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-success-bg); color: var(--color-success-text)">
                    <i class="fas fa-file-circle-check"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-success-text)">{{ $firmados }}</p>
            <p class="folium-kpi-meta">Acta de recibimiento completa</p>
        </div>

        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Pendientes</span>
                <span class="folium-kpi-icon">
                    <i class="fas fa-clock"></i>
                </span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-text-muted)">{{ $pendientes }}</p>
            <p class="folium-kpi-meta">Visita por confirmar</p>
        </div>
    </div>

    {{-- Notificaciones de éxito --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-[#E8F3EF] p-4 text-[#064C3D] flex items-center gap-3 shadow-sm">
            <i class="fas fa-circle-check text-xl text-emerald-600"></i>
            <div>
                <strong class="font-bold">¡Operación exitosa!</strong>
                <p class="text-xs sm:text-sm mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Filtros y Búsqueda --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form action="{{ route('talentohumano.empalmes.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por cargo o aprendiz..."
                       class="rounded-full border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40 w-64 shadow-sm">
            </div>

            <div class="flex gap-1.5 bg-slate-100 p-1 rounded-full text-xs font-semibold">
                <a href="{{ route('talentohumano.empalmes.index', ['estado' => 'todos']) }}"
                   class="rounded-full px-3 py-1 transition {{ !request('estado') || request('estado') === 'todos' ? 'bg-white text-[#064C3D] font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Todos
                </a>
                <a href="{{ route('talentohumano.empalmes.index', ['estado' => 'en_curso']) }}"
                   class="rounded-full px-3 py-1 transition {{ request('estado') === 'en_curso' ? 'bg-white text-amber-800 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    En curso
                </a>
                <a href="{{ route('talentohumano.empalmes.index', ['estado' => 'firmado']) }}"
                   class="rounded-full px-3 py-1 transition {{ request('estado') === 'firmado' ? 'bg-white text-emerald-800 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Firmados
                </a>
                <a href="{{ route('talentohumano.empalmes.index', ['estado' => 'pendiente']) }}"
                   class="rounded-full px-3 py-1 transition {{ request('estado') === 'pendiente' ? 'bg-white text-slate-800 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Pendientes
                </a>
            </div>
        </form>

        <p class="text-xs text-slate-400 font-medium">
            Mostrando <strong>{{ $empalmes->count() }}</strong> procesos de empalme
        </p>
    </div>

    {{-- Tabla de Empalmes --}}
    <section class="folium-card">
        <div class="folium-table-wrapper">
            <table class="folium-table">
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th>Aprendiz Saliente</th>
                        <th>Aprendiz Receptor</th>
                        <th style="text-align: center">Inventario</th>
                        <th style="text-align: center">Firmas Digitales</th>
                        <th>Estado</th>
                        <th style="text-align: right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($empalmes as $emp)
                        @php
                            $sal = $emp->participantes->firstWhere('papel', 'saliente');
                            $rec = $emp->participantes->firstWhere('papel', 'receptor');

                            $totalInv = $emp->inventarios->count();
                            $verifInv = $emp->inventarios->where('verificado', true)->count();

                            $firmasCount = $emp->acta ? $emp->acta->firmas->count() : 0;
                        @endphp
                        <tr class="hover:bg-[#E8F3EF]/30 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full {{ $emp->estado === 'firmado' ? 'bg-emerald-500' : ($emp->estado === 'en_curso' ? 'bg-amber-500' : 'bg-slate-300') }}"></span>
                                    {{ $emp->cargo->nombre ?? 'Cargo' }}
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium">Fase {{ $emp->fase->numero ?? '36' }} · Entrega individual</span>
                            </td>

                            <td class="px-4 py-4 text-xs">
                                @if ($sal && $sal->asignacion && $sal->asignacion->person)
                                    <p class="font-semibold text-slate-800">{{ $sal->asignacion->person->full_name }}</p>
                                    <p class="text-slate-400 text-[11px]">Saliente (Fase 35)</p>
                                @else
                                    <span class="text-slate-400 italic">Por definir</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-xs">
                                @if ($rec && $rec->asignacion && $rec->asignacion->person)
                                    <p class="font-semibold text-slate-800">{{ $rec->asignacion->person->full_name }}</p>
                                    <p class="text-emerald-700 font-medium text-[11px]">Receptor (Fase 36)</p>
                                @else
                                    <span class="text-slate-400 italic">Por asignar</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="font-bold text-xs {{ $totalInv > 0 && $verifInv == $totalInv ? 'text-emerald-700' : 'text-slate-700' }}">
                                        {{ $verifInv }}/{{ $totalInv }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">verificados</span>
                                </div>
                                @if ($totalInv > 0)
                                    <div class="w-16 bg-slate-100 rounded-full h-1.5 mx-auto mt-1 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $verifInv == $totalInv ? 'bg-emerald-500' : 'bg-amber-400' }}"
                                             style="width: {{ round(($verifInv / $totalInv) * 100) }}%"></div>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-center">
                                <div class="inline-flex items-center gap-1 text-xs">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $firmasCount >= 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        <i class="fas fa-signature mr-0.5"></i> {{ $firmasCount }}/3 firmas
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                @php
                                    $chipClass = match ($emp->estado) {
                                        'firmado' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                                        'en_curso' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                                        default => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                                    };
                                    $chipTexto = match ($emp->estado) {
                                        'firmado' => 'Firmado',
                                        'en_curso' => 'En curso',
                                        default => 'Pendiente',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $chipClass }}">
                                    {{ $chipTexto }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('talentohumano.empalmes.show', $emp->id) }}"
                                   class="folium-btn folium-btn-primary folium-btn-sm">
                                    <span>Gestionar</span>
                                    <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-xs text-slate-400">
                                <i class="fas fa-handshake text-3xl text-slate-300 mb-2 block"></i>
                                No se encontraron procesos de empalme con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-talentohumano::layouts.master>
