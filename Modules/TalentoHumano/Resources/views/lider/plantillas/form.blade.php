@use('Modules\TalentoHumano\Entities\PlantillaActa')
@use('Modules\TalentoHumano\Http\Requests\PlantillaActaRequest')
@php
    $editando = $plantilla->exists;
    $campo = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $tarjeta = 'rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70';
    $estado = [
        'secciones' => old('secciones', $plantilla->secciones),
        'columnas' => old('columnas_inventario', $plantilla->columnas_inventario),
        'items' => old('items_base', $plantilla->items_base ?? []),
    ];
    $firmantes = old('firmantes', $plantilla->firmantes);
    $area = (string) old('area_id', $areaInicial);
    $mensajes = collect($errors->all())->unique();
    $incompleta = $mensajes->contains(PlantillaActaRequest::MENSAJE_INCOMPLETA);
    $volver = $editando ? route('talentohumano.lider.plantillas.show', $plantilla) : route('talentohumano.lider.plantillas.index');
@endphp

<x-talentohumano::layouts.lider :title="$editando ? 'Editar plantilla' : 'Nueva plantilla de acta'" activo="plantillas">
    <a href="{{ $volver }}" data-confirmar-salida="#form-plantilla" class="text-sm font-semibold text-emerald-700 hover:underline">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> {{ $editando ? $plantilla->nombre : 'Plantillas de acta' }}
    </a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">{{ $editando ? 'Editar ' . $plantilla->nombre : 'Nueva plantilla de acta' }}</h1>
    <p class="mt-1 text-sm text-slate-600">Define cómo será el acta de recibimiento del cargo del área. Los campos marcados con <span class="text-red-500">*</span> son obligatorios.</p>

    @if ($editando && $actas)
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-code-branch mt-0.5 text-amber-500" aria-hidden="true"></i>
            <span>Esta plantilla ya se usó en <strong>{{ $actas }} actas</strong>. Al guardar se creará la <strong>versión {{ $siguienteVersion }}</strong> y quedará vigente; las actas existentes conservan la versión {{ $plantilla->version }} con la que se generaron.</span>
        </div>
    @elseif ($editando)
        <div class="mt-4 flex items-start gap-3 rounded-2xl bg-[#E8F3EF] px-4 py-3 text-sm text-[#064C3D]">
            <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i>
            <span>Esta versión todavía no se ha usado en ningún acta: los cambios se guardan sobre la misma versión.</span>
        </div>
    @endif

    <div id="errores-plantilla" role="alert" @class(['mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700', 'hidden' => $mensajes->isEmpty()])>
        <p class="font-semibold"><i class="fas fa-circle-exclamation mr-1" aria-hidden="true"></i> Revisa la plantilla antes de guardar</p>
        <ul class="mt-1 list-inside list-disc" data-lista-errores>
            @foreach ($mensajes as $mensaje) <li>{{ $mensaje }}</li> @endforeach
        </ul>
    </div>

    <form id="form-plantilla" method="POST" novalidate class="mt-6 space-y-4"
          action="{{ $editando ? route('talentohumano.lider.plantillas.update', $plantilla) : route('talentohumano.lider.plantillas.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <input type="hidden" name="secciones">
        <input type="hidden" name="columnas_inventario">
        <input type="hidden" name="items_base">
        <input type="hidden" name="firmantes">

        {{-- Datos de la plantilla --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Datos de la plantilla</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="area_id" class="mb-1 block text-sm font-semibold text-slate-700">Área <span class="text-red-500">*</span></label>
                    <select id="area_id" name="area_id" data-obligatorio @disabled($editando && $actas) class="{{ $campo }} @error('area_id') border-red-400 @enderror">
                        <option value="">Selecciona…</option>
                        <option value="general" @selected($area === 'general')>General (áreas sin plantilla propia)</option>
                        @foreach ($areas as $id => $nombre)
                            <option value="{{ $id }}" @selected($area === (string) $id)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @if ($editando && $actas) <input type="hidden" name="area_id" value="{{ $area }}"> @endif
                    @error('area_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @else <p class="mt-1 text-xs text-slate-500">Cada área tiene como máximo una plantilla vigente. El área no se puede cambiar después de usar la plantilla.</p> @enderror
                </div>
                <div>
                    <label for="nombre" class="mb-1 block text-sm font-semibold text-slate-700">Nombre <span class="text-red-500">*</span></label>
                    <input id="nombre" name="nombre" maxlength="150" data-obligatorio value="{{ old('nombre', $plantilla->nombre) }}" placeholder="Por ejemplo: Plantilla Agrícola"
                           class="{{ $campo }} @error('nombre') border-red-400 @enderror">
                    @error('nombre') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="indicaciones" class="mb-1 block text-sm font-semibold text-slate-700">Indicaciones para los aprendices</label>
                    <textarea id="indicaciones" name="indicaciones" rows="2" maxlength="2000" placeholder="Se muestran al inicio del acta" class="{{ $campo }}">{{ old('indicaciones', $plantilla->indicaciones) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Secciones y campos --}}
        <section class="{{ $tarjeta }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-[#064C3D]">Secciones y campos del acta</h2>
                    <p class="mt-1 text-sm text-slate-600">Agrega o quita secciones y campos. Los campos «Precargado» los llena el sistema con los datos del cargo, de los aprendices y de la unidad productiva.</p>
                </div>
                <button type="button" data-accion="agregar-seccion" class="rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar sección</button>
            </div>
            <div id="editor-secciones" class="mt-4 space-y-3"></div>
        </section>

        {{-- Columnas del inventario --}}
        <section id="bloque-columnas" @class([$tarjeta, 'ring-2 ring-red-300' => $incompleta && empty($estado['columnas'])])>
            <h2 class="font-bold text-[#064C3D]">Columnas del inventario <span class="text-red-500">*</span></h2>
            <p class="mt-1 text-sm text-slate-600">El aprendiz saliente diligencia estas columnas por cada implemento y el receptor verifica cada uno. La columna «Implemento» siempre está.</p>
            <div id="editor-columnas" class="mt-4 space-y-2"></div>
            <button type="button" data-accion="agregar-columna" class="mt-3 rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar columna</button>
        </section>

        {{-- Lista base de implementos --}}
        <section class="{{ $tarjeta }}">
            <h2 class="font-bold text-[#064C3D]">Lista base de implementos</h2>
            <p class="mt-1 text-sm text-slate-600">Solo se usa la primera vez que un cargo del área hace un empalme; después se precarga el inventario vigente del cargo.</p>
            <div id="editor-items" class="mt-4 space-y-2"></div>
            <button type="button" data-accion="agregar-item" class="mt-3 rounded-xl bg-[#E8F3EF] px-4 py-2 text-sm font-semibold text-[#064C3D] hover:bg-emerald-100"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Agregar implemento</button>
        </section>

        {{-- Firmas --}}
        <section id="bloque-firmantes" @class([$tarjeta, 'ring-2 ring-red-300' => $incompleta && empty($firmantes)])>
            <h2 class="font-bold text-[#064C3D]">Firmas requeridas <span class="text-red-500">*</span></h2>
            <p class="mt-1 text-sm text-slate-600">El acta solo queda válida cuando firman todas las partes marcadas. El visto bueno del Gestor de Talento Humano se registra aparte.</p>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach (PlantillaActa::FIRMANTES as $clave => [$texto, $ayuda])
                    <label class="flex items-start gap-3 rounded-2xl p-3 ring-1 ring-slate-200 has-[:checked]:bg-[#E8F3EF]/50 has-[:checked]:ring-[#064C3D]">
                        <input type="checkbox" data-firmante value="{{ $clave }}" @checked(in_array($clave, $firmantes)) class="mt-1 accent-[#064C3D]">
                        <span><span class="block text-sm font-semibold text-slate-700">{{ $texto }}</span><span class="text-xs text-slate-500">{{ $ayuda }}</span></span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2 pb-4">
            <a href="{{ $volver }}" data-confirmar-salida="#form-plantilla" class="rounded-full px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Cancelar</a>
            <button class="rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]">
                <i class="fas fa-floppy-disk mr-1" aria-hidden="true"></i> {{ $editando && $actas ? 'Guardar como versión ' . $siguienteVersion : 'Guardar' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            (() => {
                const TIPOS_CAMPO = @json(PlantillaActa::TIPOS_CAMPO);
                const TIPOS_COLUMNA = @json(PlantillaActa::TIPOS_COLUMNA);
                const MENSAJE_INCOMPLETA = @json(PlantillaActaRequest::MENSAJE_INCOMPLETA);
                const estado = @json($estado);
                estado.secciones = (estado.secciones || []).map((s) => ({ titulo: s.titulo || '', fija: !!s.fija, campos: s.campos || [] }));
                estado.columnas = (estado.columnas || []).map((c) => ({ nombre: c.nombre || '', tipo: c.tipo || 'texto', opciones: c.opciones || [] }));
                estado.items = estado.items || [];

                const form = document.getElementById('form-plantilla');
                const campo = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
                const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const opciones = (lista, actual) => Object.entries(lista).map(([v, t]) => `<option value="${v}" ${v === actual ? 'selected' : ''}>${esc(t)}</option>`).join('');
                const quitar = (accion, datos, etiqueta) => `<button type="button" data-accion="${accion}" ${datos} class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="${etiqueta}"><i class="fas fa-xmark" aria-hidden="true"></i></button>`;

                const pintarSecciones = () => {
                    document.getElementById('editor-secciones').innerHTML = estado.secciones.map((s, i) => `
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                            <div class="flex items-center gap-2">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#064C3D] text-xs font-bold text-white">${i + 1}</span>
                                <input value="${esc(s.titulo)}" data-s="${i}" data-k="titulo" aria-label="Título de la sección" placeholder="Título de la sección" class="${campo} min-w-0 flex-1 font-semibold" ${s.fija ? 'readonly' : ''}>
                                ${s.fija ? '<span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">Fija</span>' : quitar('quitar-seccion', `data-s="${i}"`, 'Quitar sección')}
                            </div>
                            <div class="mt-3 space-y-2 sm:pl-9">
                                ${s.campos.map((c, j) => `
                                    <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                                        <input value="${esc(c.etiqueta)}" data-s="${i}" data-c="${j}" data-k="etiqueta" aria-label="Nombre del campo" placeholder="Nombre del campo" class="${campo} min-w-0 flex-1">
                                        <select data-s="${i}" data-c="${j}" data-k="tipo" aria-label="Tipo de campo" class="${campo} w-40">${opciones(TIPOS_CAMPO, c.tipo)}</select>
                                        ${quitar('quitar-campo', `data-s="${i}" data-c="${j}"`, 'Quitar campo')}
                                    </div>`).join('')}
                                <button type="button" data-accion="agregar-campo" data-s="${i}" class="text-sm font-semibold text-emerald-700 hover:underline"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Agregar campo</button>
                            </div>
                        </div>`).join('');
                };

                const pintarColumnas = () => {
                    document.getElementById('editor-columnas').innerHTML = estado.columnas.length ? estado.columnas.map((c, i) => `
                        <div class="flex flex-wrap items-center gap-2 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200/70 sm:flex-nowrap">
                            <input value="${esc(c.nombre)}" data-col="${i}" data-k="nombre" aria-label="Nombre de la columna" placeholder="Nombre de la columna" class="${campo} min-w-0 flex-1">
                            <select data-col="${i}" data-k="tipo" aria-label="Tipo de columna" class="${campo} w-44">${opciones(TIPOS_COLUMNA, c.tipo)}</select>
                            <input value="${esc(c.opciones.join(', '))}" data-col="${i}" data-k="opciones" aria-label="Opciones separadas por coma" placeholder="Opciones: Bueno, Regular, Malo"
                                   class="${campo} min-w-0 flex-1 ${c.tipo === 'lista' ? '' : 'hidden'}">
                            ${quitar('quitar-columna', `data-col="${i}"`, 'Quitar columna')}
                        </div>`).join('')
                        : '<p class="rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-center text-sm text-slate-500">Sin columnas: agrega al menos una (por ejemplo, Cantidad o Estado).</p>';
                };

                const pintarItems = () => {
                    document.getElementById('editor-items').innerHTML = estado.items.length ? estado.items.map((it, i) => `
                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <input value="${esc(it.item)}" data-it="${i}" data-k="item" aria-label="Implemento" placeholder="Implemento" class="${campo} min-w-0 flex-1">
                            <input value="${esc(it.cantidad)}" data-it="${i}" data-k="cantidad" type="number" min="1" aria-label="Cantidad" placeholder="Cant." class="${campo} w-24">
                            <input value="${esc(it.unidad)}" data-it="${i}" data-k="unidad" aria-label="Unidad" placeholder="Unidad" class="${campo} w-28">
                            ${quitar('quitar-item', `data-it="${i}"`, 'Quitar implemento')}
                        </div>`).join('')
                        : '<p class="rounded-2xl border border-dashed border-slate-300 px-4 py-4 text-center text-sm text-slate-500">Sin lista base: en el primer empalme el aprendiz saliente registra los implementos.</p>';
                };

                const pintar = () => { pintarSecciones(); pintarColumnas(); pintarItems(); };

                // Cambios en los campos: se actualiza el estado sin volver a pintar (para no perder el foco)
                form.addEventListener('input', (e) => {
                    const d = e.target.dataset;
                    form.dataset.modificado = '1';
                    if (d.s !== undefined && d.c !== undefined) estado.secciones[d.s].campos[d.c][d.k] = e.target.value;
                    else if (d.s !== undefined) estado.secciones[d.s][d.k] = e.target.value;
                    else if (d.col !== undefined) estado.columnas[d.col][d.k] = d.k === 'opciones' ? e.target.value.split(',').map((o) => o.trim()).filter(Boolean) : e.target.value;
                    else if (d.it !== undefined) estado.items[d.it][d.k] = e.target.value;
                });
                form.addEventListener('change', (e) => {
                    if (e.target.dataset.col !== undefined && e.target.dataset.k === 'tipo') pintarColumnas();
                });

                form.addEventListener('click', (e) => {
                    const boton = e.target.closest('[data-accion]');
                    if (!boton) return;
                    const d = boton.dataset;
                    const acciones = {
                        'agregar-seccion': () => estado.secciones.push({ titulo: '', fija: false, campos: [{ etiqueta: '', tipo: 'texto' }] }),
                        'quitar-seccion': () => estado.secciones.splice(d.s, 1),
                        'agregar-campo': () => estado.secciones[d.s].campos.push({ etiqueta: '', tipo: 'texto' }),
                        'quitar-campo': () => estado.secciones[d.s].campos.splice(d.c, 1),
                        'agregar-columna': () => estado.columnas.push({ nombre: '', tipo: 'texto', opciones: [] }),
                        'quitar-columna': () => estado.columnas.splice(d.col, 1),
                        'agregar-item': () => estado.items.push({ item: '', cantidad: 1, unidad: '' }),
                        'quitar-item': () => estado.items.splice(d.it, 1),
                    };
                    if (!acciones[d.accion]) return;
                    acciones[d.accion]();
                    form.dataset.modificado = '1';
                    pintar();
                    // Foco en lo que se acaba de agregar
                    const nuevos = {
                        'agregar-seccion': () => document.querySelectorAll('#editor-secciones [data-k="titulo"]'),
                        'agregar-campo': () => document.querySelectorAll(`#editor-secciones [data-s="${d.s}"][data-k="etiqueta"]`),
                        'agregar-columna': () => document.querySelectorAll('#editor-columnas [data-k="nombre"]'),
                        'agregar-item': () => document.querySelectorAll('#editor-items [data-k="item"]'),
                    }[d.accion]?.();
                    nuevos?.[nuevos.length - 1]?.focus();
                });

                // Al enviar: verificación mínima (HU-007, esc. 5) y estructura como JSON
                form.addEventListener('submit', (e) => {
                    const firmantes = [...form.querySelectorAll('[data-firmante]:checked')].map((c) => c.value);
                    const errores = [];
                    const vacios = [...form.querySelectorAll('[data-obligatorio]')].filter((c) => !c.disabled && !c.value.trim());
                    if (vacios.length) errores.push('Debe completar los campos obligatorios');
                    if (!estado.columnas.length || !firmantes.length) errores.push(MENSAJE_INCOMPLETA);
                    vacios.forEach((c) => c.classList.add('border-red-400'));
                    document.getElementById('bloque-columnas').classList.toggle('ring-red-300', !estado.columnas.length);
                    document.getElementById('bloque-columnas').classList.toggle('ring-2', !estado.columnas.length);
                    document.getElementById('bloque-firmantes').classList.toggle('ring-red-300', !firmantes.length);
                    document.getElementById('bloque-firmantes').classList.toggle('ring-2', !firmantes.length);

                    const caja = document.getElementById('errores-plantilla');
                    caja.classList.toggle('hidden', !errores.length);
                    if (errores.length) {
                        e.preventDefault();
                        caja.querySelector('[data-lista-errores]').innerHTML = errores.map((m) => `<li>${esc(m)}</li>`).join('');
                        caja.scrollIntoView({ block: 'center' });
                        return;
                    }
                    form.elements.secciones.value = JSON.stringify(estado.secciones);
                    form.elements.columnas_inventario.value = JSON.stringify(estado.columnas);
                    form.elements.items_base.value = JSON.stringify(estado.items);
                    form.elements.firmantes.value = JSON.stringify(firmantes);
                    delete form.dataset.modificado;
                });
                form.querySelectorAll('[data-firmante]').forEach((c) => c.addEventListener('change', () => { form.dataset.modificado = '1'; }));

                pintar();
            })();
        </script>
    @endpush
</x-talentohumano::layouts.lider>
