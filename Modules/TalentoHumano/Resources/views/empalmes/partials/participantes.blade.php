{{-- Bloque: Participantes del Empalme (Saliente, Receptor y Acompañamiento de Instructor) --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <h2 class="font-bold text-[#064C3D] text-base mb-4 flex items-center justify-between">
        <span class="flex items-center gap-2">
            <i class="fas fa-users text-emerald-600"></i>
            <span>Participantes del proceso</span>
        </span>
        @if ($empalme->visto_bueno_por)
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 flex items-center gap-1">
                <i class="fas fa-check-circle"></i> Visto bueno de Instructor otorgado
            </span>
        @endif
    </h2>

    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Aprendiz Saliente --}}
        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                    Saliente (Fase 35)
                </span>
                @php
                    $salienteFirma = $firmas->firstWhere('rol_firmante', 'Aprendiz Saliente');
                @endphp
                @if ($salienteFirma)
                    <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                        <i class="fas fa-signature"></i> Firmó digitalmente
                    </span>
                @else
                    <span class="text-[11px] text-amber-600 font-medium">Pendiente de firma</span>
                @endif
            </div>
            <p class="font-bold text-slate-800 text-sm">
                {{ $saliente->asignacion->person->full_name ?? 'Pendiente de registrar' }}
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
                Doc: {{ $saliente->asignacion->person->document_number ?? '---' }}
            </p>
        </div>

        {{-- Aprendiz Receptor --}}
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="rounded-full bg-[#064C3D] px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider">
                    Receptor (Fase 36)
                </span>
                @php
                    $receptorFirma = $firmas->firstWhere('rol_firmante', 'Aprendiz Receptor');
                @endphp
                @if ($receptorFirma)
                    <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                        <i class="fas fa-signature"></i> Firmó digitalmente
                    </span>
                @else
                    <span class="text-[11px] text-amber-600 font-medium">Pendiente de firma</span>
                @endif
            </div>
            <p class="font-bold text-slate-800 text-sm">
                {{ $receptor->asignacion->person->full_name ?? 'Pendiente de registrar' }}
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
                Doc: {{ $receptor->asignacion->person->document_number ?? '---' }}
            </p>
        </div>
    </div>

    {{-- Acciones de Acompañamiento del Instructor --}}
    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div class="text-xs text-slate-500">
            <i class="fas fa-location-dot text-slate-400 mr-1"></i>
            Visita al puesto de trabajo:
            <strong>{{ $empalme->visita_confirmada ? 'Realizada y confirmada' : 'Pendiente de visita de campo' }}</strong>
        </div>

        @if (!$empalme->visto_bueno_por)
            <form action="{{ route('talentohumano.empalmes.visto-bueno', $empalme->id) }}" method="POST">
                @csrf
                <button type="submit"
                        class="rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-800 px-4 py-1.5 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas fa-check"></i> Otorgar Visto Bueno del Instructor
                </button>
            </form>
        @endif
    </div>
</section>
