<?php
/**
 * DigyFenix | Pulso — Pie de Página Único Compartido (includes/footer.php)
 * Incluido al cierre de todas las vistas del portal y tienda electrónica.
 */
$pieTexto   = htmlspecialchars($SITE_CFG['pie_pagina'] ?? 'Tech Store Guatemala — Cómputo, Hardware & Observabilidad', ENT_QUOTES, 'UTF-8');
$telSoporte = htmlspecialchars($SITE_CFG['telefono_soporte'] ?? '+502 2290-4000', ENT_QUOTES, 'UTF-8');
$corSoporte = htmlspecialchars($SITE_CFG['correo_soporte'] ?? 'contacto@techstore.com.gt', ENT_QUOTES, 'UTF-8');
$dbActiva   = !empty($PULSO_SNAP['connected']);
?>
<?php if (($pageLayout ?? 'default') !== 'login'): ?>
  <footer class="foot">
    <div class="foot-inner">
      <div>© <span id="year"><?= date('Y') ?></span> <?= $pieTexto ?></div>
      <div class="foot-links">
        <span>Atención: <?= $telSoporte ?> · <?= $corSoporte ?></span>
        <a href="tienda">Tienda en línea</a>
        <a href="inicio">Panel de Operaciones</a>
        <a href="adm-productos">Catálogo Admin</a>
        <span style="color:<?= $dbActiva ? '#16a34a' : '#d97706' ?>">● <?= $dbActiva ? 'MySQL Conectado' : 'Modo Local' ?></span>
      </div>
    </div>
  </footer>
</div>
<?php endif; ?>
</body>
</html>

