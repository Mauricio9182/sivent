<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * CU-03 Gestionar productos y CU-04 Gestionar clientes.
 * Cada cambio se guarda junto con su registro de auditoría en una transacción.
 */
class CatalogoService
{
    public function productos()
    {
        return DB::table('productos as p')
            ->join('categorias as c', 'c.id', '=', 'p.categoria_id')
            ->orderBy('p.codigo')
            ->get(['p.*', 'c.nombre as categoria']);
    }

    public function categorias()
    {
        return DB::table('categorias')->orderBy('nombre')->get(['id', 'nombre']);
    }

    public function crearProducto(Usuario $usuario, array $data): int
    {
        return DB::transaction(function () use ($usuario, $data) {
            $fila = [
                'categoria_id' => (int) $data['categoria_id'],
                'codigo' => $data['codigo'],
                'nombre' => trim($data['nombre']),
                'unidad_venta' => $data['unidad_venta'],
                'precio' => number_format((float) $data['precio'], 2, '.', ''),
                // La existencia inicia en 0: el inventario entra por CU-05.
                'existencia' => '0.00',
                'existencia_minima' => number_format((float) ($data['existencia_minima'] ?? 0), 2, '.', ''),
                'activo' => 1,
            ];
            $id = DB::table('productos')->insertGetId($fila + ['created_at' => now(), 'updated_at' => now()]);
            $this->auditar($usuario, 'Crear producto', 'productos', $id, null, null, $fila);

            return $id;
        });
    }

    /** Cambia precio, existencia mínima y estado; guarda el valor anterior y el nuevo. */
    public function actualizarProducto(Usuario $usuario, int $id, array $data): bool
    {
        return DB::transaction(function () use ($usuario, $id, $data) {
            $p = DB::table('productos')->where('id', $id)->lockForUpdate()->first();
            if (!$p) {
                return false;
            }
            $antes = ['precio' => $p->precio, 'existencia_minima' => $p->existencia_minima, 'activo' => (int) $p->activo];
            $despues = [
                'precio' => number_format((float) $data['precio'], 2, '.', ''),
                'existencia_minima' => number_format((float) ($data['existencia_minima'] ?? 0), 2, '.', ''),
                'activo' => !empty($data['activo']) ? 1 : 0,
            ];
            if ($antes == $despues) {
                return true;
            }
            DB::table('productos')->where('id', $id)->update($despues + ['updated_at' => now()]);

            $operacion = $antes['precio'] !== $despues['precio'] ? 'Actualizar precio' : 'Actualizar producto';
            $this->auditar($usuario, $operacion, 'productos', $id, null, $antes, $despues);

            return true;
        });
    }

    public function clientes(string $buscar = '')
    {
        return DB::table('clientes')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nit', 'like', "%{$buscar}%")
                ->orWhere('nombre', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->get();
    }

    public function crearCliente(Usuario $usuario, array $data): int
    {
        return DB::transaction(function () use ($usuario, $data) {
            $nit = strtoupper(trim((string) ($data['nit'] ?? '')));
            $fila = [
                // "CF" (consumidor final) no es un NIT: se guarda vacío para no chocar con UNIQUE.
                'nit' => ($nit === '' || $nit === 'CF') ? null : $nit,
                'nombre' => trim($data['nombre']),
                'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
                'direccion' => trim((string) ($data['direccion'] ?? '')) ?: null,
            ];
            $id = DB::table('clientes')->insertGetId($fila + ['created_at' => now(), 'updated_at' => now()]);
            $this->auditar($usuario, 'Crear cliente', 'clientes', $id, null, null, $fila);

            return $id;
        });
    }

    private function auditar(Usuario $usuario, string $operacion, string $entidad, int $id, ?string $motivo, ?array $antes, ?array $despues): void
    {
        DB::table('auditoria')->insert([
            'usuario_id' => $usuario->id,
            'operacion' => $operacion,
            'entidad' => $entidad,
            'registro_id' => $id,
            'motivo' => $motivo,
            'valor_anterior' => $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            'valor_nuevo' => $despues ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
