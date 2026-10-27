@use('Modules\TalentoHumano\Http\Controllers\FaseController')
@php
    $persona = fn ($p) => $p ? trim($p->first_name . ' ' . $p->first_last_name) : 'Sin registro';
    $chipFase = fn ($estado) => $estado === 'cerrada' ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-800';
    $tarjeta = 'flex items-start gap-3 rounded-2xl p-4 ring-1 ring-slate-200';
    $icono = fn (bool $listo) => 'grid h-10 w-10 shrink-0 place-items-center rounded-xl ' . ($listo ? 'bg-[#E8F3EF] text-[#064C3D]' : 'bg-slate-100 text-slate-400');
@endphp

<x-talentohumano::layouts.lider title="Supervisión · Actas e historial" activo="supervision">
    @include('talentohumano::lider.supervision._encabezado', ['actual' => 'actas'])

    @if ($fase)
        <section class="mt-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]">Actas de la {{ $fase->nombre_completo }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="{{ $tarjeta }}">
                    <span class="{{ $icono((bool) $cronograma) }}"><i class="fas fa-calendar-days" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-800">Cronograma de selección</p>
                        @if ($cronograma)
                            <p class="text-xs text-slate-500">Aprobado el {{ $cronograma->fecha_aprobacion?->format('d/m/Y') }}</p>
                            <a href="{{ route('talentohumano.lider.aprobaciones.cronograma', $cronograma) }}" class="mt-2 inline-block text-xs font-semibold text-emerald-700 hover:underline">Ver</a>
                        @else
                            <p class="text-xs text-slate-500">Sin aprobar</p>
                        @endif
                    </div>
                </div>
                <div class="{{ $tarjeta }}">
                    <span class="{{ $icono((bool) $actaSeleccionados) }}"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-800">Acta de seleccionados</p>
                        @if ($actaSeleccionados)
                            <p class="text-xs text-slate-500">Publicada el {{ $actaSeleccionados->fecha_publicacion?->format('d/m/Y') }}</p>
                            <button type="button" data-abrir="#modal-acta" class="mt-2 text-xs font-semibold text-emerald-700 hover:underline">Ver</button>
                        @else
                            <p class="text-xs text-slate-500">Pendiente{{ $porValidar ? " · {$porValidar} cargos por validar" : '' }}</p>
                        @endif
                    </div>
                </div>
                <div class="{{ $tarjeta }}">
                    <span class="{{ $icono((bool) $recibimiento?->total) }}"><i class="fas fa-file-signature" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-800">Actas de recibimiento</p>
                        @if ($recibimiento?->total)
                            <p class="text-xs text-slate-500">{{ (int) $recibimiento->firmadas }} firmadas de {{ $recibimiento->total }}</p>
                            <a href="{{ route('talentohumano.lider.supervision.empalmes', request()->only('fase')) }}" class="mt-2 inline-block text-xs font-semibold text-emerald-700 hover:underline">Ver empalmes</a>
                        @else
                            <p class="text-xs text-slate-500">Se generan en el empalme</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="mt-4 rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <h2 class="p-6 pb-0 font-bold text-[#064C3D]">Historial de fases</h2>
        @if ($historial->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-slate-500">Aún no hay fases registradas.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-4 py-3 font-semibold">Fase</th><th class="hidden px-4 py-3 font-semibold md:table-cell">Periodo</th><th class="px-4 py-3 text-center font-semibold">Cargos</th><th class="px-4 py-3 text-center font-semibold">Postulados</th><th class="hidden px-4 py-3 text-center font-semibold sm:table-cell">Reubicados</th><th class="hidden px-4 py-3 text-center font-semibold lg:table-cell">Empalmes</th><th class="px-4 py-3"><span class="sr-only">Consultar</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($historial as $fila)
                            <tr class="hover:bg-[#E8F3EF]/40">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-800">{{ $fila->fase->nombre_completo }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $chipFase($fila->fase->estado) }}">{{ FaseController::ESTADOS[$fila->fase->estado] ?? $fila->fase->estado }}</span>
                                    @if ($fila->cargaInicial && $fila->postulados === null) <p class="mt-1 text-xs text-slate-500">Carga inicial (puesta en marcha)</p> @endif
                                </td>
                                <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ $fila->fase->fecha_inicio?->format('d/m/Y') }} – {{ $fila->fase->fecha_fin?->format('d/m/Y') ?? 'sin fecha de fin' }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $fila->cargos ?: '—' }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $fila->postulados ?? '—' }}</td>
                                <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $fila->reubicados ?? '—' }}</td>
                                <td class="hidden px-4 py-3 text-center lg:table-cell">{{ $fila->empalmes ? "{$fila->cerrados} / {$fila->empalmes}" : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($fase?->id !== $fila->fase->id)
                                        <a href="{{ route('talentohumano.lider.supervision.index', ['fase' => $fila->fase->id]) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Consultar</a>
                                    @else
                                        <span class="text-xs text-slate-400">Consultando</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-500"><i class="fas fa-circle-info mr-1" aria-hidden="true"></i>Cada fase guarda aquí su cronograma, su acta de seleccionados y sus actas de recibimiento.</p>
    </section>

    {{-- HU-014, esc. 4: acta de seleccionados en solo lectura --}}
    @if ($actaSeleccionados)
        <dialog id="modal-acta" aria-labelledby="titulo-acta" class="m-auto w-[min(94vw,46rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50">
            <div class="max-h-[85vh] overflow-y-auto p-6 sm:p-8">
                <div class="flex items-center justify-between gap-3 border-b-2 border-[#064C3D] pb-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-[#E8F3EF] text-lg text-[#064C3D]"><i class="fas fa-seedling" aria-hidden="true"></i></span>
                        <div><p class="text-xs font-bold uppercase tracking-[.18em] text-slate-400">SENA Empresa · La Angostura</p><h2 id="titulo-acta" class="text-lg font-extrabold text-[#064C3D]">Acta de seleccionados · {{ $fase->nombre_completo }}</h2></div>
                    </div>
                    <button type="button" data-cerrar class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Cerrar"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </div>
                <p class="mt-4 text-sm text-slate-600">Publicada el {{ $actaSeleccionados->fecha_publicacion?->format('d/m/Y') }} por {{ $persona($actaSeleccionados->publicadaPor) }}.</p>
                <table class="mt-4 w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-3 py-2">Aprendiz</th><th class="px-3 py-2">Ficha</th><th class="px-3 py-2">Cargo</th><th class="px-3 py-2">Observación</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($seleccionados as $fila)
                            <tr>
                                <td class="px-3 py-2">{{ $fila->aprendiz }}</td>
                                <td class="px-3 py-2">{{ $fila->ficha ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $fila->cargo }}</td>
                                <td class="px-3 py-2">
                                    @if ($fila->observacion) <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">{{ $fila->observacion }}</span> @else <span class="text-slate-400">—</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">El acta no tiene aprendices registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </dialog>
    @endif
</x-talentohumano::layouts.lider>
