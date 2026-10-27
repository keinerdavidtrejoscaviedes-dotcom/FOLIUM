@props([
    'title' => 'Aprendiz · FOLIUM',
    'activo' => 'panel',
    'estado' => null,
    'estados' => [],
])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · FOLIUM · SENA Empresa</title>

    {{-- Tailwind CSS 4 --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('modules/talentohumano/assets/folium.css') }}">

    <style>
        .folium-dots { background-image: radial-gradient(rgba(255, 255, 255, .08) 1px, transparent 1px); background-size: 22px 22px; }
        .folium-glow { background: radial-gradient(circle at 15% 20%, rgba(52, 211, 153, .20), transparent 45%), radial-gradient(circle at 85% 75%, rgba(52, 211, 153, .12), transparent 40%); }
        @media (prefers-reduced-motion: no-preference) {
            .entrada { animation: entrada .6s cubic-bezier(.2, .8, .2, 1) both; }
            .fade-in { animation: entrada .45s cubic-bezier(.2, .8, .2, 1); }
        }
        @keyframes entrada { from { opacity: 0; transform: translateY(1rem); } to { opacity: 1; transform: none; } }
        .firma { font-family: 'Brush Script MT', 'Segoe Script', cursive; }
        dialog::backdrop { background: rgb(15 23 42 / .5); }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased font-sans" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">

    {{-- Notificación Toast Flotante --}}
    <div id="toast" class="pointer-events-none fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl bg-[#064C3D] px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-black/20 ring-1 ring-white/20 transition-all duration-300 opacity-0 translate-y-4">
        <i id="toast-icono" class="fas fa-circle-check text-[#34D399]"></i>
        <span id="toast-mensaje"></span>
    </div>

    {{-- Cabecera Principal --}}
    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#064C3D] text-white shadow-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button" id="btn-toggle-menu" class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 text-white lg:hidden hover:bg-white/20 focus:outline-none" aria-label="Abrir menú de navegación">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <a href="{{ route('talentohumano.aprendiz.index') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/10 text-[#34D399] shadow-sm ring-1 ring-white/20">
                        <i class="fas fa-seedling text-lg"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-extrabold tracking-tight">FOLIUM</span>
                            <span class="rounded-full bg-[#34D399]/20 px-2 py-0.5 text-[10px] font-bold text-[#34D399] uppercase">Aprendiz</span>
                        </div>
                        <p class="text-[11px] text-emerald-100/70 hidden sm:block">Talento Humano · SENA Empresa</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('talentohumano.index') }}" class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-emerald-100 hover:bg-white/15 hover:text-white transition">
                    <i class="fas fa-leaf text-[#34D399]"></i> Presentación
                </a>
                
                {{-- Perfil del Aprendiz --}}
                <div class="flex items-center gap-2 rounded-full bg-white/10 py-1 pl-1 pr-3 ring-1 ring-white/15">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-[#34D399] text-xs font-black text-[#043A2F]">
                        {{ auth()->user()->initials ?? 'SR' }}
                    </span>
                    <span class="text-xs font-semibold text-white truncate max-w-[120px] sm:max-w-[180px]">
                        {{ auth()->user()->person->first_name ?? (auth()->user()->name ?? 'Santiago Rojas') }}
                    </span>
                </div>

                {{-- Salir --}}
                <a href="{{ route('talentohumano.index') }}" class="grid h-8 w-8 place-items-center rounded-full text-emerald-100/80 hover:bg-white/10 hover:text-white transition" title="Salir al inicio">
                    <i class="fas fa-right-from-bracket text-sm"></i>
                </a>
            </div>
        </div>
    </header>

    {{-- Banner de Escenarios / Estados (Para verificación de HU y Mockups) --}}
    @if (!empty($estados))
    <div class="bg-[#043A2F] text-white border-b border-white/10 px-4 py-2 text-xs">
        <div class="mx-auto max-w-7xl flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="font-bold text-[#34D399] uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-vial"></i> Criterios de Aceptación / Escenario:
                </span>
                <span class="text-emerald-100/80 hidden sm:inline">Selecciona para simular las condiciones de la HU:</span>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($estados as $clave => $etiqueta)
                    <a href="?estado={{ $clave }}"
                       @class([
                           'rounded-full px-3 py-1 font-semibold transition',
                           'bg-[#34D399] text-[#043A2F] shadow-sm' => ($estado ?? array_key_first($estados)) === $clave,
                           'bg-white/10 text-white/90 hover:bg-white/20' => ($estado ?? array_key_first($estados)) !== $clave,
                       ])>
                        {{ $etiqueta }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Alerta Global si existe --}}
    @if (session('success') || session('info') || session('error'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
            <div @class([
                'flex items-center gap-3 rounded-2xl p-4 text-sm font-semibold shadow-sm',
                'bg-emerald-50 text-emerald-900 border border-emerald-200' => session('success'),
                'bg-sky-50 text-sky-900 border border-sky-200' => session('info'),
                'bg-red-50 text-red-900 border border-red-200' => session('error'),
            ]) role="alert">
                <i @class([
                    'fas',
                    'fa-circle-check text-emerald-600' => session('success'),
                    'fa-circle-info text-sky-600' => session('info'),
                    'fa-circle-exclamation text-red-600' => session('error'),
                ])></i>
                <div class="flex-1">{{ session('success') ?? session('info') ?? session('error') }}</div>
            </div>
        </div>
    @endif

    {{-- Estructura Principal con Menú Lateral --}}
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        <div class="lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8">
            
            {{-- Menú Lateral (Desktop + Drawer Móvil) --}}
            <aside id="menu-lateral" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full bg-white p-5 shadow-2xl transition-transform duration-300 lg:static lg:z-auto lg:w-auto lg:translate-x-0 lg:bg-transparent lg:p-0 lg:shadow-none">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 lg:hidden">
                    <span class="text-sm font-bold text-[#064C3D] flex items-center gap-2">
                        <i class="fas fa-user-graduate"></i> Panel del Aprendiz
                    </span>
                    <button type="button" id="btn-cerrar-menu" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                {{-- Tarjeta de Identificación del Aprendiz --}}
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 mb-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#E8F3EF] text-base font-black text-[#064C3D] shadow-inner">
                            SR
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-extrabold text-slate-900 truncate">Santiago Rojas Polanía</h2>
                            <p class="text-xs text-slate-500 truncate">Ficha 2902187</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs font-medium text-emerald-700">Gestión de la Producción Agrícola</p>
                    <div class="mt-3 flex flex-wrap gap-1.5 border-t border-slate-100 pt-3">
                        <span class="rounded-full bg-[#E8F3EF] px-2.5 py-0.5 text-[11px] font-bold text-[#064C3D]">Rol: Aprendiz</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Fase XXXVI</span>
                    </div>
                </div>

                {{-- Grupos de Enlaces --}}
                <nav class="space-y-6 text-sm">
                    {{-- General --}}
                    <div>
                        <a href="{{ route('talentohumano.aprendiz.index') }}"
                           @class([
                               'flex items-center gap-3 rounded-2xl px-4 py-3 font-semibold transition duration-200',
                               'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'panel',
                               'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'panel',
                           ])>
                            <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'panel' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                <i class="fas fa-house"></i>
                            </span>
                            <span>Inicio</span>
                        </a>
                    </div>

                    {{-- Proceso de Selección --}}
                    <div>
                        <p class="px-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Selección</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.cronograma') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'cronograma',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'cronograma',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'cronograma' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-calendar-days"></i>
                                    </span>
                                    <span>Cronograma</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.convocatoria') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'convocatoria',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'convocatoria',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'convocatoria' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-bullhorn"></i>
                                    </span>
                                    <span>Convocatoria</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.postulacion') }}"
                                   @class([
                                       'flex items-center justify-between rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'postulacion',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'postulacion',
                                   ])>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'postulacion' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                        <span>Mi postulación</span>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.prueba') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'prueba',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'prueba',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'prueba' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-brain"></i>
                                    </span>
                                    <span>Prueba psicotécnica</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.acta') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'acta',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'acta',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'acta' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-user-check"></i>
                                    </span>
                                    <span>Acta seleccionados</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Proceso de Empalme --}}
                    <div>
                        <p class="px-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Empalme</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.empalme') }}"
                                   @class([
                                       'flex items-center justify-between rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'empalme',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'empalme',
                                   ])>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'empalme' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                            <i class="fas fa-handshake"></i>
                                        </span>
                                        <span>Mi empalme</span>
                                    </div>
                                    <span class="grid h-5 w-5 place-items-center rounded-full bg-amber-400 text-[11px] font-black text-amber-950">1</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.satisfaccion') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'satisfaccion',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'satisfaccion',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'satisfaccion' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-face-smile"></i>
                                    </span>
                                    <span>Satisfacción empalme</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Cargo e Historial --}}
                    <div>
                        <p class="px-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mi cargo</p>
                        <ul class="mt-2 space-y-1">
                            <li>
                                <a href="{{ route('talentohumano.aprendiz.cargo') }}"
                                   @class([
                                       'flex items-center gap-3 rounded-2xl px-4 py-2.5 font-semibold transition duration-200',
                                       'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20' => $activo === 'cargo',
                                       'text-slate-700 hover:bg-[#E8F3EF]/60 hover:text-[#064C3D]' => $activo !== 'cargo',
                                   ])>
                                    <span class="grid h-7 w-7 place-items-center rounded-lg {{ $activo === 'cargo' ? 'bg-white/15 text-[#34D399]' : 'text-slate-500' }}">
                                        <i class="fas fa-id-card-clip"></i>
                                    </span>
                                    <span>Mi cargo e historial</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>

            {{-- Fondo oscurecido para menú móvil --}}
            <div id="backdrop-menu" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300 opacity-0 pointer-events-none lg:hidden"></div>

            {{-- Contenido de la Pantalla --}}
            <section class="min-w-0 flex-1">
                {{ $slot }}
            </section>
        </div>
    </main>

    {{-- Script del Layout --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnToggle = document.getElementById('btn-toggle-menu');
            const btnCerrar = document.getElementById('btn-cerrar-menu');
            const menu = document.getElementById('menu-lateral');
            const backdrop = document.getElementById('backdrop-menu');

            function abrirMenu() {
                menu.classList.remove('-translate-x-full');
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
            }
            function cerrarMenu() {
                menu.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }

            btnToggle?.addEventListener('click', abrirMenu);
            btnCerrar?.addEventListener('click', cerrarMenu);
            backdrop?.addEventListener('click', cerrarMenu);
        });

        // Helper global para mostrar Toast
        window.mostrarAviso = function(mensaje, tipo = 'success') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-mensaje');
            const toastIcono = document.getElementById('toast-icono');
            if (!toast) return;

            toastMsg.textContent = mensaje;
            if (tipo === 'info') {
                toastIcono.className = 'fas fa-circle-info text-sky-300';
            } else if (tipo === 'error') {
                toastIcono.className = 'fas fa-circle-xmark text-red-300';
            } else {
                toastIcono.className = 'fas fa-circle-check text-[#34D399]';
            }

            toast.classList.remove('opacity-0', 'translate-y-4');
            toast.classList.add('opacity-100', 'translate-y-0');

            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-4');
            }, 3500);
        };
    </script>
    @stack('scripts')
</body>
</html>
