<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Módulo de Ventas | INBOT SYSTEM</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    
    <style nonce="{{ $cspNonce ?? '' }}">
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .navbar-cms { background-color: #090d16 !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .card-cms { background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px -2px rgba(15,23,42,0.05); }
        .table-cms { background: #fff; border-radius: 1rem; overflow: hidden; border: 1px solid #e2e8f0; }
        .table-cms th { background-color: #f8fafc; color: #475569; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .cart-item-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cms sticky-top py-3">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <span class="p-2 rounded-3 bg-success text-white d-inline-flex align-items-center justify-content-center shadow-sm">
                    <i class="bi bi-cart4"></i>
                </span>
                <span class="fs-5 tracking-tight text-white">INBOT <span class="text-info fw-normal">VENTAS</span></span>
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="{{ route('facturas.compra.lista') }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-truck me-1"></i> Módulo Compras
                </a>
                <div class="text-white small fw-bold">
                    <i class="bi bi-person-circle text-info me-1"></i> {{ auth()->user()->name }} ({{ strtoupper(auth()->user()->role) }})
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid px-lg-4 py-4 flex-grow-1">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Errores de validación:</div>
                <ul class="mb-0 small ps-3">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            
            <!-- Columna Izquierda: Punto de Venta / Facturador (Flujo 9.2) -->
            <div class="col-lg-7">
                <div class="card card-cms p-4 h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-upc-scan text-success me-2"></i> Facturación de Venta (Caja)</h5>
                            <span class="text-muted small">Flujo 9.2: Búsqueda rápida por código o nombre y validación pesimista de existencias.</span>
                        </div>
                        <span class="badge bg-success-subtle text-success border px-3 py-2 fw-semibold">
                            Caja Activa
                        </span>
                    </div>

                    <!-- Buscador rápido por código de barras o nombre -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Buscar por Código de Barras o Lector</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-upc"></i></span>
                                <input type="text" id="inputScanCode" class="form-control mono" placeholder="Escanear o digitar código y presionar Enter..." autofocus>
                                <button type="button" class="btn btn-outline-secondary" id="btnBuscarScanCode">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">O seleccionar del Catálogo</label>
                            <select id="selectCatalogo" class="form-select">
                                <option value="">Seleccione producto...</option>
                                @foreach($productos as $prod)
                                    <option value="{{ $prod->id }}" data-nombre="{{ $prod->nombre }}" data-precio="{{ $prod->precio_venta }}" data-stock="{{ $prod->stock_actual }}" data-codigo="{{ $prod->codigo_barras }}">
                                        {{ $prod->nombre }} — ${{ number_format($prod->precio_venta, 2) }} (Disponibles: {{ $prod->stock_actual }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Carrito / Detalle de la Venta -->
                    <form action="{{ route('cms.ventas.store') }}" method="POST" id="formVentaCaja">
                        @csrf
                        
                        <div class="table-responsive mb-3" style="min-height: 220px; max-height: 320px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Producto</th>
                                        <th style="width: 100px;">Precio</th>
                                        <th style="width: 110px;">Cantidad</th>
                                        <th style="width: 110px;" class="text-end">Subtotal</th>
                                        <th style="width: 50px;" class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="contenedorItemsVenta">
                                    <tr id="filaVaciaVenta">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                                            El carrito de venta está vacío.<br>Escanea un código de barras o selecciona un producto arriba.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Datos del Cliente y Pago -->
                        <div class="bg-light p-3 rounded-3 border mb-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nombre del Cliente</label>
                                    <input type="text" name="cliente_nombre" class="form-control form-control-sm" placeholder="Consumidor Final" value="Consumidor Final">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Documento / Cédula</label>
                                    <input type="text" name="cliente_documento" class="form-control form-control-sm mono" placeholder="N/A">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Método de Pago</label>
                                    <select name="metodo_pago" class="form-select form-select-sm" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta Débito / Crédito</option>
                                        <option value="transferencia">Transferencia Bancaria</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Total y Botón de Cobro -->
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <div>
                                <span class="text-muted small d-block">TOTAL A PAGAR</span>
                                <span class="mono fw-bold fs-3 text-success" id="lblTotalVenta">$0.00</span>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg px-4 fw-bold shadow" id="btnConfirmarVenta" disabled>
                                <i class="bi bi-check-circle-fill me-2"></i> Cobrar y Descontar Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna Derecha: Ventas del Día (Sección 6.5 & 5.3) -->
            <div class="col-lg-5">
                <div class="card card-cms p-4 h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-check text-primary me-2"></i> Ventas del Día</h5>
                            <span class="text-muted small">Total hoy: <b>{{ $ventasHoy->count() }} facturas</b> | ${{ number_format($ventasHoy->sum('total'), 2) }}</span>
                        </div>
                        <a href="{{ route('reportes.word', ['tipo' => 'ventas', 'fecha' => date('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm" title="Descargar Reporte Word de Hoy">
                            <i class="bi bi-file-earmark-word me-1"></i> Word
                        </a>
                    </div>

                    <div class="table-responsive table-cms" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Hora</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ventasHoy as $vh)
                                    <tr>
                                        <td class="mono fw-bold text-success small">{{ $vh->numero_factura }}</td>
                                        <td class="small text-muted">{{ $vh->fecha ? $vh->fecha->format('H:i') : $vh->created_at->format('H:i') }}</td>
                                        <td class="small fw-medium">{{ $vh->cliente_nombre ?? 'Consumidor Final' }}</td>
                                        <td class="mono fw-bold text-dark text-end">${{ number_format($vh->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No se han registrado ventas hoy.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        const catalogo = @json($productos);
        let carrito = [];

        function buscarYAgregarPorCodigo() {
            const input = document.getElementById('inputScanCode');
            const code = input.value.trim();
            if (!code) return;

            const prod = catalogo.find(p => p.codigo_barras.toLowerCase() === code.toLowerCase());
            if (prod) {
                agregarAlCarrito(prod);
                input.value = '';
            } else {
                alert(`Producto con código "${code}" no encontrado en catálogo activo.`);
            }
        }

        function agregarDesdeSelect(prodId) {
            if (!prodId) return;
            const prod = catalogo.find(p => p.id == prodId);
            if (prod) {
                agregarAlCarrito(prod);
            }
        }

        function agregarAlCarrito(prod) {
            const existe = carrito.find(item => item.id == prod.id);
            if (existe) {
                if (existe.cantidad + 1 > prod.stock_actual) {
                    alert(`Stock máximo alcanzado para "${prod.nombre}". Disponible: ${prod.stock_actual} uds.`);
                    return;
                }
                existe.cantidad++;
            } else {
                if (prod.stock_actual < 1) {
                    alert(`El producto "${prod.nombre}" no tiene existencias disponibles.`);
                    return;
                }
                carrito.push({
                    id: prod.id,
                    nombre: prod.nombre,
                    codigo_barras: prod.codigo_barras,
                    precio_venta: parseFloat(prod.precio_venta),
                    stock_actual: parseInt(prod.stock_actual),
                    cantidad: 1
                });
            }
            renderizarCarrito();
        }

        function cambiarCantidad(prodId, nuevaCant) {
            const item = carrito.find(i => i.id == prodId);
            if (!item) return;

            const cant = parseInt(nuevaCant) || 1;
            if (cant > item.stock_actual) {
                alert(`No hay suficiente inventario. Máximo disponible: ${item.stock_actual} uds.`);
                item.cantidad = item.stock_actual;
            } else if (cant < 1) {
                quitarDelCarrito(prodId);
                return;
            } else {
                item.cantidad = cant;
            }
            renderizarCarrito();
        }

        function quitarDelCarrito(prodId) {
            carrito = carrito.filter(i => i.id != prodId);
            renderizarCarrito();
        }

        function renderizarCarrito() {
            const tbody = document.getElementById('contenedorItemsVenta');
            const btnCobrar = document.getElementById('btnConfirmarVenta');
            const lblTotal = document.getElementById('lblTotalVenta');

            if (carrito.length === 0) {
                tbody.innerHTML = `
                    <tr id="filaVaciaVenta">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                            El carrito de venta está vacío.<br>Escanea un código de barras o selecciona un producto arriba.
                        </td>
                    </tr>
                `;
                lblTotal.textContent = '$0.00';
                btnCobrar.disabled = true;
                return;
            }

            let html = '';
            let total = 0;

            carrito.forEach((item, idx) => {
                const sub = item.cantidad * item.precio_venta;
                total += sub;

                html += `
                    <tr class="cart-item-row">
                        <td>
                            <input type="hidden" name="items[${idx}][producto_id]" value="${item.id}">
                            <div class="fw-semibold text-dark">${item.nombre}</div>
                            <small class="text-muted mono">${item.codigo_barras} | Disp: ${item.stock_actual} uds</small>
                        </td>
                        <td class="mono">$${item.precio_venta.toFixed(2)}</td>
                        <td>
                            <input type="number" name="items[${idx}][cantidad]" class="form-control form-control-sm mono text-center input-item-cant" data-id="${item.id}" min="1" max="${item.stock_actual}" value="${item.cantidad}">
                        </td>
                        <td class="mono fw-bold text-end text-dark">$${sub.toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 btn-item-quitar" data-id="${item.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            lblTotal.textContent = '$' + total.toFixed(2);
            btnCobrar.disabled = false;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const btnBuscar = document.getElementById('btnBuscarScanCode');
            const inputScan = document.getElementById('inputScanCode');
            const selectCat = document.getElementById('selectCatalogo');
            const tbody = document.getElementById('contenedorItemsVenta');

            if (btnBuscar) {
                btnBuscar.addEventListener('click', buscarYAgregarPorCodigo);
            }
            if (inputScan) {
                inputScan.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        buscarYAgregarPorCodigo();
                    }
                });
            }
            if (selectCat) {
                selectCat.addEventListener('change', () => {
                    agregarDesdeSelect(selectCat.value);
                    selectCat.value = '';
                });
            }
            if (tbody) {
                tbody.addEventListener('change', (e) => {
                    if (e.target.classList.contains('input-item-cant')) {
                        cambiarCantidad(e.target.dataset.id, e.target.value);
                    }
                });
                tbody.addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-item-quitar');
                    if (btn) {
                        quitarDelCarrito(btn.dataset.id);
                    }
                });
            }
        });
    </script>
</body>
</html>

