<x-talentohumano::layouts.master title="Reubicaciones · Comité">
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

    {{-- Notificaciones de éxito --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-[#E8F3EF] p-4 text-[#064C3D] flex items-center gap-3 shadow-sm">
            <i class="fas fa-circle-check text-xl text-emerald-600"></i>
            <div>
                <strong class="font-bold">¡Reubicación registrada!</strong>
                <p class="text-xs sm:text-sm mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Layout principal de 2 columnas según Mockup INS-04 --}}
    <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        {{-- Columna izquierda: Lista de Casos por reubicar --}}
        <aside class="space-y-3">
            <p class="px-1 text-xs font-bold uppercase tracking-[.18em] text-slate-400">
                Por reubicar · {{ count($aprendices ?? [1, 2, 3]) }}
            </p>

            {{-- Aprendiz 1: Óscar David Lara (Activo) --}}
            <button type="button"
                    class="block w-full rounded-2xl bg-[#064C3D] p-4 text-left text-white shadow-md shadow-[#064C3D]/20 transition ring-2 ring-[#34D399]">
                <p class="font-bold text-base">Óscar David Lara</p>
                <p class="text-xs text-emerald-100 mt-0.5">Ficha 2902230 · Producción Ganadera · 134 pts</p>
                <div class="mt-2.5 flex items-center gap-2">
                    <span class="inline-block rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-semibold text-white">
                        Caso en estudio
                    </span>
                </div>
            </button>

            {{-- Aprendiz 2: Natalia Polanía --}}
            <button type="button"
                    class="block w-full rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-200/70 hover:ring-[#34D399] transition">
                <p class="font-bold text-slate-800">Natalia Polanía</p>
                <p class="text-xs text-slate-500 mt-0.5">Ficha 2902114 · Gestión Agroempresarial · 113 pts</p>
            </button>

            {{-- Aprendiz 3: Kevin Ortiz --}}
            <button type="button"
                    class="block w-full rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-200/70 hover:ring-[#34D399] transition">
                <p class="font-bold text-slate-800">Kevin Ortiz</p>
                <p class="text-xs text-slate-500 mt-0.5">Ficha 2902187 · Producción Agrícola · 111 pts</p>
            </button>
        </aside>

        {{-- Columna derecha: Detalle del aspirante y Decisión del comité --}}
        <div class="space-y-6">
            {{-- Ficha del Aspirante Seleccionado --}}
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-[#064C3D]">Óscar David Lara</h2>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Ficha 2902230 · Producción Ganadera ·
                            <a href="{{ route('talentohumano.postulaciones.index') }}" class="font-semibold text-emerald-700 hover:underline">
                                ver ficha completa <i class="fas fa-arrow-up-right-from-square text-xs ml-0.5"></i>
                            </a>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Puntaje total</p>
                        <p class="text-3xl font-extrabold text-[#064C3D]">134</p>
                    </div>
                </div>

                {{-- Resultados de evaluaciones --}}
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <p class="text-xs text-slate-500 font-semibold">Prueba psicotécnica</p>
                        <p class="text-lg font-extrabold text-[#064C3D] mt-0.5">
                            68 <span class="text-xs font-semibold text-slate-400">/ 100</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Perfil: metódico, trabajo en equipo</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <p class="text-xs text-slate-500 font-semibold">Entrevista técnica</p>
                        <p class="text-lg font-extrabold text-[#064C3D] mt-0.5">
                            66 <span class="text-xs font-semibold text-emerald-700">· Apto</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">«Buen manejo de riego y registros»</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <p class="text-xs text-slate-500 font-semibold">Entrevista psicológica</p>
                        <p class="text-lg font-extrabold text-emerald-700 mt-0.5">
                            Apto
                        </p>
                        <p class="text-xs text-slate-500 mt-1">«Comunicación asertiva, algo tímido»</p>
                    </div>
                </div>

                {{-- Opciones previas del postulante --}}
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-500">Sus opciones (todas cubiertas por puntajes mayores):</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                            1. Gestor Pecuario
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                            2. Líder de reproducción
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                            3. Equipo de Sanidad
                        </span>
                    </div>
                </div>
            </section>

            {{-- Formulario de Decisión del Comité (Mockup INS-04) --}}
            <form action="{{ route('talentohumano.seleccion.reubicaciones.guardar') }}"
                  method="POST"
                  class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                @csrf
                <input type="hidden" name="postulacion_id" value="1">

                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-[#064C3D] text-lg">Decisión del comité</h2>
                    <span class="text-xs text-slate-400 font-medium">HU-036 · RF-011</span>
                </div>

                {{-- Selección de cargo vacante --}}
                <div class="mt-5 space-y-3">
                    <p class="text-sm font-semibold text-slate-700">
                        Cargo con cupos vacantes <span class="text-red-500">*</span>
                    </p>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-2">
                            Compatibles con Producción Ganadera
                        </p>
                        <label class="flex items-center gap-3 rounded-2xl p-3.5 ring-1 ring-slate-200 hover:bg-[#E8F3EF]/40 has-[:checked]:bg-[#E8F3EF] has-[:checked]:ring-2 has-[:checked]:ring-[#064C3D] cursor-pointer transition">
                            <input type="radio" name="cargo_id" value="mantenimiento" class="accent-[#064C3D] h-4 w-4">
                            <span class="flex-1 text-sm font-semibold text-slate-700">
                                Equipo de mantenimiento de cercos
                            </span>
                            <span class="text-xs text-slate-500 font-medium">1 cupo vacante</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                            Otros cargos vacantes
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 rounded-2xl p-3.5 ring-1 ring-slate-200 hover:bg-[#E8F3EF]/40 has-[:checked]:bg-[#E8F3EF] has-[:checked]:ring-2 has-[:checked]:ring-[#064C3D] cursor-pointer transition">
                                <input type="radio" name="cargo_id" value="riego" checked class="accent-[#064C3D] h-4 w-4">
                                <span class="flex-1 text-sm font-semibold text-slate-700">
                                    Líder de riego
                                    <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                        No compatible
                                    </span>
                                </span>
                                <span class="text-xs text-slate-500 font-medium">1 cupo vacante</span>
                            </label>

                            <label class="flex items-center gap-3 rounded-2xl p-3.5 ring-1 ring-slate-200 hover:bg-[#E8F3EF]/40 has-[:checked]:bg-[#E8F3EF] has-[:checked]:ring-2 has-[:checked]:ring-[#064C3D] cursor-pointer transition">
                                <input type="radio" name="cargo_id" value="sebi" class="accent-[#064C3D] h-4 w-4">
                                <span class="flex-1 text-sm font-semibold text-slate-700">
                                    Líder Semillero SEBI
                                    <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                        No compatible
                                    </span>
                                </span>
                                <span class="text-xs text-slate-500 font-medium">1 cupo vacante</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> Los cargos con todos sus cupos cubiertos no se muestran.
                        </p>
                    </div>
                </div>

                {{-- Participantes y Fecha --}}
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <fieldset class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                        <legend class="text-sm font-semibold text-slate-700 px-1">
                            Participantes de la reunión <span class="text-red-500">*</span>
                        </legend>
                        <div class="space-y-2 mt-2 text-sm">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="checkbox" name="participantes[]" value="Yina" checked class="accent-[#064C3D] rounded h-4 w-4">
                                <span>Yina · Líder</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="checkbox" name="participantes[]" value="Jorge Iván" checked class="accent-[#064C3D] rounded h-4 w-4">
                                <span>Jorge Iván Cuéllar · técnico</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="checkbox" name="participantes[]" value="Diana Patricia" checked class="accent-[#064C3D] rounded h-4 w-4">
                                <span>Diana Patricia Losada · técnica</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="checkbox" name="participantes[]" value="Adriana Carolina" checked class="accent-[#064C3D] rounded h-4 w-4">
                                <span>Adriana Carolina Muñoz · psicóloga</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                                <input type="checkbox" name="participantes[]" value="Luz Marina" class="accent-[#064C3D] rounded h-4 w-4">
                                <span>Luz Marina Vargas · técnica</span>
                            </label>
                        </div>
                    </fieldset>

                    <div>
                        <label for="fecha" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Fecha de la reunión <span class="text-red-500">*</span>
                        </label>
                        <input id="fecha" type="date" name="fecha" value="{{ date('Y-m-d') }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:ring-2 focus:ring-[#34D399]/40">
                        <p class="text-xs text-slate-400 mt-2">
                            Fecha en la que el comité sesionó para acordar las reubicaciones.
                        </p>
                    </div>
                </div>

                {{-- Justificación --}}
                <div class="mt-5">
                    <label for="justificacion" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Justificación del comité <span class="text-red-500">*</span>
                    </label>
                    <textarea id="justificacion" name="justificacion" rows="3" required
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40"
                              placeholder="Explique las razones técnicas y formativas de la reubicación...">Tiene experiencia en riego en la unidad de pastos; el cargo quedó vacante y la unidad lo necesita.</textarea>
                    @error('justificacion')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Botón Guardar --}}
                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="rounded-full bg-[#064C3D] px-7 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition flex items-center gap-2">
                        <i class="fas fa-floppy-disk"></i> Guardar reubicación
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-talentohumano::layouts.master>
