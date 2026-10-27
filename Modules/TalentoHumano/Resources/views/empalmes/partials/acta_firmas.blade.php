{{-- Bloque: Acta de Recibimiento y Firmas Digitales (RF-013) --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 border-t-4 border-[#064C3D]">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#064C3D]">Acta de Recibimiento</span>
            <p class="font-extrabold text-slate-800 text-sm mt-0.5">
                {{ $acta->contenido['codigo'] ?? ('ACT-REC-F36-' . str_pad($empalme->id, 3, '0', STR_PAD_LEFT)) }}
            </p>
        </div>
        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $acta && $acta->estado === 'firmada' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
            {{ $acta && $acta->estado === 'firmada' ? 'Firmada' : 'Borrador' }}
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

    {{-- Sub-bloque: Firmas Digitales (RF-013) --}}
    <div class="mt-5 pt-4 border-t border-slate-100 space-y-3">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
            Firmas Digitales ({{ $firmas->count() }}/3)
        </p>

        {{-- 1. Firma Saliente --}}
        @php
            $fSal = $firmas->firstWhere('rol_firmante', 'Aprendiz Saliente');
        @endphp
        <div class="rounded-2xl p-3 border {{ $fSal ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700">Aprendiz Saliente</span>
                @if ($fSal)
                    <span class="text-emerald-700 font-bold flex items-center gap-1">
                        <i class="fas fa-check"></i> Firmado
                    </span>
                @else
                    <span class="text-slate-400">Pendiente</span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">
                {{ $saliente->asignacion->person->full_name ?? 'Aprendiz' }}
            </p>
            @if ($fSal)
                <p class="text-[10px] text-slate-400 mt-1">
                    <i class="fas fa-clock mr-0.5"></i> {{ optional($fSal->fecha_firma)->format('d/m/Y h:i a') }}
                </p>
            @endif
        </div>

        {{-- 2. Firma Receptor --}}
        @php
            $fRec = $firmas->firstWhere('rol_firmante', 'Aprendiz Receptor');
        @endphp
        <div class="rounded-2xl p-3 border {{ $fRec ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700">Aprendiz Receptor</span>
                @if ($fRec)
                    <span class="text-emerald-700 font-bold flex items-center gap-1">
                        <i class="fas fa-check"></i> Firmado
                    </span>
                @else
                    <span class="text-slate-400">Pendiente</span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">
                {{ $receptor->asignacion->person->full_name ?? 'Aprendiz' }}
            </p>
            @if ($fRec)
                <p class="text-[10px] text-slate-400 mt-1">
                    <i class="fas fa-clock mr-0.5"></i> {{ optional($fRec->fecha_firma)->format('d/m/Y h:i a') }}
                </p>
            @endif
        </div>

        {{-- 3. Firma Instructor de Talento Humano --}}
        @php
            $fInst = $firmas->firstWhere('rol_firmante', 'Instructor de Talento Humano');
        @endphp
        <div class="rounded-2xl p-3 border {{ $fInst ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' }}">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700">Instructor de TH</span>
                @if ($fInst)
                    <span class="text-emerald-700 font-bold flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> Firmado
                    </span>
                @else
                    <span class="text-amber-700 font-bold">Por firmar</span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">
                {{ $fInst ? ($fInst->person->full_name ?? 'Instructor') : (auth()->user()->name ?? 'Instructor') }}
            </p>
            @if ($fInst)
                <p class="text-[10px] text-slate-400 mt-1">
                    <i class="fas fa-clock mr-0.5"></i> {{ optional($fInst->fecha_firma)->format('d/m/Y h:i a') }}
                </p>
            @endif
        </div>
    </div>

    {{-- Botón de Acción para Firmar como Instructor --}}
    <div class="mt-6">
        @if ($yaFirmoInstructor || ($fInst ?? false))
            <div class="rounded-2xl bg-[#E8F3EF] p-3 text-center text-xs font-bold text-[#064C3D] flex items-center justify-center gap-2">
                <i class="fas fa-lock"></i>
                <span>Acta firmada y avalada por ti</span>
            </div>
        @else
            <form action="{{ route('talentohumano.empalmes.firmar', $empalme->id) }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full rounded-full bg-[#064C3D] hover:bg-[#043A2F] text-white px-4 py-3 text-xs font-extrabold shadow-md shadow-[#064C3D]/20 transition flex items-center justify-center gap-2">
                    <i class="fas fa-signature text-emerald-300"></i>
                    <span>Firmar acta como Instructor de TH</span>
                </button>
            </form>
        @endif
    </div>
</section>
