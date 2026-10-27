{{-- Opción de una pregunta de selección: texto y puntaje. $i = clave de la pregunta, $j = clave de la opción. --}}
<div class="flex items-center gap-2" data-opcion>
    <input type="text" name="preguntas[{{ $i }}][opciones][{{ $j }}][texto]" value="{{ $opcion['texto'] ?? '' }}"
           placeholder="Texto de la opción" aria-label="Texto de la opción"
           class="min-w-0 flex-1 rounded-xl border-0 px-3 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]">
    <input type="number" name="preguntas[{{ $i }}][opciones][{{ $j }}][puntaje]" value="{{ $opcion['puntaje'] ?? '' }}"
           min="0" max="999.99" step="0.01" placeholder="Puntaje" aria-label="Puntaje de la opción"
           class="w-28 rounded-xl border-0 px-3 py-2 text-sm ring-1 ring-slate-300 focus:ring-2 focus:ring-[#064C3D]">
    <button type="button" data-accion="quitar-opcion" class="grid h-9 w-9 place-items-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600" title="Quitar opción">
        <i class="fas fa-xmark" aria-hidden="true"></i>
    </button>
</div>
