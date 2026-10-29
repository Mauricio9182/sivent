<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RNF-06: la estructura se crea con migraciones.
 * Contiene la parte de estructura del script v3 de Edgar (tablas y triggers),
 * sin CREATE DATABASE, USE ni líneas DELIMITER. Los datos van en SiventSeeder.
 */
return new class extends Migration
{
    private const SUFIJO = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(): void
    {
        $tablas = [];

        $tablas[] = <<<'SQL'
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    CONSTRAINT uq_roles_nombre UNIQUE (nombre)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(60) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_usuarios_usuario UNIQUE (usuario),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id)
        REFERENCES roles(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_usuarios_activo CHECK (activo IN (0, 1)),
    CONSTRAINT ck_usuarios_intentos CHECK (intentos_fallidos <= 5)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE productos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    unidad_venta ENUM('UNIDAD','CAJA','LIBRA','GALON','METRO') NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    existencia DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    existencia_minima DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_productos_codigo UNIQUE (codigo),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_productos_precio CHECK (precio > 0),
    CONSTRAINT ck_productos_existencia CHECK (existencia >= 0),
    CONSTRAINT ck_productos_minima CHECK (existencia_minima >= 0),
    CONSTRAINT ck_productos_activo CHECK (activo IN (0, 1)),
    CONSTRAINT ck_productos_existencia_entera CHECK (
        unidad_venta NOT IN ('UNIDAD','CAJA')
        OR existencia = FLOOR(existencia)
    ),
    CONSTRAINT ck_productos_minima_entera CHECK (
        unidad_venta NOT IN ('UNIDAD','CAJA')
        OR existencia_minima = FLOOR(existencia_minima)
    ),
    INDEX idx_productos_activo_nombre (activo, nombre),
    INDEX idx_productos_categoria (categoria_id)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nit VARCHAR(25) NULL,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(25) NULL,
    direccion VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_clientes_nit UNIQUE (nit),
    CONSTRAINT ck_clientes_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
    CONSTRAINT ck_clientes_nit CHECK (
        nit IS NULL OR CHAR_LENGTH(TRIM(nit)) > 0
    ),
    INDEX idx_clientes_nombre (nombre)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE ventas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero BIGINT UNSIGNED NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    cliente_id BIGINT UNSIGNED NULL,
    usuario_creacion_id BIGINT UNSIGNED NOT NULL,
    usuario_confirmacion_id BIGINT UNSIGNED NULL,
    usuario_anulacion_id BIGINT UNSIGNED NULL,
    estado ENUM('BORRADOR','CONFIRMADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    token_confirmacion VARCHAR(100) NOT NULL,
    fecha_confirmacion DATETIME(6) NULL,
    fecha_anulacion DATETIME(6) NULL,
    motivo_anulacion VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_ventas_numero UNIQUE (numero),
    CONSTRAINT uq_ventas_token UNIQUE (token_confirmacion),
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_creador FOREIGN KEY (usuario_creacion_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_confirmador FOREIGN KEY (usuario_confirmacion_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_anulador FOREIGN KEY (usuario_anulacion_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_ventas_total CHECK (total >= 0),
    CONSTRAINT ck_ventas_estado_datos CHECK (
        (estado = 'BORRADOR'
            AND numero IS NULL
            AND usuario_confirmacion_id IS NULL
            AND fecha_confirmacion IS NULL
            AND usuario_anulacion_id IS NULL
            AND fecha_anulacion IS NULL
            AND motivo_anulacion IS NULL)
        OR
        (estado = 'CONFIRMADA'
            AND numero IS NOT NULL
            AND usuario_confirmacion_id IS NOT NULL
            AND fecha_confirmacion IS NOT NULL
            AND usuario_anulacion_id IS NULL
            AND fecha_anulacion IS NULL
            AND motivo_anulacion IS NULL)
        OR
        (estado = 'ANULADA'
            AND numero IS NOT NULL
            AND usuario_confirmacion_id IS NOT NULL
            AND fecha_confirmacion IS NOT NULL
            AND usuario_anulacion_id IS NOT NULL
            AND fecha_anulacion IS NOT NULL
            AND motivo_anulacion IS NOT NULL
            AND CHAR_LENGTH(TRIM(motivo_anulacion)) >= 10)
    ),
    INDEX idx_ventas_estado_fecha (estado, fecha_confirmacion),
    INDEX idx_ventas_cliente (cliente_id)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE detalle_venta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(14,2) NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    CONSTRAINT uq_detalle_venta_producto UNIQUE (venta_id, producto_id),
    CONSTRAINT fk_detalle_venta_venta FOREIGN KEY (venta_id)
        REFERENCES ventas(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_detalle_venta_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_detalle_venta_cantidad CHECK (cantidad > 0),
    CONSTRAINT ck_detalle_venta_precio CHECK (precio_unitario > 0),
    CONSTRAINT ck_detalle_venta_subtotal CHECK (
        subtotal = ROUND(cantidad * precio_unitario, 2)
    ),
    INDEX idx_detalle_venta_producto (producto_id)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE entradas_inventario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero BIGINT UNSIGNED NOT NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    motivo ENUM('COMPRA','DEVOLUCION','AJUSTE') NOT NULL,
    proveedor VARCHAR(150) NULL,
    documento VARCHAR(100) NULL,
    token VARCHAR(100) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT uq_entradas_numero UNIQUE (numero),
    CONSTRAINT uq_entradas_token UNIQUE (token),
    CONSTRAINT fk_entradas_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_entradas_fecha (fecha)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE detalle_entrada (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrada_inventario_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(14,2) NOT NULL,
    CONSTRAINT uq_detalle_entrada_producto UNIQUE
        (entrada_inventario_id, producto_id),
    CONSTRAINT fk_detalle_entrada_entrada FOREIGN KEY (entrada_inventario_id)
        REFERENCES entradas_inventario(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_detalle_entrada_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_detalle_entrada_cantidad CHECK (cantidad > 0),
    INDEX idx_detalle_entrada_producto (producto_id)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE movimientos_inventario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    producto_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    venta_id BIGINT UNSIGNED NULL,
    entrada_inventario_id BIGINT UNSIGNED NULL,
    tipo ENUM('ENTRADA','SALIDA_VENTA','ENTRADA_ANULACION') NOT NULL,
    cantidad DECIMAL(14,2) NOT NULL,
    existencia_resultante DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_mov_venta FOREIGN KEY (venta_id)
        REFERENCES ventas(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_mov_entrada FOREIGN KEY (entrada_inventario_id)
        REFERENCES entradas_inventario(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_mov_venta_producto_tipo UNIQUE (venta_id, producto_id, tipo),
    CONSTRAINT uq_mov_entrada_producto_tipo UNIQUE
        (entrada_inventario_id, producto_id, tipo),
    CONSTRAINT ck_mov_cantidad CHECK (cantidad > 0),
    CONSTRAINT ck_mov_resultante CHECK (existencia_resultante >= 0),
    CONSTRAINT ck_mov_origen CHECK (
        (tipo = 'ENTRADA'
            AND entrada_inventario_id IS NOT NULL AND venta_id IS NULL)
        OR
        (tipo IN ('SALIDA_VENTA','ENTRADA_ANULACION')
            AND venta_id IS NOT NULL AND entrada_inventario_id IS NULL)
    ),
    INDEX idx_mov_producto_fecha (producto_id, fecha),
    INDEX idx_mov_fecha (fecha)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    fecha_hora DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    operacion VARCHAR(100) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    registro_id BIGINT UNSIGNED NULL,
    motivo VARCHAR(500) NULL,
    valor_anterior JSON NULL,
    valor_nuevo JSON NULL,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_auditoria_usuario_fecha (usuario_id, fecha_hora),
    INDEX idx_auditoria_operacion_fecha (operacion, fecha_hora),
    INDEX idx_auditoria_entidad_registro (entidad, registro_id)
) ENGINE=InnoDB
SQL;

        $tablas[] = <<<'SQL'
CREATE TABLE secuencias_documento (
    tipo ENUM('VENTA','ENTRADA') PRIMARY KEY,
    ultimo_numero BIGINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT ck_secuencias_numero CHECK (ultimo_numero >= 0)
) ENGINE=InnoDB
SQL;

        foreach ($tablas as $sql) {
            DB::unprepared(str_replace('ENGINE=InnoDB', self::SUFIJO, $sql));
        }

        // Triggers: cada uno es UNA sola instrucción (sin DELIMITER).
        $triggers = [];

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_auditoria_sin_edicion
BEFORE UPDATE ON auditoria FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RN-07: los registros de auditoria no se modifican'
SQL;

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_auditoria_sin_borrado
BEFORE DELETE ON auditoria FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RN-07: los registros de auditoria no se eliminan'
SQL;

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_movimientos_sin_edicion
BEFORE UPDATE ON movimientos_inventario FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RF-06: el historial de inventario no se modifica'
SQL;

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_movimientos_sin_borrado
BEFORE DELETE ON movimientos_inventario FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RF-06: el historial de inventario no se elimina'
SQL;

        // RN-05: el detalle de ventas solo se modifica en BORRADOR.
        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_detalle_venta_ins BEFORE INSERT ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = NEW.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: solo se agregan productos a ventas en BORRADOR';
    END IF;
END
SQL;

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_detalle_venta_upd BEFORE UPDATE ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = OLD.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: el detalle de una venta confirmada o anulada no se modifica';
    END IF;

    IF (SELECT estado FROM ventas WHERE id = NEW.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: solo se trasladan detalles a ventas en BORRADOR';
    END IF;
END
SQL;

        $triggers[] = <<<'SQL'
CREATE TRIGGER trg_detalle_venta_del BEFORE DELETE ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = OLD.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: el detalle de una venta confirmada o anulada no se elimina';
    END IF;
END
SQL;

        foreach ($triggers as $sql) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([
            'secuencias_documento', 'auditoria', 'movimientos_inventario',
            'detalle_entrada', 'entradas_inventario', 'detalle_venta', 'ventas',
            'clientes', 'productos', 'categorias', 'usuarios', 'roles',
        ] as $tabla) {
            DB::unprepared("DROP TABLE IF EXISTS {$tabla}");
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');
    }
};
