<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Producto;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    /**
     * Panel Principal de Gestión de Bodegas, Kardex y Transferencias
     */
    public function index(): View
    {
        $warehouses = Warehouse::with(['responsable', 'stocks.producto'])
            ->withCount('stocks')
            ->orderBy('nombre')
            ->get();

        $productos = Producto::where('is_active', true)
            ->with('inventoryStocks')
            ->orderBy('nombre')
            ->get();

        $transfers = StockTransfer::with(['origen', 'destino', 'solicitante', 'autorizador', 'receptor', 'items.producto'])
            ->latest('id')
            ->take(30)
            ->get();

        $adjustments = InventoryAdjustment::with(['producto', 'warehouse', 'solicitante', 'autorizador'])
            ->latest('id')
            ->take(30)
            ->get();

        $movements = InventoryMovement::with(['producto', 'warehouse', 'user'])
            ->latest('id')
            ->take(50)
            ->get();

        $bodegueros = User::whereIn('role', ['admin', 'bodeguero'])->orderBy('name')->get();

        return view('dashboard.bodegas', compact(
            'warehouses',
            'productos',
            'transfers',
            'adjustments',
            'movements',
            'bodegueros'
        ));
    }

    /**
     * Registrar una nueva Bodega
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo'         => ['required', 'string', 'max:50', 'unique:warehouses,codigo'],
            'nombre'         => ['required', 'string', 'max:255'],
            'ubicacion'      => ['nullable', 'string', 'max:255'],
            'descripcion'    => ['nullable', 'string', 'max:500'],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ]);

        $warehouse = Warehouse::create([
            'codigo'         => strtoupper($validated['codigo']),
            'nombre'         => $validated['nombre'],
            'ubicacion'      => $validated['ubicacion'] ?? null,
            'descripcion'    => $validated['descripcion'] ?? null,
            'responsable_id' => $validated['responsable_id'] ?? null,
            'is_active'      => true,
        ]);

        if (!empty($validated['responsable_id'])) {
            $warehouse->users()->syncWithoutDetaching([$validated['responsable_id']]);
        }

        return redirect()->route('bodegas.index')->with('success', 'Bodega "' . $warehouse->nombre . '" registrada correctamente.');
    }

    /**
     * Actualizar información de una Bodega
     */
    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'         => ['required', 'string', 'max:255'],
            'ubicacion'      => ['nullable', 'string', 'max:255'],
            'descripcion'    => ['nullable', 'string', 'max:500'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $warehouse->update([
            'nombre'         => $validated['nombre'],
            'ubicacion'      => $validated['ubicacion'] ?? null,
            'descripcion'    => $validated['descripcion'] ?? null,
            'responsable_id' => $validated['responsable_id'] ?? null,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        if (!empty($validated['responsable_id'])) {
            $warehouse->users()->syncWithoutDetaching([$validated['responsable_id']]);
        }

        return redirect()->route('bodegas.index')->with('success', 'Bodega actualizada correctamente.');
    }

    /**
     * Crear solicitud de transferencia entre bodegas (Flujo 6 Estados: Inicia en Pendiente)
     */
    public function storeTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'origen_warehouse_id'  => ['required', 'exists:warehouses,id', 'different:destino_warehouse_id'],
            'destino_warehouse_id' => ['required', 'exists:warehouses,id'],
            'observaciones'        => ['nullable', 'string', 'max:500'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.producto_id'  => ['required', 'exists:productos,id'],
            'items.*.cantidad'     => ['required', 'integer', 'min:1'],
        ]);

        // Verificar existencias en la bodega de origen
        $origenId = $validated['origen_warehouse_id'];
        foreach ($validated['items'] as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            $stockOrigen = (int) (InventoryStock::where('warehouse_id', $origenId)
                ->where('producto_id', $producto->id)
                ->value('stock_actual') ?? 0);

            if ($item['cantidad'] > $stockOrigen && $stockOrigen <= 0) {
                // Si la bodega no tiene registro aún, fallback a stock general si es bodega 1
                if ($item['cantidad'] > $producto->stock_actual) {
                    return back()->with('error', 'Existencias insuficientes para "' . $producto->nombre . '". Disponible en origen: ' . $stockOrigen . ' uds.');
                }
            }
        }

        $codigo = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        DB::transaction(function () use ($validated, $codigo, $request) {
            $transfer = StockTransfer::create([
                'codigo'               => $codigo,
                'origen_warehouse_id'  => $validated['origen_warehouse_id'],
                'destino_warehouse_id' => $validated['destino_warehouse_id'],
                'estado'               => 'pendiente',
                'solicitado_por'       => $request->user()->id,
                'observaciones'        => $validated['observaciones'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad'    => $item['cantidad'],
                ]);
            }
        });

        return redirect()->route('bodegas.index')->with('success', 'Solicitud de transferencia ' . $codigo . ' creada con éxito (Estado: Pendiente).');
    }

    /**
     * Aprobar transferencia: Pasa a "en_transito" y descuenta de la bodega de origen
     */
    public function approveTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->estado !== 'pendiente') {
            return back()->with('error', 'La transferencia no se encuentra en estado pendiente.');
        }

        DB::transaction(function () use ($transfer, $request) {
            foreach ($transfer->items as $item) {
                $stockOrigen = InventoryStock::firstOrCreate(
                    [
                        'warehouse_id' => $transfer->origen_warehouse_id,
                        'producto_id'  => $item->producto_id,
                    ],
                    ['stock_actual' => 0]
                );

                $stockAnterior = $stockOrigen->stock_actual;
                $stockNuevo = max(0, $stockAnterior - $item->cantidad);

                $stockOrigen->update(['stock_actual' => $stockNuevo]);

                // Registrar en Kardex
                InventoryMovement::create([
                    'producto_id'          => $item->producto_id,
                    'warehouse_id'         => $transfer->origen_warehouse_id,
                    'tipo'                 => 'transferencia_salida',
                    'cantidad'             => $item->cantidad,
                    'stock_anterior'       => $stockAnterior,
                    'stock_nuevo'          => $stockNuevo,
                    'documento_referencia' => $transfer->codigo,
                    'motivo'               => 'Salida por transferencia en tránsito a bodega ' . $transfer->destino->nombre,
                    'user_id'              => $request->user()->id,
                ]);
            }

            $transfer->update([
                'estado'         => 'en_transito',
                'autorizado_por' => $request->user()->id,
                'aprobada_at'    => now(),
            ]);
        });

        return redirect()->route('bodegas.index')->with('success', 'Transferencia ' . $transfer->codigo . ' aprobada. Mercancía despachada en tránsito.');
    }

    /**
     * Confirmar recepción de transferencia: Pasa a "recibida" y suma a la bodega de destino
     */
    public function receiveTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->estado !== 'en_transito') {
            return back()->with('error', 'Solo transferencias en tránsito pueden ser confirmadas como recibidas.');
        }

        DB::transaction(function () use ($transfer, $request) {
            foreach ($transfer->items as $item) {
                $stockDestino = InventoryStock::firstOrCreate(
                    [
                        'warehouse_id' => $transfer->destino_warehouse_id,
                        'producto_id'  => $item->producto_id,
                    ],
                    ['stock_actual' => 0, 'stock_minimo' => 5]
                );

                $stockAnterior = $stockDestino->stock_actual;
                $stockNuevo = $stockAnterior + $item->cantidad;

                $stockDestino->update(['stock_actual' => $stockNuevo]);

                // Registrar en Kardex de destino
                InventoryMovement::create([
                    'producto_id'          => $item->producto_id,
                    'warehouse_id'         => $transfer->destino_warehouse_id,
                    'tipo'                 => 'transferencia_entrada',
                    'cantidad'             => $item->cantidad,
                    'stock_anterior'       => $stockAnterior,
                    'stock_nuevo'          => $stockNuevo,
                    'documento_referencia' => $transfer->codigo,
                    'motivo'               => 'Recepción física de transferencia desde ' . $transfer->origen->nombre,
                    'user_id'              => $request->user()->id,
                ]);
            }

            $transfer->update([
                'estado'      => 'recibida',
                'recibido_por' => $request->user()->id,
                'recibida_at' => now(),
            ]);
        });

        return redirect()->route('bodegas.index')->with('success', 'Transferencia ' . $transfer->codigo . ' recibida y stock acreditado en ' . $transfer->destino->nombre . '.');
    }

    /**
     * Rechazar o cancelar transferencia
     */
    public function rejectTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        if (in_array($transfer->estado, ['recibida', 'cancelada', 'rechazada'], true)) {
            return back()->with('error', 'La transferencia ya tiene un estado final inalterable.');
        }

        DB::transaction(function () use ($transfer, $request) {
            // Si ya estaba en tránsito, reincorporar las existencias a la bodega origen
            if ($transfer->estado === 'en_transito') {
                foreach ($transfer->items as $item) {
                    $stockOrigen = InventoryStock::firstOrCreate(
                        [
                            'warehouse_id' => $transfer->origen_warehouse_id,
                            'producto_id'  => $item->producto_id,
                        ],
                        ['stock_actual' => 0]
                    );

                    $stockAnterior = $stockOrigen->stock_actual;
                    $stockNuevo = $stockAnterior + $item->cantidad;
                    $stockOrigen->update(['stock_actual' => $stockNuevo]);

                    InventoryMovement::create([
                        'producto_id'          => $item->producto_id,
                        'warehouse_id'         => $transfer->origen_warehouse_id,
                        'tipo'                 => 'entrada_ajuste',
                        'cantidad'             => $item->cantidad,
                        'stock_anterior'       => $stockAnterior,
                        'stock_nuevo'          => $stockNuevo,
                        'documento_referencia' => $transfer->codigo,
                        'motivo'               => 'Reincorporación de stock por transferencia rechazada/cancelada',
                        'user_id'              => $request->user()->id,
                    ]);
                }
            }

            $transfer->update([
                'estado' => 'rechazada',
                'observaciones' => trim(($transfer->observaciones ?? '') . ' | Rechazada por ' . $request->user()->name),
            ]);
        });

        return redirect()->route('bodegas.index')->with('success', 'Transferencia ' . $transfer->codigo . ' rechazada.');
    }

    /**
     * Solicitar Ajuste de Inventario (Físico vs Digital)
     */
    public function storeAdjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id'      => ['required', 'exists:productos,id'],
            'warehouse_id'     => ['required', 'exists:warehouses,id'],
            'existencia_nueva' => ['required', 'integer', 'min:0'],
            'motivo'           => ['required', 'string', 'max:500'],
        ]);

        $stock = InventoryStock::firstOrCreate(
            [
                'warehouse_id' => $validated['warehouse_id'],
                'producto_id'  => $validated['producto_id'],
            ],
            ['stock_actual' => 0]
        );

        $existenciaAnterior = $stock->stock_actual;
        $existenciaNueva = (int) $validated['existencia_nueva'];
        $diferencia = $existenciaNueva - $existenciaAnterior;

        if ($diferencia === 0) {
            return back()->with('error', 'La nueva existencia coincide con la existencia digital actual. No hay ajuste que registrar.');
        }

        $codigo = 'AJU-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        InventoryAdjustment::create([
            'codigo'              => $codigo,
            'producto_id'         => $validated['producto_id'],
            'warehouse_id'        => $validated['warehouse_id'],
            'existencia_anterior' => $existenciaAnterior,
            'existencia_nueva'    => $existenciaNueva,
            'diferencia'          => $diferencia,
            'motivo'              => $validated['motivo'],
            'estado'              => 'pendiente',
            'solicitado_por'      => $request->user()->id,
        ]);

        return redirect()->route('bodegas.index')->with('success', 'Solicitud de ajuste ' . $codigo . ' registrada (' . ($diferencia > 0 ? "+$diferencia" : "$diferencia") . ' uds). Pendiente de aprobación administrativa.');
    }

    /**
     * Aprobar ajuste de inventario (Solo Administrador)
     */
    public function approveAdjustment(Request $request, InventoryAdjustment $adjustment): RedirectResponse
    {
        if ($adjustment->estado !== 'pendiente') {
            return back()->with('error', 'El ajuste ya fue procesado anteriormente.');
        }

        DB::transaction(function () use ($adjustment, $request) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'warehouse_id' => $adjustment->warehouse_id,
                    'producto_id'  => $adjustment->producto_id,
                ],
                ['stock_actual' => $adjustment->existencia_anterior]
            );

            $stockAnterior = $stock->stock_actual;
            $stockNuevo = $adjustment->existencia_nueva;

            $stock->update(['stock_actual' => $stockNuevo]);

            // Actualizar stock global del producto
            $producto = $adjustment->producto;
            $producto->increment('stock_actual', $adjustment->diferencia);

            // Registrar en Kardex
            InventoryMovement::create([
                'producto_id'          => $adjustment->producto_id,
                'warehouse_id'         => $adjustment->warehouse_id,
                'tipo'                 => $adjustment->diferencia >= 0 ? 'ajuste_positivo' : 'ajuste_negativo',
                'cantidad'             => abs($adjustment->diferencia),
                'stock_anterior'       => $stockAnterior,
                'stock_nuevo'          => $stockNuevo,
                'documento_referencia' => $adjustment->codigo,
                'motivo'               => 'Ajuste de inventario físico conciliado: ' . $adjustment->motivo,
                'user_id'              => $request->user()->id,
            ]);

            $adjustment->update([
                'estado'         => 'aprobado',
                'autorizado_por' => $request->user()->id,
                'autorizado_at'  => now(),
            ]);
        });

        return redirect()->route('bodegas.index')->with('success', 'Ajuste ' . $adjustment->codigo . ' aprobado. Existencias y Kardex sincronizados.');
    }

    /**
     * Rechazar ajuste de inventario
     */
    public function rejectAdjustment(Request $request, InventoryAdjustment $adjustment): RedirectResponse
    {
        if ($adjustment->estado !== 'pendiente') {
            return back()->with('error', 'El ajuste ya fue procesado.');
        }

        $adjustment->update([
            'estado'         => 'rechazado',
            'autorizado_por' => $request->user()->id,
            'autorizado_at'  => now(),
        ]);

        return redirect()->route('bodegas.index')->with('success', 'Ajuste ' . $adjustment->codigo . ' rechazado.');
    }
}
