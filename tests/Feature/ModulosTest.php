<?php

namespace Tests\Feature;

use App\Exceptions\VentaException;
use App\Models\Usuario;
use App\Services\InventarioService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Módulos que antes eran solo prototipo y ahora guardan en MySQL:
 * productos, clientes, entradas de inventario, crear y anular venta.
 */
class ModulosTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $u): Usuario { return Usuario::where('usuario', $u)->firstOrFail(); }
    private function exis(string $codigo): string { return DB::table('productos')->where('codigo', $codigo)->value('existencia'); }
    private function pid(string $codigo): int { return (int) DB::table('productos')->where('codigo', $codigo)->value('id'); }

    public function test_todas_las_pantallas_abren_para_cada_rol(): void
    {
        $rutas = [
            'admin' => ['/panel', '/productos', '/clientes', '/inventario', '/consultas', '/auditoria', '/ventas', '/ventas/1'],
            'vendedor1' => ['/panel', '/clientes', '/consultas', '/ventas', '/ventas/nueva', '/ventas/1'],
            'bodega1' => ['/panel', '/productos', '/inventario', '/consultas'],
            'gerente1' => ['/panel', '/productos', '/clientes', '/inventario', '/consultas', '/auditoria', '/ventas'],
        ];
        foreach ($rutas as $u => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->user($u))->get($url)->assertOk();
            }
        }
        $this->actingAs($this->user('vendedor1'))->get('/auditoria')->assertForbidden();
        $this->actingAs($this->user('bodega1'))->get('/ventas/nueva')->assertForbidden();
    }

    public function test_admin_crea_producto_y_queda_en_auditoria(): void
    {
        $this->actingAs($this->user('admin'))->post('/productos', [
            'codigo' => 'herr-099', 'nombre' => 'Nivel de burbuja', 'categoria_id' => 1,
            'unidad_venta' => 'UNIDAD', 'precio' => '45.50', 'existencia_minima' => '2',
        ])->assertRedirect('/productos');

        $p = DB::table('productos')->where('codigo', 'HERR-099')->first();
        $this->assertNotNull($p);
        $this->assertSame('45.50', $p->precio);
        $this->assertSame('0.00', $p->existencia);
        $this->assertSame(1, DB::table('auditoria')->where('operacion', 'Crear producto')->where('registro_id', $p->id)->count());

        $this->get('/productos')->assertSee('Nivel de burbuja');
    }

    public function test_producto_con_codigo_repetido_o_precio_cero_se_rechaza(): void
    {
        $antes = DB::table('productos')->count();
        $this->actingAs($this->user('admin'))->post('/productos', [
            'codigo' => 'HERR-001', 'nombre' => 'X', 'categoria_id' => 1, 'unidad_venta' => 'UNIDAD', 'precio' => '0',
        ])->assertSessionHasErrors(['codigo', 'precio']);
        $this->assertSame($antes, DB::table('productos')->count());
    }

    public function test_solo_admin_crea_productos(): void
    {
        $this->actingAs($this->user('bodega1'))->post('/productos', [
            'codigo' => 'X-1', 'nombre' => 'X', 'categoria_id' => 1, 'unidad_venta' => 'UNIDAD', 'precio' => '1',
        ])->assertForbidden();
    }

    public function test_actualizar_precio_guarda_valor_anterior(): void
    {
        $id = $this->pid('HERR-001');
        $this->actingAs($this->user('admin'))->post("/productos/{$id}", [
            'precio' => '70.00', 'existencia_minima' => '3', 'activo' => '1',
        ])->assertRedirect('/productos');

        $this->assertSame('70.00', DB::table('productos')->where('id', $id)->value('precio'));
        $a = DB::table('auditoria')->where('operacion', 'Actualizar precio')->where('registro_id', $id)->first();
        $this->assertSame('65.00', json_decode($a->valor_anterior, true)['precio']);
    }

    public function test_crear_cliente(): void
    {
        $this->actingAs($this->user('vendedor1'))->post('/clientes', [
            'nit' => '7777777-7', 'nombre' => 'Cliente Nuevo', 'telefono' => '5555-9999', 'direccion' => 'Jutiapa',
        ])->assertRedirect('/clientes');

        $this->assertTrue(DB::table('clientes')->where('nit', '7777777-7')->exists());
        $this->assertSame(1, DB::table('auditoria')->where('operacion', 'Crear cliente')->count());
    }

    public function test_entrada_de_inventario_suma_existencia_y_no_se_duplica(): void
    {
        $tok = 'entrada-prueba-1';
        $lineas = [['producto_id' => $this->pid('PLOM-001'), 'cantidad' => '5'], ['producto_id' => $this->pid('CONS-001'), 'cantidad' => '2.5']];
        $svc = app(InventarioService::class);

        $r1 = $svc->registrarEntrada($this->user('bodega1'), 'COMPRA', 'Proveedor X', 'F-1', $tok, $lineas);
        $r2 = $svc->registrarEntrada($this->user('bodega1'), 'COMPRA', 'Proveedor X', 'F-1', $tok, $lineas);

        $this->assertFalse($r1['repetido']);
        $this->assertTrue($r2['repetido']);
        $this->assertSame('6.00', $this->exis('PLOM-001'));   // 1 + 5, una sola vez
        $this->assertSame('28.00', $this->exis('CONS-001'));  // 25.50 + 2.50
        $this->assertSame(2, DB::table('movimientos_inventario')->where('entrada_inventario_id', $r1['id'])->where('tipo', 'ENTRADA')->count());
        $this->assertSame(2, (int) DB::table('secuencias_documento')->where('tipo', 'ENTRADA')->value('ultimo_numero'));
    }

    public function test_entrada_con_cantidad_fraccionaria_en_unidad_revierte_todo(): void
    {
        try {
            app(InventarioService::class)->registrarEntrada($this->user('bodega1'), 'COMPRA', null, null, 'tok-x', [
                ['producto_id' => $this->pid('CONS-001'), 'cantidad' => '3'],
                ['producto_id' => $this->pid('HERR-001'), 'cantidad' => '1.5'],
            ]);
            $this->fail('Debió rechazar 1.5 martillos.');
        } catch (VentaException $e) {
            $this->assertSame(422, $e->status);
        }
        $this->assertSame('25.50', $this->exis('CONS-001'));
        $this->assertFalse(DB::table('entradas_inventario')->where('token', 'tok-x')->exists());
    }

    public function test_vendedor_no_registra_entradas(): void
    {
        $this->expectException(VentaException::class);
        app(InventarioService::class)->registrarEntrada($this->user('vendedor1'), 'COMPRA', null, null, 't', [['producto_id' => 1, 'cantidad' => 1]]);
    }

    public function test_crear_venta_y_confirmarla(): void
    {
        $this->actingAs($this->user('vendedor1'))->post('/ventas', [
            'cliente_id' => 1,
            'lineas' => [['producto_id' => $this->pid('HERR-003'), 'cantidad' => '2'], ['producto_id' => $this->pid('CONS-001'), 'cantidad' => '1.5']],
        ])->assertRedirect();

        $v = DB::table('ventas')->orderByDesc('id')->first();
        $this->assertSame('BORRADOR', $v->estado);
        $this->assertSame('74.75', $v->total);               // 2*28.00 + 1.5*12.50
        $this->assertSame('18.00', $this->exis('HERR-003'));    // no descuenta todavía

        $r = app(VentaService::class)->confirmar($v->id, $this->user('vendedor1'), $v->token_confirmacion, '74.75');
        $this->assertSame('CONFIRMADA', $r['estado']);
        $this->assertSame('16.00', $this->exis('HERR-003'));
    }

    public function test_crear_venta_rechaza_inactivo_y_fracciones(): void
    {
        $antes = DB::table('ventas')->count();
        $this->actingAs($this->user('vendedor1'))->post('/ventas', [
            'lineas' => [['producto_id' => $this->pid('HERR-002'), 'cantidad' => '1'], ['producto_id' => $this->pid('HERR-001'), 'cantidad' => '1.5']],
        ])->assertSessionHasErrors('venta');
        $this->assertSame($antes, DB::table('ventas')->count());
    }

    public function test_anular_regresa_inventario_una_sola_vez(): void
    {
        $svc = app(VentaService::class);
        $tok = DB::table('ventas')->where('id', 1)->value('token_confirmacion');
        $svc->confirmar(1, $this->user('vendedor1'), $tok);
        $this->assertSame('8.00', $this->exis('HERR-001'));

        $this->actingAs($this->user('gerente1'))->post('/ventas/1/anular', ['motivo_anulacion' => 'Cliente devolvió todo'])
            ->assertRedirect('/ventas/1');

        $this->assertSame('ANULADA', DB::table('ventas')->where('id', 1)->value('estado'));
        $this->assertSame('10.00', $this->exis('HERR-001'));
        $this->assertSame('25.50', $this->exis('CONS-001'));
        $this->assertSame(2, DB::table('movimientos_inventario')->where('venta_id', 1)->where('tipo', 'ENTRADA_ANULACION')->count());

        // Segunda anulación: rechazada y sin tocar inventario (RN-06).
        try {
            $svc->anular(1, $this->user('gerente1'), 'Intento repetido de anular');
            $this->fail('Debió rechazar la segunda anulación.');
        } catch (VentaException $e) {
            $this->assertSame(409, $e->status);
        }
        $this->assertSame('10.00', $this->exis('HERR-001'));
    }

    public function test_vendedor_no_puede_anular_y_motivo_corto_se_rechaza(): void
    {
        $svc = app(VentaService::class);
        $svc->confirmar(1, $this->user('vendedor1'), DB::table('ventas')->where('id', 1)->value('token_confirmacion'));

        $this->actingAs($this->user('vendedor1'))->post('/ventas/1/anular', ['motivo_anulacion' => 'Motivo suficientemente largo'])->assertForbidden();
        $this->actingAs($this->user('gerente1'))->post('/ventas/1/anular', ['motivo_anulacion' => 'corto'])->assertSessionHasErrors('motivo_anulacion');
        $this->assertSame('CONFIRMADA', DB::table('ventas')->where('id', 1)->value('estado'));
    }

    public function test_consultas_cuentan_solo_confirmadas(): void
    {
        app(VentaService::class)->confirmar(1, $this->user('vendedor1'), DB::table('ventas')->where('id', 1)->value('token_confirmacion'));
        $hoy = now()->toDateString();
        $this->actingAs($this->user('gerente1'))->get("/consultas?desde={$hoy}&hasta={$hoy}")
            ->assertOk()->assertSee('Q 148.75');
    }
}
