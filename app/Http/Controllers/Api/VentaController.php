<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\VentaResource;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\AuditService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VentaController extends Controller
{
    /**
     * Listado paginado de ventas con detalles y usuario.
     * Soporta filtro por hoy o fecha específica.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Venta::with(['usuario', 'detalles.producto']);

        if ($request->boolean('hoy') || $request->get('filtro') === 'hoy') {
            $query->whereDate('fecha', date('Y-m-d'));
        } elseif ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $ventas = $query->latest('fecha')
            ->paginate(min((int) ($request->get('per_page', 10)), 100));

        return response()->json([
            'data' => VentaResource::collection($ventas),
            'meta' => [
                'current_page' => $ventas->currentPage(),
                'last_page'    => $ventas->lastPage(),
                'per_page'     => $ventas->perPage(),
                'total'        => $ventas->total(),
            ],
        ]);
    }

    /**
     * Consulta específica de las ventas del día de hoy.
     */
    public function ventasHoy(Request $request): JsonResponse
    {
        $request->merge(['hoy' => true]);
        return $this->index($request);
    }

    /**
     * Muestra una venta específica.
     */
    public function show(Venta $venta): JsonResponse
    {
        return response()->json([
            'venta' => new VentaResource($venta->load(['usuario', 'detalles.producto'])),
        ]);
    }

    /**
     * Registra una venta dentro de una transacción con lockForUpdate().
     * Verifica stock disponible; si falta, revierte todo y responde 422.
     * Calcula subtotales y total exclusivamente en backend.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $venta = DB::transaction(function () use ($validated, $request) {
            $totalVenta = 0;
            $itemsDetalles = [];

            // 1. Fase de verificación con bloqueo pesimista de filas (lockForUpdate)
            foreach ($validated['items'] as $item) {
                $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->first();

                if (! $producto) {
                    throw new HttpResponseException(response()->json([
                        'message' => "El producto con ID {$item['producto_id']} no existe.",
                    ], 422));
                }

                $cantidad = (int) $item['cantidad'];

                if ($producto->stock_actual < $cantidad) {
                    throw new HttpResponseException(response()->json([
                        'message' => "Stock insuficiente para el producto '{$producto->nombre}'. Stock disponible: {$producto->stock_actual}, solicitado: {$cantidad}.",
                        'error_code' => 'INSUFFICIENT_STOCK',
                        'producto_id' => $producto->id,
                        'stock_actual' => $producto->stock_actual,
                    ], 422));
                }

                // Cálculo estricto en backend (nunca confiar en precios del cliente)
                $precioUnitario = (float) $producto->precio_venta;
                $subtotal = $cantidad * $precioUnitario;
                $totalVenta += $subtotal;

                $itemsDetalles[] = [
                    'producto'        => $producto,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                ];
            }

            // 2. Generar número de factura único si no fue suministrado
            $numeroFactura = $validated['numero_factura'] ?? ('FAC-' . strtoupper(Str::random(8)) . '-' . time());

            $venta = Venta::create([
                'numero_factura'    => $numeroFactura,
                'user_id'           => $request->user()->id,
                'metodo_pago'       => $validated['metodo_pago'],
                'total'             => $totalVenta,
                'fecha'             => now(),
                'cliente_nombre'    => $validated['cliente_nombre'] ?? null,
                'cliente_documento' => $validated['cliente_documento'] ?? null,
            ]);

            // 3. Descontar stock y registrar detalle
            foreach ($itemsDetalles as $detalle) {
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $detalle['producto']->id,
                    'cantidad'        => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal'        => $detalle['subtotal'],
                ]);

                // Descontar existencias de forma atómica
                $detalle['producto']->decrement('stock_actual', $detalle['cantidad']);
            }

            return $venta;
        });

        \App\Services\DebugLogService::log('VENTA_REGISTRADA', [
            'venta_id'       => $venta->id,
            'numero_factura' => $venta->numero_factura,
            'total'          => $venta->total,
            'metodo_pago'    => $venta->metodo_pago,
        ]);

        AuditService::log('venta_registrada', Venta::class, $venta->id, [
            'numero_factura' => $venta->numero_factura,
            'total'          => $venta->total,
            'metodo_pago'    => $venta->metodo_pago,
        ]);

        return response()->json([
            'message' => 'Venta facturada exitosamente y stock descontado.',
            'venta'   => new VentaResource($venta->load(['usuario', 'detalles.producto'])),
        ], 201);
    }
}
