<?php

namespace App\Http\Controllers;

use App\Exceptions\VentaException;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function __construct(private VentaService $service) {}

    public function index()
    {
        $ventas = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->orderByDesc('v.id')
            ->select('v.id', 'v.numero', 'v.estado', 'v.total', 'c.nombre as cliente')
            ->get();
        return view('ventas.index', compact('ventas'));
    }

    public function show(int $venta)
    {
        $v = $this->service->detalle($venta);
        abort_if(!$v, 404);
        return view('ventas.show', ['v' => $v]);
    }

    /** POST /ventas/{venta}/confirmar  (HTML o JSON) */
    public function confirmar(Request $r, int $venta)
    {
        try {
            $res = $this->service->confirmar(
                $venta,
                $r->user(),
                $r->input('token_confirmacion'),
                $r->input('total_visible')
            );
        } catch (VentaException $e) {
            if ($r->expectsJson()) {
                return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], $e->status);
            }
            return back()->withErrors(['venta' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // E2: falla durante la persistencia. La transacción ya se revirtió completa.
            report($e);
            $msg = 'No se pudo confirmar la venta. Intente de nuevo.';
            if ($r->expectsJson()) {
                return response()->json(['ok' => false, 'mensaje' => $msg], 500);
            }
            return back()->withErrors(['venta' => $msg]);
        }

        if ($r->expectsJson()) {
            return response()->json(['ok' => true, 'comprobante' => $res]);
        }
        return redirect()->route('ventas.show', $venta)->with(
            'ok',
            $res['repetido'] ? 'La venta ya estaba confirmada; no se duplicó nada.' : 'Venta confirmada correctamente.'
        );
    }
}
