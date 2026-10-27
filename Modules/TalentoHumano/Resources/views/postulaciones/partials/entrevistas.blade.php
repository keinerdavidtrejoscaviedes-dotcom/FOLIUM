{{-- Bloque: Evaluaciones de Entrevistas (Mockup INS-05) --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-comments text-emerald-600"></i>
            <span>Entrevistas</span>
        </h2>
        <p class="text-sm text-slate-600">
            Puntaje de entrevista: <strong class="text-[#064C3D]">{{ $entrevistas['puntaje'] }}</strong>
            <span class="text-xs text-slate-400">(promedio de {{ count($entrevistas['evaluaciones']) }} evaluadores)</span>
        </p>
    </div>

    <ul class="mt-4 space-y-3 text-sm">
        @foreach ($entrevistas['evaluaciones'] as $eval)
            <li class="rounded-2xl ring-1 ring-slate-200 p-4 bg-slate-50/40">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-semibold text-slate-800">
                        {{ $eval['evaluador'] }}
                        <span class="text-xs font-normal text-slate-500">· {{ $eval['rol'] }}</span>
                    </p>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        {{ $eval['puntaje'] }} · {{ $eval['concepto'] }}
                    </span>
                </div>
                <p class="mt-1.5 text-xs text-slate-600 italic">
                    «{{ $eval['comentarios'] }}»
                </p>
            </li>
        @endforeach
    </ul>
</section>
