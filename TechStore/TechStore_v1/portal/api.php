<?php
/**
 * DigyFenix | Pulso — API REST & Sincronización en Tiempo Real con MySQL
 * Soporta sincronización automática desde todos los módulos, CRUD de catálogo con imágenes,
 * configuración de página (CMS) y exportación de métricas para Prometheus.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Endpoint de métricas en formato Prometheus text/plain
if ($action === 'prometheus' || $action === 'metrics') {
    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
    $pdo = getPDO();
    if (!$pdo) {
        echo "# HELP pulso_mysql_up Estado de conexion a la base de datos MySQL del portal Pulso (1=UP, 0=DOWN)\n";
        echo "# TYPE pulso_mysql_up gauge\n";
        echo "pulso_mysql_up 0\n";
        exit;
    }
    echo "# HELP pulso_mysql_up Estado de conexion a la base de datos MySQL del portal Tech Store (1=UP, 0=DOWN)\n";
    echo "# TYPE pulso_mysql_up gauge\n";
    echo "pulso_mysql_up 1\n\n";

    echo "# HELP techstore_info Informacion de identidad y metadata de la tienda Tech Store\n";
    echo "# TYPE techstore_info gauge\n";
    echo "techstore_info{tienda=\"Tech Store Guatemala\",bd=\"digyfenix_pulso\",puerto=\"8085\",moneda=\"GTQ\",grupo=\"Grupo #3\"} 1\n\n";

    // Productos e inventario de cómputo
    $totalProd    = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `activo`=1")->fetchColumn();
    $sinStock     = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `stock`=0 AND `activo`=1")->fetchColumn();
    $stockBajo    = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `stock`>0 AND `stock`<=`stock_minimo` AND `activo`=1")->fetchColumn();
    $totalStock   = (int)$pdo->query("SELECT COALESCE(SUM(`stock`),0) FROM `productos` WHERE `activo`=1")->fetchColumn();
    $valorInv     = (float)$pdo->query("SELECT COALESCE(SUM(`stock` * `precio`),0) FROM `productos` WHERE `activo`=1")->fetchColumn();
    $disponibPct  = $totalProd > 0 ? round((($totalProd - $sinStock) / $totalProd) * 100, 2) : 0.0;

    echo "# HELP pulso_productos_activos Total de productos activos en catalogo de computo MySQL\n";
    echo "# TYPE pulso_productos_activos gauge\n";
    echo "pulso_productos_activos {$totalProd}\n\n";

    echo "# HELP pulso_productos_sin_stock_total Total de productos sin existencias en bodega (stock = 0)\n";
    echo "# TYPE pulso_productos_sin_stock_total gauge\n";
    echo "pulso_productos_sin_stock_total {$sinStock}\n\n";

    echo "# HELP pulso_productos_stock_bajo_total Total de productos con existencia igual o menor al punto de reorden\n";
    echo "# TYPE pulso_productos_stock_bajo_total gauge\n";
    echo "pulso_productos_stock_bajo_total {$stockBajo}\n\n";

    echo "# HELP pulso_disponibilidad_stock_porcentaje Porcentaje del catalogo de computo con stock disponible para venta inmediata\n";
    echo "# TYPE pulso_disponibilidad_stock_porcentaje gauge\n";
    echo "pulso_disponibilidad_stock_porcentaje {$disponibPct}\n\n";

    echo "# HELP pulso_inventario_unidades_total Suma de unidades de computo en stock en bodega central\n";
    echo "# TYPE pulso_inventario_unidades_total gauge\n";
    echo "pulso_inventario_unidades_total {$totalStock}\n\n";

    echo "# HELP pulso_inventario_valor_quetzales Valor monetario total del inventario de computo en bodega (GTQ)\n";
    echo "# TYPE pulso_inventario_valor_quetzales gauge\n";
    echo "pulso_inventario_valor_quetzales {$valorInv}\n\n";

    // Pedidos, ventas y transacciones
    $pedRows = $pdo->query("SELECT `estado`, `canal`, `pago`, `totales_json` FROM `pedidos`")->fetchAll();
    $totalPed = count($pedRows);
    $ventasTot = 0.0;
    $ventasCont = 0;
    $pedPorEstado = [];
    $pedPorCanal  = [];
    $pedPorPago   = [];
    $entregadosOk = 0;
    $cancelados   = 0;

    foreach ($pedRows as $pr) {
        $st = $pr['estado'] ?? 'Borrador';
        $pedPorEstado[$st] = ($pedPorEstado[$st] ?? 0) + 1;

        $cn = $pr['canal'] ?? 'Teléfono';
        $pedPorCanal[$cn] = ($pedPorCanal[$cn] ?? 0) + 1;

        $pg = $pr['pago'] ?? 'Contra entrega';
        $pedPorPago[$pg] = ($pedPorPago[$pg] ?? 0) + 1;

        $tData = json_decode($pr['totales_json'] ?? '[]', true) ?: [];
        $monto = (float)($tData['total'] ?? 0.0);

        if ($st !== 'Cancelado' && $st !== 'Borrador') {
            $ventasTot += $monto;
            $ventasCont++;
        } elseif ($st === 'Cancelado') {
            $cancelados++;
        }
        if ($st === 'Entregado') {
            $entregadosOk++;
        }
    }
    $ticketMedio = $ventasCont > 0 ? round($ventasTot / $ventasCont, 2) : 0.0;
    $borradores = (int)($pedPorEstado['Borrador'] ?? 0);
    $entregasDenom = max(0, $totalPed - $cancelados - $borradores);
    $tasaEntregas = $entregasDenom > 0 ? round(($entregadosOk / $entregasDenom) * 100, 2) : 0.0;

    echo "# HELP pulso_pedidos_registrados Total de pedidos registrados en el sistema MySQL\n";
    echo "# TYPE pulso_pedidos_registrados gauge\n";
    echo "pulso_pedidos_registrados {$totalPed}\n\n";

    echo "# HELP pulso_ventas_quetzales_total Monto acumulado de ventas de computo en Quetzales (GTQ)\n";
    echo "# TYPE pulso_ventas_quetzales_total gauge\n";
    echo "pulso_ventas_quetzales_total {$ventasTot}\n\n";

    echo "# HELP pulso_ticket_promedio_quetzales Valor promedio por pedido en Quetzales (GTQ)\n";
    echo "# TYPE pulso_ticket_promedio_quetzales gauge\n";
    echo "pulso_ticket_promedio_quetzales {$ticketMedio}\n\n";

    echo "# HELP pulso_tasa_entregas_exitosas_porcentaje Porcentaje de pedidos completados con exito vs activos\n";
    echo "# TYPE pulso_tasa_entregas_exitosas_porcentaje gauge\n";
    echo "pulso_tasa_entregas_exitosas_porcentaje {$tasaEntregas}\n\n";

    echo "# HELP pulso_pedidos_por_estado Pedidos distribuidos por fase del ciclo operativo\n";
    echo "# TYPE pulso_pedidos_por_estado gauge\n";
    foreach ($pedPorEstado as $est => $cant) {
        $estNorm = str_replace(' ', '_', $est);
        echo "pulso_pedidos_por_estado{estado=\"{$estNorm}\",etiqueta=\"{$est}\"} {$cant}\n";
    }
    echo "\n";

    echo "# HELP pulso_pedidos_por_canal Pedidos distribuidos por canal de venta\n";
    echo "# TYPE pulso_pedidos_por_canal gauge\n";
    foreach ($pedPorCanal as $cn => $cant) {
        $cnNorm = str_replace(' ', '_', $cn);
        echo "pulso_pedidos_por_canal{canal=\"{$cnNorm}\",etiqueta=\"{$cn}\"} {$cant}\n";
    }
    echo "\n";

    // Logística y flota
    $rutasActivas = (int)$pdo->query("SELECT COUNT(*) FROM `rutas` WHERE `estado` IN ('Planificada', 'Despachada', 'En ruta', 'En preparación')")->fetchColumn();
    $flotaDisp    = (int)$pdo->query("SELECT COUNT(*) FROM `vehiculos` WHERE `estado` = 'Disponible'")->fetchColumn();
    $sol24h       = (int)$pdo->query("SELECT COUNT(*) FROM `telemetria_eventos` WHERE `tipo` = 'solicitud_recibida' AND `ts` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

    echo "# HELP pulso_rutas_activas Numero de rutas de entrega actualmente planificadas o en transito\n";
    echo "# TYPE pulso_rutas_activas gauge\n";
    echo "pulso_rutas_activas {$rutasActivas}\n\n";

    echo "# HELP pulso_flota_vehiculos_disponibles Unidades de transporte listas en parque logistico\n";
    echo "# TYPE pulso_flota_vehiculos_disponibles gauge\n";
    echo "pulso_flota_vehiculos_disponibles {$flotaDisp}\n\n";

    echo "# HELP pulso_solicitudes_24h Solicitudes de compra registradas en las ultimas 24 horas en telemetria MySQL\n";
    echo "# TYPE pulso_solicitudes_24h gauge\n";
    echo "pulso_solicitudes_24h {$sol24h}\n\n";

    // Clientes y usuarios
    $totalCli = (int)$pdo->query("SELECT COUNT(*) FROM `clientes`")->fetchColumn();
    $totalUsr = (int)$pdo->query("SELECT COUNT(*) FROM `usuarios` WHERE `activo`=1")->fetchColumn();

    echo "# HELP pulso_clientes_registrados Total de clientes registrados en MySQL\n";
    echo "# TYPE pulso_clientes_registrados gauge\n";
    echo "pulso_clientes_registrados {$totalCli}\n\n";

    echo "# HELP pulso_usuarios_activos Total de operadores y administradores activos en MySQL\n";
    echo "# TYPE pulso_usuarios_activos gauge\n";
    echo "pulso_usuarios_activos {$totalUsr}\n";
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
if (!$pdo) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'No se pudo conectar con el servidor MySQL.']);
    exit;
}

// Leer cuerpo JSON si la petición viene como application/json
$rawBody = file_get_contents('php://input');
$jsonBody = $rawBody ? (json_decode($rawBody, true) ?: []) : [];
if ($action === '' && isset($jsonBody['action'])) {
    $action = (string)$jsonBody['action'];
}

function isoToMysql(?string $iso): string {
    if (!$iso) {
        return date('Y-m-d H:i:s');
    }
    $ts = strtotime($iso);
    return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}

try {
    switch ($action) {
        case 'login':
            $userIn = trim((string)($jsonBody['user'] ?? $_POST['user'] ?? ''));
            $passIn = trim((string)($jsonBody['pass'] ?? $_POST['pass'] ?? ''));
            if ($userIn === '' || $passIn === '') {
                echo json_encode(['ok' => false, 'error' => 'Usuario y contraseña requeridos.']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT `username`, `hash_clave`, `nombre`, `email`, `rol_id`, `activo` FROM `usuarios` WHERE LOWER(`username`) = LOWER(?)");
            $stmt->execute([$userIn]);
            $u = $stmt->fetch();
            if (!$u) {
                echo json_encode(['ok' => false, 'error' => 'Usuario o contraseña incorrectos.']);
                exit;
            }
            if ((int)$u['activo'] === 0) {
                echo json_encode(['ok' => false, 'error' => 'Esta cuenta está desactivada. Contacta al administrador.']);
                exit;
            }
            $passHash = hash('sha256', $passIn);
            $valid = hash_equals(strtolower((string)$u['hash_clave']), strtolower($passHash));

            if (!$valid) {
                echo json_encode(['ok' => false, 'error' => 'Usuario o contraseña incorrectos.']);
                exit;
            }
            $stmtR = $pdo->prepare("SELECT `modulos_json`, `permisos_json` FROM `roles` WHERE `id` = ?");
            $stmtR->execute([$u['rol_id']]);
            $r = $stmtR->fetch() ?: ['modulos_json' => '[]', 'permisos_json' => '[]'];
            $session = [
                'user'     => $u['username'],
                'name'     => $u['nombre'],
                'email'    => $u['email'],
                'role'     => $u['rol_id'],
                'modules'  => json_decode($r['modulos_json'], true) ?: [],
                'permisos' => json_decode($r['permisos_json'], true) ?: [],
                'loginAt'  => date('c'),
            ];
            // Persistir de forma nativa en sesión PHP de servidor
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['df_session'] = $session;
            $_SESSION['df_user']    = $session['user'];

            $bStmt = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, ?, ?)");
            $bStmt->execute([date('Y-m-d H:i:s'), $u['username'], 'login', 'Inicio de sesión exitoso desde portal web (' . $u['rol_id'] . ')']);
            echo json_encode(['ok' => true, 'session' => $session]);
            exit;

        case 'logout':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            if (session_id() !== '') {
                session_destroy();
            }
            echo json_encode(['ok' => true]);
            exit;

        case 'status':
            echo json_encode([
                'ok'        => true,
                'database'  => 'digyfenix_pulso',
                'productos' => (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `activo`=1")->fetchColumn(),
                'clientes'  => (int)$pdo->query("SELECT COUNT(*) FROM `clientes`")->fetchColumn(),
                'pedidos'   => (int)$pdo->query("SELECT COUNT(*) FROM `pedidos`")->fetchColumn(),
                'usuarios'  => (int)$pdo->query("SELECT COUNT(*) FROM `usuarios`")->fetchColumn(),
            ]);
            break;

        case 'snapshot':
            echo json_encode(['ok' => true, 'data' => getPulsoSnapshot()], JSON_UNESCAPED_UNICODE);
            break;

        case 'kpis_reales':
            echo json_encode(['ok' => true, 'kpis' => computeRealKpisFromPdo($pdo)], JSON_UNESCAPED_UNICODE);
            break;

        case 'track_event':
            $allowedTipos = [
                'checkout_iniciado', 'checkout_abandono', 'solicitud_recibida',
                'validacion_ok', 'validacion_error', 'stock_ok', 'stock_fallo',
                'pedido_creado', 'pedido_fallido', 'liquidacion', 'empaque', 'despacho'
            ];
            $tipo = trim((string)($jsonBody['tipo'] ?? $_POST['tipo'] ?? ''));
            if (!in_array($tipo, $allowedTipos, true)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Tipo de evento no permitido.']);
                exit;
            }
            $canal   = trim((string)($jsonBody['canal'] ?? $_POST['canal'] ?? 'Tienda web'));
            $refId   = isset($jsonBody['ref_id']) ? trim((string)$jsonBody['ref_id']) : (isset($_POST['ref_id']) ? trim((string)$_POST['ref_id']) : null);
            $valido  = isset($jsonBody['valido']) ? (int)(bool)$jsonBody['valido'] : (isset($_POST['valido']) ? (int)(bool)$_POST['valido'] : 1);
            $valor   = isset($jsonBody['valor']) && $jsonBody['valor'] !== null ? (float)$jsonBody['valor'] : (isset($_POST['valor']) ? (float)$_POST['valor'] : null);
            $detalle = trim((string)($jsonBody['detalle'] ?? $_POST['detalle'] ?? ''));
            $meta    = isset($jsonBody['meta']) && is_array($jsonBody['meta']) ? $jsonBody['meta'] : null;
            $once    = !empty($jsonBody['once']) || !empty($_POST['once']);

            if ($once && $refId) {
                recordTelemetryOnce($pdo, $tipo, $refId, $canal, $valido, $valor, $detalle, $meta);
            } else {
                trackTelemetryEvent($pdo, $tipo, $canal, $refId, $valido, $valor, $detalle, $meta);
            }
            echo json_encode(['ok' => true, 'kpis' => computeRealKpisFromPdo($pdo)], JSON_UNESCAPED_UNICODE);
            break;

        case 'sync':
            $key = (string)($jsonBody['key'] ?? '');
            $val = $jsonBody['value'] ?? null;

            if ($key === 'df_stock_v1' && is_array($val)) {
                $stmt = $pdo->prepare("UPDATE `productos` SET `stock` = ? WHERE `sku` = ?");
                foreach ($val as $sku => $qty) {
                    $stmt->execute([(int)$qty, (string)$sku]);
                }
            } elseif ($key === 'df_seq_v1') {
                $stmt = $pdo->prepare("INSERT INTO `configuracion_sitio` (`clave`, `valor`) VALUES ('seq_pedidos', ?) ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)");
                $stmt->execute([(string)(int)$val]);
            } elseif ($key === 'df_clientes_v1' && is_array($val)) {
                $stmt = $pdo->prepare("
                    INSERT INTO `clientes` (`id`, `nombre`, `tipo`, `nit`, `telefono`, `email`, `entrega_json`, `facturacion_json`, `creado_en`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `nombre` = VALUES(`nombre`),
                        `tipo` = VALUES(`tipo`),
                        `nit` = VALUES(`nit`),
                        `telefono` = VALUES(`telefono`),
                        `email` = VALUES(`email`),
                        `entrega_json` = VALUES(`entrega_json`),
                        `facturacion_json` = VALUES(`facturacion_json`)
                ");
                foreach ($val as $c) {
                    if (!isset($c['id'])) continue;
                    $stmt->execute([
                        $c['id'],
                        $c['nombre'] ?? '',
                        $c['tipo'] ?? 'Individual',
                        $c['nit'] ?? 'CF',
                        $c['telefono'] ?? '',
                        $c['email'] ?? '',
                        json_encode($c['entrega'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($c['facturacion'] ?? [], JSON_UNESCAPED_UNICODE),
                        isoToMysql($c['creadoEn'] ?? null),
                    ]);
                }
            } elseif ($key === 'df_cuentas_v1' && is_array($val)) {
                $stmt = $pdo->prepare("
                    INSERT INTO `cuentas_tienda` (`email`, `cliente_id`, `nombre`, `hash_clave`, `creado_en`)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `cliente_id` = VALUES(`cliente_id`),
                        `nombre` = VALUES(`nombre`),
                        `hash_clave` = VALUES(`hash_clave`)
                ");
                foreach ($val as $ct) {
                    if (!isset($ct['email'])) continue;
                    $stmt->execute([
                        strtolower(trim((string)$ct['email'])),
                        $ct['clienteId'] ?? '',
                        $ct['nombre'] ?? '',
                        $ct['hash'] ?? '',
                        isoToMysql($ct['creadoEn'] ?? null),
                    ]);
                }
            } elseif ($key === 'df_pedidos_v1' && is_array($val)) {
                // Capturar estado previo para registrar transiciones reales K01-K10 sin duplicar
                $prevMap = [];
                foreach ($pdo->query("SELECT `id`, `estado`, `fecha`, `extra_json` FROM `pedidos`")->fetchAll() as $prPrev) {
                    $prevMap[$prPrev['id']] = $prPrev;
                }

                $knownKeys = ['id', 'estado', 'fecha', 'clienteId', 'cliente', 'entrega', 'facturacion', 'items', 'envio', 'totales', 'canal', 'pago', 'prioridad', 'franja', 'fechaEntrega', 'obsCliente', 'notasInternas', 'creadoPor', 'actualizado', 'rutaId', 'picking'];
                $stmt = $pdo->prepare("
                    INSERT INTO `pedidos` (
                        `id`, `estado`, `fecha`, `cliente_id`, `cliente_json`, `entrega_json`, `facturacion_json`,
                        `items_json`, `envio`, `totales_json`, `canal`, `pago`, `prioridad`, `franja`,
                        `fecha_entrega`, `obs_cliente`, `notas_internas`, `creado_por`, `actualizado`,
                        `ruta_id`, `picking_json`, `extra_json`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `estado` = VALUES(`estado`),
                        `cliente_id` = VALUES(`cliente_id`),
                        `cliente_json` = VALUES(`cliente_json`),
                        `entrega_json` = VALUES(`entrega_json`),
                        `facturacion_json` = VALUES(`facturacion_json`),
                        `items_json` = VALUES(`items_json`),
                        `envio` = VALUES(`envio`),
                        `totales_json` = VALUES(`totales_json`),
                        `canal` = VALUES(`canal`),
                        `pago` = VALUES(`pago`),
                        `prioridad` = VALUES(`prioridad`),
                        `franja` = VALUES(`franja`),
                        `fecha_entrega` = VALUES(`fecha_entrega`),
                        `obs_cliente` = VALUES(`obs_cliente`),
                        `notas_internas` = VALUES(`notas_internas`),
                        `actualizado` = VALUES(`actualizado`),
                        `ruta_id` = VALUES(`ruta_id`),
                        `picking_json` = VALUES(`picking_json`),
                        `extra_json` = VALUES(`extra_json`)
                ");
                foreach ($val as $p) {
                    if (!isset($p['id'])) continue;
                    $pid = (string)$p['id'];
                    $pEstado = (string)($p['estado'] ?? 'Borrador');
                    $pCanal = (string)($p['canal'] ?? 'Teléfono');
                    $pFechaSql = isoToMysql($p['fecha'] ?? null);
                    $pMonto = (float)($p['totales']['total'] ?? 0);

                    $extra = [];
                    foreach ($p as $pk => $pv) {
                        if (!in_array($pk, $knownKeys, true)) {
                            $extra[$pk] = $pv;
                        }
                    }
                    $stmt->execute([
                        $pid,
                        $pEstado,
                        $pFechaSql,
                        $p['clienteId'] ?? '',
                        json_encode($p['cliente'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($p['entrega'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($p['facturacion'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($p['items'] ?? [], JSON_UNESCAPED_UNICODE),
                        (float)($p['envio'] ?? 0),
                        json_encode($p['totales'] ?? [], JSON_UNESCAPED_UNICODE),
                        $pCanal,
                        $p['pago'] ?? 'Contra entrega',
                        $p['prioridad'] ?? 'Normal',
                        $p['franja'] ?? '',
                        $p['fechaEntrega'] ?? '',
                        $p['obsCliente'] ?? '',
                        $p['notasInternas'] ?? '',
                        $p['creadoPor'] ?? '',
                        isoToMysql($p['actualizado'] ?? ($p['fecha'] ?? null)),
                        !empty($p['rutaId']) ? $p['rutaId'] : null,
                        isset($p['picking']) ? json_encode($p['picking'], JSON_UNESCAPED_UNICODE) : null,
                        count($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
                    ]);

                    // Registro automático y deduplicado de hitos reales de telemetría (K01–K10)
                    $prevEstado = $prevMap[$pid]['estado'] ?? null;
                    if ($pEstado !== 'Borrador' && $pEstado !== 'Cancelado') {
                        recordTelemetryOnce($pdo, 'solicitud_recibida', $pid, $pCanal, 1, null, "Solicitud {$pid}", null, $pFechaSql);
                        recordTelemetryOnce($pdo, 'validacion_ok', $pid, $pCanal, 1, null, "Datos válidos {$pid}", null, $pFechaSql);
                        recordTelemetryOnce($pdo, 'stock_ok', $pid, $pCanal, 1, null, "Stock reservado {$pid}", null, $pFechaSql);
                        recordTelemetryOnce($pdo, 'pedido_creado', $pid, $pCanal, 1, $pMonto, "Pedido creado {$pid}", null, $pFechaSql);

                        if ($pCanal === 'Tienda web') {
                            $chkRef = (string)($p['checkoutRefId'] ?? '');
                            $hasChkRef = false;
                            if ($chkRef !== '') {
                                $chkQ = $pdo->prepare("SELECT `id` FROM `telemetria_eventos` WHERE `tipo` = 'checkout_iniciado' AND `ref_id` = ? LIMIT 1");
                                $chkQ->execute([$chkRef]);
                                $hasChkRef = (bool)$chkQ->fetchColumn();
                            }
                            if (!$hasChkRef) {
                                recordTelemetryOnce($pdo, 'checkout_iniciado', $pid, 'Tienda web', 1, null, "Checkout completado {$pid}", null, $pFechaSql);
                            }
                        }
                    }

                    // Hito P5 / K07: Liquidación (aprobación web o emisión directa en Contact Center)
                    if (in_array($pEstado, ['Pendiente de despacho', 'En preparación', 'En ruta', 'Entregado'], true)) {
                        $aprobTs = isoToMysql($p['fechaAprobacion'] ?? ($p['actualizado'] ?? null));
                        $minLiq = isset($p['minLiquidacion'])
                            ? round((float)$p['minLiquidacion'], 1)
                            : max(0.5, round((strtotime($aprobTs) - strtotime($pFechaSql)) / 60, 1));
                        recordTelemetryOnce($pdo, 'liquidacion', $pid, $pCanal, $minLiq <= 60.0 ? 1 : 0, $minLiq, "Liquidación {$pid}", null, $aprobTs);
                    }

                    // Hito P6 / K08: Empaque en bodega
                    if (isset($p['minEmpaque']) && (float)$p['minEmpaque'] > 0) {
                        $minEmp = round((float)$p['minEmpaque'], 1);
                        $empTs = isoToMysql($p['fechaEmpaque'] ?? ($p['fechaEnvio'] ?? ($p['actualizado'] ?? null)));
                        recordTelemetryOnce($pdo, 'empaque', $pid, 'Bodega', $minEmp <= 30.0 ? 1 : 0, $minEmp, "Empaque {$pid}", null, $empTs);
                    }

                    // Hito P7 / K09 & K10: Despacho a ruta
                    if (in_array($pEstado, ['En ruta', 'Entregado'], true) && ($prevEstado === null || !in_array($prevEstado, ['En ruta', 'Entregado'], true) || isset($p['fechaEnvio']))) {
                        $envTs = isoToMysql($p['fechaEnvio'] ?? ($p['actualizado'] ?? null));
                        $horas = isset($p['horasDespacho'])
                            ? round((float)$p['horasDespacho'], 2)
                            : max(0.05, round((strtotime($envTs) - strtotime($pFechaSql)) / 3600, 2));
                        recordTelemetryOnce($pdo, 'despacho', $pid, 'Logística', $horas <= 8.0 ? 1 : 0, $horas, "Despacho {$pid}", null, $envTs);
                    }
                }
            } elseif ($key === 'df_vehiculos_v1' && is_array($val)) {
                $pdo->exec("DELETE FROM `vehiculos`");
                $stmt = $pdo->prepare("INSERT INTO `vehiculos` (`id`, `placa`, `tipo`, `marca`, `piloto`, `tel`, `cap_kg`, `cap_m3`, `estado`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($val as $v) {
                    if (!isset($v['id'])) continue;
                    $stmt->execute([
                        $v['id'],
                        $v['placa'] ?? '',
                        $v['tipo'] ?? 'Panel',
                        $v['marca'] ?? ($v['nombre'] ?? ''),
                        $v['piloto'] ?? ($v['pilotoHabitual'] ?? ''),
                        $v['tel'] ?? '',
                        (float)($v['capKg'] ?? 1000),
                        (float)($v['capM3'] ?? 5),
                        $v['estado'] ?? 'Disponible',
                    ]);
                }
            } elseif ($key === 'df_rutas_v1' && is_array($val)) {
                $knownR = ['id', 'codigo', 'nombre', 'zona', 'fecha', 'franja', 'vehiculoId', 'piloto', 'telPiloto', 'estado', 'pedidos', 'notas', 'creadaPor', 'creadaEn'];
                $pdo->exec("DELETE FROM `rutas`");
                $stmt = $pdo->prepare("
                    INSERT INTO `rutas` (`id`, `codigo`, `nombre`, `zona`, `fecha`, `franja`, `vehiculo_id`, `piloto`, `tel_piloto`, `estado`, `pedidos_json`, `notas`, `creada_por`, `creada_en`, `extra_json`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($val as $r) {
                    if (!isset($r['id'])) continue;
                    $extra = [];
                    foreach ($r as $rk => $rv) {
                        if (!in_array($rk, $knownR, true)) {
                            $extra[$rk] = $rv;
                        }
                    }
                    $stmt->execute([
                        $r['id'],
                        $r['codigo'] ?? $r['id'],
                        $r['nombre'] ?? '',
                        $r['zona'] ?? '',
                        $r['fecha'] ?? '',
                        $r['franja'] ?? '',
                        $r['vehiculoId'] ?? '',
                        $r['piloto'] ?? '',
                        $r['telPiloto'] ?? '',
                        $r['estado'] ?? 'Planificada',
                        json_encode($r['pedidos'] ?? [], JSON_UNESCAPED_UNICODE),
                        $r['notas'] ?? '',
                        $r['creadaPor'] ?? '',
                        isoToMysql($r['creadaEn'] ?? null),
                        count($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
                    ]);

                    // Hito P6 / K08 automático si Bodega confirmó carga de la ruta
                    if (is_array($r['carga'] ?? null) && ($r['carga']['estado'] ?? '') === 'Cargada' && !empty($r['pedidos']) && is_array($r['pedidos'])) {
                        $ini = !empty($r['carga']['inicio']) ? strtotime((string)$r['carga']['inicio']) : 0;
                        $fin = !empty($r['carga']['fin']) ? strtotime((string)$r['carga']['fin']) : time();
                        $minEmp = $ini > 0 ? max(1.0, round(($fin - $ini) / 60, 1)) : 22.0;
                        $finSql = date('Y-m-d H:i:s', $fin ?: time());
                        $codigoRuta = (string)($r['codigo'] ?? $r['id']);
                        foreach ($r['pedidos'] as $pedId) {
                            recordTelemetryOnce($pdo, 'empaque', (string)$pedId, 'Bodega', $minEmp <= 30.0 ? 1 : 0, $minEmp, "Carga {$codigoRuta}", null, $finSql);
                        }
                    }
                }
            } elseif ($key === 'df_movs_v1' && is_array($val)) {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO `movimientos_inventario` (`id`, `ts`, `sku`, `tipo`, `delta`, `stock_antes`, `stock_despues`, `motivo`, `ref`, `usuario`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach (array_slice($val, 0, 150) as $m) {
                    if (!isset($m['id'])) continue;
                    $stmt->execute([
                        $m['id'],
                        isoToMysql($m['ts'] ?? null),
                        $m['sku'] ?? '',
                        $m['tipo'] ?? 'ajuste',
                        (int)($m['delta'] ?? 0),
                        (int)($m['stockAntes'] ?? 0),
                        (int)($m['stockDespues'] ?? 0),
                        $m['motivo'] ?? '',
                        $m['ref'] ?? '',
                        $m['usuario'] ?? '',
                    ]);
                }
            } elseif ($key === 'df_roles_v1' && is_array($val)) {
                if (($_SESSION['df_session']['role'] ?? '') !== 'Administrador') {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'Requiere rol Administrador en sesión activa.']);
                    exit;
                }
                $pdo->exec("DELETE FROM `roles` WHERE `base` = 0");
                $stmt = $pdo->prepare("
                    INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `base`, `modulos_json`, `permisos_json`)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `descripcion` = VALUES(`descripcion`),
                        `modulos_json` = VALUES(`modulos_json`),
                        `permisos_json` = VALUES(`permisos_json`)
                ");
                foreach ($val as $rName => $rData) {
                    $stmt->execute([
                        $rName,
                        $rName,
                        $rData['descripcion'] ?? '',
                        !empty($rData['base']) ? 1 : 0,
                        json_encode($rData['modulos'] ?? [], JSON_UNESCAPED_UNICODE),
                        json_encode($rData['permisos'] ?? [], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            } elseif ($key === 'df_users_v1' && is_array($val)) {
                if (($_SESSION['df_session']['role'] ?? '') !== 'Administrador') {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'Requiere rol Administrador en sesión activa.']);
                    exit;
                }
                $pdo->exec("DELETE FROM `usuarios` WHERE `base` = 0");
                $stmt = $pdo->prepare("
                    INSERT INTO `usuarios` (`username`, `hash_clave`, `nombre`, `email`, `rol_id`, `activo`, `base`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        `hash_clave` = VALUES(`hash_clave`),
                        `nombre` = VALUES(`nombre`),
                        `email` = VALUES(`email`),
                        `rol_id` = VALUES(`rol_id`),
                        `activo` = VALUES(`activo`)
                ");
                foreach ($val as $uName => $uData) {
                    $stmt->execute([
                        $uName,
                        $uData['hash'] ?? '',
                        $uData['name'] ?? $uName,
                        $uData['email'] ?? '',
                        $uData['role'] ?? 'Comercial',
                        !empty($uData['active']) ? 1 : 0,
                        !empty($uData['base']) ? 1 : 0,
                    ]);
                }
            } elseif ($key === 'df_metas_v1' && is_array($val)) {
                $stmt = $pdo->prepare("INSERT INTO `kpi_metas` (`id`, `metas_json`) VALUES (1, ?) ON DUPLICATE KEY UPDATE `metas_json` = VALUES(`metas_json`)");
                $stmt->execute([json_encode($val, JSON_UNESCAPED_UNICODE)]);
            }

            echo json_encode(['ok' => true]);
            break;

        case 'audit':
            $user = (string)($_SESSION['df_session']['user'] ?? ($jsonBody['user'] ?? 'sistema'));
            $act  = (string)($jsonBody['auditAction'] ?? 'evento');
            $det  = (string)($jsonBody['detail'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, ?, ?)");
            $stmt->execute([date('Y-m-d H:i:s'), $user, $act, $det]);
            echo json_encode(['ok' => true]);
            break;

        case 'reset_demo':
            if (($_SESSION['df_session']['role'] ?? '') !== 'Administrador') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Requiere rol Administrador en sesión activa.']);
                exit;
            }
            $pdo->exec("TRUNCATE TABLE `pedidos`");
            $pdo->exec("TRUNCATE TABLE `rutas`");
            $pdo->exec("TRUNCATE TABLE `movimientos_inventario`");
            $pdo->exec("TRUNCATE TABLE `telemetria_eventos`");
            $pdo->exec("TRUNCATE TABLE `vehiculos`");
            ensureSchemaAndSeed($pdo, true);
            echo json_encode(['ok' => true]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no reconocida: ' . $action]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

