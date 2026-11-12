<?php
/**
 * DigyFenix | Pulso — Encabezado Único Compartido (includes/header.php)
 * Incluido en todas las vistas del portal y tienda electrónica.
 */
require_once __DIR__ . '/../config/db.php';

$PULSO_SNAP = getPulsoSnapshot();
$SITE_CFG   = $PULSO_SNAP['config'] ?? getSiteConfig();

$empresaNombre  = htmlspecialchars($SITE_CFG['empresa'] ?? 'Tech Store', ENT_QUOTES, 'UTF-8');
$productoNombre = htmlspecialchars($SITE_CFG['producto'] ?? 'Portal', ENT_QUOTES, 'UTF-8');
$colorPrimario  = htmlspecialchars($SITE_CFG['color_primario'] ?? '#2563eb', ENT_QUOTES, 'UTF-8');

$pageLayout = $pageLayout ?? 'default';
$fullTitle  = isset($pageTitle) && $pageTitle !== ''
    ? "{$empresaNombre} | {$productoNombre} · " . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')
    : "{$empresaNombre} | {$productoNombre}";
$pageCss    = $pageCss ?? [];
$pageLibs   = $pageLibs ?? [];
$pageStyles = $pageStyles ?? '';
$includeDarkCss = $includeDarkCss ?? true;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $fullTitle ?></title>
<link rel="icon" href="assets/logo-icono.png">
<?php if ($includeDarkCss): ?>
<link rel="stylesheet" href="css/dark.css">
<?php endif; ?>
<?php foreach ($pageCss as $cssFile): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($cssFile, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<style>
  :root {
    --orange: <?= $colorPrimario ?>;
  }
  .top-promo-bar {
    background: #eff6ff;
    border-bottom: 1px solid #dbeafe;
    color: #1e40af;
    font-size: 13px;
    font-weight: 500;
    text-align: center;
    padding: 8px 16px;
    letter-spacing: .2px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .top-promo-bar .dot-live {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #16a34a;
    display: inline-block;
  }
  footer.foot {
    margin-top: auto;
    padding: 24px clamp(16px, 3vw, 40px);
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
    font-size: 13px;
    color: #64748b;
  }
  footer.foot .foot-inner {
    max-width: 1380px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
  }
  footer.foot .foot-links {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }
  footer.foot .foot-links a {
    color: #475569;
    text-decoration: none;
    font-weight: 500;
  }
  footer.foot .foot-links a:hover {
    color: #0f172a;
    text-decoration: underline;
  }
<?= $pageStyles ?>
</style>
<script>
window.__PULSO_DB__ = <?= json_encode($PULSO_SNAP, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
<?php if (!empty($_SESSION['df_session']) && is_array($_SESSION['df_session'])): ?>
window.__PHP_SESSION__ = <?= json_encode($_SESSION['df_session'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
try {
  sessionStorage.setItem("df_session", JSON.stringify(window.__PHP_SESSION__));
} catch (e) {}
<?php endif; ?>
</script>
<script src="js/session.js"></script>
<?php if ($pageLayout !== 'login'): ?>
<script src="js/data.js"></script>
<script src="js/shell.js"></script>
<?php endif; ?>
<?php foreach ($pageLibs as $jsFile): ?>
<script src="<?= htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</head>
<body>
<?php if ($pageLayout === 'login'): ?>
<div class="page page-login">
<?php elseif ($pageLayout === 'inicio'): ?>
<div class="page">
  <header class="top">
    <div class="brand">
      <div class="logo-chip">💻</div>
      <div class="title">
        <b><?= $empresaNombre ?></b>
        <span><?= $productoNombre ?> · Centro de operaciones</span>
      </div>
    </div>
    <div class="who">
      <?php if (!empty($PULSO_SNAP['connected'])): ?>
      <span class="role" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0">● MySQL Activo</span>
      <?php endif; ?>
      <a class="btn sm" href="tienda" style="text-decoration:none">Ver Tienda →</a>
      <span class="name" id="who-name"></span>
      <span class="role" id="who-role"></span>
      <button class="btn sm" id="logout" type="button">Cerrar sesión</button>
    </div>
  </header>
<?php else: ?>
<div class="page">
  <?php if ($pageLayout === 'tienda' && ($SITE_CFG['anuncio_activo'] ?? '1') === '1' && !empty($SITE_CFG['anuncio_texto'])): ?>
  <div class="top-promo-bar">
    <span class="dot-live"></span>
    <span><?= htmlspecialchars($SITE_CFG['anuncio_texto'], ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <?php endif; ?>
  <header class="top" id="top"></header>
<?php endif; ?>

