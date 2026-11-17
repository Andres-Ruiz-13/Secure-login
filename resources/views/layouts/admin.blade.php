<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'INBOT SYSTEM') }}</title>
    
    <!-- Fuentes Tipográficas Seguras -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 + Icons con Subresource Integrity (SRI) y CSP Seguro -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    
    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --sidebar-width: 260px;
            --topbar-height: 68px;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --role-color: {{ auth()->check() ? auth()->user()->role_color : '#0284c7' }};
        }

        body {
            font-family: var(--font-main);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .mono { font-family: var(--font-mono); }

        /* Sidebar Styling */
        #adminSidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            background: #090d16;
            color: #e2e8f0;
            transition: all 0.25s ease-in-out;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        #adminSidebar.collapsed {
            transform: translateX(-100%);
        }

        @media (min-width: 992px) {
            #adminSidebar.collapsed {
                transform: translateX(0);
                width: 78px;
            }
            #adminSidebar.collapsed .sidebar-text,
            #adminSidebar.collapsed .sidebar-header-title,
            #adminSidebar.collapsed .user-info-text {
                display: none !important;
            }
            #adminSidebar.collapsed .nav-item-link {
                text-align: center;
                padding: 0.75rem 0;
            }
            #adminSidebar.collapsed .nav-item-link i {
                margin: 0 !important;
                font-size: 1.25rem;
            }
            #mainWrapper {
                margin-left: var(--sidebar-width);
                transition: margin-left 0.25s ease-in-out;
            }
            #mainWrapper.expanded {
                margin-left: 78px;
            }
        }

        @media (max-width: 991.98px) {
            #adminSidebar {
                transform: translateX(-100%);
            }
            #adminSidebar.mobile-open {
                transform: translateX(0);
            }
            #mainWrapper {
                margin-left: 0 !important;
            }
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-nav-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0.75rem;
        }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 0.75rem 0.75rem 0.25rem;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.6rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.15s ease;
            margin-bottom: 0.2rem;
        }

        .nav-item-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .nav-item-link.active {
            color: #ffffff;
            background: var(--role-color);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        /* Topbar Styling */
        #adminTopbar {
            height: var(--topbar-height);
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }

        /* Distinctive Role Badge Indicator */
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.8rem;
            font-weight: 600;
            background: {{ auth()->check() ? auth()->user()->role_color : '#0284c7' }};
            color: #ffffff;
        }

        .role-border-accent {
            border-left: 4px solid var(--role-color) !important;
        }

        /* Cards & Components */
        .card-cms {
            border-radius: 1rem;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-card-bg);
            box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.05);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .card-cms:hover {
            box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.08);
        }

        .badge-priority-urgente { background-color: #ef4444; color: white; }
        .badge-priority-alta { background-color: #f97316; color: white; }
        .badge-priority-normal { background-color: #0284c7; color: white; }
        .badge-priority-baja { background-color: #64748b; color: white; }

        /* Modales 100% Sólidos y Opacos (Sin transparencias en Modo Claro ni Oscuro) */
        .modal {
            background-color: transparent !important;
        }

        .modal-dialog {
            z-index: 1055 !important;
        }

        .modal-content {
            opacity: 1 !important;
            border-radius: 1rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        [data-bs-theme="dark"] .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-bs-theme="dark"] .modal-content {
            background-color: #111827 !important;
            color: #f8fafc !important;
            border: 1px solid #374151 !important;
        }

        [data-bs-theme="dark"] .modal-header,
        [data-bs-theme="dark"] .modal-body,
        [data-bs-theme="dark"] .modal-footer {
            background-color: #111827 !important;
            color: #f8fafc !important;
            border-color: #374151 !important;
        }

        [data-bs-theme="dark"] .modal-footer.bg-light,
        [data-bs-theme="dark"] .modal-body .bg-light,
        [data-bs-theme="dark"] .modal-body .alert-light {
            background-color: #1f2937 !important;
            color: #f1f5f9 !important;
            border-color: #374151 !important;
        }

        [data-bs-theme="dark"] .modal-content .form-control,
        [data-bs-theme="dark"] .modal-content .form-select {
            background-color: #1f2937 !important;
            color: #f8fafc !important;
            border-color: #4b5563 !important;
        }

        [data-bs-theme="dark"] .modal-content .form-control:focus,
        [data-bs-theme="dark"] .modal-content .form-select:focus {
            background-color: #1f2937 !important;
            color: #ffffff !important;
            border-color: #38bdf8 !important;
        }

        [data-bs-theme="light"] .modal-content {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }

        [data-bs-theme="light"] .modal-header,
        [data-bs-theme="light"] .modal-body,
        [data-bs-theme="light"] .modal-footer {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #e2e8f0 !important;
        }

        .modal-backdrop.show {
            opacity: 0.75 !important;
            background-color: #000000 !important;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- SIDEBAR EMPRESARIAL -->
    <aside id="adminSidebar">
        <!-- Brand / Logo -->
        <div class="sidebar-brand justify-content-between">
            <a href="{{ route('dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2 text-white">
                <span class="p-2 rounded-3 text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="background-color: var(--role-color);">
                    <i class="bi {{ auth()->user()->role_icon }}"></i>
                </span>
                <div class="sidebar-header-title">
                    <span class="fw-bold fs-5 tracking-tight">INBOT</span>
                    <span class="badge text-white ms-1" style="background: rgba(255,255,255,0.2); font-size: 0.65rem;">CMS ERP</span>
                </div>
            </a>
            <button type="button" class="btn btn-sm btn-link text-white-50 d-lg-none" id="btnCerrarSidebarMobile">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <!-- Perfil del Usuario Activo en Sidebar -->
        <div class="p-3 mx-2 my-2 rounded-3" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 38px; height: 38px; background: var(--role-color); font-size: 0.95rem;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="user-info-text overflow-hidden">
                    <div class="text-white fw-bold small text-truncate">{{ auth()->user()->name }}</div>
                    <div class="badge {{ auth()->user()->role_badge_class }}" style="font-size: 0.7rem;">
                        <i class="bi {{ auth()->user()->role_icon }} me-1"></i> {{ auth()->user()->role_title }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación Modular Estricta por Roles -->
        <div class="sidebar-nav-container">
            
            <!-- SECCIÓN GENERAL: Para todos los roles autenticados -->
            <div class="nav-section-title">Principal</div>
            <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="sidebar-text">Panel Dashboard</span>
            </a>

            <!-- MÓDULOS DE CAJERO / VENDEDOR -->
            @if(auth()->user()->isVendedor() || auth()->user()->isAdmin())
                <div class="nav-section-title">Caja y Facturación</div>
                <a href="{{ route('ventas.lista') }}" class="nav-item-link {{ request()->routeIs('ventas.lista') ? 'active' : '' }}">
                    <i class="bi bi-cart4 text-success"></i>
                    <span class="sidebar-text">Módulo Ventas</span>
                </a>
            @endif

            <!-- MÓDULOS DE BODEGUERO -->
            @if(auth()->user()->isBodeguero() || auth()->user()->isAdmin())
                <div class="nav-section-title">Almacén e Inventarios</div>
                <a href="{{ route('bodegas.index') }}" class="nav-item-link {{ request()->routeIs('bodegas.*') ? 'active' : '' }}">
                    <i class="bi bi-building text-info"></i>
                    <span class="sidebar-text">Bodegas & Kardex</span>
                </a>
                <a href="{{ route('facturas.compra.lista') }}" class="nav-item-link {{ request()->routeIs('facturas.compra.lista') ? 'active' : '' }}">
                    <i class="bi bi-truck text-primary"></i>
                    <span class="sidebar-text">Facturas Compra</span>
                </a>
                <a href="{{ route('dashboard') }}#seccionProductos" class="nav-item-link">
                    <i class="bi bi-boxes text-info"></i>
                    <span class="sidebar-text">Catálogo de Productos</span>
                </a>
                <a href="{{ route('dashboard') }}#seccionStockBajo" class="nav-item-link">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span class="sidebar-text">Alertas de Stock Bajo</span>
                </a>
            @endif

            <!-- MÓDULOS EXCLUSIVOS DE ADMINISTRADOR -->
            @if(auth()->user()->isAdmin())
                <div class="nav-section-title">Administración y Control</div>
                <a href="{{ route('dashboard') }}#seccionUsuarios" class="nav-item-link">
                    <i class="bi bi-people-fill text-danger"></i>
                    <span class="sidebar-text">Gestión de Usuarios</span>
                </a>
                <a href="{{ route('dashboard') }}#seccionNews" class="nav-item-link">
                    <i class="bi bi-megaphone-fill text-primary"></i>
                    <span class="sidebar-text">CMS de Comunicados</span>
                </a>
                <a href="{{ route('dashboard') }}#seccionMedia" class="nav-item-link">
                    <i class="bi bi-images text-secondary"></i>
                    <span class="sidebar-text">Biblioteca Multimedia</span>
                </a>
            @endif

            <!-- REPORTES Y EXPORTACIÓN -->
            @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                <div class="nav-section-title">Reportes Ejecutivos</div>
                <a href="{{ route('reportes.word', ['tipo' => 'inventario']) }}" class="nav-item-link">
                    <i class="bi bi-file-earmark-word text-primary"></i>
                    <span class="sidebar-text">Reporte Inventario Word</span>
                </a>
                <a href="{{ route('reportes.word', ['tipo' => 'ventas', 'fecha' => date('Y-m-d')]) }}" class="nav-item-link">
                    <i class="bi bi-receipt-cutoff text-success"></i>
                    <span class="sidebar-text">Reporte Ventas Diarias</span>
                </a>
            @endif

            <!-- PORTAL PÚBLICO -->
            <div class="nav-section-title">Sitio Web</div>
            <a href="{{ route('home') }}" target="_blank" class="nav-item-link">
                <i class="bi bi-box-arrow-up-right"></i>
                <span class="sidebar-text">Ver Portal Web</span>
            </a>
        </div>

        <!-- Footer Sidebar: Botón de Cierre de Sesión -->
        <div class="p-3 border-top border-secondary border-opacity-25">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="sidebar-text">Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- WRAPPER DE CONTENIDO PRINCIPAL -->
    <div id="mainWrapper">
        
        <!-- TOPBAR SUPERIOR -->
        <header id="adminTopbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleSidebar" title="Colapsar / Expandir menú">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <!-- Buscador Rápido Global -->
                <div class="input-group input-group-sm d-none d-md-flex" style="width: 280px;">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="globalQuickSearch" class="form-control border-start-0" placeholder="Buscar producto, código, módulo...">
                </div>
            </div>

            <!-- Acciones Derecha -->
            <div class="ms-auto d-flex align-items-center gap-3">
                
                <!-- Fecha y Hora Dinámica -->
                <div class="d-none d-xl-flex flex-column text-end small">
                    <span class="fw-semibold text-body" id="clockDate">{{ date('d M, Y') }}</span>
                    <span class="text-muted mono" style="font-size: 0.75rem;" id="clockTime">{{ date('H:i:s') }}</span>
                </div>

                <!-- Selector de Modo Claro / Oscuro -->
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" id="btnThemeToggle" title="Alternar tema Claro/Oscuro" style="width: 36px; height: 36px;">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>

                <!-- Menú de Notificaciones -->
                @php
                    $sysNotifications = \App\Models\SystemNotification::paraRol(auth()->user()->role)->noLeidas()->latest()->take(6)->get();
                    $unreadCount = $sysNotifications->count();
                @endphp
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle position-relative p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px;" title="Centro de Alertas y Notificaciones">
                        <i class="bi bi-bell"></i>
                        @if($unreadCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                {{ $unreadCount }}
                                <span class="visually-hidden">Alertas no leídas</span>
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width: 340px; border-radius: 1rem; overflow: hidden;">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Centro de Alertas</h6>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $unreadCount }} pendientes para {{ auth()->user()->role_title }}</small>
                            </div>
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-primary text-decoration-none" style="font-size: 0.75rem;">
                                        Limpiar todas
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="p-2" style="max-height: 300px; overflow-y: auto;">
                            @forelse($sysNotifications as $notif)
                                <div class="p-2 rounded-2 border-bottom d-flex gap-2 align-items-start">
                                    <span class="p-1 rounded bg-{{ $notif->tipo ?? 'primary' }}-subtle text-{{ $notif->tipo ?? 'primary' }} mt-1">
                                        <i class="bi {{ $notif->icono ?? 'bi-bell' }}"></i>
                                    </span>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-semibold small text-truncate">{{ $notif->titulo }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $notif->mensaje }}</div>
                                        <small class="text-secondary mono" style="font-size: 0.68rem;">{{ $notif->created_at->diffForHumans() }}</small>
                                    </div>
                                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm p-0 px-1 border-0" title="Marcar como leída">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="p-3 text-center text-muted small">
                                    <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                    No tienes notificaciones pendientes.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Perfil y Badge del Rol Activo -->
                <div class="role-pill shadow-sm">
                    <i class="bi {{ auth()->user()->role_icon }}"></i>
                    <span>{{ auth()->user()->role_title }}</span>
                </div>
            </div>
        </header>

        <!-- ÁREA PRINCIPAL DE CONTENIDO CON BREADCRUMBS -->
        <main class="container-fluid px-3 px-lg-4 py-4">

            <!-- Breadcrumbs de Navegación -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">INBOT SYSTEM</a></li>
                    @yield('breadcrumbs')
                </ol>
            </nav>

            <!-- Alertas Flash del Sistema -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Por favor verifique los siguientes campos:</div>
                    <ul class="mb-0 small ps-3">
                        @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Contenido Inyectado -->
            @yield('content')

        </main>
    </div>

    <!-- Scripts Esenciales con SRI y Nonce Criptográfico CSP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        // 1. Alternador de Modo Claro / Oscuro con persistencia en localStorage
        const htmlElement = document.documentElement;
        const themeBtn = document.getElementById('btnThemeToggle');
        const themeIcon = document.getElementById('themeIcon');

        const savedTheme = localStorage.getItem('inbot_theme') || 'light';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        actualizarIconoTema(savedTheme);

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const current = htmlElement.getAttribute('data-bs-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                htmlElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('inbot_theme', next);
                actualizarIconoTema(next);
            });
        }

        function actualizarIconoTema(theme) {
            if (!themeIcon) return;
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun-fill text-warning';
            } else {
                themeIcon.className = 'bi bi-moon-stars text-secondary';
            }
        }

        // 2. Colapsar / Expandir Sidebar
        const sidebar = document.getElementById('adminSidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const btnToggleSidebar = document.getElementById('btnToggleSidebar');
        const btnCerrarMobile = document.getElementById('btnCerrarSidebarMobile');

        if (btnToggleSidebar && sidebar && mainWrapper) {
            btnToggleSidebar.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle('mobile-open');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainWrapper.classList.toggle('expanded');
                }
            });
        }

        if (btnCerrarMobile && sidebar) {
            btnCerrarMobile.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
            });
        }

        // 3. Reloj en tiempo real
        function actualizarReloj() {
            const timeSpan = document.getElementById('clockTime');
            if (!timeSpan) return;
            const now = new Date();
            timeSpan.textContent = now.toLocaleTimeString('es-CO');
        }
        setInterval(actualizarReloj, 1000);

        // 4. Buscador rápido interactivo
        const searchInput = document.getElementById('globalQuickSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.table-searchable tbody tr');
                rows.forEach(tr => {
                    const text = tr.textContent.toLowerCase();
                    tr.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
