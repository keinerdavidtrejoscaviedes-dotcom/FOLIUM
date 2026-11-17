{{-- Bloque: Acta de Recibimiento y Firmas Digitales (RF-013, HU-057) --}}
@php
    $nombrePersona = function ($personId) use ($empalme) {
        $asignacion = $empalme->participantes->first(fn ($p) => $p->asignacion?->person_id == $personId)?->asignacion;

        return $asignacion?->person?->full_name ?? 'Aprendiz';
    };
    $totalFirmas = count($aprendicesFirmantes) + count($externos);
    $firmadas = $firmas->filter(fn ($f) => isset($aprendicesFirmantes[$f->person_id]))->count()
        + collect($externos)->filter(fn ($e) => $e['firma'])->count();
    $faltanAprendices = array_diff(array_keys($aprendicesFirmantes), $firmas->pluck('person_id')->all());
@endphp
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 border-t-4 border-[#064C3D]">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#064C3D]">Acta de Recibimiento</span>
            <p class="font-extrabold text-slate-800 text-sm mt-0.5">
                {{ $acta->contenido['codigo'] ?? ('ACT-REC-' . str_pad($empalme->id, 3, '0', STR_PAD_LEFT)) }}
            </p>
        </div>
        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $acta && $acta->estado === 'firmada' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
            {{ $acta && $acta->estado === 'firmada' ? 'Firmada' : 'Pendiente de firmas' }}
        </span>
    </div>

    <div class="mt-4 space-y-2 text-xs text-slate-600 leading-relaxed">
        <p>
            Constancia formal de entrega y recepción del cargo <strong>{{ $empalme->cargo->nombre ?? 'Asignado' }}</strong>,
            correspondiente a la estrategia formativa de <strong>SENA Empresa</strong>.
        </p>
        <p class="text-[11px] text-slate-400">
            Se verificaron {{ $empalme->inventarios->where('verificado', true)->count() }} de {{ $empalme->inventarios->count() }} elementos del inventario.
        </p>
    </div>

    {{-- Sub-bloque: Firmas Digitales. Cada firmante firma desde su propia cuenta (HU-057B, HU-057C) --}}
    <div class="mt-5 pt-4 border-t border-slate-100 space-y-3">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
            Firmas digitales ({{ $firmadas }}/{{ $totalFirmas }})
        </p>

        @foreach ($aprendicesFirmantes as $personId => $rol)
            @php $firma = $firmas->firstWhere('person_id', $personId); @endphp
            <div class="rounded-2xl p-3 border {{ $firma ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">{{ $rol }}</span>
                    @if ($firma)
                        <span class="text-emerald-700 font-bold flex items-center gap-1"><i class="fas fa-check" aria-hidden="true"></i> Firmado</span>
                    @else
                        <span class="text-slate-400">Pendiente</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $nombrePersona($personId) }}</p>
                @if ($firma)
                    <p class="text-[10px] text-slate-400 mt-1"><i class="fas fa-clock mr-0.5" aria-hidden="true"></i> {{ optional($firma->fecha_firma)->format('d/m/Y h:i a') }}</p>
                @endif
            </div>
        @endforeach

        @foreach ($externos as $clave => $externo)
            <div class="rounded-2xl p-3 border {{ $externo['firma'] ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' }}">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">{{ $externo['rol'] }}</span>
                    @if ($externo['firma'])
                        <span class="text-emerald-700 font-bold flex items-center gap-1"><i class="fas fa-check-circle" aria-hidden="true"></i> Firmado</span>
                    @else
                        <span class="text-amber-700 font-bold">Por firmar</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    {{ $externo['nombre'] ?? 'Sin designar: el Gestor de Talento Humano debe indicar quién firma' }}
                </p>
                @if ($externo['firma'])
                    <p class="text-[10px] text-slate-400 mt-1"><i class="fas fa-clock mr-0.5" aria-hidden="true"></i> {{ optional($externo['firma']->fecha_firma)->format('d/m/Y h:i a') }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Firma de quien consulta: solo si es el instructor del área o el jefe inmediato designado --}}
    @if ($miRolExterno)
        <div class="mt-6">
            @if ($externos[$miRolExterno]['firma'])
                <div class="rounded-2xl bg-[#E8F3EF] p-3 text-center text-xs font-bold text-[#064C3D] flex items-center justify-center gap-2">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <span>Ya firmaste como {{ $externos[$miRolExterno]['rol'] }}</span>
                </div>
            @elseif ($faltanAprendices)
                <p class="rounded-2xl bg-slate-50 p-3 text-center text-xs text-slate-500">Podrás firmar como {{ $externos[$miRolExterno]['rol'] }} cuando firmen los aprendices del empalme.</p>
            @else
                <form action="{{ route('talentohumano.empalmes.firmar', $empalme->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-full bg-[#064C3D] hover:bg-[#043A2F] text-white px-4 py-3 text-xs font-extrabold shadow-md shadow-[#064C3D]/20 transition flex items-center justify-center gap-2">
                        <i class="fas fa-signature text-emerald-300" aria-hidden="true"></i>
                        <span>Firmar acta como {{ $externos[$miRolExterno]['rol'] }}</span>
                    </button>
                </form>
            @endif
        </div>
    @elseif ($externos)
        <p class="mt-6 rounded-2xl bg-slate-50 p-3 text-center text-xs text-slate-500">
            El instructor del área y el jefe inmediato firman el acta desde su propia cuenta.
        </p>
    @endif
</section>
