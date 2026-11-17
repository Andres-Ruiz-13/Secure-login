@extends('layouts.admin')

@section('title', 'Punto de Venta (POS) y Caja | INBOT SYSTEM')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    .mono { font-family: 'JetBrains Mono', monospace; }
    .pos-card { border-radius: 1rem; border: 1px solid var(--admin-card-border, #e2e8f0); }
    .cart-table th { font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
    .cart-row:hover { background-color: rgba(5, 150, 105, 0.04); }
    .pos-total-display {
        background: linear-gradient(135deg, rgba(5, 150, 105, 0.08) 0%, rgba(16, 185, 129, 0.04) 100%);
        border: 1px dashed rgba(5, 150, 105, 0.3);
        border-radius: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Módulo de Ventas (POS)</li>
        </ol>
    </nav>

    <!-- Banner de Estado de Turno de Caja -->
    <div class="card pos-card mb-4 shadow-sm border-0">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 {{ $currentSession ? 'bg-success text-white' : 'bg-warning text-dark' }} d-flex align-items-center justify-content-center shadow-sm">
                        <i class="bi {{ $currentSession ? 'bi-cash-coin fs-3' : 'bi-lock fs-3' }}"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0 text-body">
                                {{ $currentSession ? 'Turno Activo: ' . $currentSession->cashRegister->nombre : 'Sin Turno de Caja Abierto' }}
                            </h5>
                            @if($currentSession)
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Operando
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold">
                                    <i class="bi bi-pause-circle me-1"></i> Cerrada
                                </span>
                            @endif
                        </div>
                        <div class="small text-muted mt-1">
                            @if($currentSession)
                                Apertura: <b>{{ $currentSession->apertura_at?->format('d/m/Y H:i') }}</b> |
                                Base Inicial: <b class="mono text-dark">$ {{ number_format($currentSession->saldo_inicial, 0, ',', '.') }}</b> |
                                Cajero: <b>{{ $currentSession->user->name }}</b>
                            @else
                                Debes aperturar una sesión de caja antes de realizar ventas o registrar movimientos.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($currentSession)
                        <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMovimientoCaja">
                            <i class="bi bi-arrow-left-right me-1"></i> Movimiento Efectivo
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCierreCaja">
                            <i class="bi bi-box-arrow-right me-1"></i> Arqueo y Cerrar Turno
                        </button>
                    @else
                        <button type="button" class="btn btn-success btn-sm px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
                            <i class="bi bi-key-fill me-1"></i> Abrir Turno de Caja
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Principal POS -->
    <div class="row g-4">
        
        <!-- Columna Izquierda: Facturador POS -->
        <div class="col-lg-7">
            <div class="card pos-card shadow-sm border-0 p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="fw-bold text-body mb-0">
                            <i class="bi bi-upc-scan text-success me-2"></i> Facturación de Venta
                        </h5>
                        <span class="text-muted small">Búsqueda rápida por lector de código de barras o selección de catálogo.</span>
                    </div>
                    <span class="badge bg-light text-dark border mono">
                        {{ $productos->count() }} ítems disponibles
                    </span>
                </div>

                @if(!$currentSession)
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                        <div>
                            <div class="fw-bold">Aviso de Caja</div>
                            <div class="small">Se recomienda abrir un turno de caja para vincular los cobros y mantener la conciliación contable.</div>
                        </div>
                    </div>
                @endif

                <!-- Búsqueda rápida por lector o catálogo -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Escanear Código de Barras</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-upc"></i></span>
                            <input type="text" id="inputScanCode" class="form-control mono" placeholder="Escanear o digitar código y pulsar Enter..." autofocus>
                            <button type="button" class="btn btn-outline-secondary" id="btnBuscarScanCode">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Seleccionar del Catálogo</label>
                        <select id="selectCatalogo" class="form-select">
                            <option value="">Seleccione producto...</option>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}" data-nombre="{{ $prod->nombre }}" data-precio="{{ $prod->precio_venta }}" data-stock="{{ $prod->stock_actual }}" data-codigo="{{ $prod->codigo_barras }}">
                                    {{ $prod->nombre }} — $ {{ number_format($prod->precio_venta, 0, ',', '.') }} (Disp: {{ $prod->stock_actual }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Formulario POS y Carrito -->
                <form action="{{ route('cms.ventas.store') }}" method="POST" id="formVentaCaja">
                    @csrf
                    
                    <div class="table-responsive mb-3 border rounded-3" style="min-height: 220px; max-height: 320px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 cart-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width: 110px;">Precio</th>
                                    <th style="width: 100px;">Cantidad</th>
                                    <th style="width: 110px;" class="text-end">Subtotal</th>
                                    <th style="width: 50px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorItemsVenta">
                                <tr id="filaVaciaVenta">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        El carrito de venta está vacío.<br>Escanea un código de barras o selecciona un producto arriba.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Datos del Cliente y Forma de Pago -->
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

                    <!-- Panel de Total y Confirmación -->
                    <div class="pos-total-display p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold d-block">TOTAL A COBRAR</span>
                            <span class="mono fw-bold fs-2 text-success" id="lblTotalVenta">$0.00</span>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg px-4 fw-bold shadow-sm" id="btnConfirmarVenta" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i> Cobrar y Facturar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Columna Derecha: Turno Actual & Ventas del Día -->
        <div class="col-lg-5">
            <div class="card pos-card shadow-sm border-0 p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="fw-bold text-body mb-0">
                            <i class="bi bi-clock-history text-primary me-2"></i> Operaciones del Turno
                        </h5>
                        <span class="text-muted small">
                            Ventas turno: <b>{{ $ventasTurno->count() }}</b> | $ {{ number_format($totalTurno, 0, ',', '.') }}
                        </span>
                    </div>
                    <a href="{{ route('reportes.word', ['tipo' => 'ventas', 'fecha' => date('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm" title="Descargar Reporte Word de Hoy">
                        <i class="bi bi-file-earmark-word me-1"></i> Word
                    </a>
                </div>

                <!-- Pestañas de Resumen -->
                <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small fw-bold py-2" id="tab-ventas-turno" data-bs-toggle="tab" data-bs-target="#pane-ventas-turno" type="button" role="tab">
                            <i class="bi bi-receipt me-1"></i> Mis Ventas ({{ $ventasTurno->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-bold py-2" id="tab-ventas-hoy" data-bs-toggle="tab" data-bs-target="#pane-ventas-hoy" type="button" role="tab">
                            <i class="bi bi-calendar3 me-1"></i> Hoy General ({{ $ventasHoy->count() }})
                        </button>
                    </li>
                    @if($currentSession)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small fw-bold py-2" id="tab-movs-caja" data-bs-toggle="tab" data-bs-target="#pane-movs-caja" type="button" role="tab">
                                <i class="bi bi-arrow-left-right me-1"></i> Movimientos
                            </button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content flex-grow-1">
                    <!-- Tab 1: Ventas de este turno -->
                    <div class="tab-pane fade show active" id="pane-ventas-turno" role="tabpanel">
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Factura</th>
                                        <th>Hora</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ventasTurno as $vt)
                                        <tr>
                                            <td class="mono fw-bold text-success">{{ $vt->numero_factura }}</td>
                                            <td class="text-muted">{{ $vt->fecha ? $vt->fecha->format('H:i') : $vt->created_at->format('H:i') }}</td>
                                            <td class="text-truncate" style="max-width: 110px;">{{ $vt->cliente_nombre ?? 'Consumidor Final' }}</td>
                                            <td class="mono fw-bold text-end">$ {{ number_format($vt->total, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                No has registrado ventas en este turno aún.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Ventas del día general -->
                    <div class="tab-pane fade" id="pane-ventas-hoy" role="tabpanel">
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Factura</th>
                                        <th>Cajero</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ventasHoy as $vh)
                                        <tr>
                                            <td class="mono fw-bold text-primary">{{ $vh->numero_factura }}</td>
                                            <td class="small">{{ $vh->usuario->name ?? 'N/A' }}</td>
                                            <td class="mono fw-bold text-end">$ {{ number_format($vh->total, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                No se han emitido facturas hoy.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Movimientos de caja -->
                    @if($currentSession)
                        <div class="tab-pane fade" id="pane-movs-caja" role="tabpanel">
                            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Motivo</th>
                                            <th class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($currentSession->movements as $m)
                                            <tr>
                                                <td>
                                                    <span class="badge {{ $m->tipo === 'ingreso' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ strtoupper($m->tipo) }}
                                                    </span>
                                                </td>
                                                <td>{{ $m->motivo }}</td>
                                                <td class="mono fw-bold text-end {{ $m->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                                    {{ $m->tipo === 'ingreso' ? '+' : '-' }}$ {{ number_format($m->monto, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">
                                                    Sin movimientos extraordinarios de efectivo en este turno.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ======================================================== -->
<!-- MODALES DE CONTROL DE CAJA                               -->
<!-- ======================================================== -->

<!-- Modal: Abrir Turno de Caja -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('cash.session.open') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-key-fill me-2"></i> Apertura de Turno de Caja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Seleccionar Caja Registradora</label>
                    <select name="cash_register_id" class="form-select" required>
                        @foreach($cashRegisters as $cr)
                            <option value="{{ $cr->id }}">{{ $cr->nombre }} ({{ $cr->warehouse?->nombre ?? 'Sin bodega' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Saldo Base Inicial ($ Efectivo en Gaveta - COP)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="1" min="0" name="saldo_inicial" class="form-control mono" placeholder="0" value="0" required>
                    </div>
                    <small class="text-muted">Monto físico con el que arranca el turno de caja (en pesos colombianos).</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Observaciones de Apertura</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Novedades de apertura (opcional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold">Iniciar Turno</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Registrar Movimiento de Caja -->
@if($currentSession)
<div class="modal fade" id="modalMovimientoCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('cash.session.movement', $currentSession->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> Movimiento Extraordinario de Caja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tipo de Movimiento</label>
                    <select name="tipo" class="form-select" required>
                        <option value="ingreso">Ingreso de Efectivo (Aporte extra / Cambio)</option>
                        <option value="egreso">Egreso de Efectivo (Gasto menor / Retiro de seguridad)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Monto ($ COP)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="1" min="1" name="monto" class="form-control mono" placeholder="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Motivo o Justificación</label>
                    <input type="text" name="motivo" class="form-control" placeholder="Ej: Compra de insumos de aseo" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary fw-bold">Registrar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Arqueo y Cierre de Turno -->
<div class="modal fade" id="modalCierreCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('cash.session.close', $currentSession->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-calculator me-2"></i> Arqueo y Cierre de Caja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Base Inicial:</span>
                        <span class="mono fw-bold">$ {{ number_format($currentSession->saldo_inicial, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Ventas en Efectivo:</span>
                        <span class="mono fw-bold text-success">+$ {{ number_format($currentSession->ventas()->where('metodo_pago', 'efectivo')->sum('total'), 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Movimientos Netos:</span>
                        <span class="mono fw-bold">
                            @php
                                $movsNeto = $currentSession->movements()->where('tipo', 'ingreso')->sum('monto') - $currentSession->movements()->where('tipo', 'egreso')->sum('monto');
                            @endphp
                            {{ $movsNeto >= 0 ? '+' : '' }}$ {{ number_format($movsNeto, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Dinero Físico en Gaveta (Conteo Real en COP)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="1" min="0" name="saldo_final_real" class="form-control mono fs-5 fw-bold" placeholder="0" required>
                    </div>
                    <small class="text-muted">El sistema calculará automáticamente sobrantes o faltantes contra el balance del sistema.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Observaciones de Cierre</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalles del arqueo físico o justificación de descuadres"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuar Turno</button>
                <button type="submit" class="btn btn-danger fw-bold">Confirmar Arqueo y Cerrar Caja</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    const catalogo = @json($productos);
    let carrito = [];

    function buscarYAgregarPorCodigo() {
        const input = document.getElementById('inputScanCode');
        if (!input) return;
        const code = input.value.trim();
        if (!code) return;

        const prod = catalogo.find(p => p.codigo_barras && p.codigo_barras.toLowerCase() === code.toLowerCase());
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
        if (!tbody || !btnCobrar || !lblTotal) return;

        if (carrito.length === 0) {
            tbody.innerHTML = `
                <tr id="filaVaciaVenta">
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        El carrito de venta está vacío.<br>Escanea un código de barras o selecciona un producto arriba.
                    </td>
                </tr>
            `;
            lblTotal.textContent = '$ 0';
            btnCobrar.disabled = true;
            return;
        }

        let html = '';
        let total = 0;

        carrito.forEach((item, idx) => {
            const sub = item.cantidad * item.precio_venta;
            total += sub;

            html += `
                <tr class="cart-row">
                    <td>
                        <input type="hidden" name="items[${idx}][producto_id]" value="${item.id}">
                        <div class="fw-semibold text-dark">${item.nombre}</div>
                        <small class="text-muted mono">${item.codigo_barras} | Disp: ${item.stock_actual} uds</small>
                    </td>
                    <td class="mono">$ ${Math.round(item.precio_venta).toLocaleString('es-CO')}</td>
                    <td>
                        <input type="number" name="items[${idx}][cantidad]" class="form-control form-control-sm mono text-center input-item-cant" data-id="${item.id}" min="1" max="${item.stock_actual}" value="${item.cantidad}">
                    </td>
                    <td class="mono fw-bold text-end text-dark">$ ${Math.round(sub).toLocaleString('es-CO')}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2 btn-item-quitar" data-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        lblTotal.textContent = '$ ' + Math.round(total).toLocaleString('es-CO');
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
@endpush
