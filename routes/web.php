<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
 Route::get('/login', [LoginController::class, 'create'])
 ->name('login');

 Route::post('/login', [LoginController::class, 'store'])
 ->middleware('throttle:login')
 ->name('login.store');
});

Route::middleware('auth')->group(function () {
 Route::get('/dashboard', function () {
 return view('dashboard.dashboard');
 })->name('dashboard');

 Route::post('/logout', [LoginController::class, 'destroy'])
 ->name('logout');
});
