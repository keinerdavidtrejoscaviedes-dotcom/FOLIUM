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
                        @if ($entrevista->modalidad === 'grupal')
                            Calificar grupo ({{ $grupo->count() }} aprendices)
                        @else
                            Calificar: {{ $postulacion->nombre_completo }}
                        @endif
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Entrevista {{ $entrevista->tipo === 'psicologica' ? 'psicológica' : 'técnica' }} {{ $entrevista->modalidad }} · Lugar: {{ $entrevista->lugar }} · Ficha {{ $postulacion->course->code ?? 'N/A' }}
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

    {{-- HU-041B: después de registrar la suya, el evaluador ve las de los demás, sin poder modificarlas --}}
    @if ($otras->isNotEmpty())
        <section class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-base text-[#064C3D]">Calificaciones de los demás evaluadores</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($otras as $otra)
                    <li class="flex flex-wrap items-start justify-between gap-2 py-2">
                        <span>
                            <span class="font-semibold text-slate-800">{{ trim(($otra->evaluador->first_name ?? '') . ' ' . ($otra->evaluador->first_last_name ?? '')) }}</span>
                            <span class="block text-xs text-slate-500">{{ $otra->observaciones }}</span>
                        </span>
                        <span class="text-xs font-semibold text-[#064C3D]">{{ (float) $otra->puntaje }} / 100 · {{ $otra->concepto === 'apto' ? 'Apto' : 'No apto' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <x-talentohumano::errores />

    {{-- Formulario de Calificación (HU-041 · RF-009). En la entrevista grupal, un bloque por aprendiz y se guarda todo el grupo (HU-041A esc. 7) --}}
    @php $esGrupal = $entrevista->modalidad === 'grupal'; @endphp
    <form method="POST" action="{{ route('talentohumano.entrevistas.calificar.guardar', ['entrevista' => $entrevista->id, 'postulacion' => $postulacion->id]) }}" class="mt-6 space-y-6">
        @csrf
        <input type="hidden" name="cuestionario_id" value="{{ $cuestionario->id }}">

        @if ($esGrupal)
            <p class="rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200">
                <i class="fas fa-users mr-1" aria-hidden="true"></i> Entrevista grupal: califique a cada aprendiz y escriba sus observaciones. La calificación se guarda cuando todo el grupo está completo.
            </p>
        @endif

        @foreach ($grupo as $aspirante)
            @php
                // Nombres de los campos: puntajes[pregunta] en la individual; puntajes[postulación][pregunta] en la grupal
                $base = $esGrupal ? "puntajes[{$aspirante->id}]" : 'puntajes';
                $clave = $esGrupal ? "puntajes.{$aspirante->id}" : 'puntajes';
                $nombreObs = $esGrupal ? "observaciones[{$aspirante->id}]" : 'observaciones';
                $claveObs = $esGrupal ? "observaciones.{$aspirante->id}" : 'observaciones';
                $previa = $calificacionesGrupo[$aspirante->id] ?? null;
            @endphp
            <div class="space-y-6" data-bloque>
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="font-bold text-base text-[#064C3D]">
                                @if ($esGrupal) {{ $aspirante->nombre_completo }} · @endif
                                Preguntas del formato {{ $entrevista->tipo === 'psicologica' ? 'psicológico' : 'técnico' }}
                            </h2>
                            <p class="text-xs text-slate-500">Asigna la puntuación individual para cada aspecto evaluado (HU-041).</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-400">Puntaje total:</p>
                            <p data-total class="text-2xl font-extrabold text-[#064C3D]">0 <span class="text-xs text-slate-400">/ 100</span></p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        @foreach ($cuestionario->preguntas as $idx => $preg)
                            @php
                                // Sin valor por defecto: cada pregunta se califica (HU-041A esc. 2)
                                $detallePuntaje = old("{$clave}.{$preg->id}", $previa->detalle[$preg->id] ?? '');
                                $faltaPuntaje = $errors->has("{$clave}.{$preg->id}");
                            @endphp
                            <div @class(['rounded-2xl p-4 ring-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3', 'ring-red-300 bg-red-50' => $faltaPuntaje, 'ring-slate-200 bg-slate-50/40' => !$faltaPuntaje])>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Pregunta {{ $idx + 1 }} · Máx: {{ (int)$preg->puntaje_maximo }} pts</span>
                                    <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $preg->enunciado }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <label class="text-xs font-semibold text-slate-500">Puntaje obtenido:</label>
                                    <input type="number" name="{{ $base }}[{{ $preg->id }}]" min="0" max="{{ (float) $preg->puntaje_maximo }}" step="0.01" data-maximo="{{ (float) $preg->puntaje_maximo }}"
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
                    <h2 class="font-bold text-base text-[#064C3D] mb-4">Concepto y observaciones finales @if ($esGrupal)· {{ $aspirante->nombre_completo }}@endif</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- HU-041A esc. 1 y RN-017: el concepto lo calcula el sistema con el umbral de la convocatoria --}}
                        <div>
                            <p class="block text-xs font-semibold text-slate-700 mb-2">Concepto</p>
                            <p data-concepto class="rounded-2xl bg-slate-50 p-3 text-sm font-bold text-slate-800 ring-1 ring-slate-200">—</p>
                            <p class="mt-1 text-[11px] text-slate-500">Apto si el puntaje llega al umbral de la convocatoria ({{ $umbral }} de 100).</p>
                        </div>

                        <div>
                            <label for="observaciones-{{ $aspirante->id }}" class="block text-xs font-semibold text-slate-700 mb-1">
                                Observaciones / Justificación de la evaluación <span class="text-red-500">*</span>:
                            </label>
                            <textarea id="observaciones-{{ $aspirante->id }}" name="{{ $nombreObs }}" rows="3" required placeholder="Escribe tus observaciones sobre la entrevista..."
                                      @class(['w-full rounded-xl border bg-white p-2.5 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]', 'border-red-400' => $errors->has($claveObs), 'border-slate-200' => !$errors->has($claveObs)])>{{ old($claveObs, $previa->observaciones ?? '') }}</textarea>
                            @error($claveObs) <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </div>
        @endforeach

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex justify-end gap-3">
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
            // Puntaje en escala de 0 a 100 (RF-009) y concepto con el umbral de la convocatoria (RN-017)
            // Un total y un concepto por bloque (en la grupal, uno por aprendiz)
            function sumarTotal() {
                document.querySelectorAll('[data-bloque]').forEach(bloque => {
                    let total = 0, maximo = 0;
                    bloque.querySelectorAll('.input-nota').forEach(input => {
                        total += parseFloat(input.value) || 0;
                        maximo += parseFloat(input.dataset.maximo) || 0;
                    });
                    const escala = maximo > 0 ? Math.round(total * 10000 / maximo) / 100 : 0;
                    bloque.querySelector('[data-total]').innerHTML = `${escala} <span class="text-xs text-slate-400">/ 100</span>`;
                    bloque.querySelector('[data-concepto]').textContent = escala >= {{ $umbral }} ? 'Apto' : 'No apto';
                });
            }
            document.addEventListener('DOMContentLoaded', sumarTotal);
        </script>
    @endpush
</x-talentohumano::layouts.master>
