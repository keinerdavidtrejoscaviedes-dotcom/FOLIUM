{{--
    Una pregunta del editor de la prueba psicotécnica.
    $i: clave de la pregunta en el formulario ('__I__' en la plantilla que usa JavaScript)
    $pregunta: enunciado, tipo, opciones, escala_min, escala_max, puntaje_maximo, imagen_actual, pregunta_id
    $numero: número que se muestra
--}}
@php
    $tipo = $pregunta['tipo'] ?? 'seleccion';
    $opciones = is_array($pregunta['opciones'] ?? null) && ($pregunta['opciones'] ?? []) ? $pregunta['opciones'] : [[], []];
    $errorOpciones = $errors->has("preguntas.$i.opciones");
    $claseCampo = 'rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D]';
@endphp
<fieldset class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200" data-pregunta data-indice="{{ $i }}">
    <div class="flex items-center justify-between gap-3">
        <legend class="font-bold text-[#064C3D]">Pregunta <span data-numero>{{ $numero }}</span></legend>
        <button type="button" data-accion="quitar-pregunta" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-red-50 hover:text-red-600">
            <i class="fas fa-trash-can" aria-hidden="true"></i> Quitar
        </button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-[1fr_14rem]">
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Enunciado</span>
            <textarea name="preguntas[{{ $i }}][enunciado]" rows="2" required
                      @class([$claseCampo, 'mt-1 w-full', 'ring-red-400' => $errors->has("preguntas.$i.enunciado"), 'ring-slate-300' => !$errors->has("preguntas.$i.enunciado")])>{{ $pregunta['enunciado'] ?? '' }}</textarea>
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Tipo de pregunta</span>
            <select name="preguntas[{{ $i }}][tipo]" data-tipo class="{{ $claseCampo }} mt-1 w-full ring-slate-300">
                @foreach (\Modules\TalentoHumano\Services\CuestionarioService::TIPOS_PREGUNTA as $valor => $nombre)
                    <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Selección: opciones con su puntaje --}}
    <div data-seccion="seleccion" @if ($tipo !== 'seleccion') hidden @endif
         @class(['mt-4 rounded-xl p-4', 'bg-red-50 ring-1 ring-red-300' => $errorOpciones, 'bg-slate-50' => !$errorOpciones])>
        <p class="text-sm font-semibold text-slate-700">Opciones de respuesta</p>
        <p class="text-xs text-slate-500">Al menos dos opciones. El puntaje de la pregunta es el de la opción con mayor puntaje.</p>
        <div class="mt-3 space-y-2" data-opciones data-siguiente="{{ count($opciones) }}">
            @foreach ($opciones as $j => $opcion)
                @include('talentohumano::pruebas._opcion', ['i' => $i, 'j' => $j, 'opcion' => $opcion])
            @endforeach
        </div>
        <button type="button" data-accion="agregar-opcion" class="mt-3 text-sm font-semibold text-[#064C3D] hover:underline">
            <i class="fas fa-plus" aria-hidden="true"></i> Agregar opción
        </button>
    </div>

    {{-- Escala: valor mínimo y máximo, y puntaje de la pregunta --}}
    <div data-seccion="escala" @if ($tipo !== 'escala') hidden @endif
         @class(['mt-4 grid gap-4 rounded-xl p-4 sm:grid-cols-3', 'bg-red-50 ring-1 ring-red-300' => $errorOpciones, 'bg-slate-50' => !$errorOpciones])>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Valor mínimo</span>
            <input type="number" name="preguntas[{{ $i }}][escala_min]" value="{{ $pregunta['escala_min'] ?? 1 }}" min="0" max="9"
                   class="{{ $claseCampo }} mt-1 w-full ring-slate-300" @disabled($tipo !== 'escala')>
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Valor máximo</span>
            <input type="number" name="preguntas[{{ $i }}][escala_max]" value="{{ $pregunta['escala_max'] ?? 5 }}" min="1" max="10"
                   class="{{ $claseCampo }} mt-1 w-full ring-slate-300" @disabled($tipo !== 'escala')>
        </label>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Puntaje de la pregunta</span>
            <input type="number" name="preguntas[{{ $i }}][puntaje_maximo]" value="{{ $pregunta['puntaje_maximo'] ?? '' }}" min="0.01" max="999.99" step="0.01"
                   class="{{ $claseCampo }} mt-1 w-full ring-slate-300" @disabled($tipo !== 'escala')>
        </label>
        <p class="text-xs text-slate-500 sm:col-span-3">El aprendiz elige un valor de la escala; su puntaje es proporcional al valor elegido.</p>
    </div>

    {{-- Respuesta abierta: puntaje opcional --}}
    <div data-seccion="abierta" @if ($tipo !== 'abierta') hidden @endif
         @class(['mt-4 grid gap-4 rounded-xl p-4 sm:grid-cols-3', 'bg-red-50 ring-1 ring-red-300' => $errorOpciones, 'bg-slate-50' => !$errorOpciones])>
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Puntaje de la pregunta</span>
            <input type="number" name="preguntas[{{ $i }}][puntaje_maximo]" value="{{ $pregunta['puntaje_maximo'] ?? '' }}" min="0" max="999.99" step="0.01" placeholder="0"
                   class="{{ $claseCampo }} mt-1 w-full ring-slate-300" @disabled($tipo !== 'abierta')>
        </label>
        <p class="text-xs text-slate-500 sm:col-span-2 sm:self-end">Las respuestas abiertas no se califican automáticamente. Deje 0 si la pregunta es solo informativa.</p>
    </div>

    {{-- Imagen opcional (RNF-007: imágenes de hasta 5 MB, guardadas sin acceso público) --}}
    <div class="mt-4 flex flex-wrap items-center gap-4">
        @if (!empty($pregunta['imagen_actual']) && !empty($pregunta['pregunta_id']))
            <img src="{{ route('talentohumano.pruebas.imagen', $pregunta['pregunta_id']) }}" alt="Imagen actual de la pregunta" class="h-20 rounded-lg object-cover ring-1 ring-slate-200">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="preguntas[{{ $i }}][quitar_imagen]" value="1" class="rounded border-slate-300"> Quitar imagen
            </label>
        @endif
        <input type="hidden" name="preguntas[{{ $i }}][imagen_actual]" value="{{ $pregunta['imagen_actual'] ?? '' }}">
        <input type="hidden" name="preguntas[{{ $i }}][pregunta_id]" value="{{ $pregunta['pregunta_id'] ?? '' }}">
        <label class="block text-sm">
            <span class="font-semibold text-slate-700">{{ empty($pregunta['imagen_actual']) ? 'Imagen (opcional)' : 'Reemplazar imagen' }}</span>
            <input type="file" name="preguntas[{{ $i }}][imagen]" accept="image/*" data-imagen
                   @class(['mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#E8F3EF] file:px-4 file:py-1.5 file:font-semibold file:text-[#064C3D]', 'text-red-600' => $errors->has("preguntas.$i.imagen")])>
        </label>
    </div>
</fieldset>
