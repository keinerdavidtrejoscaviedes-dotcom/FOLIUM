{{-- Encabezado y pestañas de Supervisión. Conserva la fase consultada desde el historial. --}}
@php
    $consulta = request()->filled('fase') ? ['fase' => request('fase')] : [];
    $pestanas = [
        'index' => ['Selección', 'fa-user-check'],
        'empalmes' => ['Empalmes', 'fa-handshake'],
        'actas' => ['Actas e historial', 'fa-folder-open'],
    ];
@endphp
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Supervisión</h1>
        <p class="mt-1 text-sm text-slate-600">Consulta del proceso en modo de solo lectura, sin intervenir en su contenido.</p>
    </div>
    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
        <i class="fas fa-eye text-emerald-600" aria-hidden="true"></i> Solo lectura{{ $fase ? ' · ' . $fase->nombre_completo : '' }}
    </span>
</div>

<nav class="mt-6 flex gap-2 overflow-x-auto" aria-label="Supervisión">
    @foreach ($pestanas as $ruta => [$titulo, $icono])
        <a href="{{ route('talentohumano.lider.supervision.' . $ruta, $consulta) }}" @if ($actual === $ruta) aria-current="page" @endif
           class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold {{ $actual === $ruta ? 'bg-[#064C3D] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-[#064C3D]' }}">
            <i class="fas {{ $icono }} mr-1.5" aria-hidden="true"></i>{{ $titulo }}
        </a>
    @endforeach
</nav>

@if (request()->filled('fase') && $fase)
    <p class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl bg-sky-50 px-4 py-2.5 text-xs text-sky-900">
        <i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Estás consultando la {{ $fase->nombre_completo }} desde el historial.
        <a href="{{ route('talentohumano.lider.supervision.' . $actual) }}" class="font-semibold underline">Volver a la fase en curso</a>
    </p>
@endif
