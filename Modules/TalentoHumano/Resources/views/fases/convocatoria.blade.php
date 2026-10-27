@use('Modules\TalentoHumano\Http\Controllers\ConvocatoriaController')
@php
    $claseCampo = 'w-full rounded-xl border-0 px-3 py-2 text-sm ring-1 focus:ring-2 focus:ring-[#064C3D] disabled:bg-slate-50 disabled:text-slate-500';
    $anillo = fn ($campo) => $errors->has($campo) ? 'ring-red-400' : 'ring-slate-300';
    $hayOld = old('cargos') !== null;
    $fecha = fn ($valor) => $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : '—';
@endphp
<x-talentohumano::layouts.master title="Convocatoria · Fase {{ $fase->numero }}">
    <x-talentohumano::encabezado titulo="Convocatoria · Fase {{ $fase->numero }}" :volver="route('talentohumano.fases.show', $fase->id)" hu="HU-022 · HU-023 · RF-004 · RN-048"
        subtitulo="Defina los requisitos de cada cargo activado, el umbral mínimo de aprobación y la política para cuando nadie lo alcance. Al publicarla, solo la verán los aprendices de las fichas de la fase.">
        @if ($convocatoria)
            <x-talentohumano::estado :valor="$convocatoria->estado" :etiqueta="ConvocatoriaController::ESTADOS[$convocatoria->estado]" class="text-sm" />
        @endif
    </x-talentohumano::encabezado>

    @if ($bloqueo)
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
            <p class="text-slate-600"><i class="fas fa-lock mr-1 text-amber-600" aria-hidden="true"></i> {{ $bloqueo }}</p>
            <a href="{{ route('talentohumano.fases.show', $fase->id) }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a la fase
            </a>
        </div>
    @else
        @if (!$editable)
            <div class="mb-6 rounded-2xl bg-[#E8F3EF] p-4 text-sm text-[#064C3D]">
                <i class="fas fa-circle-check mr-1" aria-hidden="true"></i>
                La convocatoria está {{ mb_strtolower(ConvocatoriaController::ESTADOS[$convocatoria->estado]) }}. Su contenido ya no se puede modificar.
            </div>
        @elseif ($cronograma->estado !== 'aprobado')
            <div class="mb-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                <i class="fas fa-hourglass-half mr-1" aria-hidden="true"></i>
                Puede preparar la convocatoria y guardarla como borrador. Se podrá publicar cuando el Instructor Líder apruebe el cronograma.
            </div>
        @endif

        <x-talentohumano::errores />

        <form method="POST" action="{{ route('talentohumano.fases.convocatoria.update', $fase->id) }}">
            @csrf
            @method('PUT')

            {{-- Datos generales --}}
            <div class="grid gap-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Título</span>
                    <input type="text" name="titulo" required maxlength="255" @disabled(!$editable)
                           value="{{ old('titulo', $convocatoria->titulo ?? 'Convocatoria SENA Empresa · Fase ' . $fase->numero) }}"
                           class="{{ $claseCampo }} {{ $anillo('titulo') }} mt-1">
                </label>
                <div class="text-sm">
                    <p class="font-semibold text-slate-700">Apertura y cierre</p>
                    <p class="mt-1 text-slate-600">{{ $fecha($fechas['apertura']) }} al {{ $fecha($fechas['cierre']) }}</p>
                    <p class="text-xs text-slate-400">Se toman del cronograma, para que siempre coincidan con él.</p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-slate-700">Fichas habilitadas ({{ $fichas->count() }})</p>
                    <ul class="mt-1 flex flex-wrap gap-1.5">
                        @foreach ($fichas as $ficha)
                            <li class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700">
                                <strong>{{ $ficha->code }}</strong> · {{ $ficha->programa }}{{ $ficha->trimestre ? ' · ' . $ficha->trimestre . '.º trim.' : '' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Umbral mínimo de aprobación</span>
                    <input type="number" name="umbral" min="0" max="999.99" step="0.01" @disabled(!$editable)
                           value="{{ old('umbral', $convocatoria ? (float) $convocatoria->umbral : '') }}" placeholder="Por ejemplo: 60"
                           class="{{ $claseCampo }} {{ $anillo('umbral') }} mt-1">
                    <span class="text-xs text-slate-400">Puntaje total mínimo (prueba psicotécnica + entrevista) para ser apto.</span>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Si ningún candidato alcanza el umbral</span>
                    <select name="politica_sin_aptos" @disabled(!$editable) class="{{ $claseCampo }} {{ $anillo('politica_sin_aptos') }} mt-1">
                        <option value="">Seleccione…</option>
                        @foreach (ConvocatoriaController::POLITICAS as $valor => $nombre)
                            <option value="{{ $valor }}" @selected(old('politica_sin_aptos', $convocatoria->politica_sin_aptos ?? '') === $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            {{-- Requisitos de cada cargo --}}
            <h2 class="mb-3 mt-8 font-bold text-[#064C3D]">Cargos ofrecidos ({{ $cargos->count() }})</h2>
            <div class="space-y-4">
                @foreach ($cargos as $cargo)
                    @php
                        $base = "cargos.{$cargo->id}";
                        $guardado = $cargo->guardado;
                        $documentos = $hayOld ? (array) old("$base.documentos", []) : ($guardado['documentos'] ?? ['hoja_vida']);
                        $extra = array_map('intval', $hayOld ? (array) old("$base.programas_extra", []) : ($guardado['programas_extra'] ?? []));
                        $tieneError = collect($errors->getMessages())->keys()->contains(fn ($k) => str_starts_with($k, "$base."));
                    @endphp
                    <section @class(['rounded-2xl bg-white p-5 shadow-sm ring-1', 'ring-red-300' => $tieneError, 'ring-slate-200' => !$tieneError])>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-slate-900">{{ $cargo->nombre }}</h3>
                                <p class="text-xs text-slate-500">{{ $cargo->area ?? 'Sin área' }} · {{ $cargo->nivel === 'Lider' ? 'Líder' : $cargo->nivel }}</p>
                            </div>
                            <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-semibold text-[#064C3D]">{{ $cargo->numero_aprendices }} {{ $cargo->numero_aprendices == 1 ? 'cupo' : 'cupos' }}</span>
                        </div>

                        {{-- Perfil compatible (RN-007) --}}
                        <div class="mt-3 text-sm">
                            <p class="font-semibold text-slate-700">Tecnólogos compatibles</p>
                            @if ($cargo->compatibles)
                                <p class="text-slate-600">{{ implode(', ', $cargo->compatibles) }}</p>
                            @else
                                <p class="text-slate-500">El catálogo no tiene programas compatibles para este cargo.</p>
                            @endif
                            <p @class(['text-xs', 'text-slate-500' => $cargo->aprendices_compatibles > 0, 'font-semibold text-amber-700' => $cargo->aprendices_compatibles == 0])>
                                {{ $cargo->aprendices_compatibles }} aprendices de la fase tienen un programa compatible.
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Requisitos y competencias mínimas</span>
                                <textarea name="cargos[{{ $cargo->id }}][requisitos]" rows="4" @disabled(!$editable)
                                          placeholder="Titulación requerida, competencias mínimas y lo que se espera del aspirante"
                                          class="{{ $claseCampo }} {{ $anillo("$base.requisitos") }} mt-1">{{ old("$base.requisitos", $guardado['requisitos'] ?? $cargo->requisitos) }}</textarea>
                            </label>
                            <fieldset>
                                <legend class="text-sm font-semibold text-slate-700">Documentos obligatorios</legend>
                                <p class="text-xs text-slate-400">La propuesta de trabajo se pide siempre, una por cada cargo elegido.</p>
                                <div class="mt-2 space-y-1.5 text-sm">
                                    @foreach (ConvocatoriaController::DOCUMENTOS as $tipo => $nombre)
                                        <label class="flex items-center gap-2">
                                            @if ($tipo === 'hoja_vida')
                                                <input type="checkbox" checked disabled class="rounded border-slate-300 text-[#064C3D]">
                                                <input type="hidden" name="cargos[{{ $cargo->id }}][documentos][]" value="hoja_vida">
                                            @else
                                                <input type="checkbox" name="cargos[{{ $cargo->id }}][documentos][]" value="{{ $tipo }}" @disabled(!$editable)
                                                       class="rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D]" @checked(in_array($tipo, $documentos))>
                                            @endif
                                            {{ $nombre }}
                                        </label>
                                    @endforeach
                                    <input type="text" name="cargos[{{ $cargo->id }}][otro]" maxlength="255" @disabled(!$editable)
                                           value="{{ old("$base.otro", $guardado['otro'] ?? '') }}" placeholder="¿Cuál otro documento?"
                                           class="{{ $claseCampo }} {{ $anillo("$base.otro") }} mt-1">
                                </div>
                            </fieldset>
                        </div>

                        {{-- HU-023: excepción con otros programas de la fase --}}
                        @if ($cargo->otros_programas)
                            <details class="mt-4 rounded-xl bg-slate-50 p-4" @if ($extra || $errors->has("$base.justificacion") || $errors->has("$base.programas_extra")) open @endif>
                                <summary class="cursor-pointer text-sm font-semibold text-[#064C3D]">
                                    Habilitar otros programas de la fase{{ $extra ? ' (' . count($extra) . ' habilitados)' : '' }}
                                </summary>
                                @if ($cargo->aprendices_compatibles >= $cargo->numero_aprendices)
                                    <p class="mt-2 text-xs font-semibold text-amber-700">
                                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Hay fichas compatibles con este cargo en la fase.
                                    </p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-sm">
                                    @foreach ($cargo->otros_programas as $programaId => $programa)
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="cargos[{{ $cargo->id }}][programas_extra][]" value="{{ $programaId }}" @disabled(!$editable)
                                                   class="rounded border-slate-300 text-[#064C3D] focus:ring-[#064C3D]" @checked(in_array($programaId, $extra))>
                                            {{ $programa }}
                                        </label>
                                    @endforeach
                                </div>
                                <label class="mt-3 block">
                                    <span class="text-sm font-semibold text-slate-700">Justificación</span>
                                    <textarea name="cargos[{{ $cargo->id }}][justificacion]" rows="2" @disabled(!$editable)
                                              placeholder="Por qué se abre el cargo a otros programas"
                                              class="{{ $claseCampo }} {{ $anillo("$base.justificacion") }} mt-1">{{ old("$base.justificacion", $guardado['justificacion'] ?? '') }}</textarea>
                                </label>
                            </details>
                        @endif

                        @foreach (collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, "$base."))->flatten()->unique() as $mensaje)
                            <p class="mt-2 text-xs font-semibold text-red-700">{{ $mensaje }}</p>
                        @endforeach
                    </section>
                @endforeach
            </div>

            @if ($editable)
                <div class="sticky bottom-0 mt-6 flex flex-wrap justify-end gap-3 rounded-2xl bg-white/95 p-4 shadow-lg ring-1 ring-slate-200 backdrop-blur">
                    <button type="submit" name="accion" value="guardar" formnovalidate
                            class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
                        <i class="fas fa-floppy-disk" aria-hidden="true"></i> Guardar borrador
                    </button>
                    <button type="submit" name="accion" value="publicar"
                            onclick="return confirm('Al publicar, la convocatoria queda visible para los aprendices y ya no se podrán cambiar sus datos, las fichas ni los cargos de la fase. ¿Publicar?')"
                            class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F]">
                        <i class="fas fa-bullhorn" aria-hidden="true"></i> Publicar
                    </button>
                </div>
            @endif
        </form>
    @endif
</x-talentohumano::layouts.master>
