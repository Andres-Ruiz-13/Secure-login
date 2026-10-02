<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Media;
use App\Models\News;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use App\Services\AuditService;
use App\Services\ImageHashService;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InbotWebController extends Controller
{
    /**
     * Portal Público Principal de INBOT SYSTEM
     */
    public function home(): View
    {
        $productos = Producto::with(['categoria', 'proveedor'])
            ->where('is_active', true)
            ->latest('id')
            ->take(12)
            ->get();

        $categorias = Category::where('is_active', true)
            ->withCount(['productos' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $totalProductos   = Producto::where('is_active', true)->count();
        $totalStock       = (int) Producto::where('is_active', true)->sum('stock_actual');
        $stockBajoCount   = Producto::stockBajo()->where('is_active', true)->count();
        $totalProveedores = Proveedor::where('is_active', true)->count();
        $totalVentas      = Venta::count();

        // Notificaciones y comunicados institucionales
        $news = News::with('media')
            ->where('published', true)
            ->latest()
            ->take(3)
            ->get();

        return view('index', compact(
            'productos',
            'categorias',
            'totalProductos',
            'totalStock',
            'stockBajoCount',
            'totalProveedores',
            'totalVentas',
            'news'
        ));
    }

    /**
     * Panel de Control CMS de INBOT SYSTEM (Protegido por Auth)
     */
    public function dashboard(): View
    {
        $productos = Producto::with(['categoria', 'proveedor'])
            ->latest('id')
            ->get();

        $stockBajo = Producto::with(['categoria', 'proveedor'])
            ->stockBajo()
            ->latest('stock_actual')
            ->get();

        $compras = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->latest('fecha')
            ->take(20)
            ->get();

        $ventas = Venta::with(['usuario', 'detalles.producto'])
            ->latest('fecha')
            ->take(20)
            ->get();

        $proveedores = Proveedor::where('is_active', true)->orderBy('nombre')->get();
        $categorias  = Category::where('is_active', true)->orderBy('name')->get();

        // Multimedia y noticias para retrocompatibilidad
        $media = Media::latest()->get();
        $news  = News::with('media')->latest()->get();

        // Estadísticas financieras y de almacén
        $totalProductos       = Producto::count();
        $stockBajoCount       = $stockBajo->count();
        $totalFacturado       = (float) Venta::sum('total');
        $totalCompras         = (float) Compra::sum('total');
        $valorInventarioCosto = (float) Producto::sum(DB::raw('stock_actual * precio_compra'));
        $valorInventarioVenta = (float) Producto::sum(DB::raw('stock_actual * precio_venta'));

        return view('dashboard.dashboard', compact(
            'productos',
            'stockBajo',
            'compras',
            'ventas',
            'proveedores',
            'categorias',
            'media',
            'news',
            'totalProductos',
            'stockBajoCount',
            'totalFacturado',
            'totalCompras',
            'valorInventarioCosto',
            'valorInventarioVenta'
        ));
    }

    /**
     * Registrar producto desde el CMS (Admin y Bodeguero; Vendedor bloqueado)
     */
    public function storeProducto(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): El rol Vendedor no está autorizado para crear productos.');
        }

        $validated = $request->validate([
            'nombre'              => ['required', 'string', 'max:150'],
            'codigo_barras'       => ['required', 'string', 'max:50', 'unique:productos,codigo_barras'],
            'categoria_id'        => ['required', 'exists:categories,id'],
            'proveedor_id'        => ['required', 'exists:proveedores,id'],
            'precio_compra'       => ['required', 'numeric', 'min:0.01'],
            'porcentaje_ganancia' => ['required', 'numeric', 'min:0', 'max:1000'],
            'stock_actual'        => ['required', 'integer', 'min:0'],
            'stock_minimo'        => ['required', 'integer', 'min:0'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $imagePath = null;
        $imageHash = null;

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . $extension;
            $imagePath = $file->storeAs('productos', $fileName, 'public');

            $absolutePath = Storage::disk('public')->path($imagePath);
            $imageHash = ImageHashService::generateHash($absolutePath);
        }

        $producto = Producto::create([
            'nombre'              => $validated['nombre'],
            'codigo_barras'       => $validated['codigo_barras'],
            'categoria_id'        => $validated['categoria_id'],
            'proveedor_id'        => $validated['proveedor_id'],
            'precio_compra'       => $validated['precio_compra'],
            'porcentaje_ganancia' => $validated['porcentaje_ganancia'],
            'stock_actual'        => $validated['stock_actual'],
            'stock_minimo'        => $validated['stock_minimo'],
            'imagen'              => $imagePath,
            'image_hash'          => $imageHash,
            'is_active'           => true,
        ]);

        AuditService::log('PRODUCTO_CREADO_CMS', [
            'producto_id'    => $producto->id,
            'codigo_barras'  => $producto->codigo_barras,
            'precio_compra'  => $producto->precio_compra,
            'precio_venta'   => $producto->precio_venta,
            'stock_inicial'  => $producto->stock_actual,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$producto->nombre}' registrado exitosamente con precio de venta calculado automáticamente: $" . number_format((float) $producto->precio_venta, 2));
    }

    /**
     * Actualizar producto desde el CMS
     */
    public function updateProducto(Request $request, Producto $producto): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): El rol Vendedor no está autorizado para modificar productos.');
        }

        $validated = $request->validate([
            'nombre'              => ['required', 'string', 'max:150'],
            'codigo_barras'       => ['required', 'string', 'max:50', 'unique:productos,codigo_barras,' . $producto->id],
            'categoria_id'        => ['required', 'exists:categories,id'],
            'proveedor_id'        => ['required', 'exists:proveedores,id'],
            'precio_compra'       => ['required', 'numeric', 'min:0.01'],
            'porcentaje_ganancia' => ['required', 'numeric', 'min:0', 'max:1000'],
            'stock_actual'        => ['required', 'integer', 'min:0'],
            'stock_minimo'        => ['required', 'integer', 'min:0'],
            'is_active'           => ['nullable', 'boolean'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . $extension;
            $path = $file->storeAs('productos', $fileName, 'public');
            $producto->imagen = $path;

            $absolutePath = Storage::disk('public')->path($path);
            $producto->image_hash = ImageHashService::generateHash($absolutePath);
        }

        $producto->nombre = $validated['nombre'];
        $producto->codigo_barras = $validated['codigo_barras'];
        $producto->categoria_id = $validated['categoria_id'];
        $producto->proveedor_id = $validated['proveedor_id'];
        $producto->precio_compra = $validated['precio_compra'];
        $producto->porcentaje_ganancia = $validated['porcentaje_ganancia'];
        $producto->stock_actual = $validated['stock_actual'];
        $producto->stock_minimo = $validated['stock_minimo'];
        $producto->is_active = $request->has('is_active');
        $producto->save();

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$producto->nombre}' actualizado exitosamente.");
    }

    /**
     * Eliminar producto del inventario
     */
    public function destroyProducto(Request $request, Producto $producto): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return back()->with('error', 'Acceso denegado (403): Solo el Administrador puede eliminar productos permanentemente.');
        }

        if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $nombre = $producto->nombre;
        $producto->delete();

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$nombre}' eliminado del inventario.");
    }

    /**
     * Registrar compra a proveedor (Entrada atómica de almacén)
     */
    public function storeCompra(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): Solo Administradores y Bodegueros pueden ingresar compras.');
        }

        $validated = $request->validate([
            'numero_factura' => ['required', 'string', 'max:50'],
            'proveedor_id'   => ['required', 'exists:proveedores,id'],
            'observaciones'  => ['nullable', 'string', 'max:500'],
            'producto_id'    => ['required', 'exists:productos,id'],
            'cantidad'       => ['required', 'integer', 'min:1'],
            'precio_unitario'=> ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $user) {
            $subtotal = round((float) $validated['precio_unitario'] * (int) $validated['cantidad'], 2);

            $compra = Compra::create([
                'numero_factura' => $validated['numero_factura'],
                'proveedor_id'   => $validated['proveedor_id'],
                'user_id'        => $user->id,
                'total'          => $subtotal,
                'fecha'          => now(),
                'observaciones'  => $validated['observaciones'] ?? null,
            ]);

            DetalleCompra::create([
                'compra_id'       => $compra->id,
                'producto_id'     => $validated['producto_id'],
                'cantidad'        => $validated['cantidad'],
                'precio_unitario' => $validated['precio_unitario'],
                'subtotal'        => $subtotal,
            ]);

            // Incremento atómico del stock con bloqueo
            $producto = Producto::where('id', $validated['producto_id'])->lockForUpdate()->first();
            $producto->increment('stock_actual', (int) $validated['cantidad']);
        });

        return redirect()->route('dashboard')
            ->with('success', "Compra '{$validated['numero_factura']}' procesada. El stock del producto ha sido incrementado de forma atómica.");
    }

    /**
     * Facturar venta (Salida atómica de almacén con validación pesimista)
     */
    public function storeVenta(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_nombre'    => ['nullable', 'string', 'max:120'],
            'cliente_documento' => ['nullable', 'string', 'max:30'],
            'metodo_pago'       => ['required', 'in:efectivo,tarjeta,transferencia,otro'],
            'producto_id'       => ['required', 'exists:productos,id'],
            'cantidad'          => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $producto = Producto::where('id', $validated['producto_id'])->lockForUpdate()->first();
                $solicitado = (int) $validated['cantidad'];

                if ($producto->stock_actual < $solicitado) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual} unidades.",
                    ]);
                }

                $subtotal = round((float) $producto->precio_venta * $solicitado, 2);
                $numeroFactura = 'FAC-' . strtoupper(Str::random(8));

                $venta = Venta::create([
                    'numero_factura'    => $numeroFactura,
                    'user_id'           => $request->user()->id,
                    'metodo_pago'       => $validated['metodo_pago'],
                    'total'             => $subtotal,
                    'fecha'             => now(),
                    'cliente_nombre'    => $validated['cliente_nombre'] ?? 'Cliente General',
                    'cliente_documento' => $validated['cliente_documento'] ?? 'N/A',
                ]);

                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $producto->id,
                    'cantidad'        => $solicitado,
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal'        => $subtotal,
                ]);

                // Deducción atómica
                $producto->decrement('stock_actual', $solicitado);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('dashboard')
            ->with('success', 'Venta facturada exitosamente. El inventario ha sido descontado en tiempo real.');
    }

    /**
     * Registrar proveedor
     */
    public function storeProveedor(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): Solo Administradores y Bodegueros pueden registrar proveedores.');
        }

        $validated = $request->validate([
            'nit'       => ['required', 'string', 'max:30', 'unique:proveedores,nit'],
            'nombre'    => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:150'],
            'correo'    => ['nullable', 'email', 'max:100'],
        ]);

        $prov = Proveedor::create([
            'nit'       => $validated['nit'],
            'nombre'    => $validated['nombre'],
            'telefono'  => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'correo'    => $validated['correo'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Proveedor '{$prov->nombre}' registrado correctamente.");
    }
}
