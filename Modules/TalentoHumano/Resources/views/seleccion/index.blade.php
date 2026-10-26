<x-talentohumano::layouts.master title="Selección y actas">
    {{-- Breadcrumbs y Cabecera --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Selección y actas</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-user-check"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Propuestas de selección
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Valida a los seleccionados, haz reubicaciones y genera el acta de seleccionados (HU-038 · RF-010).
                    </p>
                </div>
            </div>
        </div>

        {{-- Acceso a Reubicaciones (Mockup INS-04) --}}
        <a href="{{ route('talentohumano.seleccion.reubicaciones') }}" class="folium-btn folium-btn-mint">
            <i class="fas fa-arrows-rotate text-emerald-600"></i>
            <span>Comité de reubicaciones (3)</span>
        </a>
    </div>

    {{-- Tarjeta de Propuestas de selección (Mockup INS-01 / INS-06) --}}
    <section class="folium-card">
        <div class="folium-card-header">
            <div>
                <h2 class="folium-card-title">
                    <i class="fas fa-user-check text-emerald-600"></i>
                    <span>Cargos en proceso de selección · {{ $fase->romano ?? 'Fase actual' }}</span>
                </h2>
                <p class="folium-card-subtitle">
                    Calculadas por puntaje total y jerarquía de preferencia. Cada cargo se valida una sola vez: tú o el Instructor Líder.
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="cambiarPestana('pendientes')" id="tab-pendientes"
                        class="folium-btn folium-btn-primary folium-btn-sm">
                    Pendientes <span class="opacity-80">({{ $pendientes->count() }})</span>
                </button>
                <button type="button" onclick="cambiarPestana('validadas')" id="tab-validadas"
                        class="folium-btn folium-btn-secondary folium-btn-sm">
                    Validadas <span class="opacity-80">({{ $validadas->count() }})</span>
                </button>
            </div>
        </div>

        {{-- Panel Pendientes --}}
        <div id="panel-pendientes" class="folium-table-wrapper">
            <table class="folium-table">
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th style="text-align: center">Cupos</th>
                        <th>Propuesta del sistema</th>
                        <th>Estado</th>
                        <th style="text-align: right"><span class="sr-only">Acción</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendientes as $item)
                        <tr class="hover:bg-[#E8F3EF]/30 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-800">{{ $item->cargo_nombre }}</td>
                            <td class="px-4 py-3.5 text-center text-xs font-bold text-slate-600">{{ $item->cupos }}</td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">{{ $item->propuesta }}</td>
                            <td class="px-4 py-3.5">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $item->chip }}">
                                    {{ $item->estado }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('talentohumano.seleccion.validar', $item->id) }}"
                                   class="inline-flex rounded-full bg-[#064C3D] text-white hover:bg-[#043A2F] px-4 py-1.5 text-xs font-bold shadow-sm transition">
                                    {{ $item->accion }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-xs text-slate-400">
                                <i class="fas fa-circle-check text-emerald-500 text-lg mb-1 block"></i>
                                No hay cargos pendientes de validación.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Panel Validadas --}}
        <div id="panel-validadas" class="folium-table-wrapper hidden">
            <table class="folium-table">
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th style="text-align: center">Cupos</th>
                        <th>Aspirante seleccionado</th>
                        <th>Estado</th>
                        <th style="text-align: right"><span class="sr-only">Acción</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($validadas as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-800">{{ $item->cargo_nombre }}</td>
                            <td class="px-4 py-3.5 text-center text-xs font-bold text-slate-600">{{ $item->cupos }}</td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">{{ $item->propuesta }}</td>
                            <td class="px-4 py-3.5">
                                <span class="folium-badge folium-badge-success">
                                    {{ $item->estado }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('talentohumano.seleccion.validar', $item->id) }}"
                                   class="folium-btn folium-btn-secondary folium-btn-sm">
                                    {{ $item->accion }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-xs text-slate-400">
                                Aún no has validado propuestas de selección.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @push('scripts')
        <script>
            function cambiarPestana(tipo) {
                const btnPen = document.getElementById('tab-pendientes');
                const btnVal = document.getElementById('tab-validadas');
                const pPen = document.getElementById('panel-pendientes');
                const pVal = document.getElementById('panel-validadas');

                if (tipo === 'pendientes') {
                    btnPen.className = "rounded-full bg-[#064C3D] px-4 py-1.5 text-xs font-bold text-white shadow-sm transition";
                    btnVal.className = "rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition";
                    pPen.classList.remove('hidden');
                    pVal.classList.add('hidden');
                } else {
                    btnVal.className = "rounded-full bg-[#064C3D] px-4 py-1.5 text-xs font-bold text-white shadow-sm transition";
                    btnPen.className = "rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition";
                    pVal.classList.remove('hidden');
                    pPen.classList.add('hidden');
                }
            }
        </script>
    @endpush
</x-talentohumano::layouts.master>
