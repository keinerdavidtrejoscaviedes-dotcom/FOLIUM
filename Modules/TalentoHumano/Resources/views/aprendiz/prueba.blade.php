<x-talentohumano::layouts.aprendiz title="Prueba Psicotécnica" activo="prueba" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-2xl">
        
        {{-- Encabezado --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Prueba Psicotécnica</h1>
                <p class="mt-0.5 text-xs text-slate-500">Evaluación de competencias transversales y conductuales.</p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-bold text-[#064C3D]">
                FOLIUM Test
            </span>
        </div>

        @if ($estado === 'intro')
            {{-- Instrucciones Iniciales --}}
            <section class="entrada rounded-3xl bg-linear-to-br from-[#064C3D] to-[#043A2F] p-6 sm:p-8 text-white shadow-xl shadow-[#064C3D]/20">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] mb-4">
                    <i class="fas fa-brain"></i>
                </span>
                <h2 class="text-xl font-extrabold sm:text-2xl">Instrucciones de la Prueba</h2>
                <p class="mt-2 text-sm text-emerald-100/90 leading-relaxed">
                    Esta prueba consta de <strong>32 preguntas</strong> diseñadas por el área de Psicología. Tiempo estimado: <strong>40 minutos</strong>. Es prerrequisito obligatorio para habilitar tu citación a entrevistas.
                </p>
                
                <ul class="mt-6 space-y-2.5 text-xs text-emerald-50/90 border-t border-white/10 pt-5">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-floppy-disk text-[#34D399]"></i>
                        <span>Tus respuestas se guardan automáticamente en tiempo real. Si pierdes conexión, continuarás donde ibas.</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-mobile-screen text-[#34D399]"></i>
                        <span>Optimizado para presentar desde tu teléfono móvil o computador.</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-ban text-amber-300"></i>
                        <span>Solo se puede enviar una sola vez. No se podrá repetir ni modificar una vez enviada.</span>
                    </li>
                </ul>

                <div class="mt-8 flex justify-end">
                    <a href="?estado=curso" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-full bg-[#34D399] px-8 py-3.5 text-sm font-extrabold text-[#043A2F] hover:bg-emerald-300 transition shadow-lg">
                        Comenzar la prueba ahora <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </section>

        @elseif ($estado === 'enviada')
            {{-- Envío Exitoso (HU-047) --}}
            <section class="entrada rounded-3xl bg-white p-8 sm:p-12 text-center shadow-lg ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-3xl text-[#064C3D] mb-4">
                    <i class="fas fa-circle-check"></i>
                </span>
                <h2 class="text-2xl font-extrabold text-[#064C3D]">Prueba enviada correctamente</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 leading-relaxed">
                    28/09/2026 a las 9:12 a. m. Has completado exitosamente la prueba psicotécnica. Cumpliste el prerrequisito para ser citado a entrevista técnica y psicológica.
                </p>
                <p class="mt-3 text-xs text-slate-400">
                    El resultado será analizado directamente por el Evaluador psicológico; no se muestra en pantalla.
                </p>
                <div class="mt-6 flex justify-center">
                    <a href="{{ route('talentohumano.aprendiz.postulacion') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#043A2F] transition">
                        Ver el estado de mi postulación
                    </a>
                </div>
            </section>

        @elseif ($estado === 'ya-presentada')
            <section class="entrada rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-3xl text-[#064C3D] mb-3">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">Ya presentaste la prueba psicotécnica</h2>
                <p class="mt-1 text-sm text-slate-500">Fue registrada el 28/09/2026. Por política institucional, no es posible presentarla dos veces.</p>
            </section>

        @elseif ($estado === 'vencida')
            <section class="entrada rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-red-200">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-red-50 text-3xl text-red-500 mb-3">
                    <i class="fas fa-calendar-xmark"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">El plazo para presentar la prueba terminó el 29/09/2026</h2>
                <p class="mt-1 text-sm text-slate-500">Tu estado quedó marcado como <strong>«No evaluado»</strong> y no podrás ser citado a entrevista en esta fase.</p>
            </section>

        @elseif ($estado === 'no-postulado')
            <section class="entrada rounded-3xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-3xl text-slate-400 mb-3">
                    <i class="fas fa-lock"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">Acceso restringido a postulantes activos</h2>
                <p class="mt-1 text-sm text-slate-500">Debes tener una postulación confirmada en la fase para acceder al cuestionario.</p>
            </section>

        @else
            {{-- En Curso / Pendientes --}}
            <div class="space-y-4">
                
                {{-- Barra Pegajosa de Progreso y Guardado --}}
                <div class="sticky top-16 z-20 rounded-3xl bg-white p-4 shadow-md ring-1 ring-slate-200/80">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">
                            Pregunta <span id="num-pregunta">{{ $estado === 'pendientes' ? '14' : '9' }}</span> de 32
                        </span>
                        <span class="text-emerald-700 font-semibold flex items-center gap-1.5" id="estado-guardado">
                            <i class="fas fa-cloud-arrow-up text-xs"></i> Guardado automáticamente
                        </span>
                    </div>
                    <div class="mt-2.5 h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div id="progreso-barra" class="h-full rounded-full bg-[#064C3D] transition-all duration-300" style="width: {{ $estado === 'pendientes' ? '92%' : '28%' }};"></div>
                    </div>
                </div>

                @if ($estado === 'pendientes')
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-800 flex items-center gap-2 shadow-xs" role="alert">
                        <i class="fas fa-circle-exclamation text-red-600 text-sm"></i>
                        <span>Responda todas las preguntas antes de enviar (RN-013). Te llevamos a la primera pregunta pendiente (#14).</span>
                    </div>
                @endif

                {{-- Tarjeta de Pregunta --}}
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            {{ $estado === 'pendientes' ? 'Resolución de problemas bajo presión' : 'Trabajo en equipo y adaptabilidad' }}
                        </span>
                        <span class="text-xs text-slate-400">Selección única</span>
                    </div>

                    @if ($estado === 'pendientes')
                        <h3 class="mt-4 text-base font-bold text-slate-900 leading-snug">
                            14. Faltan dos horas para entregar el informe mensual de la unidad y se daña el equipo de cómputo. ¿Qué acción tomas primero?
                        </h3>
                        <div class="mt-5 space-y-2.5">
                            <label class="flex items-center gap-3 rounded-2xl p-4 ring-1 ring-red-200 bg-red-50/20 hover:bg-[#E8F3EF]/40 transition cursor-pointer">
                                <input type="radio" name="p14" value="A" class="h-4 w-4 accent-[#064C3D]" onchange="guardarRespuesta()">
                                <span class="text-sm font-medium text-slate-800">Aviso a mi jefe inmediato y propongo una solución alternativa</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl p-4 ring-1 ring-red-200 bg-red-50/20 hover:bg-[#E8F3EF]/40 transition cursor-pointer">
                                <input type="radio" name="p14" value="B" class="h-4 w-4 accent-[#064C3D]" onchange="guardarRespuesta()">
                                <span class="text-sm font-medium text-slate-800">Espero a que soporte técnico solucione el inconveniente</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl p-4 ring-1 ring-red-200 bg-red-50/20 hover:bg-[#E8F3EF]/40 transition cursor-pointer">
                                <input type="radio" name="p14" value="C" class="h-4 w-4 accent-[#064C3D]" onchange="guardarRespuesta()">
                                <span class="text-sm font-medium text-slate-800">Elaboro el consolidado a mano y lo radico temporalmente</span>
                            </label>
                        </div>
                    @else
                        <h3 class="mt-4 text-base font-bold text-slate-900 leading-snug">
                            9. Cuando un compañero de equipo no cumple a tiempo su parte del trabajo, hablo con él directamente para acordar una solución.
                        </h3>
                        <div class="mt-6 grid grid-cols-5 gap-2 text-center">
                            @foreach ([1 => 'Nunca', 2 => 'Pocas veces', 3 => 'A veces', 4 => 'Casi siempre', 5 => 'Siempre'] as $num => $desc)
                                <label class="group relative flex flex-col items-center justify-center rounded-2xl p-3 ring-1 ring-slate-200 hover:ring-[#064C3D] hover:bg-[#E8F3EF]/50 transition cursor-pointer has-[:checked]:bg-[#064C3D] has-[:checked]:text-white has-[:checked]:ring-[#064C3D]">
                                    <input type="radio" name="p9" value="{{ $num }}" @checked($num === 4) class="sr-only" onchange="guardarRespuesta()">
                                    <span class="text-lg font-black">{{ $num }}</span>
                                    <span class="text-[10px] opacity-75 font-medium mt-0.5">{{ $desc }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Navegación y Envío --}}
                <div class="flex items-center justify-between gap-3 pt-2">
                    <button type="button" class="rounded-full px-5 py-2.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 hover:bg-white transition">
                        <i class="fas fa-arrow-left mr-1"></i> Anterior
                    </button>

                    <div class="flex items-center gap-2">
                        @if ($estado === 'curso')
                            <a href="?estado=pendientes" class="rounded-full px-4 py-2.5 text-xs font-bold text-[#064C3D] ring-1 ring-[#064C3D]/30 hover:bg-[#E8F3EF] transition">
                                Intentar enviar
                            </a>
                        @else
                            <button type="button" onclick="document.getElementById('modal-confirmar-envio').showModal()" class="rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition shadow-md">
                                <i class="fas fa-paper-plane mr-1"></i> Finalizar y enviar prueba
                            </button>
                        @endif

                        <button type="button" class="rounded-full bg-[#064C3D] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                            Siguiente <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal de Confirmación de Envío Final --}}
            <dialog id="modal-confirmar-envio" class="m-auto w-[min(94vw,28rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50">
                <div class="p-6">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-xl text-[#064C3D] mb-3">
                        <i class="fas fa-circle-question"></i>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">¿Deseas enviar tu prueba psicotécnica?</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                        Has respondido las 32 preguntas de la prueba. Una vez confirmes el envío, el resultado quedará registrado y no podrás modificar ninguna respuesta.
                    </p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-confirmar-envio').close()" class="rounded-full px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                            Revisar respuestas
                        </button>
                        <form action="{{ route('talentohumano.aprendiz.prueba.enviar') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-full bg-[#064C3D] px-6 py-2 text-xs font-bold text-white hover:bg-[#043A2F]">
                                Sí, enviar prueba
                            </button>
                        </form>
                    </div>
                </div>
            </dialog>
        @endif

    </div>

    @push('scripts')
    <script>
        function guardarRespuesta() {
            const estadoEl = document.getElementById('estado-guardado');
            if (estadoEl) {
                estadoEl.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Guardando…';
                setTimeout(() => {
                    estadoEl.innerHTML = '<i class="fas fa-cloud-arrow-up text-xs"></i> Guardado automáticamente';
                }, 400);
            }
        }
    </script>
    @endpush
</x-talentohumano::layouts.aprendiz>
