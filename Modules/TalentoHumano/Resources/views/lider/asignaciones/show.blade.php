@use('Modules\TalentoHumano\Entities\Asignacion')
@php
    $chipEstado = ['asignada' => 'bg-sky-100 text-sky-800', 'vigente' => 'bg-emerald-100 text-emerald-800', 'suspendida' => 'bg-amber-100 text-amber-800', 'finalizada' => 'bg-slate-100 text-slate-500'];
    $eventos = [
        'asignacion_creada' => ['Asignación creada', 'fa-plus', 'bg-[#E8F3EF] text-[#064C3D]'],
        'asignacion_suspendida' => ['Asignación suspendida', 'fa-pause', 'bg-amber-100 text-amber-700'],
        'asignacion_reactivada' => ['Asignación reactivada', 'fa-play', 'bg-emerald-100 text-emerald-700'],
        'rol_gestor_otorgado' => ['Se otorgó el rol Gestor de Talento Humano', 'fa-user-shield', 'bg-sky-100 text-sky-700'],
        'rol_gestor_retirado' => ['Se retiró el rol Gestor de Talento Humano', 'fa-user-slash', 'bg-slate-100 text-slate-600'],
    ];
    $iniciales = collect(explode(' ', $aprendiz->nombre))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<x-talentohumano::layouts.lider :title="$aprendiz->nombre" activo="asignaciones">
    <a href="{{ route('talentohumano.lider.asignaciones.index', ['fase' => $asignacion->fase_id]) }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Asignaciones
    </a>

    <div class="mt-4 flex items-center gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-[#064C3D] text-lg font-bold text-[#34D399]">{{ $iniciales }}</span>
        <div class="min-w-0">
            <h1 class="text-xl font-extrabold text-[#064C3D] sm:text-2xl">{{ $aprendiz->nombre }}</h1>
            <p class="text-sm text-slate-500">
                CC {{ number_format((float) $aprendiz->documento, 0, ',', '.') }}
                @if ($aprendiz->ficha) · Ficha {{ $aprendiz->ficha }} @endif
                @if ($aprendiz->programa) · {{ $aprendiz->programa }} @endif
            </p>
            @if ($aprendiz->correo) <p class="text-xs text-slate-400">{{ $aprendiz->correo }}</p> @endif
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]">Asignaciones</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($asignaciones as $item)
                    <li @class(['flex items-start justify-between gap-3 rounded-2xl p-3', 'bg-[#E8F3EF]/60 ring-1 ring-[#064C3D]/20' => $item->id === $asignacion->id, 'bg-slate-50' => $item->id !== $asignacion->id])>
                        <div class="min-w-0 text-sm">
                            <p class="font-semibold text-slate-800">{{ $item->cargo->nombre }} · {{ $item->fase->nombre() }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $item->fecha_inicio?->format('d/m/Y') }}@if ($item->fecha_fin) – {{ $item->fecha_fin->format('d/m/Y') }}@endif
                                · Origen: {{ mb_strtolower(Asignacion::ORIGENES[$item->origen]) }}
                                @if ($item->unidades->isNotEmpty()) · {{ $item->unidades->pluck('name')->implode(', ') }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chipEstado[$item->estado] }}">{{ Asignacion::ESTADOS[$item->estado] }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-slate-400"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Las asignaciones no se eliminan: solo se suspenden o se finalizan al cierre de la fase.</p>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]">Movimientos</h2>
            <ol class="mt-3 space-y-3">
                @forelse ($movimientos as $movimiento)
                    @php
                        [$texto, $icono, $color] = $eventos[$movimiento->event] ?? [$movimiento->event, 'fa-circle', 'bg-slate-100 text-slate-500'];
                        $nuevos = json_decode($movimiento->new_values, true) ?: [];
                        [$tipoOrigen, $fuenteOrigen] = \Modules\TalentoHumano\Support\Auditoria::origenDe($movimiento->tags);
                    @endphp
                    <li class="flex gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs {{ $color }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                        <div class="min-w-0 text-sm">
                            <p class="font-semibold text-slate-700">{{ $texto }}</p>
                            <p class="text-xs text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($movimiento->created_at)->format('d/m/Y g:i a') }} · {{ $movimiento->nickname ?? 'Sistema' }}
                                · {{ $tipoOrigen }}{{ $fuenteOrigen && $fuenteOrigen !== $tipoOrigen ? ': ' . $fuenteOrigen : '' }}
                            </p>
                            @if (!empty($nuevos['justificacion'])) <p class="mt-0.5 text-xs text-slate-600">Justificación: {{ $nuevos['justificacion'] }}</p> @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Sin movimientos registrados.</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-talentohumano::layouts.lider>
