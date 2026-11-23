{{-- Reemplazo de un archivo de la postulación (HU-046 esc. 6). Recibe: $accion (ruta POST), $id (único en la página) y $texto. --}}
<details class="mt-1">
    <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-[#064C3D]"><i class="fas fa-arrow-right-arrow-left mr-1" aria-hidden="true"></i>Reemplazar</summary>
    <form method="POST" action="{{ $accion }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap items-center gap-2">
        @csrf
        <label for="reemplazo-{{ $id }}" class="sr-only">Nuevo archivo para {{ $texto }}</label>
        <input id="reemplazo-{{ $id }}" name="archivo" type="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*"
               class="block w-full min-w-0 flex-1 text-xs text-slate-600 file:mr-2 file:rounded-full file:border-0 file:bg-[#E8F3EF] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-[#064C3D]">
        <button type="submit" class="rounded-full bg-[#064C3D] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#043A2F]">Reemplazar</button>
    </form>
</details>
