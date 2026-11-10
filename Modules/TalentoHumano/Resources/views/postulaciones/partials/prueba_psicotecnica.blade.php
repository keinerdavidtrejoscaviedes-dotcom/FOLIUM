{{-- Bloque: Prueba psicotécnica (Mockup INS-05) --}}
@php $chipPrueba = ['presentada' => ['Presentada', 'bg-emerald-50 text-emerald-700'], 'no_evaluado' => ['No presentada en el plazo', 'bg-red-50 text-red-700'], 'en_curso' => ['En curso', 'bg-amber-50 text-amber-700'], 'habilitada' => ['Habilitada', 'bg-sky-50 text-sky-700']]; @endphp
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <div class="flex items-center justify-between">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-brain text-emerald-600"></i>
            <span>Prueba psicotécnica</span>
        </h2>
        @php [$textoPrueba, $colorPrueba] = $chipPrueba[$pruebaPsicotecnica['estado'] ?? ''] ?? ['Sin presentar', 'bg-slate-100 text-slate-600']; @endphp
        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colorPrueba }}">{{ $textoPrueba }}</span>
    </div>

    @if ($pruebaPsicotecnica && $pruebaPsicotecnica['puntaje'] !== null)
        <div class="mt-4 flex flex-wrap items-start gap-6">
            <div>
                <p class="text-4xl font-extrabold text-[#064C3D]">{{ rtrim(rtrim(number_format($pruebaPsicotecnica['puntaje'], 2, ',', '.'), '0'), ',') }}</p>
                <p class="text-xs text-slate-500">de {{ rtrim(rtrim(number_format($pruebaPsicotecnica['maximo'], 2, ',', '.'), '0'), ',') }}{{ $pruebaPsicotecnica['fecha'] ? ' · presentada el ' . $pruebaPsicotecnica['fecha'] : '' }}</p>
            </div>
            <div class="min-w-0 flex-1 space-y-2 text-sm">
                @foreach ($pruebaPsicotecnica['preguntas'] as $pregunta)
                    @php $max = (float) $pregunta->puntaje_maximo; $obtenido = $pregunta->puntaje !== null ? (float) $pregunta->puntaje : null; @endphp
                    <div>
                        <div class="flex justify-between gap-3 text-xs">
                            <span class="text-slate-600">{{ $pregunta->enunciado }}</span>
                            <span class="shrink-0 font-bold text-slate-800">{{ $pregunta->tipo === 'abierta' ? 'Abierta' : ($obtenido ?? '—') . ' / ' . $max }}</span>
                        </div>
                        @if ($pregunta->tipo === 'abierta')
                            <p class="mt-1 rounded-xl bg-slate-50 px-3 py-2 text-xs italic text-slate-600">«{{ $pregunta->respuesta }}»</p>
                        @else
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-[#064C3D]" style="width: {{ $max > 0 && $obtenido !== null ? round($obtenido * 100 / $max) : 0 }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="mt-3 text-sm text-slate-500">El aspirante aún no ha presentado la prueba.</p>
    @endif
</section>
