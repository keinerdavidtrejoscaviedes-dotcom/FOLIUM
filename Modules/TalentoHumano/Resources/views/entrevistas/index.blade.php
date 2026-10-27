<x-talentohumano::layouts.master title="Mis entrevistas">
    {{-- Breadcrumbs y Cabecera (Mockup INS-07) --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Mis entrevistas</span>
            </nav>
            <p class="text-sm font-semibold text-emerald-700">Hola, {{ $user->person->first_name ?? $user->name }}</p>
            <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                Mis entrevistas
            </h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                Solo ves las entrevistas y los aspirantes que tienes asignados en la convocatoria de la {{ $fase->romano ?? 'Fase actual' }} (HU-042).
            </p>
        </div>

        {{-- Acceso a Mi formato de entrevista (HU-040) --}}
        <a href="{{ route('talentohumano.entrevistas.formato') }}"
           class="rounded-full bg-[#E8F3EF] px-5 py-2.5 text-xs font-bold text-[#064C3D] ring-1 ring-[#34D399]/40 hover:bg-emerald-100 flex items-center gap-2 transition shadow-sm">
            <i class="fas fa-list-check text-emerald-600"></i>
            <span>Mi formato de entrevista</span>
        </a>
    </div>

    {{-- Métricas de entrevistas asignadas (Mockup INS-07) --}}
    <div class="folium-kpi-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label">Asignadas</span>
                <span class="folium-kpi-icon"><i class="fas fa-calendar-check"></i></span>
            </div>
            <p class="folium-kpi-value">{{ $totalAsignadas }}</p>
            <p class="folium-kpi-meta">Citas programadas</p>
        </div>
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-success-text)">Calificadas</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-success-bg); color: var(--color-success-text)"><i class="fas fa-check"></i></span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-success-text)">{{ $totalCalificadas }}</p>
            <p class="folium-kpi-meta">Evaluación completada</p>
        </div>
        <div class="folium-kpi-card">
            <div class="folium-kpi-top">
                <span class="folium-kpi-label" style="color: var(--color-warning-text)">Por calificar</span>
                <span class="folium-kpi-icon" style="background-color: var(--color-warning-bg); color: var(--color-warning-text)"><i class="fas fa-hourglass-half"></i></span>
            </div>
            <p class="folium-kpi-value" style="color: var(--color-warning-text)">{{ $totalPorCalificar }}</p>
            <p class="folium-kpi-meta">Pendientes de calificar</p>
        </div>
    </div>

    {{-- Lista de Entrevistas Asignadas (Mockup INS-07) --}}
    <div class="space-y-4">
        @forelse ($entrevistas as $entr)
            @php
                $aspirante = $entr->postulaciones->first();
                $persona = $aspirante?->person;
                $calificacion = $entr->calificaciones->first();
                $esCalificada = (bool)$calificacion;

                // Otros evaluadores presentes en la misma entrevista
                $otrosEvaluadores = $entr->evaluadores
                    ->filter(fn ($ev) => $ev->person_id != (auth()->user()->person_id ?? 1))
                    ->map(fn ($ev) => $ev->person->first_name ?? 'Evaluador')
                    ->join(', ');
            @endphp
            <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 {{ $esCalificada ? 'ring-slate-200/70' : 'ring-amber-200 border-l-4 border-l-amber-400' }} hover:shadow-md transition">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <i class="fas fa-screwdriver-wrench text-[#064C3D]"></i>
                            <span>{{ ucfirst($entr->tipo) }} · {{ ucfirst($entr->modalidad) }} · {{ $entr->fecha_hora ? $entr->fecha_hora->isoFormat('ddd D MMM · h:mm a') : 'Fecha por programar' }}</span>
                        </p>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">
                            {{ $aspirante ? $aspirante->nombre_completo : 'Aspirante por definir' }}
                            <span class="text-sm font-normal text-slate-500">· ficha {{ $aspirante->course->code ?? 'N/A' }}</span>
                        </h2>

                        {{-- Cargos a los que aspira --}}
                        <div class="mt-1 flex flex-wrap gap-1.5 text-xs text-slate-600">
                            @if ($aspirante)
                                @foreach ($aspirante->opciones as $op)
                                    <span class="rounded-md bg-slate-50 px-2 py-0.5 text-slate-600 ring-1 ring-slate-200">
                                        {{ $op->cargo_nombre }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        {{-- Lugar y otros evaluadores --}}
                        <p class="mt-2 text-xs text-slate-500 flex flex-wrap items-center gap-3">
                            <span><i class="fas fa-location-dot mr-1 text-red-500"></i>{{ $entr->lugar }}</span>
                            @if ($otrosEvaluadores)
                                <span><i class="fas fa-user-group mr-1 text-[#34D399]"></i>con {{ $otrosEvaluadores }}</span>
                            @endif
                        </p>
                    </div>

                    {{-- Chip de Estado --}}
                    <div>
                        @if ($esCalificada)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 flex items-center gap-1">
                                <i class="fas fa-check-circle"></i> Calificada · {{ (int)$calificacion->puntaje }} pts
                            </span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 flex items-center gap-1">
                                <i class="fas fa-clock"></i> Por calificar
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Acciones (Mockup INS-07) --}}
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                    @if ($aspirante)
                        <a href="{{ route('talentohumano.postulaciones.show', $aspirante->id) }}"
                           class="rounded-full px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 flex items-center gap-1.5 transition">
                            <i class="fas fa-file-pdf text-red-500"></i>
                            <span>Hoja de vida y propuestas</span>
                        </a>

                        <a href="{{ route('talentohumano.entrevistas.calificar', ['entrevista' => $entr->id, 'postulacion' => $aspirante->id]) }}"
                           class="rounded-full {{ $esCalificada ? 'text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF]' : 'bg-[#064C3D] text-white hover:bg-[#043A2F]' }} px-4 py-1.5 text-xs font-semibold flex items-center gap-1.5 shadow-sm transition">
                            <i class="fas {{ $esCalificada ? 'fa-eye' : 'fa-pen-to-square' }}"></i>
                            <span>{{ $esCalificada ? 'Ver mi calificación' : 'Calificar' }}</span>
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <i class="fas fa-calendar-xmark mb-2 block text-3xl text-slate-300"></i>
                <p class="font-semibold text-slate-700">No tiene entrevistas asignadas</p>
                <p class="mt-1 text-xs text-slate-500">
                    Cuando el Gestor de Talento Humano te cite, tus entrevistas aparecerán listadas aquí con su cronograma.
                </p>
            </div>
        @endforelse
    </div>
</x-talentohumano::layouts.master>
