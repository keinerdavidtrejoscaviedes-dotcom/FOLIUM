<x-talentohumano::layouts.aprendiz title="Inicio" activo="panel" :estado="$estado" :estados="$estados">
    
    {{-- Banner de Bienvenida y Ficha --}}
    <section class="relative isolate overflow-hidden rounded-3xl bg-[#064C3D] text-white p-6 sm:p-8 mb-6 shadow-xl shadow-[#064C3D]/10">
        <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
        <i class="fas fa-leaf absolute -right-6 -bottom-6 -z-10 rotate-12 text-[10rem] text-white/5" aria-hidden="true"></i>
        
        <p class="entrada text-xs font-bold uppercase tracking-[.2em] text-[#34D399]">FOLIUM · Talento Humano</p>
        <h1 class="entrada mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">
            ¡Hola, {{ explode(' ', $aprendiz['nombre'])[0] }}!
        </h1>
        <p class="entrada mt-1.5 max-w-2xl text-sm text-emerald-50/85">
            Bienvenido a tu panel de Talento Humano en SENA Empresa. Aquí podrás consultar la convocatoria, gestionar tu postulación, presentar tus pruebas y realizar el empalme de tu cargo.
        </p>

        <div class="entrada mt-4 flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 font-semibold ring-1 ring-white/20">
                <i class="fas fa-id-badge text-[#34D399]"></i> {{ $aprendiz['nombre'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 font-semibold ring-1 ring-white/20">
                <i class="fas fa-users-rectangle text-[#34D399]"></i> Ficha {{ $aprendiz['ficha'] }} ({{ $aprendiz['programa'] }})
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#34D399] px-3 py-1 font-bold text-[#043A2F]">
                <i class="fas fa-flag"></i> {{ $aprendiz['fase'] }}
            </span>
        </div>
    </section>

    @if ($estado === 'sin-ficha')
        {{-- Caso Ficha No Habilitada (HU-043 esc. 3, HU-044 esc. 2) --}}
        <div class="entrada rounded-3xl bg-white p-8 text-center shadow-lg ring-1 ring-slate-200/70 sm:p-12">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-2xl text-[#064C3D]">
                <i class="fas fa-hourglass-half"></i>
            </span>
            <h2 class="mt-5 text-xl font-bold text-[#064C3D]">Tu ficha no participa en la convocatoria actual</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 leading-relaxed">
                Cuando el equipo de Talento Humano habilite tu ficha para la fase entrante, aquí verás disponible el cronograma, los cargos compatibles y el acceso a postularte.
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('talentohumano.aprendiz.cargo', ['estado' => 'finalizada']) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#043A2F] transition shadow-md shadow-[#064C3D]/20">
                    <i class="fas fa-clock-rotate-left"></i> Consultar mi historial anterior
                </a>
            </div>
        </div>
    @else

        {{-- Siguiente Paso según la Fase del Proceso --}}
        @if ($estado === 'postulante')
            <div class="entrada rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 mb-6">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-[.18em] text-emerald-600 flex items-center gap-1.5">
                        <i class="fas fa-compass"></i> Tu siguiente paso
                    </span>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">En plazo</span>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-[#064C3D]">Postúlate antes del 12/09/2026</h2>
                        <p class="mt-0.5 text-sm text-slate-600">Elige 3 cargos en orden de preferencia, registra tu procedencia y carga tus propuestas de trabajo.</p>
                    </div>
                    <a href="{{ route('talentohumano.aprendiz.postularme') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition transform hover:scale-[1.02]">
                        <i class="fas fa-file-signature"></i> Postularme ahora <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                {{-- Pasos del Aprendiz en la Selección (HU-043 a HU-055) --}}
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Ruta de Selección del Aprendiz</p>
                    <ol class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
                        @php
                            $pasosRuta = [
                                ['Postulación', 'fa-file-signature', true],
                                ['Análisis de Hoja de Vida', 'fa-magnifying-glass', false],
                                ['Prueba psicotécnica', 'fa-brain', false],
                                ['Entrevistas', 'fa-comments', false],
                                ['Acta de seleccionados', 'fa-user-check', false],
                                ['Empalme y recibimiento', 'fa-handshake', false],
                            ];
                        @endphp
                        @foreach ($pasosRuta as $i => [$nomPaso, $icoPaso, $esActual])
                            <li @class([
                                'flex items-center gap-2.5 rounded-2xl p-2.5 text-xs font-semibold',
                                'bg-amber-50 text-amber-900 ring-1 ring-amber-300 shadow-sm' => $esActual,
                                'bg-slate-50 text-slate-500' => !$esActual,
                            ])>
                                <span @class([
                                    'grid h-6 w-6 place-items-center rounded-full text-[11px] font-bold shrink-0',
                                    'bg-amber-400 text-amber-950' => $esActual,
                                    'bg-slate-200 text-slate-600' => !$esActual,
                                ])>
                                    {{ $i + 1 }}
                                </span>
                                <span class="truncate">{{ $nomPaso }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @elseif ($estado === 'seleccionado')
            <div class="entrada rounded-3xl bg-linear-to-r from-[#064C3D] to-[#043A2F] p-6 text-white shadow-xl shadow-[#064C3D]/20 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="grid h-16 w-16 place-items-center rounded-2xl bg-white/10 text-3xl text-[#34D399] ring-1 ring-white/20">
                        <i class="fas fa-award"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="rounded-full bg-[#34D399] px-2.5 py-0.5 text-xs font-bold text-[#043A2F] uppercase tracking-wide">
                            ¡Felicitaciones! Fuiste seleccionado
                        </span>
                        <h2 class="mt-2 text-xl font-extrabold sm:text-2xl">Líder de Unidad de Formación Productiva Cacao</h2>
                        <p class="mt-1 text-sm text-emerald-100/90 leading-relaxed">
                            Tu asignación está en estado <strong>«asignada»</strong>. Para vincularte plenamente debes completar el empalme con el aprendiz saliente (María José Ospina) y recibir el visto bueno.
                        </p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <a href="{{ route('talentohumano.aprendiz.empalme', ['estado' => 'entrante']) }}" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-full bg-[#34D399] px-6 py-3 text-sm font-bold text-[#043A2F] hover:bg-emerald-300 transition shadow-lg">
                            <i class="fas fa-handshake"></i> Ir a mi empalme <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Accesos Rápidos a Secciones del Aprendiz --}}
        <div>
            <h2 class="text-xs font-bold uppercase tracking-[.18em] text-slate-400 mb-4">Funciones de tu Rol Aprendiz</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                
                {{-- Cronograma --}}
                <a href="{{ route('talentohumano.aprendiz.cronograma') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-calendar-days"></i>
                            </span>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Publicado</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Cronograma Oficial</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Consulta las fechas aprobadas de cada etapa del proceso de selección y empalme.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Ver etapas y fechas <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                {{-- Convocatoria --}}
                <a href="{{ route('talentohumano.aprendiz.convocatoria') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-bullhorn"></i>
                            </span>
                            <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-xs font-bold text-[#064C3D]">Fase XXXVI</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Cargos Disponibles</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Explora los cargos filtrados según los tecnólogos compatibles con tu programa de formación.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Explorar cargos compatibles <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                {{-- Mi Postulación --}}
                <a href="{{ route('talentohumano.aprendiz.postulacion') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Confidencial</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Mi Postulación y Estado</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Revisa tus 3 opciones, citación a entrevistas técnica/psicológica y estado de tu resultado.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Consultar mi estado <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                {{-- Prueba Psicotécnica --}}
                <a href="{{ route('talentohumano.aprendiz.prueba') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-brain"></i>
                            </span>
                            <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800">En línea</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Prueba Psicotécnica</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Prerrequisito obligatorio para entrevistas. Presenta la prueba con guardado automático.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Acceder a la prueba <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                {{-- Acta de Seleccionados --}}
                <a href="{{ route('talentohumano.aprendiz.acta') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-user-check"></i>
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Oficial</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Acta de Seleccionados</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Consulta la lista oficial de aprendices asignados a los cargos de SENA Empresa.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Ver acta publicada <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

                {{-- Mi Empalme --}}
                <a href="{{ route('talentohumano.aprendiz.empalme') }}" class="group relative flex flex-col justify-between rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10 hover:ring-[#34D399] transition duration-300">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] group-hover:bg-[#064C3D] group-hover:text-[#34D399] transition">
                                <i class="fas fa-handshake"></i>
                            </span>
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Pendiente</span>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-slate-900 group-hover:text-[#064C3D] transition">Mi Empalme y Entrega</h3>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">Verificación de inventario, registro de visita a la unidad productiva y firma digital.</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-emerald-700">
                        Gestionar empalme <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>

            </div>
        </div>

    @endif
</x-talentohumano::layouts.aprendiz>
