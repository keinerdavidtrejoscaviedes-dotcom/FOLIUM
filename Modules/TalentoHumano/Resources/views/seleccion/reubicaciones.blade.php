<x-talentohumano::layouts.master title="Reubicaciones · Comité">
    @php
        $campo = 'w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
        $borde = fn (string $nombre) => $errors->has($nombre) ? 'border-red-400' : 'border-slate-200 focus:border-[#064C3D]';
        $opcion = 'flex items-center gap-3 rounded-2xl p-3.5 ring-1 ring-slate-200 hover:bg-[#E8F3EF]/40 has-[:checked]:bg-[#E8F3EF] has-[:checked]:ring-2 has-[:checked]:ring-[#064C3D] cursor-pointer transition';
        $puntos = fn ($valor) => $valor === null ? '—' : rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
    @endphp

    {{-- Breadcrumbs y Cabecera --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('talentohumano.panel') }}" class="hover:text-[#064C3D] hover:underline flex items-center gap-1">
                    <i class="fas fa-home"></i> Panel
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <a href="{{ route('talentohumano.seleccion.index') }}" class="hover:text-[#064C3D] hover:underline">
                    Selección y actas
                </a>
                <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                <span class="text-[#064C3D]">Reubicaciones</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#064C3D] text-lg text-[#34D399] shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-arrows-rotate"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">
                        Reubicaciones
                    </h1>
                    <p class="text-xs text-slate-500 sm:text-sm">
                        Aprendices que no quedaron en ninguna de sus 3 opciones. Registra la decisión del comité (instructores y psicólogos que evaluaron).
                    </p>
                </div>
            </div>
        </div>

        <a href="{{ route('talentohumano.seleccion.index') }}"
           class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver a propuestas
        </a>
    </div>

    @if (!$convocatoria)
        <div class="rounded-3xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200/70">
            <i class="fas fa-bullhorn mb-3 block text-3xl text-slate-300"></i>
            <p class="font-semibold text-slate-700">No hay una convocatoria en curso.</p>
            <p class="text-sm text-slate-500">Las reubicaciones se registran después de la selección de la convocatoria de la fase.</p>
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
            {{-- Columna izquierda: casos por reubicar (HU-036, esc. 1 y 8) --}}
            <aside class="space-y-3">
                <p class="px-1 text-xs font-bold uppercase tracking-[.18em] text-slate-400">
                    Por reubicar · {{ $pendientes->count() }}
                </p>

                @forelse ($pendientes as $pendiente)
                    @php $activo = $caso && $pendiente->id === $caso->id; @endphp
                    <a href="{{ route('talentohumano.seleccion.reubicaciones', ['postulacion' => $pendiente->id]) }}"
                       @class(['block w-full rounded-2xl p-4 text-left transition',
                               'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20 ring-2 ring-[#34D399]' => $activo,
                               'bg-white shadow-sm ring-1 ring-slate-200/70 hover:ring-[#34D399]' => !$activo])>
                        <p @class(['font-bold', 'text-base' => $activo, 'text-slate-800' => !$activo])>{{ $pendiente->nombre_completo }}</p>
                        <p @class(['text-xs mt-0.5', 'text-emerald-100' => $activo, 'text-slate-500' => !$activo])>
                            Ficha {{ $pendiente->course?->code }} · {{ $pendiente->course?->program?->name }} · {{ $puntos($pendiente->puntaje_total) }} pts
                        </p>
                        @if ($activo)
                            <span class="mt-2.5 inline-block rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-semibold text-white">Caso en estudio</span>
                        @endif
                    </a>
                @empty
                    <div class="rounded-2xl bg-white p-5 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                        <i class="fas fa-circle-check mb-2 block text-2xl text-emerald-500"></i>
                        No hay aprendices pendientes de reubicación
                    </div>
                @endforelse
            </aside>

            <div class="space-y-6">
                @if ($caso)
                    {{-- Ficha del aspirante --}}
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-extrabold text-[#064C3D]">{{ $caso->nombre_completo }}</h2>
                                <p class="text-sm text-slate-500 mt-0.5">
                                    Ficha {{ $caso->course?->code }} · {{ $caso->course?->program?->name }} ·
                                    <a href="{{ route('talentohumano.postulaciones.show', $caso->id) }}" class="font-semibold text-emerald-700 hover:underline">
                                        ver ficha completa <i class="fas fa-arrow-up-right-from-square text-xs ml-0.5"></i>
                                    </a>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Puntaje total</p>
                                <p class="text-3xl font-extrabold text-[#064C3D]">{{ $puntos($caso->puntaje_total) }}</p>
                            </div>
                        </div>

                        {{-- Resultados de evaluaciones --}}
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                                <p class="text-xs text-slate-500 font-semibold">Prueba psicotécnica</p>
                                @if ($evaluacion['prueba']?->puntaje !== null)
                                    <p class="text-lg font-extrabold text-[#064C3D] mt-0.5">{{ $puntos($evaluacion['prueba']->puntaje) }} <span class="text-xs font-semibold text-slate-400">/ 100</span></p>
                                @else
                                    <p class="text-sm font-semibold text-slate-500 mt-1">{{ $evaluacion['prueba'] ? ucfirst(str_replace('_', ' ', $evaluacion['prueba']->estado)) : 'Sin aplicar' }}</p>
                                @endif
                            </div>
                            @foreach (['tecnica' => 'Entrevista técnica', 'psicologica' => 'Entrevista psicológica'] as $tipo => $titulo)
                                @php $entrevista = $evaluacion['entrevistas'][$tipo] ?? null; @endphp
                                <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                                    <p class="text-xs text-slate-500 font-semibold">{{ $titulo }}</p>
                                    @if ($entrevista)
                                        <p class="text-lg font-extrabold text-[#064C3D] mt-0.5">
                                            {{ $puntos($entrevista['puntaje']) }}
                                            <span @class(['text-xs font-semibold', 'text-emerald-700' => $entrevista['concepto'] === 'Apto', 'text-amber-700' => $entrevista['concepto'] !== 'Apto'])>· {{ $entrevista['concepto'] }}</span>
                                        </p>
                                        @if ($entrevista['observacion']) <p class="text-xs text-slate-500 mt-1">«{{ \Illuminate\Support\Str::limit($entrevista['observacion'], 80) }}»</p> @endif
                                    @else
                                        <p class="text-sm font-semibold text-slate-500 mt-1">Sin calificar</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Opciones del postulante --}}
                        @if ($evaluacion['opciones']->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-xs font-semibold text-slate-500">Sus opciones (no quedó seleccionado en ninguna):</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    @foreach ($evaluacion['opciones'] as $opcionPostulante)
                                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">{{ $opcionPostulante->preferencia }}. {{ $opcionPostulante->cargo_nombre }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </section>

                    {{-- Decisión del comité (HU-036, esc. 2 a 6) --}}
                    <form action="{{ route('talentohumano.seleccion.reubicaciones.guardar') }}" method="POST" novalidate
                          class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        @csrf
                        <input type="hidden" name="postulacion_id" value="{{ $caso->id }}">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="font-bold text-[#064C3D] text-lg">Decisión del comité</h2>
                            <span class="text-xs text-slate-400 font-medium">HU-036 · RF-011</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            <p class="text-sm font-semibold text-slate-700">Cargo con cupos vacantes <span class="text-red-500">*</span></p>
                            @php $compatibles = $vacantes->where('compatible', true); $otros = $vacantes->where('compatible', false); @endphp

                            @if ($vacantes->isEmpty())
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                    <i class="fas fa-triangle-exclamation mr-1 text-amber-500"></i> No hay cargos con cupos vacantes
                                </div>
                                <label class="{{ $opcion }}">
                                    <input type="radio" name="cargo_activado_id" value="sin_cargo" checked class="accent-[#064C3D] h-4 w-4">
                                    <span class="flex-1 text-sm font-semibold text-slate-700">Sin cargo asignado</span>
                                    <span class="text-xs text-slate-500 font-medium">Requiere justificación</span>
                                </label>
                            @endif

                            @foreach (['Compatibles con ' . ($caso->course?->program?->name ?? 'su programa') => $compatibles, 'Otros cargos vacantes' => $otros] as $grupo => $lista)
                                @if ($lista->isNotEmpty())
                                    <div @class(['pt-2' => !$loop->first])>
                                        <p @class(['text-xs font-bold uppercase tracking-wider mb-2', 'text-emerald-700' => $loop->first, 'text-slate-400' => !$loop->first])>{{ $grupo }}</p>
                                        <div class="space-y-2">
                                            @foreach ($lista as $vacante)
                                                <label class="{{ $opcion }}">
                                                    <input type="radio" name="cargo_activado_id" value="{{ $vacante['cargo_activado']->id }}"
                                                           @checked((string) old('cargo_activado_id') === (string) $vacante['cargo_activado']->id) class="accent-[#064C3D] h-4 w-4">
                                                    <span class="flex-1 text-sm font-semibold text-slate-700">
                                                        {{ $vacante['cargo_activado']->cargo->nombre }}
                                                        @unless ($vacante['compatible'])
                                                            <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">No compatible</span>
                                                        @endunless
                                                    </span>
                                                    <span class="text-xs text-slate-500 font-medium">{{ $vacante['vacantes'] }} {{ $vacante['vacantes'] === 1 ? 'cupo vacante' : 'cupos vacantes' }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            @error('cargo_activado_id') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            @if ($vacantes->isNotEmpty())
                                <p class="text-xs text-slate-400"><i class="fas fa-info-circle mr-1"></i> Los cargos con todos sus cupos cubiertos no se muestran.</p>
                            @endif
                        </div>

                        {{-- Participantes y fecha --}}
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <fieldset @class(['rounded-2xl border p-4', 'border-red-300 bg-red-50/40' => $errors->has('participantes'), 'border-slate-100 bg-slate-50/50' => !$errors->has('participantes')])>
                                <legend class="text-sm font-semibold text-slate-700 px-1">Participantes de la reunión <span class="text-red-500">*</span></legend>
                                <div class="space-y-2 mt-2 text-sm">
                                    @forelse ($integrantes as $integrante)
                                        <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                            <input type="checkbox" name="participantes[]" value="{{ $integrante->id }}"
                                                   @checked(in_array($integrante->id, array_map('intval', old('participantes', $integrantes->pluck('id')->all())))) class="accent-[#064C3D] rounded h-4 w-4">
                                            <span>{{ $integrante->nombre }} · <span class="text-slate-500">{{ $integrante->funcion }}</span></span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-slate-500">No hay Líder de la estrategia designado ni evaluadores de entrevistas en esta convocatoria.</p>
                                    @endforelse
                                </div>
                                @error('participantes') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            </fieldset>

                            <div>
                                <label for="fecha_reunion" class="mb-1.5 block text-sm font-semibold text-slate-700">Fecha de la reunión <span class="text-red-500">*</span></label>
                                <input id="fecha_reunion" type="date" name="fecha_reunion" value="{{ old('fecha_reunion', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="{{ $campo }} {{ $borde('fecha_reunion') }}">
                                @error('fecha_reunion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @else <p class="text-xs text-slate-400 mt-2">Fecha en la que el comité sesionó para acordar la reubicación.</p> @enderror
                            </div>
                        </div>

                        <div class="mt-5">
                            <label for="justificacion" class="mb-1.5 block text-sm font-semibold text-slate-700">Justificación del comité</label>
                            <textarea id="justificacion" name="justificacion" rows="3" maxlength="2000" class="{{ $campo }} {{ $borde('justificacion') }}"
                                      placeholder="Explique las razones técnicas y formativas de la reubicación...">{{ old('justificacion') }}</textarea>
                            @error('justificacion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @else <p class="mt-1 text-xs text-slate-400">Obligatoria si el cargo no es compatible con su programa o si queda sin cargo asignado (RN-049).</p> @enderror
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="rounded-full bg-[#064C3D] px-7 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition flex items-center gap-2">
                                <i class="fas fa-floppy-disk"></i> Guardar reubicación
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Decisiones ya registradas --}}
                @if ($decididas->isNotEmpty())
                    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h2 class="font-bold text-[#064C3D]">Decisiones registradas · {{ $decididas->count() }}</h2>
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($decididas as $decision)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                                    <span class="font-semibold text-slate-800">{{ $decision->postulacion?->nombre_completo }}</span>
                                    <span class="text-slate-600">
                                        {{ $decision->cargoActivado?->cargo?->nombre ?? 'Sin cargo asignado' }}
                                        · {{ $decision->fecha_reunion?->format('d/m/Y') }} · {{ count($decision->participantes) }} participantes
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    @endif
</x-talentohumano::layouts.master>
