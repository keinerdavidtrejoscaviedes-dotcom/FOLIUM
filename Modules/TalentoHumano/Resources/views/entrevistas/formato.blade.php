<x-talentohumano::layouts.master title="Mi formato de entrevista">
    {{-- Breadcrumbs y Título (Mockup INS-08) --}}
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
            <span class="text-[#064C3D]">Mi formato</span>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                    Mi formato de entrevista
                </h1>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                    Tus propias preguntas para la entrevista <strong>técnica</strong>, cada una con su puntaje. Solo se usan en las entrevistas que tú califiques (HU-040 · RF-009 · RN-016).
                </p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800">
                Versión {{ $cuestionario->version }} · {{ ucfirst($cuestionario->estado) }}
            </span>
        </div>
    </div>

    {{-- Aviso de formato usado con calificaciones si aplica --}}
    @if ($totalCalificaciones > 0)
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-lock text-amber-500 text-lg"></i>
            <span class="flex-1">
                Este formato ya tiene <strong>{{ $totalCalificaciones }} calificaciones</strong> registradas. Al modificarlo y guardar, el sistema creará automáticamente la <strong>Versión {{ $cuestionario->version + 1 }}</strong> para proteger el histórico.
            </span>
        </div>
    @endif

    {{-- Formulario interactivo de preguntas y puntajes (Mockup INS-08) --}}
    <form method="POST" action="{{ route('talentohumano.entrevistas.formato.guardar') }}" class="mt-6 space-y-4" id="formFormato">
        @csrf
        <input type="hidden" name="cuestionario_id" value="{{ $cuestionario->id }}">

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <h2 class="font-bold text-base text-[#064C3D] flex items-center gap-2">
                    <i class="fas fa-list-ol text-emerald-600"></i>
                    <span>Preguntas de evaluación</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600">
                    Puntaje acumulado: <strong id="total-puntaje" class="text-lg text-[#064C3D] font-extrabold">{{ (int)$puntajeTotal }}</strong>
                    <span class="text-xs text-slate-400">· escala 0 a 100</span>
                </p>
            </div>

            <ol id="lista-preguntas" class="mt-4 space-y-2.5">
                @foreach ($cuestionario->preguntas as $idx => $preg)
                    @php
                        $esBase = $idx < 4;
                    @endphp
                    <li class="item-pregunta flex flex-col gap-2 rounded-2xl p-3 ring-1 ring-slate-200 sm:flex-row sm:items-center hover:bg-slate-50/50 transition">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#064C3D] text-xs font-bold text-white">
                            {{ $idx + 1 }}
                        </span>
                        <input type="text" name="preguntas[{{ $idx }}][enunciado]" value="{{ $preg->enunciado }}" required
                               class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">

                        @if ($esBase)
                            <span class="w-fit rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 uppercase">
                                Ítem base
                            </span>
                        @endif

                        <label class="flex items-center gap-1.5 text-xs text-slate-500 shrink-0">
                            Puntaje:
                            <input type="number" name="preguntas[{{ $idx }}][puntaje]" min="1" max="50"
                                   value="{{ (int)$preg->puntaje_maximo }}" required
                                   oninput="recalcularTotal()"
                                   class="input-puntaje w-16 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-[#064C3D] focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                        </label>

                        <button type="button" onclick="eliminarPregunta(this)"
                                class="self-end rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 sm:self-auto" title="Quitar pregunta">
                            <i class="fas fa-trash-can text-xs"></i>
                        </button>
                    </li>
                @endforeach
            </ol>

            <button type="button" id="btn-agregar" onclick="agregarPregunta()"
                    class="mt-4 rounded-xl bg-[#E8F3EF] px-4 py-2 text-xs font-bold text-[#064C3D] hover:bg-emerald-100 transition flex items-center gap-1.5">
                <i class="fas fa-plus"></i>
                <span>Agregar nueva pregunta</span>
            </button>
        </section>

        <div class="flex justify-end gap-2 pb-4">
            <button type="submit"
                    class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] flex items-center gap-1.5 transition">
                <i class="fas fa-floppy-disk text-[#34D399]"></i>
                <span>Guardar formato</span>
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            let indexPregunta = {{ $cuestionario->preguntas->count() }};

            function recalcularTotal() {
                const inputs = document.querySelectorAll('.input-puntaje');
                let total = 0;
                inputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('total-puntaje').textContent = Math.round(total);
            }

            function agregarPregunta() {
                indexPregunta++;
                const lista = document.getElementById('lista-preguntas');
                const nuevoItem = document.createElement('li');
                nuevoItem.className = 'item-pregunta flex flex-col gap-2 rounded-2xl p-3 ring-1 ring-slate-200 sm:flex-row sm:items-center hover:bg-slate-50/50 transition';
                nuevoItem.innerHTML = `
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#064C3D] text-xs font-bold text-white">+</span>
                    <input type="text" name="preguntas[${indexPregunta}][enunciado]" placeholder="Escribe el enunciado de la pregunta..." required
                           class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                    <label class="flex items-center gap-1.5 text-xs text-slate-500 shrink-0">
                        Puntaje:
                        <input type="number" name="preguntas[${indexPregunta}][puntaje]" min="1" max="50" value="10" required
                               oninput="recalcularTotal()"
                               class="input-puntaje w-16 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-[#064C3D] focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                    </label>
                    <button type="button" onclick="eliminarPregunta(this)"
                            class="self-end rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 sm:self-auto" title="Quitar pregunta">
                        <i class="fas fa-trash-can text-xs"></i>
                    </button>
                `;
                lista.appendChild(nuevoItem);
                recalcularTotal();
            }

            function eliminarPregunta(btn) {
                const items = document.querySelectorAll('.item-pregunta');
                if (items.length <= 1) {
                    alert('Debes mantener al menos una pregunta en el formato.');
                    return;
                }
                btn.closest('.item-pregunta').remove();
                recalcularTotal();
            }
        </script>
    @endpush
</x-talentohumano::layouts.master>
