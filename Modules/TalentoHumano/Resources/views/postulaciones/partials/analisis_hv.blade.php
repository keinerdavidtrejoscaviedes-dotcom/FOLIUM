{{-- Bloque: Análisis de hoja de vida (HU-024 · RF-006) --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
            <i class="fas fa-file-signature text-emerald-600"></i>
            <span>Análisis de la hoja de vida</span>
        </h2>

        @if ($postulacion->analisisHv)
            <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-800">
                Registrado por {{ $postulacion->analisisHv->evaluador->first_name ?? 'Instructor' }} · {{ $postulacion->analisisHv->created_at->format('d/m/Y') }}
            </span>
        @else
            <span class="rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-800">
                Pendiente por registrar
            </span>
        @endif
    </div>

    {{-- Si ya tiene análisis registrado, mostrar los datos (con opción de editar) --}}
    @if ($postulacion->analisisHv)
        <dl class="mt-4 space-y-3 text-sm border-b border-slate-100 pb-4">
            <div>
                <dt class="text-xs font-semibold text-slate-400 uppercase">Formación</dt>
                <dd class="text-slate-800 font-medium mt-0.5">{{ $postulacion->analisisHv->formacion }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-400 uppercase">Habilidades</dt>
                <dd class="text-slate-800 font-medium mt-0.5">{{ $postulacion->analisisHv->habilidades }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-400 uppercase">Afinidad con los cargos postulados</dt>
                <dd class="flex flex-wrap gap-1.5 mt-1.5">
                    @if ($postulacion->analisisHv->afinidad)
                        @foreach (explode('|', $postulacion->analisisHv->afinidad) as $item)
                            <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-semibold text-[#064C3D]">
                                {{ trim($item) }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-xs text-slate-400">Sin afinidad registrada</span>
                    @endif
                </dd>
            </div>
            @if ($postulacion->analisisHv->observaciones)
                <div>
                    <dt class="text-xs font-semibold text-slate-400 uppercase">Observaciones</dt>
                    <dd class="text-slate-600 text-xs mt-0.5">{{ $postulacion->analisisHv->observaciones }}</dd>
                </div>
            @endif
        </dl>
    @endif

    {{-- Formulario para registrar o actualizar el concepto de hoja de vida (HU-024) --}}
    <form method="POST" action="{{ route('talentohumano.postulaciones.analisis', $postulacion->id) }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                {{ $postulacion->analisisHv ? 'Actualizar concepto' : 'Registrar concepto de hoja de vida' }}
            </h3>
            <p class="text-xs text-slate-500">
                Identifica la formación y habilidades del aspirante frente a los cargos para habilitar la prueba psicotécnica (HU-024).
            </p>
        </div>

        <div>
            <label for="formacion" class="mb-1 block text-xs font-semibold text-slate-700">
                Formación académica y técnica <span class="text-red-500">*</span>
            </label>
            <textarea id="formacion" name="formacion" rows="2" required placeholder="Ej: Tecnóloga en Gestión Agroempresarial, diplomados, cursos complementarios..."
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">{{ old('formacion', $postulacion->analisisHv->formacion ?? '') }}</textarea>
        </div>

        <div>
            <label for="habilidades" class="mb-1 block text-xs font-semibold text-slate-700">
                Habilidades y competencias clave <span class="text-red-500">*</span>
            </label>
            <textarea id="habilidades" name="habilidades" rows="2" required placeholder="Ej: Liderazgo, manejo de Excel, trabajo en equipo, registros..."
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40">{{ old('habilidades', $postulacion->analisisHv->habilidades ?? '') }}</textarea>
        </div>

        {{-- Nivel de afinidad por cada una de sus 3 opciones --}}
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-700">
                Afinidad frente a los cargos elegidos:
            </label>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($postulacion->opciones as $op)
                    <div class="rounded-xl bg-slate-50 p-2.5 ring-1 ring-slate-200">
                        <p class="text-[11px] font-bold text-slate-700 truncate" title="{{ $op->cargo_nombre }}">
                            {{ $op->preferencia }}. {{ $op->cargo_nombre }}
                        </p>
                        <select name="afinidades[{{ $op->id }}]"
                                class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-800 focus:border-[#064C3D] focus:ring-1 focus:ring-[#34D399]">
                            <option value="Alta">Alta afinidad</option>
                            <option value="Media" selected>Media afinidad</option>
                            <option value="Baja">Baja afinidad</option>
                        </select>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <label for="observaciones" class="mb-1 block text-xs font-semibold text-slate-700">
                Observaciones adicionales (opcional):
            </label>
            <textarea id="observaciones" name="observaciones" rows="1" placeholder="Comentarios generales sobre la postulación..."
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40">{{ old('observaciones', $postulacion->analisisHv->observaciones ?? '') }}</textarea>
        </div>

        <div class="flex justify-end pt-1">
            <button type="submit"
                    class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-[#064C3D]/20 hover:bg-[#043A2F] flex items-center gap-1.5 transition">
                <i class="fas fa-floppy-disk text-[#34D399]"></i>
                <span>Guardar concepto</span>
            </button>
        </div>
    </form>
</section>
