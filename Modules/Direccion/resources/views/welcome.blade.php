<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dirección y Gestión Estratégica • SENA Empresa ERP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS, Icons & FontAwesome -->
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
            --sena-navy-light: #002D44;
            --sena-card-bg: #ffffff;
        }

        body {
            font-family: 'Poppins', 'Nunito', sans-serif;
            background-color: #f8faf9;
            color: #2c3e50;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Navbar */
        .navbar-direccion {
            background: linear-gradient(135deg, var(--sena-dark) 0%, var(--sena-navy) 100%);
            border-bottom: 3px solid var(--sena-green);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            padding: 12px 0;
            transition: all 0.3s ease;
        }

        .navbar-direccion .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 14px !important;
            transition: all 0.25s ease;
            border-radius: 8px;
        }

        .navbar-direccion .nav-link:hover,
        .navbar-direccion .nav-link.active {
            color: var(--sena-neon) !important;
            background: rgba(255, 255, 255, 0.08);
        }

        .logo-img {
            max-height: 42px;
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
            padding: 9px 22px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(57, 169, 0, 0.3);
        }

        .btn-sena:hover {
            background-color: var(--sena-green-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(57, 169, 0, 0.45);
        }

        .btn-outline-sena {
            border: 2px solid var(--sena-green);
            color: #ffffff !important;
            background: transparent;
            font-weight: 600;
            border-radius: 50px;
            padding: 8px 20px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline-sena:hover {
            background: var(--sena-green);
            color: #ffffff !important;
            transform: translateY(-2px);
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--sena-green), #20c997);
            color: #ffffff;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        /* Hero Section */
        .hero-direccion {
            background: linear-gradient(135deg, rgba(0, 19, 30, 0.95) 0%, rgba(0, 45, 68, 0.92) 100%),
                        url('{{ asset("general/assets/img/cefaempresa.png") }}') center/cover no-repeat;
            color: #ffffff;
            padding: 80px 0 90px;
            position: relative;
            overflow: hidden;
        }

        .hero-direccion::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, #f8faf9, transparent);
        }

        .stat-card-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 18px;
            padding: 24px 20px;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .stat-card-glass:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.16);
            border-color: var(--sena-neon);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Feature & Policy Cards */
        .card-pillar {
            background: #ffffff;
            border-radius: 20px;
            border: none;
            padding: 30px 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.35s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-pillar:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.09);
        }

        .pillar-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: rgba(57, 169, 0, 0.12);
            color: var(--sena-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card-pillar:hover .pillar-icon-box {
            background: var(--sena-green);
            color: #ffffff;
            transform: rotate(6deg) scale(1.05);
        }

        .policy-item-card {
            background: #ffffff;
            border-radius: 16px;
            border-left: 5px solid var(--sena-green);
            padding: 22px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .policy-item-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transform: translateX(4px);
        }

        .footer-direccion {
            background: var(--sena-dark);
            color: rgba(255, 255, 255, 0.7);
            padding: 25px 0;
            font-size: 13px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="navbar-direccion sticky-top">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-dark p-0">
                <!-- Brand Logo & Title -->
                <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('direccion.welcome') }}">
                    <img src="{{ asset('general/assets/img/cefaempresa.png') }}" alt="SENA Empresa" class="logo-img">
                    <div>
                        <span class="fs-6 fw-bold d-block text-white">SENA EMPRESA</span>
                        <small class="text-white-50 fs-8">Procesos Estratégicos • <strong style="color: var(--sena-neon);">Dirección</strong></small>
                    </div>
                </a>

                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navDireccionMenu" aria-controls="navDireccionMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Nav Links & Auth Area -->
                <div class="collapse navbar-collapse" id="navDireccionMenu">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" href="#inicio"><i class="fas fa-home me-1"></i> Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pilares"><i class="fas fa-sitemap me-1"></i> Ejes Estratégicos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#politicas"><i class="fas fa-file-signature me-1"></i> Políticas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#directrices"><i class="fas fa-bullseye me-1"></i> Objetivos</a>
                        </li>
                    </ul>

                    <!-- User Authentication Area in Nav -->
                    <div class="d-flex align-items-center gap-2">
                        <!-- Link to General ERP Portal -->
                        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold text-white d-none d-md-inline-flex align-items-center gap-1" title="Volver al Portal ERP Principal">
                            <i class="fas fa-arrow-left"></i> <span>Portal ERP</span>
                        </a>

                        @guest
                            <!-- If User is NOT authenticated: Display Login Button -->
                            <a href="{{ route('login', ['redirect' => route('direccion.welcome')]) }}" class="btn btn-sm btn-sena">
                                <i class="fas fa-right-to-bracket"></i>
                                <span>Iniciar Sesión</span>
                            </a>
                        @else
                            <!-- If User IS authenticated: Display User Name, Role Badge, Dashboard & Logout -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-dark bg-opacity-50 border border-secondary border-opacity-50 text-white rounded-pill px-3 py-1 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar-circle">
                                        {{ Auth::user()->initials }}
                                    </div>
                                    <div class="text-start d-none d-sm-block">
                                        <span class="fw-bold fs-7 text-white d-block lh-1">{{ Auth::user()->full_name }}</span>
                                        <span class="badge bg-success text-white fs-8" style="background-color: var(--sena-green) !important;">
                                            <i class="fas fa-user-shield me-1"></i> {{ Auth::user()->primary_role }}
                                        </span>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" style="min-width: 250px;">
                                    <li class="p-2 border-bottom">
                                        <div class="fw-bold text-dark fs-7">{{ Auth::user()->full_name }}</div>
                                        <small class="text-muted fs-8">{{ Auth::user()->email }}</small>
                                        <div class="mt-1">
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 fs-8">
                                                <i class="fas fa-id-badge me-1"></i> Rol: {{ Auth::user()->primary_role }}
                                            </span>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2 mt-1" href="{{ route('direccion.dashboard') }}">
                                            <i class="fas fa-chart-line text-success fs-6"></i>
                                            <span>Dashboard de Dirección</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2" href="{{ route('direccion.politicas.index') }}">
                                            <i class="fas fa-file-contract text-primary fs-6"></i>
                                            <span>Gestión de Políticas (CRUD)</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 rounded-3 text-dark fw-semibold d-flex align-items-center gap-2" href="{{ route('home') }}">
                                            <i class="fas fa-grid-horizontal text-secondary fs-6"></i>
                                            <span>Módulos del ERP</span>
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

                            @if (Auth::user()->hasSuperAdmin())
                                <a href="{{ route('talentohumano.panel') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold d-none d-sm-inline-flex align-items-center gap-1" title="Submódulo de Talento Humano (FOLIUM)">
                                    <i class="fas fa-users-cog"></i> <span>Talento Humano</span>
                                </a>
                            @endif

                            <!-- Fast Access to Dashboard / Management -->
                            <a href="{{ route('direccion.dashboard') }}" class="btn btn-sm btn-sena d-none d-sm-inline-flex">
                                <i class="fas fa-sliders"></i>
                                <span>Panel de Gestión</span>
                            </a>
                        @endguest
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1" id="inicio">

        <!-- Flash messages -->
        @if(session('success'))
            <div class="container mt-4">
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-check-circle fs-4 text-success"></i>
                    <div><strong>¡Bienvenido!</strong> {{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="container mt-4">
                <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-info-circle fs-4 text-info"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            </div>
        @endif

        <!-- Hero Section -->
        <section class="hero-direccion">
            <div class="container position-relative">
                <div class="row align-items-center">
                    
                    <div class="col-lg-7 text-start mb-5 mb-lg-0">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-15 border border-white border-opacity-25 text-white mb-3 shadow-sm">
                            <i class="fas fa-chess-king" style="color: var(--sena-neon);"></i>
                            <span class="fs-7 fw-semibold">Módulo de Procesos Estratégicos</span>
                        </div>

                        <h1 class="display-4 fw-bold mb-3 text-white lh-sm">
                            Direccionamiento Estratégico y <br>
                            <span style="color: var(--sena-neon);">Gobierno Corporativo</span>
                        </h1>

                        <p class="fs-6 text-white-50 mb-4 leading-relaxed" style="max-width: 600px;">
                            Formulación, seguimiento y control de directrices institucionales, políticas de calidad, seguridad y normas operativas que rigen a los procesos de SENA Empresa en el Centro Agroindustrial <strong>"La Angostura"</strong>.
                        </p>

                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            @auth
                                <a href="{{ route('direccion.dashboard') }}" class="btn btn-sena btn-lg px-4 py-3">
                                    <i class="fas fa-chart-pie me-1"></i> Ir al Dashboard Ejecutivo
                                </a>
                                <a href="{{ route('direccion.politicas.index') }}" class="btn btn-outline-sena btn-lg px-4 py-3">
                                    <i class="fas fa-list-check me-1"></i> Administrar Políticas
                                </a>
                            @else
                                <a href="{{ route('login', ['redirect' => route('direccion.dashboard')]) }}" class="btn btn-sena btn-lg px-4 py-3">
                                    <i class="fas fa-right-to-bracket me-1"></i> Iniciar Sesión en el ERP
                                </a>
                                <a href="#politicas" class="btn btn-outline-sena btn-lg px-4 py-3">
                                    <i class="fas fa-eye me-1"></i> Ver Políticas Públicas
                                </a>
                            @endauth
                        </div>
                    </div>

                    <!-- Right Column: Live Strategic Metrics -->
                    <div class="col-lg-5">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card-glass text-center">
                                    <div class="fs-1 fw-bold text-white mb-1">{{ $totalPoliticas }}</div>
                                    <div class="fs-7 text-white-50 text-uppercase fw-semibold">Total Políticas</div>
                                    <i class="fas fa-file-contract fs-4 mt-2 opacity-50"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card-glass text-center" style="border-color: var(--sena-neon);">
                                    <div class="fs-1 fw-bold mb-1" style="color: var(--sena-neon);">{{ $activas }}</div>
                                    <div class="fs-7 text-white-50 text-uppercase fw-semibold">Políticas Activas</div>
                                    <i class="fas fa-check-circle fs-4 mt-2" style="color: var(--sena-neon);"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card-glass text-center">
                                    <div class="fs-1 fw-bold text-warning mb-1">{{ $enRevision }}</div>
                                    <div class="fs-7 text-white-50 text-uppercase fw-semibold">En Revisión</div>
                                    <i class="fas fa-clock fs-4 mt-2 text-warning opacity-75"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card-glass text-center">
                                    <div class="fs-1 fw-bold text-info mb-1">100%</div>
                                    <div class="fs-7 text-white-50 text-uppercase fw-semibold">Alineación ERP</div>
                                    <i class="fas fa-shield-alt fs-4 mt-2 text-info opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Strategic Pillars Section -->
        <section class="py-5" id="pilares">
            <div class="container py-4">
                <div class="text-center mb-5">
                    <span class="badge px-3 py-2 rounded-pill fs-7 fw-bold mb-2" style="background: rgba(57, 169, 0, 0.15); color: var(--sena-green);">Ejes Fundamentales</span>
                    <h2 class="fw-bold text-dark fs-2">Pilares de la Gestión de Dirección</h2>
                    <p class="text-muted fs-6" style="max-width: 650px; margin: 0 auto;">
                        Estructura integral para garantizar la excelencia académica y productiva en cada unidad didáctica.
                    </p>
                </div>

                <div class="row g-4">
                    <!-- Pillar 1 -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card-pillar">
                            <div class="pillar-icon-box">
                                <i class="fas fa-landmark"></i>
                            </div>
                            <h4 class="fw-bold text-dark fs-5 mb-2">Gobierno y Liderazgo</h4>
                            <p class="text-muted fs-6 mb-0 flex-grow-1">
                                Establecimiento de la visión gerencial, roles de instructores líderes y aprendices administradores en cada turno rotativo.
                            </p>
                        </div>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card-pillar">
                            <div class="pillar-icon-box">
                                <i class="fas fa-award"></i>
                            </div>
                            <h4 class="fw-bold text-dark fs-5 mb-2">Calidad e Inocuidad</h4>
                            <p class="text-muted fs-6 mb-0 flex-grow-1">
                                Garantía de estándares BPM, trazabilidad en derivados lácteos, cárnicos, café y productos agrícolas del centro.
                            </p>
                        </div>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card-pillar">
                            <div class="pillar-icon-box">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h4 class="fw-bold text-dark fs-5 mb-2">Sostenibilidad</h4>
                            <p class="text-muted fs-6 mb-0 flex-grow-1">
                                Gestión ambientalmente responsable y optimización de insumos en los procesos agropecuarios de La Angostura.
                            </p>
                        </div>
                    </div>

                    <!-- Pillar 4 -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card-pillar">
                            <div class="pillar-icon-box">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4 class="fw-bold text-dark fs-5 mb-2">Mejora Continua</h4>
                            <p class="text-muted fs-6 mb-0 flex-grow-1">
                                Evaluación sistemática de indicadores de gestión, cumplimiento de metas de producción y satisfacción de clientes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Institutional Policies Showcase Section -->
        <section class="py-5 bg-white border-top border-bottom" id="politicas">
            <div class="container py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <span class="badge px-3 py-2 rounded-pill fs-7 fw-bold mb-1" style="background: rgba(0, 35, 54, 0.1); color: var(--sena-navy);">Lineamientos Institucionales</span>
                        <h2 class="fw-bold text-dark fs-2 mb-0">Políticas y Directrices Registradas</h2>
                    </div>
                    <div>
                        @auth
                            <a href="{{ route('direccion.politicas.index') }}" class="btn btn-sena">
                                <i class="fas fa-arrow-right me-1"></i> Ver Todas las Políticas
                            </a>
                        @else
                            <a href="{{ route('login', ['redirect' => route('direccion.politicas.index')]) }}" class="btn btn-sena">
                                <i class="fas fa-lock me-1"></i> Iniciar Sesión para Gestionar
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="row g-4">
                    @forelse($politicasDestacadas as $pol)
                        <div class="col-lg-6">
                            <div class="policy-item-card h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-dark text-white rounded-pill px-3 py-1 fs-8">
                                            <code>{{ $pol->codigo }}</code>
                                        </span>
                                        <span class="badge {{ $pol->estado == 'Activa' ? 'bg-success' : ($pol->estado == 'En Revisión' ? 'bg-warning text-dark' : 'bg-secondary') }} rounded-pill px-3 py-1 fs-8">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i> {{ $pol->estado }}
                                        </span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-2 fs-6">{{ $pol->titulo }}</h5>
                                    <p class="text-muted fs-7 mb-3 leading-normal">
                                        {{ Str::limit($pol->descripcion, 140) }}
                                    </p>
                                </div>
                                <div class="pt-3 border-top d-flex align-items-center justify-content-between fs-8 text-muted">
                                    <div>
                                        <i class="fas fa-tag me-1 text-success"></i> <strong>Tipo:</strong> {{ $pol->tipo }}
                                    </div>
                                    <div>
                                        <i class="fas fa-user-tie me-1 text-primary"></i> {{ $pol->responsable }}
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt me-1 text-warning"></i> Vigencia: {{ $pol->vigencia }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-file-contract text-muted display-4 mb-3 opacity-50"></i>
                            <h5 class="text-muted">No hay políticas directivas registradas actualmente.</h5>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- CTA Call to Action Banner -->
        <section class="py-5" id="directrices">
            <div class="container">
                <div class="card border-0 rounded-4 overflow-hidden shadow-lg text-white" style="background: linear-gradient(135deg, var(--sena-dark) 0%, var(--sena-navy-light) 60%, var(--sena-green) 100%);">
                    <div class="card-body p-4 p-md-5">
                        <div class="row align-items-center">
                            <div class="col-lg-8 mb-4 mb-lg-0">
                                <span class="badge text-dark px-3 py-2 rounded-pill fs-7 mb-3 fw-bold" style="background-color: var(--sena-neon) !important;">
                                    <i class="fas fa-shield-alt me-1"></i> Control de Acceso y Roles
                                </span>
                                <h3 class="fw-bold fs-2 text-white mb-2">Administración del Módulo de Dirección</h3>
                                <p class="text-white-50 fs-6 mb-0">
                                    Los directores, coordinadores académicos e instructores líderes pueden autenticarse a través del portal ERP para crear, actualizar y auditar las decisiones estratégicas de la empresa didáctica.
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                @auth
                                    <a href="{{ route('direccion.dashboard') }}" class="btn btn-sena btn-lg px-4 py-3 shadow-lg">
                                        <i class="fas fa-gauge me-1"></i> Entrar al Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login', ['redirect' => route('direccion.dashboard')]) }}" class="btn btn-sena btn-lg px-4 py-3 shadow-lg">
                                        <i class="fas fa-right-to-bracket me-1"></i> Iniciar Sesión en ERP
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer-direccion text-center">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="text-white-50">
                    SENA Empresa &copy; {{ date('Y') }} • Centro de Formación Agroindustrial <strong>"La Angostura"</strong> (Campoalegre - Huila)
                </div>
                <div>
                    <span class="badge bg-dark border border-secondary text-white-50 px-3 py-1">
                        Módulo de Dirección • Procesos Estratégicos ERP
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
