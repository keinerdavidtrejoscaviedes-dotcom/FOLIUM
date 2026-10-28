@props([
    'title' => 'Aprendiz',
    'activo' => 'panel',
    'p',
])
@php
    $persona = $p->persona;
    $nombre = $persona->nombre ?? auth()->user()->nickname ?? 'Aprendiz';
    $iniciales = collect(explode(' ', $nombre))->filter()->take(2)->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))->implode('');
    $empalmePendiente = $p->empalme && $p->empalme->estado !== 'cerrado';

    // Cronograma y convocatoria solo para fichas habilitadas en la convocatoria (HU-043 esc. 3, HU-044 esc. 2)
    $menu = [
        [null, [['panel', 'talentohumano.aprendiz.index', 'Inicio', 'fa-house']]],
        ['Selección', array_values(array_filter([
            $p->fichaHabilitada ? ['cronograma', 'talentohumano.aprendiz.cronograma', 'Cronograma', 'fa-calendar-days'] : null,
            $p->fichaHabilitada ? ['convocatoria', 'talentohumano.aprendiz.convocatoria', 'Convocatoria', 'fa-bullhorn'] : null,
            ['postulacion', 'talentohumano.aprendiz.postulacion', 'Mi postulación', 'fa-id-card'],
            ['prueba', 'talentohumano.aprendiz.prueba', 'Prueba psicotécnica', 'fa-brain'],
            ['acta', 'talentohumano.aprendiz.acta', 'Acta de seleccionados', 'fa-user-check'],
        ]))],
        ['Empalme', [
            ['empalme', 'talentohumano.aprendiz.empalme', 'Mi empalme', 'fa-handshake'],
            ['satisfaccion', 'talentohumano.aprendiz.satisfaccion', 'Satisfacción del empalme', 'fa-face-smile'],
        ]],
        ['Mi cargo', [['cargo', 'talentohumano.aprendiz.cargo', 'Mi cargo e historial', 'fa-id-card-clip']]],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · FOLIUM · SENA Empresa</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .folium-dots { background-image: radial-gradient(rgba(255, 255, 255, .08) 1px, transparent 1px); background-size: 22px 22px; }
        @media (prefers-reduced-motion: no-preference) {
            .entrada { animation: entrada .6s cubic-bezier(.2, .8, .2, 1) both; }
        }
        @keyframes entrada { from { opacity: 0; transform: translateY(1rem); } to { opacity: 1; transform: none; } }
        dialog::backdrop { background: rgb(15 23 42 / .5); }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">

    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#064C3D] text-white shadow-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button" id="btn-toggle-menu" class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 text-white hover:bg-white/20 lg:hidden" aria-label="Abrir menú de navegación" aria-controls="menu-lateral">
                    <i class="fas fa-bars text-lg" aria-hidden="true"></i>
                </button>
                <a href="{{ route('talentohumano.aprendiz.index') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/10 text-[#34D399] ring-1 ring-white/20"><i class="fas fa-seedling text-lg" aria-hidden="true"></i></span>
                    <span>
                        <span class="flex items-center gap-2">
                            <span class="text-base font-extrabold tracking-tight">FOLIUM</span>
                            <span class="hidden rounded-full bg-[#34D399]/20 px-2 py-0.5 text-[10px] font-bold uppercase text-[#34D399] sm:inline">Aprendiz</span>
                        </span>
                        <span class="hidden text-[11px] text-emerald-100/70 sm:block">Talento Humano · SENA Empresa</span>
                    </span>
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex items-center gap-2 rounded-full bg-white/10 p-1 ring-1 ring-white/15 sm:pr-3">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-[#34D399] text-xs font-black text-[#043A2F]" aria-hidden="true">{{ $iniciales }}</span>
                    <span class="hidden max-w-[180px] truncate text-xs font-semibold sm:inline">{{ $persona->first_name ?? $nombre }}</span>
                </div>
                <a href="{{ route('talentohumano.index') }}" class="grid h-8 w-8 place-items-center rounded-full text-emerald-100/80 hover:bg-white/10 hover:text-white" title="Ir a la presentación de FOLIUM" aria-label="Ir a la presentación de FOLIUM">
                    <i class="fas fa-right-from-bracket text-sm" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </header>

    @foreach (['error' => ['bg-red-50 text-red-900 border-red-200', 'fa-circle-exclamation text-red-600'], 'success' => ['bg-emerald-50 text-emerald-900 border-emerald-200', 'fa-circle-check text-emerald-600'], 'info' => ['bg-sky-50 text-sky-900 border-sky-200', 'fa-circle-info text-sky-600']] as $tipo => [$color, $icono])
        @if (session($tipo))
            <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
                <div class="flex items-center gap-3 rounded-2xl border p-4 text-sm font-semibold shadow-sm {{ $color }}" role="{{ $tipo === 'error' ? 'alert' : 'status' }}">
                    <i class="fas {{ $icono }}" aria-hidden="true"></i>
                    <div class="flex-1">{{ session($tipo) }}</div>
                </div>
            </div>
        @endif
    @endforeach

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        <div class="lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8">

            {{-- Menú lateral (escritorio) y cajón (celular, HU-056) --}}
            <aside id="menu-lateral" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition-transform duration-300 lg:static lg:z-auto lg:w-auto lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 lg:hidden">
                    <span class="flex items-center gap-2 text-sm font-bold text-[#064C3D]"><i class="fas fa-user-graduate" aria-hidden="true"></i> Panel del Aprendiz</span>
                    <button type="button" id="btn-cerrar-menu" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar menú"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </div>

                <div class="mb-5 mt-4 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 lg:mt-0">
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-base font-black text-[#064C3D]" aria-hidden="true">{{ $iniciales }}</span>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-sm font-extrabold text-slate-900">{{ $nombre }}</h2>
                            <p class="truncate text-xs text-slate-500">{{ $persona?->ficha ? 'Ficha ' . $persona->ficha : 'Sin ficha registrada en SICEFA' }}</p>
                        </div>
                    </div>
                    @if ($persona?->programa)
                        <p class="mt-2 text-xs font-medium text-emerald-700">{{ $persona->programa }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-1.5 border-t border-slate-100 pt-3">
                        <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-[11px] font-bold text-[#064C3D]">Aprendiz</span>
                        @if ($p->fase)
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ $p->fase->nombre_completo }}</span>
                        @endif
                    </div>
                </div>

                <nav class="space-y-6 text-sm" aria-label="Portal del Aprendiz">
                    @foreach ($menu as [$grupo, $enlaces])
                        <div>
                            @if ($grupo)
                                <p class="px-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $grupo }}</p>
                            @endif
                            <ul class="mt-2 space-y-1">
                                @foreach ($enlaces as [$clave, $ruta, $texto, $icono])
                                    <li>
                                        <a href="{{ route($ruta) }}" @if ($activo === $clave) aria-current="page" @endif
                                           class="flex items-center justify-between rounded-2xl px-4 py-2.5 font-semibold transition {{ $activo === $clave ? 'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' : 'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' }}">
                                            <span class="flex items-center gap-3">
                                                <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === $clave ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                                                {{ $texto }}
                                            </span>
                                            @if ($clave === 'empalme' && $empalmePendiente)
                                                <span class="grid h-5 w-5 place-items-center rounded-full bg-amber-400 text-[11px] font-black text-amber-950" title="Empalme pendiente">!</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div id="backdrop-menu" class="pointer-events-none fixed inset-0 z-40 bg-slate-900/50 opacity-0 transition-opacity duration-300 lg:hidden"></div>

            <section class="min-w-0">
                {{ $slot }}
            </section>
        </div>
    </main>

    <script>
        (() => {
            const menu = document.getElementById('menu-lateral');
            const fondo = document.getElementById('backdrop-menu');
            const abrir = () => { menu.classList.remove('-translate-x-full'); fondo.classList.remove('opacity-0', 'pointer-events-none'); };
            const cerrar = () => { menu.classList.add('-translate-x-full'); fondo.classList.add('opacity-0', 'pointer-events-none'); };
            document.getElementById('btn-toggle-menu')?.addEventListener('click', abrir);
            document.getElementById('btn-cerrar-menu')?.addEventListener('click', cerrar);
            fondo?.addEventListener('click', cerrar);

            // Ventanas <dialog>: data-abrir="#id" las abre y data-cerrar las cierra
            document.addEventListener('click', (e) => {
                const abre = e.target.closest('[data-abrir]');
                if (abre) document.querySelector(abre.dataset.abrir)?.showModal();
                const cierra = e.target.closest('[data-cerrar]');
                if (cierra) cierra.closest('dialog')?.close();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
