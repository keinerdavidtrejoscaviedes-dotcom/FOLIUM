<x-talentohumano::layouts.aprendiz title="Acta de seleccionados" activo="acta" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Acta de seleccionados</h1>
        <p class="mt-1 text-sm text-slate-600">Resultado oficial de la selección, en modo de solo lectura.</p>
    </div>

    @if (!$p->postulacion)
        {{-- HU-051, esc. 2: solo los postulantes de la convocatoria consultan el acta --}}
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-lock', 'titulo' => 'El acta solo está disponible para los postulantes de la convocatoria'])
    @elseif (!$acta)
        @include('talentohumano::aprendiz._aviso', [
            'icono' => 'fa-file-circle-question',
            'titulo' => 'El acta de seleccionados aún no ha sido publicada',
            'texto' => $etapa?->inicio ? 'Según el cronograma, se publica el ' . $etapa->inicio->format('d/m/Y') . '.' : 'La fecha de publicación aún no está en el cronograma.',
        ])
    @else
        <article class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
            <header class="flex flex-wrap items-center gap-3 border-b-2 border-[#064C3D] pb-4">
                <span class="grid h-11 w-11 place-items-center rounded-full bg-[#E8F3EF] text-lg text-[#064C3D]"><i class="fas fa-seedling" aria-hidden="true"></i></span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-400">SENA Empresa · Talento Humano</p>
                    <h2 class="text-lg font-extrabold text-[#064C3D]">Acta de seleccionados · {{ $p->fase->nombre_completo }}</h2>
                </div>
                <p class="ml-auto text-xs text-slate-500">Publicada el {{ $acta->fecha_publicacion?->format('d/m/Y') }}</p>
            </header>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-3 py-2">Cargo</th><th class="px-3 py-2">Aprendiz</th><th class="hidden px-3 py-2 sm:table-cell">Ficha</th><th class="px-3 py-2"><span class="sr-only">Observación</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($filas as $fila)
                            @php $propia = $fila->person_id == $p->user->person_id; @endphp
                            <tr class="{{ $propia ? 'bg-[#E8F3EF] font-semibold' : '' }}">
                                <td class="px-3 py-2 text-slate-800">{{ $fila->cargo }}</td>
                                <td class="px-3 py-2">{{ $fila->aprendiz }}{{ $propia ? ' (tú)' : '' }}</td>
                                <td class="hidden px-3 py-2 text-slate-500 sm:table-cell">{{ $fila->ficha ?? '—' }}</td>
                                <td class="px-3 py-2">@if ($fila->observacion)<span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">Reubicación</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">El acta no tiene aprendices registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endif
</x-talentohumano::layouts.aprendiz>
