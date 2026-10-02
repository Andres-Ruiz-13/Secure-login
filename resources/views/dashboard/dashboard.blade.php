<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Control CMS | INBOT SYSTEM</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --brand-primary: #0284c7;
            --brand-primary-dark: #0369a1;
            --brand-accent: #38bdf8;
            --brand-dark: #090d16;
            --brand-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .navbar-cms {
            background-color: #090d16 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-card-cms {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }

        .stat-card-cms:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.08);
        }

        .stat-icon-cms {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .nav-pills-cms .nav-link {
            color: #475569;
            font-weight: 600;
            padding: 0.65rem 1.15rem;
            border-radius: 0.75rem;
            transition: all 0.15s ease;
        }

        .nav-pills-cms .nav-link.active {
            background-color: var(--brand-primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        .table-cms {
            background: #ffffff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--brand-border);
        }

        .table-cms th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--brand-border);
        }

        .table-cms td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .badge-role {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
        }

        .chat-console {
            background: #0f172a;
            border-radius: 1rem;
            color: #e2e8f0;
            overflow: hidden;
        }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">

    <!-- Navbar CMS -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cms sticky-top py-3">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <span class="p-2 rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm">
                    <i class="bi bi-boxes"></i>
                </span>
                <span class="fs-5 tracking-tight text-white">INBOT <span class="text-info fw-normal">CMS</span></span>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="{{ route('home') }}" class="btn btn-outline-light btn-sm px-3" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Ver Catálogo Público
                </a>

                @php
                    $userRole = auth()->user()->role ?? 'user';
                    $roleBadgeClass = match($userRole) {
                        'admin' => 'bg-danger text-white',
                        'bodeguero' => 'bg-primary text-white',
                        'vendedor' => 'bg-success text-white',
                        default => 'bg-secondary text-white'
                    };
                @endphp

                <div class="d-none d-md-flex align-items-center gap-2 text-white">
                    <i class="bi bi-person-circle text-info fs-5"></i>
                    <div>
                        <div class="small fw-bold lh-1">{{ auth()->user()->name }}</div>
                        <span class="badge {{ $roleBadgeClass }} badge-role mt-1">{{ strtoupper($userRole) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                        <i class="bi bi-box-arrow-right me-1"></i> Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="container-fluid px-lg-4 py-4 flex-grow-1">

        <!-- Notificaciones Flash -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Por favor corrige los siguientes errores:</div>
                <ul class="mb-0 small ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Tarjetas de Resumen General (Métricas Clave) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card-cms d-flex align-items-center gap-3">
                    <div class="stat-icon-cms bg-primary-subtle text-primary">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalProductos }}</div>
                        <div class="text-muted small fw-medium">Total Productos</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card-cms d-flex align-items-center gap-3">
                    <div class="stat-icon-cms {{ $stockBajoCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold {{ $stockBajoCount > 0 ? 'text-danger' : 'text-dark' }}">{{ $stockBajoCount }}</div>
                        <div class="text-muted small fw-medium">Alertas Stock Bajo</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card-cms d-flex align-items-center gap-3">
                    <div class="stat-icon-cms bg-success-subtle text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark mono">${{ number_format($totalFacturado, 2) }}</div>
                        <div class="text-muted small fw-medium">Facturado en Ventas ({{ $ventas->count() }})</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card-cms d-flex align-items-center gap-3">
                    <div class="stat-icon-cms bg-warning-subtle text-warning">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">{{ $proveedores->count() }}</div>
                        <div class="text-muted small fw-medium">Proveedores Activos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas del CMS de Inventarios -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <ul class="nav nav-pills nav-pills-cms" id="cmsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="tab-inventario" data-bs-toggle="pill" data-bs-target="#pane-inventario" type="button">
                        <i class="bi bi-boxes me-1"></i> Inventario & Productos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-compras" data-bs-toggle="pill" data-bs-target="#pane-compras" type="button">
                        <i class="bi bi-arrow-down-left-square me-1"></i> Compras (Entradas)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-ventas" data-bs-toggle="pill" data-bs-target="#pane-ventas" type="button">
                        <i class="bi bi-receipt me-1"></i> Ventas (Salidas)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-proveedores" data-bs-toggle="pill" data-bs-target="#pane-proveedores" type="button">
                        <i class="bi bi-truck me-1"></i> Proveedores
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-reportes" data-bs-toggle="pill" data-bs-target="#pane-reportes" type="button">
                        <i class="bi bi-bar-chart-line me-1"></i> Reportes & Exportar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-chatbot" data-bs-toggle="pill" data-bs-target="#pane-chatbot" type="button">
                        <i class="bi bi-robot me-1"></i> Chatbot & Reconocimiento
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-media" data-bs-toggle="pill" data-bs-target="#pane-media" type="button">
                        <i class="bi bi-images me-1"></i> Multimedia & Noticias
                    </button>
                </li>
            </ul>

            @if(auth()->user()->isVendedor())
                <span class="badge bg-warning-subtle text-dark border p-2">
                    <i class="bi bi-info-circle-fill text-warning me-1"></i> Rol Vendedor: Permiso exclusivo de consulta y facturación de ventas (403 en catálogo).
                </span>
            @endif
        </div>

        <div class="tab-content" id="cmsTabsContent">

            <!-- ======================================================== -->
            <!-- 1. PESTAÑA: INVENTARIO & PRODUCTOS                       -->
            <!-- ======================================================== -->
            <div class="tab-pane fade show active" id="pane-inventario" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="cmsSearchInput" class="form-control form-control-sm" placeholder="🔍 Buscar producto..." style="min-width: 250px;" onkeyup="filterCmsTable()">
                        <button class="btn btn-outline-danger btn-sm" onclick="toggleLowStockFilter()">
                            <i class="bi bi-funnel"></i> Solo Stock Bajo
                        </button>
                    </div>

                    @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                            <i class="bi bi-plus-circle me-1"></i> Nuevo Producto
                        </button>
                    @endif
                </div>

                <div class="table-responsive table-cms">
                    <table class="table table-hover align-middle mb-0" id="cmsProductsTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Proveedor</th>
                                <th>P. Compra</th>
                                <th>Ganancia %</th>
                                <th>P. Venta</th>
                                <th>Stock Actual</th>
                                <th>Mínimo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $p)
                                @php $esBajo = $p->stock_actual <= $p->stock_minimo; @endphp
                                <tr class="cms-row {{ $esBajo ? 'table-danger-subtle' : '' }}" data-nombre="{{ strtolower($p->nombre) }}" data-codigo="{{ $p->codigo_barras }}" data-stockbajo="{{ $esBajo ? '1' : '0' }}">
                                    <td>
                                        @if($p->imagen)
                                            <img src="{{ Storage::url($p->imagen) }}" alt="{{ $p->nombre }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px;">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded" style="width: 44px; height: 44px;">
                                                <i class="bi bi-box"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="mono small">{{ $p->codigo_barras }}</td>
                                    <td class="fw-bold">{{ $p->nombre }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $p->categoria->name ?? 'N/A' }}</span></td>
                                    <td class="small">{{ $p->proveedor->nombre ?? 'N/A' }}</td>
                                    <td class="mono text-muted">${{ number_format($p->precio_compra, 2) }}</td>
                                    <td class="mono text-muted">{{ number_format($p->porcentaje_ganancia, 1) }}%</td>
                                    <td class="mono fw-bold text-primary">${{ number_format($p->precio_venta, 2) }}</td>
                                    <td class="fw-bold {{ $esBajo ? 'text-danger' : 'text-dark' }}">
                                        {{ $p->stock_actual }}
                                    </td>
                                    <td class="text-muted small">{{ $p->stock_minimo }}</td>
                                    <td>
                                        @if($esBajo)
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Stock Bajo</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                                            @if(auth()->user()->isAdmin())
                                                <form action="{{ route('cms.productos.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este producto permanentemente?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2" title="Eliminar Producto">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted small">Solo lectura</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4 text-muted">
                                        No hay productos registrados en el inventario.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 2. PESTAÑA: COMPRAS A PROVEEDORES (ENTRADAS ATÓMICAS)    -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-compras" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Historial de Compras (Entradas a Bodega)</h5>
                    @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                        <button class="btn btn-primary btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaCompra">
                            <i class="bi bi-plus-circle me-1"></i> Registrar Compra
                        </button>
                    @endif
                </div>

                <div class="table-responsive table-cms">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Factura Nº</th>
                                <th>Proveedor</th>
                                <th>Total Facturado</th>
                                <th>Fecha</th>
                                <th>Registrado Por</th>
                                <th>Detalle de Productos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compras as $c)
                                <tr>
                                    <td class="mono fw-bold text-primary">{{ $c->numero_factura }}</td>
                                    <td class="fw-medium">{{ $c->proveedor->nombre ?? 'N/A' }}</td>
                                    <td class="mono fw-bold">${{ number_format($c->total, 2) }}</td>
                                    <td class="small text-muted">{{ $c->fecha ? $c->fecha->format('d/m/Y H:i') : $c->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $c->usuario->name ?? 'Sistema' }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($c->detalles as $det)
                                                <span class="badge bg-light text-dark border me-1">
                                                    {{ $det->producto->nombre ?? 'Item' }} (x{{ $det->cantidad }} uds)
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay compras registradas en el historial.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 3. PESTAÑA: VENTAS Y FACTURACIÓN (SALIDAS ATÓMICAS)      -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-ventas" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Facturación de Ventas (Salidas de Almacén)</h5>
                    <button class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaVenta">
                        <i class="bi bi-cart-plus-fill me-1"></i> Facturar Nueva Venta
                    </button>
                </div>

                <div class="table-responsive table-cms">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Factura Nº</th>
                                <th>Cliente</th>
                                <th>Método de Pago</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Vendedor</th>
                                <th>Productos Descontados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ventas as $v)
                                <tr>
                                    <td class="mono fw-bold text-success">{{ $v->numero_factura }}</td>
                                    <td class="fw-medium">{{ $v->cliente_nombre ?? 'Cliente General' }}</td>
                                    <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $v->metodo_pago }}</span></td>
                                    <td class="mono fw-bold text-dark">${{ number_format($v->total, 2) }}</td>
                                    <td class="small text-muted">{{ $v->fecha ? $v->fecha->format('d/m/Y H:i') : $v->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $v->usuario->name ?? 'Vendedor' }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($v->detalles as $dv)
                                                <span class="badge bg-light text-dark border me-1">
                                                    {{ $dv->producto->nombre ?? 'Item' }} (x{{ $dv->cantidad }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No hay facturas de venta emitidas aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 4. PESTAÑA: PROVEEDORES & CATEGORÍAS                     -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-proveedores" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Proveedores Homologados</h5>
                    @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                        <button class="btn btn-primary btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevoProveedor">
                            <i class="bi bi-plus-circle me-1"></i> Registrar Proveedor
                        </button>
                    @endif
                </div>

                <div class="table-responsive table-cms mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>NIT</th>
                                <th>Razón Social</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Correo Electrónico</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proveedores as $prov)
                                <tr>
                                    <td class="mono fw-bold">{{ $prov->nit }}</td>
                                    <td class="fw-semibold">{{ $prov->nombre }}</td>
                                    <td>{{ $prov->telefono ?? 'N/A' }}</td>
                                    <td class="small">{{ $prov->direccion ?? 'N/A' }}</td>
                                    <td><a href="mailto:{{ $prov->correo }}">{{ $prov->correo ?? 'N/A' }}</a></td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">No hay proveedores dados de alta.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 5. PESTAÑA: REPORTES Y EXPORTACIÓN CSV                   -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-reportes" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                            <h5 class="fw-bold mb-3"><i class="bi bi-cash-coin text-success me-2"></i> Valoración Económica del Inventario</h5>
                            <p class="text-muted small">Cálculo en tiempo real comparativo entre costo de adquisición y proyección comercial.</p>

                            <div class="p-3 bg-light rounded-3 mb-3">
                                <div class="small text-muted">VALOR TOTAL A PRECIO DE COSTO (COMPRA)</div>
                                <div class="fs-4 fw-bold text-dark mono">${{ number_format($valorInventarioCosto, 2) }}</div>
                            </div>

                            <div class="p-3 bg-primary-subtle text-primary rounded-3">
                                <div class="small text-primary-emphasis">VALOR TOTAL A PRECIO DE VENTA COMERCIAL</div>
                                <div class="fs-3 fw-bold mono">${{ number_format($valorInventarioVenta, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card p-4 border-0 shadow-sm rounded-4 h-100">
                            <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i> Exportación Contable</h5>
                            <p class="text-muted small">Descarga el historial completo de ventas, métodos de pago y clientes en formato CSV compatible con Microsoft Excel o ERPs contables.</p>
                            
                            <div class="mt-auto">
                                <a href="/api/reportes/exportar-ventas-csv" class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2" download>
                                    <i class="bi bi-download"></i> Descargar Historial de Ventas (CSV)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 6. PESTAÑA: CHATBOT ASISTENTE & RECONOCIMIENTO           -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-chatbot" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                            <h5 class="fw-bold mb-2"><i class="bi bi-robot text-primary me-2"></i> Consola del Chatbot Asistente</h5>
                            <p class="text-muted small mb-3">Prueba en vivo las respuestas en lenguaje natural del asistente conversacional de almacén.</p>

                            <div class="chat-console p-3 mb-3 d-flex flex-column gap-2" id="cmsChatConsole" style="height: 280px; overflow-y: auto;">
                                <div class="p-2 rounded bg-primary-subtle text-dark small align-self-start">
                                    ¡Hola! Puedes preguntarme: "¿Cuánto cuesta el martillo?", "¿Hay stock bajo?" o "¿Cuántos productos hay?".
                                </div>
                            </div>

                            <div class="input-group">
                                <input type="text" id="cmsChatInput" class="form-control" placeholder="Escribe tu consulta al bot..." onkeydown="if(event.key==='Enter') sendCmsChat()">
                                <button class="btn btn-primary" type="button" onclick="sendCmsChat()">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                            <h5 class="fw-bold mb-2"><i class="bi bi-qr-code-scan text-info me-2"></i> Consulta Rápida por Código de Barras</h5>
                            <p class="text-muted small mb-3">Simula la lectura de un escáner físico de código de barras.</p>

                            <div class="input-group mb-3">
                                <input type="text" id="barcodeInput" class="form-control mono" placeholder="Ej: 7701234567890">
                                <button class="btn btn-info text-white" type="button" onclick="queryBarcode()">
                                    <i class="bi bi-search"></i> Consultar
                                </button>
                            </div>

                            <div id="barcodeResult" class="p-3 bg-light rounded-3 d-none">
                                <h6 class="fw-bold mb-1" id="barcodeProdName"></h6>
                                <div class="small text-muted" id="barcodeProdDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 7. PESTAÑA: MULTIMEDIA & NOTICIAS (RETROCOMPATIBILIDAD)  -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="pane-media" role="tabpanel">
                <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-cloud-arrow-up text-primary me-2"></i> Subir Imagen a la Biblioteca Multimedia</h5>
                    <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nombre del Archivo</label>
                            <input type="text" name="name" class="form-control" required placeholder="Ej: banner_bodega.jpg">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Archivo de Imagen (Máx 5MB)</label>
                            <input type="file" name="file" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                <i class="bi bi-upload"></i> Subir
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive table-cms">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Miniatura</th>
                                <th>Nombre</th>
                                <th>Tipo MIME</th>
                                <th>Tamaño</th>
                                <th>Ruta Física</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($media as $m)
                                <tr>
                                    <td>
                                        <img src="{{ Storage::url($m->path) }}" alt="{{ $m->name }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px;">
                                    </td>
                                    <td class="fw-semibold">{{ $m->name }}</td>
                                    <td class="mono small">{{ $m->mime_type }}</td>
                                    <td class="small">{{ round($m->size / 1024, 1) }} KB</td>
                                    <td class="mono small text-muted">{{ $m->path }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.media.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar archivo físico y registro?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">No hay medios cargados en la biblioteca.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- ======================================================== -->
    <!-- MODALES DE OPERACIÓN                                     -->
    <!-- ======================================================== -->

    <!-- Modal: Nuevo Producto -->
    <div class="modal fade" id="modalNuevoProducto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0">
                <form action="{{ route('cms.productos.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i> Registrar Nuevo Producto en Inventario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nombre del Producto</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Martillo de Uña 16oz">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Código de Barras</label>
                            <input type="text" name="codigo_barras" class="form-control mono" required placeholder="Ej: 7701234567890">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Categoría</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Seleccione una categoría...</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Proveedor</label>
                            <select name="proveedor_id" class="form-select" required>
                                <option value="">Seleccione un proveedor...</option>
                                @foreach($proveedores as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nombre }} (NIT: {{ $pr->nit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Precio de Compra ($)</label>
                            <input type="number" step="0.01" name="precio_compra" class="form-control mono" required placeholder="Ej: 25000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Margen de Ganancia (%)</label>
                            <input type="number" step="0.01" name="porcentaje_ganancia" class="form-control mono" required value="30.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Stock Inicial</label>
                            <input type="number" name="stock_actual" class="form-control" required value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Stock Mínimo (Alerta)</label>
                            <input type="number" name="stock_minimo" class="form-control" required value="3">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Foto del Producto (Calcula hash visual aHash)</label>
                            <input type="file" name="imagen" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar en Inventario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Registrar Compra -->
    <div class="modal fade" id="modalNuevaCompra" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <form action="{{ route('cms.compras.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2 text-primary"></i> Registrar Compra a Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Número de Factura de Compra</label>
                            <input type="text" name="numero_factura" class="form-control mono" required placeholder="Ej: COM-2026-001">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Proveedor</label>
                            <select name="proveedor_id" class="form-select" required>
                                <option value="">Seleccione proveedor...</option>
                                @foreach($proveedores as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nombre }} (NIT: {{ $pr->nit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Producto a Abastecer</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione producto...</option>
                                @foreach($productos as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->nombre }} (Stock actual: {{ $prod->stock_actual }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Cantidad Ingresada</label>
                            <input type="number" name="cantidad" class="form-control" min="1" required value="5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Costo Unitario ($)</label>
                            <input type="number" step="0.01" name="precio_unitario" class="form-control mono" required placeholder="Ej: 50000">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Confirmar Entrada Atómica
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Facturar Venta -->
    <div class="modal fade" id="modalNuevaVenta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <form action="{{ route('cms.ventas.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2 text-success"></i> Facturación de Venta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nombre del Cliente</label>
                            <input type="text" name="cliente_nombre" class="form-control" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Método de Pago</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta Débito / Crédito</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Producto a Vender</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione producto...</option>
                                @foreach($productos as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->nombre }} — ${{ number_format($prod->precio_venta, 2) }} (Disponible: {{ $prod->stock_actual }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Cantidad a Vender</label>
                            <input type="number" name="cantidad" class="form-control" min="1" required value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-semibold">
                            <i class="bi bi-cash-stack me-1"></i> Facturar y Descontar Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Proveedor -->
    <div class="modal fade" id="modalNuevoProveedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <form action="{{ route('cms.proveedores.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2 text-primary"></i> Registrar Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">NIT / Identificación</label>
                            <input type="text" name="nit" class="form-control mono" required placeholder="Ej: 900888777-1">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Razón Social</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Proveedora Nacional S.A.S.">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej: 3001234567">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" name="correo" class="form-control" placeholder="ventas@proveedor.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-control" placeholder="Ej: Carrera 10 # 20-30">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let onlyLowStock = false;

        function filterCmsTable() {
            const query = document.getElementById('cmsSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.cms-row');

            rows.forEach(row => {
                const nombre = row.getAttribute('data-nombre') || '';
                const codigo = row.getAttribute('data-codigo') || '';
                const esBajo = row.getAttribute('data-stockbajo') === '1';

                const matchesSearch = nombre.includes(query) || codigo.includes(query);
                const matchesStock = !onlyLowStock || esBajo;

                if (matchesSearch && matchesStock) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleLowStockFilter() {
            onlyLowStock = !onlyLowStock;
            filterCmsTable();
        }

        async function sendCmsChat() {
            const input = document.getElementById('cmsChatInput');
            const msg = input.value.trim();
            if (!msg) return;

            const consoleDiv = document.getElementById('cmsChatConsole');
            const userMsg = document.createElement('div');
            userMsg.className = 'p-2 rounded bg-primary text-white small align-self-end text-end';
            userMsg.textContent = msg;
            consoleDiv.appendChild(userMsg);
            input.value = '';
            consoleDiv.scrollTop = consoleDiv.scrollHeight;

            try {
                const res = await fetch('/api/aplicacion_movil/chatbot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mensaje: msg })
                });
                const data = await res.json();
                const botMsg = document.createElement('div');
                botMsg.className = 'p-2 rounded bg-primary-subtle text-dark small align-self-start';
                botMsg.style.whiteSpace = 'pre-line';
                botMsg.textContent = data.respuesta || 'Sin respuesta';
                consoleDiv.appendChild(botMsg);
            } catch (err) {
                const errDiv = document.createElement('div');
                errDiv.className = 'p-2 rounded bg-danger-subtle text-danger small align-self-start';
                errDiv.textContent = 'Error al consultar API';
                consoleDiv.appendChild(errDiv);
            }
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        async function queryBarcode() {
            const code = document.getElementById('barcodeInput').value.trim();
            if (!code) return;

            const resDiv = document.getElementById('barcodeResult');
            const nameEl = document.getElementById('barcodeProdName');
            const detEl = document.getElementById('barcodeProdDetails');

            try {
                const res = await fetch(`/api/aplicacion_movil/producto/codigo/${encodeURIComponent(code)}`);
                const data = await res.json();

                if (data.encontrado) {
                    const p = data.producto;
                    nameEl.textContent = p.nombre;
                    detEl.innerHTML = `Precio Venta: <b>$${Number(p.precio_venta).toFixed(2)}</b> | Stock actual: <b>${p.stock_actual} uds</b> | Cód: <code>${p.codigo_barras}</code>`;
                    resDiv.classList.remove('d-none', 'alert-danger');
                    resDiv.classList.add('alert-success');
                } else {
                    nameEl.textContent = 'No encontrado';
                    detEl.textContent = data.message || 'Código no registrado.';
                    resDiv.classList.remove('d-none', 'alert-success');
                    resDiv.classList.add('alert-danger');
                }
            } catch (err) {
                nameEl.textContent = 'Error';
                detEl.textContent = 'No se pudo contactar el endpoint.';
                resDiv.classList.remove('d-none');
            }
        }
    </script>
</body>
</html>