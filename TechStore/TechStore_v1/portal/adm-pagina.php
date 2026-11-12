<?php
/**
 * DigyFenix | Pulso — Panel de Personalización de Página y Parámetros (CMS MySQL)
 * Permite modificar en tiempo real los textos de la tienda, cintillo promocional,
 * parámetros comerciales (IVA, envío gratis, tarifa base), colores y datos de contacto.
 */
require_once __DIR__ . '/config/db.php';

$pdo = getPDO();
$flashMsg  = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $sessRole = $_SESSION['df_session']['role'] ?? '';
    $sessUser = $_SESSION['df_session']['user'] ?? '';
    if ($sessRole !== 'Administrador' || $sessUser === '') {
        http_response_code(403);
        $flashMsg  = 'Acceso denegado: inicia sesión como Administrador para guardar cambios en CMS.';
        $flashType = 'bad';
    } else {
        $postAction = $_POST['form_action'] ?? '';
        try {
            if ($postAction === 'save_cms') {
                $keys = [
                    'empresa', 'producto', 'lema', 'color_primario',
                    'telefono_soporte', 'correo_soporte', 'pie_pagina',
                    'anuncio_texto', 'hero_eyebrow', 'hero_titulo_1',
                    'hero_titulo_acento', 'hero_subtitulo',
                    'perk_1', 'perk_2', 'perk_3',
                    'iva_porcentaje', 'envio_base', 'envio_gratis_desde'
                ];
                $stmt = $pdo->prepare("
                    INSERT INTO `configuracion_sitio` (`clave`, `valor`)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)
                ");
                foreach ($keys as $k) {
                    if (isset($_POST[$k])) {
                        $stmt->execute([$k, trim((string)$_POST[$k])]);
                    }
                }
                $anuncioActivo = isset($_POST['anuncio_activo']) ? '1' : '0';
                $stmt->execute(['anuncio_activo', $anuncioActivo]);

                $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'pagina.configurar', ?)");
                $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser, 'Actualización de textos de la página, portada de tienda y reglas comerciales en MySQL.']);
                $flashMsg = 'Los cambios de la página y parámetros comerciales se guardaron en MySQL y ya están activos en todo el portal.';
            } elseif ($postAction === 'restore_cms_defaults') {
                $pdo->exec("TRUNCATE TABLE `configuracion_sitio`");
                seedInitialDatabase($pdo);
                $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'pagina.restaurar', 'Restauración de textos predeterminados de fábrica en CMS')");
                $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser]);
                $flashMsg = 'Se restauraron los textos y valores predeterminados de fábrica.';
            }
        } catch (Throwable $e) {
            $flashMsg  = $e->getMessage();
            $flashType = 'bad';
        }
    }
}

$cfg = getSiteConfig();
$pageTitle = 'Administración · Personalizar Página';
$pageStyles = <<<'CSS'
  .cms-layout { display: grid; grid-template-columns: minmax(0, 1fr) 420px; gap: 20px; align-items: start; }
  .cms-col { display: flex; flex-direction: column; gap: 18px; }
  .cms-side { position: sticky; top: 82px; display: flex; flex-direction: column; gap: 18px; }
  .live-preview {
    padding: 20px; border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  .live-preview .pv-promo {
    margin: -20px -20px 16px; padding: 8px 12px; font-size: 12px; font-weight: 600;
    background: #eff6ff; border-bottom: 1px solid #dbeafe;
    color: #1e40af; text-align: center; border-radius: 11px 11px 0 0;
  }
  .live-preview .pv-eye { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #2563eb; }
  .live-preview h3 { margin: 6px 0 8px; font-size: 22px; line-height: 1.2; font-weight: 800; color: #0f172a; }
  .live-preview h3 span { color: #2563eb; }
  .live-preview p { margin: 0; font-size: 13.5px; color: #475569; line-height: 1.5; }
  .live-preview .pv-perks { display: flex; flex-direction: column; gap: 6px; margin-top: 14px; font-size: 13px; color: #334155; }
  .live-preview .pv-perks span::before { content: "✓ "; color: #16a34a; font-weight: 800; }
  .flash-banner {
    padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
  }
  .flash-banner.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
  .flash-banner.bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
  @media (max-width: 1100px) { .cms-layout { grid-template-columns: 1fr; } .cms-side { position: static; } }
CSS;

require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Administración · Gestor de Contenido (CMS)</div>
        <h1>Personalizar página y parámetros</h1>
        <p>Modifica textos de la tienda en línea, cintillo promocional, reglas de envío gratis, IVA e identidad corporativa.</p>
      </div>
      <div class="actions">
        <a class="btn" href="admin"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Administración</a>
        <a class="btn" href="adm-productos">Ir a Productos</a>
        <a class="btn primary" href="tienda" target="_blank">Ver Tienda en vivo ↗</a>
      </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
    <div class="flash-banner <?= $flashType ?>">
      <span><?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?></span>
      <button class="btn sm ghost" type="button" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <form method="post" action="adm-pagina" id="cms-form">
      <input type="hidden" name="form_action" value="save_cms">
      <div class="cms-layout">
        <div class="cms-col">

          <!-- 1. Portada de la Tienda -->
          <section class="panel">
            <div class="panel-h"><span class="n">1</span><h2>Portada de la tienda y cintillo promocional</h2><span class="sub">Se refleja en /tienda</span></div>
            <div class="panel-b">
              <div class="grid-form">
                <div class="field c12">
                  <label class="check">
                    <input type="checkbox" name="anuncio_activo" id="f-anuncio-on" value="1" <?= ($cfg['anuncio_activo'] ?? '1') === '1' ? 'checked' : '' ?>>
                    Mostrar cintillo promocional en la parte superior de la tienda
                  </label>
                </div>
                <div class="field c12">
                  <label>Texto del cintillo promocional</label>
                  <input class="inp" name="anuncio_texto" id="f-anuncio-txt" value="<?= htmlspecialchars($cfg['anuncio_texto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c12">
                  <label>Encabezado superior (Eyebrow)</label>
                  <input class="inp" name="hero_eyebrow" id="f-eye" value="<?= htmlspecialchars($cfg['hero_eyebrow'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c7">
                  <label>Título principal de bienvenida</label>
                  <input class="inp" name="hero_titulo_1" id="f-t1" value="<?= htmlspecialchars($cfg['hero_titulo_1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c5">
                  <label>Frase destacada en degradado</label>
                  <input class="inp" name="hero_titulo_acento" id="f-ta" value="<?= htmlspecialchars($cfg['hero_titulo_acento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c12">
                  <label>Subtítulo descriptivo de la portada</label>
                  <textarea class="inp" name="hero_subtitulo" id="f-sub" rows="2"><?= htmlspecialchars($cfg['hero_subtitulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="field c4">
                  <label>Beneficio destacado 1</label>
                  <input class="inp" name="perk_1" id="f-p1" value="<?= htmlspecialchars($cfg['perk_1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Beneficio destacado 2</label>
                  <input class="inp" name="perk_2" id="f-p2" value="<?= htmlspecialchars($cfg['perk_2'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Beneficio destacado 3</label>
                  <input class="inp" name="perk_3" id="f-p3" value="<?= htmlspecialchars($cfg['perk_3'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
            </div>
          </section>

          <!-- 2. Reglas comerciales, impuestos y envíos -->
          <section class="panel">
            <div class="panel-h"><span class="n">2</span><h2>Parámetros comerciales, IVA y tarifas de envío</h2><span class="sub">Cálculo automático en carrito y pedidos</span></div>
            <div class="panel-b">
              <div class="grid-form">
                <div class="field c4">
                  <label>Impuesto IVA (%)</label>
                  <input class="inp" type="number" step="0.5" min="0" max="50" name="iva_porcentaje" value="<?= htmlspecialchars($cfg['iva_porcentaje'] ?? '12', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Costo base de envío sin IVA (Q)</label>
                  <input class="inp" type="number" step="1" min="0" name="envio_base" value="<?= htmlspecialchars($cfg['envio_base'] ?? '35', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Envío gratis a partir de (Q)</label>
                  <input class="inp" type="number" step="10" min="0" name="envio_gratis_desde" value="<?= htmlspecialchars($cfg['envio_gratis_desde'] ?? '500', ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
            </div>
          </section>

          <!-- 3. Identidad corporativa y pie de página -->
          <section class="panel">
            <div class="panel-h"><span class="n">3</span><h2>Identidad corporativa y contacto</h2><span class="sub">Encabezado y pie de página global</span></div>
            <div class="panel-b">
              <div class="grid-form">
                <div class="field c4">
                  <label>Nombre de la empresa</label>
                  <input class="inp" name="empresa" value="<?= htmlspecialchars($cfg['empresa'] ?? 'Tech Store', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Nombre del sistema</label>
                  <input class="inp" name="producto" value="<?= htmlspecialchars($cfg['producto'] ?? 'Portal', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c4">
                  <label>Color de acento principal</label>
                  <input class="inp" type="color" name="color_primario" id="f-color" value="<?= htmlspecialchars($cfg['color_primario'] ?? '#2563eb', ENT_QUOTES, 'UTF-8') ?>" style="padding:4px;height:42px">
                </div>
                <div class="field c12">
                  <label>Lema del sistema</label>
                  <input class="inp" name="lema" value="<?= htmlspecialchars($cfg['lema'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c6">
                  <label>Teléfono de soporte</label>
                  <input class="inp" name="telefono_soporte" value="<?= htmlspecialchars($cfg['telefono_soporte'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c6">
                  <label>Correo de atención al cliente</label>
                  <input class="inp" name="correo_soporte" type="email" value="<?= htmlspecialchars($cfg['correo_soporte'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field c12">
                  <label>Texto de derechos reservados en el pie de página (Footer)</label>
                  <input class="inp" name="pie_pagina" value="<?= htmlspecialchars($cfg['pie_pagina'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- Columna lateral: Vista previa en vivo y guardado -->
        <aside class="cms-side">
          <section class="panel">
            <div class="panel-h"><h2>Vista previa en tiempo real</h2><span class="sub">Portada tienda</span></div>
            <div class="panel-b">
              <div class="live-preview">
                <div class="pv-promo" id="pv-promo"></div>
                <div class="pv-eye" id="pv-eye"></div>
                <h3><b id="pv-t1" style="font-weight:800"></b> <span id="pv-ta"></span>.</h3>
                <p id="pv-sub"></p>
                <div class="pv-perks">
                  <span id="pv-p1"></span>
                  <span id="pv-p2"></span>
                  <span id="pv-p3"></span>
                </div>
              </div>

              <div style="display:flex;flex-direction:column;gap:10px;margin-top:18px">
                <button class="btn primary" type="submit" style="height:44px;font-size:15px">Guardar cambios en MySQL</button>
                <button class="btn ghost" type="button" id="btn-restore">Restaurar textos originales</button>
              </div>
            </div>
          </section>
        </aside>
      </div>
    </form>
  </main>

  <script>
    const s = requireModulo("admin");
    if (s) {
      pintarShell(s, [{ t: "Administración", href: "admin" }, { t: "Personalizar página" }]);

      const syncPreview = () => {
        const promoOn = document.getElementById("f-anuncio-on").checked;
        const promoEl = document.getElementById("pv-promo");
        promoEl.hidden = !promoOn;
        promoEl.textContent = document.getElementById("f-anuncio-txt").value;
        document.getElementById("pv-eye").textContent = document.getElementById("f-eye").value;
        document.getElementById("pv-t1").textContent = document.getElementById("f-t1").value;
        document.getElementById("pv-ta").textContent = document.getElementById("f-ta").value;
        document.getElementById("pv-sub").textContent = document.getElementById("f-sub").value;
        document.getElementById("pv-p1").textContent = document.getElementById("f-p1").value;
        document.getElementById("pv-p2").textContent = document.getElementById("f-p2").value;
        document.getElementById("pv-p3").textContent = document.getElementById("f-p3").value;
        document.documentElement.style.setProperty("--orange", document.getElementById("f-color").value);
      };

      document.getElementById("cms-form").addEventListener("input", syncPreview);
      document.getElementById("cms-form").addEventListener("change", syncPreview);
      syncPreview();

      document.getElementById("btn-restore").addEventListener("click", async () => {
        if (await confirmar("Restaurar textos originales", "¿Deseas restablecer los textos y valores predeterminados de fábrica?", "Restaurar")) {
          const f = document.createElement("form");
          f.method = "post";
          f.action = "adm-pagina";
          f.innerHTML = '<input type="hidden" name="form_action" value="restore_cms_defaults">';
          document.body.appendChild(f);
          f.submit();
        }
      });
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

