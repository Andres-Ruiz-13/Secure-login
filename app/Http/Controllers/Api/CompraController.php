<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Listado de compras registradas con proveedor y detalles.
     */
    public function index(Request $request): JsonResponse
    {
        $compras = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->latest('fecha')
            ->paginate(min((int) ($request->get('per_page', 15)), 100));

        return response()->json([
            'data' => CompraResource::collection($compras),
            'meta' => [
                'current_page' => $compras->currentPage(),
                'total'        => $compras->total(),
            ],
        ]);
    }

    /**
     * Muestra una compra específica.
     */
    public function show(Compra $compra): JsonResponse
    {
        return response()->json([
            'compra' => new CompraResource($compra->load(['proveedor', 'usuario', 'detalles.producto'])),
        ]);
    }

    /**
     * Registra una compra a proveedor dentro de una transacción atómica e incrementa el stock.
     * Permite productos existentes o productos nuevos con cálculo automático de precio de venta.
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $globalProfit = (float) ($validated['porcentaje_ganancia'] ?? config('inbot.default_profit_percentage', 30.0));

        $compra = DB::transaction(function () use ($validated, $request, $globalProfit) {
            $totalCompra = 0;

            $compra = Compra::create([
                'numero_factura' => $validated['numero_factura'],
                'proveedor_id'   => $validated['proveedor_id'],
                'user_id'        => $request->user()->id,
                'total'          => 0, // Se calcula con los items
                'fecha'          => $validated['fecha'] ?? now(),
                'observaciones'  => $validated['observaciones'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $cantidad = (int) $item['cantidad'];
                $precioUnitario = (float) $item['precio_unitario'];
                $subtotal = round($cantidad * $precioUnitario, 2);

                // Caso 1: Producto Nuevo
                if (! empty($item['nombre']) || (isset($item['tipo']) && $item['tipo'] === 'nuevo')) {
                    $itemProfit = isset($item['porcentaje_ganancia']) ? (float) $item['porcentaje_ganancia'] : $globalProfit;
                    
                    $codigoBarras = ! empty($item['codigo_barras']) 
                        ? $item['codigo_barras'] 
                        : ('GEN-' . strtoupper(\Illuminate\Support\Str::random(10)));

                    // Garantizar código único
                    while (Producto::where('codigo_barras', $codigoBarras)->exists()) {
                        $codigoBarras = 'GEN-' . strtoupper(\Illuminate\Support\Str::random(10));
                    }

                    $producto = Producto::create([
                        'codigo_barras'       => $codigoBarras,
                        'nombre'              => $item['nombre'],
                        'descripcion'         => $item['descripcion'] ?? null,
                        'categoria_id'        => $item['categoria_id'] ?? null,
                        'proveedor_id'        => $validated['proveedor_id'],
                        'precio_compra'       => $precioUnitario,
                        'porcentaje_ganancia' => $itemProfit,
                        'stock_actual'        => $cantidad,
                        'stock_minimo'        => 5,
                        'is_active'           => true,
                    ]);
                } else {
                    // Caso 2: Producto Existente
                    $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();

                    // Incrementar stock atómicamente
                    $producto->increment('stock_actual', $cantidad);

                    // Actualizar último precio de compra y recalcular precio de venta
                    if ($precioUnitario > 0) {
                        $producto->update(['precio_compra' => $precioUnitario]);
                    }
                }

                DetalleCompra::create([
                    'compra_id'       => $compra->id,
                    'producto_id'     => $producto->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                ]);

                $totalCompra += $subtotal;
            }

            $compra->update(['total' => $totalCompra]);

            return $compra;
        });

        \App\Services\DebugLogService::log('COMPRA_REGISTRADA', [
            'compra_id'      => $compra->id,
            'numero_factura' => $compra->numero_factura,
            'proveedor_id'   => $compra->proveedor_id,
            'total'          => $compra->total,
            'items_count'    => count($validated['items']),
        ]);

        AuditService::log('compra_registrada', Compra::class, $compra->id, [
            'numero_factura' => $compra->numero_factura,
            'proveedor_id'   => $compra->proveedor_id,
            'total'          => $compra->total,
        ]);

        return response()->json([
            'message' => 'Compra registrada con éxito y stock actualizado.',
            'compra'  => new CompraResource($compra->load(['proveedor', 'usuario', 'detalles.producto'])),
        ], 201);
    }
}
