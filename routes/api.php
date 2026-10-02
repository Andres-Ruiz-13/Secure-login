<?php

use App\Http\Controllers\Api\AplicacionMovilController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
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

    // Módulo A: Endpoint de stock bajo
    Route::get('/stock_bajo', [ProductoController::class, 'stockBajo']);

    // Módulo A: Catálogo de productos (Lectura para Admin, Bodeguero y Vendedor)
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{producto}', [ProductoController::class, 'show']);

    // Módulo A y E: Mutación de productos (Solo Admin y Bodeguero; Vendedor recibe 403 Forbidden)
    Route::middleware('role:admin,bodeguero')->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('/productos/{producto}', [ProductoController::class, 'destroy']);
    });

    // Módulo B: Compras a proveedores (Admin y Bodeguero)
    Route::get('/compras', [CompraController::class, 'index']);
    Route::get('/compras/{compra}', [CompraController::class, 'show']);
    Route::post('/compras', [CompraController::class, 'store'])->middleware('role:admin,bodeguero');

    // Módulo C: Ventas y facturación (Admin, Bodeguero y Vendedor)
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{venta}', [VentaController::class, 'show']);
    Route::post('/ventas', [VentaController::class, 'store'])->middleware('role:admin,vendedor,bodeguero');

    // Módulo C: Reportes estadísticos y exportación CSV
    Route::prefix('reportes')->middleware('role:admin,bodeguero')->group(function () {
        Route::get('/ventas-diarias', [ReporteController::class, 'ventasDiarias']);
        Route::get('/ventas-mensuales', [ReporteController::class, 'ventasMensuales']);
        Route::get('/inventario', [ReporteController::class, 'inventario']);
        Route::get('/exportar-ventas-csv', [ReporteController::class, 'exportarVentasCsv']);
    });
});
