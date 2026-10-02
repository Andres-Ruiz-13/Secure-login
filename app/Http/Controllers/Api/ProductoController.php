<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Services\AuditService;
use App\Services\ImageHashService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    /**
     * Listado paginado de productos con filtros de búsqueda y categoría.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with(['categoria', 'proveedor']);

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', $term)
                  ->orWhere('codigo_barras', 'like', $term);
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        $perPage = min((int) ($request->get('per_page', 15)), 100);
        $productos = $query->latest('id')->paginate($perPage);

        return response()->json([
            'data' => ProductoResource::collection($productos),
            'meta' => [
                'current_page' => $productos->currentPage(),
                'last_page'    => $productos->lastPage(),
                'per_page'     => $productos->perPage(),
                'total'        => $productos->total(),
            ],
        ]);
    }

    /**
     * Endpoint especializado para productos con stock bajo (stock actual <= stock mínimo).
     * GET /api/stock_bajo
     */
    public function stockBajo(Request $request): JsonResponse
    {
        $productos = Producto::with(['categoria', 'proveedor'])
            ->stockBajo()
            ->latest('stock_actual')
            ->paginate(min((int) ($request->get('per_page', 15)), 100));

        return response()->json([
            'data'    => ProductoResource::collection($productos),
            'message' => 'Productos con stock crítico o bajo su umbral mínimo.',
            'meta'    => [
                'total_alertas' => $productos->total(),
                'current_page'  => $productos->currentPage(),
            ],
        ]);
    }

    /**
     * Crea un nuevo producto. Solo accesible por Admin y Bodeguero (Vendedor -> 403).
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Cálculo de precio de venta automático en backend
        $data['precio_venta'] = PricingService::calculateSalePrice(
            (float) $data['precio_compra'],
            (float) ($data['porcentaje_ganancia'] ?? config('inbot.default_profit_percentage', 30.0))
        );

        // Procesamiento seguro de imagen y generación de aHash
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . $extension;
            $path = $file->storeAs('productos', $fileName, 'public');
            $data['imagen'] = $path;

            // Precalcular Average Hash de 64 bits para búsqueda visual
            $absolutePath = Storage::disk('public')->path($path);
            $data['image_hash'] = ImageHashService::generateHash($absolutePath);
        }

        $producto = Producto::create($data);

        AuditService::log('producto_creado', Producto::class, $producto->id, [
            'nombre'        => $producto->nombre,
            'codigo_barras' => $producto->codigo_barras,
            'precio_venta'  => $producto->precio_venta,
        ]);

        return response()->json([
            'message'  => 'Producto creado exitosamente.',
            'producto' => new ProductoResource($producto->load(['categoria', 'proveedor'])),
        ], 201);
    }

    /**
     * Muestra el detalle de un producto.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'producto' => new ProductoResource($producto->load(['categoria', 'proveedor'])),
        ]);
    }

    /**
     * Actualiza un producto existente. Solo Admin y Bodeguero (Vendedor -> 403).
     */
    public function update(UpdateProductRequest $request, Producto $producto): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['precio_compra']) || isset($data['porcentaje_ganancia'])) {
            $purchase = (float) ($data['precio_compra'] ?? $producto->precio_compra);
            $profit = (float) ($data['porcentaje_ganancia'] ?? $producto->porcentaje_ganancia);
            $data['precio_venta'] = PricingService::calculateSalePrice($purchase, $profit);
        }

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior de Storage si existía
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . $extension;
            $path = $file->storeAs('productos', $fileName, 'public');
            $data['imagen'] = $path;

            $absolutePath = Storage::disk('public')->path($path);
            $data['image_hash'] = ImageHashService::generateHash($absolutePath);
        }

        $producto->update($data);

        AuditService::log('producto_actualizado', Producto::class, $producto->id, [
            'nombre'       => $producto->nombre,
            'precio_venta' => $producto->precio_venta,
        ]);

        return response()->json([
            'message'  => 'Producto actualizado exitosamente.',
            'producto' => new ProductoResource($producto->load(['categoria', 'proveedor'])),
        ]);
    }

    /**
     * Elimina un producto. Solo Admin y Bodeguero (Vendedor -> 403).
     */
    public function destroy(Request $request, Producto $producto): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole(['admin', 'bodeguero'])) {
            return response()->json([
                'message' => 'Acceso denegado: permisos insuficientes para esta operación.',
            ], 403);
        }

        if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $id = $producto->id;
        $nombre = $producto->nombre;
        $producto->delete();

        AuditService::log('producto_eliminado', Producto::class, $id, [
            'nombre' => $nombre,
        ]);

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
