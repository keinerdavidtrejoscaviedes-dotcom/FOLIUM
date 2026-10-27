@props(['titulo', 'subtitulo' => null, 'volver' => null, 'hu' => null])
{{-- Encabezado de las páginas del módulo: enlace de regreso, título, subtítulo y acciones (slot). --}}
<div class="mb-6">
    @if ($volver)
        <a href="{{ $volver }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#064C3D] hover:underline">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
        </a>
    @endif
    <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D]">{{ $titulo }}</h1>
            @if ($subtitulo)
                <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ $subtitulo }}</p>
            @endif
            @if ($hu)
                <p class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-400">{{ $hu }}</p>
            @endif
        </div>
        @if (trim($slot))
            <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
        @endif
    </div>
</div>
