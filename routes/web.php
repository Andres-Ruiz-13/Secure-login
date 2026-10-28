<?php

use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InbotWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Web Público — INBOT SYSTEM
|--------------------------------------------------------------------------
*/
Route::get('/', [InbotWebController::class, 'home'])->name('home');

// Formulario de contacto web
Route::post('/contacto', [ContactController::class, 'send'])->name('contact.send');

/*
|--------------------------------------------------------------------------
| Autenticación Web (Sesiones protegidas con Rate Limiting)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [LoginController::class, 'createRegister'])->name('register');
    Route::post('/register', [LoginController::class, 'register'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Panel de Control CMS — INBOT SYSTEM (Autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [InbotWebController::class, 'dashboard'])->name('dashboard');

    // Flujos Principales 9.1 y 9.2 (Listados dedicados de Compras y Ventas)
    Route::get('/facturas-compra-lista', [InbotWebController::class, 'facturasCompraLista'])->name('facturas.compra.lista');
    Route::get('/ventas-lista', [InbotWebController::class, 'ventasLista'])->name('ventas.lista');

    // Operaciones del CMS de Inventarios
    Route::post('/cms/productos', [InbotWebController::class, 'storeProducto'])->name('cms.productos.store');
    Route::put('/cms/productos/{producto}', [InbotWebController::class, 'updateProducto'])->name('cms.productos.update');
    Route::delete('/cms/productos/{producto}', [InbotWebController::class, 'destroyProducto'])->name('cms.productos.destroy');

    Route::post('/cms/compras', [InbotWebController::class, 'storeCompra'])->name('cms.compras.store');
    Route::post('/cms/ventas', [InbotWebController::class, 'storeVenta'])->name('cms.ventas.store');
    Route::post('/cms/proveedores', [InbotWebController::class, 'storeProveedor'])->name('cms.proveedores.store');
    Route::post('/cms/categorias', [InbotWebController::class, 'storeCategoria'])->name('cms.categorias.store');
    Route::delete('/cms/categorias/{categoria}', [InbotWebController::class, 'destroyCategoria'])->name('cms.categorias.destroy');

    // Descarga de Reportes en Word (.docx)
    Route::get('/reportes/word', [InbotWebController::class, 'descargarReporteWord'])->name('reportes.word');

    // Gestión de Usuarios y Roles (Exclusivo Administrador)
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Mantenimiento de Multimedia y Noticias
    Route::post('/admin/media', [MediaController::class, 'store'])->name('admin.media.store');
    Route::post('/admin/media/{media}/atribuir', [MediaController::class, 'atribuir'])->name('admin.media.atribuir');
    Route::put('/admin/media/{media}', [MediaController::class, 'update'])->name('admin.media.update');
    Route::delete('/admin/media/{media}', [MediaController::class, 'destroy'])->name('admin.media.destroy');

    Route::post('/admin/news', [NewsController::class, 'store'])->name('admin.news.store');
    Route::put('/admin/news/{news}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/admin/news/{news}', [NewsController::class, 'destroy'])->name('admin.news.destroy');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
