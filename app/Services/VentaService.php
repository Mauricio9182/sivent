<?php
 
namespace App\Services;
 
use App\Exceptions\VentaException;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
 
/**
 * CU-08 Confirmar venta (pasos a-i del contrato de Edgar).
 *
 * Orden de bloqueos (igual en todas las transacciones, evita deadlocks):
 *   1) fila de ventas  2) productos por ID ascendente  3) secuencias_documento
 *
 * Los dinero/cantidades se manejan en centésimas (enteros) para no
 * depender de floats: 1.50 * 12.50 = 18.75 exacto.
 */
class VentaService
{
    public const ROL_VENDEDOR = 'Vendedor';
 
    /** Listado de ventas (RNF-06: el controlador no accede a la base de datos). */
    public function listar()
    {
        return DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->orderByDesc('v.id')
            ->select('v.id', 'v.numero', 'v.estado', 'v.total', 'c.nombre as cliente')
            ->get();
    }
 
    /** @return array comprobante (ver detalle()) + clave 'repetido' */
    public function confirmar(int $ventaId, ?Usuario $usuario, ?string $token, ?string $totalVisible = null): array
    {
        // a. Sesión, rol y token
        if (!$usuario || !$usuario->activo) {
          throw new VentaException('Sesión no válida. Inicie sesión de nuevo.', 401, 'SESION_INVALIDA');
        }
        $rol = DB::table('roles')->where('id', $usuario->rol_id)->value('nombre');
        if ($rol !== self::ROL_VENDEDOR) {
            // E1: el intento queda en auditoría (fuera de la transacción de venta).
            DB::table('auditoria')->insert([
                'usuario_id' => $usuario->id,
                'operacion' => 'Acceso denegado',
                'entidad' => 'ventas',
                'registro_id' => $ventaId,
                'motivo' => "Intento de confirmar venta con rol {$rol}",
            ]);
            throw new VentaException('Acceso denegado.', 403, 'SIN_PERMISO');
        }
        if ($token === null || trim($token) === '') {
            throw new VentaException('Falta el token de confirmación.', 422, 'TOKEN_INVALIDO');
        }
 
        // b..h. Una sola transacción; cualquier excepción = ROLLBACK completo.
        // El 2.º parámetro reintenta solo si MySQL reporta deadlock.
        return DB::transaction(function () use ($ventaId, $usuario, $token, $totalVisible) {
 
            // b. Bloquear la venta
            $venta = DB::table('ventas')->where('id', $ventaId)->lockForUpdate()->first();
            if (!$venta) {
                throw new VentaException('La venta no existe.', 404);
            }
            if (!hash_equals((string) $venta->token_confirmacion, $token)) {
                throw new VentaException('Token de confirmación inválido.', 422, 'TOKEN_INVALIDO');
            }
            if ($venta->estado === 'CONFIRMADA') {
                // Reintento / doble clic: devolver comprobante sin tocar nada.
                return $this->detalle($ventaId) + ['repetido' => true];
            }
            if ($venta->estado === 'ANULADA') {
                throw new VentaException('La venta está anulada y no se puede confirmar.', 409, 'VENTA_ANULADA');
            }
 
            $detalles = DB::table('detalle_venta')
                ->where('venta_id', $ventaId)->orderBy('producto_id')->get();
            if ($detalles->isEmpty()) {
                throw new VentaException('La venta no tiene productos.', 422);
            }
 
            // c. Bloquear productos en orden ascendente de ID y validar
            $productos = DB::table('productos')
                ->whereIn('id', $detalles->pluck('producto_id')->all())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
 
            // Cada error es [codigo, mensaje]
            $errores = [];
            $lineas = [];
            $totalCent = 0;
 
            foreach ($detalles as $d) {
                $p = $productos[$d->producto_id] ?? null;
                if (!$p) {
                    $errores[] = [null, "Producto #{$d->producto_id} no existe."];
                    continue;
                }
                $cant = $this->cent($d->cantidad);
                $exis = $this->cent($p->existencia);
 
                if (!$p->activo) {
                    $errores[] = ['PRODUCTO_INACTIVO', "{$p->codigo} {$p->nombre}: producto inactivo."];
                    continue;
                }
                if (in_array($p->unidad_venta, ['UNIDAD', 'CAJA'], true) && $cant % 100 !== 0) {
                    $errores[] = ['CANTIDAD_INVALIDA', "{$p->codigo}: la unidad {$p->unidad_venta} solo admite cantidades enteras."];
                    continue;
                }
                if ($cant <= 0) {
                    $errores[] = ['CANTIDAD_INVALIDA', "{$p->codigo}: cantidad inválida."];
                    continue;
                }
                if ($exis < $cant) {
                    $errores[] = ['EXISTENCIA_INSUFICIENTE', "{$p->codigo} {$p->nombre}: existencia insuficiente "
                        . "(pedido {$this->fmt($cant)}, disponible {$this->fmt($exis)})."];
                    continue;
                }
 
                // d. Precio SIEMPRE de la BD; el navegador no manda precios.
                $precio = $this->cent($p->precio);
                $sub = intdiv($cant * $precio + 50, 100); // ROUND(cant*precio, 2)
                $totalCent += $sub;
 
                $lineas[] = compact('d', 'p', 'cant', 'exis', 'precio', 'sub');
            }
            if ($errores) {
                // Mensaje con todos los errores; codigo = el del primer error.
                throw new VentaException(
                    implode(' | ', array_column($errores, 1)),
                    409,
                    $errores[0][0]
                );
            }
 
            if ($totalVisible !== null && $totalVisible !== ''
                && $this->cent($totalVisible) !== $totalCent) {
                throw new VentaException(
                    'El total cambió (ahora es Q ' . $this->fmt($totalCent) . '). Revise y vuelva a confirmar.',
                    409,
                    'TOTAL_CAMBIO'
                );
            }
 
            // e. Número correlativo (fila bloqueada)
            $sec = DB::table('secuencias_documento')->where('tipo', 'VENTA')->lockForUpdate()->first();
            if (!$sec) {
                throw new VentaException('Falta la secuencia de ventas en la base de datos.', 500);
            }
            $numero = $sec->ultimo_numero + 1;
            DB::table('secuencias_documento')->where('tipo', 'VENTA')->update(['ultimo_numero' => $numero]);
 
            // d (cont.) Detalle ANTES de confirmar: los triggers nuevos no dejan
            // modificar detalle_venta cuando la venta ya no está en BORRADOR.
            foreach ($lineas as $l) {
                DB::table('detalle_venta')->where('id', $l['d']->id)->update([
                    'precio_unitario' => $this->fmt($l['precio']),
                    'subtotal' => $this->fmt($l['sub']),
                ]);
            }
 
            // f. Actualizar la venta
            DB::table('ventas')->where('id', $ventaId)->update([
                'estado' => 'CONFIRMADA',
                'numero' => $numero,
                'usuario_confirmacion_id' => $usuario->id,
                'fecha_confirmacion' => DB::raw('CURRENT_TIMESTAMP(6)'),
                'total' => $this->fmt($totalCent),
                'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
            ]);
 
            // g. Descuento de existencia y movimientos
            foreach ($lineas as $l) {
                $nueva = $l['exis'] - $l['cant'];
                DB::table('productos')->where('id', $l['p']->id)->update([
                    'existencia' => $this->fmt($nueva),
                    'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
                ]);
 
                DB::table('movimientos_inventario')->insert([
                    'producto_id' => $l['p']->id,
                    'usuario_id' => $usuario->id,
                    'venta_id' => $ventaId,
                    'entrada_inventario_id' => null,
                    'tipo' => 'SALIDA_VENTA',
                    'cantidad' => $this->fmt($l['cant']),
                    'existencia_resultante' => $this->fmt($nueva),
                ]);
            }
 
            // h. Auditoría
            DB::table('auditoria')->insert([
                'usuario_id' => $usuario->id,
                'operacion' => 'Confirmar venta',
                'entidad' => 'ventas',
                'registro_id' => $ventaId,
                'motivo' => null,
                'valor_anterior' => json_encode(['estado' => 'BORRADOR', 'total' => $venta->total]),
                'valor_nuevo' => json_encode([
                    'estado' => 'CONFIRMADA',
                    'numero' => $numero,
                    'total' => $this->fmt($totalCent),
                    'productos' => count($lineas),
                ]),
            ]);
 
            return $this->detalle($ventaId) + ['repetido' => false];
        }, 3);
    }
 
    /**
     * Datos de una venta para pantalla/comprobante.
     * BORRADOR: precios actuales del catálogo (total estimado).
     * CONFIRMADA/ANULADA: valores guardados en detalle_venta.
     */
    public function detalle(int $ventaId): ?array
    {
        $v = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'v.usuario_confirmacion_id')
            ->where('v.id', $ventaId)
            ->select('v.*', 'c.nombre as cliente', 'c.nit as cliente_nit', 'u.nombre as confirmado_por')
            ->first();
        if (!$v) {
            return null;
        }
 
        $rows = DB::table('detalle_venta as d')
            ->join('productos as p', 'p.id', '=', 'd.producto_id')
            ->where('d.venta_id', $ventaId)->orderBy('d.producto_id')
            ->select('p.codigo', 'p.nombre', 'p.unidad_venta', 'p.precio as precio_actual', 'p.existencia',
                'd.cantidad', 'd.precio_unitario', 'd.subtotal')
            ->get();
 
        $borrador = $v->estado === 'BORRADOR';
        $total = 0;
        $lineas = [];
        foreach ($rows as $r) {
            $precio = $borrador ? $r->precio_actual : $r->precio_unitario;
            $sub = $borrador
                ? intdiv($this->cent($r->cantidad) * $this->cent($precio) + 50, 100)
                : $this->cent($r->subtotal);
            $total += $sub;
            $lineas[] = [
                'codigo' => $r->codigo, 'nombre' => $r->nombre, 'unidad' => $r->unidad_venta,
                'cantidad' => $r->cantidad, 'precio' => $this->fmt($this->cent($precio)),
                'subtotal' => $this->fmt($sub), 'existencia' => $r->existencia,
            ];
        }
 
        return [
            'id' => $v->id, 'numero' => $v->numero, 'estado' => $v->estado, 'fecha' => $v->fecha,
            'cliente' => $v->cliente ?? 'Consumidor Final', 'cliente_nit' => $v->cliente_nit ?? 'CF',
            'confirmado_por' => $v->confirmado_por, 'fecha_confirmacion' => $v->fecha_confirmacion,
            'token' => $v->token_confirmacion,
            'lineas' => $lineas,
            'total' => $this->fmt($borrador ? $total : $this->cent($v->total)),
        ];
    }
 
    /** "12.50" -> 1250 */
    private function cent(string|int|float|null $v): int
    {
        return (int) round(((float) $v) * 100);
    }
 
    /** 1250 -> "12.50" */
    private function fmt(int $c): string
    {
        return sprintf('%d.%02d', intdiv($c, 100), $c % 100);
    }
}