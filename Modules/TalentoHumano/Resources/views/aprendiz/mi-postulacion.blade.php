<x-talentohumano::layouts.aprendiz title="Mi Postulación" activo="postulacion" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-3xl">
        
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Mi Postulación y Seguimiento</h1>
                <p class="mt-1 text-sm text-slate-600">Convocatoria Fase XXXVI · Espacio individual y confidencial.</p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3.5 py-1 text-xs font-bold text-[#064C3D]">
                <i class="fas fa-shield-halved mr-1"></i> Datos protegidos
            </span>
        </div>

        @if ($estado === 'sin-postulacion')
            {{-- Escenario: Sin postulación registrada (HU-049 esc. 4) --}}
            <div class="entrada mt-8 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-3xl text-slate-400 mb-3">
                    <i class="fas fa-id-card"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">No tienes una postulación registrada en esta convocatoria</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
                    Aún estás a tiempo de consultar los cargos disponibles y enviar tu postulación antes del cierre.
                </p>
                <div class="mt-6">
                    <a href="{{ route('talentohumano.aprendiz.convocatoria') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition">
                        <i class="fas fa-bullhorn"></i> Ver la convocatoria abierta
                    </a>
                </div>
            </div>
        @else

            {{-- Banners de Resultado Final (HU-049, HU-050) --}}
            @if ($estado === 'seleccionado')
                <section class="entrada relative isolate mt-6 overflow-hidden rounded-3xl bg-linear-to-r from-[#064C3D] to-[#043A2F] p-6 text-white shadow-xl shadow-[#064C3D]/20">
                    <div class="folium-dots absolute inset-0 -z-10"></div>
                    <span class="rounded-full bg-[#34D399] px-2.5 py-0.5 text-xs font-bold text-[#043A2F] uppercase tracking-wide">
                        ¡Seleccionado! · Acta publicada el 28/10/2026
                    </span>
                    <h2 class="mt-3 text-2xl font-extrabold sm:text-3xl">Líder de Unidad de Formación Productiva Cacao</h2>
                    <p class="mt-1.5 text-sm text-emerald-100/90 leading-relaxed">
                        Quedaste seleccionado en tu <strong>1.ª opción de cargo</strong>. Tu estado actual es <strong>«asignada»</strong>; debes completar el empalme y recibimiento de inventario para quedar vinculado formalmente en estado «vigente».
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('talentohumano.aprendiz.empalme', ['estado' => 'entrante']) }}" class="inline-flex items-center gap-2 rounded-full bg-[#34D399] px-6 py-2.5 text-sm font-bold text-[#043A2F] hover:bg-emerald-300 transition shadow-md">
                            <i class="fas fa-handshake"></i> Ir a mi empalme <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </section>
            @elseif ($estado === 'reubicado')
                <section class="entrada mt-6 rounded-3xl bg-sky-50 border border-sky-200 p-6 text-sky-950 shadow-sm">
                    <span class="rounded-full bg-sky-200 px-3 py-1 text-xs font-bold text-sky-900">
                        <i class="fas fa-arrows-rotate mr-1"></i> Reubicación por Comité
                    </span>
                    <h2 class="mt-3 text-xl font-extrabold text-sky-900">Fuiste reubicado en: Líder de Sistema de Riego</h2>
                    <p class="mt-1.5 text-sm text-sky-800 leading-relaxed">
                        No alcanzaste cupo en tus 3 opciones iniciales, pero el comité de reubicación evaluó favorablemente tu perfil y te asignó este cargo según las necesidades estratégicas de SENA Empresa. Te hemos enviado un correo con el acta de decisión.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('talentohumano.aprendiz.empalme', ['estado' => 'entrante']) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-5 py-2 text-xs font-bold text-white hover:bg-[#043A2F]">
                            Comenzar empalme en este cargo
                        </a>
                    </div>
                </section>
            @elseif ($estado === 'sin-cargo')
                <section class="entrada mt-6 rounded-3xl bg-slate-100 border border-slate-200 p-6 text-slate-800 shadow-sm">
                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
                        Sin cargo asignado
                    </span>
                    <h2 class="mt-3 text-xl font-extrabold text-slate-900">No quedaste asignado a ningún cargo en esta fase</h2>
                    <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                        El comité de selección y reubicación no encontró vacantes compatibles con cupos disponibles para esta fase. Podrás participar nuevamente en la siguiente convocatoria de tu ciclo formativo.
                    </p>
                </section>
            @endif

            {{-- Estado del Proceso (Línea de Pasos) --}}
            <section class="mt-6 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                        <i class="fas fa-route text-emerald-600"></i> Estado de tu proceso
                    </h2>
                    <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-bold text-[#064C3D]">
                        Postulado el 05/09/2026
                    </span>
                </div>

                @php
                    $nivelAvance = match($estado) {
                        'postulado' => 2,
                        'entrevistas' => 4,
                        'seleccionado', 'reubicado', 'sin-cargo' => 6,
                        default => 1,
                    };

                    $pasosSeguimiento = [
                        ['Postulación completa con documentos', '05/09/2026'],
                        ['Hoja de vida analizada por TH', '15/09/2026'],
                        ['Prueba psicotécnica presentada', '28/09/2026'],
                        ['Entrevistas técnica y psicológica programadas', '02 y 03 de octubre'],
                        ['Entrevistas realizadas y calificadas', '05/10/2026'],
                        ['Acta oficial de seleccionados publicada', '28/10/2026'],
                    ];
                @endphp

                <ol class="mt-5 space-y-3.5">
                    @foreach ($pasosSeguimiento as $idx => [$tituloPaso, $fechaPaso])
                        @php
                            $i = $idx + 1;
                            $completo = $i <= $nivelAvance;
                            $enCurso = $i === ($nivelAvance + 1);
                        @endphp
                        <li class="flex items-center gap-3">
                            <span @class([
                                'grid h-8 w-8 place-items-center rounded-full text-xs font-bold shrink-0 transition',
                                'bg-[#064C3D] text-[#34D399]' => $completo,
                                'bg-amber-100 text-amber-700 ring-2 ring-amber-300 animate-pulse' => $enCurso,
                                'bg-slate-100 text-slate-400' => !$completo && !$enCurso,
                            ])>
                                @if ($completo)
                                    <i class="fas fa-check"></i>
                                @elseif ($enCurso)
                                    <i class="fas fa-hourglass-half text-[11px]"></i>
                                @else
                                    {{ $i }}
                                @endif
                            </span>
                            <span @class([
                                'flex-1 text-sm',
                                'font-bold text-slate-800' => $completo,
                                'font-semibold text-amber-900' => $enCurso,
                                'text-slate-400' => !$completo && !$enCurso,
                            ])>
                                {{ $tituloPaso }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                @if ($completo)
                                    {{ $fechaPaso }}
                                @elseif ($enCurso)
                                    <span class="text-amber-600 font-bold">En curso</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Tus 3 Opciones Elegidas --}}
            <section class="mt-5 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="font-bold text-[#064C3D] flex items-center gap-2 mb-3">
                    <i class="fas fa-list-ol text-emerald-600"></i> Tus cargos elegidos
                </h2>
                <ol class="space-y-2 text-sm">
                    @foreach ($opciones as $opc)
                        <li class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200/60">
                            <span @class([
                                'grid h-7 w-7 place-items-center rounded-full text-xs font-black shrink-0',
                                'bg-[#064C3D] text-white' => $opc['preferencia'] === 1,
                                'bg-emerald-600 text-white' => $opc['preferencia'] === 2,
                                'bg-[#34D399] text-[#043A2F]' => $opc['preferencia'] === 3,
                            ])>
                                {{ $opc['preferencia'] }}
                            </span>
                            <div class="flex-1">
                                <span class="font-bold text-slate-800">{{ $opc['cargo'] }}</span>
                                <span class="text-xs text-slate-500 ml-2">Área {{ $opc['area'] }}</span>
                            </div>
                            @if ($opc['preferencia'] === 1 && $estado === 'seleccionado')
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                    <i class="fas fa-check mr-1"></i> Asignado
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Citación a Entrevistas (HU-048) --}}
            <section class="mt-5 rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-[#064C3D] flex items-center gap-2">
                        <i class="fas fa-calendar-check text-emerald-600"></i> Citación a Entrevistas
                    </h2>
                    @if ($estado !== 'postulado')
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Programadas</span>
                    @endif
                </div>

                @if ($estado === 'postulado')
                    <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500 flex items-center gap-2.5">
                        <i class="fas fa-calendar-xmark text-slate-400 text-base"></i>
                        <span>Aún no tienes entrevistas programadas. Se asignarán una vez presentes la prueba psicotécnica.</span>
                    </div>
                @else
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($entrevistas as $ent)
                            <div class="rounded-2xl p-4 ring-1 ring-slate-200 bg-slate-50/50">
                                <p class="font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas {{ $ent['icono'] }} {{ $ent['color'] }}"></i>
                                    {{ $ent['tipo'] }}
                                </p>
                                <div class="mt-2.5 space-y-1 text-xs text-slate-600">
                                    <p class="flex items-center gap-1.5">
                                        <i class="far fa-calendar text-slate-400"></i> {{ $ent['fecha'] }}
                                    </p>
                                    <p class="flex items-center gap-1.5 font-semibold text-slate-800">
                                        <i class="far fa-clock text-slate-400"></i> {{ $ent['hora'] }}
                                    </p>
                                    <p class="flex items-center gap-1.5">
                                        <i class="fas fa-location-dot text-slate-400"></i> {{ $ent['lugar'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <p class="mt-5 text-center text-xs text-slate-400">
                <i class="fas fa-info-circle mr-1"></i> Por confidencialidad (RN-066), las calificaciones y puntajes numéricos son de uso exclusivo del comité evaluador.
            </p>

        @endif

    </div>
</x-talentohumano::layouts.aprendiz>
