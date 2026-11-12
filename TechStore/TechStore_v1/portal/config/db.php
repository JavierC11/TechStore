<?php
/**
 * DigyFenix | Pulso — Capa de Conexión y Persistencia MySQL (PDO)
 * Inicializa automáticamente el esquema y los datos semilla si la base de datos está vacía.
 */

declare(strict_types=1);

require_once __DIR__ . '/telemetry.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Guatemala');

/**
 * Obtiene la conexión PDO a MySQL probando primero variables de entorno (Docker)
 * y luego fallback a XAMPP local (127.0.0.1:3306 / 3307).
 */
function getPDO(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }

    $envHost = getenv('DB_HOST') ?: '';
    $envPort = getenv('DB_PORT') ?: '3306';
    $envName = getenv('DB_NAME') ?: 'digyfenix_pulso';
    $envUser = getenv('DB_USER') ?: 'root';
    $envPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    $candidates = [];
    if ($envHost !== '') {
        $candidates[] = ['host' => $envHost, 'port' => $envPort, 'db' => $envName, 'user' => $envUser, 'pass' => $envPass];
    }
    // Candidatos locales (Docker expuesto en 3307 o XAMPP local en 3306)
    $candidates[] = ['host' => 'portal-db', 'port' => '3306', 'db' => 'digyfenix_pulso', 'user' => 'pulso_user', 'pass' => 'Pulso@2026'];
    $candidates[] = ['host' => '127.0.0.1', 'port' => '3307', 'db' => 'digyfenix_pulso', 'user' => 'pulso_user', 'pass' => 'Pulso@2026'];
    $candidates[] = ['host' => '127.0.0.1', 'port' => '3306', 'db' => 'digyfenix_pulso', 'user' => 'root', 'pass' => ''];

    foreach ($candidates as $c) {
        try {
            // Intentar conectar directamente a la base de datos
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['db']);
            $conn = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 2,
            ]);
            $pdo = $conn;
            ensureSchemaAndSeed($pdo);
            return $pdo;
        } catch (PDOException $e) {
            // Si la base de datos aún no existe en el servidor MySQL, intentar crearla
            try {
                $dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $c['host'], $c['port']);
                $conn = new PDO($dsnNoDb, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 2,
                ]);
                $dbSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $c['db']);
                $conn->exec("CREATE DATABASE IF NOT EXISTS `{$dbSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $conn->exec("USE `{$dbSafe}`");
                $pdo = $conn;
                ensureSchemaAndSeed($pdo);
                return $pdo;
            } catch (PDOException $e2) {
                continue;
            }
        }
    }

    $pdo = null;
    return null;
}

/**
 * Crea las tablas necesarias en MySQL y puebla los datos iniciales si están vacías.
 */
function ensureSchemaAndSeed(PDO $pdo, bool $force = false): void {
    static $checked = false;
    if (!$force && $checked) {
        return;
    }
    $checked = true;

    try {
        ensureTelemetryTable($pdo);
    } catch (Throwable $e) {
        // Ignorar si aún no existe el esquema completo
    }

    if (!$force) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `productos` WHERE `sku` LIKE 'DF-LAP%' OR `sku` LIKE 'DF-CMP%'");
            if ($stmt && (int)$stmt->fetchColumn() >= 10) {
                migrateLegacyDataIfNeeded($pdo);
                seedOrdersIfEmpty($pdo, false);
                seedTelemetryIfEmpty($pdo, false);
                return;
            }
        } catch (Throwable $e) {
            // La tabla aún no existe; continuar con creación de esquema y semilla
        }
    }

    // 1. Crear tablas
    $pdo->exec("
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
    ");

    seedInitialDatabase($pdo, $force);
}

/**
 * Migra datos heredados (hashes viejos de semilla o pedidos con SKUs obsoletos) si el volumen MySQL ya existía.
 */
function migrateLegacyDataIfNeeded(PDO $pdo): void {
    try {
        // 1. Actualizar solo hashes que coinciden exactamente con los de la semilla antigua
        $legacyMap = [
            'admin'              => ['old' => '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'new' => 'a36aef5a11c4073fbe60314fc9df530a9d5f986533594d1f5190742ff9e0e408'],
            'carlos.lopez'       => ['old' => '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'new' => '104937774c7abf0d900bd80332f1e3815b9a052c4b00fab4d271263bb2423689'],
            'victor.castillo'    => ['old' => '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'new' => 'b6d5af78d1361603dfb57e5782d36ebeda101619f6e06edcdd041bb00b5092a1'],
            'oscar.ortiz'        => ['old' => '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'new' => 'dd07d83636335b578ad481994a5fd68a5d9f53b4a0d505cce562cfbc69cd0838'],
            'emmanuel.ajsivinac' => ['old' => '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'new' => 'acdf94c530b9e70ba13ec06182a4482eb28d984ae102b20445dab495c9fcec76'],
        ];
        $stmtUp = $pdo->prepare("UPDATE `usuarios` SET `hash_clave` = ? WHERE `username` = ? AND `hash_clave` = ?");
        foreach ($legacyMap as $usr => $hashes) {
            $stmtUp->execute([$hashes['new'], $usr, $hashes['old']]);
        }
        $stmtUp->execute([
            'b6d5af78d1361603dfb57e5782d36ebeda101619f6e06edcdd041bb00b5092a1',
            'victor.castillo',
            'b6d5af78d1361603dfb57e5782d36ebeda101619f6e06edcdd041bb00b5092a',
        ]);

        // 2. Si `pedidos` aún tiene los SKUs viejos de hogar (DF-103/DF-201), resembrar pedidos, rutas y telemetría real
        $badOrders = (int)$pdo->query("SELECT COUNT(*) FROM `pedidos` WHERE `items_json` LIKE '%DF-10%' OR `items_json` LIKE '%DF-20%' OR `ruta_id` LIKE 'RUT-2026-%'")->fetchColumn();
        if ($badOrders > 0) {
            $pdo->exec("TRUNCATE TABLE `pedidos`");
            $pdo->exec("TRUNCATE TABLE `rutas`");
            $pdo->exec("TRUNCATE TABLE `telemetria_eventos`");
            $pdo->prepare("INSERT INTO `configuracion_sitio` (`clave`, `valor`) VALUES ('seq_pedidos', '48') ON DUPLICATE KEY UPDATE `valor` = '48'")->execute();
            seedOrdersIfEmpty($pdo, true);
        }
    } catch (Throwable $e) {
        // No bloquear el arranque si la migración falla
    }
}

/**
 * Inserta los datos iniciales completos en MySQL.
 */
function seedInitialDatabase(PDO $pdo, bool $force = false): void {
    // 1. Configuración del sitio (CMS orientado a Cómputo - Tech Store)
    $configs = [
        ['empresa', 'Tech Store', 'Nombre comercial de la empresa'],
        ['producto', 'Guatemala', 'Nombre de la plataforma operativa'],
        ['lema', 'Tecnología, Cómputo y Observabilidad E-commerce', 'Subtítulo corporativo del sistema'],
        ['hero_eyebrow', 'Cómputo & Hardware de Alto Rendimiento · Despacho en toda Guatemala', 'Texto superior del banner en tienda'],
        ['hero_titulo_1', 'Equipa tu oficina y estación de trabajo', 'Título principal de la tienda'],
        ['hero_titulo_acento', 'con tecnología garantizada', 'Texto resaltado en la tienda'],
        ['hero_subtitulo', 'Catálogo en tiempo real sincronizado con bodega central. Laptops, monitores, tarjetas gráficas, procesadores y periféricos con garantía y despacho inmediato.', 'Descripción principal del banner de tienda'],
        ['perk_1', 'Garantía oficial y factura con IVA incluido', 'Beneficio destacado 1'],
        ['perk_2', 'Envío gratis en compras mayores a Q 500', 'Beneficio destacado 2'],
        ['perk_3', 'Monitoreo y observabilidad en tiempo real con Prometheus', 'Beneficio destacado 3'],
        ['anuncio_activo', '1', 'Mostrar cintillo promocional superior en la tienda (1=Sí, 0=No)'],
        ['anuncio_texto', 'Equipos de cómputo con stock en bodega · Despacho con flota propia en toda Guatemala · Métricas en vivo en Grafana', 'Texto del cintillo promocional'],
        ['iva_porcentaje', '12', 'Porcentaje de IVA aplicable (%)'],
        ['envio_base', '35', 'Costo base de envío sin IVA (Q)'],
        ['envio_gratis_desde', '500', 'Monto mínimo para envío gratuito (Q)'],
        ['color_primario', '#2563eb', 'Color acento principal del portal'],
        ['telefono_soporte', '+502 2290-4000', 'Teléfono de contacto y atención al cliente'],
        ['correo_soporte', 'contacto@techstore.com.gt', 'Correo electrónico de soporte'],
        ['pie_pagina', 'Tech Store Guatemala · E-commerce & Hardware Solutions — Grupo #3 Telemática', 'Texto de derechos reservados en el pie de página'],
        ['seq_pedidos', '48', 'Secuencia correlativa de pedidos'],
    ];
    $stmtCfg = $pdo->prepare("INSERT INTO `configuracion_sitio` (`clave`, `valor`, `descripcion`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `descripcion` = VALUES(`descripcion`)");
    foreach ($configs as $c) {
        $stmtCfg->execute($c);
    }

    // 2. Categorías tecnológicas
    $categorias = [
        ['Laptops y Portátiles', '#3b82f6', '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>', 1],
        ['Monitores y Pantallas', '#06b6d4', '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>', 2],
        ['Componentes y Hardware', '#8b5cf6', '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>', 3],
        ['Periféricos y Accesorios', '#f97316', '<rect x="2" y="4" width="20" height="12" rx="2"/><line x1="6" y1="8" x2="6.01" y2="8"/><line x1="10" y1="8" x2="10.01" y2="8"/><line x1="14" y1="8" x2="14.01" y2="8"/><line x1="18" y1="8" x2="18.01" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><path d="M12 16v4"/>', 4],
        ['Redes y Servidores', '#10b981', '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>', 5],
    ];
    $stmtCat = $pdo->prepare("INSERT INTO `categorias` (`nombre`, `color`, `icono`, `orden`, `activo`) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE `color`=VALUES(`color`), `icono`=VALUES(`icono`), `orden`=VALUES(`orden`)");
    foreach ($categorias as $cat) {
        $stmtCat->execute($cat);
    }

    // 3. Catálogo de 28 Productos de Cómputo Reales
    $productos = [
        ['DF-LAP-01', 'Apple MacBook Pro 16" M3 Pro (36GB RAM, 512GB SSD, Space Black)', 'Chip Apple M3 Pro con CPU de 12 núcleos y GPU de 18 núcleos, 36 GB de memoria unificada, pantalla Liquid Retina XDR de 16.2 pulgadas con ProMotion 120Hz, 3 puertos Thunderbolt 4, HDMI y hasta 22 horas de autonomía.', 'Laptops y Portátiles', 23499.00, 14, 4, 2.15, 0.008, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-LAP-02', 'ASUS ROG Zephyrus G16 OLED (Ultra 9 185H, RTX 4080, 32GB DDR5, 1TB)', 'Laptop gaming ultradelgada de alta gama con procesador Intel Core Ultra 9 185H, NVIDIA GeForce RTX 4080 de 12GB GDDR6, 32GB LPDDR5X, pantalla ROG Nebula OLED 2.5K a 240Hz y chasis mecanizado de aluminio CNC.', 'Laptops y Portátiles', 19850.00, 8, 3, 1.95, 0.007, 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-LAP-03', 'Lenovo ThinkPad X1 Carbon Gen 12 (Ultra 7 155H, 32GB RAM, 1TB SSD)', 'Portátil empresarial insignia en fibra de carbono ultraligera. Intel Core Ultra 7 155H con aceleración por NPU para IA, 32GB LPDDR5X, pantalla 14" 2.8K OLED antirreflejo y certificación militar MIL-STD-810H.', 'Laptops y Portátiles', 16200.00, 18, 5, 1.09, 0.005, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-LAP-04', 'Dell XPS 14 9440 (Intel Core Ultra 7, RTX 4050, 16GB RAM, 1TB SSD)', 'Diseño minimalista premium en aluminio mecanizado con touchpad de cristal háptico continuo, pantalla táctil InfinityEdge 3.2K OLED de 120Hz y tarjeta gráfica dedicada NVIDIA GeForce RTX 4050 de 6GB.', 'Laptops y Portátiles', 14750.00, 3, 5, 1.68, 0.006, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-LAP-05', 'HP Victus Gaming 15.6" (Ryzen 5 8645HS, RTX 4050, 16GB, 512GB)', 'Rendimiento gamer balanceado con procesador AMD Zen 4 de 6 núcleos y 12 hilos, gráficos NVIDIA GeForce RTX 4050 con DLSS 3, 16GB de memoria DDR5 a 5600MHz y panel IPS Full HD de 144Hz con microbordes.', 'Laptops y Portátiles', 6890.00, 26, 8, 2.29, 0.009, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-LAP-06', 'Acer Swift Go 14 OLED (Core Ultra 5 125H, 16GB RAM, 512GB SSD)', 'Movilidad extrema con panel OLED 2.8K 90Hz certificado VESA DisplayHDR True Black 500, procesador con motor Intel AI Boost, cámara web QHD 1440p con reducción de ruido y chasis delgado de 14.9 mm.', 'Laptops y Portátiles', 5450.00, 0, 6, 1.32, 0.005, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80', 0, 1],

        ['DF-MON-01', 'Samsung Odyssey Neo G9 49" Curvo Dual QHD (240Hz, Mini-LED, 1ms)', 'Monitor curvo panorámico 32:9 de 49 pulgadas equivalente a dos monitores de 27", curvatura 1000R inmersiva, tecnología Quantum Matrix Mini-LED con 2,048 zonas de atenuación local y HDR2000.', 'Monitores y Pantallas', 13999.00, 4, 2, 14.50, 0.145, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-MON-02', 'LG UltraGear 34" Curvo WQHD 34GP83A-B (Nano IPS, 160Hz, G-Sync)', 'Resolución 3440x1440 con panel Nano IPS de 1ms de respuesta nativo (GtG), cobertura 98% DCI-P3, frecuencia overclockeable a 160Hz, HDR400 y compatibilidad oficial certificada con NVIDIA G-Sync.', 'Monitores y Pantallas', 6450.00, 11, 4, 7.60, 0.068, 'https://images.unsplash.com/photo-1585792180666-f75a7c527e74?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-MON-03', 'Dell UltraSharp 27" 4K USB-C Hub U2723QE (IPS Black, 90W PD)', 'Monitor profesional 4K UHD 3840x2160 con tecnología IPS Black con contraste 2000:1, cobertura 98% DCI-P3, hub USB-C integrado con suministro de energía de 90W, RJ45 Ethernet y conmutador KVM.', 'Monitores y Pantallas', 4890.00, 16, 5, 6.64, 0.042, 'https://images.unsplash.com/photo-1547119957-637f8679db1e?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-MON-04', 'ASUS TUF Gaming 27" QHD VG27AQ3A (Fast IPS, 180Hz, 1ms, ELMB)', 'Panel Fast IPS de 2560x1440 con frecuencia de refresco de 180Hz, 1ms MPRT, tecnología Extreme Low Motion Blur Sync, 130% sRGB y soporte HDR10 para partidas competitivas ultra fluidas.', 'Monitores y Pantallas', 2450.00, 22, 6, 4.80, 0.035, 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-MON-05', 'ViewSonic ColorPro 32" 4K VP3268a-4K (Pantone Validated, 100% sRGB)', 'Monitor profesional de diseño gráfico y corrección de color calibrado de fábrica con Delta E < 2, tabla 3D LUT de 14 bits, uniformidad de panel del 95% y puerto USB-C con 90W de carga.', 'Monitores y Pantallas', 5900.00, 0, 3, 10.30, 0.075, 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=800&q=80', 0, 1],

        ['DF-CMP-01', 'ASUS TUF Gaming GeForce RTX 4080 Super 16GB GDDR6X', 'GPU con arquitectura Ada Lovelace, 10,240 núcleos CUDA, DLSS 3.5 con Ray Reconstruction, rodamientos de ventilador de doble bola y disipador de 3.65 ranuras con condensadores militares de larga vida.', 'Componentes y Hardware', 9850.00, 7, 3, 1.97, 0.012, 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-CMP-02', 'MSI Gaming X Slim GeForce RTX 4070 Super 12GB GDDR6X', 'Diseño térmico TRI FROZR 3 con aspas TORX Fan 5.0, base de cobre niquelado, backplate de aluminio ventilado y excelente rendimiento en gaming 1440p con trazado de rayos a altas tasas de cuadros.', 'Componentes y Hardware', 5990.00, 15, 4, 1.25, 0.009, 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-CMP-03', 'AMD Ryzen 7 7800X3D (8 Núcleos / 16 Hilos, 5.0GHz, 104MB Cache)', 'El procesador para gaming más rápido y eficiente con tecnología 3D V-Cache de segunda generación, zócalo AM5 compatible con DDR5 y PCIe 5.0, arquitectura Zen 4 de 5nm y TDP contenido de 120W.', 'Componentes y Hardware', 3850.00, 24, 6, 0.35, 0.002, 'https://images.unsplash.com/photo-1555680202-c86f0e12f086?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-CMP-04', 'Intel Core i7-14700K (20 Núcleos / 28 Hilos, hasta 5.6GHz, LGA1700)', 'Procesador de 14ª generación con 8 Performance-cores y 12 Efficient-cores, 33MB Intel Smart Cache, compatibilidad con placas base Z790/B760 y soporte para memoria DDR5 y carriles PCIe 5.0.', 'Componentes y Hardware', 3690.00, 19, 5, 0.32, 0.002, 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-CMP-05', 'Corsair Dominator Titanium RGB DDR5 32GB (2x16GB) 6000MHz CL30', 'Kit de memoria RAM de alto rendimiento con chips seleccionados a mano, iluminación RGB personalizable de 11 zonas por módulo, perfiles Intel XMP 3.0 / AMD EXPO y barra superior intercambiable.', 'Componentes y Hardware', 1450.00, 35, 10, 0.28, 0.001, 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-CMP-06', 'SSD NVMe M.2 Samsung 990 Pro 2TB PCIe 4.0 con Disipador', 'Almacenamiento sólido con velocidades de lectura secuencial de hasta 7,450 MB/s y escritura de 6,900 MB/s, disipador de calor integrado de bajo perfil compatible con PlayStation 5 y computadoras de escritorio.', 'Componentes y Hardware', 1650.00, 42, 12, 0.15, 0.001, 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-CMP-07', 'Fuente Corsair RM850x Shift 850W 80+ Gold Modular ATX 3.0', 'Fuente modular de última generación con panel lateral de conectores de fácil acceso, certificación Cybenetics Gold, condensadores 100% japoneses y conector nativo PCIe 5.0 12VHPWR para tarjetas serie RTX 40.', 'Componentes y Hardware', 1350.00, 2, 6, 2.80, 0.010, 'https://images.unsplash.com/photo-1587202372616-b43abea06c2a?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-CMP-08', 'NVIDIA GeForce RTX 4090 Founders Edition 24GB GDDR6X', 'La tarjeta gráfica más potente para creadores, render 3D y modelos de IA, con 16,384 núcleos CUDA, 24 GB de memoria de video a 21 Gbps, 4ª generación de núcleos Tensor y sistema de enfriamiento de doble flujo.', 'Componentes y Hardware', 18900.00, 0, 2, 2.20, 0.015, 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80', 0, 1],

        ['DF-PRF-01', 'Teclado Mecánico Custom Keychron Q1 Pro Wireless QMK/VIA', 'Chasis de aluminio 6063 mecanizado por CNC, factor de forma 75% con perilla rotatoria multimedia programable, switches mecánicos lubricados de fábrica, montura doble de junta y conectividad Bluetooth / USB-C.', 'Periféricos y Accesorios', 1750.00, 18, 5, 1.75, 0.006, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-PRF-02', 'Mouse Inalámbrico Logitech G Pro X Superlight 2 (60g, 32K HERO 2)', 'Mouse ultraligero preferido en esports profesionales con peso de 60 gramos, interruptores híbridos óptico-mecánicos LIGHTFORCE, sensor HERO 2 de 32,000 DPI con tasa de sondeo inalámbrico de 4000Hz.', 'Periféricos y Accesorios', 1190.00, 28, 8, 0.25, 0.002, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-PRF-03', 'Headset Gamer HyperX Cloud III Wireless (120h Batería, DTS:X)', 'Audífonos inalámbricos de confort prolongado con almohadillas viscoelásticas, transductores angulados de 53 mm rediseñados, audio espacial DTS Headphone:X de por vida y micrófono de 10 mm con filtro antipop.', 'Periféricos y Accesorios', 1250.00, 21, 6, 0.72, 0.005, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-PRF-04', 'Micrófono Profesional Shure MV7+ Podcast XLR / USB-C con DSP', 'Micrófono dinámico con tecnología de aislamiento por voz de Shure, panel táctil LED personalizable, procesador DSP con reductor de ruido en tiempo real, filtro Digital Popper Stopper y salida de audífonos.', 'Periféricos y Accesorios', 2490.00, 9, 4, 0.95, 0.004, 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-PRF-05', 'Cámara Web Elgato Facecam Pro 4K60 UHD True Color', 'Primera webcam con transmisión 4K a 60 fps sin compresión, óptica de 21 mm con apertura f/2.0 y enfoque automático, sensor Sony STARVIS de gran formato y memoria interna para almacenar perfiles de imagen.', 'Periféricos y Accesorios', 2650.00, 6, 3, 0.45, 0.003, 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=800&q=80', 0, 1],
        ['DF-PRF-06', 'Silla Ergonómica Herman Miller Aeron Remastered Talla B', 'Silla ergonómica de oficina con malla Pellicle 8Z que elimina puntos de presión corporal, soporte lumbar PostureFit SL ajustable para columna sacra y mecanismos reclinables de precisión equilibrada.', 'Periféricos y Accesorios', 11500.00, 0, 2, 19.50, 0.280, 'https://images.unsplash.com/photo-1580481077195-c277724a737a?auto=format&fit=crop&w=800&q=80', 0, 1],

        ['DF-NET-01', 'Router Gaming ASUS ROG Rapture GT-AX11000 Pro WiFi 6 Tri-Banda', 'Router tribanda WiFi 6 de alto rendimiento con velocidades combinadas de hasta 11,000 Mbps, doble puerto multigigabit de 10G y 2.5G, procesador quad-core de 64 bits a 2.0GHz y compatibilidad ASUS AiMesh.', 'Redes y Servidores', 3450.00, 13, 4, 2.10, 0.014, 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-NET-02', 'Servidor NAS Synology DiskStation DS224+ 2-Bahías Gigabit', 'Servidor de almacenamiento en red para copias de seguridad centralizadas y nube privada empresarial. Procesador Intel Celeron J4125 de 4 núcleos, 2 puertos LAN GbE con tolerancia a fallos y sistema DSM 7.2.', 'Redes y Servidores', 3290.00, 8, 3, 2.20, 0.012, 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=800&q=80', 1, 1],
        ['DF-NET-03', 'Switch Gestionable Ubiquiti UniFi Pro Max 16 PoE (2.5GbE)', 'Switch de nivel empresarial capa 3 con 16 puertos (4 puertos 2.5GbE PoE++, 12 puertos 1GbE PoE+), 2 enlaces ascendentes SFP+ 10G, presupuesto PoE total de 180W e iluminación Etherlighting para puertos.', 'Redes y Servidores', 3850.00, 2, 4, 3.10, 0.016, 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80', 0, 1],
    ];

    $stmtProd = $pdo->prepare("
        INSERT INTO `productos` (`sku`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `stock_minimo`, `kg`, `m3`, `imagen`, `destacado`, `activo`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `nombre`=VALUES(`nombre`), `descripcion`=VALUES(`descripcion`), `categoria`=VALUES(`categoria`),
            `precio`=VALUES(`precio`), `stock`=VALUES(`stock`), `stock_minimo`=VALUES(`stock_minimo`),
            `kg`=VALUES(`kg`), `m3`=VALUES(`m3`), `imagen`=VALUES(`imagen`), `destacado`=VALUES(`destacado`), `activo`=VALUES(`activo`)
    ");
    foreach ($productos as $p) {
        $stmtProd->execute($p);
    }

    // 4. Roles
    $allPerms = [
        'admin.usuarios.ver', 'admin.usuarios.crear', 'admin.usuarios.editar', 'admin.usuarios.eliminar', 'admin.usuarios.password',
        'admin.roles.ver', 'admin.roles.crear', 'admin.roles.editar', 'admin.roles.eliminar', 'admin.bitacora.ver', 'admin.sistema.reset',
        'cc.pedidos.ver', 'cc.pedidos.crear', 'cc.pedidos.editar', 'cc.pedidos.descuento', 'cc.pedidos.cancelar', 'cc.pedidos.exportar',
        'cc.clientes.crear', 'cc.direcciones.editar', 'cc.web.aprobar',
        'bd.cargas.ver', 'bd.cargas.confirmar', 'bd.picking.ver', 'bd.picking.ejecutar', 'bd.inventario.ver', 'bd.inventario.ajustar', 'bd.inventario.exportar',
        'log.plan.ver', 'log.plan.asignar', 'log.rutas.ver', 'log.rutas.crear', 'log.rutas.despachar', 'log.rutas.entregar', 'log.flota.ver', 'log.flota.gestionar', 'log.exportar',
        'cm.tablero.ver', 'cm.metas.editar', 'cm.ventas.ver', 'cm.operacion.ver', 'cm.exportar'
    ];
    $roles = [
        [
            'Administrador', 'Administrador', 'Control total del sistema, catálogo, página web, usuarios, roles y módulos.', 1,
            ['admin', 'contact_center', 'bodega', 'logistica', 'comercial'],
            $allPerms
        ],
        [
            'Contact Center', 'Contact Center', 'Atención al cliente, captura de pedidos, direcciones y aprobación de pedidos web.', 1,
            ['contact_center'],
            ['cc.pedidos.ver', 'cc.pedidos.crear', 'cc.pedidos.editar', 'cc.pedidos.descuento', 'cc.pedidos.cancelar', 'cc.pedidos.exportar', 'cc.clientes.crear', 'cc.direcciones.editar', 'cc.web.aprobar']
        ],
        [
            'Bodega', 'Bodega', 'Recepción de cargas, preparación de pedidos (picking y empaque) y control de inventario.', 1,
            ['bodega'],
            ['bd.cargas.ver', 'bd.cargas.confirmar', 'bd.picking.ver', 'bd.picking.ejecutar', 'bd.inventario.ver', 'bd.inventario.ajustar', 'bd.inventario.exportar']
        ],
        [
            'Logística', 'Logística', 'Planificación de cargas, asignación de rutas de reparto, entregas y control de flota.', 1,
            ['logistica'],
            ['log.plan.ver', 'log.plan.asignar', 'log.rutas.ver', 'log.rutas.crear', 'log.rutas.despachar', 'log.rutas.entregar', 'log.flota.ver', 'log.flota.gestionar', 'log.exportar']
        ],
        [
            'Comercial', 'Comercial', 'Seguimiento de ventas, clientes, metas e indicadores clave del negocio (KPIs).', 1,
            ['comercial'],
            ['cm.tablero.ver', 'cm.metas.editar', 'cm.ventas.ver', 'cm.operacion.ver', 'cm.exportar']
        ],
    ];
    $stmtRol = $pdo->prepare("INSERT IGNORE INTO `roles` (`id`, `nombre`, `descripcion`, `base`, `modulos_json`, `permisos_json`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($roles as $r) {
        $stmtRol->execute([$r[0], $r[1], $r[2], $r[3], json_encode($r[4], JSON_UNESCAPED_UNICODE), json_encode($r[5], JSON_UNESCAPED_UNICODE)]);
    }

    // 5. Usuarios (Cuentas operativas base + integrantes Grupo #3 con credenciales oficiales)
    $usuarios = [
        ['df_admin', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Valeria Morales', 'vmorales@digyfenix.com', 'Administrador', 1, 1],
        ['df_contact', 'd0ec30a68c45c786c252bd384fea800debd4e0b25ee752670ad8bcddee2d7b2d', 'Diego Herrera', 'dherrera@digyfenix.com', 'Contact Center', 1, 1],
        ['df_bodega', '0ba7383ea34b483e49c019270543f32d3dbca09795c24050e8a746b70f0e5644', 'Marco Tulio Reyes', 'mreyes@digyfenix.com', 'Bodega', 1, 1],
        ['df_logistica', 'dbfadc6cec5de2fa4c566dd1f909d1577be428a3f0f8f85b2d498fd797a0027e', 'Sofía Castellanos', 'scastellanos@digyfenix.com', 'Logística', 1, 1],
        ['df_comercial', '898ac5b60209a642ad45c3b553c5401800acf775e4a992d289f26f1b416675dd', 'Andrés Solórzano', 'asolorzano@digyfenix.com', 'Comercial', 1, 1],
        // Integrantes Grupo #3 (Dev1@Grupo3 .. Qa4@Grupo3)
        ['carlos.lopez', '104937774c7abf0d900bd80332f1e3815b9a052c4b00fab4d271263bb2423689', 'Carlos Daniel López (Grupo #3)', 'clopez@telematica.edu.gt', 'Administrador', 1, 1],
        ['victor.castillo', 'b6d5af78d1361603dfb57e5782d36ebeda101619f6e06edcdd041bb00b5092a1', 'Victor Javier Castillo (Grupo #3)', 'vcastillo@telematica.edu.gt', 'Comercial', 1, 1],
        ['oscar.ortiz', 'dd07d83636335b578ad481994a5fd68a5d9f53b4a0d505cce562cfbc69cd0838', 'Oscar Geovany Ortíz (Grupo #3)', 'oortiz@telematica.edu.gt', 'Logística', 1, 1],
        ['emmanuel.ajsivinac', 'acdf94c530b9e70ba13ec06182a4482eb28d984ae102b20445dab495c9fcec76', 'Emmanuel Alexander Ajsivinac (Grupo #3)', 'eajsivinac@telematica.edu.gt', 'Bodega', 1, 1],
        ['admin', 'a36aef5a11c4073fbe60314fc9df530a9d5f986533594d1f5190742ff9e0e408', 'Administrador General', 'admin@techstore.com.gt', 'Administrador', 1, 1],
    ];
    $stmtUsr = $pdo->prepare("INSERT INTO `usuarios` (`username`, `hash_clave`, `nombre`, `email`, `rol_id`, `activo`, `base`) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `hash_clave` = VALUES(`hash_clave`), `nombre` = VALUES(`nombre`), `email` = VALUES(`email`), `rol_id` = VALUES(`rol_id`), `activo` = 1, `base` = VALUES(`base`)");
    foreach ($usuarios as $u) {
        $stmtUsr->execute($u);
    }

    // 6. Clientes semilla (14 clientes)
    $clientes = [
        ['CLI-0001', 'Ana Lucía Morales Pérez', 'Individual', '4829103-7', '5512-8840', 'ana.morales@gmail.com',
            ['contacto' => 'Ana Lucía Morales Pérez', 'telefono' => '5512-8840', 'departamento' => 'Guatemala', 'municipio' => 'Guatemala', 'zona' => '10', 'direccion' => '6a Avenida 12-45, Edificio Reforma Of. 402', 'referencia' => 'Frente a la parada del Transmetro, recepción en planta baja'],
            ['nombre' => 'Ana Lucía Morales Pérez', 'nit' => '4829103-7', 'direccion' => '6a Avenida 12-45, zona 10, Guatemala', 'email' => 'ana.morales@gmail.com'], '2026-02-14 15:20:00'],
        ['CLI-0002', 'Comercializadora El Quetzal, S.A.', 'Empresa', '7745120-K', '2367-9100', 'compras@elquetzal.com.gt',
            ['contacto' => 'Ing. Roberto Castañeda (Bodega)', 'telefono' => '5489-3301', 'departamento' => 'Guatemala', 'municipio' => 'Villa Nueva', 'zona' => '2', 'direccion' => 'Km 17.5 Carretera al Pacífico, Complejo Industrial Sur, Bodega 8', 'referencia' => 'Garita azul, anunciarse con recepción de mercadería'],
            ['nombre' => 'Comercializadora El Quetzal, S.A.', 'nit' => '7745120-K', 'direccion' => '12 Calle 1-25, Géminis 10 Torre Norte Of. 1104, zona 10, Guatemala', 'email' => 'facturacion@elquetzal.com.gt'], '2026-01-22 10:05:00'],
        ['CLI-0003', 'Juan Carlos Méndez Estrada', 'Individual', '3190482-5', '4218-7765', 'jcmendez@outlook.com',
            ['contacto' => 'Juan Carlos Méndez', 'telefono' => '4218-7765', 'departamento' => 'Guatemala', 'municipio' => 'Mixco', 'zona' => '4', 'direccion' => 'Condominio Bosques de San Nicolás, Casa 34', 'referencia' => 'Portón gris, llamar al llegar a garita'],
            ['nombre' => 'Juan Carlos Méndez Estrada', 'nit' => '3190482-5', 'direccion' => 'Condominio Bosques de San Nicolás, Casa 34, zona 4, Mixco', 'email' => 'jcmendez@outlook.com'], '2026-03-03 17:40:00'],
        ['CLI-0004', 'Ferretería y Suministros La Reforma, S.A.', 'Empresa', '9021845-3', '2251-4400', 'pedidos@ferreteriareforma.gt',
            ['contacto' => 'Héctor Samayoa', 'telefono' => '5890-1122', 'departamento' => 'Guatemala', 'municipio' => 'Guatemala', 'zona' => '9', 'direccion' => '7a Avenida 14-19', 'referencia' => 'Portón metálico lateral sobre la 14 calle'],
            ['nombre' => 'Ferretería y Suministros La Reforma, S.A.', 'nit' => '9021845-3', 'direccion' => '7a Avenida 14-19, zona 9, Guatemala', 'email' => 'contabilidad@ferreteriareforma.gt'], '2026-01-11 09:00:00'],
        ['CLI-0005', 'María Fernanda Cabrera Orantes', 'Individual', '6350219-1', '5701-9934', 'mfcabrera@yahoo.com',
            ['contacto' => 'María Fernanda Cabrera', 'telefono' => '5701-9934', 'departamento' => 'Sacatepéquez', 'municipio' => 'Antigua Guatemala', 'zona' => '', 'direccion' => '4a Calle Oriente N.º 28', 'referencia' => 'Casa amarilla de esquina, portón de madera'],
            ['nombre' => 'María Fernanda Cabrera Orantes', 'nit' => '6350219-1', 'direccion' => '4a Calle Oriente N.º 28, Antigua Guatemala', 'email' => 'mfcabrera@yahoo.com'], '2026-03-19 14:15:00'],
        ['CLI-0006', 'Café y Pastelería Los Altos', 'Empresa', '5510394-8', '7761-2280', 'administracion@cafelosaltos.gt',
            ['contacto' => 'Gabriela Quemé', 'telefono' => '4602-8811', 'departamento' => 'Quetzaltenango', 'municipio' => 'Quetzaltenango', 'zona' => '3', 'direccion' => '14 Avenida 4-32', 'referencia' => 'A media cuadra del Parque Benito Juárez'],
            ['nombre' => 'Inversiones Gastronómicas de Occidente, S.A.', 'nit' => '5510394-8', 'direccion' => '14 Avenida 4-32, zona 3, Quetzaltenango', 'email' => 'facturas@cafelosaltos.gt'], '2026-02-01 11:30:00'],
        ['CLI-0007', 'Luis Pedro Alvarado Girón', 'Individual', 'CF', '3345-6090', 'lpalvarado@gmail.com',
            ['contacto' => 'Luis Pedro Alvarado', 'telefono' => '3345-6090', 'departamento' => 'Guatemala', 'municipio' => 'Santa Catarina Pinula', 'zona' => '1', 'direccion' => 'Residenciales Las Luces, Km 13.5 Carretera a El Salvador, Lote 19', 'referencia' => 'Garita principal, indicar visita a familia Alvarado'],
            ['nombre' => 'Consumidor Final', 'nit' => 'CF', 'direccion' => 'Ciudad', 'email' => 'lpalvarado@gmail.com'], '2026-04-01 16:00:00'],
        ['CLI-0008', 'Clínica Dental Sonrisa Clara', 'Empresa', '8412760-9', '2334-5520', 'recepcion@sonrisaclara.gt',
            ['contacto' => 'Dra. Claudia Santizo', 'telefono' => '5219-4408', 'departamento' => 'Guatemala', 'municipio' => 'Guatemala', 'zona' => '14', 'direccion' => '5a Avenida 15-45, Centro Empresarial Torre II, Clínica 605', 'referencia' => 'Subir por elevadores pares al sexto nivel'],
            ['nombre' => 'Servicios Odontológicos Santizo, S.A.', 'nit' => '8412760-9', 'direccion' => '5a Avenida 15-45, zona 14, Guatemala', 'email' => 'contabilidad@sonrisaclara.gt'], '2026-02-18 13:10:00'],
        ['CLI-0009', 'Estuardo Javier Pineda Choc', 'Individual', '2984710-4', '4812-3095', 'epineda@gmail.com',
            ['contacto' => 'Estuardo Pineda', 'telefono' => '4812-3095', 'departamento' => 'Escuintla', 'municipio' => 'Escuintla', 'zona' => '1', 'direccion' => '4a Avenida 6-18, Colonia El Recreo', 'referencia' => 'Casa de dos niveles color crema, frente a tienda La Bendición'],
            ['nombre' => 'Estuardo Javier Pineda Choc', 'nit' => '2984710-4', 'direccion' => '4a Avenida 6-18, zona 1, Escuintla', 'email' => 'epineda@gmail.com'], '2026-03-25 18:05:00'],
        ['CLI-0010', 'Hotel Posada del Bosquecito, S.A.', 'Empresa', '6198430-2', '7951-0840', 'compras@posadadelbosquecito.com',
            ['contacto' => 'Erwin Caal (Mantenimiento)', 'telefono' => '5540-7712', 'departamento' => 'Alta Verapaz', 'municipio' => 'Cobán', 'zona' => '2', 'direccion' => '3a Calle 5-90', 'referencia' => 'Portón de servicio al costado del parqueo'],
            ['nombre' => 'Hotel Posada del Bosquecito, S.A.', 'nit' => '6198430-2', 'direccion' => '3a Calle 5-90, zona 2, Cobán, Alta Verapaz', 'email' => 'facturas@posadadelbosquecito.com'], '2026-03-08 09:45:00'],
        ['CLI-0011', 'Paola Beatriz Barrios Rodas', 'Individual', '5478123-6', '3091-2240', 'paola.barrios@gmail.com',
            ['contacto' => 'Paola Barrios', 'telefono' => '3091-2240', 'departamento' => 'Guatemala', 'municipio' => 'Guatemala', 'zona' => '15', 'direccion' => '20 Calle 2-40, Vista Hermosa II, Apartamento 5B', 'referencia' => 'Edificio Terracota, dejar con conserje si no responde'],
            ['nombre' => 'Paola Beatriz Barrios Rodas', 'nit' => '5478123-6', 'direccion' => '20 Calle 2-40, zona 15, Guatemala', 'email' => 'paola.barrios@gmail.com'], '2026-04-04 12:30:00'],
        ['CLI-0012', 'Despacho Contable Orozco & Asociados', 'Empresa', '4401928-1', '2230-1188', 'info@orozcoasociados.gt',
            ['contacto' => 'Licda. Miriam Orozco', 'telefono' => '5110-6674', 'departamento' => 'Guatemala', 'municipio' => 'Guatemala', 'zona' => '4', 'direccion' => 'Ruta 2, 4-63, Edificio Cuatro Grados, Oficina 304', 'referencia' => 'Frente a plazuela, timbre 304'],
            ['nombre' => 'Orozco & Asociados Contadores Públicos, S.A.', 'nit' => '4401928-1', 'direccion' => 'Ruta 2, 4-63, zona 4, Guatemala', 'email' => 'administracion@orozcoasociados.gt'], '2026-02-27 16:50:00'],
        ['CLI-0013', 'Carlos Humberto Xiloj Tzul', 'Individual', '7123904-8', '4509-8821', 'cxiloj@gmail.com',
            ['contacto' => 'Carlos Xiloj', 'telefono' => '4509-8821', 'departamento' => 'Chimaltenango', 'municipio' => 'Chimaltenango', 'zona' => '2', 'direccion' => '2a Calle 7-55', 'referencia' => 'A una cuadra de los Bomberos Voluntarios'],
            ['nombre' => 'Carlos Humberto Xiloj Tzul', 'nit' => '7123904-8', 'direccion' => '2a Calle 7-55, zona 2, Chimaltenango', 'email' => 'cxiloj@gmail.com'], '2026-03-30 15:00:00'],
        ['CLI-0014', 'Colegio Bilingüe Monte Verde', 'Empresa', '3891045-K', '6634-9000', 'compras@colegiomonteverde.edu.gt',
            ['contacto' => 'Prof. Byron Velásquez', 'telefono' => '5923-7014', 'departamento' => 'Guatemala', 'municipio' => 'San Miguel Petapa', 'zona' => '7', 'direccion' => 'Avenida Principal 10-00, Prados de Villa Hermosa', 'referencia' => 'Entrada por portón 2 de proveeduría, horario de 7:30 a 14:30'],
            ['nombre' => 'Centro Educativo Monte Verde, S.A.', 'nit' => '3891045-K', 'direccion' => 'Avenida Principal 10-00, zona 7, San Miguel Petapa', 'email' => 'tesoreria@colegiomonteverde.edu.gt'], '2026-01-29 08:20:00'],
    ];
    $stmtCli = $pdo->prepare("INSERT IGNORE INTO `clientes` (`id`, `nombre`, `tipo`, `nit`, `telefono`, `email`, `entrega_json`, `facturacion_json`, `creado_en`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($clientes as $c) {
        $stmtCli->execute([
            $c[0], $c[1], $c[2], $c[3], $c[4], $c[5],
            json_encode($c[6], JSON_UNESCAPED_UNICODE),
            json_encode($c[7], JSON_UNESCAPED_UNICODE),
            $c[8]
        ]);
    }

    // 7. Cuenta demo de tienda
    $stmtCta = $pdo->prepare("INSERT INTO `cuentas_tienda` (`email`, `cliente_id`, `nombre`, `hash_clave`, `creado_en`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `hash_clave` = VALUES(`hash_clave`)");
    $stmtCta->execute([
        'cliente@demo.com',
        'CLI-0001',
        'Ana Lucía Morales Pérez',
        'c383f8002fb5d3448518471a4527097d458b7e4661e89259bcdc44ed07d1094b', // Cliente@2026
        '2026-02-14 15:20:00'
    ]);

    // 8. Flota de vehículos
    $vehiculos = [
        ['VEH-01', 'C-412BKM', 'Panel', 'Hyundai H-100', 'Ramiro Ajanel', '5514-2201', 1200, 7.5, 'En ruta'],
        ['VEH-02', 'C-839DFR', 'Panel', 'Toyota Hiace Cargo', 'Selvin Orozco', '5623-8910', 1400, 8.2, 'Disponible'],
        ['VEH-03', 'C-205GHT', 'Camión 3.5 t', 'Isuzu NPR', 'Baudilio Pérez', '4712-3309', 3500, 18.0, 'Disponible'],
        ['VEH-04', 'P-671JKL', 'Pick-up', 'Toyota Hilux', 'Cristian Gómez', '5298-4412', 900, 3.8, 'Disponible'],
        ['VEH-05', 'M-918MNP', 'Motocicleta', 'Honda Cargo 150', 'Kevin Xol', '4180-7766', 45, 0.25, 'Disponible'],
        ['VEH-06', 'C-550QRS', 'Camión 3.5 t', 'Hino Serie 300', 'Édgar Tucubal', '5801-6543', 3500, 19.0, 'Mantenimiento'],
    ];
    $stmtVeh = $pdo->prepare("INSERT INTO `vehiculos` (`id`, `placa`, `tipo`, `marca`, `piloto`, `tel`, `cap_kg`, `cap_m3`, `estado`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `placa`=VALUES(`placa`), `tipo`=VALUES(`tipo`), `marca`=VALUES(`marca`), `piloto`=VALUES(`piloto`), `tel`=VALUES(`tel`), `cap_kg`=VALUES(`cap_kg`), `cap_m3`=VALUES(`cap_m3`), `estado`=VALUES(`estado`)");
    foreach ($vehiculos as $v) {
        $stmtVeh->execute($v);
    }

    // 9. Metas comerciales KPI
    $metas = [
        'ingresosMes'   => 65000,
        'pedidosMes'    => 28,
        'ticketMedio'   => 2400,
        'conversionWeb' => 75,
        'otif'          => 85,
        'horasCiclo'    => 36
    ];
    $stmtMetas = $pdo->prepare("INSERT IGNORE INTO `kpi_metas` (`id`, `metas_json`) VALUES (1, ?)");
    $stmtMetas->execute([json_encode($metas, JSON_UNESCAPED_UNICODE)]);

    // 10. Bitácora inicial
    $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, ?, ?)");
    $stmtBit->execute([date('Y-m-d H:i:s'), 'sistema', 'mysql.init', 'Base de datos MySQL digyfenix_pulso inicializada con catálogo de cómputo, clientes, flota, rutas y usuarios.']);

    seedOrdersIfEmpty($pdo, $force);
}

/**
 * Siembra los pedidos, rutas y eventos iniciales de telemetría en MySQL.
 */
function seedOrdersIfEmpty(PDO $pdo, bool $force = false): void {
    try {
        if (!$force) {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `pedidos`")->fetchColumn();
            if ($cnt > 0) {
                seedTelemetryIfEmpty($pdo, false);
                return;
            }
        }
    } catch (Throwable $e) {
        return;
    }

    $cliRows = $pdo->query("SELECT * FROM `clientes` ORDER BY `id` ASC")->fetchAll();
    $prodRows = $pdo->query("SELECT * FROM `productos` ORDER BY `sku` ASC")->fetchAll();
    if (!count($cliRows) || !count($prodRows)) {
        return;
    }
    $prodBySku = [];
    foreach ($prodRows as $pr) {
        $prodBySku[$pr['sku']] = $pr;
    }

    $franjas = ['Mañana (8:00 – 12:00)', 'Tarde (13:00 – 17:00)', 'Día completo (8:00 – 17:00)'];
    $now = time();

    $armar = function(int $num, int $cliIdx, string $estado, float $haceH, int $diasEnt, string $canal, string $pago, string $prio, float $envio, array $defs, string $obs = '', string $notas = '', array $extra = []) use ($cliRows, $prodBySku, $prodRows, $franjas, $now): array {
        $c = $cliRows[$cliIdx % count($cliRows)];
        $items = [];
        $subtotal = 0.0;
        $descuento = 0.0;
        $unidades = 0;
        foreach ($defs as $d) {
            [$sku, $cant, $desc] = [$d[0], (int)$d[1], (float)($d[2] ?? 0)];
            $p = $prodBySku[$sku] ?? $prodRows[0];
            $precio = (float)$p['precio'];
            $bruto = round($precio * $cant, 2);
            $rebaja = round($bruto * ($desc / 100), 2);
            $subtotal = round($subtotal + $bruto, 2);
            $descuento = round($descuento + $rebaja, 2);
            $unidades += $cant;
            $items[] = [
                'sku'       => $p['sku'],
                'nombre'    => $p['nombre'],
                'categoria' => $p['categoria'],
                'precio'    => $precio,
                'cantidad'  => $cant,
                'descuento' => $desc,
            ];
        }
        $base = round($subtotal - $descuento + $envio, 2);
        $iva  = round($base * 0.12, 2);
        $total = round($base + $iva, 2);
        $totales = [
            'lineas'    => count($items),
            'unidades'  => $unidades,
            'subtotal'  => $subtotal,
            'descuento' => $descuento,
            'envio'     => $envio,
            'base'      => $base,
            'iva'       => $iva,
            'total'     => $total,
        ];
        $ts = date('Y-m-d H:i:s', (int)($now - $haceH * 3600));
        return [
            'id'            => sprintf('PED-2026-%04d', $num),
            'estado'        => $estado,
            'fecha'         => $ts,
            'cliente_id'    => $c['id'],
            'cliente_json'  => json_encode(['nombre' => $c['nombre'], 'nit' => $c['nit'], 'telefono' => $c['telefono'], 'email' => $c['email'], 'tipo' => $c['tipo']], JSON_UNESCAPED_UNICODE),
            'entrega_json'  => $c['entrega_json'],
            'facturacion_json' => $c['facturacion_json'],
            'items_json'    => json_encode($items, JSON_UNESCAPED_UNICODE),
            'envio'         => $envio,
            'totales_json'  => json_encode($totales, JSON_UNESCAPED_UNICODE),
            'canal'         => $canal,
            'pago'          => $pago,
            'prioridad'     => $prio,
            'franja'        => $franjas[$num % count($franjas)],
            'fecha_entrega' => date('Y-m-d', (int)($now + $diasEnt * 86400)),
            'obs_cliente'   => $obs,
            'notas_internas'=> $notas,
            'creado_por'    => $canal === 'Tienda web' ? 'Tienda web (autoatención)' : ($num % 2 === 0 ? 'Diego Herrera' : 'Valeria Morales'),
            'actualizado'   => $ts,
            'ruta_id'       => $extra['rutaId'] ?? null,
            'picking_json'  => isset($extra['picking']) ? json_encode($extra['picking'], JSON_UNESCAPED_UNICODE) : null,
            'extra_json'    => count($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
        ];
    };

    $isoOffset = static fn(float $haceH): string => date('c', (int)($now - $haceH * 3600));

    $pedidos = [
        // 4 pedidos web recientes "Por aprobar" (#48..#45): #48 creado hace 12 min (<=60m al aprobar), #45 hace 78 min (>60m)
        $armar(48, 0,  'Por aprobar',           0.20, 1, 'Tienda web', 'Tarjeta (enlace de pago)', 'Alta',    0, [['DF-LAP-03', 1, 0], ['DF-PRF-02', 1, 0]], 'Llamar 15 minutos antes de llegar.', 'Cliente frecuente · pago con tarjeta solicitado en tienda web', ['checkoutRefId' => 'CHK-SEED-0048']),
        $armar(47, 4,  'Por aprobar',           0.47, 2, 'Tienda web', 'Transferencia bancaria',   'Normal',  0, [['DF-MON-04', 2, 0], ['DF-PRF-01', 1, 0]], 'Dejar en recepción si no estoy.', '', ['checkoutRefId' => 'CHK-SEED-0047']),
        $armar(46, 10, 'Por aprobar',           0.75, 1, 'Tienda web', 'Contra entrega',           'Normal',  0, [['DF-CMP-03', 1, 0], ['DF-CMP-05', 2, 0]], 'Entregar por la tarde por favor.', '', ['checkoutRefId' => 'CHK-SEED-0046']),
        $armar(45, 5,  'Por aprobar',           1.30, 3, 'Tienda web', 'Transferencia bancaria',   'Alta',    0, [['DF-LAP-05', 2, 0], ['DF-PRF-03', 2, 0]], 'Empacar bien para transporte a Xela.', 'Solicita confirmación de cuenta bancaria por correo', ['checkoutRefId' => 'CHK-SEED-0045']),

        // Pedidos aprobados pendientes de asignación a ruta (#44, #43, #41, #37)
        $armar(44, 1,  'Pendiente de despacho', 2.2,  1, 'Teléfono',   'Crédito empresarial',      'Urgente', 0, [['DF-LAP-03', 2, 5], ['DF-MON-03', 3, 5], ['DF-CMP-06', 4, 0]], 'Entregar en bodega 8 antes de las 11:00.', 'OC-88412 autorizada por gerencia', ['fechaAprobacion' => $isoOffset(1.83), 'minLiquidacion' => 22.0]),
        $armar(43, 2,  'Pendiente de despacho', 3.5,  1, 'WhatsApp',   'Contra entrega',           'Normal',  0, [['DF-CMP-02', 1, 0], ['DF-CMP-07', 1, 0]], 'Avisar en garita.', '', ['fechaAprobacion' => $isoOffset(3.20), 'minLiquidacion' => 18.0]),
        $armar(42, 3,  'En preparación',        5.0,  1, 'Teléfono',   'Crédito empresarial',      'Alta',    0, [['DF-CMP-03', 2, 5], ['DF-CMP-05', 2, 5]], 'Recibe Héctor Samayoa en portón lateral.', 'Cliente mayorista', ['rutaId' => 'RUT-0003', 'picking' => ['DF-CMP-03' => 2, 'DF-CMP-05' => 1], 'fechaAprobacion' => $isoOffset(4.58), 'minLiquidacion' => 25.0]),
        $armar(41, 7,  'Pendiente de despacho', 7.0,  2, 'Correo',     'Transferencia bancaria',   'Normal',  0, [['DF-MON-04', 2, 0], ['DF-PRF-04', 1, 0]], 'Subir al sexto nivel por elevadores pares.', 'Transferencia verificada #994102', ['fechaAprobacion' => $isoOffset(6.43), 'minLiquidacion' => 34.0]),
        $armar(40, 6,  'Borrador',              9.5,  3, 'WhatsApp',   'Contra entrega',           'Normal', 35, [['DF-PRF-02', 1, 0]], '', 'Pendiente que el cliente confirme si agrega teclado mecánico'),
        $armar(39, 13, 'En preparación',        12.0, 1, 'Correo',     'Crédito empresarial',      'Alta',    0, [['DF-MON-03', 2, 5], ['DF-PRF-01', 2, 5], ['DF-NET-01', 1, 0]], 'Entregar en proveeduría antes de las 14:00.', 'Contrato anual vigente', ['rutaId' => 'RUT-0004', 'picking' => ['DF-MON-03' => 2, 'DF-PRF-01' => 2, 'DF-NET-01' => 1], 'fechaAprobacion' => $isoOffset(11.32), 'minLiquidacion' => 41.0]),

        // Pedidos despachados en ruta activa RUT-0001 (#38, #36)
        $armar(38, 11, 'En ruta',               15.0, 0, 'Teléfono',   'Transferencia bancaria',   'Normal',  0, [['DF-MON-02', 1, 0], ['DF-PRF-05', 2, 0]], 'Timbre 304.', 'Ruta metropolitana mañana', ['rutaId' => 'RUT-0001', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(14.53), 'minLiquidacion' => 28.0, 'minEmpaque' => 19.0, 'fechaEnvio' => $isoOffset(11.90), 'horasDespacho' => 3.10, 'enPlazo' => true]),
        $armar(37, 8,  'Pendiente de despacho', 18.0, 2, 'WhatsApp',   'Contra entrega',           'Normal',  0, [['DF-LAP-05', 1, 0], ['DF-PRF-02', 2, 0]], '', '', ['fechaAprobacion' => $isoOffset(17.42), 'minLiquidacion' => 35.0]),
        $armar(36, 9,  'En ruta',               21.5, 0, 'Teléfono',   'Transferencia bancaria',   'Alta',    0, [['DF-NET-02', 2, 5], ['DF-CMP-06', 4, 5], ['DF-NET-03', 1, 0]], 'Portón de servicio al costado del parqueo.', 'Envío departamental norte', ['rutaId' => 'RUT-0001', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(20.70), 'minLiquidacion' => 48.0, 'minEmpaque' => 26.0, 'fechaEnvio' => $isoOffset(17.65), 'horasDespacho' => 3.85, 'enPlazo' => true]),

        // Pedidos recientes entregados en RUT-0002 (#35, #34, #33, #31) y cancelado (#32)
        $armar(35, 0,  'Entregado',             36.0, -1, 'Tienda web', 'Tarjeta (enlace de pago)', 'Normal', 0, [['DF-LAP-01', 1, 0], ['DF-CMP-06', 1, 0]], 'Entregar en recepción.', '', ['rutaId' => 'RUT-0002', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(35.58), 'minLiquidacion' => 25.0, 'minEmpaque' => 22.0, 'fechaEnvio' => $isoOffset(33.10), 'horasDespacho' => 2.90, 'enPlazo' => true, 'entregadoEn' => $isoOffset(26.0), 'recibidoPor' => 'Recepción Edificio Reforma', 'aTiempo' => true]),
        $armar(34, 4,  'Entregado',             54.0, -2, 'WhatsApp',   'Contra entrega',           'Normal', 0, [['DF-LAP-04', 1, 0]], '', '', ['rutaId' => 'RUT-0002', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(53.40), 'minLiquidacion' => 36.0, 'minEmpaque' => 24.0, 'fechaEnvio' => $isoOffset(50.50), 'horasDespacho' => 3.50, 'enPlazo' => true, 'entregadoEn' => $isoOffset(44.0), 'recibidoPor' => 'María Fernanda Cabrera', 'aTiempo' => true]),
        $armar(33, 5,  'Entregado',             78.0, -3, 'Teléfono',   'Transferencia bancaria',   'Normal', 0, [['DF-CMP-01', 1, 0], ['DF-CMP-04', 1, 0], ['DF-CMP-05', 2, 5]], '', '', ['rutaId' => 'RUT-0002', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(77.30), 'minLiquidacion' => 42.0, 'minEmpaque' => 31.0, 'fechaEnvio' => $isoOffset(73.80), 'horasDespacho' => 4.20, 'enPlazo' => true, 'entregadoEn' => $isoOffset(62.0), 'recibidoPor' => 'Gabriela Quemé', 'aTiempo' => true]),
        $armar(32, 12, 'Cancelado',             96.0, -3, 'Teléfono',   'Contra entrega',           'Normal', 0, [['DF-LAP-02', 1, 0]], '', 'Cliente solicitó cancelar por cambio de presupuesto'),
        $armar(31, 1,  'Entregado',            118.0, -4, 'Correo',     'Crédito empresarial',      'Normal', 0, [['DF-MON-01', 1, 5], ['DF-NET-01', 2, 10]], 'Entregar en bodega 8.', 'Proyecto ampliación oficinas', ['rutaId' => 'RUT-0002', 'empacadoPor' => 'Marco Tulio Reyes', 'fechaAprobacion' => $isoOffset(117.50), 'minLiquidacion' => 30.0, 'minEmpaque' => 29.0, 'fechaEnvio' => $isoOffset(114.20), 'horasDespacho' => 3.80, 'enPlazo' => true, 'entregadoEn' => $isoOffset(101.0), 'recibidoPor' => 'Ing. Roberto Castañeda', 'aTiempo' => true]),
    ];

    $canalesHist = ['Teléfono', 'WhatsApp', 'Tienda web', 'Correo', 'Mostrador'];
    $pagosHist   = ['Contra entrega', 'Transferencia bancaria', 'Tarjeta (enlace de pago)', 'Crédito empresarial'];
    // Solo SKUs con stock disponible para pedidos históricos realistas
    $skusActivos = array_values(array_filter(array_keys($prodBySku), static fn($k) => !in_array($k, ['DF-LAP-06', 'DF-MON-05', 'DF-CMP-08', 'DF-PRF-06'], true)));
    for ($n = 30; $n >= 1; $n--) {
        $cliIdx = ($n * 3 + 1) % count($cliRows);
        $c = $cliRows[$cliIdx];
        $entC = json_decode($c['entrega_json'], true) ?: [];
        $haceH = 140 + (30 - $n) * 32;
        $canal = $canalesHist[$n % count($canalesHist)];
        $pago = ($c['tipo'] === 'Empresa' && $n % 2 === 0) ? 'Crédito empresarial' : $pagosHist[$n % 3];
        $estado = ($n === 19 || $n === 7) ? 'Cancelado' : 'Entregado';
        $s1 = $skusActivos[($n * 2) % count($skusActivos)];
        $s2 = $skusActivos[($n * 5 + 3) % count($skusActivos)];
        $cant1 = ($n % 2) + 1;
        $cant2 = (($n + 1) % 2) + 1;
        $defs = ($s1 === $s2) ? [[$s1, $cant1 + 1, 0]] : [[$s1, $cant1, $n % 4 === 0 ? 5 : 0], [$s2, $cant2, 0]];
        $envio = (($entC['departamento'] ?? 'Guatemala') === 'Guatemala') ? 0 : 55;
        $extra = [];
        if ($estado === 'Entregado') {
            $tardeLiq = ($n === 14 || $n === 28);
            $minLiq = $tardeLiq ? (float)(74 + ($n % 9)) : (float)(18 + ($n % 35));
            $minEmp = (float)(20 + ($n % 16));
            $tardeDesp = ($n === 14 || $n === 28);
            $horasDesp = $tardeDesp ? round(9.2 + ($n % 2), 2) : round(2.1 + (($n % 7) * 0.45), 2);
            $aTiempo = !$tardeDesp && ($n % 7 !== 0);
            $entH = $haceH - ($aTiempo ? 22 : 52);
            $extra = [
                'empacadoPor'    => 'Marco Tulio Reyes',
                'fechaAprobacion'=> $isoOffset(max(1.0, $haceH - ($minLiq / 60))),
                'minLiquidacion' => $minLiq,
                'minEmpaque'     => $minEmp,
                'fechaEnvio'     => $isoOffset(max(1.0, $haceH - $horasDesp)),
                'horasDespacho'  => $horasDesp,
                'enPlazo'        => !$tardeDesp,
                'entregadoEn'    => $isoOffset(max(1.0, $entH)),
                'recibidoPor'    => $entC['contacto'] ?? $c['nombre'],
                'aTiempo'        => $aTiempo,
            ];
        }
        $pedidos[] = $armar($n, $cliIdx, $estado, $haceH, (int)(-ceil($haceH / 24) + 1), $canal, $pago, $n % 6 === 0 ? 'Alta' : 'Normal', $envio, $defs, '', '', $extra);
    }

    $stmtPed = $pdo->prepare("
        INSERT IGNORE INTO `pedidos` (
            `id`, `estado`, `fecha`, `cliente_id`, `cliente_json`, `entrega_json`, `facturacion_json`,
            `items_json`, `envio`, `totales_json`, `canal`, `pago`, `prioridad`, `franja`,
            `fecha_entrega`, `obs_cliente`, `notas_internas`, `creado_por`, `actualizado`,
            `ruta_id`, `picking_json`, `extra_json`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($pedidos as $p) {
        $stmtPed->execute(array_values($p));
    }

    // Sembrar rutas iniciales reales vinculadas a los pedidos
    $hoyStr = date('Y-m-d', $now);
    $ayerStr = date('Y-m-d', $now - 86400);
    $rutasSeed = [
        [
            'RUT-0001', 'RUT-0001', 'Metropolitana Central & Norte', 'Guatemala Z4 / Alta Verapaz',
            $hoyStr, 'Mañana (8:00 – 12:00)', 'VEH-01', 'Ramiro Ajanel', '5514-2201', 'Despachada',
            json_encode(['PED-2026-0038', 'PED-2026-0036']),
            'Unidad cargada y despachada en tránsito.', 'Sofía Castellanos',
            date('Y-m-d H:i:s', (int)($now - 18 * 3600)),
            json_encode([
                'despachadaEn' => $isoOffset(11.9),
                'carga' => [
                    'estado' => 'Cargada',
                    'bultos' => 7,
                    'picks' => ['DF-NET-02' => 2, 'DF-CMP-06' => 4, 'DF-NET-03' => 1, 'DF-MON-02' => 1, 'DF-PRF-05' => 2],
                    'cargados' => ['PED-2026-0036', 'PED-2026-0038'],
                    'confirmadaPor' => 'Marco Tulio Reyes',
                    'inicio' => $isoOffset(12.35),
                    'fin' => $isoOffset(11.98),
                    'notas' => 'Verificado por serie',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ],
        [
            'RUT-0002', 'RUT-0002', 'Corredor Reforma & Occidente', 'Guatemala Z10 / Sacatepéquez / Xela',
            $ayerStr, 'Día completo (8:00 – 17:00)', 'VEH-03', 'Baudilio Pérez', '4712-3309', 'Entregada',
            json_encode(['PED-2026-0035', 'PED-2026-0034', 'PED-2026-0033', 'PED-2026-0031']),
            'Ruta histórica completada satisfactoriamente.', 'Sofía Castellanos',
            date('Y-m-d H:i:s', (int)($now - 72 * 3600)),
            json_encode([
                'despachadaEn' => $isoOffset(50.5),
                'entregadaEn' => $isoOffset(26.0),
                'carga' => [
                    'estado' => 'Cargada',
                    'bultos' => 11,
                    'picks' => ['DF-MON-01' => 1, 'DF-NET-01' => 2, 'DF-CMP-01' => 1, 'DF-CMP-04' => 1, 'DF-CMP-05' => 2, 'DF-LAP-04' => 1, 'DF-LAP-01' => 1, 'DF-CMP-06' => 1],
                    'cargados' => ['PED-2026-0031', 'PED-2026-0033', 'PED-2026-0034', 'PED-2026-0035'],
                    'confirmadaPor' => 'Marco Tulio Reyes',
                    'inicio' => $isoOffset(51.0),
                    'fin' => $isoOffset(50.58),
                    'notas' => 'Entrega completada',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ],
        [
            'RUT-0003', 'RUT-0003', 'Zona Industrial Sur & Petapa', 'Guatemala Z9 / Villa Nueva',
            $hoyStr, 'Tarde (13:00 – 17:00)', 'VEH-04', 'Cristian Gómez', '5298-4412', 'Planificada',
            json_encode(['PED-2026-0042']),
            'En estación de picking para despacho de tarde.', 'Sofía Castellanos',
            date('Y-m-d H:i:s', (int)($now - 4.0 * 3600)),
            json_encode([
                'carga' => [
                    'estado' => 'En picking',
                    'bultos' => 2,
                    'picks' => ['DF-CMP-03' => 2],
                    'cargados' => [],
                    'inicio' => $isoOffset(0.30),
                ],
            ], JSON_UNESCAPED_UNICODE),
        ],
        [
            'RUT-0004', 'RUT-0004', 'Ruta Institucional Sur', 'San Miguel Petapa Z7',
            $hoyStr, 'Mañana (8:00 – 12:00)', 'VEH-02', 'Selvin Orozco', '5623-8910', 'Planificada',
            json_encode(['PED-2026-0039']),
            'Pendiente confirmación de carga en bodega.', 'Sofía Castellanos',
            date('Y-m-d H:i:s', (int)($now - 2.5 * 3600)),
            json_encode([
                'carga' => [
                    'estado' => 'Pendiente',
                    'bultos' => 3,
                    'picks' => new stdClass(),
                    'cargados' => [],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ],
    ];
    $stmtRut = $pdo->prepare("
        INSERT IGNORE INTO `rutas` (`id`, `codigo`, `nombre`, `zona`, `fecha`, `franja`, `vehiculo_id`, `piloto`, `tel_piloto`, `estado`, `pedidos_json`, `notas`, `creada_por`, `creada_en`, `extra_json`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($rutasSeed as $rs) {
        $stmtRut->execute($rs);
    }

    seedTelemetryIfEmpty($pdo, $force);
}

/**
 * Obtiene el diccionario de configuración del sitio desde MySQL.
 */
function getSiteConfig(): array {
    $defaults = [
        'empresa'             => 'Tech Store',
        'producto'            => 'Portal',
        'lema'                => 'Tu tienda de cómputo, componentes y tecnología',
        'hero_eyebrow'        => 'Tech Store Guatemala · Envíos a todo el país',
        'hero_titulo_1'       => 'Tecnología y Cómputo',
        'hero_titulo_acento'  => 'al mejor precio',
        'hero_subtitulo'      => 'Laptops, tarjetas de video, procesadores, monitores y periféricos con inventario en tiempo real y garantía oficial.',
        'perk_1'              => 'Garantía oficial y factura con IVA incluido',
        'perk_2'              => 'Envío gratis en compras mayores a Q 500',
        'perk_3'              => 'Telemetría y observabilidad en tiempo real con Prometheus',
        'anuncio_activo'      => '1',
        'anuncio_texto'       => '🚀 Tech Store · Hardware y Cómputo con stock real en bodega · Despacho inmediato en toda Guatemala',
        'iva_porcentaje'      => '12',
        'envio_base'          => '35',
        'envio_gratis_desde'  => '500',
        'color_primario'      => '#2563eb',
        'telefono_soporte'    => '+502 2290-4000',
        'correo_soporte'      => 'ventas@techstore.com.gt',
        'pie_pagina'          => 'Tech Store Guatemala — Equipos de Cómputo, Hardware & Observabilidad',
        'seq_pedidos'         => '48',
    ];

    $pdo = getPDO();
    if (!$pdo) {
        return $defaults;
    }
    try {
        $rows = $pdo->query("SELECT `clave`, `valor` FROM `configuracion_sitio`")->fetchAll();
        foreach ($rows as $r) {
            $defaults[$r['clave']] = $r['valor'];
        }
    } catch (Throwable $e) {
        // Usar defaults
    }
    return $defaults;
}

/**
 * Construye el payload completo desde MySQL para hidratar el estado del portal.
 */
function getPulsoSnapshot(): array {
    $cfg = getSiteConfig();
    $pdo = getPDO();
    if (!$pdo) {
        return ['connected' => false, 'config' => $cfg];
    }

    try {
        // Categorías
        $catRows = $pdo->query("SELECT * FROM `categorias` WHERE `activo` = 1 ORDER BY `orden` ASC, `id` ASC")->fetchAll();
        $categorias = [];
        foreach ($catRows as $cr) {
            $categorias[$cr['nombre']] = [
                'color' => $cr['color'],
                'icono' => $cr['icono'],
            ];
        }

        // Productos y mapa de stock
        $prodRows = $pdo->query("SELECT * FROM `productos` WHERE `activo` = 1 ORDER BY `categoria` ASC, `sku` ASC")->fetchAll();
        $productos = [];
        $stockMap = [];
        foreach ($prodRows as $pr) {
            $stock = (int)$pr['stock'];
            $stockMap[$pr['sku']] = $stock;
            $productos[] = [
                'sku'         => $pr['sku'],
                'nombre'      => $pr['nombre'],
                'descripcion' => $pr['descripcion'] ?? '',
                'categoria'   => $pr['categoria'],
                'precio'      => (float)$pr['precio'],
                'stock'       => $stock,
                'stockMinimo' => (int)$pr['stock_minimo'],
                'kg'          => (float)$pr['kg'],
                'm3'          => (float)$pr['m3'],
                'imagen'      => $pr['imagen'] ?: null,
                'destacado'   => (bool)$pr['destacado'],
            ];
        }

        // Roles
        $rolRows = $pdo->query("SELECT * FROM `roles`")->fetchAll();
        $roles = [];
        foreach ($rolRows as $rr) {
            $roles[$rr['id']] = [
                'descripcion' => $rr['descripcion'] ?? '',
                'base'        => (bool)$rr['base'],
                'modulos'     => json_decode($rr['modulos_json'], true) ?: [],
                'permisos'    => json_decode($rr['permisos_json'], true) ?: [],
            ];
        }

        // Usuarios
        $usrRows = $pdo->query("SELECT * FROM `usuarios`")->fetchAll();
        $users = [];
        foreach ($usrRows as $ur) {
            $users[$ur['username']] = [
                'hash'   => $ur['hash_clave'],
                'name'   => $ur['nombre'],
                'email'  => $ur['email'],
                'role'   => $ur['rol_id'],
                'active' => (bool)$ur['activo'],
                'base'   => (bool)$ur['base'],
            ];
        }

        // Clientes
        $cliRows = $pdo->query("SELECT * FROM `clientes` ORDER BY `id` ASC")->fetchAll();
        $clientes = [];
        foreach ($cliRows as $clr) {
            $clientes[] = [
                'id'          => $clr['id'],
                'nombre'      => $clr['nombre'],
                'tipo'        => $clr['tipo'],
                'nit'         => $clr['nit'],
                'telefono'    => $clr['telefono'],
                'email'       => $clr['email'] ?? '',
                'entrega'     => json_decode($clr['entrega_json'], true) ?: [],
                'facturacion' => json_decode($clr['facturacion_json'], true) ?: [],
                'creadoEn'    => date('c', strtotime($clr['creado_en'])),
            ];
        }

        // Cuentas tienda
        $ctaRows = $pdo->query("SELECT * FROM `cuentas_tienda`")->fetchAll();
        $cuentas = [];
        foreach ($ctaRows as $ctr) {
            $cuentas[] = [
                'email'     => $ctr['email'],
                'clienteId' => $ctr['cliente_id'],
                'nombre'    => $ctr['nombre'],
                'hash'      => $ctr['hash_clave'],
                'creadoEn'  => date('c', strtotime($ctr['creado_en'])),
            ];
        }

        // Pedidos
        $pedRows = $pdo->query("SELECT * FROM `pedidos` ORDER BY `fecha` DESC")->fetchAll();
        $pedidos = null;
        if (count($pedRows) > 0) {
            $pedidos = [];
            foreach ($pedRows as $pdr) {
                $extra = $pdr['extra_json'] ? (json_decode($pdr['extra_json'], true) ?: []) : [];
                $ped = array_merge($extra, [
                    'id'            => $pdr['id'],
                    'estado'        => $pdr['estado'],
                    'fecha'         => date('c', strtotime($pdr['fecha'])),
                    'clienteId'     => $pdr['cliente_id'],
                    'cliente'       => json_decode($pdr['cliente_json'], true) ?: [],
                    'entrega'       => json_decode($pdr['entrega_json'], true) ?: [],
                    'facturacion'   => json_decode($pdr['facturacion_json'], true) ?: [],
                    'items'         => json_decode($pdr['items_json'], true) ?: [],
                    'envio'         => (float)$pdr['envio'],
                    'totales'       => json_decode($pdr['totales_json'], true) ?: [],
                    'canal'         => $pdr['canal'],
                    'pago'          => $pdr['pago'],
                    'prioridad'     => $pdr['prioridad'],
                    'franja'        => $pdr['franja'] ?? '',
                    'fechaEntrega'  => $pdr['fecha_entrega'] ?? '',
                    'obsCliente'    => $pdr['obs_cliente'] ?? '',
                    'notasInternas' => $pdr['notas_internas'] ?? '',
                    'creadoPor'     => $pdr['creado_por'] ?? '',
                    'actualizado'   => $pdr['actualizado'] ? date('c', strtotime($pdr['actualizado'])) : date('c', strtotime($pdr['fecha'])),
                    'rutaId'        => $pdr['ruta_id'] ?: null,
                ]);
                if ($pdr['picking_json']) {
                    $ped['picking'] = json_decode($pdr['picking_json'], true);
                }
                $pedidos[] = $ped;
            }
        }

        // Vehículos (normalizado con alias `nombre` y `pilotoHabitual` para todos los módulos JS)
        $vehRows = $pdo->query("SELECT * FROM `vehiculos` ORDER BY `id` ASC")->fetchAll();
        $vehiculos = [];
        foreach ($vehRows as $vr) {
            $vehiculos[] = [
                'id'             => $vr['id'],
                'placa'          => $vr['placa'],
                'tipo'           => $vr['tipo'],
                'marca'          => $vr['marca'],
                'nombre'         => $vr['marca'],
                'piloto'         => $vr['piloto'],
                'pilotoHabitual' => $vr['piloto'],
                'tel'            => $vr['tel'],
                'capKg'          => (float)$vr['cap_kg'],
                'capM3'          => (float)$vr['cap_m3'],
                'estado'         => $vr['estado'],
            ];
        }

        // Rutas
        $rutRows = $pdo->query("SELECT * FROM `rutas` ORDER BY `id` ASC")->fetchAll();
        $rutas = null;
        if (count($rutRows) > 0) {
            $rutas = [];
            foreach ($rutRows as $rr) {
                $extra = $rr['extra_json'] ? (json_decode($rr['extra_json'], true) ?: []) : [];
                if (isset($extra['carga']) && is_array($extra['carga'])) {
                    $picksRaw = $extra['carga']['picks'] ?? [];
                    $extra['carga']['picks'] = (is_array($picksRaw) && count($picksRaw) > 0) ? $picksRaw : new stdClass();
                    $extra['carga']['cargados'] = (isset($extra['carga']['cargados']) && is_array($extra['carga']['cargados']))
                        ? array_values($extra['carga']['cargados'])
                        : [];
                }
                $rutas[] = array_merge($extra, [
                    'id'         => $rr['id'],
                    'codigo'     => $rr['codigo'],
                    'nombre'     => $rr['nombre'],
                    'zona'       => $rr['zona'],
                    'fecha'      => $rr['fecha'],
                    'franja'     => $rr['franja'],
                    'vehiculoId' => $rr['vehiculo_id'],
                    'piloto'     => $rr['piloto'],
                    'telPiloto'  => $rr['tel_piloto'],
                    'estado'     => $rr['estado'],
                    'pedidos'    => json_decode($rr['pedidos_json'], true) ?: [],
                    'notas'      => $rr['notas'] ?? '',
                    'creadaPor'  => $rr['creada_por'] ?? '',
                    'creadaEn'   => $rr['creada_en'] ? date('c', strtotime($rr['creada_en'])) : date('c'),
                ]);
            }
        }

        // Movimientos de inventario
        $movRows = $pdo->query("SELECT * FROM `movimientos_inventario` ORDER BY `ts` DESC LIMIT 400")->fetchAll();
        $movs = null;
        if (count($movRows) > 0) {
            $movs = [];
            foreach ($movRows as $mr) {
                $movs[] = [
                    'id'           => $mr['id'],
                    'ts'           => date('c', strtotime($mr['ts'])),
                    'sku'          => $mr['sku'],
                    'tipo'         => $mr['tipo'],
                    'delta'        => (int)$mr['delta'],
                    'stockAntes'   => (int)$mr['stock_antes'],
                    'stockDespues' => (int)$mr['stock_despues'],
                    'motivo'       => $mr['motivo'] ?? '',
                    'ref'          => $mr['ref'] ?? '',
                    'usuario'      => $mr['usuario'] ?? '',
                ];
            }
        }

        // Bitácora
        $bitRows = $pdo->query("SELECT * FROM `bitacora` ORDER BY `id` DESC LIMIT 200")->fetchAll();
        $audit = [];
        foreach ($bitRows as $br) {
            $audit[] = [
                'ts'     => date('c', strtotime($br['ts'])),
                'user'   => $br['user'],
                'action' => $br['action'],
                'detail' => $br['detail'] ?? '',
            ];
        }

        // Metas KPI
        $metaRow = $pdo->query("SELECT `metas_json` FROM `kpi_metas` WHERE `id` = 1")->fetch();
        $metas = $metaRow ? (json_decode($metaRow['metas_json'], true) ?: null) : null;

        return [
            'connected'  => true,
            'config'     => $cfg,
            'categorias' => $categorias,
            'productos'  => $productos,
            'stockMap'   => $stockMap,
            'roles'      => $roles,
            'users'      => $users,
            'clientes'   => $clientes,
            'cuentas'    => $cuentas,
            'pedidos'    => $pedidos,
            'vehiculos'  => $vehiculos,
            'rutas'      => $rutas,
            'movs'       => $movs,
            'audit'      => $audit,
            'metas'      => $metas,
            'kpisReales' => computeRealKpisFromPdo($pdo),
            'seq'        => (int)($cfg['seq_pedidos'] ?? 48),
        ];
    } catch (Throwable $e) {
        return ['connected' => false, 'config' => $cfg, 'error' => $e->getMessage()];
    }
}

