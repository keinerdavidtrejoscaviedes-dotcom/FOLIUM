@use('Modules\TalentoHumano\Entities\PlantillaActa')
@php
    $nombre = fn ($persona) => $persona ? trim($persona->first_name . ' ' . $persona->first_last_name . ' ' . $persona->second_last_name) : 'Sin registro';
@endphp

<x-talentohumano::layouts.aprendiz title="Mi empalme" activo="empalme" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi empalme</h1>
        <p class="mt-1 text-sm text-slate-600">Inventario, visita a la unidad productiva y firma del acta de recibimiento del cargo.</p>
    </div>

    @if (!$empalme)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-handshake', 'titulo' => 'No tienes un empalme asignado', 'texto' => 'El empalme se habilita cuando quedas seleccionado en el acta o cuando debes entregar tu cargo al terminar la fase.'])
    @else
        @php
            $salientes = $empalme->participantes->where('papel', 'saliente');
            $receptores = $empalme->participantes->where('papel', 'receptor');
            $contenido = $empalme->acta?->contenido ?? [];
            $registrado = !empty($contenido['inventario_registrado']);
            $verificado = $pasos[1][1];
            $cerrado = $empalme->estado === 'cerrado';
            $editaInventario = !$cerrado && !$empalme->visita_confirmada && ($papel === 'saliente' || ($papel === 'receptor' && $salientes->isEmpty()));
            $verificaInventario = !$cerrado && !$empalme->visita_confirmada && $papel === 'receptor' && $salientes->isNotEmpty() && $registrado;
            $firmas = $empalme->acta?->firmas ?? collect();
            $otros = array_intersect($firmantes, ['instructor_area', 'jefe_inmediato']);
            $campo = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
        @endphp

        <section class="mb-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Empalme {{ $empalme->tipo }} · {{ $empalme->cargo?->area?->nombre ?? 'Sin área' }} · {{ $empalme->fase?->nombre_completo }}</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $empalme->cargo?->nombre }}</h2>
                    @if ($empalme->cargo_receptor_id && $empalme->cargo_receptor_id !== $empalme->cargo_id)
                        <p class="mt-1 text-xs text-sky-700"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Lo recibe el cargo {{ $empalme->cargoReceptor?->nombre }}, que queda con la custodia del inventario.</p>
                    @endif
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $papel === 'saliente' ? 'bg-amber-100 text-amber-800' : 'bg-[#E8F3EF] text-[#064C3D]' }}">{{ $papel === 'saliente' ? 'Entregas el cargo' : 'Recibes el cargo' }}</span>
            </div>
            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-400">Entrega{{ $salientes->count() > 1 ? 'n' : '' }}</p><p class="mt-1 text-slate-700">{{ $salientes->map(fn ($s) => $nombre($s->asignacion?->persona))->implode(' · ') ?: 'Sin aprendiz saliente (cargo nuevo)' }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-400">Recibe{{ $receptores->count() > 1 ? 'n' : '' }}</p><p class="mt-1 text-slate-700">{{ $receptores->map(fn ($r) => $nombre($r->asignacion?->persona))->implode(' · ') ?: '—' }}</p></div>
            </div>
            <ol class="mt-5 grid gap-2 sm:grid-cols-3 2xl:grid-cols-6">
                @foreach ($pasos as $i => [$texto, $hecho])
                    <li class="flex items-center gap-2 rounded-2xl p-2.5 text-xs font-semibold {{ $hecho ? 'bg-[#E8F3EF] text-[#064C3D]' : 'bg-slate-50 text-slate-500' }}">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] {{ $hecho ? 'bg-[#064C3D] text-[#34D399]' : 'bg-slate-200 text-slate-600' }}">@if ($hecho)<i class="fas fa-check" aria-hidden="true"></i>@else{{ $i + 1 }}@endif</span>
                        {{ $texto }}
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- 1. Inventario (HU-052, HU-053) --}}
        <section class="mb-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]">1. Inventario del cargo</h2>
            @if ($editaInventario)
                <p class="text-xs text-slate-500">{{ $papel === 'saliente' ? 'Ajusta el inventario real que entregas: agrega o quita implementos e indica cantidad y estado.' : 'El cargo no tiene aprendiz saliente: registra el inventario inicial. Quedará pendiente de validación por el Gestor de Talento Humano.' }}</p>
                <form method="POST" action="{{ route('talentohumano.aprendiz.empalme.inventario') }}" class="mt-4">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="py-2 pr-2">Implemento</th><th class="w-24 px-2 py-2">Cantidad</th><th class="w-36 px-2 py-2">Estado</th><th class="px-2 py-2">Observación</th><th class="w-10"><span class="sr-only">Quitar</span></th></tr></thead>
                            <tbody id="filas-inventario">
                                @php
                                    $filas = $empalme->inventarios->map(fn ($i) => ['item' => $i->item, 'cantidad' => $i->cantidad, 'estado' => $i->estado, 'observacion' => $i->novedad, 'falta_cantidad' => false, 'falta_estado' => false])
                                        ->merge(collect(session('itemsInvalidos', []))->map(fn ($i) => $i + ['observacion' => '']));
                                @endphp
                                @foreach ($filas->isEmpty() ? collect([['item' => '', 'cantidad' => '', 'estado' => '', 'observacion' => '', 'falta_cantidad' => false, 'falta_estado' => false]]) : $filas as $n => $fila)
                                    <tr class="border-t border-slate-100" data-fila>
                                        <td class="py-2 pr-2"><input name="items[{{ $n }}][item]" value="{{ $fila['item'] }}" maxlength="255" aria-label="Implemento" class="{{ $campo }}"></td>
                                        <td class="px-2 py-2"><input name="items[{{ $n }}][cantidad]" value="{{ $fila['cantidad'] }}" type="number" min="1" inputmode="numeric" aria-label="Cantidad" class="{{ $campo }} {{ $fila['falta_cantidad'] ? 'border-red-400 ring-1 ring-red-200' : '' }}"></td>
                                        <td class="px-2 py-2">
                                            <select name="items[{{ $n }}][estado]" aria-label="Estado" class="{{ $campo }} {{ $fila['falta_estado'] ? 'border-red-400 ring-1 ring-red-200' : '' }}">
                                                <option value="">—</option>
                                                @foreach (collect($estados)->push($fila['estado'])->filter()->unique() as $estado)
                                                    <option @selected($fila['estado'] === $estado)>{{ $estado }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2"><input name="items[{{ $n }}][observacion]" value="{{ $fila['observacion'] }}" maxlength="500" aria-label="Observación" class="{{ $campo }}"></td>
                                        <td class="py-2 text-right"><button type="button" data-quitar class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Quitar implemento"><i class="fas fa-trash" aria-hidden="true"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex flex-wrap justify-between gap-2">
                        <button type="button" id="agregar-fila" class="rounded-full bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-[#d5ebe3]"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar implemento</button>
                        <button type="submit" class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-bold text-white hover:bg-[#043A2F]">Guardar inventario</button>
                    </div>
                </form>
            @elseif ($verificaInventario)
                <p class="text-xs text-slate-500">Revisa cada implemento en la unidad. Marca «Coincide» o describe la diferencia: la novedad queda documentada y se avisa al Gestor de Talento Humano.</p>
                <form method="POST" action="{{ route('talentohumano.aprendiz.empalme.verificacion') }}" class="mt-4 space-y-2">
                    @csrf
                    @foreach ($empalme->inventarios as $item)
                        <div class="grid items-center gap-2 rounded-2xl bg-slate-50 p-3 text-sm sm:grid-cols-[1fr_auto_1fr]">
                            <p><span class="font-semibold text-slate-800">{{ $item->item }}</span> <span class="text-slate-500">· {{ $item->cantidad }} · {{ $item->estado }}</span></p>
                            <label class="flex items-center gap-2"><input type="checkbox" name="verificacion[{{ $item->id }}][coincide]" value="1" @checked(old("verificacion.{$item->id}.coincide", $item->verificado && !$item->novedad)) class="h-4 w-4 accent-[#064C3D]"> Coincide</label>
                            <input name="verificacion[{{ $item->id }}][novedad]" value="{{ old("verificacion.{$item->id}.novedad", $item->novedad) }}" maxlength="500" placeholder="Diferencia encontrada (opcional)" aria-label="Diferencia en {{ $item->item }}" class="{{ $campo }}">
                        </div>
                    @endforeach
                    <div class="flex justify-end pt-2"><button type="submit" class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-bold text-white hover:bg-[#043A2F]">Guardar verificación</button></div>
                </form>
            @else
                @if (!$registrado && !$cerrado)
                    <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">El aprendiz que entrega aún no registra el inventario.</p>
                @endif
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="py-2 pr-2">Implemento</th><th class="px-2 py-2">Cantidad</th><th class="px-2 py-2">Estado</th><th class="px-2 py-2">Verificación</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($empalme->inventarios as $item)
                                <tr>
                                    <td class="py-2 pr-2 font-semibold text-slate-800">{{ $item->item }}</td>
                                    <td class="px-2 py-2">{{ $item->cantidad }}</td>
                                    <td class="px-2 py-2">{{ $item->estado ?? '—' }}</td>
                                    <td class="px-2 py-2">
                                        @if ($item->novedad && $item->verificado)<span class="text-amber-700"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>{{ $item->novedad }}</span>
                                        @elseif ($item->verificado)<span class="text-emerald-700"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i>Verificado</span>
                                        @else<span class="text-slate-400">Pendiente</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-500">El inventario está vacío.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- 2. Visita a la unidad productiva (HU-057) --}}
        <section class="mb-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]">2. Visita a la unidad productiva</h2>
            @if ($empalme->visita_confirmada)
                <p class="mt-2 text-sm text-slate-700"><i class="fas fa-circle-check mr-1 text-emerald-600" aria-hidden="true"></i>Visita confirmada{{ $empalme->fecha_visita ? ' el ' . $empalme->fecha_visita->format('d/m/Y') : '' }}.</p>
                @if ($empalme->participantes_visita)
                    <p class="mt-1 text-xs text-slate-500">Participantes: {{ implode(', ', $empalme->participantes_visita) }}</p>
                @endif
            @elseif (!$verificado)
                <p class="mt-2 text-sm text-slate-500"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Se habilita cuando el inventario esté verificado.</p>
            @elseif (!$cerrado)
                <form method="POST" action="{{ route('talentohumano.aprendiz.empalme.visita') }}" class="mt-3 space-y-3">
                    @csrf
                    <div class="max-w-xs">
                        <label for="fecha_visita" class="text-sm font-semibold text-slate-700">Fecha de la visita *</label>
                        <input id="fecha_visita" name="fecha_visita" type="date" max="{{ today()->toDateString() }}" value="{{ old('fecha_visita', today()->toDateString()) }}" class="mt-1 {{ $campo }}">
                        @error('fecha_visita')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-700">Participantes *</legend>
                        <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                            @foreach ($empalme->participantes as $participante)
                                @php $persona = $nombre($participante->asignacion?->persona); @endphp
                                <label class="flex items-center gap-2"><input type="checkbox" name="participantes[]" value="{{ $persona }}" @checked(in_array($persona, old('participantes', []))) class="h-4 w-4 accent-[#064C3D]"> {{ $persona }} <span class="text-xs text-slate-400">({{ $participante->papel === 'saliente' ? 'entrega' : 'recibe' }})</span></label>
                            @endforeach
                        </div>
                        <label for="otros" class="mt-2 block text-xs text-slate-500">Otros participantes (instructor, gestor…), separados por coma</label>
                        <input id="otros" name="otros" value="{{ old('otros') }}" maxlength="500" class="mt-1 {{ $campo }}">
                        @error('participantes')<p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    </fieldset>
                    <button type="submit" class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-bold text-white hover:bg-[#043A2F]">Confirmar visita</button>
                </form>
            @endif
        </section>

        {{-- 3. Firmas del acta de recibimiento (HU-052, HU-057) --}}
        <section class="mb-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-bold text-[#064C3D]">3. Acta de recibimiento del cargo</h2>
                @php $estadoActa = $empalme->acta?->estado === 'firmada' ? ['Firmada', 'bg-emerald-100 text-emerald-800'] : ($firmas->isNotEmpty() ? ['Pendiente de firmas', 'bg-amber-100 text-amber-800'] : ['Sin firmas', 'bg-slate-100 text-slate-600']); @endphp
                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $estadoActa[1] }}">{{ $estadoActa[0] }}</span>
            </div>
            @if (!empty($contenido['codigo']))<p class="text-xs text-slate-500">Acta {{ $contenido['codigo'] }}</p>@endif
            <ul class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach ($aprendices as $personId => $rol)
                    @php $firma = $firmas->firstWhere('person_id', $personId); $participante = $empalme->participantes->first(fn ($x) => $x->asignacion?->person_id == $personId); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span><span class="font-semibold text-slate-800">{{ $nombre($participante?->asignacion?->persona) }}</span> <span class="text-xs text-slate-500">· {{ $rol }}{{ $personId == $p->user->person_id ? ' (tú)' : '' }}</span></span>
                        @if ($firma)<span class="text-xs text-emerald-700"><i class="fas fa-signature mr-1" aria-hidden="true"></i>Firmó el {{ $firma->fecha_firma->format('d/m/Y h:i a') }}</span>@else<span class="text-xs text-amber-700">Por firmar</span>@endif
                    </li>
                @endforeach
                @foreach ($otros as $firmante)
                    @php $firma = $firmas->whereNotIn('person_id', array_keys($aprendices))->first(); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="font-semibold text-slate-800">{{ PlantillaActa::FIRMANTES[$firmante][0] }}</span>
                        @if ($firma)<span class="text-xs text-emerald-700"><i class="fas fa-signature mr-1" aria-hidden="true"></i>Firmó {{ $nombre($firma->person) }}</span>@else<span class="text-xs text-amber-700">Por firmar</span>@endif
                    </li>
                @endforeach
            </ul>
            @if (!$yaFirme && !$cerrado && isset($aprendices[$p->user->person_id]))
                <form method="POST" action="{{ route('talentohumano.aprendiz.empalme.firmar') }}" class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    @csrf
                    <p class="text-xs text-slate-500">Al firmar confirmas que el acta y el inventario son correctos. Se registra la fecha y la hora.</p>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2 text-sm font-bold text-white hover:bg-[#043A2F]"><i class="fas fa-signature" aria-hidden="true"></i> Firmar acta</button>
                </form>
            @endif
        </section>

        @if ($empalme->acta?->estado === 'firmada')
            <a href="{{ route('talentohumano.aprendiz.satisfaccion') }}" class="flex items-center justify-between gap-3 rounded-3xl bg-[#064C3D] p-5 text-white shadow-lg hover:bg-[#043A2F]">
                <span><span class="block font-bold">4. Formulario de Satisfacción del Empalme</span><span class="text-sm text-emerald-100/85">El acta está firmada: diligencia el formulario para cerrar el empalme.</span></span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        @endif

        @if ($editaInventario)
            @push('scripts')
                <script>
                    (() => {
                        const cuerpo = document.getElementById('filas-inventario');
                        let indice = cuerpo.querySelectorAll('[data-fila]').length;
                        document.getElementById('agregar-fila').addEventListener('click', () => {
                            const fila = cuerpo.querySelector('[data-fila]').cloneNode(true);
                            fila.querySelectorAll('input, select').forEach((campo) => {
                                campo.name = campo.name.replace(/items\[\d+\]/, `items[${indice}]`);
                                campo.value = '';
                                campo.classList.remove('border-red-400', 'ring-1', 'ring-red-200');
                            });
                            indice++;
                            cuerpo.appendChild(fila);
                            fila.querySelector('input').focus();
                        });
                        cuerpo.addEventListener('click', (e) => {
                            const boton = e.target.closest('[data-quitar]');
                            if (!boton) return;
                            const fila = boton.closest('[data-fila]');
                            if (cuerpo.querySelectorAll('[data-fila]').length > 1) fila.remove();
                            else fila.querySelectorAll('input, select').forEach((campo) => { campo.value = ''; });
                        });
                    })();
                </script>
            @endpush
        @endif
    @endif
</x-talentohumano::layouts.aprendiz>
