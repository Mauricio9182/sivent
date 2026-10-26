<?php

namespace Tests\Feature;

use App\Exceptions\VentaException;
use App\Models\Usuario;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Corre contra la base "sivent" recién importada (datos de Edgar).
 * DatabaseTransactions deshace todo al terminar cada prueba.
 */
class ConfirmarVentaTest extends TestCase
{
    use DatabaseTransactions;

    private function svc(): VentaService { return app(VentaService::class); }
    private function user(string $u = 'vendedor1'): Usuario { return Usuario::where('usuario', $u)->firstOrFail(); }
    private function token(int $v): string { return DB::table('ventas')->where('id', $v)->value('token_confirmacion'); }
    private function exis(string $codigo): string { return DB::table('productos')->where('codigo', $codigo)->value('existencia'); }

    public function test_confirma_venta_normal_con_todo_en_una_transaccion(): void
    {
        $r = $this->svc()->confirmar(1, $this->user(), $this->token(1), '148.75');

        $this->assertSame('CONFIRMADA', $r['estado']);
        $this->assertSame('148.75', $r['total']);                 // 2*65.00 + 1.5*12.50
        $this->assertSame('8.00', $this->exis('HERR-001'));       // 10 - 2
        $this->assertSame('24.00', $this->exis('CONS-001'));      // 25.50 - 1.50
        $this->assertSame(2, DB::table('movimientos_inventario')->where('venta_id', 1)->where('tipo', 'SALIDA_VENTA')->count());
        $this->assertSame(1, DB::table('auditoria')->where('operacion', 'Confirmar venta')->where('registro_id', 1)->count());
        $this->assertNotNull(DB::table('ventas')->where('id', 1)->value('numero'));
    }

    public function test_reintento_con_mismo_token_no_duplica(): void
    {
        $this->svc()->confirmar(1, $this->user(), $this->token(1));
        $r = $this->svc()->confirmar(1, $this->user(), $this->token(1));

        $this->assertTrue($r['repetido']);
        $this->assertSame('8.00', $this->exis('HERR-001'));       // descontó UNA sola vez
        $this->assertSame(2, DB::table('movimientos_inventario')->where('venta_id', 1)->count());
    }

    public function test_total_visible_distinto_hace_rollback(): void
    {
        try {
            $this->svc()->confirmar(1, $this->user(), $this->token(1), '1.00');
            $this->fail('Debió rechazar el total manipulado.');
        } catch (VentaException $e) {
            $this->assertSame(409, $e->status);
        }
        $this->assertSame('BORRADOR', DB::table('ventas')->where('id', 1)->value('estado'));
        $this->assertSame('10.00', $this->exis('HERR-001'));
    }

    public function test_rol_que_no_es_vendedor_es_rechazado_y_queda_en_auditoria(): void
    {
        try {
            $this->svc()->confirmar(1, $this->user('bodega1'), $this->token(1));
            $this->fail('Debió rechazar el rol.');
        } catch (VentaException $e) {
            $this->assertSame(403, $e->status);
        }
        $this->assertSame('BORRADOR', DB::table('ventas')->where('id', 1)->value('estado'));
        $this->assertSame(1, DB::table('auditoria')->where('operacion', 'Acceso denegado')->count());
    }

    public function test_falla_a_mitad_de_la_transaccion_revierte_todo(): void
    {
        // Simula una caída justo al escribir la auditoría (paso final de la transacción).
        DB::listen(function ($q) {
            if (str_contains($q->sql, 'insert into `auditoria`')) {
                throw new \RuntimeException('falla simulada');
            }
        });

        try {
            $this->svc()->confirmar(1, $this->user(), $this->token(1));
            $this->fail('Debió fallar.');
        } catch (\RuntimeException $e) {
            $this->assertSame('falla simulada', $e->getMessage());
        }

        $this->assertSame('BORRADOR', DB::table('ventas')->where('id', 1)->value('estado'));
        $this->assertNull(DB::table('ventas')->where('id', 1)->value('numero'));
        $this->assertSame('10.00', $this->exis('HERR-001'));
        $this->assertSame('25.50', $this->exis('CONS-001'));
        $this->assertSame(0, DB::table('movimientos_inventario')->where('venta_id', 1)->count());
        $this->assertSame(0, DB::table('secuencias_documento')->where('tipo', 'VENTA')->value('ultimo_numero'));
    }

    public function test_token_incorrecto_es_rechazado(): void
    {
        $this->expectException(VentaException::class);
        $this->svc()->confirmar(1, $this->user(), 'token-falso');
    }

    public function test_ultima_unidad_solo_se_vende_una_vez(): void
    {
        $this->svc()->confirmar(2, $this->user(), $this->token(2));     // se lleva la última unidad
        $this->assertSame('0.00', $this->exis('PLOM-001'));

        try {
            $this->svc()->confirmar(3, $this->user(), $this->token(3)); // ya no hay
            $this->fail('No debió confirmar sin existencia.');
        } catch (VentaException $e) {
            $this->assertStringContainsString('existencia insuficiente', $e->getMessage());
        }
        $this->assertSame('BORRADOR', DB::table('ventas')->where('id', 3)->value('estado'));
        $this->assertSame('0.00', $this->exis('PLOM-001'));             // nunca negativa
    }
}
