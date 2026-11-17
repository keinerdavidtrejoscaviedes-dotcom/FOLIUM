{{-- Actas de recibimiento por firmar como instructor del área o jefe inmediato (HU-057C) --}}
@if ($actas->isEmpty())
    <div class="rounded-3xl bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/70">
        <i class="fas fa-file-signature mb-2 text-2xl text-slate-300" aria-hidden="true"></i>
        <p>No tiene actas de recibimiento pendientes de su firma.</p>
    </div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($actas as $item)
            @php $empalme = $item->empalme; @endphp
            <article class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#064C3D]">{{ $empalme->acta?->contenido['codigo'] ?? 'Acta de recibimiento' }}</p>
                        <h2 class="text-lg font-extrabold text-slate-900">{{ $empalme->cargo?->nombre }}</h2>
                        <p class="text-xs text-slate-500">{{ $empalme->fase?->nombre() }} · Firma como <strong>{{ $item->rol }}</strong></p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $item->firmada ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $item->firmada ? 'Firmada por usted' : 'Pendiente de su firma' }}
                    </span>
                </div>

                <ul class="mt-4 space-y-1 text-sm">
                    @foreach ($item->aprendices as $aprendiz)
                        <li class="flex items-center justify-between gap-2">
                            <span class="text-slate-700">{{ $aprendiz['rol'] }}: {{ $aprendiz['nombre'] }}</span>
                            <span class="text-xs {{ $aprendiz['firmo'] ? 'text-emerald-700' : 'text-slate-400' }}">{{ $aprendiz['firmo'] ? 'Firmó' : 'Pendiente' }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-slate-500">Inventario verificado: {{ $item->inventario[0] }} de {{ $item->inventario[1] }} elementos.</p>

                <div class="mt-5">
                    @if ($item->firmada)
                        <p class="rounded-2xl bg-[#E8F3EF] p-3 text-center text-xs font-bold text-[#064C3D]"><i class="fas fa-lock mr-1" aria-hidden="true"></i> Ya firmó esta acta</p>
                    @elseif ($item->faltanAprendices)
                        <p class="rounded-2xl bg-slate-50 p-3 text-center text-xs text-slate-500">Podrá firmar cuando firmen los aprendices del empalme ({{ $item->faltanAprendices }} pendientes).</p>
                    @else
                        <form method="POST" action="{{ route('talentohumano.actas-firma.firmar', $empalme->id) }}"
                              onsubmit="return confirm(@js('¿Firmar el acta de recibimiento del cargo ' . $empalme->cargo?->nombre . ' como ' . $item->rol . '?'))">
                            @csrf
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-full bg-[#064C3D] px-4 py-3 text-sm font-bold text-white hover:bg-[#043A2F]">
                                <i class="fas fa-signature text-emerald-300" aria-hidden="true"></i> Firmar acta como {{ $item->rol }}
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
