{{-- Vista previa del acta de recibimiento del cargo con la estructura de una versión de plantilla --}}
@use('Modules\TalentoHumano\Entities\PlantillaActa')
@php
    // Controles de prueba: muestran cómo se llenará el acta (calendario, números, Sí/No). No se guardan.
    $control = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $numero = 0;
    $idPrevia = 'vp' . $plantilla->id;
@endphp

<div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">SENA Empresa · La Angostura</p>
            <h3 class="text-lg font-extrabold text-[#064C3D]">Acta de recibimiento del cargo</h3>
        </div>
        <div class="text-right text-xs text-slate-500">
            <p class="font-semibold text-slate-700">{{ $plantilla->area_id ? 'Área ' . $plantilla->nombreArea() : 'Plantilla general' }}</p>
            <p>Plantilla v{{ $plantilla->version }}</p>
        </div>
    </div>

    <div class="space-y-6 px-6 py-5 text-sm">
        <p class="rounded-2xl bg-amber-50 px-4 py-2 text-xs text-amber-800"><i class="fas fa-eye mr-1" aria-hidden="true"></i>Vista previa: puede probar los campos, pero aquí no se guarda nada.</p>

        @if ($plantilla->indicaciones)
            <p class="rounded-2xl bg-sky-50 px-4 py-3 text-sky-900"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>{{ $plantilla->indicaciones }}</p>
        @endif

        @foreach ($plantilla->secciones as $seccion)
            <section>
                <h4 class="font-bold text-slate-800">{{ ++$numero }}. {{ $seccion['titulo'] }}</h4>
                <dl class="mt-2 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                    @foreach ($seccion['campos'] as $campo)
                        @php $idCampo = $idPrevia . '-' . $loop->parent->index . '-' . $loop->index; @endphp
                        <div @class(['sm:col-span-2' => $campo['tipo'] === 'texto_largo'])>
                            <dt class="text-xs text-slate-500"><label for="{{ $idCampo }}">{{ $campo['etiqueta'] }}</label></dt>
                            <dd @class(['mt-1 text-slate-700', 'italic text-slate-400' => $campo['tipo'] === 'precargado'])>
                                @switch($campo['tipo'])
                                    @case('precargado')
                                        Se completa automáticamente
                                        @break
                                    @case('fecha')
                                        <input type="date" id="{{ $idCampo }}" min="{{ today()->format('Y-m-d') }}" class="{{ $control }} cursor-pointer">
                                        @break
                                    @case('numero')
                                        <input type="number" id="{{ $idCampo }}" min="0" step="1" inputmode="numeric" placeholder="0" class="{{ $control }}">
                                        @break
                                    @case('si_no')
                                        <span class="flex gap-4 py-1">
                                            <label class="inline-flex items-center gap-1.5"><input type="radio" name="{{ $idCampo }}" id="{{ $idCampo }}" value="si" class="accent-[#064C3D]"> Sí</label>
                                            <label class="inline-flex items-center gap-1.5"><input type="radio" name="{{ $idCampo }}" value="no" class="accent-[#064C3D]"> No</label>
                                        </span>
                                        @break
                                    @case('texto_largo')
                                        <textarea id="{{ $idCampo }}" rows="2" class="{{ $control }}"></textarea>
                                        @break
                                    @default
                                        <input type="text" id="{{ $idCampo }}" maxlength="255" class="{{ $control }}">
                                @endswitch
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        <section>
            <h4 class="font-bold text-slate-800">{{ ++$numero }}. Inventario del cargo</h4>
            <div class="mt-2 overflow-x-auto rounded-2xl ring-1 ring-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Implemento</th>
                            @foreach ($plantilla->columnas_inventario as $columna)
                                <th class="px-3 py-2 font-semibold">{{ $columna['nombre'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @forelse (array_slice($plantilla->items_base ?? [], 0, 6) as $item)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-slate-700">{{ $item['item'] }}</td>
                                @foreach ($plantilla->columnas_inventario as $columna)
                                    <td class="px-3 py-2">
                                        @php $celda = 'w-full min-w-[7rem] rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 focus:border-[#064C3D] focus:outline-none'; @endphp
                                        @if (mb_strtolower($columna['nombre']) === 'cantidad') {{ $item['cantidad'] }} {{ $item['unidad'] }}
                                        @elseif ($columna['tipo'] === 'lista')
                                            <select aria-label="{{ $columna['nombre'] }}" class="{{ $celda }}">
                                                <option value="">Seleccione…</option>
                                                @foreach ($columna['opciones'] as $opcion)<option>{{ $opcion }}</option>@endforeach
                                            </select>
                                        @elseif ($columna['tipo'] === 'fecha')
                                            <input type="date" aria-label="{{ $columna['nombre'] }}" min="{{ today()->format('Y-m-d') }}" class="{{ $celda }} cursor-pointer">
                                        @elseif ($columna['tipo'] === 'numero')
                                            <input type="number" min="0" step="1" inputmode="numeric" aria-label="{{ $columna['nombre'] }}" class="{{ $celda }}">
                                        @else
                                            <input type="text" maxlength="255" aria-label="{{ $columna['nombre'] }}" class="{{ $celda }}">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($plantilla->columnas_inventario) + 1 }}" class="px-3 py-3 text-center text-slate-400">Sin lista base: el aprendiz saliente registra los implementos.</td></tr>
                        @endforelse
                        @if (count($plantilla->items_base ?? []) > 6)
                            <tr><td colspan="{{ count($plantilla->columnas_inventario) + 1 }}" class="px-3 py-2 text-slate-400">… y {{ count($plantilla->items_base) - 6 }} implementos más de la lista base</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h4 class="font-bold text-slate-800">{{ ++$numero }}. Firmas</h4>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($plantilla->firmantes as $firmante)
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 pb-3 pt-8 text-center text-xs text-slate-500">
                        <span class="block border-t border-slate-300 pt-1">{{ PlantillaActa::FIRMANTES[$firmante][0] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
