<?php

namespace App\Http\Controllers;

use App\Support\DatosDemo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrototipoController extends Controller
{
    public function panel(): View
    {
        $role = $this->role();

        // Cifras de inventario calculadas del mismo catálogo que ven Productos y Ventas.
        $productos = collect(DatosDemo::productos());
        $activos = $productos->where('activo', true)->count();
        $bajas = $productos
            ->filter(fn ($p) => $p['activo'] && $p['existencia'] < $p['minima'])
            ->values();

        $stats = match ($role) {
            'Vendedor' => [['label' => 'Ventas de hoy', 'value' => '12', 'icon' => '↗'], ['label' => 'Pendientes', 'value' => '3', 'icon' => '⏱'], ['label' => 'Total vendido', 'value' => 'Q 4,860', 'icon' => 'Q']],
            'Encargado de bodega' => [['label' => 'Productos activos', 'value' => (string) $activos, 'icon' => '▦'], ['label' => 'Existencias bajas', 'value' => (string) $bajas->count(), 'icon' => '!'], ['label' => 'Entradas del mes', 'value' => '36', 'icon' => '+']],
            'Gerente' => [['label' => 'Ventas del mes', 'value' => 'Q 86,420', 'icon' => '↗'], ['label' => 'Existencias bajas', 'value' => (string) $bajas->count(), 'icon' => '!'], ['label' => 'Ventas anuladas', 'value' => '2', 'icon' => '×']],
            default => [['label' => 'Ventas de hoy', 'value' => '18', 'icon' => '↗'], ['label' => 'Productos activos', 'value' => (string) $activos, 'icon' => '▦'], ['label' => 'Existencias bajas', 'value' => (string) $bajas->count(), 'icon' => '!']],
        };

        return view('panel.index', ['stats' => $stats, 'role' => $role, 'lowStock' => $bajas]);
    }

    public function productos(): View
    {
        $this->allow('productos');
        return view('productos.index', ['productos' => DatosDemo::productos()]);
    }

    public function clientes(Request $request): View
    {
        $this->allow('clientes');
        $query = trim($request->string('q')->toString());
        $clientes = collect(DatosDemo::clientes())->when($query !== '', fn ($items) => $items->filter(fn ($item) => str_contains(strtolower($item['nit'].' '.$item['nombre']), strtolower($query))))->values();
        return view('clientes.index', compact('clientes', 'query'));
    }

    public function inventario(): View
    {
        $this->allow('inventario');
        return view('inventario.index', ['productos' => DatosDemo::productos(), 'movimientos' => DatosDemo::movimientos()]);
    }

    public function consultas(): View
    {
        $this->allow('consultas');
        return view('consultas.index', ['productos' => DatosDemo::productos()]);
    }

    public function auditoria(Request $request): View
    {
        $this->allow('auditoria');
        $query = trim($request->string('q')->toString());
        $registros = collect(DatosDemo::auditoria())->when($query !== '', fn ($items) => $items->filter(fn ($item) => str_contains(strtolower(implode(' ', $item)), strtolower($query))))->values();
        return view('auditoria.index', compact('registros', 'query'));
    }

    public function anularDemo(Request $request, int $venta)
    {
        abort_unless(in_array($this->role(), ['Administrador', 'Gerente'], true), 403);

        $validated = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'motivo_anulacion.required' => 'Escribe el motivo de la anulación.',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $anulaciones = session('demo_anulaciones', []);
        $anulaciones[$venta] = [
            'motivo' => $validated['motivo_anulacion'],
            'usuario' => auth()->user()->usuario,
        ];
        session(['demo_anulaciones' => $anulaciones]);

        return back()->with('ok', "La anulación de la venta #{$venta} se guardó como demostración.");
    }

    private function role(): string
    {
        return (string) (auth()->user()->role_name ?? \DB::table('roles')->where('id', auth()->user()->rol_id)->value('nombre'));
    }

    private function allow(string $section): void
    {
        abort_unless(in_array($section, DatosDemo::roles()[$this->role()] ?? [], true), 403);
    }
}
