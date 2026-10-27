<x-talentohumano::layouts.lider :title="$seccion['titulo']" :activo="$clave">
    <div class="flex items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-xl text-[#34D399] shadow-lg shadow-[#064C3D]/20">
            <i class="fas {{ $seccion['icono'] }}" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">{{ $seccion['titulo'] }}</h1>
            <p class="mt-0.5 text-sm text-slate-600">{{ $seccion['texto'] }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
        <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
            <i class="fas fa-person-digging" aria-hidden="true"></i> En construcción
        </span>
        <h2 class="mt-4 font-bold text-slate-800">Lo que podrás hacer aquí</h2>
        <ul class="mt-3 space-y-2">
            @foreach ($seccion['items'] as $item)
                <li class="flex items-start gap-3 text-sm text-slate-600">
                    <i class="fas fa-circle-check mt-0.5 text-emerald-500" aria-hidden="true"></i> {{ $item }}
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-xs text-slate-400">Historias de usuario: {{ $seccion['hu'] }}</p>
    </div>
</x-talentohumano::layouts.lider>
