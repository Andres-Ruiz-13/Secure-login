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
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $compra = DB::transaction(function () use ($validated, $request) {
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
                // Bloquear fila del producto para evitar condiciones de carrera
                $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();

                $cantidad = (int) $item['cantidad'];
                $precioUnitario = (float) $item['precio_unitario'];
                $subtotal = $cantidad * $precioUnitario;

                DetalleCompra::create([
                    'compra_id'       => $compra->id,
                    'producto_id'     => $producto->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                ]);

                // Incrementar stock atómicamente
                $producto->increment('stock_actual', $cantidad);

                // Opcional: actualizar último precio de compra si aplica
                if ($precioUnitario > 0) {
                    $producto->update(['precio_compra' => $precioUnitario]);
                }

                $totalCompra += $subtotal;
            }

            $compra->update(['total' => $totalCompra]);

            return $compra;
        });

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
