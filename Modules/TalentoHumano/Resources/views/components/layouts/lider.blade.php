@props(['title' => null, 'activo' => 'tablero'])
@php
    $secciones = \Modules\TalentoHumano\Http\Controllers\InstructorLiderController::SECCIONES;
    $grupos = [
        'Proceso' => ['aprobaciones', 'supervision'],
        'Gestión' => ['asignaciones', 'catalogo', 'designaciones', 'plantillas'],
        'Sistema' => ['alertas', 'auditoria'],
    ];
    $esAdministrador = \Modules\TalentoHumano\Support\AccesoTH::esAdministrador(auth()->user());
    $sinLeer = $esAdministrador ? \Modules\TalentoHumano\Entities\Alerta::sinLeer()->count() : 0;
    $designaciones = \Modules\TalentoHumano\Support\AccesoTH::designaciones(auth()->user());
    $funciones = array_keys(array_filter([
        'Administrador' => $esAdministrador,
        'Líder de la estrategia' => in_array('lider_estrategia', $designaciones),
        'Instructor de TH' => in_array('instructor_th', $designaciones),
    ]));
    $enlace = fn (bool $actual) => $actual
        ? 'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20'
        : 'text-slate-600 hover:bg-[#E8F3EF] hover:text-[#064C3D]';
@endphp

<x-talentohumano::layouts.master :title="$title" :full-width="true">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[250px_minmax(0,1fr)] lg:gap-8 lg:py-8">
        {{-- Menú del submódulo --}}
        <aside class="mb-6 lg:mb-0">
            <div class="lg:sticky lg:top-6">
                <div class="rounded-3xl bg-[#064C3D] p-5 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-[#34D399]">Submódulo</p>
                    <p class="mt-1 text-lg font-extrabold leading-tight">Instructor Líder de TH</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($funciones as $funcion)
                            <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-white/20">{{ $funcion }}</span>
                        @endforeach
                    </div>
                </div>

                <nav aria-label="Secciones del submódulo" class="-mx-4 mt-3 flex gap-1.5 overflow-x-auto px-4 pb-1 lg:mx-0 lg:mt-4 lg:block lg:space-y-5 lg:overflow-visible lg:px-0">
                    <a href="{{ route('talentohumano.lider.tablero') }}" @if ($activo === 'tablero') aria-current="page" @endif
                       class="flex shrink-0 items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $enlace($activo === 'tablero') }}">
                        <i class="fas fa-gauge-high w-4 text-center" aria-hidden="true"></i> Tablero de la fase
                    </a>

                    @foreach ($grupos as $grupo => $claves)
                        <div class="contents lg:block">
                            <p class="hidden px-4 pb-1.5 text-[11px] font-bold uppercase tracking-[.18em] text-slate-400 lg:block">{{ $grupo }}</p>
                            <div class="contents lg:block lg:space-y-1">
                                @foreach ($claves as $clave)
                                    @continue(!empty($secciones[$clave]['solo_admin']) && !$esAdministrador)
                                    <a href="{{ \Modules\TalentoHumano\Http\Controllers\InstructorLiderController::url($clave) }}" @if ($activo === $clave) aria-current="page" @endif
                                       class="flex shrink-0 items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $enlace($activo === $clave) }}">
                                        <i class="fas {{ $secciones[$clave]['icono'] }} w-4 text-center" aria-hidden="true"></i> {{ $secciones[$clave]['titulo'] }}
                                        @if ($clave === 'alertas' && $sinLeer)
                                            <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[11px] font-bold text-white" aria-label="{{ $sinLeer }} sin leer">{{ $sinLeer }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        <section class="min-w-0">
            {{ $slot }}
        </section>
    </div>

    {{-- Cuadro de confirmación: formularios con data-confirmar y enlaces con data-confirmar-salida --}}
    <dialog id="confirmacion" aria-labelledby="confirmacion-titulo"
            class="m-auto w-[min(92vw,26rem)] rounded-3xl p-0 shadow-2xl backdrop:bg-slate-900/50">
        <form method="dialog" class="p-6">
            <h2 id="confirmacion-titulo" class="text-lg font-bold text-slate-900">¿Confirmar?</h2>
            <p id="confirmacion-texto" class="mt-2 text-sm text-slate-600"></p>
            <div class="mt-6 flex justify-end gap-2">
                <button value="cancelar" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                <button value="confirmar" id="confirmacion-aceptar" class="rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Confirmar</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
            (() => {
                const dialogo = document.getElementById('confirmacion');
                const preguntar = (titulo, texto, peligro, alConfirmar) => {
                    dialogo.querySelector('#confirmacion-titulo').textContent = titulo;
                    dialogo.querySelector('#confirmacion-texto').textContent = texto;
                    dialogo.querySelector('#confirmacion-aceptar').className = 'rounded-full px-4 py-2 text-sm font-semibold text-white '
                        + (peligro ? 'bg-red-600 hover:bg-red-700' : 'bg-[#064C3D] hover:bg-[#043A2F]');
                    dialogo.onclose = () => dialogo.returnValue === 'confirmar' && alConfirmar();
                    dialogo.showModal();
                };

                document.addEventListener('submit', (e) => {
                    const form = e.target;
                    if (!form.dataset.confirmar || form.dataset.confirmado) return;
                    e.preventDefault();
                    preguntar(form.dataset.titulo || '¿Confirmar?', form.dataset.confirmar, 'peligro' in form.dataset, () => {
                        form.dataset.confirmado = '1';
                        form.requestSubmit();
                    });
                });

                // Ventanas de las secciones: [data-abrir="#id"] la abre y le pasa sus data-* (id, nombre, …);
                // el formulario con data-accion recibe la dirección con el id, y [data-campo-x] muestra el dato x.
                const abrirVentana = (ventana, datos) => {
                    const form = ventana.querySelector('form[data-accion]');
                    if (form && datos.id) {
                        form.action = form.dataset.accion.replace('__ID__', datos.id);
                        form.querySelectorAll('[data-campo-id]').forEach((c) => { c.value = datos.id; });
                    }
                    Object.entries(datos).forEach(([clave, valor]) => {
                        ventana.querySelectorAll(`[data-campo-${clave}]:not(input)`).forEach((e) => { e.textContent = valor; });
                    });
                    if (datos.marcar) ventana.querySelector(`input[value="${datos.marcar}"]`)?.click();
                    ventana.showModal();
                };
                document.addEventListener('click', (e) => {
                    const abrir = e.target.closest('[data-abrir]');
                    if (abrir) abrirVentana(document.querySelector(abrir.dataset.abrir), abrir.dataset);
                    if (e.target.closest('[data-cerrar]')) e.target.closest('dialog').close();
                });
                // Después de enviar con errores, se reabre la ventana con los datos de la fila que la abrió
                document.querySelectorAll('dialog[data-abrir-al-cargar]').forEach((ventana) => {
                    const fila = ventana.dataset.id && document.querySelector(`[data-abrir="#${ventana.id}"][data-id="${ventana.dataset.id}"]`);
                    abrirVentana(ventana, fila ? fila.dataset : ventana.dataset);
                });

                document.addEventListener('click', (e) => {
                    const enlace = e.target.closest('[data-confirmar-salida]');
                    const form = enlace && document.querySelector(enlace.dataset.confirmarSalida);
                    if (!form || !form.dataset.modificado) return;
                    e.preventDefault();
                    preguntar('¿Salir sin guardar?', 'Los datos que diligenciaste no se guardarán.', true, () => { window.location = enlace.href; });
                });
            })();
        </script>
    @endpush
</x-talentohumano::layouts.master>
