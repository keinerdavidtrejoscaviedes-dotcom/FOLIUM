<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dirección') - SENA Empresa ERP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('general/assets/img/cefaempresa.png') }}">

    <style>
        :root {
            --sena-green: #39A900;
            --sena-green-hover: #2d8500;
            --sena-neon: #62E31D;
            --sena-dark: #00131E;
            --sena-navy: #001A29;
            --sena-light-navy: #00324D;
        }

        body {
            font-family: 'Poppins', 'Nunito', sans-serif;
            background-color: #f4f7f6;
            color: #333333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .direccion-navbar {
            background: linear-gradient(135deg, var(--sena-dark) 0%, var(--sena-navy) 100%);
            border-bottom: 3px solid var(--sena-green);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            padding: 10px 0;
        }

        .logo-img {
            max-height: 38px;
            width: auto;
            background: #ffffff;
            padding: 2px;
            border-radius: 50%;
        }

        .btn-sena {
            background-color: var(--sena-green);
            color: #ffffff !important;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-sena:hover {
            background-color: var(--sena-green-hover);
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(57, 169, 0, 0.35);
        }

        .nav-link-custom {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13.5px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link-custom:hover,
        .nav-link-custom.active {
            color: var(--sena-neon);
            background: rgba(255, 255, 255, 0.08);
        }

        .card-custom {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .badge-sena-estrategico {
            background-color: rgba(57, 169, 0, 0.15);
            color: #2b8000;
            font-weight: 600;
            border: 1px solid rgba(57, 169, 0, 0.3);
        }

        .avatar-circle-sm {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--sena-green), #20c997);
            color: #ffffff;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .footer-bottom {
            background: var(--sena-dark);
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            padding: 18px 0;
            margin-top: auto;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Header Navbar -->
    <header class="direccion-navbar sticky-top">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
            
            <!-- Left Branding -->
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('direccion.welcome') }}" class="text-white text-decoration-none d-flex align-items-center gap-2">
                    <img src="{{ asset('general/assets/img/cefaempresa.png') }}" alt="SENA Empresa" class="logo-img">
                    <div>
                        <span class="fs-6 fw-bold d-block text-white">SENA EMPRESA</span>
                        <small class="text-white-50 fs-8">Procesos Estratégicos • <strong style="color: var(--sena-neon);">Dirección</strong></small>
                    </div>
                </a>
            </div>

            <!-- Middle Navigation Links -->
            <div class="d-none d-lg-flex align-items-center gap-1">
                <a href="{{ route('direccion.welcome') }}" class="nav-link-custom {{ Route::is('direccion.welcome') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> Inicio
                </a>
                <a href="{{ route('direccion.dashboard') }}" class="nav-link-custom {{ Route::is('direccion.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="{{ route('direccion.politicas.index') }}" class="nav-link-custom {{ Route::is('direccion.politicas.*') && !Route::is('direccion.politicas.create') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i> Gestión de Políticas
                </a>
                @if (Auth::check() && Auth::user()->hasSuperAdmin())
                    <a href="{{ route('talentohumano.panel') }}" class="nav-link-custom">
                        <i class="fas fa-users-cog"></i> Talento Humano
                    </a>
                @endif
            </div>

            <!-- Right Actions & User Profile Area -->
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold d-none d-sm-inline-flex align-items-center gap-1" title="Volver al Portal ERP Principal">
                    <i class="fas fa-arrow-left"></i> <span>Portal ERP</span>
                </a>

                <a href="{{ route('direccion.politicas.create') }}" class="btn btn-sm btn-sena">
                    <i class="fas fa-plus-circle"></i> <span>Nueva Política</span>
                </a>

                @auth
                    <!-- Profile & Role Badge Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark bg-opacity-50 border border-secondary border-opacity-50 text-white rounded-pill px-2 py-1 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-circle-sm">
                                {{ Auth::user()->initials }}
                            </div>
                            <div class="text-start d-none d-md-block pe-1">
                                <span class="fw-bold fs-8 text-white d-block lh-1">{{ Str::limit(Auth::user()->full_name, 18) }}</span>
                                <span class="badge bg-success text-white fs-8 p-1" style="font-size: 10px !important;">
                                    {{ Auth::user()->primary_role }}
                                </span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" style="min-width: 240px;">
                            <li class="p-2 border-bottom">
                                <div class="fw-bold text-dark fs-7">{{ Auth::user()->full_name }}</div>
                                <small class="text-muted fs-8">{{ Auth::user()->email }}</small>
                                <div class="mt-1">
                                    <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 fs-8">
                                        <i class="fas fa-user-shield me-1"></i> {{ Auth::user()->primary_role }}
                                    </span>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2 mt-1" href="{{ route('direccion.welcome') }}">
                                    <i class="fas fa-home text-success fs-6"></i>
                                    <span>Página Welcome Dirección</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2" href="{{ route('direccion.dashboard') }}">
                                    <i class="fas fa-chart-line text-primary fs-6"></i>
                                    <span>Dashboard de Dirección</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2" href="{{ route('direccion.politicas.index') }}">
                                    <i class="fas fa-list-check text-info fs-6"></i>
                                    <span>Listado de Políticas</span>
                                </a>
                            </li>
                            @if (Auth::user()->hasSuperAdmin())
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><h6 class="dropdown-header px-2 py-1 fs-8 text-uppercase">Submódulos</h6></li>
                                <li>
                                    <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2" href="{{ route('talentohumano.panel') }}">
                                        <i class="fas fa-users-cog fs-6" style="color: #064C3D;"></i>
                                        <span>Talento Humano · FOLIUM</span>
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-danger fw-semibold d-flex align-items-center gap-2" href="{{ route('logout', ['redirect' => route('direccion.welcome')]) }}">
                                    <i class="fas fa-sign-out-alt fs-6"></i>
                                    <span>Cerrar Sesión</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold">
                        <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                    </a>
                @endauth
            </div>

        </div>
    </header>

    <!-- Main Content Area -->
    <main class="py-4 flex-grow-1">
        <div class="container">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="fas fa-check-circle fs-4 text-success"></i>
                    <div><strong>¡Éxito!</strong> {{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="fas fa-info-circle fs-4 text-info"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i> Por favor corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-bottom text-center">
        <div class="container">
            <p class="mb-0">
                ERP SENA Empresa • Centro de Formación Agroindustrial "La Angostura" • Módulo de Gestión Estratégica
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
