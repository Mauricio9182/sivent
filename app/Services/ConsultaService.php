<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * CU-11 Consultas (ventas por período, existencias bajas), CU-12 auditoría
 * y las cifras del panel principal. Solo lectura.
 */
class ConsultaService
{
    /** Productos activos con existencia por debajo del mínimo. */
    public function existenciasBajas()
    {
        return DB::table('productos')
            ->where('activo', 1)
            ->whereColumn('existencia', '<', 'existencia_minima')
            ->orderBy('existencia')->orderBy('codigo')
            ->get();
    }

    /** Ventas confirmadas en el período (las anuladas no cuentan). */
    public function ventasPorPeriodo(string $desde, string $hasta): array
    {
        $base = DB::table('ventas as v')
            ->where('v.estado', 'CONFIRMADA')
            ->whereBetween('v.fecha_confirmacion', [$desde.' 00:00:00', $hasta.' 23:59:59.999999']);

        $cantidad = (clone $base)->count();
        $total = (float) (clone $base)->sum('v.total');

        $ventas = (clone $base)
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'v.usuario_confirmacion_id')
            ->orderByDesc('v.fecha_confirmacion')
            ->get(['v.id', 'v.numero', 'v.fecha_confirmacion', 'v.total', 'c.nombre as cliente', 'u.usuario']);

        $anuladas = DB::table('ventas')->where('estado', 'ANULADA')
            ->whereBetween('fecha_anulacion', [$desde.' 00:00:00', $hasta.' 23:59:59.999999'])->count();

        return [
            'cantidad' => $cantidad,
            'total' => $total,
            'promedio' => $cantidad ? $total / $cantidad : 0,
            'anuladas' => $anuladas,
            'ventas' => $ventas,
        ];
    }

    public function auditoria(string $buscar = '', int $limite = 200)
    {
        return DB::table('auditoria as a')
            ->leftJoin('usuarios as u', 'u.id', '=', 'a.usuario_id')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('u.usuario', 'like', "%{$buscar}%")
                ->orWhere('a.operacion', 'like', "%{$buscar}%")
                ->orWhere('a.entidad', 'like', "%{$buscar}%")
                ->orWhere('a.motivo', 'like', "%{$buscar}%")))
            ->orderByDesc('a.id')->limit($limite)
            ->get(['a.*', 'u.usuario']);
    }

    /** Tarjetas del panel según el rol, todas calculadas en la base de datos. */
    public function panel(string $rol, int $usuarioId): array
    {
        $hoy = now()->toDateString();
        $mes = now()->startOfMonth()->toDateString();

        $confirmadas = fn (string $desde, ?int $usuario = null) => DB::table('ventas')
            ->where('estado', 'CONFIRMADA')
            ->where('fecha_confirmacion', '>=', $desde.' 00:00:00')
            ->when($usuario, fn ($q) => $q->where('usuario_confirmacion_id', $usuario));

        $q = fn ($n) => 'Q '.number_format($n, 2);
        $bajas = $this->existenciasBajas()->count();
        $activos = DB::table('productos')->where('activo', 1)->count();

        return match ($rol) {
            'Vendedor' => [
                ['label' => 'Mis ventas de hoy', 'value' => (string) $confirmadas($hoy, $usuarioId)->count(), 'icon' => '↗'],
                ['label' => 'Ventas pendientes', 'value' => (string) DB::table('ventas')->where('estado', 'BORRADOR')->count(), 'icon' => '⏱'],
                ['label' => 'Total vendido hoy', 'value' => $q((float) $confirmadas($hoy, $usuarioId)->sum('total')), 'icon' => 'Q'],
            ],
            'Encargado de bodega' => [
                ['label' => 'Productos activos', 'value' => (string) $activos, 'icon' => '▦'],
                ['label' => 'Existencias bajas', 'value' => (string) $bajas, 'icon' => '!'],
                ['label' => 'Entradas del mes', 'value' => (string) DB::table('entradas_inventario')->where('fecha', '>=', $mes.' 00:00:00')->count(), 'icon' => '+'],
            ],
            'Gerente' => [
                ['label' => 'Ventas del mes', 'value' => $q((float) $confirmadas($mes)->sum('total')), 'icon' => '↗'],
                ['label' => 'Existencias bajas', 'value' => (string) $bajas, 'icon' => '!'],
                ['label' => 'Ventas anuladas del mes', 'value' => (string) DB::table('ventas')->where('estado', 'ANULADA')->where('fecha_anulacion', '>=', $mes.' 00:00:00')->count(), 'icon' => '×'],
            ],
            default => [
                ['label' => 'Ventas de hoy', 'value' => (string) $confirmadas($hoy)->count(), 'icon' => '↗'],
                ['label' => 'Productos activos', 'value' => (string) $activos, 'icon' => '▦'],
                ['label' => 'Existencias bajas', 'value' => (string) $bajas, 'icon' => '!'],
            ],
        };
    }
}
