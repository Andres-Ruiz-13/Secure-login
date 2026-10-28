<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INBOT SYSTEM | Gestión Inteligente de Inventarios, Compras y Facturación</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    
    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --brand-primary: #0284c7;
            --brand-primary-dark: #0369a1;
            --brand-accent: #38bdf8;
            --brand-dark: #0f172a;
            --brand-darker: #020617;
            --brand-card-bg: #ffffff;
            --brand-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            overflow-x: hidden;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Hero Banner Futurista */
        .hero-banner {
            background: radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.25) 0%, transparent 40%),
                        radial-gradient(circle at 20% 80%, rgba(56, 189, 248, 0.15) 0%, transparent 40%),
                        linear-gradient(135deg, #020617 0%, #0f172a 60%, #1e293b 100%);
            color: #ffffff;
            padding: 5.5rem 1rem 6rem;
            border-radius: 0 0 2.5rem 2.5rem;
            position: relative;
            box-shadow: 0 20px 40px -15px rgba(2, 6, 23, 0.5);
        }

        .hero-badge {
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: #7dd3fc;
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
        }

        .hero-title {
            font-size: clamp(2.2rem, 4.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .hero-gradient-text {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Stat Cards */
        .stat-strip {
            margin-top: -3.5rem;
            position: relative;
            z-index: 10;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        /* Tarjetas de Producto */
        .product-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 1.25rem;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }

        .product-img-wrapper {
            width: 100%;
            height: 200px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .product-badge-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.35rem 0.65rem;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .feature-box {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 1.25rem;
            padding: 2rem;
            height: 100%;
            transition: all 0.25s ease;
        }

        .feature-box:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 12px 28px -6px rgba(2, 132, 199, 0.12);
        }

        .feature-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1.25rem;
        }

        /* Chatbot Preview Widget */
        .chatbot-card {
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            color: #ffffff;
        }

        .chat-bubble-bot {
            background: rgba(14, 165, 233, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 1rem 1rem 1rem 0;
            padding: 0.85rem 1.15rem;
            color: #e0f2fe;
            font-size: 0.9rem;
        }

        .chat-bubble-user {
            background: #3b82f6;
            color: white;
            border-radius: 1rem 1rem 0 1rem;
            padding: 0.85rem 1.15rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">

    <!-- Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3" style="background-color: #090d16 !important;">
        <div class="container">
            <a class="navbar-brand fw-extrabold d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="p-2 rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm">
                    <i class="bi bi-boxes fs-5"></i>
                </span>
                <span class="fs-4 fw-bold tracking-tight">INBOT <span class="text-info fw-normal">SYSTEM</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#inbotNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="inbotNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link active fw-medium" href="{{ route('home') }}">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#catalogo">Catálogo en Vivo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#modulos">Módulos del Sistema</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#asistente">Asistente Chatbot</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#contacto">Contacto</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    @auth
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm px-3 dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle text-info"></i>
                                <span>{{ auth()->user()->name }}</span>
                                <span class="badge bg-primary text-uppercase ms-1">{{ auth()->user()->role ?? 'Admin' }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                                        <i class="bi bi-speedometer2 text-primary"></i> Ir al Panel CMS
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm px-3 fw-semibold">
                            <i class="bi bi-gear-fill me-1"></i> Panel CMS
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-3 fw-medium">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
                            Registrarse
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <header class="hero-banner text-center">
        <div class="container py-4">
            <div class="hero-badge mx-auto">
                <i class="bi bi-robot"></i>
                <span>INBOT SYSTEM 2.0 • Gestión Inteligente de Inventarios</span>
            </div>
            
            <h1 class="hero-title mb-4">
                Control Total de tu Almacén, <br>
                <span class="hero-gradient-text">Inventario y Facturación en Tiempo Real</span>
            </h1>
            
            <p class="lead text-slate-300 max-w-3xl mx-auto mb-5 text-light opacity-75" style="max-width: 820px;">
                Plataforma tecnológica integral diseñada para el registro automatizado de productos, compras atómicas a proveedores, alertas críticas de stock mínimo, cálculo matemático de precios comerciales y asistente móvil con reconocimiento visual por aHash.
            </p>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg px-4 py-3 fw-bold rounded-pill shadow-lg d-inline-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2"></i> Acceder al Panel CMS de Inventarios
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4 py-3 fw-bold rounded-pill shadow-lg d-inline-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill"></i> Acceder al Panel de Control
                    </a>
                @endauth
                <a href="#catalogo" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold rounded-pill d-inline-flex align-items-center gap-2">
                    <i class="bi bi-boxes"></i> Ver Catálogo en Vivo
                </a>
            </div>
        </div>
    </header>

    <!-- Franja de Estadísticas del Sistema (KPIs en tiempo real) -->
    <section class="stat-strip mb-5">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ $totalProductos }}</div>
                            <div class="text-muted small fw-medium">Referencias Activas</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($totalStock) }}</div>
                            <div class="text-muted small fw-medium">Unidades en Stock</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-icon {{ $stockBajoCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' }}">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold {{ $stockBajoCount > 0 ? 'text-danger' : 'text-dark' }}">{{ $stockBajoCount }}</div>
                            <div class="text-muted small fw-medium">Alertas Stock Bajo</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ $totalProveedores }}</div>
                            <div class="text-muted small fw-medium">Proveedores Homologados</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Catálogo de Productos en Vivo -->
    <section id="catalogo" class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-2 rounded-pill mb-2">
                        Inventario en Almacén
                    </span>
                    <h2 class="h3 fw-bold text-dark mb-1">Catálogo de Productos en Tiempo Real</h2>
                    <p class="text-muted mb-0">Precios calculados con margen de ganancia comercial y validación inmediata de existencias.</p>
                </div>
                <div>
                    <input type="text" id="liveSearchInput" class="form-control form-control-sm" placeholder="🔍 Filtrar por nombre o código..." style="min-width: 260px;" onkeyup="filterProducts()">
                </div>
            </div>

            @if($productos->isEmpty())
                <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                    <i class="bi bi-inbox text-muted display-4 mb-3"></i>
                    <h5 class="fw-bold">No hay productos registrados aún</h5>
                    <p class="text-muted">Inicia sesión en el panel CMS para comenzar a poblar el inventario o ejecutar los seeders.</p>
                </div>
            @else
                <div class="row g-4" id="productsGrid">
                    @foreach($productos as $prod)
                        @php
                            $esStockBajo = $prod->stock_actual <= $prod->stock_minimo;
                        @endphp
                        <div class="col-md-6 col-lg-4 col-xl-3 product-item" data-nombre="{{ strtolower($prod->nombre) }}" data-codigo="{{ $prod->codigo_barras }}">
                            <div class="product-card">
                                <div class="product-img-wrapper">
                                    @php
                                        $imgUrl = $prod->image_url;
                                    @endphp
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $prod->nombre }}" class="product-img" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'text-center text-muted p-4\'><i class=\'bi bi-box-seam display-5 text-secondary opacity-50\'></i><div class=\'small mt-2 text-muted fw-semibold\'>Imagen no disponible</div></div>';">
                                    @else
                                        <div class="text-center text-muted p-4">
                                            <i class="bi bi-box-seam display-5 text-secondary opacity-50"></i>
                                            <div class="small mt-2 text-muted fw-semibold">Sin foto cargada</div>
                                        </div>
                                    @endif

                                    @if($esStockBajo)
                                        <span class="badge bg-danger product-badge-stock">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> Stock Bajo
                                        </span>
                                    @else
                                        <span class="badge bg-success product-badge-stock">
                                            <i class="bi bi-check-circle-fill me-1"></i> En Stock
                                        </span>
                                    @endif
                                </div>

                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <span class="badge bg-light text-secondary border small text-truncate" style="max-width: 62%;" title="{{ $prod->categoria->name ?? 'General' }}">
                                            {{ $prod->categoria->name ?? 'General' }}
                                        </span>
                                        <span class="mono text-muted small flex-shrink-0">
                                            <i class="bi bi-upc"></i> {{ $prod->codigo_barras }}
                                        </span>
                                    </div>

                                    <h5 class="fw-bold fs-6 text-dark mb-1 text-truncate" title="{{ $prod->nombre }}">
                                        {{ $prod->nombre }}
                                    </h5>

                                    <div class="text-muted small mb-3 text-truncate" title="{{ $prod->proveedor->nombre ?? 'N/A' }}">
                                        <i class="bi bi-building me-1"></i> {{ $prod->proveedor->nombre ?? 'N/A' }}
                                    </div>

                                    <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-end gap-2">
                                        <div>
                                            <div class="text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">PRECIO VENTA</div>
                                            <div class="fs-5 fw-bold text-primary mono text-nowrap">
                                                ${{ number_format($prod->precio_venta, 2) }}
                                            </div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">DISPONIBLE</div>
                                            <div class="fw-bold text-nowrap {{ $esStockBajo ? 'text-danger' : 'text-dark' }}">
                                                {{ $prod->stock_actual }} <span class="small text-muted fw-normal">uds</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Módulos Principales del Sistema INBOT -->
    <section id="modulos" class="py-5 bg-white border-top border-bottom">
        <div class="container">
            <div class="text-center max-w-2xl mx-auto mb-5">
                <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-2 rounded-pill mb-2">
                    Arquitectura Empresarial
                </span>
                <h2 class="h3 fw-bold text-dark">Módulos Nativos de INBOT SYSTEM</h2>
                <p class="text-muted">Diseñado bajo estándares de alta disponibilidad, transacciones ACID y cumplimiento OWASP.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-primary-subtle text-primary">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Cálculo Automático de Precios</h4>
                        <p class="text-muted small mb-0">
                            Fórmula inmutable en el backend: <code>precio_compra * (1 + ganancia / 100)</code> con redondeo comercial automático para evitar pérdidas y alteraciones desde el frontend.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-danger-subtle text-danger">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Monitoreo de Stock Bajo</h4>
                        <p class="text-muted small mb-0">
                            Endpoint dedicado <code>/api/stock_bajo</code> y vista especializada en el CMS que filtran productos en umbral crítico para prevenir desabastecimientos de bodega.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Transacciones Atómicas ACID</h4>
                        <p class="text-muted small mb-0">
                            Control con bloqueo pesimista <code>lockForUpdate()</code> en ventas y compras. Si falta stock, aborta con código 422 previniendo condiciones de carrera e inventarios negativos.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-info-subtle text-info">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Reconocimiento Visual aHash</h4>
                        <p class="text-muted small mb-0">
                            Búsqueda y validación de productos mediante Average Hash (aHash de 64 bits con PHP GD nativo) comparando la distancia de Hamming para identificar fotos tomadas desde móviles.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-warning-subtle text-warning">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Chatbot de Existencias en Vivo</h4>
                        <p class="text-muted small mb-0">
                            Asistente en tiempo real para empleados y clientes. Consulta precios, disponibilidad por nombre y emite alertas de inventario mediante lenguaje natural.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-box">
                        <div class="feature-icon-wrapper bg-purple-subtle text-dark" style="background-color: #f3e8ff;">
                            <i class="bi bi-person-badge-fill text-purple" style="color: #9333ea;"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Seguridad RBAC Granular</h4>
                        <p class="text-muted small mb-0">
                            Roles estandarizados: Administrador (control total), Bodeguero (compras y catálogo) y Vendedor (consultas y facturación; bloqueado con 403 Forbidden ante intentos de mutación).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Asistente Móvil y Chatbot INBOT Interactivo -->
    <section id="asistente" class="py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge bg-info-subtle text-info fw-bold text-uppercase px-3 py-2 rounded-pill mb-2">
                        Inteligencia Móvil
                    </span>
                    <h2 class="h3 fw-bold text-dark mb-3">Asistente Conversacional INBOT</h2>
                    <p class="text-muted mb-4">
                        Conecta a tus vendedores de salón o clientes directamente con el almacén. Permite consultar existencias, cotizaciones y estados de stock con simples preguntas o fotos del producto.
                    </p>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Consultas Rápidas por Nombre o Código</h6>
                                <p class="text-muted small mb-0">Busca directamente en el catálogo mediante endpoints optimizados.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Detección de Stock Crítico</h6>
                                <p class="text-muted small mb-0">Alerta de inmediato si un producto solicitado está próximo a agotarse.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- Widget Interactivo del Chatbot -->
                    <div class="chatbot-card p-4 shadow-lg">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3" style="border-color: rgba(255,255,255,0.1) !important;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-2 rounded-circle bg-primary text-white">
                                    <i class="bi bi-robot"></i>
                                </span>
                                <div>
                                    <div class="fw-bold text-white fs-6">INBOT Asistente</div>
                                    <div class="small text-success d-flex align-items-center gap-1">
                                        <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span> Conectado a la API
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-secondary-subtle text-white small mono">API /chatbot</span>
                        </div>

                        <div id="chatMessages" class="d-flex flex-column gap-3 mb-3 overflow-y-auto" style="height: 250px;">
                            <div class="chat-bubble-bot align-self-start">
                                ¡Hola! Soy el asistente virtual de INBOT SYSTEM. Pregúntame sobre cualquier producto, existencias o alertas de stock bajo.
                            </div>
                        </div>

                        <div class="input-group">
                            <input type="text" id="chatInput" class="form-control bg-dark text-white border-secondary" placeholder="Ej: ¿Hay existencias de martillo?" onkeydown="if(event.key==='Enter') sendChatMessage()">
                            <button class="btn btn-primary" type="button" onclick="sendChatMessage()">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Novedades de Almacén (Retrocompatibilidad) -->
    @if(isset($news) && $news->isNotEmpty())
        <section class="py-5 bg-light border-top">
            <div class="container">
                <h3 class="h4 fw-bold mb-4">Comunicados y Novedades de Operación</h3>
                <div class="row g-4">
                    @foreach($news as $item)
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                                @if($item->media)
                                    <img src="{{ Storage::url($item->media->path) }}" class="card-img-top" alt="{{ $item->title }}" style="height: 160px; object-fit: cover;">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title fw-bold fs-6">{{ $item->title }}</h5>
                                    <p class="card-text text-muted small">{{ $item->excerpt }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Formulario de Contacto -->
    <section id="contacto" class="py-5 bg-white border-top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-2 rounded-pill mb-2">
                            Soporte y Consultas
                        </span>
                        <h2 class="h3 fw-bold text-dark">Contáctanos</h2>
                        <p class="text-muted">¿Necesitas soporte técnico o integración para tu bodega? Envíanos un mensaje.</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('contact.send') }}" method="POST" class="card p-4 border rounded-4 shadow-sm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nombre Completo</label>
                                <input type="text" name="name" class="form-control" required placeholder="Tu nombre">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required placeholder="correo@empresa.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Asunto</label>
                                <input type="text" name="subject" class="form-control" required placeholder="Asunto de la consulta">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Mensaje</label>
                                <textarea name="message" rows="4" class="form-control" required placeholder="Escribe tu mensaje..."></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-envelope-paper-fill me-1"></i> Enviar Mensaje
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto" style="background-color: #020617 !important;">
        <div class="container text-center text-md-between d-md-flex align-items-center justify-content-between">
            <div class="mb-2 mb-md-0">
                <span class="fw-bold">INBOT SYSTEM</span> &copy; {{ date('Y') }} — Plataforma Empresarial de Gestión de Inventarios.
            </div>
            <div class="small text-muted">
                Laravel 12 • MariaDB XAMPP • REST API • RBAC
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        function filterProducts() {
            const query = document.getElementById('liveSearchInput').value.toLowerCase().trim();
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const nombre = item.getAttribute('data-nombre') || '';
                const codigo = item.getAttribute('data-codigo') || '';
                if (nombre.includes(query) || codigo.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg) return;

            const chatMessages = document.getElementById('chatMessages');

            // Mensaje del usuario
            const userBubble = document.createElement('div');
            userBubble.className = 'chat-bubble-user align-self-end';
            userBubble.textContent = msg;
            chatMessages.appendChild(userBubble);
            input.value = '';
            chatMessages.scrollTop = chatMessages.scrollHeight;

            try {
                const response = await fetch('/api/aplicacion_movil/chatbot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ mensaje: msg })
                });

                const data = await response.json();
                const botBubble = document.createElement('div');
                botBubble.className = 'chat-bubble-bot align-self-start';
                botBubble.style.whiteSpace = 'pre-line';
                botBubble.textContent = data.respuesta || 'No se recibió respuesta.';
                chatMessages.appendChild(botBubble);
            } catch (err) {
                const errBubble = document.createElement('div');
                errBubble.className = 'chat-bubble-bot align-self-start text-danger';
                errBubble.textContent = 'Error al comunicarse con el asistente INBOT.';
                chatMessages.appendChild(errBubble);
            }

            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>