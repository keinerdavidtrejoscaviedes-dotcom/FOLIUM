@use('Modules\TalentoHumano\Entities\Asignacion')
@use('Modules\TalentoHumano\Entities\Cargo')
@php
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $cerrada = $fase?->estado === 'cerrada';
    $campo = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $chipEstado = ['asignada' => 'bg-sky-100 text-sky-800', 'vigente' => 'bg-emerald-100 text-emerald-800', 'suspendida' => 'bg-amber-100 text-amber-800', 'finalizada' => 'bg-slate-100 text-slate-500'];
    $estadosFase = ['planeacion' => 'Planeación', 'seleccion' => 'Selección', 'empalme' => 'Empalme', 'en_curso' => 'En curso', 'cerrada' => 'Cerrada'];
    $fechas = fn ($f) => $f->fecha_inicio ? ' (' . \Illuminate\Support\Carbon::parse($f->fecha_inicio)->format('d/m/Y') . ($f->fecha_fin ? ' – ' . \Illuminate\Support\Carbon::parse($f->fecha_fin)->format('d/m/Y') : '') . ')' : '';
    $nombre = fn ($p) => trim($p->first_name . ' ' . $p->first_last_name . ' ' . $p->second_last_name);
    $documento = fn ($p) => 'CC ' . number_format((float) $p->document_number, 0, ',', '.');
@endphp

<x-talentohumano::layouts.lider title="Asignaciones" activo="asignaciones">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Asignaciones</h1>
            <p class="mt-1 text-sm text-slate-600">Quién ocupa cada cargo en cada fase, con su estado y su origen.</p>
        </div>
        @if ($esAdministrador && $fase && !$cerrada)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('talentohumano.lider.asignaciones.create') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]">
                    <i class="fas fa-user-plus" aria-hidden="true"></i> Asignación inicial
                </a>
                <a href="{{ route('talentohumano.lider.asignaciones.carga-masiva') }}" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF]">
                    <i class="fas fa-file-excel" aria-hidden="true"></i> Carga masiva
                </a>
            </div>
        @endif
    </div>

    @if (!$fase)
        {{-- Puesta en marcha: todavía no hay fases --}}
        <div class="relative isolate mt-6 overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white sm:p-10">
            <div class="absolute inset-0 -z-10" style="background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 22px 22px" aria-hidden="true"></div>
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20"><i class="fas fa-flag-checkered" aria-hidden="true"></i></span>
            <h2 class="mt-5 text-xl font-extrabold sm:text-2xl">Puesta en marcha de FOLIUM</h2>
            <p class="mt-2 max-w-2xl text-emerald-50/85">
                Aún no hay fases registradas. Registra la fase que ya está en curso en SENA Empresa; después podrás cargar las asignaciones
                de los aprendices que ya ocupan cargos, una por una o desde Excel.
            </p>
            @if ($esAdministrador)
                <button type="button" data-abrir="#modal-fase" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#34D399] px-5 py-2.5 text-sm font-semibold text-[#043A2F] hover:bg-emerald-300">
                    <i class="fas fa-plus" aria-hidden="true"></i> Registrar la fase en curso
                </button>
            @endif
        </div>
    @else
        {{-- Fase --}}
        <form method="GET" class="mt-6 flex flex-wrap items-center gap-3">
            <label for="fase" class="text-sm font-semibold text-slate-700">Fase</label>
            <select id="fase" name="fase" onchange="this.form.submit()" class="{{ $campo }} max-w-full font-semibold text-[#064C3D]">
                @foreach ($fases as $f)
                    <option value="{{ $f->id }}" @selected($f->id === $fase->id)>{{ $f->nombre() }} · {{ $estadosFase[$f->estado] }}{{ $fechas($f) }}</option>
                @endforeach
            </select>
            @if ($cerrada)
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Fase cerrada · solo consulta</span>
            @endif
            <noscript><button class="rounded-xl bg-[#064C3D] px-3 py-2 text-sm font-semibold text-white">Ver</button></noscript>
        </form>

        {{-- Resumen por estado --}}
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (['vigente' => ['Vigentes', 'text-[#064C3D]'], 'asignada' => ['Asignadas (en empalme)', 'text-[#064C3D]'], 'suspendida' => ['Suspendidas', 'text-amber-600'], 'finalizada' => ['Finalizadas', 'text-slate-500']] as $clave => [$texto, $color])
                <a href="{{ request()->fullUrlWithQuery(['estado' => request('estado') === $clave ? null : $clave, 'page' => null]) }}"
                   @class(['rounded-2xl bg-white p-4 shadow-sm ring-1 transition hover:ring-[#34D399]', 'ring-2 ring-[#064C3D]' => request('estado') === $clave, 'ring-slate-200/70' => request('estado') !== $clave])>
                    <p class="text-xs text-slate-500">{{ $texto }}</p><p class="text-2xl font-extrabold {{ $color }}">{{ $conteo[$clave] ?? 0 }}</p>
                </a>
            @endforeach
        </div>

        {{-- Filtros --}}
        <form method="GET" class="mt-4 grid gap-3 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_10rem_11rem_11rem_auto]">
            <input type="hidden" name="fase" value="{{ $fase->id }}">
            <label class="relative"><span class="sr-only">Buscar</span>
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Aprendiz, documento o cargo…" class="{{ $campo }} w-full pl-8">
            </label>
            <select name="estado" aria-label="Estado" class="{{ $campo }}">
                <option value="">Todos los estados</option>
                @foreach (Asignacion::ESTADOS as $valor => $texto) <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $texto }}</option> @endforeach
            </select>
            <select name="origen" aria-label="Origen" class="{{ $campo }}">
                <option value="">Todos los orígenes</option>
                @foreach (Asignacion::ORIGENES as $valor => $texto) <option value="{{ $valor }}" @selected(request('origen') === $valor)>{{ $texto }}</option> @endforeach
            </select>
            <select name="area" aria-label="Área" class="{{ $campo }}">
                <option value="">Todas las áreas</option>
                @foreach ($areas as $id => $texto) <option value="{{ $id }}" @selected((string) request('area') === (string) $id)>{{ $texto }}</option> @endforeach
            </select>
            <div class="flex gap-2">
                <button class="rounded-xl bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Filtrar</button>
                @if (request()->hasAny(['q', 'estado', 'origen', 'area']))
                    <a href="{{ route('talentohumano.lider.asignaciones.index', ['fase' => $fase->id]) }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Limpiar</a>
                @endif
            </div>
        </form>

        {{-- Listado --}}
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            @if ($asignaciones->isEmpty())
                <div class="px-6 py-14 text-center text-sm text-slate-500">
                    <i class="fas fa-id-card-clip mb-2 block text-3xl text-slate-300" aria-hidden="true"></i>
                    @if (request()->hasAny(['q', 'estado', 'origen', 'area']))
                        No hay asignaciones que coincidan con la búsqueda.
                    @else
                        No hay asignaciones registradas en esta fase.
                        @if ($esAdministrador && !$cerrada)
                            <a href="{{ route('talentohumano.lider.asignaciones.create') }}" class="mt-3 block font-semibold text-emerald-700 hover:underline">Crear una asignación inicial</a>
                        @endif
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Aprendiz</th>
                                <th class="px-4 py-3 font-semibold">Cargo</th>
                                <th class="hidden px-4 py-3 font-semibold xl:table-cell">Unidad productiva</th>
                                <th class="hidden px-4 py-3 font-semibold md:table-cell">Inicio</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="hidden px-4 py-3 font-semibold lg:table-cell">Origen</th>
                                <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($asignaciones as $asignacion)
                                @php $persona = $nombre($asignacion->persona); @endphp
                                <tr class="transition hover:bg-[#E8F3EF]/40">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('talentohumano.lider.asignaciones.show', $asignacion) }}" class="font-semibold text-slate-800 hover:text-[#064C3D] hover:underline">{{ $persona }}</a>
                                        <p class="text-xs text-slate-500">{{ $documento($asignacion->persona) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-700">{{ $asignacion->cargo->nombre }}</p>
                                        <p class="text-xs text-slate-500">{{ Cargo::NIVELES[$asignacion->cargo->nivel] }} · {{ $asignacion->cargo->area?->nombre }}</p>
                                    </td>
                                    <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">{{ $asignacion->unidades->pluck('name')->implode(', ') ?: '—' }}</td>
                                    <td class="hidden whitespace-nowrap px-4 py-3 text-slate-600 md:table-cell">{{ $asignacion->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chipEstado[$asignacion->estado] }}">{{ Asignacion::ESTADOS[$asignacion->estado] }}</span></td>
                                    <td class="hidden px-4 py-3 text-xs text-slate-500 lg:table-cell">{{ Asignacion::ORIGENES[$asignacion->origen] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('talentohumano.lider.asignaciones.show', $asignacion) }}" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-[#064C3D]" title="Ver detalle" aria-label="Ver el detalle de {{ $persona }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                            @if ($asignacion->estado === 'vigente')
                                                <button type="button" data-abrir="#modal-suspender" data-id="{{ $asignacion->id }}" data-nombre="{{ $persona }} · {{ $documento($asignacion->persona) }}"
                                                        data-cargo="{{ $asignacion->cargo->nombre }} · {{ $fase->nombre() }} · Vigente desde {{ $asignacion->fecha_inicio?->format('d/m/Y') }}"                                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-600" title="Suspender" aria-label="Suspender la asignación de {{ $persona }}"><i class="fas fa-pause" aria-hidden="true"></i></button>
                                            @elseif ($asignacion->estado === 'suspendida')
                                                <button type="button" data-abrir="#modal-reactivar" data-id="{{ $asignacion->id }}" data-nombre="{{ $persona }} · {{ $documento($asignacion->persona) }}"
                                                        data-cargo="{{ $asignacion->cargo->nombre }} · {{ $fase->nombre() }}"
                                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-700" title="Reactivar" aria-label="Reactivar la asignación de {{ $persona }}"><i class="fas fa-play" aria-hidden="true"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span>{{ $asignaciones->firstItem() }}–{{ $asignaciones->lastItem() }} de {{ $asignaciones->total() }} asignaciones</span>
                    @if ($asignaciones->hasPages())
                        <div class="flex gap-1">
                            <a href="{{ $asignaciones->previousPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => $asignaciones->onFirstPage(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => !$asignaciones->onFirstPage()])><i class="fas fa-chevron-left mr-1" aria-hidden="true"></i>Anterior</a>
                            <a href="{{ $asignaciones->nextPageUrl() ?? '#' }}" @class(['rounded-lg px-3 py-1.5 font-semibold', 'pointer-events-none text-slate-300' => !$asignaciones->hasMorePages(), 'text-[#064C3D] hover:bg-[#E8F3EF]' => $asignaciones->hasMorePages()])>Siguiente<i class="fas fa-chevron-right ml-1" aria-hidden="true"></i></a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <p class="mt-4 flex items-start gap-2 text-xs text-slate-500">
            <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i>
            Los demás módulos de FOLIUM consultan las asignaciones vigentes (persona, cargo, fase y estado) en modo de solo lectura. Ninguna asignación se elimina: al cierre de la fase pasa a «finalizada».
        </p>
    @endif

    {{-- Suspender (HU-012) --}}
    <dialog id="modal-suspender" aria-labelledby="titulo-suspender" class="m-auto w-[min(94vw,32rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50"
            @if ($errors->suspender->any()) data-abrir-al-cargar data-id="{{ old('asignacion_id') }}" @endif>
        <form method="POST" data-accion="{{ route('talentohumano.lider.asignaciones.suspender', '__ID__') }}" class="p-6" novalidate>
            @csrf @method('PATCH')
            <input type="hidden" name="asignacion_id" data-campo-id>
            <h2 id="titulo-suspender" class="text-lg font-bold text-slate-900">Suspender asignación</h2>
            <div class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm">
                <p class="font-semibold text-slate-800" data-campo-nombre></p>
                <p class="text-xs text-slate-500" data-campo-cargo></p>
            </div>
            <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                <li><i class="fas fa-circle-minus mr-2 text-amber-500" aria-hidden="true"></i>Pierde de inmediato las funciones del cargo.</li>
                <li><i class="fas fa-user-shield mr-2 text-amber-500" aria-hidden="true"></i>Si es Gestor de Talento Humano, se le retira ese rol.</li>
                <li><i class="fas fa-envelope mr-2 text-amber-500" aria-hidden="true"></i>Se avisa por correo al otro responsable (Administrador o Instructor Líder).</li>
            </ul>
            <label for="suspender-justificacion" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Justificación <span class="text-red-500">*</span></label>
            <textarea id="suspender-justificacion" name="justificacion" rows="3" maxlength="2000" class="{{ $campo }} w-full {{ $errors->suspender->has('justificacion') ? 'border-red-400' : '' }}">{{ $errors->suspender->any() ? old('justificacion') : '' }}</textarea>
            @if ($errors->suspender->has('justificacion')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $errors->suspender->first('justificacion') }}</p> @endif
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                <button class="rounded-full bg-amber-500 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-600">Suspender</button>
            </div>
        </form>
    </dialog>

    {{-- Reactivar (HU-012) --}}
    <dialog id="modal-reactivar" aria-labelledby="titulo-reactivar" class="m-auto w-[min(94vw,32rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50"
            @if ($errors->reactivar->any()) data-abrir-al-cargar data-id="{{ old('asignacion_id') }}" @endif>
        <form method="POST" data-accion="{{ route('talentohumano.lider.asignaciones.reactivar', '__ID__') }}" class="p-6" novalidate>
            @csrf @method('PATCH')
            <input type="hidden" name="asignacion_id" data-campo-id>
            <h2 id="titulo-reactivar" class="text-lg font-bold text-slate-900">Reactivar asignación</h2>
            <div class="mt-4 rounded-2xl bg-[#E8F3EF]/60 px-4 py-3 text-sm">
                <p class="font-semibold text-slate-800" data-campo-nombre></p>
                <p class="text-xs text-slate-500" data-campo-cargo></p>
            </div>
            <p class="mt-4 text-sm text-slate-600">El aprendiz recupera las funciones del cargo y se avisa por correo al otro responsable.</p>
            <label for="reactivar-justificacion" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Justificación <span class="text-red-500">*</span></label>
            <textarea id="reactivar-justificacion" name="justificacion" rows="3" maxlength="2000" class="{{ $campo }} w-full {{ $errors->reactivar->has('justificacion') ? 'border-red-400' : '' }}">{{ $errors->reactivar->any() ? old('justificacion') : '' }}</textarea>
            @if ($errors->reactivar->has('justificacion')) <p class="mt-1 text-xs font-semibold text-red-600">{{ $errors->reactivar->first('justificacion') }}</p> @endif
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                <button class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Reactivar</button>
            </div>
        </form>
    </dialog>

    {{-- Registrar la fase en curso (puesta en marcha) --}}
    @if ($esAdministrador && !$fase)
        <dialog id="modal-fase" aria-labelledby="titulo-fase" class="m-auto w-[min(94vw,30rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50" @if ($errors->fase->any()) data-abrir-al-cargar @endif>
            <form method="POST" action="{{ route('talentohumano.lider.asignaciones.fase') }}" class="p-6" novalidate>
                @csrf
                <h2 id="titulo-fase" class="text-lg font-bold text-slate-900">Registrar la fase en curso</h2>
                <p class="text-sm text-slate-500">La fase que SENA Empresa tiene en marcha cuando empieza a usar FOLIUM.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach (['numero' => ['Número', 'number'], 'fecha_inicio' => ['Inicio', 'date'], 'fecha_fin' => ['Fin', 'date']] as $nombreCampo => [$etiqueta, $tipo])
                        <div>
                            <label for="fase-{{ $nombreCampo }}" class="mb-1 block text-sm font-semibold text-slate-700">{{ $etiqueta }} @if ($nombreCampo !== 'fecha_fin') <span class="text-red-500">*</span> @endif</label>
                            <input id="fase-{{ $nombreCampo }}" name="{{ $nombreCampo }}" type="{{ $tipo }}" value="{{ old($nombreCampo) }}" @if ($tipo === 'number') min="1" @endif
                                   class="{{ $campo }} w-full {{ $errors->fase->has($nombreCampo) ? 'border-red-400' : '' }}">
                            @if ($errors->fase->has($nombreCampo)) <p class="mt-1 text-xs font-semibold text-red-600">{{ $errors->fase->first($nombreCampo) }}</p> @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-cerrar class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button class="rounded-full bg-[#064C3D] px-5 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Registrar fase</button>
                </div>
            </form>
        </dialog>
    @endif
</x-talentohumano::layouts.lider>
