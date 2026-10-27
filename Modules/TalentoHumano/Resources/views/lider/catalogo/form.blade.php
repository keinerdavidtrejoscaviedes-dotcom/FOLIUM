@use('Modules\TalentoHumano\Entities\Cargo')
@php
    $editando = $cargo->exists;
    $campo = 'w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $borde = fn (string $nombre) => $errors->has($nombre) ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-[#064C3D]';
    $etiqueta = 'mb-1 block text-sm font-semibold text-slate-700';
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $obligatorio = '<span class="text-red-500" aria-hidden="true">*</span>';

    $jefeTipo = old('jefe_tipo', $cargo->jefe_inmediato_texto && !$cargo->cargo_superior_id ? 'alta_direccion' : 'cargo');
    $programasForm = old('programas', $cargo->exists
        ? $cargo->programas->map(fn ($p) => ['program_id' => $p->id, 'tipo' => $p->pivot->tipo])->all()
        : []);
    $requisitosForm = old('requisitos_adicionales', $cargo->exists
        ? $cargo->requisitosAdicionales->map(fn ($r) => ['descripcion' => $r->descripcion, 'exige_soporte' => $r->exige_soporte])->all()
        : []);
    $nombresProgramas = $programas->mapWithKeys(fn ($p) => [$p->id => $p->name . ' · SOFIA ' . $p->sofia_code]);
    $faltanObligatorios = collect($errors->getMessages())->flatten()->contains('Este campo es obligatorio');
@endphp

<x-talentohumano::layouts.lider :title="$editando ? 'Editar cargo' : 'Nuevo cargo'" activo="catalogo">
    <a href="{{ $editando ? route('talentohumano.lider.catalogo.show', $cargo) : route('talentohumano.lider.catalogo.index') }}" data-confirmar-salida="#form-cargo"
       class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> {{ $editando ? $cargo->nombre : 'Catálogo de cargos' }}
    </a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">{{ $editando ? 'Editar cargo' : 'Nuevo cargo' }}</h1>
    <p class="mt-1 text-sm text-slate-600">Los campos marcados con <span class="text-red-500">*</span> son obligatorios.</p>

    <div id="aviso-obligatorios" role="alert" @class(['mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700', 'hidden' => !$faltanObligatorios])>
        <i class="fas fa-circle-exclamation mr-1" aria-hidden="true"></i> Debe completar los campos obligatorios
    </div>

    <form id="form-cargo" method="POST" novalidate
          action="{{ $editando ? route('talentohumano.lider.catalogo.update', $cargo) : route('talentohumano.lider.catalogo.store') }}"
          class="mt-6 space-y-4">
        @csrf
        @if ($editando) @method('PUT') @endif

        {{-- Datos básicos --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Datos del cargo</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nombre" class="{{ $etiqueta }}">Nombre del cargo {!! $obligatorio !!}</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre', $cargo->nombre) }}" maxlength="150" data-obligatorio class="{{ $campo }} {{ $borde('nombre') }}">
                    @error('nombre') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nivel" class="{{ $etiqueta }}">Nivel jerárquico {!! $obligatorio !!}</label>
                    <select id="nivel" name="nivel" data-obligatorio class="{{ $campo }} {{ $borde('nivel') }}">
                        <option value="">Selecciona…</option>
                        @foreach (Cargo::NIVELES as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('nivel', $cargo->nivel) === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    @error('nivel') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="area_id" class="{{ $etiqueta }}">Área funcional {!! $obligatorio !!}</label>
                    <select id="area_id" name="area_id" data-obligatorio class="{{ $campo }} {{ $borde('area_id') }}">
                        <option value="">Selecciona…</option>
                        @foreach ($areas as $id => $nombre)
                            <option value="{{ $id }}" @selected((string) old('area_id', $cargo->area_id) === (string) $id)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('area_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="numero_aprendices" class="{{ $etiqueta }}">Número de aprendices {!! $obligatorio !!}</label>
                    <input id="numero_aprendices" name="numero_aprendices" type="number" min="1" max="50" value="{{ old('numero_aprendices', $cargo->numero_aprendices) }}"
                           data-obligatorio class="{{ $campo }} {{ $borde('numero_aprendices') }}">
                    @error('numero_aprendices') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Jefe inmediato --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Jefe inmediato {!! $obligatorio !!}</h2>
            <p id="jefe-gerente" class="mt-3 hidden rounded-2xl bg-[#E8F3EF] px-4 py-3 text-sm text-[#064C3D]">
                <i class="fas fa-circle-info mr-1" aria-hidden="true"></i>
                Todo Gerente queda bajo el <strong>{{ Cargo::JEFE_GERENTE }}</strong>; el sistema lo registra automáticamente.
            </p>
            <div id="jefe-opciones" class="mt-3 space-y-3">
                <label class="flex items-start gap-3 rounded-2xl p-3 ring-1 ring-slate-200 has-[:checked]:bg-[#E8F3EF]/50 has-[:checked]:ring-[#064C3D]">
                    <input type="radio" name="jefe_tipo" value="cargo" @checked($jefeTipo === 'cargo') class="mt-1 accent-[#064C3D]">
                    <span class="flex-1">
                        <span class="text-sm font-semibold text-slate-700">Un cargo del catálogo</span>
                        <select name="cargo_superior_id" id="cargo_superior_id" aria-label="Cargo jefe inmediato" data-obligatorio
                                class="mt-2 {{ $campo }} {{ $borde('cargo_superior_id') }}">
                            <option value="">Selecciona el jefe inmediato…</option>
                            @foreach ($jefes->groupBy('nivel') as $nivel => $grupo)
                                <optgroup label="{{ Cargo::NIVELES[$nivel] }}">
                                    @foreach ($grupo as $jefe)
                                        <option value="{{ $jefe->id }}" data-rango="{{ Cargo::rango($jefe->nivel) }}" @selected((string) old('cargo_superior_id', $cargo->cargo_superior_id) === (string) $jefe->id)>{{ $jefe->nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="mt-1 block text-xs text-slate-500">Solo se muestran los cargos de nivel superior al elegido.</span>
                        @error('cargo_superior_id') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-2xl p-3 ring-1 ring-slate-200 has-[:checked]:bg-[#E8F3EF]/50 has-[:checked]:ring-[#064C3D]">
                    <input type="radio" name="jefe_tipo" value="alta_direccion" @checked($jefeTipo === 'alta_direccion') class="mt-1 accent-[#064C3D]">
                    <span class="flex-1">
                        <span class="text-sm font-semibold text-slate-700">Jefe de la alta dirección</span>
                        <input name="jefe_inmediato_texto" id="jefe_inmediato_texto" aria-label="Rol de la alta dirección" maxlength="150" data-obligatorio
                               value="{{ old('jefe_inmediato_texto', $cargo->cargo_superior_id ? '' : $cargo->jefe_inmediato_texto) }}" placeholder="Por ejemplo: Instructor Líder"
                               class="mt-2 {{ $campo }} {{ $borde('jefe_inmediato_texto') }}">
                        @error('jefe_inmediato_texto') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </span>
                </label>
                @error('jefe_tipo') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- Tecnólogos compatibles --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Tecnólogos compatibles</h2>
            <p class="mt-1 text-sm text-slate-600">
                <strong>Principal</strong> y <strong>alterno</strong> pueden postularse. <strong>Exclusivo si hay ficha en la fase</strong>:
                si en la fase hay una ficha de ese tecnólogo, solo sus aprendices pueden postularse (por ejemplo, Gestión Documental con SIG).
            </p>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <label for="agregar-programa" class="sr-only">Tecnólogo a agregar</label>
                <select id="agregar-programa" class="{{ $campo }} border-slate-200 focus:border-[#064C3D]">
                    <option value="">Selecciona un tecnólogo para agregar…</option>
                    @foreach ($nombresProgramas as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                <button type="button" id="boton-agregar-programa" class="shrink-0 rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100">
                    <i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar
                </button>
            </div>
            <ul id="lista-programas" class="mt-3 space-y-2">
                @foreach ($programasForm as $i => $programa)
                    <li class="flex flex-col gap-2 rounded-2xl bg-[#E8F3EF]/60 p-3 sm:flex-row sm:items-center" data-programa="{{ $programa['program_id'] }}">
                        <input type="hidden" name="programas[{{ $i }}][program_id]" value="{{ $programa['program_id'] }}">
                        <span class="flex-1 text-sm font-semibold text-slate-700">{{ $nombresProgramas[$programa['program_id']] ?? 'Programa ' . $programa['program_id'] }}</span>
                        <select name="programas[{{ $i }}][tipo]" aria-label="Tipo de compatibilidad" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm">
                            @foreach (Cargo::TIPOS_PROGRAMA as $valor => $texto)
                                <option value="{{ $valor }}" @selected(($programa['tipo'] ?? 'principal') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        <button type="button" data-quitar class="self-end rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-white hover:text-red-600 sm:self-auto" aria-label="Quitar tecnólogo"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                    </li>
                @endforeach
            </ul>
            <p id="sin-programas" @class(['mt-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800', 'hidden' => $programasForm])>
                <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                Sin tecnólogos compatibles, el cargo no se propondrá automáticamente en la activación y solo podrá agregarse de forma manual.
            </p>
            @error('programas.*') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Requisitos --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Requisitos</h2>
            <label for="requisitos" class="{{ $etiqueta }} mt-4">Requisitos del manual</label>
            <textarea id="requisitos" name="requisitos" rows="3" class="{{ $campo }} {{ $borde('requisitos') }}">{{ old('requisitos', $cargo->requisitos) }}</textarea>

            <h3 class="mt-5 text-sm font-semibold text-slate-700">Requisitos que el sistema no puede verificar</h3>
            <p class="text-xs text-slate-500">Por ejemplo, «haber aprobado un curso virtual». Si marcas «Exigir soporte», el documento será obligatorio en la postulación a este cargo.</p>
            <ul id="lista-requisitos" class="mt-3 space-y-2">
                @foreach ($requisitosForm as $i => $requisito)
                    <li class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input name="requisitos_adicionales[{{ $i }}][descripcion]" value="{{ $requisito['descripcion'] }}" maxlength="255" aria-label="Requisito" class="{{ $campo }} border-slate-200 focus:border-[#064C3D]">
                        <label class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="requisitos_adicionales[{{ $i }}][exige_soporte]" value="1" @checked(!empty($requisito['exige_soporte'])) class="accent-[#064C3D]"> Exigir soporte
                        </label>
                        <button type="button" data-quitar class="self-end rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-slate-100 hover:text-red-600 sm:self-auto" aria-label="Quitar requisito"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                    </li>
                @endforeach
            </ul>
            <button type="button" id="boton-agregar-requisito" class="mt-3 rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100">
                <i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar requisito
            </button>
        </section>

        {{-- Descripción del manual --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Descripción del cargo</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach (['macroproceso' => 'Macroproceso', 'proceso' => 'Proceso', 'subproceso' => 'Subproceso'] as $nombre => $texto)
                    <div>
                        <label for="{{ $nombre }}" class="{{ $etiqueta }}">{{ $texto }}</label>
                        <input id="{{ $nombre }}" name="{{ $nombre }}" value="{{ old($nombre, $cargo->$nombre) }}" class="{{ $campo }} {{ $borde($nombre) }}">
                    </div>
                @endforeach
            </div>
            @foreach (['rol_principal' => 'Rol principal', 'actividades' => 'Actividades', 'responsabilidades_sig' => 'Responsabilidades en el Sistema Integrado de Gestión', 'documentacion' => 'Documentación'] as $nombre => $texto)
                <label for="{{ $nombre }}" class="{{ $etiqueta }} mt-4">{{ $texto }}</label>
                <textarea id="{{ $nombre }}" name="{{ $nombre }}" rows="{{ $nombre === 'rol_principal' ? 3 : 5 }}" class="{{ $campo }} {{ $borde($nombre) }}">{{ old($nombre, $cargo->$nombre) }}</textarea>
            @endforeach
        </section>

        <div class="flex flex-wrap justify-end gap-2 pb-4">
            <a href="{{ $editando ? route('talentohumano.lider.catalogo.show', $cargo) : route('talentohumano.lider.catalogo.index') }}" data-confirmar-salida="#form-cargo"
               class="rounded-full px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Cancelar</a>
            <button class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]">
                <i class="fas fa-floppy-disk mr-1" aria-hidden="true"></i> {{ $editando ? 'Actualizar' : 'Guardar' }}
            </button>
        </div>
    </form>

    {{-- Plantillas para las filas que se agregan con JavaScript --}}
    <template id="plantilla-programa">
        <li class="flex flex-col gap-2 rounded-2xl bg-[#E8F3EF]/60 p-3 sm:flex-row sm:items-center">
            <input type="hidden" data-campo="program_id">
            <span class="flex-1 text-sm font-semibold text-slate-700" data-nombre></span>
            <select data-campo="tipo" aria-label="Tipo de compatibilidad" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm">
                @foreach (Cargo::TIPOS_PROGRAMA as $valor => $texto)
                    <option value="{{ $valor }}">{{ $texto }}</option>
                @endforeach
            </select>
            <button type="button" data-quitar class="self-end rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-white hover:text-red-600 sm:self-auto" aria-label="Quitar tecnólogo"><i class="fas fa-xmark" aria-hidden="true"></i></button>
        </li>
    </template>
    <template id="plantilla-requisito">
        <li class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input data-campo="descripcion" maxlength="255" aria-label="Requisito" class="{{ $campo }} border-slate-200 focus:border-[#064C3D]">
            <label class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" data-campo="exige_soporte" value="1" class="accent-[#064C3D]"> Exigir soporte
            </label>
            <button type="button" data-quitar class="self-end rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-slate-100 hover:text-red-600 sm:self-auto" aria-label="Quitar requisito"><i class="fas fa-xmark" aria-hidden="true"></i></button>
        </li>
    </template>

    @push('scripts')
        <script>
            (() => {
                const form = document.getElementById('form-cargo');
                const nivel = document.getElementById('nivel');
                const jefe = document.getElementById('cargo_superior_id');
                const rangos = { Gerente: 0, Gestor: 1, Lider: 2, Equipo: 3 };
                let contador = Date.now();

                form.addEventListener('input', () => { form.dataset.modificado = '1'; });

                // Jefe inmediato según el nivel: Gerente automático; los demás solo ven cargos de nivel superior
                const actualizarJefe = () => {
                    const esGerente = nivel.value === 'Gerente';
                    document.getElementById('jefe-gerente').classList.toggle('hidden', !esGerente);
                    document.getElementById('jefe-opciones').classList.toggle('hidden', esGerente);
                    const rango = rangos[nivel.value];
                    jefe.querySelectorAll('option[data-rango]').forEach((opcion) => {
                        opcion.hidden = opcion.disabled = rango !== undefined && Number(opcion.dataset.rango) >= rango;
                    });
                    if (jefe.selectedOptions[0]?.disabled) jefe.value = '';
                };
                const actualizarTipoJefe = () => {
                    const tipo = form.querySelector('input[name="jefe_tipo"]:checked')?.value;
                    jefe.disabled = tipo !== 'cargo';
                    document.getElementById('jefe_inmediato_texto').disabled = tipo !== 'alta_direccion';
                };
                nivel.addEventListener('change', actualizarJefe);
                form.querySelectorAll('input[name="jefe_tipo"]').forEach((r) => r.addEventListener('change', actualizarTipoJefe));
                actualizarJefe();
                actualizarTipoJefe();

                // Tecnólogos compatibles
                const listaProgramas = document.getElementById('lista-programas');
                const selectorPrograma = document.getElementById('agregar-programa');
                const avisoSinProgramas = () => document.getElementById('sin-programas').classList.toggle('hidden', listaProgramas.children.length > 0);
                document.getElementById('boton-agregar-programa').addEventListener('click', () => {
                    const id = selectorPrograma.value;
                    if (!id || listaProgramas.querySelector(`[data-programa="${id}"]`)) return;
                    const fila = document.getElementById('plantilla-programa').content.firstElementChild.cloneNode(true);
                    const n = contador++;
                    fila.dataset.programa = id;
                    fila.querySelector('[data-nombre]').textContent = selectorPrograma.selectedOptions[0].textContent;
                    fila.querySelector('[data-campo="program_id"]').name = `programas[${n}][program_id]`;
                    fila.querySelector('[data-campo="program_id"]').value = id;
                    fila.querySelector('[data-campo="tipo"]').name = `programas[${n}][tipo]`;
                    listaProgramas.appendChild(fila);
                    selectorPrograma.value = '';
                    form.dataset.modificado = '1';
                    avisoSinProgramas();
                });

                // Requisitos no verificables
                document.getElementById('boton-agregar-requisito').addEventListener('click', () => {
                    const fila = document.getElementById('plantilla-requisito').content.firstElementChild.cloneNode(true);
                    const n = contador++;
                    fila.querySelector('[data-campo="descripcion"]').name = `requisitos_adicionales[${n}][descripcion]`;
                    fila.querySelector('[data-campo="exige_soporte"]').name = `requisitos_adicionales[${n}][exige_soporte]`;
                    document.getElementById('lista-requisitos').appendChild(fila);
                    fila.querySelector('input').focus();
                });

                form.addEventListener('click', (e) => {
                    const quitar = e.target.closest('[data-quitar]');
                    if (!quitar) return;
                    quitar.closest('li').remove();
                    form.dataset.modificado = '1';
                    avisoSinProgramas();
                });

                // Verificación de campos obligatorios antes de enviar (HU-003, esc. 2)
                form.addEventListener('submit', (e) => {
                    const faltantes = [...form.querySelectorAll('[data-obligatorio]')]
                        .filter((c) => !c.disabled && c.offsetParent !== null && !c.value.trim());
                    form.querySelectorAll('[data-obligatorio]').forEach((c) => c.classList.toggle('border-red-400', faltantes.includes(c)));
                    document.getElementById('aviso-obligatorios').classList.toggle('hidden', faltantes.length === 0);
                    if (faltantes.length) {
                        e.preventDefault();
                        faltantes[0].focus();
                        document.getElementById('aviso-obligatorios').scrollIntoView({ block: 'center' });
                    } else {
                        delete form.dataset.modificado;
                    }
                });
            })();
        </script>
    @endpush
</x-talentohumano::layouts.lider>
