@php
    $preguntas = old('preguntas', $preguntas);
    if (!$preguntas) {
        $preguntas = [['enunciado' => '', 'puntaje_maximo' => '']];
    }
    $siguiente = max(array_map('intval', array_keys($preguntas))) + 1;
    $nombreTipo = $tipo === 'entrevista_psicologica' ? 'psicológica' : 'técnica';
    $claseCampo = 'rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<x-talentohumano::layouts.master title="Mi formato de entrevista">
    <x-talentohumano::encabezado titulo="Mi formato de entrevista {{ $nombreTipo }}" :volver="route('talentohumano.panel')" hu="HU-040 · RF-009"
        subtitulo="Sus propias preguntas, cada una con su puntaje. Se usarán solo en las entrevistas que usted califique; al calificar, el puntaje se lleva a una escala de 0 a 100.">
        <a href="{{ route('talentohumano.formato.edit', ['base' => 1]) }}"
           class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-slate-300 hover:bg-slate-50"
           @if ($formato) onclick="return confirm('Los ítems base reemplazarán las preguntas del editor (no se guarda nada hasta que presione Guardar). ¿Continuar?')" @endif>
            <i class="fas fa-list-check" aria-hidden="true"></i> Cargar ítems base
        </a>
    </x-talentohumano::encabezado>

    @if ($formato)
        <p class="mb-4 text-sm text-slate-600">
            Versión vigente: <strong>v{{ $formato->version }}</strong>
            · Actualizado el {{ \Illuminate\Support\Carbon::parse($formato->updated_at)->format('d/m/Y H:i') }}
        </p>
    @endif

    @if ($enUso)
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-code-branch mr-1" aria-hidden="true"></i>
            Su formato ya tiene calificaciones registradas. Al guardar los cambios se creará la <strong>versión {{ $formato->version + 1 }}</strong>
            como vigente, y las calificaciones anteriores se conservarán con la versión {{ $formato->version }}.
        </div>
    @endif

    @if ($cargoBase)
        <div class="mb-6 rounded-2xl bg-[#E8F3EF] p-4 text-sm text-[#064C3D]">
            <i class="fas fa-circle-info mr-1" aria-hidden="true"></i>
            Se cargaron los ítems base de la entrevista {{ $nombreTipo }}. Puede editarlos o eliminarlos; presione <strong>Guardar</strong> para conservarlos.
        </div>
    @endif

    <x-talentohumano::errores />

    <form method="POST" action="{{ route('talentohumano.formato.update') }}">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="grid grid-cols-[1.5rem_1fr_6.5rem_2.25rem] gap-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>#</span><span>Pregunta</span><span>Puntaje</span><span></span>
            </div>
            <div id="preguntas" class="divide-y divide-slate-100" data-siguiente="{{ $siguiente }}">
                @foreach ($preguntas as $i => $pregunta)
                    <div class="grid grid-cols-[1.5rem_1fr_6.5rem_2.25rem] items-start gap-3 px-4 py-3" data-pregunta>
                        <span class="pt-2 font-semibold text-slate-400" data-numero>{{ $loop->iteration }}</span>
                        <input type="text" name="preguntas[{{ $i }}][enunciado]" value="{{ $pregunta['enunciado'] ?? '' }}" required maxlength="2000"
                               placeholder="Pregunta o aspecto a evaluar" aria-label="Pregunta"
                               @class([$claseCampo, 'w-full min-w-0', 'ring-red-400' => $errors->has("preguntas.$i.enunciado"), 'ring-slate-300' => !$errors->has("preguntas.$i.enunciado")])>
                        <input type="number" name="preguntas[{{ $i }}][puntaje_maximo]" value="{{ $pregunta['puntaje_maximo'] ?? '' }}" min="0.01" max="999.99" step="0.01"
                               placeholder="Puntaje" aria-label="Puntaje" data-puntaje
                               @class([$claseCampo, 'w-full', 'ring-red-400' => $errors->has("preguntas.$i.puntaje_maximo"), 'ring-slate-300' => !$errors->has("preguntas.$i.puntaje_maximo")])>
                        <button type="button" data-accion="quitar" class="grid h-9 w-9 place-items-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600" title="Quitar pregunta">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3">
                <button type="button" data-accion="agregar" class="text-sm font-semibold text-[#064C3D] hover:underline">
                    <i class="fas fa-plus" aria-hidden="true"></i> Agregar pregunta
                </button>
                <p class="text-sm text-slate-600">Puntaje total: <strong id="total">0</strong></p>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-floppy-disk" aria-hidden="true"></i> Guardar
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            (() => {
                const contenedor = document.getElementById('preguntas');
                const total = document.getElementById('total');
                let siguiente = Number(contenedor.dataset.siguiente);

                const actualizar = () => {
                    const filas = contenedor.querySelectorAll('[data-pregunta]');
                    filas.forEach((fila, n) => fila.querySelector('[data-numero]').textContent = n + 1);
                    const suma = [...contenedor.querySelectorAll('[data-puntaje]')].reduce((s, campo) => s + (Number(campo.value) || 0), 0);
                    total.textContent = suma.toLocaleString('es-CO', { maximumFractionDigits: 2 });
                };

                document.addEventListener('click', (evento) => {
                    const boton = evento.target.closest('[data-accion]');
                    if (!boton) return;

                    if (boton.dataset.accion === 'agregar') {
                        // Se copia la primera fila con los campos vacíos y una clave nueva
                        const fila = contenedor.querySelector('[data-pregunta]').cloneNode(true);
                        fila.querySelectorAll('input').forEach((campo) => {
                            campo.value = '';
                            campo.name = campo.name.replace(/preguntas\[[^\]]+\]/, `preguntas[${siguiente}]`);
                            campo.classList.remove('ring-red-400');
                            campo.classList.add('ring-slate-300');
                        });
                        siguiente++;
                        contenedor.appendChild(fila);
                        fila.querySelector('input').focus();
                    }

                    if (boton.dataset.accion === 'quitar') {
                        if (contenedor.querySelectorAll('[data-pregunta]').length === 1) {
                            alert('El formato debe tener al menos una pregunta.');
                            return;
                        }
                        boton.closest('[data-pregunta]').remove();
                    }

                    actualizar();
                });

                contenedor.addEventListener('input', actualizar);
                actualizar();
            })();
        </script>
    @endpush
</x-talentohumano::layouts.master>
