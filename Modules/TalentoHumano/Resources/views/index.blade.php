@php
    // El módulo empieza en el panel; si no hay sesión, primero pasa por el login del ERP
    $ingreso = auth()->check()
        ? ['url' => route('talentohumano.panel'), 'texto' => 'Entrar a FOLIUM', 'icono' => 'fa-arrow-right']
        : ['url' => route('login', ['redirect' => route('talentohumano.panel', [], false)]), 'texto' => 'Iniciar sesión', 'icono' => 'fa-right-to-bracket'];

    $frases = [
        'Conectamos a cada aprendiz con su cargo.',
        'Selección transparente y trazable.',
        'Del papel a lo digital, fase a fase.',
    ];

    $cifras = [
        ['valor' => 103, 'texto' => 'cargos en el catálogo', 'icono' => 'fa-briefcase'],
        ['valor' => 9, 'texto' => 'áreas de SENA Empresa', 'icono' => 'fa-sitemap'],
        ['valor' => 7, 'texto' => 'etapas del proceso', 'icono' => 'fa-route'],
        ['valor' => 5, 'texto' => 'roles participantes', 'icono' => 'fa-users'],
    ];

    $funciones = [
        ['icono' => 'fa-toggle-on', 'titulo' => 'Activar los cargos de la fase', 'texto' => 'Elige las fichas que participan y activa los cargos según los tecnólogos compatibles.'],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Publicar la convocatoria', 'texto' => 'Comparte los cargos disponibles con un cronograma claro de cada etapa.'],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postularse en línea', 'texto' => 'Los aprendices eligen sus cargos por prioridad y adjuntan su hoja de vida.'],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'texto' => 'La prueba se presenta desde el sistema y los resultados quedan disponibles.'],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas y calificación', 'texto' => 'Varios evaluadores entrevistan y califican a cada aspirante con los mismos criterios.'],
        ['icono' => 'fa-user-check', 'titulo' => 'Acta de seleccionados', 'texto' => 'Se genera el acta con los seleccionados y se hacen reubicaciones cuando se necesiten.'],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalme del cargo', 'texto' => 'Acta de recibimiento, inventario del cargo y firmas digitales en un solo lugar.'],
        ['icono' => 'fa-book-open', 'titulo' => 'Catálogo de cargos', 'texto' => 'Consulta el rol, las actividades, las responsabilidades y los requisitos de cada cargo.'],
    ];

    $pasos = [
        ['icono' => 'fa-toggle-on', 'titulo' => 'Activación de cargos', 'corto' => 'Se eligen los cargos de la fase.',
         'detalle' => 'Al iniciar la fase, el instructor líder define las fichas que participan y activa los cargos del catálogo según los tecnólogos compatibles y el número de aprendices que necesita cada cargo.',
         'participan' => ['Instructor líder']],
        ['icono' => 'fa-bullhorn', 'titulo' => 'Convocatoria', 'corto' => 'Se publica con su cronograma.',
         'detalle' => 'Se publica la convocatoria con los cargos disponibles y un cronograma con las fechas de cada etapa, para que todos sepan qué viene y cuándo.',
         'participan' => ['Instructor líder', 'Instructor de Talento Humano']],
        ['icono' => 'fa-file-signature', 'titulo' => 'Postulación', 'corto' => 'Los aprendices se postulan en línea.',
         'detalle' => 'Cada aprendiz elige los cargos que le interesan en orden de prioridad, completa sus datos y adjunta su hoja de vida, todo desde el sistema.',
         'participan' => ['Aprendiz', 'Gestor de Talento Humano']],
        ['icono' => 'fa-brain', 'titulo' => 'Prueba psicotécnica', 'corto' => 'Se presenta desde el sistema.',
         'detalle' => 'Los aspirantes presentan la prueba psicotécnica en línea. El psicólogo administra el cuestionario y consulta los resultados de cada aspirante.',
         'participan' => ['Psicólogo', 'Aprendiz']],
        ['icono' => 'fa-comments', 'titulo' => 'Entrevistas', 'corto' => 'Varios evaluadores califican.',
         'detalle' => 'Se programan entrevistas con varios evaluadores, que califican a cada aspirante con criterios definidos. Todas las calificaciones quedan registradas.',
         'participan' => ['Instructor de Talento Humano', 'Psicólogo', 'Gestor de Talento Humano']],
        ['icono' => 'fa-user-check', 'titulo' => 'Selección', 'corto' => 'Se genera el acta de seleccionados.',
         'detalle' => 'Con la hoja de vida, la prueba y la entrevista se elige a la persona para cada cargo, se valida la selección y se genera el acta. Si hace falta, se reubica a un aprendiz.',
         'participan' => ['Instructor de Talento Humano', 'Instructor líder']],
        ['icono' => 'fa-handshake', 'titulo' => 'Empalme y vinculación', 'corto' => 'El cargo se entrega con acta.',
         'detalle' => 'El aprendiz saliente entrega el cargo al nuevo: se diligencia el acta de recibimiento, se revisa el inventario y todos firman digitalmente. Así queda vinculado.',
         'participan' => ['Aprendices', 'Instructor de Talento Humano']],
    ];

    $actores = [
        ['icono' => 'fa-user-tie', 'nombre' => 'Instructor líder', 'cargo' => 'Instructor líder de SENA Empresa',
         'texto' => 'Dirige el proceso en cada fase y toma las decisiones finales.',
         'acciones' => ['Activa la fase y elige las fichas participantes.', 'Activa los cargos y define cuántos aprendices necesita cada uno.', 'Publica la convocatoria y su cronograma.', 'Da el visto bueno a la selección.']],
        ['icono' => 'fa-chalkboard-user', 'nombre' => 'Instructor de TH', 'cargo' => 'Instructor de Talento Humano',
         'texto' => 'Acompaña a los aspirantes y garantiza que la selección sea justa.',
         'acciones' => ['Revisa las hojas de vida de los aspirantes.', 'Coordina las entrevistas y los evaluadores.', 'Valida la selección y las reubicaciones.', 'Acompaña el empalme y sus actas.']],
        ['icono' => 'fa-brain', 'nombre' => 'Psicólogo', 'cargo' => 'Psicólogo',
         'texto' => 'Aporta la mirada profesional sobre el perfil de cada aspirante.',
         'acciones' => ['Administra la prueba psicotécnica.', 'Consulta y analiza los resultados.', 'Participa en las entrevistas como evaluador.']],
        ['icono' => 'fa-people-group', 'nombre' => 'Gestor de TH', 'cargo' => 'Gestor de Talento Humano',
         'texto' => 'Aprendiz del área que apoya la operación del proceso.',
         'acciones' => ['Apoya la difusión de la convocatoria.', 'Orienta a los aspirantes durante la postulación.', 'Ayuda a organizar entrevistas y empalmes.']],
        ['icono' => 'fa-user-graduate', 'nombre' => 'Aprendiz', 'cargo' => 'Aprendiz',
         'texto' => 'El protagonista: encuentra el cargo donde más puede aportar.',
         'acciones' => ['Consulta los cargos y sus requisitos.', 'Se postula y adjunta su hoja de vida.', 'Presenta la prueba y la entrevista.', 'Recibe su cargo y firma el acta de empalme.']],
    ];

    $beneficios = [
        ['icono' => 'fa-leaf', 'titulo' => 'Menos papel', 'texto' => 'Formatos, actas y firmas pasan a lo digital.'],
        ['icono' => 'fa-clock-rotate-left', 'titulo' => 'Trazabilidad', 'texto' => 'Cada decisión queda registrada con fecha y responsable.'],
        ['icono' => 'fa-scale-balanced', 'titulo' => 'Transparencia', 'texto' => 'Criterios claros y los mismos para todos los aspirantes.'],
        ['icono' => 'fa-cloud', 'titulo' => 'Siempre disponible', 'texto' => 'La información de cada fase, a un clic y en cualquier momento.'],
    ];
@endphp

<x-talentohumano::layouts.master title="Inicio" :full-width="true">
    @push('styles')
        <script>document.documentElement.classList.add('js');</script>
        <style>
            .folium-dots { background-image: radial-gradient(rgba(255, 255, 255, .08) 1px, transparent 1px); background-size: 22px 22px; }
            .folium-glow { background: radial-gradient(circle at 15% 20%, rgba(52, 211, 153, .20), transparent 45%), radial-gradient(circle at 85% 75%, rgba(52, 211, 153, .12), transparent 40%); }
            .tl-fill { height: calc(var(--p, 0) * 100%); width: 100%; }
            @media (min-width: 1024px) { .tl-fill { width: calc(var(--p, 0) * 100%); height: 100%; } }
            .hoja { position: absolute; top: 105%; color: #34D399; opacity: var(--o); }
            .tilt { transition: transform .5s cubic-bezier(.2, .8, .2, 1), box-shadow .3s; transform-style: preserve-3d; }

            @media (prefers-reduced-motion: no-preference) {
                html { scroll-behavior: smooth; }
                .js [data-reveal] { opacity: 0; transform: translateY(1.5rem); transition: opacity .7s cubic-bezier(.2, .8, .2, 1), transform .7s cubic-bezier(.2, .8, .2, 1); }
                .js [data-reveal].is-visible { opacity: 1; transform: none; }
                .hoja { animation: hoja-sube var(--dur) linear var(--delay) infinite; }
                .flotar { animation: flotar 6s ease-in-out infinite; }
                .girar { animation: girar 60s linear infinite; }
                .caret { animation: parpadeo 1s steps(1) infinite; }
                .fade-in { animation: fade-in .45s cubic-bezier(.2, .8, .2, 1); }
                .entrada { animation: fade-in .8s cubic-bezier(.2, .8, .2, 1) both; }
            }
            @media (prefers-reduced-motion: reduce) {
                .folium *, .folium *::before, .folium *::after { transition-duration: .01ms !important; animation: none !important; }
            }
            @keyframes hoja-sube {
                from { transform: translate3d(0, 0, 0) rotate(0deg); }
                to { transform: translate3d(var(--dx), -130vh, 0) rotate(var(--rot)); }
            }
            @keyframes flotar { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
            @keyframes girar { to { transform: rotate(360deg); } }
            @keyframes parpadeo { 50% { opacity: 0; } }
            @keyframes fade-in { from { opacity: 0; transform: translateY(.75rem); } to { opacity: 1; transform: none; } }
            @keyframes avance { from { width: 0; } to { width: 100%; } }
        </style>
    @endpush

    <div id="inicio" class="folium overflow-x-clip">
        {{-- Barra de progreso de lectura --}}
        <div class="fixed inset-x-0 top-0 z-50 h-1" aria-hidden="true">
            <div id="progreso-lectura" class="h-full origin-left scale-x-0 bg-[#34D399]"></div>
        </div>

        {{-- 1. Portada --}}
        <section id="portada" class="relative isolate overflow-hidden bg-[#064C3D] text-white">
            <div class="folium-glow absolute inset-0 -z-10" aria-hidden="true"></div>
            <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
            <div id="hojas" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true"></div>

            <div class="mx-auto grid min-h-[86vh] max-w-6xl items-center gap-12 px-4 pb-36 pt-16 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:pt-20">
                <div>
                    <p class="entrada inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-medium tracking-wide text-emerald-100 backdrop-blur">
                        <i class="fas fa-seedling text-[#34D399]" aria-hidden="true"></i>
                        SENA Empresa · Centro de Formación Agroindustrial La Angostura
                    </p>

                    <h1 class="entrada mt-6 text-6xl font-extrabold tracking-tight sm:text-7xl lg:text-8xl" style="animation-delay: 80ms">
                        FOLIUM
                    </h1>
                    <p class="entrada mt-2 text-lg font-semibold text-emerald-100 sm:text-xl" style="animation-delay: 140ms">
                        Talento Humano · SENA Empresa
                    </p>

                    <p class="entrada mt-6 min-h-[2.5em] text-2xl font-bold leading-tight sm:min-h-[1.5em] sm:text-3xl" style="animation-delay: 200ms">
                        <span class="sr-only">{{ $frases[0] }}</span>
                        <span id="maquina" aria-hidden="true" data-frases='@json($frases)' class="text-[#34D399]">{{ $frases[0] }}</span><span class="caret ml-0.5 text-[#34D399]" aria-hidden="true">|</span>
                    </p>

                    <p class="entrada mt-5 max-w-xl text-base leading-relaxed text-emerald-50/85 sm:text-lg" style="animation-delay: 260ms">
                        El sistema que digitaliza cómo se asignan los cargos de SENA Empresa a los aprendices en cada fase:
                        desde la activación de los cargos hasta el empalme. Un proceso ordenado, trazable y transparente.
                    </p>

                    <div class="entrada mt-8 flex flex-wrap gap-3" style="animation-delay: 320ms">
                        <a href="{{ $ingreso['url'] }}"
                           class="inline-flex items-center gap-2 rounded-full bg-[#34D399] px-6 py-3 font-semibold text-[#043A2F] shadow-lg shadow-black/20 transition hover:scale-[1.03] hover:bg-emerald-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <i class="fas {{ $ingreso['icono'] }} text-sm" aria-hidden="true"></i>
                            {{ $ingreso['texto'] }}
                        </a>
                        <a href="#proceso" data-scroll
                           class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/5 px-6 py-3 font-semibold text-white backdrop-blur transition hover:scale-[1.03] hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            Conoce cómo funciona
                            <i class="fas fa-arrow-down text-sm" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                {{-- Composición visual con parallax --}}
                <div class="relative mx-auto hidden aspect-square w-full max-w-md sm:block" aria-hidden="true">
                    <div class="absolute inset-0 rounded-full border border-white/10"></div>
                    <div class="girar absolute inset-8 rounded-full border-2 border-dashed border-white/15"></div>
                    <div class="absolute inset-16 rounded-full border border-[#34D399]/25"></div>

                    <div data-depth="18" class="absolute inset-[28%] transition-transform duration-300 ease-out">
                        <div class="grid h-full w-full place-items-center rounded-full bg-white/10 shadow-2xl shadow-black/30 ring-1 ring-white/20 backdrop-blur">
                            <i class="fas fa-seedling text-7xl text-[#34D399]"></i>
                        </div>
                    </div>

                    <div data-depth="34" class="absolute left-0 top-[8%] transition-transform duration-300 ease-out">
                        <div class="flotar flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 shadow-xl ring-1 ring-white/20 backdrop-blur-md">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#34D399] text-[#043A2F]"><i class="fas fa-bullhorn"></i></span>
                            <span class="text-sm"><span class="block font-semibold">Convocatoria</span><span class="text-xs text-emerald-100">Publicada</span></span>
                        </div>
                    </div>

                    <div data-depth="48" class="absolute right-[-4%] top-[40%] transition-transform duration-300 ease-out">
                        <div class="flotar flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-slate-800 shadow-2xl" style="animation-delay: -2s">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D]"><i class="fas fa-brain"></i></span>
                            <span class="text-sm"><span class="block font-semibold">Prueba psicotécnica</span><span class="text-xs text-emerald-700"><i class="fas fa-check mr-1"></i>Completada</span></span>
                        </div>
                    </div>

                    <div data-depth="26" class="absolute bottom-[6%] left-[6%] transition-transform duration-300 ease-out">
                        <div class="flotar flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 shadow-xl ring-1 ring-white/20 backdrop-blur-md" style="animation-delay: -4s">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-white text-[#064C3D]"><i class="fas fa-signature"></i></span>
                            <span class="text-sm"><span class="block font-semibold">Acta de empalme</span><span class="text-xs text-emerald-100">Firmada por todos</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <a href="#funciones" data-scroll class="absolute bottom-24 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-xs font-medium text-emerald-100/80 transition hover:text-white md:[@media(min-height:820px)]:flex">
                Desliza
                <span class="grid h-9 w-6 place-items-start justify-center rounded-full border-2 border-white/30 pt-1.5">
                    <span class="h-2 w-1 animate-bounce rounded-full bg-[#34D399]"></span>
                </span>
            </a>
        </section>

        {{-- 2. Cifras --}}
        <section class="relative z-10 mx-auto -mt-16 max-w-6xl px-4 sm:px-6" aria-label="FOLIUM en cifras">
            <div data-reveal class="grid grid-cols-2 overflow-hidden rounded-3xl bg-white shadow-xl shadow-[#064C3D]/10 ring-1 ring-slate-200/70 lg:grid-cols-4">
                @foreach ($cifras as $cifra)
                    <div class="flex flex-col items-center gap-1 px-4 py-7 text-center sm:flex-row sm:gap-4 sm:px-6 sm:text-left {{ $loop->odd ? 'border-r border-slate-100' : '' }} {{ $loop->index < 2 ? 'border-b border-slate-100 lg:border-b-0' : '' }} {{ $loop->index === 1 ? 'lg:border-r' : '' }}">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#E8F3EF] text-[#064C3D]">
                            <i class="fas {{ $cifra['icono'] }}" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="block text-3xl font-extrabold text-[#064C3D] sm:text-4xl" data-count="{{ $cifra['valor'] }}">{{ $cifra['valor'] }}</span>
                            <span class="text-sm text-slate-500">{{ $cifra['texto'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 3. Funciones --}}
        <section id="funciones" class="scroll-mt-6 bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-600">Funcionalidades</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#064C3D] sm:text-4xl">¿Qué podrás hacer?</h2>
                    <p class="mt-4 text-slate-600">Todo el proceso de Talento Humano de SENA Empresa, de principio a fin, en un mismo lugar.</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($funciones as $funcion)
                        <div data-reveal style="transition-delay: {{ ($loop->index % 4) * 90 }}ms">
                            <article class="group relative h-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-500 hover:-translate-y-1.5 hover:border-[#34D399]/70 hover:shadow-xl hover:shadow-[#064C3D]/10">
                                <span class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-linear-to-r from-[#34D399] to-[#064C3D] transition duration-500 group-hover:scale-x-100" aria-hidden="true"></span>
                                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-lg text-[#064C3D] transition duration-500 group-hover:rotate-[-6deg] group-hover:bg-[#064C3D] group-hover:text-[#34D399]">
                                    <i class="fas {{ $funcion['icono'] }}" aria-hidden="true"></i>
                                </span>
                                <h3 class="mt-5 font-bold text-slate-900">{{ $funcion['titulo'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $funcion['texto'] }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 4. Proceso --}}
        <section id="proceso" class="scroll-mt-6 bg-[#E8F3EF]/70 py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-600">El proceso</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#064C3D] sm:text-4xl">Así funciona el proceso</h2>
                    <p class="mt-4 text-slate-600">Siete etapas, de la activación de los cargos hasta la vinculación. Selecciona una etapa para conocerla.</p>
                </div>

                <div id="linea-tiempo" class="relative mt-14">
                    <div class="absolute bottom-6 left-6 top-6 w-1 -translate-x-1/2 rounded-full bg-[#064C3D]/10 lg:bottom-auto lg:left-[7.14%] lg:right-[7.14%] lg:h-1 lg:w-auto lg:translate-x-0 lg:-translate-y-1/2" aria-hidden="true">
                        <div id="linea-relleno" class="tl-fill rounded-full bg-linear-to-b from-[#34D399] to-[#064C3D] lg:bg-linear-to-r"></div>
                    </div>

                    <div role="tablist" aria-label="Etapas del proceso" data-tabs="pasos" class="relative grid gap-5 lg:grid-cols-7 lg:gap-2">
                        @foreach ($pasos as $paso)
                            <button type="button" role="tab" id="paso-tab-{{ $loop->index }}" aria-controls="paso-panel-{{ $loop->index }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                                    class="group flex items-center gap-4 rounded-2xl text-left outline-none lg:flex-col lg:gap-3 lg:text-center focus-visible:ring-2 focus-visible:ring-[#064C3D] focus-visible:ring-offset-4 focus-visible:ring-offset-[#E8F3EF]">
                                <span class="relative z-10 grid h-12 w-12 shrink-0 place-items-center rounded-full border-2 border-[#064C3D]/20 bg-white font-bold text-[#064C3D] shadow-sm transition duration-300 group-hover:border-[#064C3D] group-aria-selected:scale-110 group-aria-selected:border-[#064C3D] group-aria-selected:bg-[#064C3D] group-aria-selected:text-white group-aria-selected:shadow-lg group-aria-selected:ring-4 group-aria-selected:ring-[#34D399]/40">
                                    {{ $loop->iteration }}
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-800 transition group-aria-selected:text-[#064C3D]">{{ $paso['titulo'] }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $paso['corto'] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-10 overflow-hidden rounded-3xl bg-white shadow-xl shadow-[#064C3D]/10 ring-1 ring-slate-200/70">
                    <div class="h-1 bg-[#E8F3EF]" aria-hidden="true"><div id="barra-auto" class="h-full w-0 bg-[#34D399]"></div></div>
                    @foreach ($pasos as $paso)
                        <div role="tabpanel" id="paso-panel-{{ $loop->index }}" aria-labelledby="paso-tab-{{ $loop->index }}" tabindex="0"
                             @if (!$loop->first) hidden @endif
                             class="grid gap-6 p-6 outline-none sm:grid-cols-[auto_1fr] sm:p-8">
                            <span class="grid h-16 w-16 place-items-center rounded-2xl bg-[#064C3D] text-2xl text-[#34D399] shadow-lg">
                                <i class="fas {{ $paso['icono'] }}" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-600">Etapa {{ $loop->iteration }} de {{ count($pasos) }}</p>
                                <h3 class="mt-1 text-2xl font-extrabold text-[#064C3D]">{{ $paso['titulo'] }}</h3>
                                <p class="mt-3 max-w-3xl leading-relaxed text-slate-600">{{ $paso['detalle'] }}</p>
                                <div class="mt-5 flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-700">Participan:</span>
                                    @foreach ($paso['participan'] as $actor)
                                        <span class="rounded-full bg-[#E8F3EF] px-3 py-1 text-xs font-semibold text-[#064C3D]">{{ $actor }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:px-8">
                        <button type="button" id="paso-anterior" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-[#064C3D] transition hover:bg-[#E8F3EF] focus-visible:outline-2 focus-visible:outline-[#064C3D]">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i> Anterior
                        </button>
                        <button type="button" id="paso-auto" aria-pressed="false" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-[#064C3D] hover:text-[#064C3D] focus-visible:outline-2 focus-visible:outline-[#064C3D]">
                            <i class="fas fa-play" aria-hidden="true"></i> <span>Reproducir</span>
                        </button>
                        <button type="button" id="paso-siguiente" class="inline-flex items-center gap-2 rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white transition hover:scale-[1.03] hover:bg-[#043A2F] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#064C3D]">
                            Siguiente <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. Actores --}}
        <section id="actores" class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-600">Participantes</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#064C3D] sm:text-4xl">¿Quiénes participan?</h2>
                    <p class="mt-4 text-slate-600">Cada persona tiene un papel claro dentro de FOLIUM.</p>
                </div>

                <div data-reveal class="mt-14 grid gap-6 lg:grid-cols-[280px_1fr]">
                    <div role="tablist" aria-label="Participantes" data-tabs="actores" class="flex flex-wrap gap-2 lg:flex-col">
                        @foreach ($actores as $actor)
                            <button type="button" role="tab" id="actor-tab-{{ $loop->index }}" aria-controls="actor-panel-{{ $loop->index }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                                    class="group flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition duration-300 hover:border-[#064C3D]/40 hover:bg-[#E8F3EF]/60 aria-selected:border-[#064C3D] aria-selected:bg-[#064C3D] aria-selected:text-white aria-selected:shadow-lg aria-selected:shadow-[#064C3D]/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#064C3D] lg:px-5 lg:py-4">
                                <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#E8F3EF] text-[#064C3D] transition group-aria-selected:bg-white/15 group-aria-selected:text-[#34D399]">
                                    <i class="fas {{ $actor['icono'] }}" aria-hidden="true"></i>
                                </span>
                                {{ $actor['nombre'] }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($actores as $actor)
                        <div role="tabpanel" id="actor-panel-{{ $loop->index }}" aria-labelledby="actor-tab-{{ $loop->index }}" tabindex="0"
                             @if (!$loop->first) hidden @endif
                             class="relative isolate overflow-hidden rounded-3xl bg-[#064C3D] p-8 text-white outline-none sm:p-10">
                            <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
                            <i class="fas {{ $actor['icono'] }} absolute -bottom-6 -right-4 -z-10 text-[10rem] text-white/5" aria-hidden="true"></i>
                            <span class="grid h-16 w-16 place-items-center rounded-2xl bg-white/10 text-2xl text-[#34D399] ring-1 ring-white/20 backdrop-blur">
                                <i class="fas {{ $actor['icono'] }}" aria-hidden="true"></i>
                            </span>
                            <h3 class="mt-6 text-2xl font-extrabold sm:text-3xl">{{ $actor['cargo'] }}</h3>
                            <p class="mt-2 max-w-xl text-emerald-50/85">{{ $actor['texto'] }}</p>
                            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach ($actor['acciones'] as $accion)
                                    <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                        <i class="fas fa-circle-check mt-0.5 text-[#34D399]" aria-hidden="true"></i>
                                        <span class="text-sm leading-relaxed">{{ $accion }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 6. Beneficios --}}
        <section class="bg-[#E8F3EF]/70 py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-600">Beneficios</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#064C3D] sm:text-4xl">¿Por qué FOLIUM?</h2>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($beneficios as $beneficio)
                        <div data-reveal style="transition-delay: {{ $loop->index * 90 }}ms">
                            <article data-tilt class="tilt h-full rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-200/70 hover:shadow-xl hover:shadow-[#064C3D]/10">
                                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-linear-to-br from-[#064C3D] to-[#043A2F] text-xl text-[#34D399] shadow-lg shadow-[#064C3D]/25">
                                    <i class="fas {{ $beneficio['icono'] }}" aria-hidden="true"></i>
                                </span>
                                <h3 class="mt-6 text-lg font-bold text-[#064C3D]">{{ $beneficio['titulo'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $beneficio['texto'] }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 7. Cierre --}}
        <section class="relative isolate overflow-hidden bg-[#064C3D] py-20 text-white sm:py-28">
            <div class="folium-glow absolute inset-0 -z-10" aria-hidden="true"></div>
            <div class="folium-dots absolute inset-0 -z-10" aria-hidden="true"></div>
            <i class="fas fa-leaf absolute -left-10 top-10 -z-10 rotate-[-25deg] text-[12rem] text-white/5" aria-hidden="true"></i>

            <div data-reveal class="mx-auto max-w-3xl px-4 text-center sm:px-6">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold text-emerald-100 ring-1 ring-white/15">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#34D399] opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-[#34D399]"></span>
                    </span>
                    Próximamente: los módulos se habilitan por etapas
                </span>
                <h2 class="mt-6 text-3xl font-extrabold tracking-tight sm:text-5xl">
                    Cada fase, un nuevo equipo.<br>
                    <span class="text-[#34D399]">Cada aprendiz, su lugar.</span>
                </h2>
                <p class="mx-auto mt-5 max-w-xl text-emerald-50/85">
                    FOLIUM acompaña a SENA Empresa para que cada cargo quede en manos de quien mejor puede aportar.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ $ingreso['url'] }}" class="inline-flex items-center gap-2 rounded-full bg-[#34D399] px-6 py-3 font-semibold text-[#043A2F] transition hover:scale-[1.03] hover:bg-emerald-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <i class="fas {{ $ingreso['icono'] }}" aria-hidden="true"></i> {{ $ingreso['texto'] }}
                    </a>
                    <button type="button" data-arriba class="inline-flex items-center gap-2 rounded-full border border-white/25 px-6 py-3 font-semibold transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        <i class="fas fa-arrow-up" aria-hidden="true"></i> Volver arriba
                    </button>
                </div>
                <p class="mt-14 text-xs text-emerald-100/60">FOLIUM · Talento Humano · SENA Empresa · Centro de Formación Agroindustrial La Angostura</p>
            </div>
        </section>

        {{-- Botón flotante para volver arriba --}}
        <button type="button" id="boton-arriba" data-arriba aria-label="Volver arriba"
                class="pointer-events-none fixed bottom-6 right-6 z-40 grid h-12 w-12 translate-y-4 place-items-center rounded-full bg-[#064C3D] text-white opacity-0 shadow-xl shadow-[#064C3D]/30 ring-2 ring-white/70 transition duration-300 hover:scale-110 hover:bg-[#043A2F] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#064C3D]">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
        </button>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const conMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                const azar = (min, max) => min + Math.random() * (max - min);

                // Aparición de elementos al hacer scroll
                function iniciarAparicion() {
                    const elementos = document.querySelectorAll('[data-reveal]');
                    if (sinMovimiento || !('IntersectionObserver' in window)) {
                        elementos.forEach((el) => el.classList.add('is-visible'));
                        return;
                    }
                    const observador = new IntersectionObserver((entradas) => {
                        entradas.forEach((entrada) => {
                            if (entrada.isIntersecting) {
                                entrada.target.classList.add('is-visible');
                                observador.unobserve(entrada.target);
                            }
                        });
                    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                    elementos.forEach((el) => observador.observe(el));
                }

                // Contadores animados
                function iniciarContadores() {
                    const contadores = document.querySelectorAll('[data-count]');
                    if (sinMovimiento || !('IntersectionObserver' in window)) return;
                    const animar = (el) => {
                        const meta = Number(el.dataset.count);
                        const duracion = 1600;
                        const inicio = performance.now();
                        const paso = (ahora) => {
                            const t = Math.min((ahora - inicio) / duracion, 1);
                            el.textContent = Math.round(meta * (1 - Math.pow(1 - t, 3))).toLocaleString('es-CO');
                            if (t < 1) requestAnimationFrame(paso);
                        };
                        requestAnimationFrame(paso);
                    };
                    const observador = new IntersectionObserver((entradas) => {
                        entradas.forEach((entrada) => {
                            if (entrada.isIntersecting) {
                                animar(entrada.target);
                                observador.unobserve(entrada.target);
                            }
                        });
                    }, { threshold: 0.6 });
                    contadores.forEach((el) => {
                        el.textContent = '0';
                        observador.observe(el);
                    });
                }

                // Frase con efecto de máquina de escribir
                function iniciarMaquina() {
                    const el = document.getElementById('maquina');
                    if (!el || sinMovimiento) return;
                    const frases = JSON.parse(el.dataset.frases);
                    let frase = 0;
                    let letras = frases[0].length;
                    let borrando = true;
                    const tick = () => {
                        const texto = frases[frase];
                        letras += borrando ? -1 : 1;
                        el.textContent = texto.slice(0, letras);
                        let espera = borrando ? 28 : 55;
                        if (!borrando && letras === texto.length) {
                            borrando = true;
                            espera = 2400;
                        } else if (borrando && letras === 0) {
                            borrando = false;
                            frase = (frase + 1) % frases.length;
                            espera = 350;
                        }
                        setTimeout(tick, espera);
                    };
                    setTimeout(tick, 2600);
                }

                // Hojas flotando en la portada
                function iniciarHojas() {
                    const contenedor = document.getElementById('hojas');
                    if (!contenedor || sinMovimiento) return;
                    for (let i = 0; i < 14; i++) {
                        const hoja = document.createElement('i');
                        const duracion = azar(16, 32);
                        hoja.className = 'fas fa-leaf hoja';
                        hoja.style.left = azar(0, 100) + '%';
                        hoja.style.fontSize = azar(12, 34) + 'px';
                        hoja.style.setProperty('--o', azar(0.06, 0.2).toFixed(2));
                        hoja.style.setProperty('--dur', duracion + 's');
                        hoja.style.setProperty('--delay', -azar(0, duracion) + 's');
                        hoja.style.setProperty('--dx', azar(-140, 140) + 'px');
                        hoja.style.setProperty('--rot', azar(180, 540) + 'deg');
                        contenedor.appendChild(hoja);
                    }
                }

                // Parallax de la composición de la portada según el mouse
                function iniciarParallax() {
                    const portada = document.getElementById('portada');
                    const capas = portada ? portada.querySelectorAll('[data-depth]') : [];
                    if (!capas.length || sinMovimiento || !conMouse) return;
                    let x = 0, y = 0, pendiente = false;
                    const aplicar = () => {
                        capas.forEach((capa) => {
                            const d = Number(capa.dataset.depth);
                            capa.style.transform = `translate3d(${x * d}px, ${y * d}px, 0)`;
                        });
                        pendiente = false;
                    };
                    portada.addEventListener('pointermove', (e) => {
                        const r = portada.getBoundingClientRect();
                        x = (e.clientX - r.left) / r.width - 0.5;
                        y = (e.clientY - r.top) / r.height - 0.5;
                        if (!pendiente) {
                            pendiente = true;
                            requestAnimationFrame(aplicar);
                        }
                    });
                    portada.addEventListener('pointerleave', () => {
                        x = 0;
                        y = 0;
                        requestAnimationFrame(aplicar);
                    });
                }

                // Barra de lectura, botón flotante y relleno de la línea de tiempo
                function iniciarScroll() {
                    const barra = document.getElementById('progreso-lectura');
                    const boton = document.getElementById('boton-arriba');
                    const linea = document.getElementById('linea-tiempo');
                    const relleno = document.getElementById('linea-relleno');
                    let pendiente = false;
                    const actualizar = () => {
                        const doc = document.documentElement;
                        const total = doc.scrollHeight - window.innerHeight;
                        barra.style.transform = `scaleX(${total > 0 ? window.scrollY / total : 0})`;

                        const visible = window.scrollY > 600;
                        boton.classList.toggle('opacity-0', !visible);
                        boton.classList.toggle('translate-y-4', !visible);
                        boton.classList.toggle('pointer-events-none', !visible);

                        const r = linea.getBoundingClientRect();
                        const p = Math.min(Math.max((window.innerHeight * 0.75 - r.top) / r.height, 0), 1);
                        relleno.style.setProperty('--p', p.toFixed(3));
                        pendiente = false;
                    };
                    window.addEventListener('scroll', () => {
                        if (!pendiente) {
                            pendiente = true;
                            requestAnimationFrame(actualizar);
                        }
                    }, { passive: true });
                    window.addEventListener('resize', actualizar, { passive: true });
                    actualizar();

                    document.querySelectorAll('[data-arriba]').forEach((b) => b.addEventListener('click', () => {
                        window.scrollTo({ top: 0, behavior: sinMovimiento ? 'auto' : 'smooth' });
                    }));
                }

                // Pestañas accesibles (flechas, Inicio y Fin)
                function iniciarPestanas(lista, alCambiar = () => {}) {
                    const pestanas = [...lista.querySelectorAll('[role="tab"]')];
                    let actual = pestanas.findIndex((p) => p.getAttribute('aria-selected') === 'true');
                    const seleccionar = (indice, porUsuario, enfocar = false) => {
                        actual = (indice + pestanas.length) % pestanas.length;
                        pestanas.forEach((p, i) => {
                            const activa = i === actual;
                            p.setAttribute('aria-selected', activa);
                            p.tabIndex = activa ? 0 : -1;
                            const panel = document.getElementById(p.getAttribute('aria-controls'));
                            panel.hidden = !activa;
                            if (activa) {
                                panel.classList.remove('fade-in');
                                void panel.offsetWidth;
                                panel.classList.add('fade-in');
                            }
                        });
                        if (enfocar) pestanas[actual].focus();
                        alCambiar(actual, porUsuario);
                    };
                    pestanas.forEach((p, i) => p.addEventListener('click', () => seleccionar(i, true)));
                    lista.addEventListener('keydown', (e) => {
                        const teclas = { ArrowRight: actual + 1, ArrowDown: actual + 1, ArrowLeft: actual - 1, ArrowUp: actual - 1, Home: 0, End: pestanas.length - 1 };
                        if (!(e.key in teclas)) return;
                        e.preventDefault();
                        seleccionar(teclas[e.key], true, true);
                    });
                    return { seleccionar, actual: () => actual };
                }

                // Línea de tiempo del proceso con reproducción automática
                function iniciarProceso() {
                    const seccion = document.getElementById('proceso');
                    const botonAuto = document.getElementById('paso-auto');
                    const barra = document.getElementById('barra-auto');
                    const intervalo = 6000;
                    let reproduciendo = !sinMovimiento;
                    let visible = false;
                    let temporizador = null;

                    const reiniciarBarra = () => {
                        barra.style.animation = 'none';
                        void barra.offsetWidth;
                        barra.style.animation = reproduciendo && visible ? `avance ${intervalo}ms linear forwards` : 'none';
                    };
                    const programar = () => {
                        clearTimeout(temporizador);
                        reiniciarBarra();
                        if (reproduciendo && visible) {
                            temporizador = setTimeout(() => pasos.seleccionar(pasos.actual() + 1, false), intervalo);
                        }
                    };
                    const pintarBoton = () => {
                        botonAuto.setAttribute('aria-pressed', reproduciendo);
                        botonAuto.querySelector('i').className = reproduciendo ? 'fas fa-pause' : 'fas fa-play';
                        botonAuto.querySelector('span').textContent = reproduciendo ? 'Pausar' : 'Reproducir';
                    };

                    const pasos = iniciarPestanas(document.querySelector('[data-tabs="pasos"]'), (indice, porUsuario) => {
                        if (porUsuario) reproduciendo = false;
                        pintarBoton();
                        programar();
                    });

                    document.getElementById('paso-anterior').addEventListener('click', () => pasos.seleccionar(pasos.actual() - 1, true));
                    document.getElementById('paso-siguiente').addEventListener('click', () => pasos.seleccionar(pasos.actual() + 1, true));
                    botonAuto.addEventListener('click', () => {
                        reproduciendo = !reproduciendo;
                        pintarBoton();
                        programar();
                    });

                    if ('IntersectionObserver' in window) {
                        new IntersectionObserver(([entrada]) => {
                            visible = entrada.isIntersecting;
                            programar();
                        }, { threshold: 0.35 }).observe(seccion);
                    }
                    pintarBoton();
                }

                // Inclinación 3D de las tarjetas de beneficios
                function iniciarInclinacion() {
                    if (sinMovimiento || !conMouse) return;
                    document.querySelectorAll('[data-tilt]').forEach((tarjeta) => {
                        tarjeta.addEventListener('pointermove', (e) => {
                            const r = tarjeta.getBoundingClientRect();
                            const px = (e.clientX - r.left) / r.width - 0.5;
                            const py = (e.clientY - r.top) / r.height - 0.5;
                            tarjeta.style.transitionDuration = '.1s';
                            tarjeta.style.transform = `perspective(900px) rotateX(${(-py * 10).toFixed(2)}deg) rotateY(${(px * 10).toFixed(2)}deg) translateY(-4px)`;
                        });
                        tarjeta.addEventListener('pointerleave', () => {
                            tarjeta.style.transitionDuration = '';
                            tarjeta.style.transform = '';
                        });
                    });
                }

                // Scroll suave de los enlaces internos
                document.querySelectorAll('[data-scroll]').forEach((enlace) => enlace.addEventListener('click', (e) => {
                    const destino = document.querySelector(enlace.getAttribute('href'));
                    if (!destino) return;
                    e.preventDefault();
                    destino.scrollIntoView({ behavior: sinMovimiento ? 'auto' : 'smooth' });
                }));

                iniciarAparicion();
                iniciarContadores();
                iniciarMaquina();
                iniciarHojas();
                iniciarParallax();
                iniciarScroll();
                iniciarPestanas(document.querySelector('[data-tabs="actores"]'));
                iniciarProceso();
                iniciarInclinacion();
            });
        </script>
    @endpush
</x-talentohumano::layouts.master>
