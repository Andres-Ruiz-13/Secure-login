<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facturas de Compra | INBOT SYSTEM</title>
    
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
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-cms sticky-top py-3">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <span class="p-2 rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm">
                    <i class="bi bi-boxes"></i>
                </span>
                <span class="fs-5 tracking-tight text-white">INBOT <span class="text-info fw-normal">CMS</span></span>
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-speedometer2 me-1"></i> Ir al Dashboard
                </a>
                <a href="{{ route('ventas.lista') }}" class="btn btn-outline-info btn-sm px-3">
                    <i class="bi bi-receipt me-1"></i> Módulo Ventas
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

        <!-- Encabezado de Sección y Botón -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="bi bi-truck text-primary me-2"></i> Facturas de Compra a Proveedores
                </h4>
                <p class="text-muted small mb-0">Flujo 9.1: Abastecimiento, creación automática de productos y cálculo automático de precios de venta.</p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isBodeguero())
                <button class="btn btn-primary px-4 fw-semibold shadow-sm" data-bs-toggle="collapse" data-bs-target="#collapseNuevaCompra">
                    <i class="bi bi-plus-circle me-1"></i> Registrar Factura de Compra (En 1 Solo Paso)
                </button>
            @endif
        </div>

        <!-- Formulario en 1 Solo Paso (Sección 6.4 y 9.1) -->
        <div class="collapse mb-4" id="collapseNuevaCompra">
            <div class="card card-cms p-4 border-primary shadow">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h5 class="fw-bold text-primary mb-0">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Nueva Factura de Compra a Proveedor
                    </h5>
                    <button class="btn-close" data-bs-toggle="collapse" data-bs-target="#collapseNuevaCompra"></button>
                </div>

                <form action="{{ route('cms.compras.store') }}" method="POST" id="formCompraMulti">
                    @csrf
                    
                    <!-- Cabecera -->
                    <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Número de Factura *</label>
                            <input type="text" name="numero_factura" class="form-control mono" required placeholder="Ej: FAC-COMP-901">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Proveedor *</label>
                            <select name="proveedor_id" class="form-select" required>
                                <option value="">Seleccione proveedor...</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre }} (NIT: {{ $prov->nit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Fecha de Compra</label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Porcentaje Ganancia Global (%) *</label>
                            <input type="number" step="0.1" name="porcentaje_ganancia" id="globalProfit" class="form-control mono" required value="30.0">
                            <span class="text-muted" style="font-size: 0.75rem;">Aplica a todos los productos nuevos</span>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Observaciones (Opcional)</label>
                            <input type="text" name="observaciones" class="form-control" placeholder="Detalles de entrega, lote o transportadora...">
                        </div>
                    </div>

                    <!-- Filas Dinámicas de Productos -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">Líneas de Detalle de la Factura (Productos)</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnAgregarFilaCompra">
                                <i class="bi bi-plus-lg me-1"></i> Agregar Otra Fila
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="tablaDetallesCompra">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="width: 140px;">Tipo</th>
                                        <th>Datos del Producto</th>
                                        <th style="width: 120px;">Cantidad</th>
                                        <th style="width: 150px;">Precio Compra Unitario ($)</th>
                                        <th style="width: 140px;">Subtotal ($)</th>
                                        <th style="width: 60px;" class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="contenedorFilasCompra">
                                    <!-- Las filas se insertan dinámicamente con JS -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">TOTAL FACTURA DE COMPRA:</td>
                                        <td class="mono fw-bold fs-5 text-primary text-end" id="lblTotalCompra">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-toggle="collapse" data-bs-target="#collapseNuevaCompra">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                            <i class="bi bi-check2-circle me-1"></i> Registrar Factura y Actualizar Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla Historial de Facturas de Compra -->
        <div class="card card-cms p-3">
            <h5 class="fw-bold text-dark mb-3">Historial de Facturas Registradas ({{ $compras->count() }})</h5>
            <div class="table-responsive table-cms">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Factura Nº</th>
                            <th>Proveedor</th>
                            <th>NIT</th>
                            <th>Fecha</th>
                            <th>Registrado Por</th>
                            <th>Total Facturado</th>
                            <th>Productos Incluidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compras as $c)
                            <tr>
                                <td class="mono fw-bold text-primary">{{ $c->numero_factura }}</td>
                                <td class="fw-semibold">{{ $c->proveedor->nombre ?? 'N/A' }}</td>
                                <td class="mono small">{{ $c->proveedor->nit ?? 'N/A' }}</td>
                                <td class="small text-muted">{{ $c->fecha ? $c->fecha->format('d/m/Y H:i') : $c->created_at->format('d/m/Y H:i') }}</td>
                                <td class="small">{{ $c->usuario->name ?? 'Bodeguero' }}</td>
                                <td class="mono fw-bold fs-6 text-dark">${{ number_format($c->total, 2) }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($c->detalles as $det)
                                            <span class="badge bg-light text-dark border">
                                                {{ $det->producto->nombre ?? 'Producto' }} (x{{ $det->cantidad }} uds a ${{ number_format($det->precio_unitario, 2) }})
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay facturas de compra registradas aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        const productosExistentes = @json($productos);
        const categoriasExistentes = @json($categorias);
        let filaIndex = 0;

        function agregarFilaCompra() {
            const tbody = document.getElementById('contenedorFilasCompra');
            const tr = document.createElement('tr');
            tr.id = `filaCompra_${filaIndex}`;

            const i = filaIndex;
            tr.innerHTML = `
                <td>
                    <select class="form-select form-select-sm fw-semibold select-tipo-fila" data-index="${i}" name="items[${i}][tipo]">
                        <option value="existente">Existente</option>
                        <option value="nuevo">Nuevo</option>
                    </select>
                </td>
                <td>
                    <!-- Contenedor Producto Existente -->
                    <div id="boxExistente_${i}">
                        <select class="form-select form-select-sm select-prod-existente" data-index="${i}" name="items[${i}][producto_id]">
                            <option value="">Seleccione producto...</option>
                            ${productosExistentes.map(p => `<option value="${p.id}" data-precio="${p.precio_compra}">${p.nombre} (Stock: ${p.stock_actual} | Costo: $${p.precio_compra})</option>`).join('')}
                        </select>
                    </div>

                    <!-- Contenedor Producto Nuevo -->
                    <div id="boxNuevo_${i}" class="d-none row g-2">
                        <div class="col-md-6">
                            <input type="text" name="items[${i}][nombre]" class="form-control form-control-sm" placeholder="Nombre del producto nuevo *">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="items[${i}][codigo_barras]" class="form-control form-control-sm mono" placeholder="Cód. barras (opcional)">
                        </div>
                        <div class="col-md-6">
                            <select name="items[${i}][categoria_id]" class="form-select form-select-sm">
                                <option value="">Categoría...</option>
                                ${categoriasExistentes.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="items[${i}][descripcion]" class="form-control form-control-sm" placeholder="Descripción breve (opcional)">
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" name="items[${i}][cantidad]" class="form-control form-control-sm input-compra-cant" min="1" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${i}][precio_unitario]" class="form-control form-control-sm mono input-compra-precio" min="0.01" placeholder="0.00" required>
                </td>
                <td class="mono fw-bold text-end" id="subtotal_${i}">
                    $0.00
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 btn-quitar-fila" data-index="${i}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            filaIndex++;
            calcularTotalesCompra();
        }

        function toggleTipoFila(index, tipo) {
            const boxExistente = document.getElementById(`boxExistente_${index}`);
            const boxNuevo = document.getElementById(`boxNuevo_${index}`);
            if (tipo === 'nuevo') {
                boxExistente.classList.add('d-none');
                boxNuevo.classList.remove('d-none');
            } else {
                boxExistente.classList.remove('d-none');
                boxNuevo.classList.add('d-none');
            }
            calcularTotalesCompra();
        }

        function alElegirProductoExistente(index, prodId) {
            const prod = productosExistentes.find(p => p.id == prodId);
            if (prod) {
                const tr = document.getElementById(`filaCompra_${index}`);
                const inputPrecio = tr.querySelector(`input[name="items[${index}][precio_unitario]"]`);
                if (inputPrecio && (!inputPrecio.value || Number(inputPrecio.value) === 0)) {
                    inputPrecio.value = prod.precio_compra;
                }
            }
            calcularTotalesCompra();
        }

        function quitarFilaCompra(index) {
            const tr = document.getElementById(`filaCompra_${index}`);
            if (tr) {
                tr.remove();
                calcularTotalesCompra();
            }
        }

        function calcularTotalesCompra() {
            let total = 0;
            const rows = document.querySelectorAll('#contenedorFilasCompra tr');
            rows.forEach(tr => {
                const inputCant = tr.querySelector('input[name*="[cantidad]"]');
                const inputPrecio = tr.querySelector('input[name*="[precio_unitario]"]');
                const tdSub = tr.querySelector('.text-end[id^="subtotal_"]');

                if (inputCant && inputPrecio && tdSub) {
                    const cant = parseFloat(inputCant.value) || 0;
                    const precio = parseFloat(inputPrecio.value) || 0;
                    const sub = cant * precio;
                    tdSub.textContent = '$' + sub.toFixed(2);
                    total += sub;
                }
            });

            document.getElementById('lblTotalCompra').textContent = '$' + total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const btnAgregar = document.getElementById('btnAgregarFilaCompra');
            const tbody = document.getElementById('contenedorFilasCompra');

            if (btnAgregar) {
                btnAgregar.addEventListener('click', agregarFilaCompra);
            }

            if (tbody) {
                tbody.addEventListener('change', (e) => {
                    if (e.target.classList.contains('select-tipo-fila')) {
                        toggleTipoFila(e.target.dataset.index, e.target.value);
                    } else if (e.target.classList.contains('select-prod-existente')) {
                        alElegirProductoExistente(e.target.dataset.index, e.target.value);
                    }
                });

                tbody.addEventListener('input', (e) => {
                    if (e.target.classList.contains('input-compra-cant') || e.target.classList.contains('input-compra-precio')) {
                        calcularTotalesCompra();
                    }
                });

                tbody.addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-quitar-fila');
                    if (btn) {
                        quitarFilaCompra(btn.dataset.index);
                    }
                });
            }

            // Agregar primera fila por defecto al cargar
            agregarFilaCompra();
        });
    </script>
</body>
</html>

