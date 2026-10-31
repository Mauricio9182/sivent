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
        $ventas = $this->service->listar();
        $puedeCrear = $this->esVendedor(request());
        return view('ventas.index', compact('ventas', 'puedeCrear'));
    }
 
    /** CU-07 Crear venta: formulario del borrador (solo vendedor). */
    public function create(Request $r)
    {
        abort_unless($this->esVendedor($r), 403);

        return view('ventas.create', [
            'clientes' => DB::table('clientes')->orderBy('nombre')->get(['id', 'nit', 'nombre']),
            'productos' => DB::table('productos')->where('activo', 1)->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre', 'unidad_venta', 'precio', 'existencia']),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($this->esVendedor($r), 403);
        $data = $r->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
        ], [
            'lineas.required' => 'Agrega al menos un producto.',
            'lineas.*.producto_id.required' => 'Selecciona el producto en cada fila.',
            'lineas.*.cantidad.required' => 'Escribe la cantidad en cada fila.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
        ]);

        try {
            $id = $this->service->crearBorrador($r->user(), isset($data['cliente_id']) ? (int) $data['cliente_id'] : null, $data['lineas']);
        } catch (VentaException $e) {
            return back()->withInput()->withErrors(['venta' => $e->getMessage()]);
        }

        return redirect()->route('ventas.show', $id)
            ->with('ok', "Venta #{$id} guardada como borrador. Revisa el total y confírmala.");
    }

    /** CU-10 Anular venta (RN-06). */
    public function anular(Request $r, int $venta)
    {
        try {
            $this->service->anular($venta, $r->user(), $r->input('motivo_anulacion'));
        } catch (VentaException $e) {
            abort_if($e->status === 403, 403, $e->getMessage());
            abort_if($e->status === 404, 404);

            return back()->withInput()->withErrors(['motivo_anulacion' => $e->getMessage()]);
        }

        return redirect()->route('ventas.show', $venta)
            ->with('ok', 'Venta anulada. El inventario se regresó y quedó registrado en auditoría.');
    }

    private function esVendedor(Request $r): bool
    {
        return DB::table('roles')->where('id', $r->user()->rol_id)->value('nombre') === VentaService::ROL_VENDEDOR;
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
                return response()->json([
                    'ok' => false,
                    'codigo' => $e->codigo,
                    'mensaje' => $e->getMessage(),
                ], $e->status);
            }
            return back()->withErrors(['venta' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // E2: falla durante la persistencia. La transacción ya se revirtió completa.
            report($e);
            $msg = 'No se pudo confirmar la venta. Intente de nuevo.';
            if ($r->expectsJson()) {
                return response()->json(['ok' => false, 'codigo' => null, 'mensaje' => $msg], 500);
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