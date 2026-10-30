<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PrototipoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ventas');

Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/panel', [PrototipoController::class, 'panel'])->name('panel');
    Route::get('/productos', [PrototipoController::class, 'productos'])->name('productos.index');
    Route::get('/clientes', [PrototipoController::class, 'clientes'])->name('clientes.index');
    Route::get('/inventario', [PrototipoController::class, 'inventario'])->name('inventario.index');
    Route::get('/consultas', [PrototipoController::class, 'consultas'])->name('consultas.index');
    Route::get('/auditoria', [PrototipoController::class, 'auditoria'])->name('auditoria.index');

    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->whereNumber('venta')->name('ventas.show');
    Route::post('/ventas/{venta}/confirmar', [VentaController::class, 'confirmar'])
        ->whereNumber('venta')->name('ventas.confirmar');
    Route::post('/ventas/{venta}/anular-demo', [PrototipoController::class, 'anularDemo'])
        ->whereNumber('venta')->name('ventas.anular-demo');
});
