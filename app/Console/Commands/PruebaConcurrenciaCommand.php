<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * RN-04 / RNF-03: N vendedores, misma última unidad (PLOM-001, 1 unidad).
 * Lanza N procesos PHP reales que arrancan en el mismo instante.
 * Exactamente 1 debe confirmarse y la existencia final debe ser 0.
 * Si hacen falta más borradores que los 2 del script de Edgar, los crea.
 */
class PruebaConcurrenciaCommand extends Command
{
    protected $signature = 'sivent:concurrencia {--procesos=2}';
    protected $description = 'Prueba de concurrencia: N procesos confirman a la vez la última unidad';

    public function handle(): int
    {
        $n = max(2, (int) $this->option('procesos'));
        $ids = $this->prepararBorradores($n);
        $inicio = microtime(true) + 4 + $n * 0.3; // margen para que todos arranquen
        $procs = [];
        $t0 = microtime(true);

        foreach ($ids as $id) {
            $cmd = [PHP_BINARY, base_path('artisan'), 'sivent:confirmar', (string) $id, "--inicio={$inicio}"];
            $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $procs[$id] = [$p, $pipes];
        }

        $ok = 0;
        foreach ($procs as $id => [$p, $pipes]) {
            $out = trim(stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
            proc_close($p);
            $this->line("  Venta {$id}: " . mb_strimwidth($out, 0, 110, '...'));
            if (str_starts_with($out, 'OK')) {
                $ok++;
            }
        }

        $existencia = DB::table('productos')->where('codigo', 'PLOM-001')->value('existencia');
        $this->newLine();
        $this->line("Procesos simultáneos: {$n}");
        $this->line("Confirmadas: {$ok} (esperado: 1)");
        $this->line("Existencia final PLOM-001: {$existencia} (esperado: 0.00)");

        if ($ok === 1 && (float) $existencia === 0.0) {
            $this->info('PRUEBA APROBADA: no se vendió la misma unidad dos veces.');
            return 0;
        }
        $this->error('PRUEBA FALLIDA. (¿Reimportó el script antes de correrla?)');
        return 1;
    }

    /** Devuelve N ids de ventas BORRADOR con 1 unidad de PLOM-001. */
    private function prepararBorradores(int $n): array
    {
        $pid = DB::table('productos')->where('codigo', 'PLOM-001')->value('id');
        $precio = DB::table('productos')->where('id', $pid)->value('precio');

        $ids = DB::table('ventas as v')
            ->join('detalle_venta as d', 'd.venta_id', '=', 'v.id')
            ->where('v.estado', 'BORRADOR')->where('d.producto_id', $pid)->where('d.cantidad', 1)
            ->orderBy('v.id')->limit($n)->pluck('v.id')->all();

        while (count($ids) < $n) {
            $id = DB::table('ventas')->insertGetId([
                'cliente_id' => null,
                'usuario_creacion_id' => DB::table('usuarios')->where('usuario', 'vendedor1')->value('id'),
                'estado' => 'BORRADOR',
                'total' => '0.00',
                'token_confirmacion' => (string) \Illuminate\Support\Str::uuid(),
            ]);
            DB::table('detalle_venta')->insert([
                'venta_id' => $id, 'producto_id' => $pid, 'cantidad' => '1.00',
                'precio_unitario' => $precio, 'subtotal' => $precio,
            ]);
            $ids[] = $id;
        }
        return $ids;
    }
}
