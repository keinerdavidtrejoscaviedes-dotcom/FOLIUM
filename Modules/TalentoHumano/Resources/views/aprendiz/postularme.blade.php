<x-talentohumano::layouts.aprendiz title="Postularme a la Convocatoria" activo="convocatoria" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('talentohumano.aprendiz.convocatoria') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline mb-2">
                    <i class="fas fa-arrow-left"></i> Volver a la convocatoria
                </a>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Formulario de Postulación</h1>
                <p class="mt-1 text-sm text-slate-600">Fase XXXVI · Convocatoria oficial de SENA Empresa.</p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3.5 py-1 text-xs font-bold text-[#064C3D] ring-1 ring-[#064C3D]/20">
                Paso 1 de 6
            </span>
        </div>

        @if ($estado === 'cerrada')
            <div class="entrada mt-6 rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400 mb-3">
                    <i class="fas fa-door-closed"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">La convocatoria está cerrada</h2>
                <p class="mt-1 text-sm text-slate-500">El plazo de postulaciones finalizó el 12/09/2026 a las 11:59 p. m.</p>
                <div class="mt-5">
                    <a href="{{ route('talentohumano.aprendiz.postulacion') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white">
                        Ver el estado de mi postulación
                    </a>
                </div>
            </div>
        @else

            {{-- Errores de Validación (HU-045, HU-046) --}}
            @if ($estado === 'errores' || (isset($errors) && $errors->any()))
                <div class="entrada mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm" role="alert">
                    <p class="font-bold flex items-center gap-2">
                        <i class="fas fa-circle-exclamation text-red-600"></i> No pudimos confirmar tu postulación
                    </p>
                    <ul class="mt-2 list-disc pl-6 space-y-1 text-xs font-medium">
                        <li>Debe seleccionar exactamente 3 cargos distintos en orden de preferencia (RN-010).</li>
                        <li>Falta adjuntar: Certificados académicos.</li>
                        <li>Falta la propuesta de trabajo para la 3.ª opción de cargo elegida.</li>
                    </ul>
                </div>
            @endif

            <form action="{{ route('talentohumano.aprendiz.postulacion.guardar') }}" method="POST" enctype="multipart/form-data" id="form-postulacion" class="mt-6 space-y-6">
                @csrf

                {{-- Sección 1: Datos del Aprendiz (Pre-cargados de SICEFA) --}}
                <section class="rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#E8F3EF] text-xs font-black text-[#064C3D]">1</span>
                            Tus datos personales y formativos
                        </h2>
                        <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
                            <i class="fas fa-lock text-[10px]"></i> Verificado en SICEFA
                        </span>
                    </div>

                    <dl class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 text-xs sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-400">Nombre completo</dt>
                            <dd class="font-bold text-slate-800 text-sm mt-0.5">{{ $aprendiz['nombre'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Documento de identidad</dt>
                            <dd class="font-bold text-slate-800 text-sm mt-0.5">{{ $aprendiz['documento'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Correo institucional</dt>
                            <dd class="font-bold text-slate-800 text-sm mt-0.5">{{ $aprendiz['correo'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Programa y ficha</dt>
                            <dd class="font-bold text-slate-800 text-sm mt-0.5">{{ $aprendiz['programa'] }} · {{ $aprendiz['ficha'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="telefono" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Teléfono de contacto <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="telefono" id="telefono" required value="{{ old('telefono', $aprendiz['telefono']) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40" placeholder="Ej. 3123456789">
                        </div>

                        <div>
                            <label for="municipio" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Municipio de procedencia <span class="text-red-500">*</span>
                            </label>
                            <select name="municipio" id="municipio" required class="w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 text-slate-800">
                                <option value="">Selecciona tu municipio…</option>
                                @foreach ($municipios as $mun)
                                    <option value="{{ $mun }}" @selected(old('municipio', $aprendiz['municipio']) === $mun)>{{ $mun }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="vereda" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Vereda o ruta de transporte <span class="text-slate-400 font-normal">(Opcional)</span>
                            </label>
                            <input type="text" name="vereda" id="vereda" value="{{ old('vereda', $aprendiz['vereda']) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40" placeholder="Ej. Vereda La Vega / Ruta 3">
                        </div>
                    </div>
                </section>

                {{-- Sección 2: Selección de 3 Cargos por Orden de Preferencia (HU-045, RN-010) --}}
                <section class="rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#E8F3EF] text-xs font-black text-[#064C3D]">2</span>
                            Tus 3 cargos en orden de preferencia
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                            El orden de preferencia es vinculante (RN-010): primero se evaluará tu 1.ª opción. No puedes repetir un mismo cargo en más de una opción.
                        </p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @for ($opc = 1; $opc <= 3; $opc++)
                            @php
                                $coloresPref = [
                                    1 => 'bg-[#064C3D] text-white',
                                    2 => 'bg-emerald-600 text-white',
                                    3 => 'bg-[#34D399] text-[#043A2F]',
                                ];
                                $defaultOpc = match($opc) {
                                    1 => 'Líder de Unidad de Formación Productiva Cacao',
                                    2 => 'Gestor Agrícola',
                                    3 => 'Líder de herramientas e insumos agrícolas',
                                    default => '',
                                };
                            @endphp
                            <div class="rounded-2xl p-4 ring-1 ring-slate-200/80 bg-slate-50/50">
                                <label for="opcion_{{ $opc }}" class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    <span class="grid h-6 w-6 place-items-center rounded-full text-xs font-black {{ $coloresPref[$opc] }}">{{ $opc }}</span>
                                    <span>Opción {{ $opc }} (Preferencia {{ $opc }}) <span class="text-red-500">*</span></span>
                                </label>
                                <select name="opcion_{{ $opc }}" id="opcion_{{ $opc }}" required class="select-opcion w-full rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40 text-slate-800 font-medium" onchange="verificarDuplicados(this)">
                                    <option value="">Selecciona un cargo…</option>
                                    @foreach ($cargosDisponibles as $cargoNom)
                                        <option value="{{ $cargoNom }}" @selected(old('opcion_'.$opc, $defaultOpc) === $cargoNom)>
                                            {{ $cargoNom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endfor
                    </div>
                </section>

                {{-- Sección 3: Carga de Documentos y Propuestas de Trabajo (HU-046) --}}
                <section class="rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#E8F3EF] text-xs font-black text-[#064C3D]">3</span>
                            Documentos obligatorios y propuestas de trabajo
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Archivos en formato PDF o imagen de hasta 5 MB. Puedes reemplazar un archivo antes de enviar.
                        </p>
                    </div>

                    <div class="mt-5 space-y-3.5">
                        {{-- Hoja de Vida --}}
                        <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D]">
                                        <i class="fas fa-file-pdf text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Hoja de vida oficial con soportes <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-slate-400" id="txt-hv">hoja_de_vida_santiago_rojas.pdf (1.8 MB)</p>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-full bg-[#064C3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                                    <span>Reemplazar</span>
                                    <input type="file" name="archivo_hv" accept=".pdf,image/*" class="sr-only" onchange="actualizarNombre(this, 'txt-hv')">
                                </label>
                            </div>
                        </div>

                        {{-- Certificados Académicos --}}
                        <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D]">
                                        <i class="fas fa-certificate text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Certificados y soportes de cursos <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-slate-400" id="txt-cert">certificados_academicos.pdf (2.4 MB)</p>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-full bg-[#064C3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                                    <span>Reemplazar</span>
                                    <input type="file" name="archivo_certificados" accept=".pdf,image/*" class="sr-only" onchange="actualizarNombre(this, 'txt-cert')">
                                </label>
                            </div>
                        </div>

                        {{-- Propuesta de Trabajo Opción 1 --}}
                        <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                                        <i class="fas fa-file-lines text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Propuesta de trabajo · Opción 1 <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-slate-400" id="txt-prop1">propuesta_cacao_santiago.pdf (920 KB)</p>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-full bg-[#064C3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                                    <span>Reemplazar</span>
                                    <input type="file" name="archivo_propuesta_1" accept=".pdf,image/*" class="sr-only" onchange="actualizarNombre(this, 'txt-prop1')">
                                </label>
                            </div>
                        </div>

                        {{-- Propuesta de Trabajo Opción 2 --}}
                        <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                                        <i class="fas fa-file-lines text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Propuesta de trabajo · Opción 2 <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-slate-400" id="txt-prop2">propuesta_gestor_agricola.pdf (1.1 MB)</p>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-full bg-[#064C3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                                    <span>Reemplazar</span>
                                    <input type="file" name="archivo_propuesta_2" accept=".pdf,image/*" class="sr-only" onchange="actualizarNombre(this, 'txt-prop2')">
                                </label>
                            </div>
                        </div>

                        {{-- Propuesta de Trabajo Opción 3 --}}
                        <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                                        <i class="fas fa-file-lines text-lg"></i>
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Propuesta de trabajo · Opción 3 <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-slate-400" id="txt-prop3">propuesta_herramientas.pdf (840 KB)</p>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-full bg-[#064C3D] px-4 py-2 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                                    <span>Reemplazar</span>
                                    <input type="file" name="archivo_propuesta_3" accept=".pdf,image/*" class="sr-only" onchange="actualizarNombre(this, 'txt-prop3')">
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Botones de Acción --}}
                <div class="flex flex-col-reverse gap-3 pt-3 sm:flex-row sm:justify-end">
                    <button type="button" onclick="guardarBorrador()" class="rounded-full px-6 py-3 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 transition">
                        Guardar borrador y continuar después
                    </button>
                    <button type="submit" class="rounded-full bg-[#064C3D] px-8 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition transform hover:scale-[1.02]">
                        <i class="fas fa-paper-plane mr-1.5"></i> Confirmar mi postulación
                    </button>
                </div>
            </form>
        @endif

        {{-- Modal de Advertencia de Perfil Incompatible / Alterno (HU-044 esc. 3) --}}
        <dialog id="modal-perfil" class="m-auto w-[min(94vw,28rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50">
            <div class="p-6">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-xl text-amber-500 mb-3">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <h3 class="text-lg font-bold text-slate-900">Tu perfil no es el principal para este cargo</h3>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    <strong>Gestor de Talento Humano</strong> es un cargo administrativo. Tu programa de formación (Producción Agrícola) está habilitado como alterno para esta fase: puedes postularte, pero ten en cuenta que se dará prioridad a los perfiles administrativos afines.
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="cerrarModalPerfil(false)" class="rounded-full px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Elegir otro cargo
                    </button>
                    <button type="button" onclick="cerrarModalPerfil(true)" class="rounded-full bg-[#064C3D] px-5 py-2 text-xs font-bold text-white hover:bg-[#043A2F]">
                        Sí, deseo elegirlo
                    </button>
                </div>
            </div>
        </dialog>

    </div>

    @push('scripts')
    <script>
        let ultimoSelectAlterno = null;

        function verificarDuplicados(selectActual) {
            const selects = document.querySelectorAll('.select-opcion');
            const valores = [];
            let hayDuplicado = false;

            selects.forEach(s => {
                if (s.value) {
                    if (valores.includes(s.value)) {
                        hayDuplicado = true;
                    }
                    valores.push(s.value);
                }
            });

            if (hayDuplicado) {
                alert('No puede seleccionar el mismo cargo en más de una opción (RN-010).');
                selectActual.value = '';
                return;
            }

            // Advertencia para perfiles alternos (ej. Gestor de Talento Humano)
            if (selectActual.value === 'Gestor de Talento Humano') {
                ultimoSelectAlterno = selectActual;
                document.getElementById('modal-perfil').showModal();
            }
        }

        function cerrarModalPerfil(confirmado) {
            const modal = document.getElementById('modal-perfil');
            modal.close();
            if (!confirmado && ultimoSelectAlterno) {
                ultimoSelectAlterno.value = '';
            }
        }

        function actualizarNombre(input, idTexto) {
            if (input.files && input.files[0]) {
                const f = input.files[0];
                if (f.size > 5 * 1024 * 1024) {
                    alert('Solo se permiten archivos de hasta 5 MB.');
                    input.value = '';
                    return;
                }
                document.getElementById(idTexto).textContent = f.name + ' (' + (f.size / (1024*1024)).toFixed(1) + ' MB)';
                window.mostrarAviso('Archivo ' + f.name + ' cargado correctamente.');
            }
        }

        function guardarBorrador() {
            window.mostrarAviso('Borrador guardado exitosamente. Podrás finalizar tu postulación antes del 12/09/2026.', 'info');
        }
    </script>
    @endpush
</x-talentohumano::layouts.aprendiz>
