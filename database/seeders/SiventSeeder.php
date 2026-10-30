<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba de SIVENT (script v3 de Edgar) + lo que pide Manuel:
 * usuario vendedor2 y borradores extra (ventas 4, 5 y 6).
 * Todos los usuarios usan la clave: contra123 (solo desarrollo local).
 */
class SiventSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->catalogosYUsuarios();
            $this->productosEInventario();
            $this->ventasBorrador();
        });
    }

    private function catalogosYUsuarios(): void
    {
        DB::table('roles')->insert([
            ['nombre' => 'Administrador'],
            ['nombre' => 'Vendedor'],
            ['nombre' => 'Encargado de bodega'],
            ['nombre' => 'Gerente'],
        ]);

        $hashVendedor = '$2y$12$1uordlWSBwLOtZ5gRmjcGO5w6rGqh2JKDh/qVaAg8.PYr8Z/H9JUW'; // contra123

        DB::table('usuarios')->insert([
            ['rol_id' => 1, 'nombre' => 'Administrador de prueba', 'usuario' => 'admin',
                'contrasena_hash' => '$2y$12$GGnUGO/VavizQgSQDtGoZeG4ti2L87lRqd4b0HBmw9/vgyM4DRrP2'],
            ['rol_id' => 2, 'nombre' => 'Vendedor de prueba', 'usuario' => 'vendedor1',
                'contrasena_hash' => $hashVendedor],
            ['rol_id' => 3, 'nombre' => 'Bodeguero de prueba', 'usuario' => 'bodega1',
                'contrasena_hash' => '$2y$12$uHjHidHVQc.RizXJIZ7IiupI751KkTa6f3MVmEHlMunIobSxB2c0C'],
            ['rol_id' => 4, 'nombre' => 'Gerente de prueba', 'usuario' => 'gerente1',
                'contrasena_hash' => '$2y$12$tzqduPSfrAeQQuwVf0XESOH5o0Z3LbGsRGjOlbCgFpXBAtCKKQRm6'],
            // Extra para la revisión de Manuel (id 5)
            ['rol_id' => 2, 'nombre' => 'Vendedor 2 de prueba', 'usuario' => 'vendedor2',
                'contrasena_hash' => $hashVendedor],
        ]);

        DB::table('categorias')->insert([
            ['nombre' => 'Herramientas'],
            ['nombre' => 'Materiales de construccion'],
            ['nombre' => 'Pintura'],
            ['nombre' => 'Electricidad'],
            ['nombre' => 'Plomeria'],
        ]);

        $clientes = [
            ['5487123-4', 'Constructora Los Pinos', '5555-0101', 'Jutiapa'],
            ['2789456-1', 'Maria Lopez', '5555-0102', 'Asuncion Mita'],
            ['3001001-1', 'Ferreteria San Jose', '5555-0103', 'Jutiapa'],
            ['3001002-2', 'Carlos Ramirez', '5555-0104', 'Asuncion Mita'],
            ['3001003-3', 'Distribuidora El Roble', '5555-0105', 'El Progreso, Jutiapa'],
            ['3001004-4', 'Ana Morales', '5555-0106', 'Santa Catarina Mita'],
            ['3001005-5', 'Constructora del Oriente', '5555-0107', 'Jutiapa'],
            ['3001006-6', 'Jose Garcia', '5555-0108', 'Asuncion Mita'],
            ['3001007-7', 'Servicios Integrales Lopez', '5555-0109', 'Jalapa'],
            ['3001008-8', 'Pedro Hernandez', '5555-0110', 'Jutiapa'],
            ['3001009-9', 'Mariana Castillo', '5555-0111', 'El Progreso, Jutiapa'],
            ['3001010-0', 'Comercial Santa Elena', '5555-0112', 'Asuncion Mita'],
            ['3001011-1', 'Sofia Perez', '5555-0113', 'Jalapa'],
            ['3001012-2', 'Constructora Las Flores', '5555-0114', 'Santa Catarina Mita'],
            ['3001013-3', 'Luis Martinez', '5555-0115', 'Jutiapa'],
        ];
        DB::table('clientes')->insert(array_map(fn ($c) => [
            'nit' => $c[0], 'nombre' => $c[1], 'telefono' => $c[2], 'direccion' => $c[3],
        ], $clientes));
    }

    private function productosEInventario(): void
    {
        // [categoria_id, codigo, nombre, unidad_venta, precio, existencia_minima, activo]
        // Los IDs salen en este orden (1..30); las pruebas dependen de ellos.
        $productos = [
            [1, 'HERR-001', 'Martillo de acero', 'UNIDAD', 65.00, 3.00, 1],
            [2, 'CONS-001', 'Clavos de 2 pulgadas', 'LIBRA', 12.50, 5.00, 1],
            [3, 'PINT-001', 'Pintura blanca', 'GALON', 175.00, 2.00, 1],
            [4, 'ELEC-001', 'Cable electrico', 'METRO', 9.75, 20.00, 1],
            [5, 'PLOM-001', 'Llave de paso', 'UNIDAD', 48.00, 2.00, 1],
            [2, 'CONS-002', 'Caja de tornillos', 'CAJA', 30.00, 3.00, 1],
            [1, 'HERR-002', 'Taladro descontinuado', 'UNIDAD', 350.00, 0.00, 0],
            [1, 'HERR-003', 'Destornillador Phillips', 'UNIDAD', 28.00, 5.00, 1],
            [1, 'HERR-004', 'Llave ajustable', 'UNIDAD', 70.00, 4.00, 1],
            [1, 'HERR-005', 'Alicate universal', 'UNIDAD', 55.00, 3.00, 1],
            [1, 'HERR-006', 'Sierra manual', 'UNIDAD', 85.00, 5.00, 1],
            [2, 'CONS-003', 'Cemento gris en bolsa', 'UNIDAD', 52.00, 15.00, 1],
            [2, 'CONS-004', 'Arena en bolsa', 'UNIDAD', 18.00, 10.00, 1],
            [2, 'CONS-005', 'Varilla corrugada', 'UNIDAD', 36.00, 8.00, 1],
            [2, 'CONS-006', 'Alambre de amarre', 'LIBRA', 14.00, 3.00, 1],
            [3, 'PINT-002', 'Pintura azul', 'GALON', 165.00, 3.00, 1],
            [3, 'PINT-003', 'Pintura roja', 'GALON', 168.00, 4.00, 1],
            [3, 'PINT-004', 'Esmalte negro', 'GALON', 195.00, 2.00, 1],
            [3, 'PINT-005', 'Sellador para pared', 'GALON', 110.00, 4.00, 1],
            [3, 'PINT-006', 'Brocha de 3 pulgadas', 'UNIDAD', 24.50, 5.00, 1],
            [4, 'ELEC-002', 'Interruptor sencillo', 'UNIDAD', 22.00, 10.00, 1],
            [4, 'ELEC-003', 'Tomacorriente doble', 'UNIDAD', 35.00, 6.00, 1],
            [4, 'ELEC-004', 'Cinta aislante', 'UNIDAD', 12.00, 5.00, 1],
            [4, 'ELEC-005', 'Tubo conduit', 'METRO', 7.80, 15.00, 1],
            [4, 'ELEC-006', 'Bombilla LED 9W', 'UNIDAD', 29.90, 6.00, 1],
            [5, 'PLOM-002', 'Tubo PVC de media pulgada', 'METRO', 15.75, 12.00, 1],
            [5, 'PLOM-003', 'Codo PVC de media pulgada', 'UNIDAD', 4.00, 15.00, 1],
            [5, 'PLOM-004', 'Pegamento para PVC', 'UNIDAD', 32.00, 4.00, 1],
            [5, 'PLOM-005', 'Valvula de esfera', 'UNIDAD', 68.00, 3.00, 1],
            [5, 'PLOM-006', 'Cinta de teflon', 'UNIDAD', 5.50, 10.00, 1],
        ];

        // Todos nacen con existencia 0; el stock llega por la entrada inicial.
        DB::table('productos')->insert(array_map(fn ($p) => [
            'categoria_id' => $p[0], 'codigo' => $p[1], 'nombre' => $p[2],
            'unidad_venta' => $p[3], 'precio' => $p[4], 'existencia' => 0.00,
            'existencia_minima' => $p[5], 'activo' => $p[6],
        ], $productos));

        DB::table('secuencias_documento')->insert([
            ['tipo' => 'VENTA', 'ultimo_numero' => 0],
            ['tipo' => 'ENTRADA', 'ultimo_numero' => 1],
        ]);

        // CU-05: entrada inicial (AJUSTE). Agotados e inactivos quedan en 0.
        DB::table('entradas_inventario')->insert([
            'numero' => 1, 'motivo' => 'AJUSTE', 'proveedor' => null,
            'documento' => 'APERTURA-SIVENT-2026',
            'token' => '4f10c2e0-3456-4a10-8025-000000000001', 'usuario_id' => 3,
        ]);

        // producto_id => cantidad de la entrada inicial
        $stock = [
            1 => 10.00, 2 => 25.50, 3 => 8.00, 4 => 100.25, 5 => 1.00, 6 => 12.00,
            8 => 18.00, 9 => 8.00, 11 => 4.00, 12 => 60.00, 13 => 35.00, 14 => 20.00,
            15 => 12.75, 16 => 9.00, 17 => 3.00, 19 => 14.50, 20 => 16.00, 21 => 40.00,
            22 => 21.00, 23 => 3.00, 24 => 75.50, 26 => 50.00, 27 => 60.00, 28 => 9.00,
            29 => 2.00, 30 => 35.00,
        ];

        foreach ($stock as $productoId => $cantidad) {
            DB::table('detalle_entrada')->insert([
                'entrada_inventario_id' => 1, 'producto_id' => $productoId, 'cantidad' => $cantidad,
            ]);
            DB::table('productos')->where('id', $productoId)->update(['existencia' => $cantidad]);
            DB::table('movimientos_inventario')->insert([
                'producto_id' => $productoId, 'usuario_id' => 3,
                'entrada_inventario_id' => 1, 'venta_id' => null,
                'tipo' => 'ENTRADA', 'cantidad' => $cantidad, 'existencia_resultante' => $cantidad,
            ]);
        }

        DB::table('auditoria')->insert([
            'usuario_id' => 3, 'operacion' => 'Registrar entrada',
            'entidad' => 'entradas_inventario', 'registro_id' => 1,
            'motivo' => 'Carga inicial de existencias de SIVENT',
        ]);
    }

        private function ventasBorrador(): void
    {
        $tok = fn (int $n) => sprintf('da3a8248-ade9-4b1f-81ba-%012d', $n);

        // Ventas 1-3 (las usan las pruebas): 1 normal; 2 y 3 compiten por la
        // última unidad de PLOM-001 (RN-04).
        // Ventas 4-7 (extra, creadas por vendedor2 = id 5):
        //   4: producto inactivo (HERR-002)            -> debe dar PRODUCTO_INACTIVO
        //   5: cantidad mayor a la existencia (HERR-001, 50 de 10) -> EXISTENCIA_INSUFICIENTE
        //   6: caso normal con 3 productos             -> se confirma sin problema
        //   7: 1.5 martillos (HERR-001, unidad entera) -> debe dar CANTIDAD_INVALIDA (CP-09)
        DB::table('ventas')->insert([
            ['cliente_id' => 1, 'usuario_creacion_id' => 2, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(1)],
            ['cliente_id' => null, 'usuario_creacion_id' => 2, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(2)],
            ['cliente_id' => null, 'usuario_creacion_id' => 2, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(3)],
            ['cliente_id' => 2, 'usuario_creacion_id' => 5, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(4)],
            ['cliente_id' => 3, 'usuario_creacion_id' => 5, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(5)],
            ['cliente_id' => 4, 'usuario_creacion_id' => 5, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(6)],
            ['cliente_id' => 5, 'usuario_creacion_id' => 5, 'estado' => 'BORRADOR', 'total' => 0.00, 'token_confirmacion' => $tok(7)],
        ]);

        // [venta_id, producto_id, cantidad, precio_unitario, subtotal]
        $detalle = [
            [1, 1, 2.00, 65.00, 130.00],
            [1, 2, 1.50, 12.50, 18.75],
            [2, 5, 1.00, 48.00, 48.00],
            [3, 5, 1.00, 48.00, 48.00],
            [4, 7, 1.00, 350.00, 350.00],
            [5, 1, 50.00, 65.00, 3250.00],
            [6, 8, 2.00, 28.00, 56.00],
            [6, 12, 3.00, 52.00, 156.00],
            [6, 21, 4.00, 22.00, 88.00],
            [7, 1, 1.50, 65.00, 97.50],
        ];
        DB::table('detalle_venta')->insert(array_map(fn ($d) => [
            'venta_id' => $d[0], 'producto_id' => $d[1], 'cantidad' => $d[2],
            'precio_unitario' => $d[3], 'subtotal' => $d[4],
        ], $detalle));
    }
}
