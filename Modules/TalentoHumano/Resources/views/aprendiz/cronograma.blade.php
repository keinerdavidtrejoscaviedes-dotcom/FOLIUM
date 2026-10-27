<x-talentohumano::layouts.aprendiz title="Cronograma del Proceso" activo="cronograma" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Cronograma del Proceso</h1>
                <p class="mt-1 text-sm text-slate-600">Proceso de selección y empalme de la Fase XXXVI · SENA Empresa.</p>
            </div>
            @if ($estado === 'publicado')
                <div class="rounded-2xl bg-white px-3.5 py-1.5 shadow-sm ring-1 ring-slate-200 text-xs">
                    <span class="text-slate-400 font-medium">Estado:</span>
                    <span class="font-bold text-[#064C3D] ml-1"><i class="fas fa-check-circle text-emerald-600 mr-1"></i>Aprobado</span>
                </div>
            @endif
        </div>

        @if ($estado === 'no-publicado')
            {{-- Escenario: Cronograma aún no publicado (HU-043 esc. 4) --}}
            <div class="entrada mt-8 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-3xl text-slate-400 mb-3">
                    <i class="fas fa-calendar-xmark"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">El cronograma del proceso de selección aún no ha sido publicado</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
                    El Gestor de Talento Humano y el Instructor Líder se encuentran definiendo los plazos oficiales de la convocatoria. Te notificaremos a tu correo institucional cuando sea publicado.
                </p>
                <div class="mt-6">
                    <a href="{{ route('talentohumano.aprendiz.convocatoria') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F] transition">
                        <i class="fas fa-bullhorn"></i> Explorar cargos del catálogo
                    </a>
                </div>
            </div>
        @else
            {{-- Escenario: Cronograma Publicado (HU-043 esc. 1) --}}
            <div class="mt-6 flex items-center gap-2 rounded-2xl bg-[#E8F3EF] px-4 py-3 text-xs text-[#064C3D] font-medium">
                <i class="fas fa-circle-check text-emerald-600 text-sm"></i>
                <span>Cronograma oficial aprobado por el <strong>Instructor Líder</strong> el 25/08/2026. Fechas vinculantes para todas las etapas.</span>
            </div>

            <ol class="relative mt-8 space-y-4 border-l-2 border-[#E8F3EF] pl-6 sm:pl-8">
                @foreach ($etapas as $etapa)
                    @php
                        $esActual = $etapa['estado'] === 'actual';
                        $esHecho = $etapa['estado'] === 'hecho';
                    @endphp
                    <li @class([
                        'relative rounded-3xl p-5 sm:p-6 transition duration-200',
                        'bg-white shadow-lg shadow-[#064C3D]/10 ring-2 ring-[#34D399]' => $esActual,
                        'bg-white ring-1 ring-slate-200/70 shadow-sm' => !$esActual,
                    ])>
                        {{-- Indicador circular sobre la línea de tiempo --}}
                        <span @class([
                            'absolute -left-[33px] sm:-left-[41px] top-6 h-4 w-4 rounded-full ring-4 transition',
                            'bg-[#064C3D] ring-[#E8F3EF]' => $esHecho,
                            'bg-[#34D399] ring-emerald-100 animate-pulse' => $esActual,
                            'bg-slate-300 ring-white' => !$esHecho && !$esActual,
                        ])></span>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 @class([
                                'text-base font-bold',
                                'text-[#064C3D]' => $esActual,
                                'text-slate-800' => $esHecho,
                                'text-slate-500' => !$esHecho && !$esActual,
                            ])>
                                {{ $etapa['titulo'] }}
                            </h3>

                            @if ($esActual)
                                <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-bold text-[#064C3D] flex items-center gap-1.5 shadow-xs">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span> Etapa en curso
                                </span>
                            @elseif ($esHecho)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 flex items-center gap-1">
                                    <i class="fas fa-check text-xs"></i> Completada
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-400">
                                    Pendiente
                                </span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
                            <i class="far fa-calendar text-slate-400"></i>
                            <span class="font-medium">{{ $etapa['fechas'] }}</span>
                        </p>
                    </li>
                @endforeach
            </ol>

            {{-- Botón para agregar a calendario (.ics) --}}
            <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200/70 pt-6">
                <button type="button" onclick="descargarCalendario()" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] shadow-sm ring-1 ring-slate-200 hover:bg-[#E8F3EF] transition">
                    <i class="fas fa-calendar-plus text-emerald-600"></i> Agregar a mi calendario (.ics)
                </button>
                <p class="text-xs text-slate-400">Recibirás alertas por correo 2 días antes de cada vencimiento.</p>
            </div>
        @endif

    </div>

    @push('scripts')
    <script>
        function descargarCalendario() {
            const icsContent = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//FOLIUM SENA Empresa//Talento Humano//ES\n" +
                "BEGIN:VEVENT\nSUMMARY:Convocatoria Talento Humano Fase XXXVI\nDESCRIPTION:Periodo de postulación y presentación de documentos en FOLIUM.\nSTATUS:CONFIRMED\nEND:VEVENT\n" +
                "END:VCALENDAR";
            const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.setAttribute('download', 'cronograma_fase_XXXVI.ics');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.mostrarAviso('Se descargó el cronograma oficial en formato de calendario (.ics).', 'info');
        }
    </script>
    @endpush
</x-talentohumano::layouts.aprendiz>
