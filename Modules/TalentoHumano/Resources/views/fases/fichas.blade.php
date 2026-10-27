@php
    $marcadas = array_map('intval', old('fichas', $seleccionadas));
@endphp
<x-talentohumano::layouts.master title="Fichas de la fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Fichas de la fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-017 · RF-018 · RN-038"
        subtitulo="Marque las fichas que ingresan a la fase. Solo se muestran las fichas activas de nivel tecnólogo y modalidad presencial; sus aprendices serán los que vean la convocatoria." />

    @if ($bloqueada)
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-lock mr-1" aria-hidden="true"></i>
            La convocatoria ya fue publicada; no se pueden modificar las fichas de la fase.
        </div>
    @endif

    <x-talentohumano::errores />

    @if ($fichas->isEmpty())
        <div class="rounded-2xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
            No hay fichas tecnólogo presenciales disponibles en SICEFA.
        </div>
    @else
        <form method="POST" action="{{ route('talentohumano.fases.fichas.update', $fase->id) }}" id="form-fichas"
              data-iniciales="{{ json_encode(array_map('intval', $seleccionadas)) }}">
            @csrf
            @method('PUT')

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <label class="relative block w-full sm:w-80">
                    <span class="sr-only">Buscar ficha</span>
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input type="search" id="buscar" placeholder="Buscar por número de ficha o programa"
                           class="w-full rounded-full border-0 py-2 pl-9 pr-4 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]">
                </label>
                <p class="text-sm text-slate-600"><strong id="contador">{{ count($marcadas) }}</strong> fichas marcadas</p>
            </div>

            <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-12 px-4 py-3"><span class="sr-only">Seleccionar</span></th>
                            <th class="px-4 py-3">Ficha</th>
                            <th class="px-4 py-3">Programa de formación</th>
                            <th class="px-4 py-3">Trimestre</th>
                            <th class="px-4 py-3">Inicio</th>
                            <th class="px-4 py-3">Aprendices</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($fichas as $ficha)
                            <tr class="hover:bg-slate-50/60" data-fila data-busqueda="{{ mb_strtolower($ficha->code . ' ' . $ficha->programa) }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="fichas[]" value="{{ $ficha->id }}" id="ficha-{{ $ficha->id }}"
                                           class="h-4 w-4 rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D]"
                                           @checked(in_array($ficha->id, $marcadas)) @disabled($bloqueada)>
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-900"><label for="ficha-{{ $ficha->id }}">{{ $ficha->code }}</label></td>
                                <td class="px-4 py-3">
                                    {{ $ficha->programa }}
                                    <span class="block text-xs text-slate-400">Código SOFIA {{ $ficha->sofia_code }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $ficha->trimestre ? $ficha->trimestre . '.º' : 'Sin iniciar' }}</td>
                                <td class="px-4 py-3">{{ $ficha->start_date ? \Illuminate\Support\Carbon::parse($ficha->start_date)->format('d/m/Y') : '—' }}</td>
                                <td class="px-4 py-3">{{ $ficha->aprendices }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('talentohumano.fases.fichas.aprendices', [$fase->id, $ficha->id]) }}" class="whitespace-nowrap font-semibold text-[#064C3D] hover:underline">
                                        Ver aprendices
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                El trimestre se calcula con la fecha de inicio de la ficha (tres meses por trimestre). Se cuentan los aprendices en formación, inducción o condicionados.
            </p>

            @unless ($bloqueada)
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        <i class="fas fa-check" aria-hidden="true"></i> Confirmar selección
                    </button>
                </div>
            @endunless
        </form>
    @endif

    @push('scripts')
        <script>
            (() => {
                const formulario = document.getElementById('form-fichas');
                if (!formulario) return;

                const casillas = [...formulario.querySelectorAll('input[name="fichas[]"]')];
                const contador = document.getElementById('contador');
                const iniciales = JSON.parse(formulario.dataset.iniciales);

                // La búsqueda solo oculta filas: las fichas marcadas se siguen enviando
                document.getElementById('buscar').addEventListener('input', (evento) => {
                    const texto = evento.target.value.trim().toLowerCase();
                    formulario.querySelectorAll('[data-fila]').forEach((fila) => {
                        fila.hidden = texto !== '' && !fila.dataset.busqueda.includes(texto);
                    });
                });

                formulario.addEventListener('change', () => {
                    contador.textContent = casillas.filter((c) => c.checked).length;
                });

                // HU-017 escenario 6: retirar fichas requiere confirmación
                formulario.addEventListener('submit', (evento) => {
                    const retiradas = casillas.filter((c) => !c.checked && iniciales.includes(Number(c.value)));
                    if (retiradas.length && !confirm(`Se retirarán ${retiradas.length} ficha(s) y sus aprendices de la fase. ¿Confirmar el retiro?`)) {
                        evento.preventDefault();
                    }
                });
            })();
        </script>
    @endpush
</x-talentohumano::layouts.master>
