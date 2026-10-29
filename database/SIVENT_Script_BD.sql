-- SIVENT - Sistema de Ventas e Inventario
-- Ferreteria El Constructor | Analisis de Sistemas II
-- Diseno de datos: Edgar Guillermo Chinchilla Chinchilla
-- MySQL 8.0.16+ / InnoDB / utf8mb4
-- Instalacion inicial: ejecutar en una instancia de desarrollo limpia.
--
-- Base documental: SIVENT_Requisitos_y_UML, diagrama de clases,
-- CU-05, CU-08, CU-10 y RN-01..RN-07.
-- IMPORTANTE:
--   1. No contiene DROP: no borra informacion existente.
--   2. CREATE TABLE falla si ya existe una tabla: no se debe importar dos veces.
--   3. El bloqueo FOR UPDATE, la autorizacion, el calculo de totales
--      y COMMIT/ROLLBACK pertenecen a VentaService en Laravel.
--   4. MySQL 8.0.16 o superior es necesario para CHECK exigibles.

CREATE DATABASE IF NOT EXISTS sivent
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sivent;

-- 01. ROLES / USUARIOS (RF-01, RF-02; RNF-01)
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    CONSTRAINT uq_roles_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- 02. CATALOGO (RF-03; RN-01, RN-02)
CREATE TABLE categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- 03. CLIENTES (RF-04): cliente_id NULL en ventas = Consumidor Final (CF).
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
) ENGINE=InnoDB;

-- 04. VENTAS Y DETALLE (RF-07, RF-08, RF-09, RF-10; RN-03, RN-05, RN-06, RN-07)
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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- 05. ENTRADAS DE INVENTARIO (RF-05; CU-05)
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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- 06. HISTORIAL DE INVENTARIO (RF-06; RN-06)
-- El origen es una entrada de inventario O una venta, nunca ambos.
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
) ENGINE=InnoDB;

-- 07. AUDITORIA (RF-12; RN-07): inserciones solamente.
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
) ENGINE=InnoDB;

-- 08. APOYO TECNICO PARA NUMEROS CORRELATIVOS.
-- No es una entidad de negocio del diagrama de clases.
-- Dentro de la transaccion, bloquear y aumentar la fila adecuada
-- antes de asignar ventas.numero o entradas_inventario.numero.
CREATE TABLE secuencias_documento (
    tipo ENUM('VENTA','ENTRADA') PRIMARY KEY,
    ultimo_numero BIGINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT ck_secuencias_numero CHECK (ultimo_numero >= 0)
) ENGINE=InnoDB;

-- 09. PROTECCIONES ADICIONALES DEL HISTORICO
-- MySQL acepta triggers de una sola instruccion sin cambiar DELIMITER.
CREATE TRIGGER trg_auditoria_sin_edicion
BEFORE UPDATE ON auditoria FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RN-07: los registros de auditoria no se modifican';

CREATE TRIGGER trg_auditoria_sin_borrado
BEFORE DELETE ON auditoria FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RN-07: los registros de auditoria no se eliminan';

CREATE TRIGGER trg_movimientos_sin_edicion
BEFORE UPDATE ON movimientos_inventario FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RF-06: el historial de inventario no se modifica';

CREATE TRIGGER trg_movimientos_sin_borrado
BEFORE DELETE ON movimientos_inventario FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'RF-06: el historial de inventario no se elimina';

-- RN-05: proteccion del detalle de ventas; solo se modifica en BORRADOR.
DELIMITER $$

CREATE TRIGGER trg_detalle_venta_ins BEFORE INSERT ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = NEW.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: solo se agregan productos a ventas en BORRADOR';
    END IF;
END$$

CREATE TRIGGER trg_detalle_venta_upd BEFORE UPDATE ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = OLD.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: el detalle de una venta confirmada o anulada no se modifica';
    END IF;

    IF (SELECT estado FROM ventas WHERE id = NEW.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: solo se trasladan detalles a ventas en BORRADOR';
    END IF;
END$$

CREATE TRIGGER trg_detalle_venta_del BEFORE DELETE ON detalle_venta FOR EACH ROW
BEGIN
    IF (SELECT estado FROM ventas WHERE id = OLD.venta_id) <> 'BORRADOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'RN-05: el detalle de una venta confirmada o anulada no se elimina';
    END IF;
END$$

DELIMITER ;

-- 10. DATOS DE PRUEBA
-- Usuarios y contrasenas son EXCLUSIVAMENTE para desarrollo local.
-- Los cuatro usuarios de demostracion usan la clave: contra123
-- Hash bcrypt de prueba; sustituir credenciales antes de publicar.
-- Cada existencia inicial nace de una entrada de inventario y su
-- movimiento: no hay inventario inicial "magico" fuera del historial.
START TRANSACTION;

INSERT INTO roles (nombre) VALUES
    ('Administrador'),
    ('Vendedor'),
    ('Encargado de bodega'),
    ('Gerente');

INSERT INTO usuarios
    (rol_id, nombre, usuario, contrasena_hash, activo, intentos_fallidos)
VALUES
    (1, 'Administrador de prueba', 'admin',
     '$2y$12$GGnUGO/VavizQgSQDtGoZeG4ti2L87lRqd4b0HBmw9/vgyM4DRrP2', 1, 0),
    (2, 'Vendedor de prueba', 'vendedor1',
     '$2y$12$1uordlWSBwLOtZ5gRmjcGO5w6rGqh2JKDh/qVaAg8.PYr8Z/H9JUW', 1, 0),
    (3, 'Bodeguero de prueba', 'bodega1',
     '$2y$12$uHjHidHVQc.RizXJIZ7IiupI751KkTa6f3MVmEHlMunIobSxB2c0C', 1, 0),
    (4, 'Gerente de prueba', 'gerente1',
     '$2y$12$tzqduPSfrAeQQuwVf0XESOH5o0Z3LbGsRGjOlbCgFpXBAtCKKQRm6', 1, 0);

INSERT INTO categorias (nombre) VALUES
    ('Herramientas'),
    ('Materiales de construccion'),
    ('Pintura'),
    ('Electricidad'),
    ('Plomeria');

INSERT INTO clientes (nit, nombre, telefono, direccion) VALUES
    ('5487123-4', 'Constructora Los Pinos', '5555-0101', 'Jutiapa'),
    ('2789456-1', 'Maria Lopez', '5555-0102', 'Asuncion Mita'),
    ('3001001-1', 'Ferreteria San Jose', '5555-0103', 'Jutiapa'),
    ('3001002-2', 'Carlos Ramirez', '5555-0104', 'Asuncion Mita'),
    ('3001003-3', 'Distribuidora El Roble', '5555-0105', 'El Progreso, Jutiapa'),
    ('3001004-4', 'Ana Morales', '5555-0106', 'Santa Catarina Mita'),
    ('3001005-5', 'Constructora del Oriente', '5555-0107', 'Jutiapa'),
    ('3001006-6', 'Jose Garcia', '5555-0108', 'Asuncion Mita'),
    ('3001007-7', 'Servicios Integrales Lopez', '5555-0109', 'Jalapa'),
    ('3001008-8', 'Pedro Hernandez', '5555-0110', 'Jutiapa'),
    ('3001009-9', 'Mariana Castillo', '5555-0111', 'El Progreso, Jutiapa'),
    ('3001010-0', 'Comercial Santa Elena', '5555-0112', 'Asuncion Mita'),
    ('3001011-1', 'Sofia Perez', '5555-0113', 'Jalapa'),
    ('3001012-2', 'Constructora Las Flores', '5555-0114', 'Santa Catarina Mita'),
    ('3001013-3', 'Luis Martinez', '5555-0115', 'Jutiapa');

-- Primero todos los productos con existencia 0.
INSERT INTO productos
    (categoria_id, codigo, nombre, unidad_venta, precio,
     existencia, existencia_minima, activo)
VALUES
    (1, 'HERR-001', 'Martillo de acero',       'UNIDAD', 65.00,  0.00, 3.00, 1),
    (2, 'CONS-001', 'Clavos de 2 pulgadas',    'LIBRA',  12.50,  0.00, 5.00, 1),
    (3, 'PINT-001', 'Pintura blanca',          'GALON', 175.00,  0.00, 2.00, 1),
    (4, 'ELEC-001', 'Cable electrico',         'METRO',   9.75,  0.00, 20.00, 1),
    (5, 'PLOM-001', 'Llave de paso',            'UNIDAD', 48.00,  0.00, 2.00, 1),
    (2, 'CONS-002', 'Caja de tornillos',       'CAJA',   30.00,  0.00, 3.00, 1),
    (1, 'HERR-002', 'Taladro descontinuado',   'UNIDAD', 350.00, 0.00, 0.00, 0),
    (1, 'HERR-003', 'Destornillador Phillips', 'UNIDAD', 28.00,  0.00, 5.00, 1),
    (1, 'HERR-004', 'Llave ajustable',          'UNIDAD', 70.00,  0.00, 4.00, 1),
    (1, 'HERR-005', 'Alicate universal',        'UNIDAD', 55.00,  0.00, 3.00, 1),
    (1, 'HERR-006', 'Sierra manual',            'UNIDAD', 85.00,  0.00, 5.00, 1),
    (2, 'CONS-003', 'Cemento gris en bolsa',   'UNIDAD', 52.00,  0.00, 15.00, 1),
    (2, 'CONS-004', 'Arena en bolsa',          'UNIDAD', 18.00,  0.00, 10.00, 1),
    (2, 'CONS-005', 'Varilla corrugada',       'UNIDAD', 36.00,  0.00, 8.00, 1),
    (2, 'CONS-006', 'Alambre de amarre',       'LIBRA',  14.00,  0.00, 3.00, 1),
    (3, 'PINT-002', 'Pintura azul',            'GALON', 165.00,  0.00, 3.00, 1),
    (3, 'PINT-003', 'Pintura roja',            'GALON', 168.00,  0.00, 4.00, 1),
    (3, 'PINT-004', 'Esmalte negro',           'GALON', 195.00,  0.00, 2.00, 1),
    (3, 'PINT-005', 'Sellador para pared',     'GALON', 110.00,  0.00, 4.00, 1),
    (3, 'PINT-006', 'Brocha de 3 pulgadas',    'UNIDAD', 24.50,  0.00, 5.00, 1),
    (4, 'ELEC-002', 'Interruptor sencillo',    'UNIDAD', 22.00,  0.00, 10.00, 1),
    (4, 'ELEC-003', 'Tomacorriente doble',     'UNIDAD', 35.00,  0.00, 6.00, 1),
    (4, 'ELEC-004', 'Cinta aislante',          'UNIDAD', 12.00,  0.00, 5.00, 1),
    (4, 'ELEC-005', 'Tubo conduit',            'METRO',   7.80,  0.00, 15.00, 1),
    (4, 'ELEC-006', 'Bombilla LED 9W',         'UNIDAD', 29.90,  0.00, 6.00, 1),
    (5, 'PLOM-002', 'Tubo PVC de media pulgada', 'METRO', 15.75, 0.00, 12.00, 1),
    (5, 'PLOM-003', 'Codo PVC de media pulgada', 'UNIDAD', 4.00, 0.00, 15.00, 1),
    (5, 'PLOM-004', 'Pegamento para PVC',      'UNIDAD', 32.00,  0.00, 4.00, 1),
    (5, 'PLOM-005', 'Valvula de esfera',       'UNIDAD', 68.00,  0.00, 3.00, 1),
    (5, 'PLOM-006', 'Cinta de teflon',         'UNIDAD',  5.50,  0.00, 10.00, 1);

INSERT INTO secuencias_documento (tipo, ultimo_numero) VALUES
    ('VENTA', 0),
    ('ENTRADA', 1);

-- CU-05: entrada inicial (tipo AJUSTE) de los 26 productos con stock.
-- Los agotados e inactivos comienzan en 0, sin movimientos ficticios.
INSERT INTO entradas_inventario
    (numero, motivo, proveedor, documento, token, usuario_id)
VALUES
    (1, 'AJUSTE', NULL, 'APERTURA-SIVENT-2026',
     '4f10c2e0-3456-4a10-8025-000000000001', 3);

INSERT INTO detalle_entrada (entrada_inventario_id, producto_id, cantidad)
VALUES
    (1, 1, 10.00),
    (1, 2, 25.50),
    (1, 3, 8.00),
    (1, 4, 100.25),
    (1, 5, 1.00),
    (1, 6, 12.00),
    (1, 8, 18.00),
    (1, 9, 8.00),
    (1, 11, 4.00),
    (1, 12, 60.00),
    (1, 13, 35.00),
    (1, 14, 20.00),
    (1, 15, 12.75),
    (1, 16, 9.00),
    (1, 17, 3.00),
    (1, 19, 14.50),
    (1, 20, 16.00),
    (1, 21, 40.00),
    (1, 22, 21.00),
    (1, 23, 3.00),
    (1, 24, 75.50),
    (1, 26, 50.00),
    (1, 27, 60.00),
    (1, 28, 9.00),
    (1, 29, 2.00),
    (1, 30, 35.00);

UPDATE productos SET existencia = 10.00   WHERE id = 1;
UPDATE productos SET existencia = 25.50   WHERE id = 2;
UPDATE productos SET existencia = 8.00    WHERE id = 3;
UPDATE productos SET existencia = 100.25  WHERE id = 4;
UPDATE productos SET existencia = 1.00    WHERE id = 5;
UPDATE productos SET existencia = 12.00   WHERE id = 6;
UPDATE productos SET existencia = 18.00   WHERE id = 8;
UPDATE productos SET existencia = 8.00    WHERE id = 9;
UPDATE productos SET existencia = 4.00    WHERE id = 11;
UPDATE productos SET existencia = 60.00   WHERE id = 12;
UPDATE productos SET existencia = 35.00   WHERE id = 13;
UPDATE productos SET existencia = 20.00   WHERE id = 14;
UPDATE productos SET existencia = 12.75   WHERE id = 15;
UPDATE productos SET existencia = 9.00    WHERE id = 16;
UPDATE productos SET existencia = 3.00    WHERE id = 17;
UPDATE productos SET existencia = 14.50   WHERE id = 19;
UPDATE productos SET existencia = 16.00   WHERE id = 20;
UPDATE productos SET existencia = 40.00   WHERE id = 21;
UPDATE productos SET existencia = 21.00   WHERE id = 22;
UPDATE productos SET existencia = 3.00    WHERE id = 23;
UPDATE productos SET existencia = 75.50   WHERE id = 24;
UPDATE productos SET existencia = 50.00   WHERE id = 26;
UPDATE productos SET existencia = 60.00   WHERE id = 27;
UPDATE productos SET existencia = 9.00    WHERE id = 28;
UPDATE productos SET existencia = 2.00    WHERE id = 29;
UPDATE productos SET existencia = 35.00   WHERE id = 30;

INSERT INTO movimientos_inventario
    (producto_id, usuario_id, entrada_inventario_id, venta_id,
     tipo, cantidad, existencia_resultante)
VALUES
    (1, 3, 1, NULL, 'ENTRADA', 10.00, 10.00),
    (2, 3, 1, NULL, 'ENTRADA', 25.50, 25.50),
    (3, 3, 1, NULL, 'ENTRADA', 8.00, 8.00),
    (4, 3, 1, NULL, 'ENTRADA', 100.25, 100.25),
    (5, 3, 1, NULL, 'ENTRADA', 1.00, 1.00),
    (6, 3, 1, NULL, 'ENTRADA', 12.00, 12.00),
    (8, 3, 1, NULL, 'ENTRADA', 18.00, 18.00),
    (9, 3, 1, NULL, 'ENTRADA', 8.00, 8.00),
    (11, 3, 1, NULL, 'ENTRADA', 4.00, 4.00),
    (12, 3, 1, NULL, 'ENTRADA', 60.00, 60.00),
    (13, 3, 1, NULL, 'ENTRADA', 35.00, 35.00),
    (14, 3, 1, NULL, 'ENTRADA', 20.00, 20.00),
    (15, 3, 1, NULL, 'ENTRADA', 12.75, 12.75),
    (16, 3, 1, NULL, 'ENTRADA', 9.00, 9.00),
    (17, 3, 1, NULL, 'ENTRADA', 3.00, 3.00),
    (19, 3, 1, NULL, 'ENTRADA', 14.50, 14.50),
    (20, 3, 1, NULL, 'ENTRADA', 16.00, 16.00),
    (21, 3, 1, NULL, 'ENTRADA', 40.00, 40.00),
    (22, 3, 1, NULL, 'ENTRADA', 21.00, 21.00),
    (23, 3, 1, NULL, 'ENTRADA', 3.00, 3.00),
    (24, 3, 1, NULL, 'ENTRADA', 75.50, 75.50),
    (26, 3, 1, NULL, 'ENTRADA', 50.00, 50.00),
    (27, 3, 1, NULL, 'ENTRADA', 60.00, 60.00),
    (28, 3, 1, NULL, 'ENTRADA', 9.00, 9.00),
    (29, 3, 1, NULL, 'ENTRADA', 2.00, 2.00),
    (30, 3, 1, NULL, 'ENTRADA', 35.00, 35.00);

INSERT INTO auditoria (usuario_id, operacion, entidad, registro_id, motivo)
VALUES
    (3, 'Registrar entrada', 'entradas_inventario', 1,
     'Carga inicial de existencias de SIVENT');

-- CU-07: los borradores tienen detalle, pero NO descuentan inventario.
-- Venta 1: caso normal con dos productos, cliente registrado.
-- Ventas 2 y 3: compiten por la ultima unidad del producto PLOM-001.
-- Debe poder confirmarse SOLO una de estas ultimas dos (RN-04).
INSERT INTO ventas
    (cliente_id, usuario_creacion_id, estado, total, token_confirmacion)
VALUES
    (1, 2, 'BORRADOR', 0.00, 'da3a8248-ade9-4b1f-81ba-000000000001'),
    (NULL, 2, 'BORRADOR', 0.00, 'da3a8248-ade9-4b1f-81ba-000000000002'),
    (NULL, 2, 'BORRADOR', 0.00, 'da3a8248-ade9-4b1f-81ba-000000000003');

INSERT INTO detalle_venta
    (venta_id, producto_id, cantidad, precio_unitario, subtotal)
VALUES
    (1, 1, 2.00, 65.00, 130.00),
    (1, 2, 1.50, 12.50, 18.75),
    (2, 5, 1.00, 48.00, 48.00),
    (3, 5, 1.00, 48.00, 48.00);

COMMIT;

-- 11. CONSULTAS DE VERIFICACION (NO modifican datos).
SELECT id, codigo, unidad_venta, existencia, existencia_minima, activo
FROM productos ORDER BY id;

SELECT v.id, v.numero, v.estado, v.token_confirmacion,
       COUNT(d.id) AS productos_en_borrador, v.total
FROM ventas AS v
LEFT JOIN detalle_venta AS d ON d.venta_id = v.id
GROUP BY v.id, v.numero, v.estado, v.token_confirmacion, v.total
ORDER BY v.id;

SELECT p.codigo, p.existencia,
    COALESCE(SUM(
        CASE m.tipo
            WHEN 'SALIDA_VENTA' THEN -m.cantidad
            ELSE m.cantidad
        END
    ), 0.00) AS existencia_segun_historial
FROM productos AS p
LEFT JOIN movimientos_inventario AS m ON m.producto_id = p.id
GROUP BY p.id, p.codigo, p.existencia
ORDER BY p.codigo;

-- 12. CONTRATO DE INTEGRACION PARA PABLO (Laravel)
-- CU-08 Confirmar venta:
--   a. Validar sesion, rol Vendedor y token de confirmacion.
--   b. DB::transaction: bloquear la fila ventas por ID (FOR UPDATE).
--      Si ya esta CONFIRMADA y token coincide, devolver comprobante.
--   c. Bloquear productos en orden ascendente de ID (FOR UPDATE).
--      Validar activo, unidad de venta, cantidad y existencia.
--   d. Leer precio de productos.precio; recalcular detalle_venta
--      (precio_unitario, subtotal) y total de ventas desde el servidor.
--      Si el total visible difiere, ROLLBACK y pedir reconfirmacion.
--   e. Bloquear fila secuencias_documento tipo VENTA (FOR UPDATE);
--      aumentar ultimo_numero y asignar ese valor a ventas.numero.
--   f. Actualizar ventas: CONFIRMADA, usuario_confirmacion_id,
--      fecha_confirmacion, total, numero.
--   g. Descontar productos.existencia y registrar un SALIDA_VENTA
--      por cada producto, con existencia_resultante.
--   h. Insertar auditoria "Confirmar venta"; COMMIT.
--      Cualquier falla: ROLLBACK completo.
--   i. El token UNIQUE y el bloqueo sobre ventas impiden reintentos
--      duplicados; la UNIQUE de movimientos agrega otra proteccion.
--
-- CU-05 Registrar entrada:
--   Una sola transaccion para entradas_inventario, detalle_entrada,
--   incremento de productos.existencia, movimientos ENTRADA
--   y asignacion de numero desde secuencias_documento.
--
-- CU-10 Anular venta:
--   Autorizar Gerente/Administrador; exigir motivo de 10+ caracteres.
--   Bloquear la venta, exigir estado CONFIRMADA, luego productos en orden.
--   Cambiar a ANULADA, guardar usuario/fecha/motivo, reponer existencias
--   y registrar movimientos ENTRADA_ANULACION y auditoria. Un solo COMMIT.
--   Una segunda anulacion se rechaza sin modificar las existencias.
--
-- RNF-06: al integrar este script en Laravel, replicar el esquema
-- como migraciones; NO ejecutar CREATE TABLE por fuera de migraciones
-- en el entorno final si Laravel ya gestiono esas mismas tablas.
-- Configurar el modelo Usuario para usar contrasena_hash y usuario
-- en vez de los nombres convencionales password y email de Laravel.
-- Recordatorio: el bloqueo de cuenta, CSRF, permisos y sesion son
-- responsabilidades de Laravel, no del DDL.
