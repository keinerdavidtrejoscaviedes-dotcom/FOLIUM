{{-- Vista previa del acta de recibimiento del cargo con la estructura de una versión de plantilla --}}
@use('Modules\TalentoHumano\Entities\PlantillaActa')
@php
    $muestra = fn (array $campo) => match ($campo['tipo']) {
        'precargado' => 'Se completa automáticamente',
        'fecha' => '__ / __ / ____',
        'si_no' => '☐ Sí   ☐ No',
        'numero' => '______',
        default => null,
    };
    $numero = 0;
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
        @if ($plantilla->indicaciones)
            <p class="rounded-2xl bg-sky-50 px-4 py-3 text-sky-900"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>{{ $plantilla->indicaciones }}</p>
        @endif

        @foreach ($plantilla->secciones as $seccion)
            <section>
                <h4 class="font-bold text-slate-800">{{ ++$numero }}. {{ $seccion['titulo'] }}</h4>
                <dl class="mt-2 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                    @foreach ($seccion['campos'] as $campo)
                        <div @class(['sm:col-span-2' => $campo['tipo'] === 'texto_largo'])>
                            <dt class="text-xs text-slate-500">{{ $campo['etiqueta'] }}</dt>
                            <dd @class(['mt-0.5 text-slate-700', 'italic text-slate-400' => $campo['tipo'] === 'precargado'])>
                                @if ($muestra($campo))
                                    {{ $muestra($campo) }}
                                @else
                                    <span class="block border-b border-dashed border-slate-300 {{ $campo['tipo'] === 'texto_largo' ? 'h-10' : 'h-5' }}"></span>
                                @endif
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
                                        @if (mb_strtolower($columna['nombre']) === 'cantidad') {{ $item['cantidad'] }} {{ $item['unidad'] }}
                                        @elseif ($columna['tipo'] === 'lista') <span class="text-slate-400">{{ implode(' / ', $columna['opciones']) }}</span>
                                        @else — @endif
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
