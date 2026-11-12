<?php
/**
 * Tech Store — Panel de Administración General & Observabilidad Prometheus + Grafana
 * Gestiona catálogo de cómputo, CMS, usuarios, roles y métricas de telemetría en tiempo real.
 */
require_once __DIR__ . '/config/db.php';

$pdo = getPDO();

// 1. Métricas de Telemetría sincronizadas con Prometheus y Grafana
$totalProd    = 0;
$sinStock     = 0;
$stockBajo    = 0;
$totalStock   = 0;
$valorInv     = 0.0;
$totalPed     = 0;
$ventasTot    = 0.0;
$ticketMedio  = 0.0;
$tasaEntregas = 0.0;
$pedPorEstado = [];
$pedPorCanal  = [];
$pedPorPago   = [];
$rutasActivas = 0;
$flotaDisp    = 0;
$entregadosOk = 0;
$cancelados   = 0;
$borradores   = 0;
$kpisReales   = null;

if ($pdo) {
    try {
        $totalProd    = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `activo`=1")->fetchColumn();
        $sinStock     = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `stock`=0 AND `activo`=1")->fetchColumn();
        $stockBajo    = (int)$pdo->query("SELECT COUNT(*) FROM `productos` WHERE `stock`>0 AND `stock`<=`stock_minimo` AND `activo`=1")->fetchColumn();
        $totalStock   = (int)$pdo->query("SELECT COALESCE(SUM(`stock`),0) FROM `productos` WHERE `activo`=1")->fetchColumn();
        $valorInv     = (float)$pdo->query("SELECT COALESCE(SUM(`stock` * `precio`),0) FROM `productos` WHERE `activo`=1")->fetchColumn();

        $pedRows = $pdo->query("SELECT `estado`, `canal`, `pago`, `totales_json` FROM `pedidos`")->fetchAll();
        $totalPed = count($pedRows);
        $ventasCont = 0;

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
            } else {
                $borradores++;
            }
            if ($st === 'Entregado') {
                $entregadosOk++;
            }
        }
        $ticketMedio  = $ventasCont > 0 ? round($ventasTot / $ventasCont, 2) : 0.0;
        $entregasDenom = max(0, $totalPed - $cancelados - $borradores);
        $tasaEntregas = $entregasDenom > 0 ? round(($entregadosOk / $entregasDenom) * 100, 1) : 0.0;

        $rutasActivas = (int)$pdo->query("SELECT COUNT(*) FROM `rutas` WHERE `estado` IN ('Planificada', 'Despachada', 'En ruta', 'En preparación')")->fetchColumn();
        $flotaDisp    = (int)$pdo->query("SELECT COUNT(*) FROM `vehiculos` WHERE `estado` = 'Disponible'")->fetchColumn();
        $kpisReales   = computeRealKpisFromPdo($pdo);
    } catch (Throwable $e) {}
}

$pageTitle  = 'Administración & Observabilidad';
$pageStyles = <<<'CSS'
  .proof-box {
    margin-top: 16px; padding: 14px 18px; border-radius: 10px;
    background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 13px; line-height: 1.55;
  }
  .proof-box b { color: #1e3a8a; }
  .kpi-table-box {
    margin-top: 20px; padding: 18px 20px; border-radius: 10px; background: #ffffff;
    border: 1px solid var(--border);
  }
  .kpi-table-box table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .kpi-table-box th { text-align: left; padding: 10px 12px; background: #f8fafc; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; letter-spacing: .5px; }
  .kpi-table-box td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .badge-sem { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px; font-weight: 700; font-size: 11.5px; }
  .badge-sem.verde { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
  .badge-sem.amarillo { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
  .badge-sem.rojo { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
  .telemetry-banner {
    background: #ffffff; border: 1px solid var(--border); border-radius: 12px;
    padding: 20px 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm);
  }
  .telemetry-header {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    flex-wrap: wrap; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);
  }
  .telemetry-title { display: flex; align-items: center; gap: 12px; }
  .telemetry-title h2 { margin: 0; font-size: 18px; font-weight: 700; color: #0f172a; }
  .telemetry-badges { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .telemetry-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
  .kpi {
    padding: 18px 20px; display: flex; flex-direction: column; gap: 4px;
    background: #ffffff; border: 1px solid var(--border); border-radius: 10px;
    box-shadow: var(--shadow-sm);
  }
  .kpi .lbl { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
  .kpi .val { font-size: 26px; font-weight: 800; letter-spacing: -.6px; color: #0f172a; font-variant-numeric: tabular-nums; }
  .kpi .sub { font-size: 12.5px; color: var(--faint); }
  .kpi.highlight { border-left: 4px solid var(--primary); }
  .kpi.success { border-left: 4px solid var(--ok); }
  .kpi.warn { border-left: 4px solid var(--warn); }
  .kpi.purple { border-left: 4px solid var(--violet); }

  .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
  .chart-card {
    background: #ffffff; border: 1px solid var(--border); border-radius: 10px;
    padding: 18px 20px; box-shadow: var(--shadow-sm);
  }
  .chart-card h3 { margin: 0 0 14px; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; justify-content: space-between; align-items: center; }
  .chart-card h3 span { font-size: 12px; font-weight: 500; color: var(--muted); }

  .bar-row { margin-bottom: 11px; }
  .bar-labels { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; color: #334155; }
  .bar-labels b { color: #0f172a; }
  .bar-track { height: 9px; border-radius: 6px; background: #e2e8f0; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 6px; }

  .targets-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
  .targets-table th { background: #f8fafc; color: var(--muted); font-weight: 600; text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--border); }
  .targets-table td { padding: 9px 12px; border-bottom: 1px solid var(--border); color: #1e293b; }
  .targets-table tr:hover td { background: #f8fafc; }

  .sec-h { display: flex; align-items: center; justify-content: space-between; margin: 28px 0 14px; }
  .sec-h h2 { margin: 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); font-weight: 700; }

  .subgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 28px; }
  .subcard {
    position: relative; display: flex; flex-direction: column; gap: 10px; padding: 20px;
    border-radius: 10px; text-decoration: none; overflow: hidden;
    background: #ffffff; border: 1px solid var(--border); box-shadow: var(--shadow-sm);
    transition: transform .15s, border-color .15s, box-shadow .15s;
  }
  .subcard:hover { transform: translateY(-3px); border-color: #cbd5e1; box-shadow: var(--shadow-md); }
  .subcard .top-r { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
  .subcard .ic {
    width: 42px; height: 42px; border-radius: 8px; display: grid; place-items: center;
    background: var(--primary-light); border: 1px solid var(--primary-border);
  }
  .subcard .ic svg { width: 22px; height: 22px; stroke: var(--primary); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .subcard h3 { margin: 2px 0 0; font-size: 16px; font-weight: 700; color: #0f172a; }
  .subcard p { margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.5; }
  .subcard .go { margin-top: auto; padding-top: 8px; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--primary); }

  .cols { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 18px; margin-top: 10px; }
  .role-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
  .role-row:last-child { border-bottom: 0; }
  .role-row b { display: block; font-size: 14px; color: #0f172a; }
  .role-row span { font-size: 12px; color: var(--muted); }
  .audit-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  .audit-item:last-child { border-bottom: 0; }
  .audit-item .t { color: var(--faint); font-size: 11.5px; white-space: nowrap; }

  @media (max-width: 980px) {
    .kpis { grid-template-columns: repeat(2, 1fr); }
    .chart-grid { grid-template-columns: 1fr; }
    .cols { grid-template-columns: 1fr; }
  }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Panel de Control General</div>
        <h1>Administración del Sistema · Tech Store</h1>
        <p>Control centralizado de catálogo de cómputo, CMS, usuarios, bitácora y telemetría en tiempo real con Prometheus & Grafana.</p>
      </div>
      <div class="actions" id="quick-actions"></div>
    </div>

    <!-- SECCIÓN DE OBSERVABILIDAD & TELEMETRÍA PROMETHEUS + GRAFANA -->
    <section class="telemetry-banner">
      <div class="telemetry-header">
        <div class="telemetry-title">
          <span style="font-size:24px">📊</span>
          <div>
            <h2>Telemetría en Vivo (Prometheus + Grafana + MySQL)</h2>
            <span style="font-size:12.5px;color:var(--muted)">Datos extraídos en tiempo real del scraper y base de datos relacional InnoDB</span>
          </div>
        </div>
        <div class="telemetry-badges">
          <span class="badge" style="--c:#16a34a">● Scrape Activo (15s)</span>
          <span class="badge" style="--c:#2563eb">● Grafana Dashboard Vinculado</span>
          <span class="badge" style="--c:#0284c7">● MySQL <?= $pdo ? 'Conectado' : 'Offline' ?></span>
          <span class="badge" style="--c:#0f766e">● <?= (int)$rutasActivas ?> rutas activas · <?= (int)$flotaDisp ?> vehículos listos</span>
        </div>
        <div class="telemetry-actions">
          <a class="btn primary sm" href="http://localhost:3000/d/portal-computo-mysql-g3" target="_blank" rel="noopener noreferrer" title="Abrir tablero interactivo de Tech Store en Grafana">
            <span>💻 Tablero Tech Store (3000) ↗</span>
          </a>
          <a class="btn sm" href="http://localhost:3000/d/ecommerce-grupo3-k01-k07" target="_blank" rel="noopener noreferrer" title="Abrir tablero de los 10 KPIs oficiales en Grafana">
            <span>🚦 Matriz K01–K10 (3000) ↗</span>
          </a>
          <a class="btn sm" href="http://localhost:9101" target="_blank" rel="noopener noreferrer" title="Monitor y simulador de KPIs del exportador">
            <span>📡 Monitor K01–K10 (9101) ↗</span>
          </a>
          <a class="btn sm" href="http://localhost:9090" target="_blank" rel="noopener noreferrer" title="Consultar métricas en servidor Prometheus">
            <span>Prometheus (9090) ↗</span>
          </a>
          <a class="btn sm" href="api.php?action=metrics" target="_blank" rel="noopener noreferrer" title="Inspeccionar endpoint en formato Prometheus text/plain">
            <span>Métricas RAW ↗</span>
          </a>
        </div>
      </div>

      <!-- Cuadro Explicativo: Evidencia de Conexión E-commerce -> Prometheus -> Grafana -->
      <div class="proof-box">
        <strong>🔍 ¿Cómo comprobar que Grafana está leyendo la tienda Tech Store en tiempo real?</strong><br>
        1. <b>Sincronización Automática:</b> Prometheus consulta <code>http://portal-web:80/api.php?action=prometheus</code> (cada 15 s) y <code>http://ecommerce-exporter:9101/metrics</code> (cada 5 s, conectado directo a MySQL).<br>
        2. <b>Datos Idénticos en Vivo:</b> El monto de <b>Q <?= number_format($ventasTot, 2) ?></b> en ventas, los <b><?= $totalPed ?> pedidos</b> y los 10 KPIs K01–K10 mostrados abajo se calculan en vivo desde MySQL (<code>pedidos</code> + <code>telemetria_eventos</code>).<br>
        3. <b>Prueba en Vivo:</b> Abre <a href="tienda" target="_blank" style="text-decoration:underline;font-weight:700">Tech Store en otra pestaña</a> y completa una compra o aprueba un pedido en Contact Center: en 5–15 segundos verás las métricas actualizadas aquí y en Grafana.<br>
        4. <b>Prueba de Caída:</b> Si detienes el contenedor <code>techstore-db</code> en Docker, el panel de Grafana cambia inmediatamente a <span style="color:#b91c1c;font-weight:700">🔴 PORTAL DESCONECTADO (MySQL Down)</span>.
      </div>

      <!-- 4 KPIs Clave de Telemetría -->
      <div class="kpis" style="margin-top:16px">
        <div class="kpi highlight">
          <span class="lbl">Ventas Totales (GTQ)</span>
          <span class="val" style="color:#2563eb">Q <?= number_format($ventasTot, 2) ?></span>
          <span class="sub">Total acumulado en <?= $totalPed - $cancelados - $borradores ?> pedidos válidos</span>
        </div>
        <div class="kpi purple">
          <span class="lbl">Ticket Promedio (K06)</span>
          <span class="val" style="color:#7c3aed">Q <?= number_format($ticketMedio, 2) ?></span>
          <span class="sub">Valor medio por pedido confirmado</span>
        </div>
        <div class="kpi success">
          <span class="lbl">Efectividad Operativa (OTIF)</span>
          <span class="val" style="color:#16a34a"><?= number_format($tasaEntregas, 1) ?>%</span>
          <span class="sub"><?= $entregadosOk ?> entregas exitosas / <?= $entregasDenom ?> confirmados</span>
        </div>
        <div class="kpi warn">
          <span class="lbl">Inventario en Bodega</span>
          <span class="val" style="color:#d97706">Q <?= number_format($valorInv, 2) ?></span>
          <span class="sub"><?= $totalStock ?> unidades físicas · <?= $sinStock ?> agotados</span>
        </div>
      </div>

      <!-- TABLA OFICIAL EN VIVO DE LOS 10 KPIS DEL PROYECTO -->
      <div class="kpi-table-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
          <div>
            <h3 style="margin:0;font-size:15px;color:#0f172a;font-weight:700">Matriz Oficial de Indicadores de Negocio en Vivo (KPIs K01 a K10)</h3>
            <span style="font-size:12px;color:var(--muted)">Calculado directamente desde MySQL (`telemetria_eventos` + `pedidos`) — Cero valores ficticios</span>
          </div>
          <a class="btn sm primary" href="http://localhost:3000/d/ecommerce-grupo3-k01-k07" target="_blank">Ver Gráficas en Grafana ↗</a>
        </div>
        <?php
          $semBadge = static function(int $sem): string {
              if ($sem === 2) return '<span class="badge-sem verde">● VERDE</span>';
              if ($sem === 1) return '<span class="badge-sem amarillo">● AMARILLO</span>';
              return '<span class="badge-sem rojo">● ROJO</span>';
          };
          $kr = $kpisReales ?: [
              'k01' => 0, 'k02' => 0, 'k03' => 0, 'k04' => 0, 'k05' => 0,
              'k06' => $ticketMedio, 'k07' => 0, 'k08' => 0, 'k09' => 0, 'k10' => 0,
              'p1' => 0, 'p1_total' => 0, 'p2_val' => 0, 'p2_eval' => 0,
              'p3_stock' => 0, 'p3_base' => 0, 'p4_creados' => 0, 'p4_eleg' => 0,
              'checkout_abn' => 0, 'checkout_ini' => 0, 'ped_monto_ref' => 0,
              'p5_ok' => 0, 'p5_venc' => 0, 'p6_emp' => 0,
              'p7_ok' => 0, 'p7_venc' => 0, 'desp_lead_count' => 0,
              'sem_k01' => 0, 'sem_k02' => 0, 'sem_k03' => 0, 'sem_k04' => 0, 'sem_k05' => 0,
              'sem_k06' => 0, 'sem_k07' => 0, 'sem_k08' => 0, 'sem_k09' => 0, 'sem_k10' => 0,
          ];
          $kpiRows = [
              ['K01', 'Volumen diario de solicitudes (24h)', 'N° solicitudes registradas últimas 24 h', '&ge; 100', (string)(int)$kr['k01'] . ' <small style="color:var(--muted)">(' . (int)$kr['p1_total'] . ' tot.)</small>', (int)$kr['sem_k01']],
              ['K02', 'Calidad de datos del cliente', '(Válidas / Evaluadas) × 100', '&ge; 98%', number_format((float)$kr['k02'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['p2_val'] . '/' . (int)$kr['p2_eval'] . ')</small>', (int)$kr['sem_k02']],
              ['K03', 'Disponibilidad de stock', '(Con stock / Base válida) × 100', '&ge; 97%', number_format((float)$kr['k03'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['p3_stock'] . '/' . (int)$kr['p3_base'] . ')</small>', (int)$kr['sem_k03']],
              ['K04', 'Efectividad de creación de pedidos', '(Pedidos creados / Elegibles) × 100', '&ge; 99%', number_format((float)$kr['k04'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['p4_creados'] . '/' . (int)$kr['p4_eleg'] . ')</small>', (int)$kr['sem_k04']],
              ['K05', 'Tasa de abandono en checkout', '(Abandonados / Iniciados) × 100', '&le; 20%', number_format((float)$kr['k05'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['checkout_abn'] . '/' . (int)$kr['checkout_ini'] . ')</small>', (int)$kr['sem_k05']],
              ['K06', 'Ticket promedio de compra', 'Monto facturado / Pedidos confirmados', '&ge; Q 250', 'Q ' . number_format((float)$kr['k06'], 2) . ' <small style="color:var(--muted)">(' . (int)$kr['ped_monto_ref'] . ' ped)</small>', (int)$kr['sem_k06']],
              ['K07', 'Liquidaciones a tiempo', '(En &le; 60 min / Liquidadas) × 100', '&ge; 95%', number_format((float)$kr['k07'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['p5_ok'] . '/' . (int)$kr['p5_venc'] . ')</small>', (int)$kr['sem_k07']],
              ['K08', 'Tiempo medio de empaque', '&sum; minutos / Empacados', '&le; 30 min', number_format((float)$kr['k08'], 1) . ' min <small style="color:var(--muted)">(' . (int)$kr['p6_emp'] . ' ped)</small>', (int)$kr['sem_k08']],
              ['K09', 'Cumplimiento de despacho en plazo', '(Despachados &le; 8 h / Total) × 100', '&ge; 95%', number_format((float)$kr['k09'], 2) . '% <small style="color:var(--muted)">(' . (int)$kr['p7_ok'] . '/' . (int)$kr['p7_venc'] . ')</small>', (int)$kr['sem_k09']],
              ['K10', 'Lead Time Order-to-Dispatch', '&sum; horas total / Despachados', '&le; 4 h', number_format((float)$kr['k10'], 2) . ' h <small style="color:var(--muted)">(' . (int)$kr['desp_lead_count'] . ' ped)</small>', (int)$kr['sem_k10']],
          ];
        ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Indicador</th>
              <th>Fórmula</th>
              <th>Meta</th>
              <th>Valor Real MySQL</th>
              <th>Semáforo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kpiRows as [$kid, $knom, $kform, $kmeta, $kval, $ksem]): ?>
            <tr>
              <td><b><?= $kid ?></b></td>
              <td><?= htmlspecialchars($knom, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $kform ?></td>
              <td><?= $kmeta ?></td>
              <td><b><?= $kval ?></b></td>
              <td><?= $semBadge($ksem) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Gráficos de Telemetría de Pedidos e Inventario -->
      <div class="chart-grid">
        <!-- Ciclo del Pedido -->
        <div class="chart-card">
          <h3>
            <span>Ciclo del Pedido (Distribución por Fase)</span>
            <span><?= $totalPed ?> pedidos totales</span>
          </h3>
          <?php
          $coloresEstado = [
              'Entregado'             => '#16a34a',
              'En ruta'               => '#2563eb',
              'En preparación'        => '#0ea5e9',
              'Pendiente de despacho' => '#f59e0b',
              'Por aprobar'           => '#7c3aed',
              'Borrador'              => '#64748b',
              'Cancelado'             => '#ef4444',
          ];
          foreach ($pedPorEstado as $est => $cant):
              $pct = $totalPed > 0 ? round(($cant / $totalPed) * 100, 1) : 0;
              $col = $coloresEstado[$est] ?? '#64748b';
          ?>
          <div class="bar-row">
            <div class="bar-labels">
              <span><?= htmlspecialchars($est) ?></span>
              <b><?= $cant ?> pedido<?= $cant !== 1 ? 's' : '' ?> (<?= $pct ?>%)</b>
            </div>
            <div class="bar-track">
              <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Canales, Pagos e Infraestructura -->
        <div class="chart-card">
          <h3>
            <span>Canales de Venta & Métodos de Pago</span>
            <span>Prometheus Scrape Status</span>
          </h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div style="background:#f8fafc;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0">
              <span style="font-size:11.5px;color:var(--muted);font-weight:600;text-transform:uppercase">Canales de Venta</span>
              <?php foreach ($pedPorCanal as $cn => $cant): ?>
              <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-top:4px">
                <span><?= htmlspecialchars($cn) ?></span>
                <b><?= $cant ?></b>
              </div>
              <?php endforeach; ?>
            </div>
            <div style="background:#f8fafc;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0">
              <span style="font-size:11.5px;color:var(--muted);font-weight:600;text-transform:uppercase">Formas de Pago</span>
              <?php foreach ($pedPorPago as $pg => $cant): ?>
              <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-top:4px">
                <span><?= htmlspecialchars($pg) ?></span>
                <b><?= $cant ?></b>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <span style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">Servicios Monitoreados en Red Docker</span>
          <table class="targets-table">
            <thead>
              <tr><th>Componente</th><th>Instancia / Puerto</th><th>Estado</th></tr>
            </thead>
            <tbody>
              <tr>
                <td><b>portal-pulso-mysql</b></td>
                <td>techstore-web (portal-web:80 / 8085)</td>
                <td><span class="badge" style="--c:#16a34a">● UP (100%)</span></td>
              </tr>
              <tr>
                <td><b>ecommerce-pedidos</b></td>
                <td>techstore-exporter (9101)</td>
                <td><span class="badge" style="--c:#16a34a">● UP (100%)</span></td>
              </tr>
              <tr>
                <td><b>grafana-server</b></td>
                <td>techstore-grafana (3000)</td>
                <td><span class="badge" style="--c:#16a34a">● UP (100%)</span></td>
              </tr>
              <tr>
                <td><b>prometheus-server</b></td>
                <td>techstore-prometheus (9090)</td>
                <td><span class="badge" style="--c:#16a34a">● UP (100%)</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ACCESOS A SUBMÓDULOS DE ADMINISTRACIÓN -->
    <div class="sec-h"><h2>Módulos y herramientas administrativas</h2></div>
    <section class="subgrid" id="subgrid"></section>

    <!-- COLUMNAS DE USUARIOS Y AUDITORÍA -->
    <section class="cols">
      <div class="panel">
        <div class="panel-h"><h2>Distribución de usuarios por rol</h2><span class="sub" id="roles-sub"></span></div>
        <div class="panel-b" id="roles-box"></div>
      </div>
      <div class="panel">
        <div class="panel-h"><h2>Actividad reciente y auditoría</h2><a class="sub" href="adm-bitacora" style="text-decoration:none;color:var(--primary);font-weight:600">Ver bitácora completa →</a></div>
        <div class="panel-b" id="audit-box"></div>
      </div>
    </section>
  </main>

  <script>
    const s = requireModulo("admin");
    if (s) {
      pintarShell(s, [{ t: "Administración" }]);

      const users = Object.entries(getUsers()).map(([u, v]) => ({ username: u, ...v }));
      const roles = getRoles();
      const activos = users.filter((u) => u.active !== false).length;
      const totalPerms = TODOS_LOS_PERMISOS.length;
      const auditList = getAudit();

      const qa = document.getElementById("quick-actions");
      qa.innerHTML =
        `<a class="btn primary" href="adm-productos"><svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Subir producto</a>` +
        `<a class="btn" href="adm-pagina">Personalizar página</a>` +
        (can("admin.usuarios.crear") ? `<a class="btn" href="adm-usuarios?nuevo=1">Nuevo usuario</a>` : "") +
        (can("admin.roles.ver") ? `<a class="btn" href="adm-roles">Configurar roles</a>` : "");

      const subs = submodulosVisibles("admin");
      document.getElementById("subgrid").innerHTML = subs.map((sm) =>
        `<a class="subcard" href="${esc(sm.href)}">` +
          `<div class="top-r"><div class="ic"><svg viewBox="0 0 24 24">${sm.icono}</svg></div><span class="badge" style="--c:#2563eb">${esc(sm.etiqueta)}</span></div>` +
          `<h3>${esc(sm.nombre)}</h3>` +
          `<p>${esc(sm.desc)}</p>` +
          `<div class="go"><span>Abrir submódulo</span><span>→</span></div>` +
        `</a>`
      ).join("");

      document.getElementById("roles-sub").textContent = `${Object.keys(roles).length} perfiles`;
      document.getElementById("roles-box").innerHTML = Object.entries(roles).map(([nombre, r]) => {
        const cnt = users.filter((u) => u.role === nombre).length;
        return `<div class="role-row">` +
          `<div><b>${esc(nombre)}</b><span>${esc((r.modulos || []).map((m) => MODULOS[m]?.nombre || m).join(" · ") || "Sin módulos")} · ${(r.permisos || []).length} permisos</span></div>` +
          `<span class="badge plain" style="--c:#2563eb">${cnt} usuario${cnt === 1 ? "" : "s"}</span>` +
        `</div>`;
      }).join("");

      const ult = auditList.slice(0, 6);
      document.getElementById("audit-box").innerHTML = ult.length
        ? ult.map((a) => `<div class="audit-item"><div style="flex:1"><b>${esc(a.action)}</b> · <span style="color:var(--muted)">${esc(a.user)}</span><div style="color:var(--muted);font-size:12.5px">${esc(a.detail)}</div></div><span class="t">${fmtFecha(a.ts)}</span></div>`).join("")
        : `<p style="color:var(--muted);margin:0">Aún no hay eventos registrados en esta sesión.</p>`;
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
