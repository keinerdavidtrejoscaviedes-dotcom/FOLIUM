@php
    $persona = fn ($p) => $p ? trim($p->first_name . ' ' . $p->first_last_name) : 'Sin registro';
    $estado = [
        'enviado' => ['En aprobación', 'bg-sky-100 text-sky-800'],
        'aprobado' => ['Aprobado', 'bg-emerald-100 text-emerald-800'],
        'rechazado' => ['Rechazado', 'bg-amber-100 text-amber-800'],
        'borrador' => ['Borrador', 'bg-slate-100 text-slate-600'],
    ][$cronograma->estado];
    $esModificacion = $vigentes !== null;
@endphp

<x-talentohumano::layouts.lider title="Cronograma del proceso de selección" activo="aprobaciones">
    <a href="{{ route('talentohumano.lider.aprobaciones.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline"><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Aprobaciones pendientes</a>
    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Cronograma del proceso de selección</h1>
            <p class="mt-1 text-sm text-slate-600">Convocatoria {{ $cronograma->fase->nombre_completo }} · enviado por {{ $persona($cronograma->enviadoPor) }} (Gestor de Talento Humano)</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $estado[1] }}">{{ $estado[0] }}</span>
    </div>

    @if ($esModificacion)
        <div class="mt-4 flex items-start gap-3 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-900">
            <i class="fas fa-code-compare mt-0.5" aria-hidden="true"></i>
            <span>Es una <strong>modificación</strong> del cronograma aprobado el {{ $vigente->fecha_aprobacion?->format('d/m/Y') }}. Mientras no la apruebes siguen vigentes las fechas anteriores.</span>
        </div>
    @elseif ($cronograma->estado === 'aprobado')
        <div class="mt-4 flex items-start gap-3 rounded-2xl bg-[#E8F3EF] px-4 py-3 text-sm text-[#064C3D]" role="status">
            <i class="fas fa-circle-check mt-0.5" aria-hidden="true"></i>
            <span><strong>Aprobado</strong> por {{ $persona($cronograma->aprobadoPor) }} el {{ $cronograma->fecha_aprobacion?->format('d/m/Y') }}. Ya se puede publicar la convocatoria.</span>
        </div>
    @elseif ($cronograma->estado === 'rechazado')
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <i class="fas fa-rotate-left mt-0.5 text-amber-500" aria-hidden="true"></i>
            <span>Devuelto al Gestor de Talento Humano con el motivo: «{{ $cronograma->motivo_rechazo }}».</span>
        </div>
    @endif

    <section class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
        <h2 class="font-bold text-[#064C3D]">Etapas</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Etapa</th>
                        @if ($esModificacion) <th class="px-4 py-3 font-semibold">Vigente</th> @endif
                        <th class="px-4 py-3 font-semibold">{{ $esModificacion ? 'Propuesta' : 'Fechas' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($etapas as $clave => $etapa)
                        @php($cambia = $esModificacion && $vigentes[$clave]['fechas'] !== $etapa['fechas'])
                        <tr class="{{ $cambia ? 'bg-amber-50/60' : '' }}">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $etapa['nombre'] }}</td>
                            @if ($esModificacion)
                                <td class="px-4 py-3 text-slate-500 {{ $cambia ? 'line-through' : '' }}">{{ $vigentes[$clave]['fechas'] }}</td>
                            @endif
                            <td class="px-4 py-3 {{ $cambia ? 'font-semibold text-amber-800' : 'text-slate-700' }}">
                                {{ $etapa['fechas'] }}
                                @if ($cambia) <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px]">cambia</span> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($cronograma->estado === 'enviado')
        <div class="mt-4 flex flex-wrap justify-end gap-2 pb-4">
            <button type="button" data-abrir="#modal-rechazo" class="rounded-full px-5 py-2.5 text-sm font-semibold text-amber-700 ring-1 ring-amber-300 hover:bg-amber-50">
                <i class="fas fa-rotate-left mr-1" aria-hidden="true"></i> Rechazar
            </button>
            <form method="POST" action="{{ route('talentohumano.lider.aprobaciones.aprobar', $cronograma) }}"
                  data-titulo="¿Aprobar el cronograma?" data-confirmar="Se avisará por correo a los aprendices de las fichas de la fase y el Gestor podrá publicar la convocatoria.">
                @csrf @method('PATCH')
                <button class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-check mr-1" aria-hidden="true"></i> Aprobar</button>
            </form>
        </div>

        {{-- HU-034, esc. 2 y 3: rechazo con motivo obligatorio --}}
        <dialog id="modal-rechazo" aria-labelledby="titulo-rechazo" class="m-auto w-[min(94vw,30rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50" @if ($errors->has('motivo')) data-abrir-al-cargar @endif>
            <form method="POST" action="{{ route('talentohumano.lider.aprobaciones.rechazar', $cronograma) }}" novalidate class="p-6">
                @csrf @method('PATCH')
                <h2 id="titulo-rechazo" class="text-lg font-bold text-slate-900">Rechazar el cronograma</h2>
                <p class="mt-1 text-sm text-slate-600">Se devolverá al Gestor de Talento Humano para que lo ajuste.</p>
                <label for="motivo" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Motivo <span class="text-red-500">*</span></label>
                <textarea id="motivo" name="motivo" rows="3" maxlength="1000" @if ($errors->has('motivo')) aria-invalid="true" aria-describedby="motivo-error" @endif
                          class="w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 {{ $errors->has('motivo') ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]' }}">{{ old('motivo') }}</textarea>
                @error('motivo') <p id="motivo-error" class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button class="rounded-full bg-amber-500 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-600">Confirmar</button>
                </div>
            </form>
        </dialog>
    @endif
</x-talentohumano::layouts.lider>
