<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModulosController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ventas');

Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/panel', [ModulosController::class, 'panel'])->name('panel');

    Route::get('/productos', [ModulosController::class, 'productos'])->name('productos.index');
    Route::post('/productos', [ModulosController::class, 'guardarProducto'])->name('productos.store');
    Route::post('/productos/{producto}', [ModulosController::class, 'actualizarProducto'])
        ->whereNumber('producto')->name('productos.update');

    Route::get('/clientes', [ModulosController::class, 'clientes'])->name('clientes.index');
    Route::post('/clientes', [ModulosController::class, 'guardarCliente'])->name('clientes.store');

    Route::get('/inventario', [ModulosController::class, 'inventario'])->name('inventario.index');
    Route::post('/inventario/entradas', [ModulosController::class, 'registrarEntrada'])->name('inventario.entrada');

    Route::get('/consultas', [ModulosController::class, 'consultas'])->name('consultas.index');
    Route::get('/auditoria', [ModulosController::class, 'auditoria'])->name('auditoria.index');

    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/nueva', [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])->whereNumber('venta')->name('ventas.show');
    Route::post('/ventas/{venta}/confirmar', [VentaController::class, 'confirmar'])
        ->whereNumber('venta')->name('ventas.confirmar');
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->whereNumber('venta')->name('ventas.anular');
});
