<?php
/**
 * DigyFenix | Pulso — Panel de Administración de Productos y Catálogo (MySQL)
 * Permite crear, editar, subir fotografías, ajustar stock, configurar peso/volumen (kg/m³)
 * y administrar categorías directamente en la base de datos MySQL.
 */
require_once __DIR__ . '/config/db.php';

$pdo = getPDO();
$flashMsg  = '';
$flashType = 'ok';

// Asegurar directorio de subida de imágenes de productos
$uploadDir = __DIR__ . '/uploads/productos';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $sessRole = $_SESSION['df_session']['role'] ?? '';
    $sessUser = $_SESSION['df_session']['user'] ?? '';
    $sessName = $_SESSION['df_session']['name'] ?? $sessUser;

    if ($sessRole !== 'Administrador' || $sessUser === '') {
        http_response_code(403);
        $flashMsg  = 'Acceso denegado: inicia sesión como Administrador para modificar el catálogo.';
        $flashType = 'bad';
    } else {
        $postAction = $_POST['form_action'] ?? '';

        try {
            if ($postAction === 'save_product') {
                $origSku     = trim((string)($_POST['orig_sku'] ?? ''));
                $sku         = strtoupper(trim((string)($_POST['sku'] ?? '')));
                $nombre      = trim((string)($_POST['nombre'] ?? ''));
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));
                $categoria   = trim((string)($_POST['categoria'] ?? 'Laptops y Portátiles'));
                $precio      = max(0.01, (float)($_POST['precio'] ?? 0));
                $stock       = max(0, (int)($_POST['stock'] ?? 0));
                $stockMin    = max(0, (int)($_POST['stock_minimo'] ?? 8));
                $kg          = max(0.05, (float)($_POST['kg'] ?? 1.0));
                $m3          = max(0.001, (float)($_POST['m3'] ?? 0.010));
                $destacado   = isset($_POST['destacado']) ? 1 : 0;
                $quitarImg   = isset($_POST['quitar_imagen']) && $_POST['quitar_imagen'] === '1';

                if ($sku === '' || $nombre === '') {
                    throw new RuntimeException('El código SKU y el nombre del producto son obligatorios.');
                }

                // Consultar producto previo si existe
                $stmtPrev = $pdo->prepare("SELECT * FROM `productos` WHERE `sku` = ?");
                $stmtPrev->execute([$origSku !== '' ? $origSku : $sku]);
                $prevRow = $stmtPrev->fetch();

                $imagenPath = $prevRow['imagen'] ?? null;
                if ($quitarImg) {
                    $imagenPath = null;
                }

                // Si se ingresó URL externa o ruta manual
                $imgUrlInput = trim((string)($_POST['imagen_url'] ?? ''));
                if ($imgUrlInput !== '' && !$quitarImg) {
                    $imagenPath = $imgUrlInput;
                }

                // Procesar archivo subido si viene en $_FILES['imagen_file']
                if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['imagen_file']['tmp_name'];
                    $origName = basename((string)$_FILES['imagen_file']['name']);
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
                    if (!in_array($ext, $allowed, true)) {
                        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG, WEBP o SVG.');
                    }
                    $safeSku = preg_replace('/[^a-zA-Z0-9_-]/', '', $sku);
                    $fileName = strtolower($safeSku) . '-' . time() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $fileName;
                    if (move_uploaded_file($tmpName, $destPath)) {
                        $imagenPath = 'uploads/productos/' . $fileName;
                    }
                }

                if ($origSku !== '' && $prevRow) {
                    $stmtUpd = $pdo->prepare("
                        UPDATE `productos`
                        SET `sku` = ?, `nombre` = ?, `descripcion` = ?, `categoria` = ?,
                            `precio` = ?, `stock` = ?, `stock_minimo` = ?, `kg` = ?, `m3` = ?,
                            `imagen` = ?, `destacado` = ?, `activo` = 1
                        WHERE `sku` = ?
                    ");
                    $stmtUpd->execute([
                        $sku, $nombre, $descripcion, $categoria,
                        $precio, $stock, $stockMin, $kg, $m3,
                        $imagenPath, $destacado, $origSku
                    ]);

                    $oldStock = (int)$prevRow['stock'];
                    if ($stock !== $oldStock) {
                        $delta = $stock - $oldStock;
                        $movId = 'MOV-' . round(microtime(true) * 1000) . '-' . random_int(100, 999);
                        $stmtMov = $pdo->prepare("
                            INSERT INTO `movimientos_inventario` (`id`, `ts`, `sku`, `tipo`, `delta`, `stock_antes`, `stock_despues`, `motivo`, `ref`, `usuario`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtMov->execute([
                            $movId, date('Y-m-d H:i:s'), $sku,
                            $delta > 0 ? 'entrada' : 'ajuste',
                            $delta, $oldStock, $stock,
                            'Actualización desde Panel de Productos', 'ADM-PROD', $sessName
                        ]);
                    }

                    $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'producto.editar', ?)");
                    $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser, "{$sku} · {$nombre} (Q " . number_format($precio, 2) . " · Stock {$stock})"]);
                    $flashMsg = "Producto «{$nombre}» ({$sku}) actualizado en MySQL.";
                } else {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO `productos` (`sku`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `stock_minimo`, `kg`, `m3`, `imagen`, `destacado`, `activo`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE
                            `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`), `categoria` = VALUES(`categoria`),
                            `precio` = VALUES(`precio`), `stock` = VALUES(`stock`), `stock_minimo` = VALUES(`stock_minimo`),
                            `kg` = VALUES(`kg`), `m3` = VALUES(`m3`), `imagen` = VALUES(`imagen`), `destacado` = VALUES(`destacado`), `activo` = 1
                    ");
                    $stmtIns->execute([
                        $sku, $nombre, $descripcion, $categoria,
                        $precio, $stock, $stockMin, $kg, $m3,
                        $imagenPath, $destacado
                    ]);

                    if ($stock > 0) {
                        $movId = 'MOV-' . round(microtime(true) * 1000) . '-' . random_int(100, 999);
                        $stmtMov = $pdo->prepare("
                            INSERT INTO `movimientos_inventario` (`id`, `ts`, `sku`, `tipo`, `delta`, `stock_antes`, `stock_despues`, `motivo`, `ref`, `usuario`)
                            VALUES (?, ?, ?, 'entrada', ?, 0, ?, 'Stock inicial al dar de alta producto', 'ALTA-SKU', ?)
                        ");
                        $stmtMov->execute([$movId, date('Y-m-d H:i:s'), $sku, $stock, $stock, $sessName]);
                    }

                    $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'producto.crear', ?)");
                    $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser, "Alta de producto {$sku} · {$nombre} (Q " . number_format($precio, 2) . ")"]);
                    $flashMsg = "Nuevo producto «{$nombre}» ({$sku}) registrado exitosamente en MySQL.";
                }
            } elseif ($postAction === 'delete_product') {
                $delSku = trim((string)($_POST['sku'] ?? ''));
                if ($delSku !== '') {
                    $stmtDel = $pdo->prepare("DELETE FROM `productos` WHERE `sku` = ?");
                    $stmtDel->execute([$delSku]);
                    $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'producto.eliminar', ?)");
                    $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser, "Producto eliminado de catálogo: {$delSku}"]);
                    $flashMsg = "Producto {$delSku} eliminado del catálogo.";
                }
            } elseif ($postAction === 'save_category') {
                $catNombre = trim((string)($_POST['cat_nombre'] ?? ''));
                $catColor  = trim((string)($_POST['cat_color'] ?? '#2563eb'));
                $catIcono  = trim((string)($_POST['cat_icono'] ?? '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/>'));
                if ($catNombre === '') {
                    throw new RuntimeException('Escribe el nombre de la categoría.');
                }
                $stmtCat = $pdo->prepare("
                    INSERT INTO `categorias` (`nombre`, `color`, `icono`, `orden`, `activo`)
                    VALUES (?, ?, ?, 10, 1)
                    ON DUPLICATE KEY UPDATE `color` = VALUES(`color`), `icono` = VALUES(`icono`), `activo` = 1
                ");
                $stmtCat->execute([$catNombre, $catColor, $catIcono]);
                $stmtBit = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, 'categoria.guardar', ?)");
                $stmtBit->execute([date('Y-m-d H:i:s'), $sessUser, "Categoría guardada: {$catNombre} ({$catColor})"]);
                $flashMsg = "Categoría «{$catNombre}» guardada en MySQL.";
            }
        } catch (Throwable $e) {
            $flashMsg  = $e->getMessage();
            $flashType = 'bad';
        }
    }
}

$pageTitle = 'Administración · Catálogo y Productos';
$pageStyles = <<<'CSS'
  .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
  .kpi { padding: 16px 18px; display: flex; flex-direction: column; gap: 4px; }
  .kpi .lbl { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .7px; }
  .kpi .val { font-size: 26px; font-weight: 800; letter-spacing: -.7px; font-variant-numeric: tabular-nums; }
  .kpi .sub { font-size: 12.5px; color: var(--faint); }

  .filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
  .filters .search { flex: 1 1 280px; }
  .chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .chip-f { height: 34px; padding: 0 13px; border-radius: 999px; border: 1px solid var(--border); background: #ffffff; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; }
  .chip-f:hover { color: #0f172a; background: #f1f5f9; border-color: #cbd5e1; }
  .chip-f.on { color: #ffffff; background: #2563eb; border-color: #2563eb; }

  .prod-thumb {
    width: 48px; height: 48px; border-radius: 13px; overflow: hidden; flex: none;
    display: grid; place-items: center; position: relative;
    background: color-mix(in srgb, var(--c, #2563eb) 14%, #ffffff);
    border: 1px solid color-mix(in srgb, var(--c, #2563eb) 35%, transparent);
  }
  .prod-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .prod-thumb svg { width: 22px; height: 22px; stroke: color-mix(in srgb, var(--c, #2563eb) 85%, #000); fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

  .preview-box {
    height: 150px; border-radius: 16px; border: 1px dashed var(--border-2);
    background: #f8fafc; display: grid; place-items: center; overflow: hidden; position: relative;
  }
  .preview-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .row-acts { display: flex; gap: 6px; justify-content: flex-end; }
  .flash-banner {
    padding: 14px 18px; border-radius: 14px; margin-bottom: 18px; font-weight: 600;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
  }
  .flash-banner.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
  .flash-banner.bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
  @media (max-width: 960px) { .kpis { grid-template-columns: repeat(2, 1fr); } }
CSS;

require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Administración · Base de Datos MySQL</div>
        <h1>Catálogo y carga de productos</h1>
        <p>Sube nuevos productos con fotografía, actualiza precios, existencias, dimensiones logísticas y categorías.</p>
      </div>
      <div class="actions">
        <a class="btn" href="admin"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Administración</a>
        <a class="btn" href="tienda" target="_blank"><svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>Ver en Tienda</a>
        <button class="btn" type="button" id="btn-cat"><svg viewBox="0 0 24 24"><path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 17h6"/><path d="M17 14v6"/></svg>Nueva categoría</button>
        <button class="btn primary" type="button" id="btn-nuevo"><svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Subir producto</button>
      </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
    <div class="flash-banner <?= $flashType ?>">
      <span><?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?></span>
      <button class="btn sm ghost" type="button" onclick="this.parentElement.remove()">✕</button>
    </div>
    <?php endif; ?>

    <section class="kpis" id="kpis"></section>

    <section class="panel">
      <div class="panel-b">
        <div class="filters">
          <div class="search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input class="inp" id="q" type="search" placeholder="Buscar por código SKU, nombre, descripción o categoría…" autocomplete="off">
          </div>
          <select class="inp" id="f-estado" style="width:auto">
            <option value="">Todos los niveles de stock</option>
            <option value="ok">En existencia normal</option>
            <option value="bajo">Stock bajo (por reponer)</option>
            <option value="agotado">Agotados (stock 0)</option>
            <option value="destacado">Destacados en portada</option>
            <option value="con_foto">Con fotografía subida</option>
          </select>
          <button class="btn sm" type="button" id="btn-csv">Exportar CSV</button>
          <button class="btn sm" type="button" id="btn-xls">Exportar Excel</button>
        </div>
        <div class="chips" id="chips" style="margin-bottom:16px"></div>

        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="num">Precio s/IVA</th>
                <th class="num">Precio c/IVA</th>
                <th class="num">Existencia</th>
                <th class="num">Peso / Vol.</th>
                <th>Estado</th>
                <th class="num">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <script>
    const s = requireModulo("admin");
    let catActual = "Todas";

    function renderKpis() {
      const total = PRODUCTOS.length;
      const unidades = PRODUCTOS.reduce((a, p) => a + (Number(p.stock) || 0), 0);
      const valorSinIva = PRODUCTOS.reduce((a, p) => a + (Number(p.precio) || 0) * (Number(p.stock) || 0), 0);
      const bajos = PRODUCTOS.filter((p) => p.stock <= (p.stockMinimo || 8)).length;
      const agotados = PRODUCTOS.filter((p) => p.stock <= 0).length;
      const conFoto = PRODUCTOS.filter((p) => !!p.imagen).length;

      document.getElementById("kpis").innerHTML =
        `<div class="panel kpi"><span class="lbl">Productos en catálogo</span><span class="val">${total}</span><span class="sub">${Object.keys(CATEGORIAS).length} categorías · ${conFoto} con foto propia</span></div>` +
        `<div class="panel kpi"><span class="lbl">Unidades en bodega</span><span class="val" style="color:#2fc986">${unidades.toLocaleString("es-GT")}</span><span class="sub">Sincronizado con tienda e inventario</span></div>` +
        `<div class="panel kpi"><span class="lbl">Valor inventario (c/IVA)</span><span class="val" style="color:var(--orange-soft)">${fmtQ(conIva(valorSinIva))}</span><span class="sub">${fmtQ(valorSinIva)} base sin IVA</span></div>` +
        `<div class="panel kpi"><span class="lbl">Alertas de reposición</span><span class="val" style="color:${agotados ? "#ef5b66" : bajos ? "#f4b63d" : "#2fc986"}">${bajos}</span><span class="sub">${agotados} agotados · ${bajos - agotados} en stock mínimo</span></div>`;
    }

    function renderChips() {
      const cats = ["Todas", ...Object.keys(CATEGORIAS)];
      document.getElementById("chips").innerHTML = cats.map((c) => {
        const count = c === "Todas" ? PRODUCTOS.length : PRODUCTOS.filter((p) => p.categoria === c).length;
        return `<button type="button" class="chip-f ${c === catActual ? "on" : ""}" data-cat="${esc(c)}">${esc(c)} <span style="opacity:.65">(${count})</span></button>`;
      }).join("");
    }

    function filtrados() {
      const q = norm(document.getElementById("q").value).trim();
      const est = document.getElementById("f-estado").value;
      return PRODUCTOS.filter((p) => {
        if (catActual !== "Todas" && p.categoria !== catActual) return false;
        if (q && !norm(`${p.sku} ${p.nombre} ${p.descripcion || ""} ${p.categoria}`).includes(q)) return false;
        const min = p.stockMinimo || 8;
        if (est === "ok" && p.stock <= min) return false;
        if (est === "bajo" && (p.stock <= 0 || p.stock > min)) return false;
        if (est === "agotado" && p.stock > 0) return false;
        if (est === "destacado" && !p.destacado) return false;
        if (est === "con_foto" && !p.imagen) return false;
        return true;
      });
    }

    function renderTabla() {
      const lista = filtrados();
      const tb = document.getElementById("tbody");
      if (!lista.length) {
        tb.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:38px;color:var(--muted)">No se encontraron productos con esos filtros.</td></tr>`;
        return;
      }
      tb.innerHTML = lista.map((p) => {
        const cat = CATEGORIAS[p.categoria] || { color: "#22b8cf", icono: "" };
        const min = p.stockMinimo || 8;
        const stBadge = p.stock <= 0
          ? `<span class="badge" style="--c:#ef5b66">Agotado (0)</span>`
          : p.stock <= min
            ? `<span class="badge" style="--c:#f4b63d">Bajo (${p.stock})</span>`
            : `<span class="badge" style="--c:#2fc986">${p.stock} uds</span>`;
        const thumb = p.imagen
          ? `<div class="prod-thumb" style="--c:${cat.color}"><img src="${esc(p.imagen)}" alt="${esc(p.nombre)}"></div>`
          : `<div class="prod-thumb" style="--c:${cat.color}"><svg viewBox="0 0 24 24">${cat.icono}</svg></div>`;

        return `<tr>` +
          `<td><div style="display:flex;align-items:center;gap:12px">${thumb}<div><div class="strong">${esc(p.nombre)} ${p.destacado ? '<span class="badge plain" style="--c:#f97316;padding:1px 7px;font-size:10.5px">Destacado</span>' : ""}</div><div class="sub">${esc(p.sku)}${p.descripcion ? " · " + esc(p.descripcion.slice(0, 62)) + (p.descripcion.length > 62 ? "…" : "") : ""}</div></div></div></td>` +
          `<td><span class="badge plain" style="--c:${cat.color}">${esc(p.categoria)}</span></td>` +
          `<td class="num">${fmtQ(p.precio)}</td>` +
          `<td class="num strong" style="color:var(--orange-soft)">${fmtQ(conIva(p.precio))}</td>` +
          `<td class="num">${stBadge}</td>` +
          `<td class="num sub">${Number(p.kg || 1).toFixed(2)} kg<br>${Number(p.m3 || 0.01).toFixed(3)} m³</td>` +
          `<td>${p.imagen ? '<span class="badge plain" style="--c:#22b8cf">Foto propia</span>' : '<span class="sub">Vectorial</span>'}</td>` +
          `<td><div class="row-acts">` +
            `<button class="btn sm" type="button" data-edit="${esc(p.sku)}">Editar</button>` +
            `<button class="btn sm danger ghost" type="button" data-del="${esc(p.sku)}">Eliminar</button>` +
          `</div></td>` +
        `</tr>`;
      }).join("");
    }

    function sugerirSiguienteSku(categoria) {
      const prefijos = {
        "Laptops y Portátiles": "DF-LAP-",
        "Monitores y Pantallas": "DF-MON-",
        "Componentes y Hardware": "DF-CMP-",
        "Periféricos y Accesorios": "DF-PRF-",
        "Redes y Servidores": "DF-NET-",
      };
      const pref = prefijos[categoria] || "DF-HW-";
      const nums = PRODUCTOS
        .filter((p) => p.sku.startsWith(pref))
        .map((p) => Number(p.sku.slice(pref.length).replace(/\D/g, "")) || 0);
      const max = nums.length ? Math.max(...nums) : 0;
      return `${pref}${String(max + 1).padStart(2, "0")}`;
    }

    function modalProducto(skuEditar) {
      const p = skuEditar ? PRODUCTOS.find((x) => x.sku === skuEditar) : null;
      const esNuevo = !p;
      const catOpts = Object.keys(CATEGORIAS).map((c) => `<option value="${esc(c)}" ${p && p.categoria === c ? "selected" : ""}>${esc(c)}</option>`).join("");
      const skuInicial = p ? p.sku : sugerirSiguienteSku(Object.keys(CATEGORIAS)[0] || "Laptops y Portátiles");

      const ov = document.createElement("div");
      ov.className = "overlay";
      ov.innerHTML =
        `<div class="modal" style="max-width:680px" role="dialog" aria-modal="true">` +
          `<div class="modal-h"><h3>${esNuevo ? "Subir nuevo producto al catálogo" : "Editar producto " + esc(p.sku)}</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
          `<form method="post" action="adm-productos" enctype="multipart/form-data">` +
            `<input type="hidden" name="form_action" value="save_product">` +
            `<input type="hidden" name="orig_sku" value="${p ? esc(p.sku) : ""}">` +
            `<div class="modal-b"><div class="grid-form">` +
              `<div class="field c4"><label>Código SKU *</label><input class="inp" name="sku" id="m-sku" value="${esc(skuInicial)}" required></div>` +
              `<div class="field c8"><label>Nombre comercial del producto *</label><input class="inp" name="nombre" id="m-nom" value="${p ? esc(p.nombre) : ""}" placeholder="Ej. Monitor Gamer 27\" 4K IPS" required></div>` +
              `<div class="field c6"><label>Categoría *</label><select class="inp" name="categoria" id="m-cat">${catOpts}</select></div>` +
              `<div class="field c3"><label>Precio sin IVA (Q) *</label><input class="inp" name="precio" id="m-pre" type="number" step="0.01" min="0.01" value="${p ? Number(p.precio).toFixed(2) : "250.00"}" required></div>` +
              `<div class="field c3"><label>Precio con IVA (${Math.round(IVA * 100)} %)</label><input class="inp" id="m-pre-iva" type="text" disabled></div>` +
              `<div class="field c12"><label>Descripción detallada para la tienda</label><textarea class="inp" name="descripcion" rows="2" placeholder="Características principales, garantía y especificaciones…">${p ? esc(p.descripcion || "") : ""}</textarea></div>` +
              `<div class="field c3"><label>Stock en bodega *</label><input class="inp" name="stock" type="number" step="1" min="0" value="${p ? p.stock : 20}" required></div>` +
              `<div class="field c3"><label>Stock mínimo alerta</label><input class="inp" name="stock_minimo" type="number" step="1" min="0" value="${p ? (p.stockMinimo || 8) : 8}"></div>` +
              `<div class="field c3"><label>Peso unitario (kg)</label><input class="inp" name="kg" type="number" step="0.05" min="0.05" value="${p ? Number(p.kg || 1).toFixed(2) : "1.50"}"></div>` +
              `<div class="field c3"><label>Volumen (m³)</label><input class="inp" name="m3" type="number" step="0.001" min="0.001" value="${p ? Number(p.m3 || 0.01).toFixed(3) : "0.015"}"></div>` +
              `<div class="field c7">` +
                `<label>Fotografía del producto (JPG, PNG, WEBP o SVG)</label>` +
                `<input class="inp" type="file" name="imagen_file" id="m-file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg" style="padding:7px 10px">` +
                `<label style="margin-top:6px">O URL / ruta de imagen (opcional)</label>` +
                `<input class="inp" type="text" name="imagen_url" id="m-url" value="${p && p.imagen ? esc(p.imagen) : ""}" placeholder="uploads/productos/ejemplo.jpg o https://…">` +
                `<div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap">` +
                  `<label class="check"><input type="checkbox" name="destacado" value="1" ${p && p.destacado ? "checked" : ""}> Destacar producto en la tienda</label>` +
                  (p && p.imagen ? `<label class="check"><input type="checkbox" name="quitar_imagen" value="1"> Quitar foto y usar ícono vectorial</label>` : "") +
                `</div>` +
              `</div>` +
              `<div class="field c5">` +
                `<label>Vista previa en tienda</label>` +
                `<div class="preview-box" id="m-prev">` +
                  (p && p.imagen ? `<img src="${esc(p.imagen)}" alt="Vista previa">` : `<span class="sub" style="padding:12px;text-align:center">Sin foto subida (se usará ilustración vectorial de la categoría)</span>`) +
                `</div>` +
              `</div>` +
            `</div></div>` +
            `<div class="modal-f">` +
              `<button class="btn" type="button" data-x>Cancelar</button>` +
              `<button class="btn primary" type="submit">${esNuevo ? "Subir y guardar en MySQL" : "Guardar cambios en MySQL"}</button>` +
            `</div>` +
          `</form>` +
        `</div>`;

      document.body.appendChild(ov);
      ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });

      const preInp = ov.querySelector("#m-pre");
      const preIva = ov.querySelector("#m-pre-iva");
      const actIva = () => { preIva.value = fmtQ(conIva(Number(preInp.value) || 0)); };
      preInp.addEventListener("input", actIva);
      actIva();

      if (esNuevo) {
        const catSel = ov.querySelector("#m-cat");
        catSel.addEventListener("change", () => {
          ov.querySelector("#m-sku").value = sugerirSiguienteSku(catSel.value);
        });
      }

      const fileInp = ov.querySelector("#m-file");
      const prevBox = ov.querySelector("#m-prev");
      fileInp.addEventListener("change", () => {
        const f = fileInp.files && fileInp.files[0];
        if (!f) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
          prevBox.innerHTML = `<img src="${ev.target.result}" alt="Vista previa">`;
        };
        reader.readAsDataURL(f);
      });
    }

    function modalCategoria() {
      const ov = document.createElement("div");
      ov.className = "overlay";
      ov.innerHTML =
        `<div class="modal" style="max-width:480px" role="dialog" aria-modal="true">` +
          `<div class="modal-h"><h3>Crear o actualizar categoría</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
          `<form method="post" action="adm-productos">` +
            `<input type="hidden" name="form_action" value="save_category">` +
            `<div class="modal-b"><div class="grid-form">` +
              `<div class="field c8"><label>Nombre de la categoría *</label><input class="inp" name="cat_nombre" placeholder="Ej. Iluminación Inteligente" required></div>` +
              `<div class="field c4"><label>Color distintivo</label><input class="inp" name="cat_color" type="color" value="#f97316" style="padding:4px;height:42px"></div>` +
              `<div class="field c12"><label>Trazos SVG del ícono (viewBox 0 0 24 24)</label><input class="inp" name="cat_icono" value='<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/><circle cx="12" cy="12" r="4"/>'></div>` +
            `</div></div>` +
            `<div class="modal-f">` +
              `<button class="btn" type="button" data-x>Cancelar</button>` +
              `<button class="btn primary" type="submit">Guardar categoría</button>` +
            `</div>` +
          `</form>` +
        `</div>`;
      document.body.appendChild(ov);
      ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });
    }

    if (s) {
      pintarShell(s, [{ t: "Administración", href: "admin" }, { t: "Catálogo y productos" }]);
      renderKpis();
      renderChips();
      renderTabla();

      document.getElementById("q").addEventListener("input", renderTabla);
      document.getElementById("f-estado").addEventListener("change", renderTabla);
      document.getElementById("chips").addEventListener("click", (e) => {
        const b = e.target.closest("[data-cat]");
        if (!b) return;
        catActual = b.dataset.cat;
        renderChips();
        renderTabla();
      });

      document.getElementById("btn-nuevo").addEventListener("click", () => modalProducto(null));
      document.getElementById("btn-cat").addEventListener("click", modalCategoria);

      document.getElementById("tbody").addEventListener("click", async (e) => {
        const ed = e.target.closest("[data-edit]");
        if (ed) { modalProducto(ed.dataset.edit); return; }
        const dl = e.target.closest("[data-del]");
        if (dl) {
          const sku = dl.dataset.del;
          const prod = PRODUCTOS.find((x) => x.sku === sku);
          if (await confirmar("Eliminar producto", `¿Seguro que deseas eliminar «${prod ? prod.nombre : sku}» (${sku}) de la base de datos MySQL?`, "Eliminar", true)) {
            const f = document.createElement("form");
            f.method = "post";
            f.action = "adm-productos";
            f.innerHTML = `<input type="hidden" name="form_action" value="delete_product"><input type="hidden" name="sku" value="${esc(sku)}">`;
            document.body.appendChild(f);
            f.submit();
          }
        }
      });

      const exportar = (formato) => {
        const cols = ["SKU", "Nombre", "Categoría", "Precio s/IVA (Q)", "Precio c/IVA (Q)", "Stock", "Peso (kg)", "Volumen (m3)", "Destacado"];
        const rows = filtrados().map((p) => [p.sku, p.nombre, p.categoria, p.precio, conIva(p.precio), p.stock, p.kg || 1, p.m3 || 0.01, p.destacado ? "Sí" : "No"]);
        if (formato === "csv") descargarCSV("catalogo_productos_mysql", cols, rows);
        else descargarXLS("catalogo_productos_mysql", "Catálogo MySQL", cols, rows);
      };
      document.getElementById("btn-csv").addEventListener("click", () => exportar("csv"));
      document.getElementById("btn-xls").addEventListener("click", () => exportar("xls"));
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

