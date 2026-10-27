@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $vigente = $versiones->firstWhere('estado', 'vigente');
    $nombre = fn ($persona) => $persona ? trim($persona->first_name . ' ' . $persona->first_last_name) : 'Sistema';
@endphp

<x-talentohumano::layouts.lider :title="$plantilla->nombre" activo="plantillas">
    <a href="{{ route('talentohumano.lider.plantillas.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Plantillas de acta
    </a>
    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">{{ $seleccionada->nombre }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $plantilla->area_id ? 'Área ' . $plantilla->nombreArea() : 'Plantilla general' }} · Cada versión conserva las actas que se generaron con ella.</p>
        </div>
        @if ($esAdministrador && $vigente)
            <a href="{{ route('talentohumano.lider.plantillas.edit', $vigente) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]">
                <i class="fas fa-pen" aria-hidden="true"></i> Editar
            </a>
        @endif
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        {{-- Versiones (HU-008, esc. 3) --}}
        <aside class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-[.18em] text-slate-400">Versiones</h2>
            @foreach ($versiones as $version)
                @php $actual = $version->id === $seleccionada->id; @endphp
                <div @class(['rounded-2xl bg-white p-4 shadow-sm ring-1 transition', 'ring-2 ring-[#064C3D]' => $actual, 'ring-slate-200/70' => !$actual])>
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-bold text-slate-800">Versión {{ $version->version }}</p>
                        <span @class(['rounded-full px-2 py-0.5 text-[11px] font-semibold', 'bg-emerald-100 text-emerald-800' => $version->estado === 'vigente', 'bg-slate-100 text-slate-500' => $version->estado !== 'vigente'])>
                            {{ $version->estado === 'vigente' ? 'Vigente' : 'Inactiva' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $version->created_at->format('d/m/Y') }} · {{ $nombre($version->creadaPor) }}</p>
                    <p class="text-xs font-semibold text-[#064C3D]">{{ $actas[$version->id] ?? 0 }} actas generadas</p>
                    <ul class="mt-2 space-y-0.5 text-xs text-slate-500">
                        @foreach ($version->cambiosRespectoA($versiones->firstWhere('version', '<', $version->version)) as $cambio)
                            <li><i class="fas fa-circle text-[5px] align-middle text-slate-300" aria-hidden="true"></i> {{ $cambio }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($actual)
                            <span class="text-xs font-semibold text-slate-400">Viendo esta versión</span>
                        @else
                            <a href="{{ route('talentohumano.lider.plantillas.show', [$plantilla, 'version' => $version->id]) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Ver esta versión</a>
                        @endif
                        @if ($esAdministrador && $version->estado !== 'vigente')
                            <form method="POST" action="{{ route('talentohumano.lider.plantillas.activar', $version) }}" data-titulo="¿Activar la versión {{ $version->version }}?"
                                  data-confirmar="Quedará como la plantilla vigente del área y la versión {{ $vigente?->version }} se desactivará sin borrarse. Las actas ya generadas no cambian.">
                                @csrf @method('PATCH')
                                <button class="text-xs font-semibold text-[#064C3D] hover:underline">Activar</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
            <p class="px-1 text-xs text-slate-400">Las versiones anteriores no se pueden modificar ni eliminar.</p>
        </aside>

        <div>
            <h2 class="mb-3 text-sm font-bold uppercase tracking-[.18em] text-slate-400">Vista previa · versión {{ $seleccionada->version }}</h2>
            @include('talentohumano::lider.plantillas._vista-previa', ['plantilla' => $seleccionada])
        </div>
    </div>
</x-talentohumano::layouts.lider>
