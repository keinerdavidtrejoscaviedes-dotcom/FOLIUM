<x-talentohumano::layouts.aprendiz title="Convocatoria y Cargos" activo="convocatoria" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-4xl">
        
        {{-- Encabezado y Acción Principal --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Convocatoria Fase XXXVI</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Cargos del catálogo disponibles para tu ficha (<strong>2902187</strong> · Gestión de la Producción Agrícola).
                </p>
            </div>

            @if ($estado === 'abierta')
                <a href="{{ route('talentohumano.aprendiz.postularme') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition transform hover:scale-[1.02]">
                    <i class="fas fa-file-signature"></i> Postularme a 3 cargos <i class="fas fa-arrow-right"></i>
                </a>
            @elseif ($estado === 'por-abrir')
                <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-2 rounded-full bg-slate-300 px-6 py-3 text-sm font-bold text-slate-500 shadow-xs">
                    <i class="fas fa-lock"></i> Postularme (Abre 01/09)
                </button>
            @else
                <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-2 rounded-full bg-slate-200 px-6 py-3 text-sm font-semibold text-slate-500">
                    <i class="fas fa-door-closed"></i> Convocatoria cerrada
                </button>
            @endif
        </div>

        {{-- Banners de Estado de Convocatoria (HU-044) --}}
        @if ($estado === 'abierta')
            <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl bg-[#E8F3EF] px-5 py-3.5 text-sm text-[#064C3D] shadow-xs">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-3 w-3 rounded-full bg-[#34D399]"></span>
                </span>
                <span>Convocatoria abierta del <strong>01/09/2026</strong> al <strong>12/09/2026</strong> hasta las 11:59 p. m. Elige exactamente 3 cargos por orden de preferencia.</span>
            </div>
        @elseif ($estado === 'por-abrir')
            <div class="mt-5 flex items-center gap-3 rounded-2xl bg-sky-50 border border-sky-200 px-5 py-3.5 text-sm text-sky-900 shadow-xs">
                <i class="fas fa-clock text-sky-500 text-base"></i>
                <span>La postulación abre el <strong>01/09/2026</strong> según el cronograma. Puedes consultar los cargos y requisitos para preparar tus documentos.</span>
            </div>
        @elseif ($estado === 'cerrada')
            <div class="mt-5 flex items-center gap-3 rounded-2xl bg-slate-100 border border-slate-200 px-5 py-3.5 text-sm text-slate-700 shadow-xs">
                <i class="fas fa-door-closed text-slate-500 text-base"></i>
                <span>La convocatoria de la Fase XXXVI finalizó el 12/09/2026. Si te postulaste, puedes hacer seguimiento en <strong>«Mi postulación»</strong>.</span>
            </div>
        @endif

        {{-- Barra de Búsqueda y Filtros en Tiempo Real --}}
        <div class="mt-6 grid gap-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:grid-cols-[minmax(0,1fr)_13rem]">
            <label class="relative">
                <span class="sr-only">Buscar cargo</span>
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="search" id="input-busqueda" placeholder="Buscar por nombre de cargo o funciones…" class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
            </label>
            <select id="select-area" aria-label="Filtrar por Área" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 font-medium text-slate-700">
                <option value="">Todas las áreas</option>
                <option value="Agrícola">Área Agrícola</option>
                <option value="Administrativa">Área Administrativa</option>
                <option value="Ambiental">Área Ambiental</option>
            </select>
        </div>

        {{-- Lista de Cargos Disponibles --}}
        <div id="lista-cargos" class="mt-4 space-y-3">
            @foreach ($cargos as $cargo)
                @php
                    $chipNivel = match($cargo['nivel']) {
                        'Gerente' => 'bg-[#043A2F] text-white',
                        'Gestor' => 'bg-[#064C3D] text-white',
                        'Líder' => 'bg-emerald-100 text-emerald-800',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <details class="item-cargo group rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70 transition duration-200 open:shadow-md" data-nombre="{{ strtolower($cargo['nombre']) }}" data-area="{{ $cargo['area'] }}">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-5 sm:p-6">
                        <div class="flex-1 min-w-[220px]">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">{{ $cargo['nombre'] }}</h3>
                                @if ($cargo['es_alterno'])
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Perfil alterno</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Área {{ $cargo['area'] }} · {{ $cargo['cupos'] }} {{ $cargo['cupos'] === 1 ? 'cupo disponible' : 'cupos disponibles' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $chipNivel }}">{{ $cargo['nivel'] }}</span>
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-xs text-slate-400 group-open:rotate-180 group-open:bg-[#E8F3EF] group-open:text-[#064C3D] transition">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                    </summary>

                    <div class="space-y-4 border-t border-slate-100 p-5 sm:p-6 text-sm bg-slate-50/50 rounded-b-3xl">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Propósito del cargo</p>
                            <p class="mt-1 text-slate-700 leading-relaxed">{{ $cargo['proposito'] }}</p>
                        </div>

                        @if ($cargo['es_alterno'] && !empty($cargo['nota']))
                            <div class="rounded-2xl bg-sky-50 border border-sky-200 p-3.5 text-xs text-sky-900 flex items-start gap-2.5">
                                <i class="fas fa-circle-info text-sky-600 mt-0.5"></i>
                                <span>{{ $cargo['nota'] }}</span>
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Documentos obligatorios requeridos</p>
                            <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach ($cargo['documentos'] as $doc)
                                    <li class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-200/70">
                                        <i class="fas fa-file-pdf text-red-500"></i> {{ $doc }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <span class="text-xs text-slate-500">Modelo según el Manual GTH-M-001.</span>
                            @if ($estado === 'abierta')
                                <a href="{{ route('talentohumano.aprendiz.postularme') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#064C3D] hover:underline">
                                    <i class="fas fa-check-to-slot"></i> Postularme a este cargo
                                </a>
                            @endif
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <p class="mt-5 text-center text-xs text-slate-500">
            <i class="fas fa-shield-halved mr-1 text-emerald-600"></i> Mostrando solo cargos autorizados para la ficha 2902187 (RN-007, RN-040).
        </p>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputBusqueda = document.getElementById('input-busqueda');
            const selectArea = document.getElementById('select-area');
            const items = document.querySelectorAll('.item-cargo');

            function filtrar() {
                const q = inputBusqueda.value.toLowerCase().trim();
                const area = selectArea.value;

                items.forEach(item => {
                    const nombre = item.dataset.nombre;
                    const itemArea = item.dataset.area;
                    const coincideTexto = !q || nombre.includes(q);
                    const coincideArea = !area || itemArea === area;

                    item.style.display = (coincideTexto && coincideArea) ? '' : 'none';
                });
            }

            inputBusqueda.addEventListener('input', filtrar);
            selectArea.addEventListener('change', filtrar);
        });
    </script>
    @endpush
</x-talentohumano::layouts.aprendiz>
