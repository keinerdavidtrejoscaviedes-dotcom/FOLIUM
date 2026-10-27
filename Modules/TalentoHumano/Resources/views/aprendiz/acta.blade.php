<x-talentohumano::layouts.aprendiz title="Acta de Seleccionados" activo="acta" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Acta de Seleccionados</h1>
                <p class="mt-1 text-sm text-slate-600">Fase XXXVI · Publicación oficial de asignación de cargos.</p>
            </div>
            @if ($estado === 'publicada')
                <button type="button" onclick="descargarActaPdf()" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold text-[#064C3D] shadow-sm ring-1 ring-slate-200 hover:bg-[#E8F3EF] transition">
                    <i class="fas fa-file-pdf text-red-500"></i> Descargar acta oficial (.pdf)
                </button>
            @endif
        </div>

        @if ($estado === 'no-publicada')
            <div class="entrada mt-8 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-50 text-3xl text-amber-500 mb-3">
                    <i class="fas fa-hourglass-half"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">El acta de seleccionados aún no ha sido publicada</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
                    El comité de selección se encuentra en proceso de validación y consolidación de resultados. La fecha oficial de publicación según cronograma es el <strong>28/10/2026</strong>.
                </p>
            </div>
        @elseif ($estado === 'no-postulante')
            <div class="entrada mt-8 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-red-200">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-red-50 text-3xl text-red-500 mb-3">
                    <i class="fas fa-lock"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">Acceso restringido</h2>
                <p class="mt-1 text-sm text-slate-500">El acta de seleccionados solo está habilitada para los aprendices postulantes en esta convocatoria (RN-066).</p>
            </div>
        @else
            {{-- Documento Oficial en Pantalla --}}
            <article class="entrada mt-6 rounded-3xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-200/70">
                
                {{-- Encabezado Institucional --}}
                <div class="flex items-center gap-3.5 border-b-2 border-[#064C3D] pb-5">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-2xl text-[#064C3D]">
                        <i class="fas fa-seedling"></i>
                    </span>
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[.2em] text-slate-400">
                            SENA Empresa · Centro La Angostura
                        </p>
                        <h2 class="text-xl font-extrabold text-[#064C3D]">
                            Acta Oficial de Seleccionados · Fase XXXVI
                        </h2>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 border-b border-slate-100 pb-3">
                    <span>Publicada oficialmente el <strong>28/10/2026</strong></span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-semibold text-slate-600">Modo solo lectura</span>
                </div>

                {{-- Banner de Identificación Propia --}}
                <div class="mt-4 rounded-2xl bg-[#E8F3EF] p-4 text-xs sm:text-sm text-[#064C3D] font-medium flex items-center gap-2.5">
                    <i class="fas fa-user-check text-emerald-600 text-base"></i>
                    <span>
                        Fuiste seleccionado como <strong>Líder de Unidad de Formación Productiva Cacao</strong>. Procede a coordinar tu empalme.
                    </span>
                </div>

                {{-- Buscador en Tiempo Real --}}
                <div class="relative mt-5">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="search" id="buscar-acta" placeholder="Buscar por nombre de cargo o de aprendiz…" class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-xs focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">
                </div>

                {{-- Tabla / Lista de Seleccionados --}}
                <ul id="lista-acta" class="mt-4 divide-y divide-slate-100 text-sm">
                    @foreach ($seleccionados as $item)
                        @php
                            $esTuCargo = $item['es_tu_cargo'] ?? false;
                        @endphp
                        <li @class([
                            'fila-acta flex flex-wrap items-center justify-between gap-2 py-3 px-3 rounded-xl transition',
                            'bg-[#E8F3EF]/60 font-semibold text-[#064C3D] ring-1 ring-[#34D399]/50' => $esTuCargo,
                            'hover:bg-slate-50 text-slate-800' => !$esTuCargo,
                        ])>
                            <div>
                                <span class="font-bold">{{ $item['cargo'] }}</span>
                                @if ($item['reubicado'])
                                    <span class="ml-2 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800">
                                        Reubicación
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs sm:text-sm {{ $esTuCargo ? 'font-black text-[#064C3D]' : 'text-slate-600' }}">
                                {{ $item['aprendiz'] }}
                            </div>
                        </li>
                    @endforeach
                    <li class="py-3 text-center text-xs text-slate-400 font-medium">
                        … y 57 aprendices más seleccionados en el Centro de Formación.
                    </li>
                </ul>

                {{-- Pie del Documento --}}
                <div class="mt-6 border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
                    <span>Documento avalado por el Instructor Líder de SENA Empresa y el Comité de Selección.</span>
                    <span class="font-semibold text-slate-600">ID: ACTA-TH-2026-F36</span>
                </div>

            </article>
        @endif

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('buscar-acta');
            const filas = document.querySelectorAll('.fila-acta');

            input?.addEventListener('input', () => {
                const q = input.value.toLowerCase().trim();
                filas.forEach(f => {
                    const texto = f.textContent.toLowerCase();
                    f.style.display = (!q || texto.includes(q)) ? '' : 'none';
                });
            });
        });

        function descargarActaPdf() {
            window.mostrarAviso('Generando y descargando acta_seleccionados_fase_XXXVI.pdf…', 'info');
        }
    </script>
    @endpush
</x-talentohumano::layouts.aprendiz>
