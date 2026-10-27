@use('Modules\TalentoHumano\Entities\Cargo')
@php
    $campo = 'w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $borde = fn (string $nombre) => $errors->has($nombre) ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]';
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $puedeAsignar = $fase && $aprendiz && $aprendiz->aprendiz_id && !$ocupada;
    $seleccionadas = array_map('strval', (array) old('unidades', []));
@endphp

<x-talentohumano::layouts.lider title="Asignación inicial" activo="asignaciones">
    <a href="{{ route('talentohumano.lider.asignaciones.index') }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Asignaciones
    </a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Asignación inicial</h1>
    <p class="mt-1 text-sm text-slate-600">Para quienes ya ocupan un cargo en la fase en curso, o para reemplazar a un Gestor de Talento Humano en una emergencia.</p>

    @if (!$fase)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-triangle-exclamation mr-1 text-amber-500" aria-hidden="true"></i>
            No hay una fase en curso. Registra primero la fase desde <a href="{{ route('talentohumano.lider.asignaciones.index') }}" class="font-semibold underline">Asignaciones</a>.
        </div>
    @else
        @if ($sinGestor)
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <i class="fas fa-triangle-exclamation mt-0.5 text-amber-500" aria-hidden="true"></i>
                <span><strong>No hay un Gestor de Talento Humano con asignación vigente.</strong> Puedes asignar a un aprendiz a ese cargo: al quedar vigente recibe el rol Gestor de Talento Humano.</span>
            </div>
        @endif

        <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-4">
                {{-- 1. Aprendiz --}}
                <section class="{{ $tarjeta }}">
                    <h2 class="font-bold text-[#064C3D]">1. Aprendiz</h2>
                    <form method="GET" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label for="buscar-documento" class="mb-1 block text-sm font-semibold text-slate-700">Documento del aprendiz <span class="text-red-500">*</span></label>
                            <input id="buscar-documento" name="documento" inputmode="numeric" value="{{ $documento }}" placeholder="Por ejemplo: 1075276540" class="{{ $campo }} {{ $borde('documento') }}">
                        </div>
                        <button class="rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100"><i class="fas fa-magnifying-glass mr-1" aria-hidden="true"></i> Buscar en SICEFA</button>
                    </form>
                    @error('documento') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror

                    @if ($documento !== '')
                        @if (!$aprendiz)
                            <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                <p class="font-semibold">No encontramos el documento {{ $documento }} en SICEFA</p>
                                <p class="text-xs">Verifica el número. Solo se pueden asignar aprendices registrados en SICEFA.</p>
                            </div>
                        @else
                            <div class="mt-4 flex items-center gap-3 rounded-2xl bg-[#E8F3EF]/60 p-4">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#064C3D] text-sm font-bold text-[#34D399]">{{ collect(explode(' ', $aprendiz->nombre))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                                <div class="min-w-0 text-sm">
                                    <p class="font-semibold text-slate-800">{{ $aprendiz->nombre }}</p>
                                    <p class="text-xs text-slate-500">CC {{ number_format((float) $aprendiz->documento, 0, ',', '.') }} @if ($aprendiz->correo) · {{ $aprendiz->correo }} @endif</p>
                                    @if ($aprendiz->ficha) <p class="text-xs text-slate-500">Ficha {{ $aprendiz->ficha }} · {{ $aprendiz->programa }}</p> @endif
                                </div>
                                <span class="ml-auto shrink-0 rounded-full bg-white px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">SICEFA</span>
                            </div>
                            @if (!$aprendiz->aprendiz_id)
                                <p class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Esta persona no está registrada como aprendiz en SICEFA, así que no puede ocupar un cargo.</p>
                            @elseif ($ocupada)
                                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                    <p class="font-semibold">Ya tiene una asignación en la {{ $fase->nombre() }}</p>
                                    <p class="text-xs">Ocupa el cargo <strong>{{ $ocupada->cargo->nombre }}</strong>. Un aprendiz solo puede tener una asignación por fase.</p>
                                </div>
                            @endif
                        @endif
                    @endif
                </section>

                {{-- 2 y 3. Cargo, fase y justificación --}}
                <form method="POST" action="{{ route('talentohumano.lider.asignaciones.store') }}" novalidate class="space-y-4"
                      data-titulo="¿Crear la asignación?" data-confirmar="La asignación quedará con origen «carga inicial» directamente en estado «vigente», sin acta de seleccionados ni empalme.">
                    @csrf
                    <input type="hidden" name="documento" value="{{ $documento }}">
                    <fieldset @disabled(!$puedeAsignar) class="space-y-4 disabled:opacity-50">
                        <section class="{{ $tarjeta }}">
                            <h2 class="font-bold text-[#064C3D]">2. Cargo y fase</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="mb-1 text-sm font-semibold text-slate-700">Fase</p>
                                    <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-semibold text-[#064C3D]">{{ $fase->nombre() }} · En curso</p>
                                </div>
                                <div>
                                    <label for="fecha_inicio" class="mb-1 block text-sm font-semibold text-slate-700">Fecha de inicio <span class="text-red-500">*</span></label>
                                    <input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $fase->fecha_inicio?->format('Y-m-d')) }}" class="{{ $campo }} {{ $borde('fecha_inicio') }}">
                                    @error('fecha_inicio') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="cargo_id" class="mb-1 block text-sm font-semibold text-slate-700">Cargo <span class="text-red-500">*</span></label>
                                    <select id="cargo_id" name="cargo_id" class="{{ $campo }} {{ $borde('cargo_id') }}">
                                        <option value="">Selecciona…</option>
                                        @foreach ($cargos->groupBy('nivel') as $nivel => $grupo)
                                            <optgroup label="{{ Cargo::NIVELES[$nivel] }}">
                                                @foreach ($grupo as $cargo)
                                                    @php $usados = $ocupados[$cargo->id] ?? 0; @endphp
                                                    <option value="{{ $cargo->id }}" @selected((string) old('cargo_id') === (string) $cargo->id)>
                                                        {{ $cargo->nombre }} · {{ $usados }} de {{ $cargo->numero_aprendices }} {{ $cargo->numero_aprendices === 1 ? 'cupo ocupado' : 'cupos ocupados' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('cargo_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @else <p class="mt-1 text-xs text-slate-500">Solo cargos activos del catálogo. Si es el Gestor de Talento Humano, el aprendiz recibe ese rol al quedar vigente.</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="unidades" class="mb-1 block text-sm font-semibold text-slate-700">Unidades productivas</label>
                                    <select id="unidades" name="unidades[]" multiple size="5" class="{{ $campo }} {{ $borde('unidades') }}">
                                        @foreach ($unidades as $id => $nombreUnidad)
                                            <option value="{{ $id }}" @selected(in_array((string) $id, $seleccionadas))>{{ $nombreUnidad }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">Opcional. Mantén presionada la tecla Ctrl para elegir varias.</p>
                                </div>
                            </div>
                        </section>

                        <section class="{{ $tarjeta }}">
                            <h2 class="font-bold text-[#064C3D]">3. Justificación</h2>
                            <label for="justificacion" class="mb-1 mt-4 block text-sm font-semibold text-slate-700">Motivo de la asignación inicial <span class="text-red-500">*</span></label>
                            <textarea id="justificacion" name="justificacion" rows="3" maxlength="2000" placeholder="Por ejemplo: puesta en marcha de FOLIUM; el aprendiz fue seleccionado en papel en la fase actual."
                                      class="{{ $campo }} {{ $borde('justificacion') }}">{{ old('justificacion') }}</textarea>
                            @error('justificacion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </section>

                        <div class="flex flex-wrap justify-end gap-2">
                            <a href="{{ route('talentohumano.lider.asignaciones.index') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Cancelar</a>
                            <button class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-check mr-1" aria-hidden="true"></i> Crear asignación</button>
                        </div>
                    </fieldset>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 text-sm">
                    <h2 class="font-bold text-[#064C3D]">¿Cuándo se usa?</h2>
                    <ul class="mt-2 space-y-2 text-slate-600">
                        <li><strong>Puesta en marcha:</strong> aprendices que ya ocupan cargos seleccionados en papel.</li>
                        <li><strong>Emergencia:</strong> no hay un Gestor de Talento Humano vigente.</li>
                    </ul>
                </div>
                <div class="rounded-3xl bg-[#064C3D] p-5 text-sm text-white">
                    <h2 class="font-bold">¿Muchos aprendices?</h2>
                    <p class="mt-1 text-emerald-50/85">Usa la carga masiva con la plantilla de Excel: es la forma prevista para el día de la implementación.</p>
                    <a href="{{ route('talentohumano.lider.asignaciones.carga-masiva') }}" class="mt-3 inline-flex items-center gap-2 rounded-full bg-[#34D399] px-4 py-2 text-xs font-semibold text-[#043A2F] hover:bg-emerald-300"><i class="fas fa-file-excel" aria-hidden="true"></i> Carga masiva</a>
                </div>
            </aside>
        </div>
    @endif
</x-talentohumano::layouts.lider>
