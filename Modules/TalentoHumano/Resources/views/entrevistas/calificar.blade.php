<x-talentohumano::layouts.master title="Calificar entrevista">
    <div>
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
            <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                <i class="fas fa-home"></i> Panel
            </a>
            <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
            <a href="{{ route('talentohumano.entrevistas.index') }}" class="hover:text-[#064C3D] hover:underline">
                Mis entrevistas
            </a>
            <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
            <span class="text-[#064C3D]">Calificar</span>
        </nav>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-[#064C3D] text-lg font-bold text-[#34D399]">
                    {{ $postulacion->iniciales }}
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Calificar: {{ $postulacion->nombre_completo }}
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Entrevista técnica individual · Lugar: {{ $entrevista->lugar }} · Ficha {{ $postulacion->course->code ?? 'N/A' }}
                    </p>
                </div>
            </div>

            @if ($calificacion)
                <div class="rounded-2xl bg-emerald-50 px-4 py-2 ring-1 ring-emerald-200 text-right">
                    <p class="text-xs text-emerald-700 font-semibold uppercase">Calificación actual</p>
                    <p class="text-2xl font-extrabold text-[#064C3D]">{{ (int)$calificacion->puntaje }} <span class="text-xs text-slate-400">/ 100</span></p>
                </div>
            @endif
        </div>
    </div>

    {{-- Formulario de Calificación (HU-041 · RF-009) --}}
    <form method="POST" action="{{ route('talentohumano.entrevistas.calificar.guardar', ['entrevista' => $entrevista->id, 'postulacion' => $postulacion->id]) }}" class="mt-6 space-y-6">
        @csrf
        <input type="hidden" name="cuestionario_id" value="{{ $cuestionario->id }}">

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="font-bold text-base text-[#064C3D]">Preguntas del formato técnico</h2>
                    <p class="text-xs text-slate-500">Asigna la puntuación individual para cada aspecto evaluado (HU-041).</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-400">Puntaje total:</p>
                    <p id="total-calif" class="text-2xl font-extrabold text-[#064C3D]">0 <span class="text-xs text-slate-400">/ 100</span></p>
                </div>
            </div>

            <div class="mt-4 space-y-4">
                @foreach ($cuestionario->preguntas as $idx => $preg)
                    @php
                        $detallePuntaje = $calificacion && isset($calificacion->detalle[$preg->id]) ? $calificacion->detalle[$preg->id] : round($preg->puntaje_maximo * 0.8);
                    @endphp
                    <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-slate-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="text-[10px] font-bold uppercase text-slate-400">Pregunta {{ $idx + 1 }} · Máx: {{ (int)$preg->puntaje_maximo }} pts</span>
                            <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $preg->enunciado }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label class="text-xs font-semibold text-slate-500">Puntaje obtenido:</label>
                            <input type="number" name="puntajes[{{ $preg->id }}]" min="0" max="{{ (int)$preg->puntaje_maximo }}"
                                   value="{{ $detallePuntaje }}" required
                                   oninput="sumarTotal()"
                                   class="input-nota w-20 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-center text-sm font-extrabold text-[#064C3D] focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Concepto y Observaciones --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-base text-[#064C3D] mb-4">Concepto y observaciones finales</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">
                        Concepto del evaluador <span class="text-red-500">*</span>:
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 rounded-2xl p-3 ring-1 ring-slate-200 bg-white flex-1 cursor-pointer has-[:checked]:bg-[#E8F3EF] has-[:checked]:ring-[#064C3D]">
                            <input type="radio" name="concepto" value="apto" {{ old('concepto', $calificacion->concepto ?? 'apto') === 'apto' ? 'checked' : '' }} class="accent-[#064C3D]">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Apto</p>
                                <p class="text-[11px] text-slate-500">Cumple competencias técnicas</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 rounded-2xl p-3 ring-1 ring-slate-200 bg-white flex-1 cursor-pointer has-[:checked]:bg-red-50 has-[:checked]:ring-red-500">
                            <input type="radio" name="concepto" value="no_apto" {{ old('concepto', $calificacion->concepto ?? '') === 'no_apto' ? 'checked' : '' }} class="accent-red-600">
                            <div>
                                <p class="text-xs font-bold text-slate-800">No apto</p>
                                <p class="text-[11px] text-slate-500">No alcanza el perfil requerido</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="observaciones" class="block text-xs font-semibold text-slate-700 mb-1">
                        Observaciones / Justificación de la evaluación:
                    </label>
                    <textarea id="observaciones" name="observaciones" rows="3" placeholder="Escribe tus observaciones técnicas sobre la entrevista..."
                              class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">{{ old('observaciones', $calificacion->observaciones ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('talentohumano.entrevistas.index') }}"
                   class="rounded-full px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] flex items-center gap-2 transition">
                    <i class="fas fa-check text-[#34D399]"></i>
                    <span>Registrar calificación</span>
                </button>
            </div>
        </section>
    </form>

    @push('scripts')
        <script>
            function sumarTotal() {
                const inputs = document.querySelectorAll('.input-nota');
                let total = 0;
                inputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('total-calif').innerHTML = `${Math.round(total)} <span class="text-xs text-slate-400">/ 100</span>`;
            }
            document.addEventListener('DOMContentLoaded', sumarTotal);
        </script>
    @endpush
</x-talentohumano::layouts.master>
