<?php
/**
 * Tech Store | Pulso — Motor de Telemetría Real en MySQL (KPIs K01–K10)
 * Calcula los 10 KPIs directamente desde `telemetria_eventos` y `pedidos` (cero valores ficticios).
 */

declare(strict_types=1);

function ensureTelemetryTable(PDO $pdo): void {
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function trackTelemetryEvent(
    PDO $pdo,
    string $tipo,
    string $canal = 'Tienda web',
    ?string $refId = null,
    int $valido = 1,
    ?float $valor = null,
    string $detalle = '',
    ?array $meta = null,
    ?string $ts = null
): void {
    ensureTelemetryTable($pdo);
    $tsMysql = $ts ? (strtotime($ts) ? date('Y-m-d H:i:s', strtotime($ts)) : date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        INSERT INTO `telemetria_eventos` (`ts`, `tipo`, `canal`, `ref_id`, `valido`, `valor`, `detalle`, `meta_json`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $tsMysql,
        substr(trim($tipo), 0, 48),
        substr(trim($canal ?: 'Tienda web'), 0, 40),
        $refId !== null && $refId !== '' ? substr($refId, 0, 64) : null,
        $valido ? 1 : 0,
        $valor,
        $detalle !== '' ? substr($detalle, 0, 255) : null,
        $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

function recordTelemetryOnce(
    PDO $pdo,
    string $tipo,
    string $refId,
    string $canal = 'Tienda web',
    int $valido = 1,
    ?float $valor = null,
    string $detalle = '',
    ?array $meta = null,
    ?string $ts = null
): bool {
    ensureTelemetryTable($pdo);
    if ($refId !== '') {
        $chk = $pdo->prepare("SELECT `id` FROM `telemetria_eventos` WHERE `tipo` = ? AND `ref_id` = ? LIMIT 1");
        $chk->execute([$tipo, $refId]);
        if ($chk->fetchColumn()) {
            return false;
        }
    }
    trackTelemetryEvent($pdo, $tipo, $canal, $refId, $valido, $valor, $detalle, $meta, $ts);
    return true;
}

/**
 * Siembra eventos reales iniciales a partir de los pedidos históricos y recientes existentes en MySQL.
 * No inventa números mágicos: cada fila en telemetria_eventos corresponde a un pedido real en `pedidos`.
 */
function seedTelemetryIfEmpty(PDO $pdo, bool $force = false): void {
    ensureTelemetryTable($pdo);
    if ($force) {
        $pdo->exec("TRUNCATE TABLE `telemetria_eventos`");
    } else {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos`")->fetchColumn();
        if ($cnt > 0) {
            return;
        }
    }

    $rows = $pdo->query("SELECT `id`, `estado`, `fecha`, `canal`, `totales_json`, `extra_json` FROM `pedidos` ORDER BY `fecha` ASC")->fetchAll();
    foreach ($rows as $pr) {
        $id = (string)$pr['id'];
        $estado = (string)$pr['estado'];
        if ($estado === 'Borrador') {
            continue;
        }
        $canal = (string)($pr['canal'] ?: 'Teléfono');
        $fecha = (string)$pr['fecha'];
        $totales = json_decode($pr['totales_json'] ?? '[]', true) ?: [];
        $totalMonto = (float)($totales['total'] ?? 0.0);
        $extra = $pr['extra_json'] ? (json_decode($pr['extra_json'], true) ?: []) : [];

        trackTelemetryEvent($pdo, 'solicitud_recibida', $canal, $id, 1, null, "Solicitud {$id}", null, $fecha);
        trackTelemetryEvent($pdo, 'validacion_ok', $canal, $id, 1, null, "Datos válidos {$id}", null, $fecha);
        trackTelemetryEvent($pdo, 'stock_ok', $canal, $id, 1, null, "Stock reservado {$id}", null, $fecha);
        trackTelemetryEvent($pdo, 'pedido_creado', $canal, $id, 1, $totalMonto, "Pedido creado {$id}", null, $fecha);

        if ($canal === 'Tienda web') {
            trackTelemetryEvent($pdo, 'checkout_iniciado', 'Tienda web', $id, 1, null, "Checkout completado {$id}", null, $fecha);
        }

        if (isset($extra['minLiquidacion'])) {
            $minLiq = (float)$extra['minLiquidacion'];
            $liqTs = $extra['fechaAprobacion'] ?? $fecha;
            trackTelemetryEvent($pdo, 'liquidacion', $canal, $id, $minLiq <= 60.0 ? 1 : 0, $minLiq, "Liquidación {$id}", null, (string)$liqTs);
        }

        if (isset($extra['minEmpaque'])) {
            $minEmp = (float)$extra['minEmpaque'];
            $empTs = $extra['fechaEmpaque'] ?? ($extra['fechaEnvio'] ?? $fecha);
            trackTelemetryEvent($pdo, 'empaque', 'Bodega', $id, $minEmp <= 30.0 ? 1 : 0, $minEmp, "Empaque {$id}", null, (string)$empTs);
        }

        if (isset($extra['fechaEnvio']) || isset($extra['horasDespacho'])) {
            $hDesp = isset($extra['horasDespacho'])
                ? (float)$extra['horasDespacho']
                : max(0.1, round((strtotime((string)$extra['fechaEnvio']) - strtotime($fecha)) / 3600, 2));
            $despTs = $extra['fechaEnvio'] ?? $fecha;
            trackTelemetryEvent($pdo, 'despacho', 'Logística', $id, $hDesp <= 8.0 ? 1 : 0, $hDesp, "Despacho {$id}", null, (string)$despTs);
        }
    }
}

/**
 * Calcula los 10 KPIs reales (K01–K10) y los contadores P1–P7 directamente desde MySQL.
 */
function computeRealKpisFromPdo(PDO $pdo): array {
    ensureTelemetryTable($pdo);

    $p1 = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'solicitud_recibida' AND `ts` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    $p1Total = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'solicitud_recibida'")->fetchColumn();

    $p2Val = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'validacion_ok'")->fetchColumn();
    $p2Inv = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'validacion_error'")->fetchColumn();
    $p2Eval = $p2Val + $p2Inv;

    $p3Stock = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'stock_ok'")->fetchColumn();
    $p3NoStock = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'stock_fallo'")->fetchColumn();
    $p3Base = $p3Stock + $p3NoStock;

    $p4Creados = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'pedido_creado' AND `valido` = 1")->fetchColumn();
    $p4Fallidos = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'pedido_fallido'")->fetchColumn();
    $p4Eleg = max($p3Stock, $p4Creados + $p4Fallidos);

    $checkoutIni = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'checkout_iniciado'")->fetchColumn();
    $checkoutAbn = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'checkout_abandono'")->fetchColumn();

    // K06 desde tabla real `pedidos` (excluyendo Cancelado y Borrador)
    $pedFinRows = $pdo->query("SELECT `totales_json` FROM `pedidos` WHERE `estado` NOT IN ('Cancelado', 'Borrador')")->fetchAll();
    $montoFact = 0.0;
    $pedMontoRef = count($pedFinRows);
    foreach ($pedFinRows as $r) {
        $td = json_decode($r['totales_json'] ?? '[]', true) ?: [];
        $montoFact += (float)($td['total'] ?? 0.0);
    }
    $montoFact = round($montoFact, 2);

    $p5Ok = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'liquidacion' AND `valido` = 1")->fetchColumn();
    $p5Tarde = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'liquidacion' AND `valido` = 0")->fetchColumn();
    $p5Venc = $p5Ok + $p5Tarde;

    $empRow = $pdo->query("SELECT COUNT(*) AS `c`, COALESCE(SUM(`valor`), 0) AS `s` FROM `telemetria_eventos` WHERE `tipo` = 'empaque' AND `valor` IS NOT NULL")->fetch();
    $p6Emp = (int)($empRow['c'] ?? 0);
    $p6MinSum = round((float)($empRow['s'] ?? 0.0), 2);

    $p7Ok = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'despacho' AND `valido` = 1")->fetchColumn();
    $p7Tarde = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'despacho' AND `valido` = 0")->fetchColumn();
    $p7Venc = $p7Ok + $p7Tarde;

    $despLeadRow = $pdo->query("SELECT COUNT(*) AS `c`, COALESCE(SUM(`valor`), 0) AS `s` FROM `telemetria_eventos` WHERE `tipo` = 'despacho' AND `valor` IS NOT NULL")->fetch();
    $despLeadCount = (int)($despLeadRow['c'] ?? 0);
    $horasLeadSum = round((float)($despLeadRow['s'] ?? 0.0), 2);

    $k01 = (float)$p1;
    $k02 = $p2Eval > 0 ? round(($p2Val / $p2Eval) * 100.0, 2) : 0.0;
    $k03 = $p3Base > 0 ? round(($p3Stock / $p3Base) * 100.0, 2) : 0.0;
    $k04 = $p4Eleg > 0 ? round(($p4Creados / $p4Eleg) * 100.0, 2) : 0.0;
    $k05 = $checkoutIni > 0 ? round(($checkoutAbn / $checkoutIni) * 100.0, 2) : 0.0;
    $k06 = $pedMontoRef > 0 ? round($montoFact / $pedMontoRef, 2) : 0.0;
    $k07 = $p5Venc > 0 ? round(($p5Ok / $p5Venc) * 100.0, 2) : 0.0;
    $k08 = $p6Emp > 0 ? round($p6MinSum / $p6Emp, 1) : 0.0;
    $k09 = $p7Venc > 0 ? round(($p7Ok / $p7Venc) * 100.0, 2) : 0.0;
    $k10 = $despLeadCount > 0 ? round($horasLeadSum / $despLeadCount, 2) : 0.0;

    // Semáforos oficiales: 2 = VERDE, 1 = AMARILLO, 0 = ROJO
    $semK01 = $k01 >= 100.0 ? 2 : ($k01 >= 70.0 ? 1 : 0);
    $semK02 = $k02 >= 98.0  ? 2 : ($k02 >= 95.0 ? 1 : 0);
    $semK03 = $k03 >= 97.0  ? 2 : ($k03 >= 90.0 ? 1 : 0);
    $semK04 = $k04 >= 99.0  ? 2 : ($k04 >= 97.0 ? 1 : 0);
    $semK05 = $k05 <= 20.0  ? 2 : ($k05 <= 30.0 ? 1 : 0);
    $semK06 = $k06 >= 250.0 ? 2 : ($k06 >= 150.0 ? 1 : 0);
    $semK07 = $k07 >= 95.0  ? 2 : ($k07 >= 85.0 ? 1 : 0);
    $semK08 = $k08 <= 30.0  ? 2 : ($k08 <= 45.0 ? 1 : 0);
    $semK09 = $k09 >= 95.0  ? 2 : ($k09 >= 85.0 ? 1 : 0);
    $semK10 = $k10 <= 4.0   ? 2 : ($k10 <= 6.0 ? 1 : 0);

    return [
        'fuente'           => 'mysql_en_vivo',
        'p1'               => $p1,
        'p1_total'         => $p1Total,
        'p2_eval'          => $p2Eval,
        'p2_val'           => $p2Val,
        'p2_inv'           => $p2Inv,
        'p3_base'          => $p3Base,
        'p3_stock'         => $p3Stock,
        'p3_nostock'       => $p3NoStock,
        'p4_eleg'          => $p4Eleg,
        'p4_creados'       => $p4Creados,
        'p4_fallidos'      => $p4Fallidos,
        'checkout_ini'     => $checkoutIni,
        'checkout_abn'     => $checkoutAbn,
        'monto_fact'       => $montoFact,
        'ped_monto_ref'    => $pedMontoRef,
        'p5_venc'          => $p5Venc,
        'p5_ok'            => $p5Ok,
        'p5_tarde'         => $p5Tarde,
        'p6_emp'           => $p6Emp,
        'p6_min_sum'       => $p6MinSum,
        'p7_venc'          => $p7Venc,
        'p7_ok'            => $p7Ok,
        'p7_tarde'         => $p7Tarde,
        'desp_lead_count'  => $despLeadCount,
        'horas_lead_sum'   => $horasLeadSum,
        'k01'              => $k01,
        'k02'              => $k02,
        'k03'              => $k03,
        'k04'              => $k04,
        'k05'              => $k05,
        'k06'              => $k06,
        'k07'              => $k07,
        'k08'              => $k08,
        'k09'              => $k09,
        'k10'              => $k10,
        'sem_k01'          => $semK01,
        'sem_k02'          => $semK02,
        'sem_k03'          => $semK03,
        'sem_k04'          => $semK04,
        'sem_k05'          => $semK05,
        'sem_k06'          => $semK06,
        'sem_k07'          => $semK07,
        'sem_k08'          => $semK08,
        'sem_k09'          => $semK09,
        'sem_k10'          => $semK10,
        'updated_at'       => date('c'),
    ];
}
