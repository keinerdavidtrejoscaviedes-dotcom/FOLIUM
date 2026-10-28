@php
    [$apertura, $cierre] = $fechas;
    $persona = $p->persona;
    $campo = 'w-full rounded-xl border bg-white px-3 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40';
    $borde = fn ($clave) => $errors->has($clave) ? 'border-red-400 ring-1 ring-red-200' : 'border-slate-200';
    $alternos = $cargos->where('alterno', true);
    $preferencias = [1 => 'Primera opción', 2 => 'Segunda opción', 3 => 'Tercera opción'];
@endphp

<x-talentohumano::layouts.aprendiz title="Postularme" activo="postulacion" :p="$p">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Postulación a la convocatoria</h1>
        <p class="mt-1 text-sm text-slate-600">El orden de preferencia es vinculante. Revisa bien antes de confirmar: la postulación no se puede cambiar después.</p>
    </div>

    @if (!$p->fichaHabilitada)
        @include('talentohumano::aprendiz._aviso', ['titulo' => 'Tu ficha no participa en la convocatoria actual', 'texto' => 'Solo los aprendices de las fichas habilitadas pueden postularse.'])
    @elseif (!$p->convocatoria)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-bullhorn', 'titulo' => 'La convocatoria aún no ha sido publicada'])
    @elseif ($estadoPostulacion === 'por_abrir')
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-door-closed', 'titulo' => 'La postulación abre el ' . $apertura?->format('d/m/Y'), 'enlace' => [route('talentohumano.aprendiz.convocatoria'), 'Ver los cargos disponibles']])
    @elseif ($estadoPostulacion === 'cerrada')
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-lock', 'titulo' => 'La convocatoria está cerrada', 'texto' => $cierre ? 'La postulación cerró el ' . $cierre->format('d/m/Y') . '.' : null])
    @elseif ($cargos->count() < 3)
        @include('talentohumano::aprendiz._aviso', ['icono' => 'fa-briefcase', 'titulo' => 'No hay suficientes cargos compatibles con tu programa', 'texto' => 'Para postularte debes elegir 3 cargos y la convocatoria solo tiene ' . $cargos->count() . ' compatibles con tu programa.'])
    @else
        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
                <p class="font-bold"><i class="fas fa-circle-exclamation mr-1" aria-hidden="true"></i>No se registró la postulación:</p>
                <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <p class="mt-2 text-xs">Por seguridad, vuelve a seleccionar los archivos antes de confirmar.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('talentohumano.aprendiz.postulacion.guardar') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D]">1. Tus datos</h2>
                <p class="text-xs text-slate-500">Vienen de SICEFA y no se pueden editar aquí.</p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['Nombre completo' => $persona->nombre, 'Documento' => trim(($persona->document_type ?? '') . ' ' . $persona->documento), 'Correo' => $persona->correo, 'Programa de formación' => $persona->programa, 'Ficha' => $persona->ficha] as $etiqueta => $valor)
                        <div class="rounded-xl bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">{{ $etiqueta }}</dt><dd class="font-semibold text-slate-800">{{ $valor ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="telefono" class="text-sm font-semibold text-slate-700">Teléfono de contacto *</label>
                        <input id="telefono" name="telefono" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('telefono', $persona->telefono) }}" class="mt-1 {{ $campo }} {{ $borde('telefono') }}" @error('telefono') aria-invalid="true" aria-describedby="error-telefono" @enderror>
                        @error('telefono')<p id="error-telefono" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="municipality_id" class="text-sm font-semibold text-slate-700">Municipio de procedencia *</label>
                        <select id="municipality_id" name="municipality_id" class="mt-1 {{ $campo }} {{ $borde('municipality_id') }}" @error('municipality_id') aria-invalid="true" aria-describedby="error-municipio" @enderror>
                            <option value="">Seleccione…</option>
                            @foreach ($municipios as $id => $municipio)
                                <option value="{{ $id }}" @selected(old('municipality_id') == $id)>{{ $municipio }}</option>
                            @endforeach
                        </select>
                        @error('municipality_id')<p id="error-municipio" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="vereda_ruta" class="text-sm font-semibold text-slate-700">Vereda o ruta <span class="font-normal text-slate-400">(opcional)</span></label>
                        <input id="vereda_ruta" name="vereda_ruta" value="{{ old('vereda_ruta') }}" maxlength="150" class="mt-1 {{ $campo }} border-slate-200">
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D]">2. Tus 3 cargos en orden de preferencia</h2>
                <p class="text-xs text-slate-500">Debes elegir exactamente 3 cargos distintos y cargar una propuesta de trabajo para cada uno.</p>
                @error('opciones')<p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                @error('propuestas')<p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                <div class="mt-4 space-y-4">
                    @foreach ($preferencias as $i => $etiqueta)
                        <div class="grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2">
                            <div>
                                <label for="opcion-{{ $i }}" class="text-sm font-semibold text-slate-700">{{ $etiqueta }} *</label>
                                <select id="opcion-{{ $i }}" name="opciones[{{ $i }}]" class="mt-1 {{ $campo }} {{ $borde('opciones') }}" data-opcion>
                                    <option value="">Seleccione un cargo…</option>
                                    @foreach ($cargos as $cargo)
                                        <option value="{{ $cargo->id }}" @selected(old("opciones.$i") == $cargo->id)>{{ $cargo->nombre }} · {{ $cargo->area ?? 'Sin área' }}{{ $cargo->alterno ? ' (programa alterno)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="propuesta-{{ $i }}" class="text-sm font-semibold text-slate-700">Propuesta de trabajo *</label>
                                <input id="propuesta-{{ $i }}" name="propuestas[{{ $i }}]" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#E8F3EF] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#064C3D]">
                                @error("propuestas.$i")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($alternos->isNotEmpty())
                    <fieldset class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <legend class="px-1 font-bold">Cargos con perfil alterno</legend>
                        <p class="text-xs">Tu programa no es el principal de estos cargos. Si eliges alguno, confirma que deseas postularte.</p>
                        @foreach ($alternos as $cargo)
                            <label class="mt-2 flex items-center gap-2"><input type="checkbox" name="confirmar_alterno[{{ $cargo->id }}]" value="1" @checked(old("confirmar_alterno.{$cargo->id}")) class="h-4 w-4 accent-[#064C3D]"> Confirmo mi postulación a {{ $cargo->nombre }}</label>
                        @endforeach
                    </fieldset>
                @endif
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D]">3. Documentos</h2>
                <p class="text-xs text-slate-500">PDF o imagen de hasta 5 MB. Se guardan sin acceso público. Si te equivocas, selecciona otro archivo antes de confirmar y el anterior se reemplaza.</p>
                @error('documentos')<p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($documentos as $tipo => $doc)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <label for="doc-{{ $tipo }}" class="text-sm font-semibold text-slate-700">{{ $doc['nombre'] }}{{ $tipo === 'hoja_vida' ? ' *' : '' }}</label>
                            <p class="text-xs text-slate-500">{{ $tipo === 'hoja_vida' ? 'Obligatoria para todos los cargos, en el modelo oficial.' : 'Obligatorio si eliges: ' . implode(', ', array_unique($doc['cargos'])) }}</p>
                            <input id="doc-{{ $tipo }}" name="documentos[{{ $tipo }}]" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#E8F3EF] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#064C3D]">
                            @error("documentos.$tipo")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('talentohumano.aprendiz.convocatoria') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F]"><i class="fas fa-paper-plane" aria-hidden="true"></i> Confirmar postulación</button>
            </div>
        </form>

        @push('scripts')
            <script>
                // Archivos de más de 5 MB o de otro tipo se rechazan antes de enviar (HU-046, esc. 4)
                document.querySelectorAll('input[type=file]').forEach((input) => input.addEventListener('change', () => {
                    const archivo = input.files[0];
                    const valido = !archivo || (archivo.size <= 5 * 1024 * 1024 && /\.(pdf|jpe?g|png)$/i.test(archivo.name));
                    input.setCustomValidity(valido ? '' : 'Solo se permiten archivos PDF o imagen de hasta 5 MB');
                    input.reportValidity();
                }));
            </script>
        @endpush
    @endif
</x-talentohumano::layouts.aprendiz>
