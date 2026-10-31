<?php

namespace App\Services;

use App\Exceptions\VentaException;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * CU-05 Registrar entrada de inventario y CU-06 historial de movimientos.
 *
 * La entrada, su detalle, el aumento de existencias, los movimientos y la
 * auditoría se guardan en UNA transacción: si algo falla, no queda nada.
 * El token del formulario evita registrar dos veces la misma entrada.
 */
class InventarioService
{
    public const ROLES = ['Administrador', 'Encargado de bodega'];

    /**
     * @param array<int, array{producto_id:int|string, cantidad:string|float}> $lineas
     * @return array{id:int, numero:int, repetido:bool}
     */
    public function registrarEntrada(?Usuario $usuario, string $motivo, ?string $proveedor, ?string $documento, string $token, array $lineas): array
    {
        if (!$usuario || !$usuario->activo) {
            throw new VentaException('Sesión no válida. Inicie sesión de nuevo.', 401, 'SESION_INVALIDA');
        }
        $rol = DB::table('roles')->where('id', $usuario->rol_id)->value('nombre');
        if (!in_array($rol, self::ROLES, true)) {
            throw new VentaException('Acceso denegado.', 403, 'SIN_PERMISO');
        }
        if (!in_array($motivo, ['COMPRA', 'DEVOLUCION', 'AJUSTE'], true)) {
            throw new VentaException('El motivo de la entrada no es válido.', 422);
        }
        if (trim($token) === '') {
            throw new VentaException('Falta el token de la entrada.', 422, 'TOKEN_INVALIDO');
        }

        $cantidades = [];
        foreach ($lineas as $l) {
            $id = (int) ($l['producto_id'] ?? 0);
            if ($id > 0) {
                $cantidades[$id] = ($cantidades[$id] ?? 0) + $this->cent($l['cantidad'] ?? 0);
            }
        }
        if (!$cantidades) {
            throw new VentaException('Agrega al menos un producto a la entrada.', 422, 'CANTIDAD_INVALIDA');
        }
        ksort($cantidades);

        return DB::transaction(function () use ($usuario, $motivo, $proveedor, $documento, $token, $cantidades) {
            // Reintento o doble clic con el mismo token: no se duplica.
            $previa = DB::table('entradas_inventario')->where('token', $token)->first();
            if ($previa) {
                return ['id' => $previa->id, 'numero' => $previa->numero, 'repetido' => true];
            }

            // Orden de bloqueo: productos por ID ascendente -> secuencia.
            $productos = DB::table('productos')->whereIn('id', array_keys($cantidades))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $errores = [];
            foreach ($cantidades as $id => $cant) {
                $p = $productos[$id] ?? null;
                if (!$p) {
                    $errores[] = "Producto #{$id} no existe.";
                } elseif ($cant <= 0) {
                    $errores[] = "{$p->codigo}: la cantidad debe ser mayor que cero.";
                } elseif (in_array($p->unidad_venta, ['UNIDAD', 'CAJA'], true) && $cant % 100 !== 0) {
                    $errores[] = "{$p->codigo}: la unidad {$p->unidad_venta} solo admite cantidades enteras.";
                }
            }
            if ($errores) {
                throw new VentaException(implode(' | ', $errores), 422, 'CANTIDAD_INVALIDA');
            }

            $sec = DB::table('secuencias_documento')->where('tipo', 'ENTRADA')->lockForUpdate()->first();
            $numero = ($sec->ultimo_numero ?? 0) + 1;
            DB::table('secuencias_documento')->where('tipo', 'ENTRADA')->update(['ultimo_numero' => $numero]);

            $entradaId = DB::table('entradas_inventario')->insertGetId([
                'numero' => $numero,
                'motivo' => $motivo,
                'proveedor' => $proveedor !== null && trim($proveedor) !== '' ? trim($proveedor) : null,
                'documento' => $documento !== null && trim($documento) !== '' ? trim($documento) : null,
                'token' => $token,
                'usuario_id' => $usuario->id,
            ]);

            foreach ($cantidades as $id => $cant) {
                $p = $productos[$id];
                $nueva = $this->cent($p->existencia) + $cant;

                DB::table('detalle_entrada')->insert([
                    'entrada_inventario_id' => $entradaId,
                    'producto_id' => $id,
                    'cantidad' => $this->fmt($cant),
                ]);
                DB::table('productos')->where('id', $id)->update([
                    'existencia' => $this->fmt($nueva),
                    'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
                ]);
                DB::table('movimientos_inventario')->insert([
                    'producto_id' => $id,
                    'usuario_id' => $usuario->id,
                    'venta_id' => null,
                    'entrada_inventario_id' => $entradaId,
                    'tipo' => 'ENTRADA',
                    'cantidad' => $this->fmt($cant),
                    'existencia_resultante' => $this->fmt($nueva),
                ]);
            }

            DB::table('auditoria')->insert([
                'usuario_id' => $usuario->id,
                'operacion' => 'Registrar entrada',
                'entidad' => 'entradas_inventario',
                'registro_id' => $entradaId,
                'motivo' => $motivo,
                'valor_nuevo' => json_encode(['numero' => $numero, 'productos' => count($cantidades)]),
            ]);

            return ['id' => $entradaId, 'numero' => $numero, 'repetido' => false];
        }, 3);
    }

    /** Historial de movimientos (CU-06), del más reciente al más antiguo. */
    public function movimientos(int $limite = 100)
    {
        return DB::table('movimientos_inventario as m')
            ->join('productos as p', 'p.id', '=', 'm.producto_id')
            ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
            ->leftJoin('ventas as v', 'v.id', '=', 'm.venta_id')
            ->leftJoin('entradas_inventario as e', 'e.id', '=', 'm.entrada_inventario_id')
            ->orderByDesc('m.id')->limit($limite)
            ->get(['m.fecha', 'm.tipo', 'm.cantidad', 'm.existencia_resultante', 'p.codigo', 'p.nombre as producto',
                'u.usuario', 'v.id as venta_id', 'v.numero as venta_numero', 'e.numero as entrada_numero', 'e.motivo as entrada_motivo']);
    }

    private function cent(string|int|float|null $v): int
    {
        return (int) round(((float) $v) * 100);
    }

    private function fmt(int $c): string
    {
        return sprintf('%d.%02d', intdiv($c, 100), $c % 100);
    }
}
