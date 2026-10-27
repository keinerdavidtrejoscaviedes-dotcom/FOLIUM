@php
    // Al volver de un error de validación se muestran las preguntas que se enviaron
    $preguntas = old('preguntas', $preguntas);
    $preguntaVacia = ['tipo' => 'seleccion', 'opciones' => [[], []]];
    if (!$preguntas) {
        $preguntas = [$preguntaVacia];
    }
    $siguiente = max(array_map('intval', array_keys($preguntas))) + 1;
    $titulo = $prueba ? 'Editar prueba psicotécnica' : 'Nueva prueba psicotécnica';
@endphp
<x-talentohumano::layouts.master :title="$titulo">
    <x-talentohumano::encabezado :titulo="$titulo" hu="HU-039 · RF-007"
        :volver="$prueba ? route('talentohumano.pruebas.show', $prueba->id) : route('talentohumano.pruebas.index')"
        subtitulo="Agregue las preguntas con su tipo y su puntaje. Las preguntas pueden ser de selección, de escala o de respuesta abierta, y pueden llevar una imagen." />

    @if ($enUso)
        <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <i class="fas fa-code-branch mr-1" aria-hidden="true"></i>
            Esta prueba ya tiene respuestas de aprendices. Al guardar los cambios se creará la <strong>versión {{ $prueba->version + 1 }}</strong>
            y los resultados anteriores se conservarán con la versión {{ $prueba->version }}.
        </div>
    @endif

    <x-talentohumano::errores />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $prueba ? route('talentohumano.pruebas.update', $prueba->id) : route('talentohumano.pruebas.store') }}">
        @csrf
        @if ($prueba)
            @method('PUT')
        @endif

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Título de la prueba</span>
                <input type="text" name="titulo" value="{{ old('titulo', $prueba->titulo ?? '') }}" required maxlength="255"
                       placeholder="Por ejemplo: Prueba psicotécnica fase 49"
                       @class(['mt-1 w-full rounded-xl border-0 px-3 py-2 ring-1 focus:ring-2 focus:ring-[#064C3D]', 'ring-red-400' => $errors->has('titulo'), 'ring-slate-300' => !$errors->has('titulo')])>
            </label>
        </div>

        <div id="preguntas" class="mt-5 space-y-5" data-siguiente="{{ $siguiente }}">
            @foreach ($preguntas as $i => $pregunta)
                @include('talentohumano::pruebas._pregunta', ['i' => $i, 'pregunta' => $pregunta, 'numero' => $loop->iteration])
            @endforeach
        </div>

        <button type="button" data-accion="agregar-pregunta"
                class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 py-4 text-sm font-semibold text-[#064C3D] hover:border-[#34D399] hover:bg-white">
            <i class="fas fa-plus" aria-hidden="true"></i> Agregar pregunta
        </button>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <a href="{{ $prueba ? route('talentohumano.pruebas.show', $prueba->id) : route('talentohumano.pruebas.index') }}"
               class="inline-flex items-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-floppy-disk" aria-hidden="true"></i> Guardar
            </button>
        </div>
    </form>

    {{-- Plantillas que usa JavaScript para agregar preguntas y opciones --}}
    <template id="plantilla-pregunta">
        @include('talentohumano::pruebas._pregunta', ['i' => '__I__', 'pregunta' => $preguntaVacia, 'numero' => ''])
    </template>
    <template id="plantilla-opcion">
        @include('talentohumano::pruebas._opcion', ['i' => '__I__', 'j' => '__J__', 'opcion' => []])
    </template>

    @push('scripts')
        <script>
            (() => {
                const contenedor = document.getElementById('preguntas');
                const plantillaPregunta = document.getElementById('plantilla-pregunta').innerHTML;
                const plantillaOpcion = document.getElementById('plantilla-opcion').innerHTML;
                let siguiente = Number(contenedor.dataset.siguiente);

                const renumerar = () => contenedor.querySelectorAll('[data-pregunta]')
                    .forEach((pregunta, n) => pregunta.querySelector('[data-numero]').textContent = n + 1);

                // Solo se envían los campos del tipo elegido
                const mostrarSeccion = (pregunta) => {
                    const tipo = pregunta.querySelector('[data-tipo]').value;
                    pregunta.querySelectorAll('[data-seccion]').forEach((seccion) => {
                        const activa = seccion.dataset.seccion === tipo;
                        seccion.hidden = !activa;
                        seccion.querySelectorAll('input').forEach((campo) => campo.disabled = !activa);
                    });
                };

                document.addEventListener('click', (evento) => {
                    const boton = evento.target.closest('[data-accion]');
                    if (!boton) return;

                    switch (boton.dataset.accion) {
                        case 'agregar-pregunta': {
                            contenedor.insertAdjacentHTML('beforeend', plantillaPregunta.replaceAll('__I__', siguiente++));
                            const nueva = contenedor.lastElementChild;
                            mostrarSeccion(nueva);
                            renumerar();
                            nueva.querySelector('textarea').focus();
                            break;
                        }
                        case 'quitar-pregunta':
                            if (contenedor.querySelectorAll('[data-pregunta]').length === 1) {
                                alert('La prueba debe tener al menos una pregunta.');
                                return;
                            }
                            boton.closest('[data-pregunta]').remove();
                            renumerar();
                            break;
                        case 'agregar-opcion': {
                            const lista = boton.closest('[data-seccion]').querySelector('[data-opciones]');
                            const indice = boton.closest('[data-pregunta]').dataset.indice;
                            lista.insertAdjacentHTML('beforeend', plantillaOpcion
                                .replaceAll('__I__', indice)
                                .replaceAll('__J__', lista.dataset.siguiente++));
                            break;
                        }
                        case 'quitar-opcion':
                            boton.closest('[data-opcion]').remove();
                            break;
                    }
                });

                document.addEventListener('change', (evento) => {
                    if (evento.target.matches('[data-tipo]')) {
                        mostrarSeccion(evento.target.closest('[data-pregunta]'));
                    }

                    // HU-039 escenario 3: el archivo no se carga si no es imagen o pasa de 5 MB
                    if (evento.target.matches('[data-imagen]')) {
                        const archivo = evento.target.files[0];
                        if (archivo && (!archivo.type.startsWith('image/') || archivo.size > 5 * 1024 * 1024)) {
                            alert('Solo se permiten imágenes de hasta 5 MB');
                            evento.target.value = '';
                        }
                    }
                });

                contenedor.querySelectorAll('[data-pregunta]').forEach(mostrarSeccion);
            })();
        </script>
    @endpush
</x-talentohumano::layouts.master>
