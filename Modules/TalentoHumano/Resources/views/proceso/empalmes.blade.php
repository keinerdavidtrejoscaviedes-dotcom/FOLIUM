<x-talentohumano::layouts.master title="Empalmes · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Empalmes · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-029A · HU-029B · HU-031A · HU-031B · HU-033"
        subtitulo="El sistema determina quién entrega y quién recibe cada cargo. Siga cada empalme hasta el visto bueno, valide los inventarios iniciales y, cuando todos entreguen, cierre la fase saliente." />

    @if ($bloqueo)
        <div class="rounded-2xl bg-white p-10 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">
            <i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> {{ $bloqueo }}
        </div>
    @else
        <x-talentohumano::errores />

        {{-- Acciones --}}
        @unless ($soloConsulta)
            <div class="mb-6 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('talentohumano.fases.empalmes.preparar', $fase->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        <i class="fas fa-people-arrows" aria-hidden="true"></i> Preparar empalmes
                    </button>
                </form>
                @if ($empalmes->isNotEmpty())
                    <form method="POST" action="{{ route('talentohumano.fases.empalmes.citar', $fase->id) }}"
                          onsubmit="return confirm('Se enviará la citación al empalme a cada aprendiz saliente y a quien recibe. ¿Enviar?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-slate-300 hover:bg-slate-50">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar citaciones
                        </button>
                    </form>
                @endif
                @if ($saliente)
                    <form method="POST" action="{{ route('talentohumano.fases.empalmes.cerrar-saliente', $fase->id) }}"
                          onsubmit="return confirm('Al cerrar la {{ $saliente->nombre() }} sus asignaciones pasan a finalizada, se retiran las funciones del cargo y el rol Gestor de Talento Humano, y la {{ $fase->nombre() }} queda en curso. ¿Confirmar cierre?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
                            <i class="fas fa-flag-checkered" aria-hidden="true"></i> Cerrar la {{ $saliente->nombre() }}
                        </button>
                    </form>
                @endif
            </div>
        @endunless

        {{-- HU-029B esc. 2: todo saliente debe entregar su cargo --}}
        @if ($sinEmpalme->isNotEmpty())
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i> Pendientes de entrega ({{ $sinEmpalme->count() }}): aún no tienen empalme</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($sinEmpalme as $asignacion)
                        <li class="rounded-full bg-white px-3 py-1 ring-1 ring-amber-200">{{ trim("{$asignacion->persona?->first_name} {$asignacion->persona?->first_last_name}") }} · {{ $asignacion->cargo?->nombre }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HU-029B esc. 1: cada empalme con sus partes, su paso y lo que falta --}}
        <div class="space-y-4">
            @forelse ($empalmes as $empalme)
                @php
                    $salientes = $empalme->participantes->where('papel', 'saliente');
                    $receptores = $empalme->participantes->where('papel', 'receptor');
                    $nombres = fn ($grupo) => $grupo->map(fn ($p) => trim("{$p->asignacion?->persona?->first_name} {$p->asignacion?->persona?->first_last_name}"))->implode(', ');
                    $hechos = collect($empalme->pasos)->filter()->count();
                    $inicial = $salientes->isEmpty();
                    $contenido = $empalme->acta?->contenido ?? [];
                @endphp
                <article @class(['rounded-2xl bg-white p-5 shadow-sm ring-1', 'ring-[#34D399]/60' => $empalme->estado === 'cerrado', 'ring-slate-200' => $empalme->estado !== 'cerrado'])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-slate-900">
                                {{ $empalme->cargo?->nombre }}
                                @if ($empalme->cargo_receptor_id != $empalme->cargo_id)
                                    <span class="font-normal text-slate-500">→ recibe {{ $empalme->cargoReceptor?->nombre }}</span>
                                @endif
                            </h2>
                            <p class="text-sm text-slate-600">
                                @if ($inicial)
                                    <span class="font-semibold text-amber-700">Inventario inicial</span> · sin aprendiz saliente
                                @else
                                    Entrega: {{ $nombres($salientes) }}
                                @endif
                                · Recibe: {{ $nombres($receptores) }}
                                · <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ ucfirst($empalme->tipo) }}</span>
                            </p>
                        </div>
                        <a href="{{ route('talentohumano.empalmes.show', $empalme->id) }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-[#064C3D] hover:bg-[#E8F3EF]">
                            Ver empalme <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-[#34D399]" style="width: {{ round($hechos * 100 / count($empalme->pasos)) }}%"></div>
                    </div>
                    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                        @foreach ($empalme->pasos as $paso => $hecho)
                            <li @class(['text-emerald-700' => $hecho, 'text-slate-500' => !$hecho])>
                                <i class="fas {{ $hecho ? 'fa-circle-check' : 'fa-circle' }} mr-0.5" aria-hidden="true"></i> {{ $paso }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- HU-033: validar o devolver el inventario inicial --}}
                    @if ($inicial && $empalme->estado !== 'cerrado')
                        <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm">
                            @if (!empty($contenido['inventario_validado']))
                                <p class="font-semibold text-emerald-700"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i> Inventario inicial validado.</p>
                            @elseif (empty($contenido['inventario_registrado']))
                                <p class="text-slate-600">
                                    @if (!empty($contenido['inventario_devuelto']))
                                        <span class="font-semibold text-amber-700">Pendiente de corrección:</span> {{ $contenido['inventario_devuelto']['observaciones'] }}
                                    @else
                                        El aprendiz entrante aún no registra el inventario inicial con la plantilla del área.
                                    @endif
                                </p>
                            @else
                                <p class="font-semibold text-slate-700">Inventario inicial registrado ({{ $empalme->inventarios->count() }} ítems)</p>
                                <ul class="mt-1 list-disc pl-5 text-slate-600">
                                    @foreach ($empalme->inventarios as $item)
                                        <li>{{ $item->cantidad }} × {{ $item->item }}{{ $item->estado ? " · {$item->estado}" : '' }}</li>
                                    @endforeach
                                </ul>
                                @unless ($soloConsulta)
                                    <div class="mt-3 flex flex-wrap items-start gap-3">
                                        <form method="POST" action="{{ route('talentohumano.fases.empalmes.inventario.validar', [$fase->id, $empalme->id]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-xs font-semibold text-white hover:bg-[#043A2F]">
                                                <i class="fas fa-check" aria-hidden="true"></i> Validar inventario
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('talentohumano.fases.empalmes.inventario.devolver', [$fase->id, $empalme->id]) }}" class="flex min-w-0 flex-1 flex-wrap items-start gap-2">
                                            @csrf
                                            <textarea name="observaciones" rows="1" placeholder="Observaciones para el aprendiz" aria-label="Observaciones"
                                                      @class(['min-w-0 flex-1 rounded-xl border-0 px-3 py-2 text-xs ring-1 focus:ring-2 focus:ring-[#064C3D]', 'ring-red-400' => $errors->has("observaciones.{$empalme->id}"), 'ring-slate-300' => !$errors->has("observaciones.{$empalme->id}")])></textarea>
                                            <button type="submit" class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">Devolver</button>
                                        </form>
                                    </div>
                                    @if ($empalme->visita_confirmada)
                                        <p class="mt-2 text-xs text-amber-700">Si lo devuelve, el aprendiz deberá confirmar de nuevo la visita, firmar el acta y diligenciar el Formulario de Satisfacción.</p>
                                    @endif
                                @endunless
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                    Aún no hay empalmes. Presione «Preparar empalmes» para que el sistema determine quién entrega y quién recibe cada cargo.
                </div>
            @endforelse
        </div>

        {{-- HU-031A: vinculación de los aprendices seleccionados --}}
        <h2 class="mb-3 mt-8 font-bold text-[#064C3D]">Vinculación de los aprendices seleccionados</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Aprendiz</th><th class="px-4 py-3">Cargo</th><th class="px-4 py-3">Acta</th><th class="px-4 py-3">Empalme</th><th class="px-4 py-3">Asignación</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($vinculacion as $fila)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $fila->aprendiz }}</td>
                            <td class="px-4 py-3">{{ $fila->cargo }} <span class="text-xs text-slate-400">· {{ $fila->origen }}</span></td>
                            <td class="px-4 py-3 text-emerald-700"><i class="fas fa-check" aria-hidden="true"></i> Publicada</td>
                            <td class="px-4 py-3">
                                @if ($fila->empalme_cerrado)
                                    <span class="text-emerald-700"><i class="fas fa-check" aria-hidden="true"></i> Cerrado con visto bueno</span>
                                @else
                                    <span class="text-amber-700">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1', 'bg-emerald-50 text-emerald-700 ring-emerald-200' => $fila->vinculado, 'bg-amber-50 text-amber-700 ring-amber-200' => !$fila->vinculado])>
                                    {{ $fila->vinculado ? 'Vinculado · ' : '' }}{{ $fila->estado }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-talentohumano::layouts.master>
