<?php

namespace App\Http\Controllers;

use App\Exceptions\VentaException;
use App\Services\CatalogoService;
use App\Services\ConsultaService;
use App\Services\InventarioService;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Panel, productos, clientes, inventario, consultas y auditoría.
 * Todo se lee y se guarda en MySQL a través de los servicios (RNF-06).
 */
class ModulosController extends Controller
{
    public function __construct(
        private CatalogoService $catalogo,
        private InventarioService $inventario,
        private ConsultaService $consultas,
    ) {}

    public function panel(Request $request): View
    {
        $rol = $this->rol($request);

        return view('panel.index', [
            'stats' => $this->consultas->panel($rol, $request->user()->id),
            'role' => $rol,
            'lowStock' => $this->consultas->existenciasBajas()->take(8),
        ]);
    }

    // ---------- Productos (CU-03) ----------

    public function productos(Request $request): View
    {
        $this->permitir($request, 'productos');

        return view('productos.index', [
            'productos' => $this->catalogo->productos(),
            'categorias' => $this->catalogo->categorias(),
            'puedeEditar' => $this->rol($request) === 'Administrador',
        ]);
    }

    public function guardarProducto(Request $request): RedirectResponse
    {
        abort_unless($this->rol($request) === 'Administrador', 403);
        $request->merge(['codigo' => strtoupper(trim((string) $request->input('codigo')))]);

        $v = Validator::make($request->all(), [
            'codigo' => ['required', 'string', 'max:40', 'unique:productos,codigo'],
            'nombre' => ['required', 'string', 'max:180'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'unidad_venta' => ['required', 'in:UNIDAD,CAJA,LIBRA,GALON,METRO'],
            'precio' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ], $this->mensajes());
        $this->minimaEntera($v, $request->input('unidad_venta'), $request->input('existencia_minima'));
        $data = $v->validate();

        $id = $this->catalogo->crearProducto($request->user(), $data);

        return redirect()->route('productos.index')
            ->with('ok', "Producto {$data['codigo']} guardado en la base de datos (id {$id}).");
    }

    public function actualizarProducto(Request $request, int $producto): RedirectResponse
    {
        abort_unless($this->rol($request) === 'Administrador', 403);
        $unidad = \DB::table('productos')->where('id', $producto)->value('unidad_venta');
        abort_if($unidad === null, 404);

        $v = Validator::make($request->all(), [
            'precio' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'activo' => ['nullable', 'boolean'],
        ], $this->mensajes());
        $this->minimaEntera($v, $unidad, $request->input('existencia_minima'));
        $data = $v->validate();

        $this->catalogo->actualizarProducto($request->user(), $producto, $data);

        return redirect()->route('productos.index')->with('ok', 'Producto actualizado.');
    }

    // ---------- Clientes (CU-04) ----------

    public function clientes(Request $request): View
    {
        $this->permitir($request, 'clientes');
        $query = trim($request->string('q')->toString());

        return view('clientes.index', ['clientes' => $this->catalogo->clientes($query), 'query' => $query]);
    }

    public function guardarCliente(Request $request): RedirectResponse
    {
        $this->permitir($request, 'clientes');
        $nit = strtoupper(trim((string) $request->input('nit')));
        $request->merge(['nit' => ($nit === '' || $nit === 'CF') ? null : $nit]);

        $data = $request->validate([
            'nit' => ['nullable', 'string', 'max:25', 'unique:clientes,nit'],
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], [
            'nit.unique' => 'Ya existe un cliente con ese NIT.',
            'nombre.required' => 'Escribe el nombre del cliente.',
        ]);

        $id = $this->catalogo->crearCliente($request->user(), $data);

        return redirect()->route('clientes.index')->with('ok', "Cliente {$data['nombre']} guardado en la base de datos (id {$id}).");
    }

    // ---------- Inventario (CU-05 y CU-06) ----------

    public function inventario(Request $request): View
    {
        $this->permitir($request, 'inventario');

        return view('inventario.index', [
            'productos' => $this->catalogo->productos(),
            'movimientos' => $this->inventario->movimientos(),
            'puedeRegistrar' => in_array($this->rol($request), InventarioService::ROLES, true),
            'token' => (string) Str::uuid(),
        ]);
    }

    public function registrarEntrada(Request $request): RedirectResponse
    {
        $this->permitir($request, 'inventario');
        $data = $request->validate([
            'motivo' => ['required', 'in:COMPRA,DEVOLUCION,AJUSTE'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:100'],
            'token' => ['required', 'string', 'max:100'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
        ], [
            'lineas.*.producto_id.required' => 'Selecciona el producto en cada fila.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'lineas.*.cantidad.required' => 'Escribe la cantidad en cada fila.',
        ]);

        try {
            $r = $this->inventario->registrarEntrada($request->user(), $data['motivo'], $data['proveedor'] ?? null,
                $data['documento'] ?? null, $data['token'], $data['lineas']);
        } catch (VentaException $e) {
            abort_if($e->status === 403, 403);

            return back()->withInput()->withErrors(['entrada' => $e->getMessage()]);
        }

        return redirect()->route('inventario.index')->with('ok', $r['repetido']
            ? "La entrada N.° {$r['numero']} ya estaba registrada; no se duplicó."
            : "Entrada N.° {$r['numero']} registrada: existencias actualizadas.");
    }

    // ---------- Consultas (CU-11) y auditoría (CU-12) ----------

    public function consultas(Request $request): View
    {
        $this->permitir($request, 'consultas');
        $desde = $request->input('desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->input('hasta') ?: now()->toDateString();
        if (!strtotime($desde) || !strtotime($hasta)) {
            [$desde, $hasta] = [now()->startOfMonth()->toDateString(), now()->toDateString()];
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return view('consultas.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $this->consultas->ventasPorPeriodo($desde, $hasta),
            'bajas' => $this->consultas->existenciasBajas(),
        ]);
    }

    public function auditoria(Request $request): View
    {
        $this->permitir($request, 'auditoria');
        $query = trim($request->string('q')->toString());

        return view('auditoria.index', ['registros' => $this->consultas->auditoria($query), 'query' => $query]);
    }

    // ---------- Apoyo ----------

    private function rol(Request $request): string
    {
        return (string) \DB::table('roles')->where('id', $request->user()->rol_id)->value('nombre');
    }

    private function permitir(Request $request, string $seccion): void
    {
        abort_unless(Permisos::puede($this->rol($request), $seccion), 403);
    }

    private function minimaEntera($validator, ?string $unidad, $minima): void
    {
        $validator->after(function ($v) use ($unidad, $minima) {
            $m = (float) ($minima ?? 0);
            if (in_array($unidad, ['UNIDAD', 'CAJA'], true) && floor($m) != $m) {
                $v->errors()->add('existencia_minima', 'Con unidad UNIDAD o CAJA la existencia mínima debe ser un número entero.');
            }
        });
    }

    private function mensajes(): array
    {
        return [
            'codigo.required' => 'Escribe el código del producto.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'nombre.required' => 'Escribe el nombre del producto.',
            'categoria_id.required' => 'Selecciona una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'unidad_venta.in' => 'La unidad de venta no es válida.',
            'precio.required' => 'Escribe el precio.',
            'precio.min' => 'El precio debe ser mayor que cero.',
            'existencia_minima.min' => 'La existencia mínima no puede ser negativa.',
        ];
    }
}
