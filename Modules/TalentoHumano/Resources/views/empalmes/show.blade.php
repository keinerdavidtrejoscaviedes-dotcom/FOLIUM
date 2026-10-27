<x-talentohumano::layouts.master title="Empalme · {{ $empalme->cargo->nombre ?? 'Cargo' }}">
    {{-- Breadcrumbs y Cabecera del Empalme --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <a href="{{ route('talentohumano.empalmes.index') }}" class="hover:text-[#064C3D] hover:underline">
                    Empalmes
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">{{ $empalme->cargo->nombre ?? 'Detalle' }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-handshake"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        {{ $empalme->cargo->nombre ?? 'Empalme de Cargo' }}
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Fase {{ $empalme->fase->numero ?? '36' }} · Transferencia de responsabilidades, bienes y firma digital (RF-013).
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @php
                $chipClass = match ($empalme->estado) {
                    'firmado' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                    'en_curso' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                    default => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                };
            @endphp
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $chipClass }}">
                Estado: {{ ucfirst(str_replace('_', ' ', $empalme->estado)) }}
            </span>
            <a href="{{ route('talentohumano.empalmes.index') }}"
               class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i> Volver a empalmes
            </a>
        </div>
    </div>

    {{-- Notificaciones de éxito / alerta --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-[#E8F3EF] p-4 text-[#064C3D] flex items-center gap-3 shadow-sm">
            <i class="fas fa-circle-check text-xl text-emerald-600"></i>
            <div>
                <strong class="font-bold">¡Operación registrada!</strong>
                <p class="text-xs sm:text-sm mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Layout modular de 2 columnas organizado en bloques mediante partials --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Columna izquierda: Participantes e Inventario --}}
        <div class="space-y-6">
            {{-- Bloque 1: Participantes del Proceso --}}
            @include('talentohumano::empalmes.partials.participantes')

            {{-- Bloque 2: Inventario del Cargo --}}
            @include('talentohumano::empalmes.partials.inventario')
        </div>

        {{-- Columna derecha: Acta de Recibimiento y Firmas Digitales --}}
        <div class="space-y-6">
            {{-- Bloque 3: Acta y Firmas Digitales --}}
            @include('talentohumano::empalmes.partials.acta_firmas')
        </div>
    </div>
</x-talentohumano::layouts.master>
