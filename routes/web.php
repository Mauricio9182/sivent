<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ventas');

Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->whereNumber('venta')->name('ventas.show');
    Route::post('/ventas/{venta}/confirmar', [VentaController::class, 'confirmar'])
        ->whereNumber('venta')->name('ventas.confirmar');
});
