<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AplicacionMovilController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas de la API
|--------------------------------------------------------------------------
*/

// Registro y Login con Rate Limiting estricto (5 intentos/min por email+IP)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Formulario de contacto público
Route::post('/contacto', [ContactController::class, 'send']);

// Endpoints de la Aplicación Móvil y Chatbot Asistente (Módulo D)
Route::prefix('aplicacion_movil')->group(function () {
    Route::get('/producto/codigo/{codigo}', [AplicacionMovilController::class, 'porCodigoBarras']);
    Route::get('/producto/nombre', [AplicacionMovilController::class, 'porNombre']);
    Route::post('/producto/imagen', [AplicacionMovilController::class, 'porImagen']);
    Route::post('/chatbot', [AplicacionMovilController::class, 'chatbot']);
});

/*
|--------------------------------------------------------------------------
| Rutas Protegidas con Laravel Sanctum (Módulos A, B, C, E)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Perfil y Logout
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Módulo A: Endpoint de stock bajo (Soporta alias /core-api/productos/stock_bajo/)
    Route::get('/stock_bajo', [ProductoController::class, 'stockBajo']);
    Route::get('/core-api/productos/stock_bajo', [ProductoController::class, 'stockBajo']);

    // Módulo A: Catálogo de productos (Lectura para Admin, Bodeguero y Vendedor)
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{producto}', [ProductoController::class, 'show']);

    // Módulo A y E: Mutación de productos (Solo Admin y Bodeguero; Vendedor recibe 403 Forbidden)
    Route::middleware('role:admin,bodeguero')->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);
    });

    // Módulo Catálogo: Categorías (Lectura para todos; Mutación solo Admin y Bodeguero)
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{categoria}', [CategoriaController::class, 'show']);
    Route::middleware('role:admin,bodeguero')->group(function () {
        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy']);
    });

    // Módulo Catálogo: Proveedores (Lectura para todos; Mutación solo Admin y Bodeguero)
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show']);
    Route::middleware('role:admin,bodeguero')->group(function () {
        Route::post('/proveedores', [ProveedorController::class, 'store']);
        Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update']);
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy']);
    });

    // Módulo B: Compras a proveedores / Facturas de Compra (Admin y Bodeguero; Vendedor recibe 403)
    Route::get('/compras', [CompraController::class, 'index']);
    Route::get('/compras/{compra}', [CompraController::class, 'show']);
    Route::get('/facturas-compra', [CompraController::class, 'index']);
    Route::post('/compras', [CompraController::class, 'store'])->middleware('role:admin,bodeguero');
    Route::post('/facturas-compra', [CompraController::class, 'store'])->middleware('role:admin,bodeguero');

    // Módulo C: Ventas y facturación (Admin y Vendedor; Bodeguero recibe 403)
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/hoy', [VentaController::class, 'ventasHoy']);
    Route::get('/ventas/{venta}', [VentaController::class, 'show']);
    Route::post('/ventas', [VentaController::class, 'store'])->middleware('role:vendedor');

    // Módulo C: Reportes estadísticos, exportación CSV y Word (.docx)
    Route::prefix('reportes')->middleware('role:admin,bodeguero')->group(function () {
        Route::get('/ventas-diarias', [ReporteController::class, 'ventasDiarias']);
        Route::get('/ventas-mensuales', [ReporteController::class, 'ventasMensuales']);
        Route::get('/inventario', [ReporteController::class, 'inventario']);
        Route::get('/compras', [ReporteController::class, 'compras']);
        Route::get('/exportar-ventas-csv', [ReporteController::class, 'exportarVentasCsv']);
        Route::get('/word', [ReporteController::class, 'descargarWord']);
    });

    // Módulo Admin: Gestión de Usuarios y Roles (Exclusivo Administrador; 403 para otros roles)
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
