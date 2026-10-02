<?php

use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsController;
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

    // Operaciones del CMS de Inventarios
    Route::post('/cms/productos', [InbotWebController::class, 'storeProducto'])->name('cms.productos.store');
    Route::put('/cms/productos/{producto}', [InbotWebController::class, 'updateProducto'])->name('cms.productos.update');
    Route::delete('/cms/productos/{producto}', [InbotWebController::class, 'destroyProducto'])->name('cms.productos.destroy');

    Route::post('/cms/compras', [InbotWebController::class, 'storeCompra'])->name('cms.compras.store');
    Route::post('/cms/ventas', [InbotWebController::class, 'storeVenta'])->name('cms.ventas.store');
    Route::post('/cms/proveedores', [InbotWebController::class, 'storeProveedor'])->name('cms.proveedores.store');

    // Mantenimiento de Multimedia y Noticias
    Route::post('/admin/media', [MediaController::class, 'store'])->name('admin.media.store');
    Route::put('/admin/media/{media}', [MediaController::class, 'update'])->name('admin.media.update');
    Route::delete('/admin/media/{media}', [MediaController::class, 'destroy'])->name('admin.media.destroy');

    Route::post('/admin/news', [NewsController::class, 'store'])->name('admin.news.store');
    Route::put('/admin/news/{news}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/admin/news/{news}', [NewsController::class, 'destroy'])->name('admin.news.destroy');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});