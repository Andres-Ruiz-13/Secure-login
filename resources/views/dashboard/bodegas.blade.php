@extends('layouts.admin')

@section('title', 'Gestión de Bodegas, Kardex y Transferencias')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Bodegas & Kardex</li>
@endsection

@section('content')

    <!-- Encabezado del Módulo -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-body">
                <i class="bi bi-building-fill text-primary me-2"></i> Gestión de Bodegas, Kardex y Transferencias
            </h4>
            <span class="text-muted small">Control multi-bodega, trazabilidad de movimientos transaccionales y conciliación física.</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user()->isAdmin())
                <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaBodega">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Bodega
                </button>
            @endif
            @if(auth()->user()->isBodeguero() || auth()->user()->isAdmin())
                <button class="btn btn-outline-primary btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaTransferencia">
                    <i class="bi bi-arrow-left-right me-1"></i> Nueva Transferencia
                </button>
                <button class="btn btn-outline-warning text-dark btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevoAjuste">
                    <i class="bi bi-sliders me-1"></i> Solicitar Ajuste Físico
                </button>
            @endif
        </div>
    </div>

    <!-- Tarjetas de Resumen Rápido -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-cms p-3 d-flex flex-row align-items-center gap-3">
                <div class="p-3 rounded-3 bg-primary-subtle text-primary fs-3">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-body">{{ $warehouses->count() }}</div>
                    <div class="text-muted small">Bodegas Activas</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-cms p-3 d-flex flex-row align-items-center gap-3">
                <div class="p-3 rounded-3 bg-info-subtle text-info fs-3">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-body">{{ $transfers->where('estado', 'en_transito')->count() }}</div>
                    <div class="text-muted small">Transferencias en Tránsito</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-cms p-3 d-flex flex-row align-items-center gap-3">
                <div class="p-3 rounded-3 bg-warning-subtle text-warning fs-3">
                    <i class="bi bi-sliders"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-body">{{ $adjustments->where('estado', 'pendiente')->count() }}</div>
                    <div class="text-muted small">Ajustes Pendientes</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card card-cms p-3 d-flex flex-row align-items-center gap-3">
                <div class="p-3 rounded-3 bg-success-subtle text-success fs-3">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-body">{{ $movements->count() }}</div>
                    <div class="text-muted small">Movimientos Kardex</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas del Módulo -->
    <ul class="nav nav-pills mb-3 gap-2" id="bodegasTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-semibold" id="tab-bodegas" data-bs-toggle="pill" data-bs-target="#pane-bodegas" type="button">
                <i class="bi bi-building me-1"></i> Bodegas Físicas
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" id="tab-transferencias" data-bs-toggle="pill" data-bs-target="#pane-transferencias" type="button">
                <i class="bi bi-truck me-1"></i> Transferencias ({{ $transfers->whereIn('estado', ['pendiente', 'en_transito'])->count() }} activas)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" id="tab-ajustes" data-bs-toggle="pill" data-bs-target="#pane-ajustes" type="button">
                <i class="bi bi-sliders me-1"></i> Ajustes de Inventario Físico
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-semibold" id="tab-kardex" data-bs-toggle="pill" data-bs-target="#pane-kardex" type="button">
                <i class="bi bi-journal-check me-1"></i> Kardex Transaccional
            </button>
        </li>
    </ul>

    <div class="tab-content" id="bodegasTabsContent">

        <!-- ======================================================== -->
        <!-- 1. PESTAÑA: BODEGAS FÍSICAS                              -->
        <!-- ======================================================== -->
        <div class="tab-pane fade show active" id="pane-bodegas" role="tabpanel">
            <div class="row g-3">
                @forelse($warehouses as $w)
                    <div class="col-md-6 col-xl-4">
                        <div class="card card-cms h-100 shadow-sm border p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary text-white mono mb-1">{{ $w->codigo }}</span>
                                    <h5 class="fw-bold mb-0 text-body">{{ $w->nombre }}</h5>
                                </div>
                                <span class="badge {{ $w->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $w->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>

                            <p class="text-muted small mb-3">{{ $w->descripcion ?? 'Sin descripción adicional.' }}</p>

                            <div class="small text-muted mb-3 d-flex flex-column gap-1">
                                <div><i class="bi bi-geo-alt me-1 text-danger"></i> Ubicación: <b>{{ $w->ubicacion ?? 'No especificada' }}</b></div>
                                <div><i class="bi bi-person-badge me-1 text-primary"></i> Responsable: <b>{{ $w->responsable->name ?? 'Sin asignar' }}</b></div>
                                <div><i class="bi bi-box-seam me-1 text-info"></i> Variedad de productos: <b>{{ $w->stocks_count }} ítems</b></div>
                            </div>

                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="mono fw-bold text-primary">
                                    {{ $w->stocks->sum('stock_actual') }} unidades en stock
                                </span>
                                @if(auth()->user()->isAdmin())
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarBodega{{ $w->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal Editar Bodega -->
                    @if(auth()->user()->isAdmin())
                    <div class="modal fade" id="modalEditarBodega{{ $w->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content card-cms shadow border-0">
                                <form action="{{ route('bodegas.update', $w->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-bottom">
                                        <h5 class="modal-title fw-bold text-body">Editar Bodega: {{ $w->codigo }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4 row g-3">
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Nombre de Bodega</label>
                                            <input type="text" name="nombre" class="form-control" value="{{ $w->nombre }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Ubicación / Sede</label>
                                            <input type="text" name="ubicacion" class="form-control" value="{{ $w->ubicacion }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Responsable</label>
                                            <select name="responsable_id" class="form-select">
                                                <option value="">Seleccione encargado...</option>
                                                @foreach($bodegueros as $b)
                                                    <option value="{{ $b->id }}" {{ $w->responsable_id == $b->id ? 'selected' : '' }}>
                                                        {{ $b->name }} ({{ strtoupper($b->role) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Descripción</label>
                                            <textarea name="descripcion" class="form-control" rows="2">{{ $w->descripcion }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="checkActive{{ $w->id }}" {{ $w->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="checkActive{{ $w->id }}">Bodega Operativa y Habilitada</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        No hay bodegas registradas en la base de datos.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- 2. PESTAÑA: TRANSFERENCIAS (FLUJO 6 ESTADOS)             -->
        <!-- ======================================================== -->
        <div class="tab-pane fade" id="pane-transferencias" role="tabpanel">
            <div class="card card-cms shadow-sm border overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Código</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Ítems Transferidos</th>
                                <th>Estado</th>
                                <th>Solicitado Por</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $tr)
                                @php
                                    $badgeEstado = match($tr->estado) {
                                        'pendiente' => 'bg-warning text-dark',
                                        'aprobada', 'en_transito' => 'bg-info text-white',
                                        'recibida' => 'bg-success text-white',
                                        'rechazada', 'cancelada' => 'bg-danger text-white',
                                        default => 'bg-secondary text-white',
                                    };
                                @endphp
                                <tr>
                                    <td class="mono fw-bold small text-primary">{{ $tr->codigo }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $tr->origen->nombre ?? 'N/A' }}</span></td>
                                    <td><span class="badge bg-light text-dark border">{{ $tr->destino->nombre ?? 'N/A' }}</span></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($tr->items as $item)
                                                <span class="badge bg-secondary-subtle text-body border" style="font-size: 0.72rem;">
                                                    {{ $item->producto->nombre ?? 'Producto' }} (x{{ $item->cantidad }} uds)
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td><span class="badge {{ $badgeEstado }} text-uppercase" style="font-size: 0.7rem;">{{ str_replace('_', ' ', $tr->estado) }}</span></td>
                                    <td class="small">{{ $tr->solicitante->name ?? 'N/A' }}</td>
                                    <td class="small text-muted">{{ $tr->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Si está pendiente: Botón Aprobar (Pasa a en_transito y descuenta origen) -->
                                            @if($tr->estado === 'pendiente' && (auth()->user()->isAdmin() || auth()->user()->isBodeguero()))
                                                <form action="{{ route('inventory.transfers.approve', $tr->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary" title="Aprobar y Despachar en Tránsito">
                                                        <i class="bi bi-check-lg me-1"></i> Despachar
                                                    </button>
                                                </form>
                                                <form action="{{ route('inventory.transfers.reject', $tr->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Rechazar">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Si está en tránsito: Botón Confirmar Recepción (Suma a destino) -->
                                            @if($tr->estado === 'en_transito' && (auth()->user()->isAdmin() || auth()->user()->isBodeguero()))
                                                <form action="{{ route('inventory.transfers.receive', $tr->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success text-white" title="Confirmar Arribo Físico y Sumar Stock">
                                                        <i class="bi bi-box-arrow-in-down me-1"></i> Confirmar Recepción
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No hay registros de transferencias de mercancía aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- 3. PESTAÑA: AJUSTES DE INVENTARIO FÍSICO VS DIGITAL      -->
        <!-- ======================================================== -->
        <div class="tab-pane fade" id="pane-ajustes" role="tabpanel">
            <div class="card card-cms shadow-sm border overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Bodega</th>
                                <th class="text-center">Existencia Digital</th>
                                <th class="text-center">Existencia Física</th>
                                <th class="text-center">Diferencia</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Solicitante</th>
                                <th class="text-end">Aprobación (Admin)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $aj)
                                @php
                                    $badgeAdj = match($aj->estado) {
                                        'pendiente' => 'bg-warning text-dark',
                                        'aprobado' => 'bg-success text-white',
                                        'rechazado' => 'bg-danger text-white',
                                        default => 'bg-secondary text-white',
                                    };
                                @endphp
                                <tr>
                                    <td class="mono small fw-bold text-primary">{{ $aj->codigo }}</td>
                                    <td>
                                        <div class="fw-semibold text-body">{{ $aj->producto->nombre ?? 'N/A' }}</div>
                                        <small class="text-muted mono">{{ $aj->producto->codigo_barras ?? '' }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $aj->warehouse->nombre ?? 'N/A' }}</span></td>
                                    <td class="text-center mono">{{ $aj->existencia_anterior }} uds</td>
                                    <td class="text-center mono fw-bold">{{ $aj->existencia_nueva }} uds</td>
                                    <td class="text-center mono fw-bold {{ $aj->diferencia >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $aj->diferencia > 0 ? "+{$aj->diferencia}" : $aj->diferencia }} uds
                                    </td>
                                    <td class="small">{{ $aj->motivo }}</td>
                                    <td><span class="badge {{ $badgeAdj }} text-uppercase" style="font-size: 0.7rem;">{{ $aj->estado }}</span></td>
                                    <td class="small">{{ $aj->solicitante->name ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        @if($aj->estado === 'pendiente' && auth()->user()->isAdmin())
                                            <div class="btn-group btn-group-sm">
                                                <form action="{{ route('inventory.adjustments.approve', $aj->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Aprobar y Sincronizar Existencias">
                                                        <i class="bi bi-check-lg"></i> Aprobar
                                                    </button>
                                                </form>
                                                <form action="{{ route('inventory.adjustments.reject', $aj->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Rechazar">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <small class="text-muted">{{ $aj->autorizador->name ?? '—' }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        No hay solicitudes de ajuste de inventario registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- 4. PESTAÑA: KARDEX DE MOVIMIENTOS TRANSACCIONALES        -->
        <!-- ======================================================== -->
        <div class="tab-pane fade" id="pane-kardex" role="tabpanel">
            <div class="card card-cms shadow-sm border overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-searchable">
                        <thead class="table-light small">
                            <tr>
                                <th>Fecha & Hora</th>
                                <th>Producto</th>
                                <th>Bodega</th>
                                <th>Tipo de Movimiento</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Stock Anterior</th>
                                <th class="text-center">Stock Resultante</th>
                                <th>Doc. Referencia</th>
                                <th>Motivo / Glosa</th>
                                <th>Responsable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $m)
                                @php
                                    $tipoColor = match($m->tipo) {
                                        'compra', 'entrada_ajuste', 'transferencia_entrada', 'devolucion' => 'text-success fw-bold',
                                        default => 'text-danger fw-bold'
                                    };
                                    $isEntrada = in_array($m->tipo, ['compra', 'entrada_ajuste', 'transferencia_entrada', 'devolucion'], true);
                                @endphp
                                <tr>
                                    <td class="small text-muted mono">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="fw-semibold text-body small">{{ $m->producto->nombre ?? 'N/A' }}</div>
                                        <small class="text-muted mono">{{ $m->producto->codigo_barras ?? '' }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $m->warehouse->nombre ?? 'Bodega' }}</span></td>
                                    <td>
                                        <span class="badge {{ $isEntrada ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase" style="font-size: 0.68rem;">
                                            <i class="bi {{ $isEntrada ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }} me-1"></i>
                                            {{ str_replace('_', ' ', $m->tipo) }}
                                        </span>
                                    </td>
                                    <td class="text-center mono {{ $tipoColor }}">
                                        {{ $isEntrada ? "+{$m->cantidad}" : "-{$m->cantidad}" }} uds
                                    </td>
                                    <td class="text-center mono text-muted small">{{ $m->stock_anterior }}</td>
                                    <td class="text-center mono fw-bold text-body">{{ $m->stock_nuevo }}</td>
                                    <td class="mono small">{{ $m->documento_referencia ?? '—' }}</td>
                                    <td class="small text-muted" style="max-width: 200px;">{{ $m->motivo ?? '—' }}</td>
                                    <td class="small">{{ $m->user->name ?? 'Sistema' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        No hay movimientos de inventario registrados en el Kardex.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- MODALES DE LA FASE 4                                     -->
    <!-- ======================================================== -->

    <!-- Modal: Nueva Bodega (Solo Admin) -->
    @if(auth()->user()->isAdmin())
    <div class="modal fade" id="modalNuevaBodega" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content card-cms shadow border-0">
                <form action="{{ route('bodegas.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-body"><i class="bi bi-building-add text-primary me-2"></i> Crear Nueva Bodega</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Código Único *</label>
                            <input type="text" name="codigo" class="form-control mono" required placeholder="BOD-SUR-01">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Nombre de Bodega *</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Bodega Sucursal Sur">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Ubicación / Dirección</label>
                            <input type="text" name="ubicacion" class="form-control" placeholder="Calle 45 # 12-34 Sede Sur">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Bodeguero Responsable</label>
                            <select name="responsable_id" class="form-select">
                                <option value="">Seleccione encargado...</option>
                                @foreach($bodegueros as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ strtoupper($b->role) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripción / Observaciones</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles de capacidad y tipo de mercancía..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Crear Bodega</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal: Nueva Transferencia entre Bodegas -->
    <div class="modal fade" id="modalNuevaTransferencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-cms shadow border-0">
                <form action="{{ route('inventory.transfers.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-body"><i class="bi bi-arrow-left-right text-primary me-2"></i> Solicitar Transferencia de Mercancía</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bodega de Origen *</label>
                            <select name="origen_warehouse_id" class="form-select" required>
                                <option value="">Seleccione origen...</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->nombre }} ({{ $w->codigo }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bodega de Destino *</label>
                            <select name="destino_warehouse_id" class="form-select" required>
                                <option value="">Seleccione destino...</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->nombre }} ({{ $w->codigo }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Producto a Transferir *</label>
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <select name="items[0][producto_id]" class="form-select" required>
                                        <option value="">Seleccione producto...</option>
                                        @foreach($productos as $pr)
                                            <option value="{{ $pr->id }}">{{ $pr->nombre }} (Disp. Global: {{ $pr->stock_actual }} uds)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="items[0][cantidad]" class="form-control mono" min="1" value="1" placeholder="Cantidad" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Observaciones / Guía de Transporte</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Motivo del traslado, transportadora o número de precinto..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Enviar Solicitud de Traslado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Solicitar Ajuste Físico de Inventario -->
    <div class="modal fade" id="modalNuevoAjuste" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content card-cms shadow border-0">
                <form action="{{ route('inventory.adjustments.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-body"><i class="bi bi-sliders text-warning me-2"></i> Solicitar Ajuste Físico vs Digital</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Bodega donde se realizó el conteo *</label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Seleccione bodega...</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Producto *</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione producto...</option>
                                @foreach($productos as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->nombre }} (Stock Digital Global: {{ $pr->stock_actual }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Existencia Física Real Contada *</label>
                            <input type="number" name="existencia_nueva" class="form-control mono" min="0" required placeholder="Cantidad exacta contada">
                            <span class="text-muted" style="font-size: 0.75rem;">El sistema calculará automáticamente la diferencia faltante o sobrante.</span>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Motivo y Justificación del Ajuste *</label>
                            <textarea name="motivo" class="form-control" rows="3" required placeholder="Ej: Merma por rotura de embalaje durante almacenamiento..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm px-4 fw-semibold text-dark">Registrar Solicitud de Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
