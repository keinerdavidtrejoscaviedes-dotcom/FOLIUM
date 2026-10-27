@use('Modules\TalentoHumano\Entities\PlantillaActa')
@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $iconos = ['Administrativa' => 'fa-briefcase', 'Sistema Integrado de Gestión' => 'fa-shield-halved', 'Innovación' => 'fa-lightbulb', 'Producción' => 'fa-industry',
        'Pecuaria' => 'fa-cow', 'Agrícola' => 'fa-seedling', 'Agroindustrial' => 'fa-jar', 'Ambiental' => 'fa-leaf', 'Comercial' => 'fa-store'];
@endphp

<x-talentohumano::layouts.lider title="Plantillas de acta" activo="plantillas">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Plantillas de acta</h1>
            <p class="mt-1 text-sm text-slate-600">Las actas de recibimiento del cargo por área: secciones, columnas del inventario, lista base de implementos y firmas.</p>
        </div>
        @if ($esAdministrador && $vigentes->isNotEmpty())
            <a href="{{ route('talentohumano.lider.plantillas.create') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 transition hover:bg-[#043A2F]">
                <i class="fas fa-plus" aria-hidden="true"></i> Nueva plantilla
            </a>
        @endif
    </div>

    @if ($vigentes->isEmpty())
        <div class="relative isolate mt-6 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white sm:p-10">
            <div class="absolute inset-0 -z-10" style="background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 22px 22px" aria-hidden="true"></div>
            <i class="fas fa-file-signature absolute -bottom-6 -right-2 -z-10 text-[9rem] text-white/5" aria-hidden="true"></i>
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20"><i class="fas fa-file-circle-plus" aria-hidden="true"></i></span>
            <h2 class="mt-5 text-xl font-extrabold sm:text-2xl">Aún no hay plantillas de acta</h2>
            <p class="mt-2 max-w-xl text-emerald-50/85">
                Sin una plantilla vigente no se pueden iniciar los empalmes. Empieza por la <strong>plantilla general</strong>: la usarán todas las áreas que no tengan una propia.
            </p>
            @if ($esAdministrador)
                <a href="{{ route('talentohumano.lider.plantillas.create', ['area' => 'general']) }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#34D399] px-5 py-2.5 text-sm font-semibold text-[#043A2F] hover:bg-emerald-300">
                    <i class="fas fa-plus" aria-hidden="true"></i> Crear plantilla general
                </a>
            @endif
        </div>
    @else
        @unless ($hayGeneral)
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
                <span class="min-w-0 flex-1"><strong>No hay plantilla general.</strong> Las áreas sin plantilla propia no podrán iniciar empalmes.</span>
                @if ($esAdministrador)
                    <a href="{{ route('talentohumano.lider.plantillas.create', ['area' => 'general']) }}" class="rounded-full bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600">Crear plantilla general</a>
                @endif
            </div>
        @endunless

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Plantillas vigentes</p><p class="text-2xl font-extrabold text-[#064C3D]">{{ $vigentes->count() }}</p></div>
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Áreas con plantilla propia</p><p class="text-2xl font-extrabold text-[#064C3D]">{{ $totalAreas - $areasSinPlantilla->count() }} <span class="text-sm font-semibold text-slate-400">de {{ $totalAreas }}</span></p></div>
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70"><p class="text-xs text-slate-500">Actas generadas</p><p class="text-2xl font-extrabold text-[#064C3D]">{{ $totalActas }}</p></div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            @foreach ($vigentes as $plantilla)
                <article class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:ring-[#34D399]/60">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-lg {{ $plantilla->area_id ? 'bg-[#E8F3EF] text-[#064C3D]' : 'bg-[#064C3D] text-[#34D399]' }}">
                            <i class="fas {{ $plantilla->area_id ? ($iconos[$plantilla->nombreArea()] ?? 'fa-file-signature') : 'fa-layer-group' }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-slate-900">{{ $plantilla->nombre }}</h2>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">Vigente · v{{ $plantilla->version }}</span>
                            </div>
                            <p class="text-sm text-slate-500">{{ $plantilla->area_id ? 'Área ' . $plantilla->nombreArea() : 'Para las áreas sin plantilla propia' }}</p>
                        </div>
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Actas generadas</dt><dd class="text-lg font-extrabold text-[#064C3D]">{{ $actasPorArea[$plantilla->area_id ?? 0] ?? 0 }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Implementos base</dt><dd class="text-lg font-extrabold text-[#064C3D]">{{ count($plantilla->items_base ?? []) }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Firmantes</dt><dd class="text-lg font-extrabold text-[#064C3D]">{{ count($plantilla->firmantes) }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-500">Inventario:</span>
                        @foreach (array_merge(['Implemento'], array_column($plantilla->columnas_inventario, 'nombre')) as $columna)
                            <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-[11px] font-semibold text-[#064C3D]">{{ $columna }}</span>
                        @endforeach
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-500">Firman:</span>
                        @foreach ($plantilla->firmantes as $firmante)
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ PlantillaActa::FIRMANTES[$firmante][0] }}</span>
                        @endforeach
                    </div>
                    <div class="flex-1"></div>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4 text-sm">
                        <span class="text-xs text-slate-400">Actualizada el {{ $plantilla->updated_at->format('d/m/Y') }}</span>
                        <div class="flex gap-1">
                            <a href="{{ route('talentohumano.lider.plantillas.show', $plantilla) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold text-[#064C3D] hover:bg-[#E8F3EF]"><i class="fas fa-eye mr-1" aria-hidden="true"></i>Vista previa y versiones</a>
                            @if ($esAdministrador)
                                <a href="{{ route('talentohumano.lider.plantillas.edit', $plantilla) }}" class="rounded-full bg-[#064C3D] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#043A2F]"><i class="fas fa-pen mr-1" aria-hidden="true"></i>Editar</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($areasSinPlantilla->isNotEmpty())
            <section class="mt-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D]">Áreas que usan la plantilla general</h2>
                <p class="mt-1 text-sm text-slate-600">No tienen plantilla propia: sus empalmes se generan con la plantilla general (RN-053).</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($areasSinPlantilla as $area)
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">{{ $area }}</span>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</x-talentohumano::layouts.lider>
