<?php

namespace App\Support;

/**
 * Datos de demostración del prototipo.
 * Son los MISMOS productos, precios, existencias y clientes que carga
 * database/seeders/SiventSeeder.php (script v3 de Edgar), para que todas
 * las pantallas muestren un solo catálogo.
 */
final class DatosDemo
{
    public static function productos(): array
    {
        return [
            ['codigo' => 'HERR-001', 'nombre' => 'Martillo de acero', 'unidad' => 'UNIDAD', 'precio' => 65.00, 'existencia' => 10, 'minima' => 3, 'activo' => true],
            ['codigo' => 'CONS-001', 'nombre' => 'Clavos de 2 pulgadas', 'unidad' => 'LIBRA', 'precio' => 12.50, 'existencia' => 25.5, 'minima' => 5, 'activo' => true],
            ['codigo' => 'PINT-001', 'nombre' => 'Pintura blanca', 'unidad' => 'GALON', 'precio' => 175.00, 'existencia' => 8, 'minima' => 2, 'activo' => true],
            ['codigo' => 'ELEC-001', 'nombre' => 'Cable electrico', 'unidad' => 'METRO', 'precio' => 9.75, 'existencia' => 100.25, 'minima' => 20, 'activo' => true],
            ['codigo' => 'PLOM-001', 'nombre' => 'Llave de paso', 'unidad' => 'UNIDAD', 'precio' => 48.00, 'existencia' => 1, 'minima' => 2, 'activo' => true],
            ['codigo' => 'CONS-002', 'nombre' => 'Caja de tornillos', 'unidad' => 'CAJA', 'precio' => 30.00, 'existencia' => 12, 'minima' => 3, 'activo' => true],
            ['codigo' => 'HERR-002', 'nombre' => 'Taladro descontinuado', 'unidad' => 'UNIDAD', 'precio' => 350.00, 'existencia' => 0, 'minima' => 0, 'activo' => false],
            ['codigo' => 'HERR-003', 'nombre' => 'Destornillador Phillips', 'unidad' => 'UNIDAD', 'precio' => 28.00, 'existencia' => 18, 'minima' => 5, 'activo' => true],
            ['codigo' => 'HERR-004', 'nombre' => 'Llave ajustable', 'unidad' => 'UNIDAD', 'precio' => 70.00, 'existencia' => 8, 'minima' => 4, 'activo' => true],
            ['codigo' => 'HERR-005', 'nombre' => 'Alicate universal', 'unidad' => 'UNIDAD', 'precio' => 55.00, 'existencia' => 0, 'minima' => 3, 'activo' => true],
            ['codigo' => 'HERR-006', 'nombre' => 'Sierra manual', 'unidad' => 'UNIDAD', 'precio' => 85.00, 'existencia' => 4, 'minima' => 5, 'activo' => true],
            ['codigo' => 'CONS-003', 'nombre' => 'Cemento gris en bolsa', 'unidad' => 'UNIDAD', 'precio' => 52.00, 'existencia' => 60, 'minima' => 15, 'activo' => true],
            ['codigo' => 'CONS-004', 'nombre' => 'Arena en bolsa', 'unidad' => 'UNIDAD', 'precio' => 18.00, 'existencia' => 35, 'minima' => 10, 'activo' => true],
            ['codigo' => 'CONS-005', 'nombre' => 'Varilla corrugada', 'unidad' => 'UNIDAD', 'precio' => 36.00, 'existencia' => 20, 'minima' => 8, 'activo' => true],
            ['codigo' => 'CONS-006', 'nombre' => 'Alambre de amarre', 'unidad' => 'LIBRA', 'precio' => 14.00, 'existencia' => 12.75, 'minima' => 3, 'activo' => true],
            ['codigo' => 'PINT-002', 'nombre' => 'Pintura azul', 'unidad' => 'GALON', 'precio' => 165.00, 'existencia' => 9, 'minima' => 3, 'activo' => true],
            ['codigo' => 'PINT-003', 'nombre' => 'Pintura roja', 'unidad' => 'GALON', 'precio' => 168.00, 'existencia' => 3, 'minima' => 4, 'activo' => true],
            ['codigo' => 'PINT-004', 'nombre' => 'Esmalte negro', 'unidad' => 'GALON', 'precio' => 195.00, 'existencia' => 0, 'minima' => 2, 'activo' => true],
            ['codigo' => 'PINT-005', 'nombre' => 'Sellador para pared', 'unidad' => 'GALON', 'precio' => 110.00, 'existencia' => 14.5, 'minima' => 4, 'activo' => true],
            ['codigo' => 'PINT-006', 'nombre' => 'Brocha de 3 pulgadas', 'unidad' => 'UNIDAD', 'precio' => 24.50, 'existencia' => 16, 'minima' => 5, 'activo' => true],
            ['codigo' => 'ELEC-002', 'nombre' => 'Interruptor sencillo', 'unidad' => 'UNIDAD', 'precio' => 22.00, 'existencia' => 40, 'minima' => 10, 'activo' => true],
            ['codigo' => 'ELEC-003', 'nombre' => 'Tomacorriente doble', 'unidad' => 'UNIDAD', 'precio' => 35.00, 'existencia' => 21, 'minima' => 6, 'activo' => true],
            ['codigo' => 'ELEC-004', 'nombre' => 'Cinta aislante', 'unidad' => 'UNIDAD', 'precio' => 12.00, 'existencia' => 3, 'minima' => 5, 'activo' => true],
            ['codigo' => 'ELEC-005', 'nombre' => 'Tubo conduit', 'unidad' => 'METRO', 'precio' => 7.80, 'existencia' => 75.5, 'minima' => 15, 'activo' => true],
            ['codigo' => 'ELEC-006', 'nombre' => 'Bombilla LED 9W', 'unidad' => 'UNIDAD', 'precio' => 29.90, 'existencia' => 0, 'minima' => 6, 'activo' => true],
            ['codigo' => 'PLOM-002', 'nombre' => 'Tubo PVC de media pulgada', 'unidad' => 'METRO', 'precio' => 15.75, 'existencia' => 50, 'minima' => 12, 'activo' => true],
            ['codigo' => 'PLOM-003', 'nombre' => 'Codo PVC de media pulgada', 'unidad' => 'UNIDAD', 'precio' => 4.00, 'existencia' => 60, 'minima' => 15, 'activo' => true],
            ['codigo' => 'PLOM-004', 'nombre' => 'Pegamento para PVC', 'unidad' => 'UNIDAD', 'precio' => 32.00, 'existencia' => 9, 'minima' => 4, 'activo' => true],
            ['codigo' => 'PLOM-005', 'nombre' => 'Valvula de esfera', 'unidad' => 'UNIDAD', 'precio' => 68.00, 'existencia' => 2, 'minima' => 3, 'activo' => true],
            ['codigo' => 'PLOM-006', 'nombre' => 'Cinta de teflon', 'unidad' => 'UNIDAD', 'precio' => 5.50, 'existencia' => 35, 'minima' => 10, 'activo' => true],
        ];
    }

    public static function clientes(): array
    {
        return [
            ['nit' => '5487123-4', 'nombre' => 'Constructora Los Pinos', 'telefono' => '5555-0101', 'direccion' => 'Jutiapa'],
            ['nit' => '2789456-1', 'nombre' => 'Maria Lopez', 'telefono' => '5555-0102', 'direccion' => 'Asuncion Mita'],
            ['nit' => 'CF', 'nombre' => 'Consumidor Final', 'telefono' => '—', 'direccion' => '—'],
            ['nit' => '3001001-1', 'nombre' => 'Ferreteria San Jose', 'telefono' => '5555-0103', 'direccion' => 'Jutiapa'],
            ['nit' => '3001002-2', 'nombre' => 'Carlos Ramirez', 'telefono' => '5555-0104', 'direccion' => 'Asuncion Mita'],
            ['nit' => '3001003-3', 'nombre' => 'Distribuidora El Roble', 'telefono' => '5555-0105', 'direccion' => 'El Progreso, Jutiapa'],
            ['nit' => '3001004-4', 'nombre' => 'Ana Morales', 'telefono' => '5555-0106', 'direccion' => 'Santa Catarina Mita'],
        ];
    }

    public static function movimientos(): array
    {
        return [
            ['fecha' => '29/10/2026 10:42', 'tipo' => 'Entrada', 'producto' => 'Cemento gris en bolsa', 'cantidad' => '+20', 'usuario' => 'Bodeguero de prueba', 'motivo' => 'Reposición semanal'],
            ['fecha' => '29/10/2026 09:18', 'tipo' => 'Salida por venta', 'producto' => 'Martillo de acero', 'cantidad' => '-2', 'usuario' => 'Vendedor de prueba', 'motivo' => 'Venta #000124'],
            ['fecha' => '28/10/2026 16:05', 'tipo' => 'Entrada', 'producto' => 'Cable electrico', 'cantidad' => '+100', 'usuario' => 'Bodeguero de prueba', 'motivo' => 'Compra a proveedor'],
        ];
    }

    public static function auditoria(): array
    {
        return [
            ['fecha' => '29/10/2026 10:42', 'usuario' => 'bodega1', 'operacion' => 'Registrar entrada', 'entidad' => 'Inventario', 'detalle' => 'Reposición semanal · Cemento gris en bolsa'],
            ['fecha' => '29/10/2026 09:18', 'usuario' => 'vendedor1', 'operacion' => 'Confirmar venta', 'entidad' => 'Venta #000124', 'detalle' => 'Total Q 130.00'],
            ['fecha' => '28/10/2026 15:30', 'usuario' => 'admin', 'operacion' => 'Actualizar precio', 'entidad' => 'Producto HERR-001', 'detalle' => 'Precio anterior Q 60.00 · Nuevo Q 65.00'],
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
