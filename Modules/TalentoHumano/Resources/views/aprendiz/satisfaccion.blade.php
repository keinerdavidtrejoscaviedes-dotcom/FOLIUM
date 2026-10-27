<x-talentohumano::layouts.aprendiz title="Satisfacción del Empalme" activo="satisfaccion" :estado="$estado" :estados="$estados">
    <div class="mx-auto max-w-2xl">
        
        {{-- Encabezado --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-[#064C3D] sm:text-3xl">Satisfacción del Empalme</h1>
                <p class="mt-0.5 text-xs text-slate-500">
                    Líder de Unidad de Formación Productiva Cacao · Evaluación de transferencia de cargo.
                </p>
            </div>
            <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-bold text-[#064C3D]">
                HU-054
            </span>
        </div>

        @if ($estado === 'bloqueado')
            {{-- Escenario: Formulario antes de las firmas (HU-054 esc. 3) --}}
            <div class="entrada mt-8 rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-50 text-3xl text-amber-500 mb-3">
                    <i class="fas fa-lock"></i>
                </span>
                <h2 class="text-lg font-bold text-slate-800">Primero debe firmarse el acta de recibimiento</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 leading-relaxed">
                    El formulario de satisfacción se habilita únicamente cuando el acta de recibimiento ha sido firmada por todas las partes exigidas. Falta la firma del instructor de área (Jorge Iván Cuéllar).
                </p>
                <div class="mt-6">
                    <a href="{{ route('talentohumano.aprendiz.empalme', ['estado' => 'instructor']) }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                        <i class="fas fa-handshake"></i> Ver el estado de firmas en mi empalme
                    </a>
                </div>
            </div>

        @elseif ($estado === 'enviado')
            {{-- Escenario: Formulario diligenciado exitosamente (HU-054 esc. 1) --}}
            <div class="entrada mt-8 rounded-3xl bg-white p-8 sm:p-12 text-center shadow-lg ring-1 ring-slate-200/70">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[#E8F3EF] text-3xl text-[#064C3D] mb-4">
                    <i class="fas fa-face-smile"></i>
                </span>
                <h2 class="text-2xl font-extrabold text-[#064C3D]">¡Gracias por diligenciar la evaluación!</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 leading-relaxed">
                    Formulario enviado el <strong>14/01/2027 a las 11:20 a. m.</strong> Tu retroalimentación permite mejorar la transferencia de puestos en SENA Empresa.
                </p>
                <div class="mt-4 rounded-2xl bg-[#E8F3EF] p-4 text-xs font-medium text-[#064C3D]">
                    <i class="fas fa-link mr-1"></i> Una vez María José Ospina también lo envíe y el Gestor de Talento Humano dé su visto bueno, tu asignación pasará formalmente a estado <strong>«vigente»</strong>.
                </div>
                <div class="mt-6 flex justify-center">
                    <a href="{{ route('talentohumano.aprendiz.cargo') }}" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#043A2F] transition">
                        Ver mi cargo
                    </a>
                </div>
            </div>

        @else
            {{-- Formulario de 5 Preguntas + Observaciones (HU-054) --}}
            <form action="{{ route('talentohumano.aprendiz.satisfaccion.enviar') }}" method="POST" class="space-y-4">
                @csrf

                @if ($estado === 'faltantes')
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-800 flex items-center gap-2 shadow-xs" role="alert">
                        <i class="fas fa-circle-exclamation text-red-600 text-sm"></i>
                        <span>Responde las preguntas pendientes antes de enviar el formulario.</span>
                    </div>
                @endif

                @foreach ($preguntas as $i => $pregunta)
                    @php
                        $incompleta = ($estado === 'faltantes' && $i >= 3);
                    @endphp
                    <fieldset @class([
                        'rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 transition',
                        'ring-red-300 bg-red-50/20' => $incompleta,
                        'ring-slate-200/70' => !$incompleta,
                    ])>
                        <legend class="sr-only">{{ $pregunta }}</legend>
                        <p class="font-bold text-slate-800 text-sm leading-snug">
                            {{ $i + 1 }}. {{ $pregunta }}
                        </p>
                        
                        <div class="mt-4 grid grid-cols-5 gap-2 text-center">
                            @for ($val = 1; $val <= 5; $val++)
                                @php
                                    $marcado = (!$incompleta && $val === 4);
                                @endphp
                                <label class="group relative flex flex-col items-center justify-center rounded-2xl p-2.5 ring-1 ring-slate-200 hover:ring-[#064C3D] transition cursor-pointer has-[:checked]:bg-[#064C3D] has-[:checked]:text-white has-[:checked]:ring-[#064C3D]">
                                    <input type="radio" name="respuestas[{{ $i }}]" value="{{ $val }}" @checked($marcado) required class="sr-only">
                                    <span class="text-base font-black">{{ $val }}</span>
                                    <span class="text-[9px] opacity-75 font-medium mt-0.5">
                                        @if ($val === 1) Nada @elseif ($val === 5) Mucho @endif
                                    </span>
                                </label>
                            @endfor
                        </div>
                    </fieldset>
                @endforeach

                {{-- Campo de Observaciones / Pendientes --}}
                <div class="rounded-3xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-200/70">
                    <label for="pendientes" class="block font-bold text-slate-800 text-sm mb-1">
                        ¿Qué compromisos o tareas quedaron pendientes tras el empalme?
                    </label>
                    <p class="text-xs text-slate-400 mb-2">Menciona inventarios por reparar, informes por radicar o fechas clave.</p>
                    <textarea name="pendientes" id="pendientes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs sm:text-sm focus:border-[#064C3D] focus:outline-none focus:ring-2 focus:ring-[#34D399]/40" placeholder="Ej. Queda pendiente el cambio de la boquilla de la bomba de espalda #2 durante la primera semana."></textarea>
                </div>

                {{-- Botón Enviar --}}
                <div class="flex justify-end pt-2">
                    <button type="submit" class="rounded-full bg-[#064C3D] px-8 py-3 text-xs font-bold text-white shadow-lg shadow-[#064C3D]/20 hover:bg-[#043A2F] transition transform hover:scale-[1.02]">
                        <i class="fas fa-paper-plane mr-1.5"></i> Enviar formulario de satisfacción
                    </button>
                </div>
            </form>
        @endif

    </div>
</x-talentohumano::layouts.aprendiz>
