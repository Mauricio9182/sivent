<?php

namespace App\Support;

final class DatosDemo
{
    public static function productos(): array
    {
        return [
            ['codigo' => 'TAL-001', 'nombre' => 'Taladro percutor 1/2"', 'unidad' => 'UNIDAD', 'precio' => 485.00, 'existencia' => 3, 'minima' => 5, 'activo' => true],
            ['codigo' => 'CEM-050', 'nombre' => 'Cemento gris 50 kg', 'unidad' => 'CAJA', 'precio' => 78.50, 'existencia' => 18, 'minima' => 10, 'activo' => true],
            ['codigo' => 'PIN-001', 'nombre' => 'Pintura látex blanca', 'unidad' => 'GALON', 'precio' => 112.00, 'existencia' => 2.5, 'minima' => 4, 'activo' => true],
            ['codigo' => 'CAB-014', 'nombre' => 'Cable eléctrico THHN', 'unidad' => 'METRO', 'precio' => 8.75, 'existencia' => 86.25, 'minima' => 50, 'activo' => false],
            ['codigo' => 'TOR-100', 'nombre' => 'Tornillo para madera 2"', 'unidad' => 'UNIDAD', 'precio' => 0.85, 'existencia' => 240, 'minima' => 100, 'activo' => true],
        ];
    }

    public static function clientes(): array
    {
        return [
            ['nit' => '5487123-4', 'nombre' => 'Constructora Los Pinos', 'telefono' => '5555-0101', 'direccion' => 'Jutiapa'],
            ['nit' => '2789456-1', 'nombre' => 'Maria Lopez', 'telefono' => '5555-0102', 'direccion' => 'Asuncion Mita'],
            ['nit' => 'CF', 'nombre' => 'Consumidor Final', 'telefono' => '—', 'direccion' => '—'],
            ['nit' => '3001001-1', 'nombre' => 'Ferreteria San Jose', 'telefono' => '5555-0103', 'direccion' => 'Jutiapa'],
        ];
    }

    public static function movimientos(): array
    {
        return [
            ['fecha' => '29/10/2026 10:42', 'tipo' => 'Entrada', 'producto' => 'Cemento gris 50 kg', 'cantidad' => '+20', 'usuario' => 'Bodeguero de prueba', 'motivo' => 'Reposición semanal'],
            ['fecha' => '29/10/2026 09:18', 'tipo' => 'Salida por venta', 'producto' => 'Taladro percutor 1/2"', 'cantidad' => '-1', 'usuario' => 'Vendedor de prueba', 'motivo' => 'Venta #000124'],
            ['fecha' => '28/10/2026 16:05', 'tipo' => 'Entrada', 'producto' => 'Cable eléctrico THHN', 'cantidad' => '+100', 'usuario' => 'Bodeguero de prueba', 'motivo' => 'Compra a proveedor'],
        ];
    }

    public static function auditoria(): array
    {
        return [
            ['fecha' => '29/10/2026 10:42', 'usuario' => 'bodega1', 'operacion' => 'Registrar entrada', 'entidad' => 'Inventario', 'detalle' => 'Reposición semanal · Cemento gris 50 kg'],
            ['fecha' => '29/10/2026 09:18', 'usuario' => 'vendedor1', 'operacion' => 'Confirmar venta', 'entidad' => 'Venta #000124', 'detalle' => 'Total Q 485.00'],
            ['fecha' => '28/10/2026 15:30', 'usuario' => 'admin', 'operacion' => 'Actualizar precio', 'entidad' => 'Producto TAL-001', 'detalle' => 'Precio anterior Q 460.00 · Nuevo Q 485.00'],
            ['fecha' => '28/10/2026 14:12', 'usuario' => 'gerente1', 'operacion' => 'Acceso denegado', 'entidad' => 'Ventas', 'detalle' => 'Intento de confirmar venta'],
        ];
    }

    public static function roles(): array
    {
        return [
            'Administrador' => ['panel', 'productos', 'clientes', 'inventario', 'ventas', 'consultas', 'auditoria'],
            'Vendedor' => ['panel', 'clientes', 'ventas', 'consultas'],
            'Encargado de bodega' => ['panel', 'productos', 'inventario', 'consultas'],
            'Gerente' => ['panel', 'productos', 'clientes', 'inventario', 'ventas', 'consultas', 'auditoria'],
        ];
    }
}
