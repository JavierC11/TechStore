-- =============================================================================
-- DigyFenix | Pulso — Esquema Relacional MySQL 8.0 / MariaDB 10.11
-- Base de datos: digyfenix_pulso
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `digyfenix_pulso`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `digyfenix_pulso`;

CREATE TABLE IF NOT EXISTS `configuracion_sitio` (
    `clave` VARCHAR(64) NOT NULL PRIMARY KEY,
    `valor` TEXT NOT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(80) NOT NULL UNIQUE,
    `color` VARCHAR(20) NOT NULL DEFAULT '#22b8cf',
    `icono` TEXT NOT NULL,
    `orden` INT NOT NULL DEFAULT 1,
    `activo` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `productos` (
    `sku` VARCHAR(32) NOT NULL PRIMARY KEY,
    `nombre` VARCHAR(180) NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `categoria` VARCHAR(80) NOT NULL,
    `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `stock_minimo` INT NOT NULL DEFAULT 8,
    `kg` DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    `m3` DECIMAL(8,3) NOT NULL DEFAULT 0.010,
    `imagen` VARCHAR(255) DEFAULT NULL,
    `destacado` TINYINT(1) NOT NULL DEFAULT 0,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `base` TINYINT(1) NOT NULL DEFAULT 0,
    `modulos_json` LONGTEXT NOT NULL,
    `permisos_json` LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `username` VARCHAR(60) NOT NULL PRIMARY KEY,
    `hash_clave` VARCHAR(128) NOT NULL,
    `nombre` VARCHAR(140) NOT NULL,
    `email` VARCHAR(140) NOT NULL,
    `rol_id` VARCHAR(50) NOT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `base` TINYINT(1) NOT NULL DEFAULT 0,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `clientes` (
    `id` VARCHAR(32) NOT NULL PRIMARY KEY,
    `nombre` VARCHAR(160) NOT NULL,
    `tipo` VARCHAR(30) NOT NULL DEFAULT 'Individual',
    `nit` VARCHAR(32) NOT NULL,
    `telefono` VARCHAR(32) NOT NULL,
    `email` VARCHAR(140) DEFAULT NULL,
    `entrega_json` LONGTEXT NOT NULL,
    `facturacion_json` LONGTEXT NOT NULL,
    `creado_en` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cuentas_tienda` (
    `email` VARCHAR(140) NOT NULL PRIMARY KEY,
    `cliente_id` VARCHAR(32) NOT NULL,
    `nombre` VARCHAR(160) NOT NULL,
    `hash_clave` VARCHAR(128) NOT NULL,
    `creado_en` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pedidos` (
    `id` VARCHAR(32) NOT NULL PRIMARY KEY,
    `estado` VARCHAR(40) NOT NULL,
    `fecha` DATETIME NOT NULL,
    `cliente_id` VARCHAR(32) NOT NULL,
    `cliente_json` LONGTEXT NOT NULL,
    `entrega_json` LONGTEXT NOT NULL,
    `facturacion_json` LONGTEXT NOT NULL,
    `items_json` LONGTEXT NOT NULL,
    `envio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `totales_json` LONGTEXT NOT NULL,
    `canal` VARCHAR(40) NOT NULL,
    `pago` VARCHAR(60) NOT NULL,
    `prioridad` VARCHAR(20) NOT NULL DEFAULT 'Normal',
    `franja` VARCHAR(60) DEFAULT NULL,
    `fecha_entrega` VARCHAR(20) DEFAULT NULL,
    `obs_cliente` TEXT DEFAULT NULL,
    `notas_internas` TEXT DEFAULT NULL,
    `creado_por` VARCHAR(120) DEFAULT NULL,
    `actualizado` DATETIME DEFAULT NULL,
    `ruta_id` VARCHAR(32) DEFAULT NULL,
    `picking_json` LONGTEXT DEFAULT NULL,
    `extra_json` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehiculos` (
    `id` VARCHAR(32) NOT NULL PRIMARY KEY,
    `placa` VARCHAR(32) NOT NULL,
    `tipo` VARCHAR(60) NOT NULL,
    `marca` VARCHAR(80) NOT NULL,
    `piloto` VARCHAR(120) NOT NULL,
    `tel` VARCHAR(32) NOT NULL,
    `cap_kg` DECIMAL(10,2) NOT NULL,
    `cap_m3` DECIMAL(10,3) NOT NULL,
    `estado` VARCHAR(40) NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rutas` (
    `id` VARCHAR(32) NOT NULL PRIMARY KEY,
    `codigo` VARCHAR(32) NOT NULL,
    `nombre` VARCHAR(140) NOT NULL,
    `zona` VARCHAR(140) NOT NULL,
    `fecha` VARCHAR(20) NOT NULL,
    `franja` VARCHAR(60) NOT NULL,
    `vehiculo_id` VARCHAR(32) NOT NULL,
    `piloto` VARCHAR(120) NOT NULL,
    `tel_piloto` VARCHAR(32) NOT NULL,
    `estado` VARCHAR(40) NOT NULL,
    `pedidos_json` LONGTEXT NOT NULL,
    `notas` TEXT DEFAULT NULL,
    `creada_por` VARCHAR(120) DEFAULT NULL,
    `creada_en` DATETIME DEFAULT NULL,
    `extra_json` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
    `id` VARCHAR(40) NOT NULL PRIMARY KEY,
    `ts` DATETIME NOT NULL,
    `sku` VARCHAR(32) NOT NULL,
    `tipo` VARCHAR(40) NOT NULL,
    `delta` INT NOT NULL,
    `stock_antes` INT NOT NULL,
    `stock_despues` INT NOT NULL,
    `motivo` VARCHAR(255) DEFAULT NULL,
    `ref` VARCHAR(100) DEFAULT NULL,
    `usuario` VARCHAR(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bitacora` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ts` DATETIME NOT NULL,
    `user` VARCHAR(80) NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    `detail` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kpi_metas` (
    `id` INT NOT NULL PRIMARY KEY DEFAULT 1,
    `metas_json` LONGTEXT NOT NULL,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telemetria_eventos` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `ts` DATETIME NOT NULL,
    `tipo` VARCHAR(48) NOT NULL,
    `canal` VARCHAR(40) NOT NULL DEFAULT 'Tienda web',
    `ref_id` VARCHAR(64) DEFAULT NULL,
    `valido` TINYINT(1) NOT NULL DEFAULT 1,
    `valor` DECIMAL(12,4) DEFAULT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `meta_json` LONGTEXT DEFAULT NULL,
    INDEX `idx_tipo_ts` (`tipo`, `ts`),
    INDEX `idx_tipo_ref` (`tipo`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

