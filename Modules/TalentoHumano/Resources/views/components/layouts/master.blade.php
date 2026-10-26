@props(['title' => null, 'fullWidth' => false])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Talento Humano' }} · FOLIUM · SENA Empresa</title>

    {{-- Tailwind CSS 4. Temporal por CDN mientras no exista el build de Vite (npm run build);
         después se reemplaza por @vite(['resources/css/app.css']) del proyecto. --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('modules/talentohumano/css/folium.css') }}">
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif">
    @if (session('success') || session('info'))
        <div class="bg-[#E8F3EF] text-sm text-[#064C3D]" role="status">
            <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-2.5">
                <i class="fas fa-circle-check" aria-hidden="true"></i> {{ session('success') ?? session('info') }}
            </div>
        </div>
    @endif
    <header class="border-b border-white/10 bg-[#064C3D] text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('talentohumano.index') }}" class="flex items-center gap-3">
                <i class="fas fa-users-cog text-2xl"></i>
                <div>
                    <p class="text-lg font-bold leading-tight">Talento Humano</p>
                    <p class="text-xs text-emerald-100">FOLIUM · SENA Empresa</p>
                </div>
            </a>
            <div class="flex items-center gap-2 text-sm sm:gap-3">
                <a href="{{ url('/') }}" class="hidden rounded-full px-3 py-1.5 text-emerald-100 hover:bg-white/10 hover:text-white sm:inline-block">
                    <i class="fas fa-th-large mr-1"></i> Inicio del ERP
                </a>
                @auth
                    <a href="{{ route('talentohumano.panel') }}" class="flex items-center gap-2 rounded-full bg-white/15 py-1 pl-1 pr-3 hover:bg-white/25">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-[#34D399] text-xs font-bold text-[#043A2F]">{{ auth()->user()->initials }}</span>
                        <span class="hidden max-w-40 truncate md:inline">{{ auth()->user()->full_name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <input type="hidden" name="redirect" value="{{ route('talentohumano.index', [], false) }}">
                        <button type="submit" class="rounded-full px-3 py-1.5 text-emerald-100 hover:bg-white/10 hover:text-white" title="Cerrar sesión">
                            <i class="fas fa-right-from-bracket"></i><span class="ml-1 hidden sm:inline">Salir</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login', ['redirect' => route('talentohumano.panel', [], false)]) }}" class="rounded-full bg-[#34D399] px-4 py-1.5 font-semibold text-[#043A2F] hover:bg-emerald-300">
                        <i class="fas fa-right-to-bracket mr-1"></i> Iniciar sesión
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main @class(['mx-auto max-w-6xl px-4 py-8' => !$fullWidth])>
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
