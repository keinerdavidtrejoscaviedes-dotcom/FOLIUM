@php
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $fecha = fn ($valor) => \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y g:i a');
    $estadoSicefa = [
        'operativo' => ['Operativo', 'bg-emerald-100 text-emerald-800'],
        'sin_respuesta' => ['Sin respuesta', 'bg-red-100 text-red-700'],
        'sin_probar' => ['Sin probar', 'bg-slate-100 text-slate-600'],
        'sin_configurar' => ['Sin configurar', 'bg-slate-100 text-slate-600'],
    ][$sicefa['estado']];
@endphp

<x-talentohumano::layouts.lider title="Alertas y servicios" activo="alertas">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Alertas y servicios</h1>
            <p class="mt-1 text-sm text-slate-600">Lo que requiere tu atención como Administrador y el estado de los servicios de los que depende FOLIUM.</p>
        </div>
        @if ($sinLeer)
            <form method="POST" action="{{ route('talentohumano.lider.alertas.leer-todas') }}">
                @csrf @method('PATCH')
                <button class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF]"><i class="fas fa-check-double" aria-hidden="true"></i> Marcar todo como leído</button>
            </form>
        @endif
    </div>

    @if ($sicefa['estado'] === 'sin_respuesta')
        <div class="mt-4 flex flex-wrap items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
            <i class="fas fa-plug-circle-xmark mt-0.5 text-red-500" aria-hidden="true"></i>
            <div class="min-w-0 flex-1">
                <p class="font-bold">SICEFA no responde desde {{ $fecha($sicefa['desde']) }}</p>
                <p>Los usuarios no pueden iniciar sesión ni consultar fichas, aprendices o instructores. La información ya registrada en FOLIUM está a salvo.</p>
                <p class="mt-1 text-xs text-red-700">Último intento: {{ $fecha($sicefa['ultimo_intento']) }} · {{ $sicefa['fallos'] }} {{ $sicefa['fallos'] === 1 ? 'intento fallido' : 'intentos fallidos' }} · {{ $sicefa['error'] }}</p>
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-4 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <div class="space-y-4">
            {{-- Servicios externos (HU-015) --}}
            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Servicios</h2>
                <div class="mt-4 space-y-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-slate-800"><i class="fas fa-database mr-2 text-[#064C3D]" aria-hidden="true"></i>API de SICEFA</p>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $estadoSicefa[1] }}">{{ $estadoSicefa[0] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            @switch($sicefa['estado'])
                                @case('sin_configurar') La API de SICEFA aún no está configurada (variable SICEFA_API_URL). Mientras tanto, FOLIUM consulta los datos de aprendices e instructores directamente en la base de datos del ERP. @break
                                @case('sin_probar') Configurada en {{ $sicefa['url'] }}. Todavía no se ha probado la conexión. @break
                                @case('operativo') Respondió en {{ $sicefa['respuesta_ms'] }} ms · {{ $fecha($sicefa['ultimo_intento']) }} @break
                                @default Sin respuesta desde {{ $fecha($sicefa['desde']) }}
                            @endswitch
                        </p>
                        @if ($sicefa['estado'] !== 'sin_configurar')
                            <form method="POST" action="{{ route('talentohumano.lider.alertas.probar-sicefa') }}" class="mt-3">
                                @csrf
                                <button class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-[#064C3D] ring-1 ring-slate-200 hover:ring-[#064C3D]"><i class="fas fa-rotate mr-1" aria-hidden="true"></i> Probar conexión</button>
                            </form>
                        @endif
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-slate-800"><i class="fas fa-envelope mr-2 text-[#064C3D]" aria-hidden="true"></i>Correo electrónico</p>
                            @if ($noEntregados->isNotEmpty())
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-semibold text-red-700">{{ $noEntregados->count() }} sin entregar</span>
                            @elseif ($correo['solo_log'])
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800">Modo de prueba</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">Operativo</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($correo['solo_log'])
                                El correo está en modo «{{ $correo['mailer'] }}»: los mensajes se escriben en el log de Laravel y no se envían. Falta configurar la cuenta institucional (IE-007).
                            @else
                                Se envía desde {{ $correo['remitente'] }} · {{ \Modules\TalentoHumano\Support\Correo::REINTENTOS }} reintentos automáticos.
                            @endif
                        </p>
                    </div>
                </div>
            </section>

            {{-- Condiciones mínimas (HU-011) --}}
            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Condiciones del proceso</h2>
                <ul class="mt-4 space-y-2">
                    @foreach ($condiciones as $condicion)
                        <li @class(['flex items-start gap-3 rounded-2xl px-3 py-2.5 text-sm', 'bg-[#E8F3EF]/60 text-[#064C3D]' => $condicion['ok'], 'bg-amber-50 text-amber-900' => !$condicion['ok']])>
                            <i @class(['fas mt-0.5', 'fa-circle-check text-emerald-600' => $condicion['ok'], 'fa-triangle-exclamation text-amber-500' => !$condicion['ok']]) aria-hidden="true"></i>
                            <span class="min-w-0 flex-1">
                                {{ $condicion['ok'] ? $condicion['texto'] : $condicion['falla'] }}
                                @if (!$condicion['ok'] && $condicion['ruta'])
                                    <a href="{{ $condicion['ruta'] }}" class="mt-1 block text-xs font-semibold underline">{{ $condicion['accion'] }}</a>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <div class="space-y-4">
            {{-- Alertas --}}
            <section class="{{ $tarjeta }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-bold text-[#064C3D]">Alertas</h2>
                    <div class="flex gap-2" role="tablist" aria-label="Alertas">
                        @foreach (['todas' => 'Todas', 'sin-leer' => 'Sin leer'] as $clave => $texto)
                            <a href="{{ route('talentohumano.lider.alertas.index', ['vista' => $clave]) }}" role="tab" aria-selected="{{ $vista === $clave ? 'true' : 'false' }}"
                               class="rounded-full px-3 py-1 text-xs font-semibold {{ $vista === $clave ? 'bg-[#064C3D] text-white' : 'text-slate-600 ring-1 ring-slate-200 hover:ring-[#064C3D]' }}">
                                {{ $texto }} @if ($clave === 'sin-leer' && $sinLeer) <span class="ml-1 rounded-full bg-red-500 px-1.5 text-[10px] text-white">{{ $sinLeer }}</span> @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($alertas as $alerta)
                        @php [$icono, $color] = $alerta->estilo(); @endphp
                        <li class="flex gap-3 py-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $color }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">
                                        @unless ($alerta->leida_en) <span class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500 align-middle" aria-label="Sin leer"></span> @endunless
                                        {{ $alerta->titulo }}
                                    </p>
                                    <span class="text-xs text-slate-400">{{ $alerta->created_at->format('d/m/Y g:i a') }}</span>
                                </div>
                                @if ($alerta->texto) <p class="mt-0.5 text-sm text-slate-600">{{ $alerta->texto }}</p> @endif
                                <div class="mt-1.5 flex flex-wrap gap-3 text-xs font-semibold">
                                    @if ($alerta->enlace) <a href="{{ $alerta->enlace }}" class="text-emerald-700 hover:underline">Ver detalle</a> @endif
                                    @unless ($alerta->leida_en)
                                        <form method="POST" action="{{ route('talentohumano.lider.alertas.leer', $alerta) }}">@csrf @method('PATCH')<button class="text-slate-500 hover:text-[#064C3D] hover:underline">Marcar como leída</button></form>
                                    @endunless
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-8 text-center text-sm text-slate-500">
                            <i class="fas fa-bell-slash mb-2 block text-2xl text-slate-300" aria-hidden="true"></i>
                            {{ $vista === 'sin-leer' ? 'No tienes alertas sin leer.' : 'Todavía no hay alertas.' }}
                        </li>
                    @endforelse
                </ul>
                @if ($alertas->hasPages())
                    <div class="mt-2 flex justify-end gap-1 border-t border-slate-100 pt-3 text-sm">
                        <a href="{{ $alertas->previousPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => $alertas->onFirstPage(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => !$alertas->onFirstPage()])>Anterior</a>
                        <a href="{{ $alertas->nextPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => !$alertas->hasMorePages(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => $alertas->hasMorePages()])>Siguiente</a>
                    </div>
                @endif
            </section>

            {{-- Correos no entregados (HU-015, esc. 2 y 3) --}}
            <section class="{{ $tarjeta }}">
                <h2 class="font-bold text-[#064C3D]">Notificaciones no entregadas</h2>
                @if ($noEntregados->isEmpty())
                    <p class="mt-3 flex items-center gap-2 rounded-2xl bg-[#E8F3EF]/60 px-4 py-3 text-sm text-[#064C3D]">
                        <i class="fas fa-circle-check" aria-hidden="true"></i> No hay notificaciones pendientes de entrega
                    </p>
                @else
                    <p class="mt-1 text-sm text-slate-600">El sistema las reintentó {{ \Modules\TalentoHumano\Support\Correo::REINTENTOS }} veces. Puedes reintentar de nuevo.</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($noEntregados as $notificacion)
                            <li class="flex flex-wrap items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-slate-800">{{ $notificacion->asunto }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ implode(', ', $notificacion->destinatarios) }} · {{ $notificacion->created_at->format('d/m/Y g:i a') }} · {{ $notificacion->intentos }} intentos</p>
                                    <p class="text-xs text-red-700">{{ $notificacion->error }}</p>
                                </div>
                                <form method="POST" action="{{ route('talentohumano.lider.alertas.reintentar', $notificacion) }}">
                                    @csrf
                                    <button class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-[#064C3D] ring-1 ring-slate-200 hover:ring-[#064C3D]"><i class="fas fa-rotate-right mr-1" aria-hidden="true"></i>Reintentar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-talentohumano::layouts.lider>
