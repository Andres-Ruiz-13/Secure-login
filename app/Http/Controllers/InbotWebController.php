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
use App\Models\User;
use App\Models\Venta;
use App\Services\AuditService;
use App\Services\DebugLogService;
use App\Services\ImageHashService;
use App\Services\PricingService;
use App\Services\ReportWordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    public function dashboard(Request $request): View
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

        $ventasHoy = Venta::with(['usuario', 'detalles.producto'])
            ->whereDate('fecha', date('Y-m-d'))
            ->latest('fecha')
            ->get();

        $proveedores = Proveedor::where('is_active', true)->orderBy('nombre')->get();
        $categorias  = Category::where('is_active', true)->orderBy('name')->get();
        $users       = $request->user()->isAdmin() ? User::latest('id')->get() : collect();

        // Multimedia y noticias para retrocompatibilidad
        $media = Media::with('producto')->latest()->get();
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
            'ventasHoy',
            'proveedores',
            'categorias',
            'users',
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
     * Vista dedicada de Facturas de Compra (/facturas-compra-lista/)
     */
    public function facturasCompraLista(): View
    {
        $compras = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->latest('fecha')
            ->get();

        $proveedores = Proveedor::where('is_active', true)->orderBy('nombre')->get();
        $categorias  = Category::where('is_active', true)->orderBy('name')->get();
        $productos   = Producto::where('is_active', true)->orderBy('nombre')->get();

        return view('dashboard.facturas-compra', compact('compras', 'proveedores', 'categorias', 'productos'));
    }

    /**
     * Vista dedicada de Ventas (/ventas-lista/)
     */
    public function ventasLista(): View
    {
        $ventas = Venta::with(['usuario', 'detalles.producto'])
            ->latest('fecha')
            ->take(50)
            ->get();

        $ventasHoy = Venta::with(['usuario', 'detalles.producto'])
            ->whereDate('fecha', date('Y-m-d'))
            ->latest('fecha')
            ->get();

        $productos = Producto::where('is_active', true)
            ->where('stock_actual', '>', 0)
            ->orderBy('nombre')
            ->get();

        return view('dashboard.ventas', compact('ventas', 'ventasHoy', 'productos'));
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
            'descripcion'         => ['nullable', 'string', 'max:1000'],
            'codigo_barras'       => ['required', 'string', 'max:50', 'unique:productos,codigo_barras'],
            'categoria_id'        => ['required', 'exists:categories,id'],
            'proveedor_id'        => ['required', 'exists:proveedores,id'],
            'precio_compra'       => ['required', 'numeric', 'min:0.01'],
            'porcentaje_ganancia' => ['required', 'numeric', 'min:0', 'max:1000'],
            'stock_actual'        => ['required', 'integer', 'min:0'],
            'stock_minimo'        => ['required', 'integer', 'min:0'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'media_id'            => ['nullable', 'exists:media,id'],
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
        } elseif ($request->filled('media_id')) {
            $existingMedia = Media::find($request->media_id);
            if ($existingMedia) {
                $imagePath = $existingMedia->path;
                $absolutePath = Storage::disk('public')->path($imagePath);
                $imageHash = ImageHashService::generateHash($absolutePath);
            }
        }

        $producto = Producto::create([
            'nombre'              => $validated['nombre'],
            'descripcion'         => $validated['descripcion'] ?? null,
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

        // Si se subió un archivo nuevo, vincularlo a la biblioteca de medios atribuido al producto
        if ($request->hasFile('imagen') && isset($file)) {
            Media::create([
                'name'        => $producto->nombre,
                'path'        => $imagePath,
                'mime_type'   => $file->getMimeType(),
                'size'        => $file->getSize(),
                'producto_id' => $producto->id,
            ]);
        } elseif ($request->filled('media_id') && isset($existingMedia)) {
            $existingMedia->producto_id = $producto->id;
            $existingMedia->save();
        }

        DebugLogService::log('PRODUCTO_CREADO_CMS', [
            'producto_id'   => $producto->id,
            'codigo_barras' => $producto->codigo_barras,
            'precio_venta'  => $producto->precio_venta,
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
            'descripcion'         => ['nullable', 'string', 'max:1000'],
            'codigo_barras'       => ['required', 'string', 'max:50', 'unique:productos,codigo_barras,' . $producto->id],
            'categoria_id'        => ['required', 'exists:categories,id'],
            'proveedor_id'        => ['required', 'exists:proveedores,id'],
            'precio_compra'       => ['required', 'numeric', 'min:0.01'],
            'porcentaje_ganancia' => ['required', 'numeric', 'min:0', 'max:1000'],
            'stock_actual'        => ['required', 'integer', 'min:0'],
            'stock_minimo'        => ['required', 'integer', 'min:0'],
            'is_active'           => ['nullable', 'boolean'],
            'imagen'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'media_id'            => ['nullable', 'exists:media,id'],
            'eliminar_imagen'     => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('eliminar_imagen')) {
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen) && str_starts_with($producto->imagen, 'productos/')) {
                Storage::disk('public')->delete($producto->imagen);
            }
            Media::where('producto_id', $producto->id)->update(['producto_id' => null]);
            $producto->imagen = null;
            $producto->image_hash = null;
        } elseif ($request->hasFile('imagen')) {
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen) && str_starts_with($producto->imagen, 'productos/')) {
                Storage::disk('public')->delete($producto->imagen);
            }

            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(32) . '.' . $extension;
            $path = $file->storeAs('productos', $fileName, 'public');
            $producto->imagen = $path;

            $absolutePath = Storage::disk('public')->path($path);
            $producto->image_hash = ImageHashService::generateHash($absolutePath);

            // Registrar en biblioteca de media atribuido al producto
            Media::create([
                'name'        => $validated['nombre'],
                'path'        => $path,
                'mime_type'   => $file->getMimeType(),
                'size'        => $file->getSize(),
                'producto_id' => $producto->id,
            ]);
        } elseif ($request->filled('media_id')) {
            $selectedMedia = Media::find($request->media_id);
            if ($selectedMedia) {
                $producto->imagen = $selectedMedia->path;
                $absolutePath = Storage::disk('public')->path($selectedMedia->path);
                $producto->image_hash = ImageHashService::generateHash($absolutePath);

                // Atribuir ese medio al producto
                $selectedMedia->producto_id = $producto->id;
                $selectedMedia->save();
            }
        }

        $producto->nombre = $validated['nombre'];
        $producto->descripcion = $validated['descripcion'] ?? $producto->descripcion;
        $producto->codigo_barras = $validated['codigo_barras'];
        $producto->categoria_id = $validated['categoria_id'];
        $producto->proveedor_id = $validated['proveedor_id'];
        $producto->precio_compra = $validated['precio_compra'];
        $producto->porcentaje_ganancia = $validated['porcentaje_ganancia'];
        $producto->stock_actual = $validated['stock_actual'];
        $producto->stock_minimo = $validated['stock_minimo'];
        $producto->is_active = $request->has('is_active');
        $producto->save();

        DebugLogService::log('PRODUCTO_ACTUALIZADO_CMS', [
            'id'     => $producto->id,
            'nombre' => $producto->nombre,
            'imagen' => $producto->imagen,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$producto->nombre}' y sus detalles actualizados exitosamente.");
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
        $id = $producto->id;
        $producto->delete();

        DebugLogService::log('PRODUCTO_ELIMINADO_CMS', ['id' => $id, 'nombre' => $nombre]);

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$nombre}' eliminado del inventario.");
    }

    /**
     * Registrar compra a proveedor (Formulario multi-fila o individual con soporte producto nuevo y existente)
     */
    public function storeCompra(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): Solo Administradores y Bodegueros pueden ingresar compras.');
        }

        $validated = $request->validate([
            'numero_factura'      => ['required', 'string', 'max:100'],
            'proveedor_id'        => ['required', 'exists:proveedores,id'],
            'fecha'               => ['nullable', 'date'],
            'porcentaje_ganancia' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'observaciones'       => ['nullable', 'string', 'max:500'],
            // Soporte multi-item
            'items'                       => ['nullable', 'array'],
            'items.*.tipo'                => ['nullable', 'in:existente,nuevo'],
            'items.*.producto_id'         => ['nullable', 'exists:productos,id'],
            'items.*.nombre'              => ['nullable', 'string', 'max:150'],
            'items.*.codigo_barras'       => ['nullable', 'string', 'max:50'],
            'items.*.descripcion'         => ['nullable', 'string', 'max:500'],
            'items.*.categoria_id'        => ['nullable', 'exists:categories,id'],
            'items.*.cantidad'            => ['nullable', 'integer', 'min:1'],
            'items.*.precio_unitario'     => ['nullable', 'numeric', 'min:0.01'],
            // Retrocompatibilidad con formulario individual
            'producto_id'    => ['nullable', 'exists:productos,id'],
            'cantidad'       => ['nullable', 'integer', 'min:1'],
            'precio_unitario'=> ['nullable', 'numeric', 'min:0.01'],
        ]);

        $globalProfit = (float) ($validated['porcentaje_ganancia'] ?? config('inbot.default_profit_percentage', 30.0));

        // Normalizar items
        $items = $validated['items'] ?? [];
        if (empty($items) && ! empty($validated['producto_id'])) {
            $items[] = [
                'tipo'            => 'existente',
                'producto_id'     => $validated['producto_id'],
                'cantidad'        => $validated['cantidad'] ?? 1,
                'precio_unitario' => $validated['precio_unitario'] ?? 0,
            ];
        }

        if (empty($items)) {
            return back()->with('error', 'Debes incluir al menos un producto en la factura de compra.');
        }

        DB::transaction(function () use ($validated, $items, $user, $globalProfit) {
            $totalCompra = 0;

            $compra = Compra::create([
                'numero_factura' => $validated['numero_factura'],
                'proveedor_id'   => $validated['proveedor_id'],
                'user_id'        => $user->id,
                'total'          => 0,
                'fecha'          => $validated['fecha'] ?? now(),
                'observaciones'  => $validated['observaciones'] ?? null,
            ]);

            foreach ($items as $item) {
                $cantidad = (int) ($item['cantidad'] ?? 1);
                $precioUnitario = (float) ($item['precio_unitario'] ?? 0);
                $subtotal = round($cantidad * $precioUnitario, 2);

                if (! empty($item['nombre']) || (isset($item['tipo']) && $item['tipo'] === 'nuevo')) {
                    // Producto Nuevo
                    $ganancia = isset($item['porcentaje_ganancia']) ? (float) $item['porcentaje_ganancia'] : $globalProfit;
                    $codigoBarras = ! empty($item['codigo_barras']) ? $item['codigo_barras'] : ('GEN-' . strtoupper(Str::random(10)));
                    
                    while (Producto::where('codigo_barras', $codigoBarras)->exists()) {
                        $codigoBarras = 'GEN-' . strtoupper(Str::random(10));
                    }

                    $producto = Producto::create([
                        'codigo_barras'       => $codigoBarras,
                        'nombre'              => $item['nombre'],
                        'descripcion'         => $item['descripcion'] ?? null,
                        'categoria_id'        => $item['categoria_id'] ?? null,
                        'proveedor_id'        => $validated['proveedor_id'],
                        'precio_compra'       => $precioUnitario,
                        'porcentaje_ganancia' => $ganancia,
                        'stock_actual'        => $cantidad,
                        'stock_minimo'        => 5,
                        'is_active'           => true,
                    ]);
                } else {
                    // Producto Existente
                    $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();
                    $producto->increment('stock_actual', $cantidad);

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
        });

        DebugLogService::log('COMPRA_PROCESADA_CMS', ['numero_factura' => $validated['numero_factura']]);

        return redirect()->route('dashboard')
            ->with('success', "Factura de compra '{$validated['numero_factura']}' procesada exitosamente. Se actualizaron existencias y precios.");
    }

    /**
     * Facturar venta (Salida atómica de almacén; Vendedor y Admin autorizados, Bodeguero bloqueado)
     */
    public function storeVenta(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isVendedor()) {
            return back()->with('error', 'Acceso denegado (403): El rol Bodeguero no está autorizado para registrar ventas.');
        }

        $validated = $request->validate([
            'cliente_nombre'    => ['nullable', 'string', 'max:120'],
            'cliente_documento' => ['nullable', 'string', 'max:30'],
            'metodo_pago'       => ['required', 'in:efectivo,tarjeta,transferencia,otro'],
            'producto_id'       => ['nullable', 'exists:productos,id'],
            'cantidad'          => ['nullable', 'integer', 'min:1'],
            'items'             => ['nullable', 'array'],
            'items.*.producto_id' => ['required_with:items', 'exists:productos,id'],
            'items.*.cantidad'    => ['required_with:items', 'integer', 'min:1'],
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items) && ! empty($validated['producto_id'])) {
            $items[] = [
                'producto_id' => $validated['producto_id'],
                'cantidad'    => $validated['cantidad'] ?? 1,
            ];
        }

        if (empty($items)) {
            return back()->with('error', 'Debes seleccionar al menos un producto para la venta.');
        }

        try {
            DB::transaction(function () use ($validated, $items, $request) {
                $totalVenta = 0;
                $detalles = [];

                foreach ($items as $item) {
                    $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->firstOrFail();
                    $solicitado = (int) $item['cantidad'];

                    if ($producto->stock_actual < $solicitado) {
                        throw ValidationException::withMessages([
                            'cantidad' => "Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual} unidades.",
                        ]);
                    }

                    $precioUnitario = (float) $producto->precio_venta;
                    $subtotal = round($precioUnitario * $solicitado, 2);
                    $totalVenta += $subtotal;

                    $detalles[] = [
                        'producto'        => $producto,
                        'cantidad'        => $solicitado,
                        'precio_unitario' => $precioUnitario,
                        'subtotal'        => $subtotal,
                    ];
                }

                $numeroFactura = 'FAC-' . strtoupper(Str::random(8));

                $venta = Venta::create([
                    'numero_factura'    => $numeroFactura,
                    'user_id'           => $request->user()->id,
                    'metodo_pago'       => $validated['metodo_pago'],
                    'total'             => $totalVenta,
                    'fecha'             => now(),
                    'cliente_nombre'    => $validated['cliente_nombre'] ?? 'Consumidor Final',
                    'cliente_documento' => $validated['cliente_documento'] ?? 'N/A',
                ]);

                foreach ($detalles as $det) {
                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $det['producto']->id,
                        'cantidad'        => $det['cantidad'],
                        'precio_unitario' => $det['precio_unitario'],
                        'subtotal'        => $det['subtotal'],
                    ]);

                    $det['producto']->decrement('stock_actual', $det['cantidad']);
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        DebugLogService::log('VENTA_FACTURADA_CMS', ['cliente' => $validated['cliente_nombre'] ?? 'General']);

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
            'nit'       => ['required', 'string', 'max:50', 'unique:proveedores,nit'],
            'nombre'    => ['required', 'string', 'max:150'],
            'telefono'  => ['nullable', 'string', 'max:50'],
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

        DebugLogService::log('PROVEEDOR_CREADO_CMS', ['nit' => $prov->nit]);

        return redirect()->route('dashboard')
            ->with('success', "Proveedor '{$prov->nombre}' registrado correctamente.");
    }

    /**
     * Registrar categoría desde CMS
     */
    public function storeCategoria(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): Solo Administradores y Bodegueros pueden gestionar categorías.');
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::slug($validated['name']);
        $orig = $slug;
        $c = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$orig}-{$c}";
            $c++;
        }

        $cat = Category::create([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        DebugLogService::log('CATEGORIA_CREADA_CMS', ['name' => $cat->name]);

        return redirect()->route('dashboard')
            ->with('success', "Categoría '{$cat->name}' creada exitosamente.");
    }

    /**
     * Eliminar categoría desde CMS
     */
    public function destroyCategoria(Request $request, Category $categoria): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isBodeguero()) {
            return back()->with('error', 'Acceso denegado (403): Permisos insuficientes.');
        }

        $name = $categoria->name;
        $categoria->delete();

        DebugLogService::log('CATEGORIA_ELIMINADA_CMS', ['name' => $name]);

        return redirect()->route('dashboard')
            ->with('success', "Categoría '{$name}' eliminada correctamente.");
    }

    /**
     * Descarga de reporte Word (.docx) generado con python-docx
     */
    public function descargarReporteWord(Request $request): BinaryFileResponse
    {
        $tipo = $request->get('tipo', 'inventario');
        $params = $request->only(['fecha', 'desde', 'hasta', 'mes', 'anio']);

        $filePath = ReportWordService::generate($tipo, $params);
        $fileName = "reporte_{$tipo}_" . date('Ymd_His') . ".docx";

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}
