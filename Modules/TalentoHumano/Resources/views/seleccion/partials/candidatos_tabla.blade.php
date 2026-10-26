{{-- Bloque: Tabla de candidatos por jerarquía de mérito (HU-038 · RN-021) --}}
<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70 mb-6">
    <div class="border-b border-slate-100 px-6 py-4">
        <h2 class="font-bold text-[#064C3D]">Candidatos por jerarquía de preferencia</h2>
        <p class="mt-1 text-xs text-slate-500">
            Primero los de 1.ª opción por puntaje total; si quedan cupos, los de 2.ª y luego 3.ª. Total = prueba psicotécnica + entrevista (promedio de los evaluadores).
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="w-12 px-4 py-3 text-center"><span class="sr-only">Elegir</span></th>
                    <th class="px-4 py-3 font-semibold">Aspirante</th>
                    <th class="px-4 py-3 text-center font-semibold">Opción</th>
                    <th class="px-4 py-3 text-center font-semibold">Prueba</th>
                    <th class="px-4 py-3 text-center font-semibold">Entrevista</th>
                    <th class="px-4 py-3 text-center font-semibold">Total</th>
                    <th class="px-4 py-3 font-semibold">Propuesta</th>
                </tr>
            </thead>
            <tbody id="candidatos-tbody" class="divide-y divide-slate-100">
                @forelse ($candidatos as $cand)
                    @php
                        $filaClase = '';
                        if ($esEmpate && $cand->total == 147) {
                            $filaClase = 'bg-red-50/40';
                        } elseif ($cand->seleccionado) {
                            $filaClase = 'bg-emerald-50/60';
                        }
                    @endphp
                    <tr class="{{ $filaClase }} hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox"
                                   name="candidatos_ids[]"
                                   form="form-decision"
                                   value="{{ $cand->postulacion_id }}"
                                   class="h-4 w-4 rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D] accent-[#064C3D] candidato-chk"
                                   {{ $cand->seleccionado ? 'checked' : '' }}
                                   {{ $esValidado || !empty($cand->nota) ? 'disabled' : '' }}
                                   data-original="{{ $cand->seleccionado ? '1' : '0' }}">
                        </td>
                        <td class="px-4 py-3">
                            @if ($cand->postulacion_id)
                                <a href="{{ route('talentohumano.postulaciones.show', $cand->postulacion_id) }}"
                                   class="font-semibold text-slate-800 hover:text-[#064C3D] hover:underline">
                                    {{ $cand->nombre }}
                                </a>
                            @else
                                <span class="font-semibold text-slate-800">{{ $cand->nombre }}</span>
                            @endif
                            @if (!empty($cand->nota))
                                <p class="text-xs text-slate-400">{{ $cand->nota }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $opClass = match ($cand->preferencia) {
                                    1 => 'bg-[#064C3D] text-white',
                                    2 => 'bg-emerald-500 text-white',
                                    default => 'bg-[#34D399] text-[#043A2F]',
                                };
                            @endphp
                            <span class="inline-grid h-6 w-6 place-items-center rounded-full {{ $opClass }} text-xs font-bold">
                                {{ $cand->preferencia }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600 font-medium">
                            {{ $cand->prueba }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600 font-medium">
                            {{ $cand->entrevista }} <span class="text-[11px] text-slate-400">· 3 ev.</span>
                        </td>
                        <td class="px-4 py-3 text-center font-extrabold {{ $cand->total >= 120 ? 'text-[#064C3D]' : 'text-red-600' }}">
                            {{ $cand->total }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($esEmpate && $cand->total == 147)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">
                                    Empate
                                </span>
                            @elseif ($cand->seleccionado)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                    Seleccionado
                                </span>
                            @elseif ($cand->total < 120)
                                <span class="text-xs font-semibold text-red-600">
                                    Bajo el umbral
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-400">
                            No hay aspirantes postulados para este cargo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
