{{-- Bloque: Prueba psicotécnica (Mockup INS-05) --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <div class="flex items-center justify-between">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-brain text-emerald-600"></i>
            <span>Prueba psicotécnica</span>
        </h2>
        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
            Presentada
        </span>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-6">
        <div>
            <p class="text-4xl font-extrabold text-[#064C3D]">{{ $pruebaPsicotecnica['puntaje'] }}</p>
            <p class="text-xs text-slate-500">de 100 · presentada el {{ $pruebaPsicotecnica['fecha'] }}</p>
        </div>
        <div class="min-w-0 flex-1 space-y-2 text-sm">
            @foreach ($pruebaPsicotecnica['competencias'] as $comp)
                <div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-600">{{ $comp['nombre'] }}</span>
                        <span class="font-bold text-slate-800">{{ $comp['puntaje'] }}</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-2 rounded-full bg-[#064C3D]" style="width: {{ $comp['puntaje'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
