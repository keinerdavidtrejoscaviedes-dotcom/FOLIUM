{{-- Bloque Lateral: Opciones de cargos y Documentos adjuntos --}}
<aside class="space-y-4">
    {{-- Opciones de cargo --}}
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-list-ol text-emerald-600"></i>
            <span>Cargos postulados</span>
        </h2>
        <ol class="mt-4 space-y-2 text-sm">
            @foreach ($postulacion->opciones as $op)
                <li class="flex items-center gap-2.5 p-2 rounded-2xl ring-1 ring-slate-100 hover:bg-slate-50">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full {{ $op->preferencia == 1 ? 'bg-[#064C3D] text-white' : ($op->preferencia == 2 ? 'bg-emerald-500 text-white' : 'bg-[#34D399] text-[#043A2F]') }} text-xs font-bold">
                        {{ $op->preferencia }}
                    </span>
                    <span class="font-semibold text-xs text-slate-800">
                        {{ $op->cargo_nombre }}
                    </span>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Documentos adjuntos --}}
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-folder-open text-emerald-600"></i>
            <span>Documentos</span>
        </h2>
        <ul class="mt-4 space-y-2 text-xs">
            @forelse ($postulacion->documentos as $doc)
                <li class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-slate-200 bg-slate-50/50 hover:bg-slate-50 transition">
                    <i class="fas fa-file-pdf text-red-500 text-lg"></i>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-slate-800 truncate">{{ $doc->nombre_original }}</p>
                        <span class="text-[10px] uppercase text-slate-400">{{ $doc->tipo }}</span>
                    </div>
                    <a href="#" class="rounded-lg p-1.5 text-slate-500 hover:text-[#064C3D]" title="Descargar documento">
                        <i class="fas fa-download"></i>
                    </a>
                </li>
            @empty
                <li class="p-3 text-slate-400 text-xs italic">
                    No hay documentos registrados.
                </li>
            @endforelse
        </ul>
    </section>
</aside>
