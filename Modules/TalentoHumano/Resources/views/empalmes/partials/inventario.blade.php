{{-- Bloque: Inventario del Cargo (Entrega y Verificación RN-061) --}}
<section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-4 border-b border-slate-100">
        <div>
            <h2 class="font-bold text-[#064C3D] text-base flex items-center gap-2">
                <i class="fas fa-clipboard-check text-emerald-600"></i>
                <span>Inventario del cargo a entregar</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Bienes físicos, documentación, carpetas y accesos transferidos durante el empalme (RN-061).
            </p>
        </div>

        <button type="button" onclick="document.getElementById('form-nuevo-item').classList.toggle('hidden')"
                class="rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <i class="fas fa-plus text-xs"></i> Agregar elemento
        </button>
    </div>

    {{-- Formulario desplegable para agregar nuevo ítem --}}
    <div id="form-nuevo-item" class="hidden bg-slate-50/80 p-5 border-b border-slate-100">
        <form action="{{ route('talentohumano.empalmes.items.agregar', $empalme->id) }}" method="POST" class="space-y-3">
            @csrf
            <div class="grid gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Descripción del elemento *</label>
                    <input type="text" name="item" required placeholder="Ej: Llaves de oficina, libro de registros..."
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cantidad *</label>
                    <input type="number" name="cantidad" value="1" min="1" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Estado físico *</label>
                    <select name="estado" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs">
                        <option value="Excelente">Excelente</option>
                        <option value="Bueno" selected>Bueno</option>
                        <option value="Regular">Regular</option>
                        <option value="Malo">Malo</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Novedad u observación (opcional)</label>
                <input type="text" name="novedad" placeholder="Ej: Rayón leve en carcasa, pendiente recarga..."
                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs">
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="document.getElementById('form-nuevo-item').classList.add('hidden')"
                        class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-full bg-[#064C3D] px-4 py-1 text-xs font-bold text-white shadow-xs">
                    Guardar elemento
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de Inventario --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="w-12 px-4 py-3 text-center">Verif.</th>
                    <th class="px-4 py-3 font-semibold">Elemento / Bien</th>
                    <th class="px-4 py-3 text-center font-semibold">Cant.</th>
                    <th class="px-4 py-3 text-center font-semibold">Estado físico</th>
                    <th class="px-4 py-3 font-semibold">Novedades</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($empalme->inventarios as $inv)
                    <tr class="hover:bg-slate-50/60 transition {{ $inv->verificado ? 'bg-emerald-50/20' : '' }}">
                        <td class="px-4 py-3 text-center">
                            <form action="{{ route('talentohumano.empalmes.items.toggle', [$empalme->id, $inv->id]) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="inline-grid h-6 w-6 place-items-center rounded-lg border transition {{ $inv->verificado ? 'bg-[#064C3D] border-[#064C3D] text-white shadow-xs' : 'border-slate-300 hover:border-[#064C3D]' }}"
                                        title="Alternar verificación">
                                    @if ($inv->verificado)
                                        <i class="fas fa-check text-xs"></i>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-800 text-xs">
                            {{ $inv->item }}
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-bold text-slate-600">
                            {{ $inv->cantidad }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $estadoPill = match ($inv->estado) {
                                    'Excelente' => 'bg-emerald-100 text-emerald-800',
                                    'Bueno' => 'bg-slate-100 text-slate-700',
                                    'Regular' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $estadoPill }}">
                                {{ $inv->estado ?? 'Bueno' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $inv->novedad ?: 'Sin novedades reportadas' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-400">
                            No hay elementos registrados en el inventario de este cargo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
