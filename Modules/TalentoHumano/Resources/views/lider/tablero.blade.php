@php
    $url = fn (string $clave) => \Modules\TalentoHumano\Http\Controllers\InstructorLiderController::url($clave);
    $estados = [
        'completa' => ['texto' => 'Completa', 'chip' => 'bg-emerald-100 text-emerald-800', 'icono' => 'bg-[#064C3D] text-[#34D399]'],
        'en_curso' => ['texto' => 'En curso', 'chip' => 'bg-amber-100 text-amber-800', 'icono' => 'bg-amber-50 text-amber-600'],
        'pendiente' => ['texto' => 'Pendiente', 'chip' => 'bg-slate-100 text-slate-500', 'icono' => 'bg-slate-100 text-slate-400'],
    ];
    $estadosFase = [
        'planeacion' => 'Planeación', 'seleccion' => 'Selección', 'empalme' => 'Empalme', 'en_curso' => 'En curso', 'cerrada' => 'Cerrada',
    ];
    $niveles = ['Gerente' => 'Gerentes', 'Gestor' => 'Gestores', 'Lider' => 'Líderes', 'Equipo' => 'Equipos'];
@endphp

<x-talentohumano::layouts.lider title="Tablero de la fase" activo="tablero">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Hola, {{ auth()->user()->person->first_name ?? auth()->user()->full_name }}</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Tablero de la fase</h1>
            <p class="mt-1 text-sm text-slate-600">El avance de cada etapa del proceso de Talento Humano y lo que está pendiente.</p>
        </div>
        @if ($fase)
            <div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fase actual</p>
                <p class="mt-0.5 flex items-center gap-2 font-bold text-[#064C3D]">
                    Fase {{ $fase->numero }}
                    <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-xs font-semibold">{{ $estadosFase[$fase->estado] ?? $fase->estado }}</span>
                </p>
                @if ($fase->fecha_inicio)
                    <p class="text-xs text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($fase->fecha_inicio)->format('d/m/Y') }}
                        @if ($fase->fecha_fin) – {{ \Illuminate\Support\Carbon::parse($fase->fecha_fin)->format('d/m/Y') }} @endif
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Alertas de condiciones mínimas (HU-011) --}}
    @if ($alertas)
        <div class="mt-6 space-y-2" role="alert">
            @foreach ($alertas as $alerta)
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">{{ $alerta['texto'] }}</span>
                    @if ($alerta['ruta'])
                        <a href="{{ $alerta['ruta'] }}" class="rounded-full bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600">{{ $alerta['accion'] }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Etapas del proceso --}}
    @if ($fase)
        <ol class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($etapas as $etapa)
                @php $estado = $estados[$etapa['estado']]; @endphp
                <li class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                    <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $estado['icono'] }}">
                        <i class="fas {{ $etapa['icono'] }}" aria-hidden="true"></i>
                        <span class="absolute -left-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-white text-[10px] font-bold text-slate-500 ring-1 ring-slate-200">{{ $loop->iteration }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $etapa['titulo'] }}</p>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estado['chip'] }}">{{ $estado['texto'] }}</span>
                        </div>
                        <p class="mt-0.5 text-lg font-extrabold text-[#064C3D]">{{ $etapa['valor'] }}</p>
                        <p class="text-xs text-slate-500">{{ $etapa['detalle'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <div class="relative isolate mt-6 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white sm:p-10">
            <div class="absolute inset-0 -z-10" style="background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 22px 22px" aria-hidden="true"></div>
            <i class="fas fa-seedling absolute -bottom-6 -right-2 -z-10 text-[9rem] text-white/5" aria-hidden="true"></i>
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
            </span>
            <h2 class="mt-5 text-xl font-extrabold sm:text-2xl">No hay una fase activa</h2>
            <p class="mt-2 max-w-xl text-emerald-50/85">
                Cuando el Gestor de Talento Humano registre las fichas de la nueva fase, aquí verás el avance de cada etapa:
                fichas, cargos activados, cronograma, convocatoria, postulados, prueba, entrevistas, selección, reubicaciones, empalmes y vinculación.
            </p>
        </div>
    @endif

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        {{-- Aprobaciones pendientes --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-[#064C3D]"><i class="fas fa-clipboard-check mr-2 text-emerald-600" aria-hidden="true"></i>Aprobaciones pendientes</h2>
                <a href="{{ $url('aprobaciones') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Ver todas</a>
            </div>
            @forelse ($aprobaciones as $aprobacion)
                <a href="{{ $url('aprobaciones') }}" class="mt-3 flex items-center gap-3 rounded-2xl bg-[#E8F3EF]/60 px-4 py-3 text-sm transition hover:bg-[#E8F3EF]">
                    <i class="fas {{ $aprobacion['icono'] }} text-[#064C3D]" aria-hidden="true"></i>
                    <span class="flex-1 font-medium text-slate-700">{{ $aprobacion['texto'] }}</span>
                    <span class="rounded-full bg-[#064C3D] px-2.5 py-0.5 text-xs font-bold text-white">{{ $aprobacion['cantidad'] }}</span>
                </a>
            @empty
                <div class="mt-4 flex items-center gap-3 rounded-2xl border border-dashed border-slate-200 px-4 py-5 text-sm text-slate-500">
                    <i class="fas fa-circle-check text-emerald-500" aria-hidden="true"></i> No tienes aprobaciones pendientes.
                </div>
            @endforelse
        </div>

        {{-- Configuración --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="font-bold text-[#064C3D]"><i class="fas fa-sliders mr-2 text-emerald-600" aria-hidden="true"></i>Configuración de FOLIUM</h2>

            <a href="{{ $url('catalogo') }}" class="group mt-4 block rounded-2xl bg-[#E8F3EF]/60 p-4 transition hover:bg-[#E8F3EF]">
                <div class="flex items-baseline justify-between">
                    <span class="text-sm font-semibold text-slate-700">Cargos activos en el catálogo</span>
                    <span class="text-2xl font-extrabold text-[#064C3D]">{{ $resumen['cargos_activos'] }}</span>
                </div>
                <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-white" aria-hidden="true">
                    @foreach ($niveles as $nivel => $etiqueta)
                        <span class="h-full {{ ['bg-[#043A2F]', 'bg-[#064C3D]', 'bg-emerald-500', 'bg-[#34D399]'][$loop->index] }}"
                              style="width: {{ $resumen['cargos_activos'] ? ($resumen['cargos_por_nivel'][$nivel] ?? 0) * 100 / $resumen['cargos_activos'] : 0 }}%"></span>
                    @endforeach
                </div>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    @foreach ($niveles as $nivel => $etiqueta)
                        <span><span class="mr-1 inline-block h-2 w-2 rounded-full {{ ['bg-[#043A2F]', 'bg-[#064C3D]', 'bg-emerald-500', 'bg-[#34D399]'][$loop->index] }}"></span>{{ $resumen['cargos_por_nivel'][$nivel] ?? 0 }} {{ $etiqueta }}</span>
                    @endforeach
                </div>
            </a>

            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                @foreach ([
                    ['Designaciones vigentes', $resumen['designaciones'], 'designaciones'],
                    ['Plantillas de acta vigentes', $resumen['plantillas'], 'plantillas'],
                    ['Asignaciones vigentes', $resumen['asignaciones_vigentes'], 'asignaciones'],
                    ['Asignaciones suspendidas', $resumen['asignaciones_suspendidas'], 'asignaciones'],
                ] as [$texto, $valor, $seccion])
                    <a href="{{ $url($seccion) }}" class="rounded-2xl p-3 ring-1 ring-slate-200 transition hover:ring-[#34D399]">
                        <dt class="text-xs text-slate-500">{{ $texto }}</dt>
                        <dd class="text-xl font-extrabold text-[#064C3D]">{{ $valor }}</dd>
                    </a>
                @endforeach
            </dl>
        </div>
    </div>
</x-talentohumano::layouts.lider>
