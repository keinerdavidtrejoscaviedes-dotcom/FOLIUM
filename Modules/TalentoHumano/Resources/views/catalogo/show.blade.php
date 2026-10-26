<x-talentohumano::layouts.master title="Manual de Funciones · {{ $cargo->nombre }}">
    {{-- Breadcrumbs y Cabecera --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <a href="{{ route('talentohumano.catalogo.index') }}" class="hover:text-[#064C3D] hover:underline">
                    Catálogo de cargos
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D] truncate max-w-[200px]">{{ $cargo->nombre }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-id-badge"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        {{ $cargo->nombre }}
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Perfil de cargo y manual de responsabilidades operativas y formativas (RF-019).
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fas fa-print"></i> Imprimir manual
            </button>
            <a href="{{ route('talentohumano.catalogo.index') }}"
               class="rounded-full bg-[#064C3D] hover:bg-[#043A2F] text-white px-4 py-2 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fas fa-arrow-left"></i> Volver al catálogo
            </a>
        </div>
    </div>

    {{-- Layout principal de 2 columnas --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Columna izquierda: Manual de Funciones y Responsabilidades --}}
        <div class="space-y-6">
            {{-- 1. Rol Principal y Propósito --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D] text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-bullseye text-emerald-600"></i>
                    <span>Rol principal y propósito del cargo</span>
                </h2>
                <div class="rounded-2xl bg-[#E8F3EF]/60 p-5 border border-[#34D399]/40 text-sm text-[#064C3D] font-medium leading-relaxed">
                    {{ $cargo->rol_principal ?: 'Liderar y ejecutar las actividades asignadas con base en los lineamientos de SENA Empresa y el Sistema Integrado de Gestión.' }}
                </div>
            </section>

            {{-- 2. Actividades y Tareas formativas --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D] text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-list-check text-emerald-600"></i>
                    <span>Actividades asignadas al cargo</span>
                </h2>
                <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line space-y-2">
                    {{ $cargo->actividades ?: 'Sin actividades específicas registradas.' }}
                </div>
            </section>

            {{-- 3. Responsabilidades en el Sistema Integrado de Gestión (SIG) --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D] text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-shield-halved text-emerald-600"></i>
                    <span>Responsabilidades en el SIG (Calidad, SST y Ambiental)</span>
                </h2>
                <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/70 p-4 rounded-2xl border border-slate-100">
                    {{ $cargo->responsabilidades_sig ?: 'Cumplir con las normas de bioseguridad, uso de EPP, clasificación de residuos y diligenciamiento oportuno de registros de calidad.' }}
                </div>
            </section>

            {{-- 4. Documentación y Registros a diligenciar --}}
            @if ($cargo->documentacion)
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="font-bold text-[#064C3D] text-base mb-3 flex items-center gap-2">
                        <i class="fas fa-folder-open text-emerald-600"></i>
                        <span>Documentación y formatos a cargo</span>
                    </h2>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                        {{ $cargo->documentacion }}
                    </div>
                </section>
            @endif
        </div>

        {{-- Columna derecha: Ficha Técnica y Programas Afines --}}
        <div class="space-y-6">
            {{-- Ficha Técnica Organizacional --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 border-t-4 border-[#064C3D]">
                <h3 class="font-extrabold text-[#064C3D] text-sm uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
                    Estructura Organizacional
                </h3>

                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-400">Nivel jerárquico:</dt>
                        <dd class="mt-0.5">
                            @php
                                $badgeClass = match ($cargo->nivel) {
                                    'Gerente' => 'bg-purple-100 text-purple-800',
                                    'Gestor' => 'bg-emerald-100 text-emerald-800',
                                    'Lider' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-sky-100 text-sky-800',
                                };
                            @endphp
                            <span class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider {{ $badgeClass }}">
                                {{ $cargo->nivel }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-400">Área funcional:</dt>
                        <dd class="mt-0.5 font-bold text-slate-800 text-sm">
                            {{ $cargo->area->nombre ?? 'General' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="font-semibold text-slate-400">Aprendices requeridos por fase:</dt>
                        <dd class="mt-0.5 font-bold text-slate-800">
                            {{ $cargo->numero_aprendices }} {{ $cargo->numero_aprendices == 1 ? 'aprendiz' : 'aprendices' }}
                        </dd>
                    </div>

                    @if ($cargo->macroproceso || $cargo->proceso)
                        <div>
                            <dt class="font-semibold text-slate-400">Proceso / Subproceso:</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">
                                {{ $cargo->macroproceso ? $cargo->macroproceso . ' · ' : '' }}{{ $cargo->proceso ?: 'Operativo' }}
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="font-semibold text-slate-400">Jefe inmediato / Superior:</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            @if ($cargo->cargoSuperior)
                                <a href="{{ route('talentohumano.catalogo.show', $cargo->cargoSuperior->id) }}"
                                   class="text-emerald-700 font-bold hover:underline">
                                    {{ $cargo->cargoSuperior->nombre }}
                                </a>
                            @else
                                {{ $cargo->jefe_inmediato_texto ?: 'Instructor Líder de Área' }}
                            @endif
                        </dd>
                    </div>

                    @if ($cargo->subordinados->isNotEmpty())
                        <div>
                            <dt class="font-semibold text-slate-400">Cargos bajo su coordinación ({{ $cargo->subordinados->count() }}):</dt>
                            <dd class="mt-1 space-y-1">
                                @foreach ($cargo->subordinados as $sub)
                                    <a href="{{ route('talentohumano.catalogo.show', $sub->id) }}"
                                       class="block rounded-lg bg-slate-50 px-2 py-1 text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 font-medium truncate">
                                        <i class="fas fa-angle-right text-[10px] text-slate-400 mr-1"></i> {{ $sub->nombre }}
                                    </a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- Programas de Formación SENA Afines --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-extrabold text-[#064C3D] text-sm uppercase tracking-wider mb-3 pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span>Programas Afines</span>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                        {{ $cargo->programs->count() }}
                    </span>
                </h3>
                <p class="text-xs text-slate-500 mb-3">
                    Aprendices de estos programas tienen afinidad directa para postularse a este cargo:
                </p>

                <div class="space-y-1.5">
                    @forelse ($cargo->programs as $prg)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-2.5 text-xs">
                            <p class="font-semibold text-slate-800">{{ $prg->name }}</p>
                            @if ($prg->sofia_code)
                                <p class="text-[10px] text-slate-400 mt-0.5">Código SOFIA: {{ $prg->sofia_code }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No hay programas restringidos: abierto a todas las áreas.</p>
                    @endforelse
                </div>
            </section>

            {{-- Requisitos y Competencias previas --}}
            @if ($cargo->requisitos)
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h3 class="font-extrabold text-[#064C3D] text-sm uppercase tracking-wider mb-2 pb-2 border-b border-slate-100">
                        Requisitos de Postulación
                    </h3>
                    <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                        {{ $cargo->requisitos }}
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-talentohumano::layouts.master>
