<?php

namespace App\Support;

/** Pantallas que puede abrir cada rol (RF-02, autorización por rol). */
final class Permisos
{
    public static function roles(): array
    {
        return [
            'Administrador' => ['panel', 'productos', 'clientes', 'inventario', 'ventas', 'consultas', 'auditoria'],
            'Vendedor' => ['panel', 'clientes', 'ventas', 'consultas'],
            'Encargado de bodega' => ['panel', 'productos', 'inventario', 'consultas'],
            'Gerente' => ['panel', 'productos', 'clientes', 'inventario', 'ventas', 'consultas', 'auditoria'],
        ];
    }

    public static function puede(?string $rol, string $seccion): bool
    {
        return in_array($seccion, self::roles()[$rol] ?? [], true);
    }
}
