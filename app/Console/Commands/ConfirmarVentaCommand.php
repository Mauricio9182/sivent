<?php

namespace App\Console\Commands;

use App\Exceptions\VentaException;
use App\Models\Usuario;
use App\Services\VentaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfirmarVentaCommand extends Command
{
    protected $signature = 'sivent:confirmar {venta} {--usuario=vendedor1} {--inicio=}';
    protected $description = 'Confirma una venta desde consola (usado por la prueba de concurrencia)';

    public function handle(VentaService $service): int
    {
        $venta = (int) $this->argument('venta');
        $u = Usuario::where('usuario', $this->option('usuario'))->firstOrFail();
        $token = DB::table('ventas')->where('id', $venta)->value('token_confirmacion');

        // Espera hasta el instante de salida para que ambos procesos choquen.
        if ($inicio = $this->option('inicio')) {
            $resta = (float) $inicio - microtime(true);
            if ($resta > 0) {
                usleep((int) ($resta * 1_000_000));
            }
        }

        try {
            $r = $service->confirmar($venta, $u, $token);
            $this->line("OK venta={$venta} numero={$r['numero']}");
            return 0;
        } catch (VentaException $e) {
            $this->line("RECHAZADA venta={$venta}: " . $e->getMessage());
            return 1;
        }
    }
}
