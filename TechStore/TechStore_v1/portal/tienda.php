<?php
$pageTitle  = 'Tienda en línea';
$pageLayout = 'tienda';
$pageCss    = ['css/tienda.css'];
$pageLibs   = ['js/tienda.js'];
require_once __DIR__ . '/includes/header.php';

$heroEye = htmlspecialchars($SITE_CFG['hero_eyebrow'] ?? 'Catálogo en línea · Entrega en toda Guatemala', ENT_QUOTES, 'UTF-8');
$heroT1  = htmlspecialchars($SITE_CFG['hero_titulo_1'] ?? 'Equipa tu hogar y tu oficina', ENT_QUOTES, 'UTF-8');
$heroTa  = htmlspecialchars($SITE_CFG['hero_titulo_acento'] ?? 'sin complicarte', ENT_QUOTES, 'UTF-8');
$heroSub = htmlspecialchars($SITE_CFG['hero_subtitulo'] ?? 'Precios con IVA incluido, existencias en tiempo real y entrega coordinada por nuestro propio equipo logístico.', ENT_QUOTES, 'UTF-8');
$perk1   = htmlspecialchars($SITE_CFG['perk_1'] ?? 'Envío gratis desde Q 500', ENT_QUOTES, 'UTF-8');
$perk2   = htmlspecialchars($SITE_CFG['perk_2'] ?? 'Pago contra entrega, transferencia o tarjeta', ENT_QUOTES, 'UTF-8');
$perk3   = htmlspecialchars($SITE_CFG['perk_3'] ?? 'Seguimiento en línea de cada pedido', ENT_QUOTES, 'UTF-8');
?>
  <main class="tw">
    <section class="hero-t">
      <div class="eyebrow"><?= $heroEye ?></div>
      <h1><?= $heroT1 ?> <span><?= $heroTa ?></span>.</h1>
      <p><?= $heroSub ?></p>
      <div class="perks"><span><?= $perk1 ?></span><span><?= $perk2 ?></span><span><?= $perk3 ?></span></div>
    </section>

    <div class="tbar">
      <div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input class="inp" id="q" type="search" placeholder="Buscar producto por nombre, descripción o código…"></div>
      <select class="inp" id="ord"><option value="rel">Destacados y relevantes</option><option value="asc">Precio: menor a mayor</option><option value="desc">Precio: mayor a menor</option><option value="az">Nombre A–Z</option></select>
    </div>
    <div class="chips" id="cats"></div>
    <div class="pgrid" id="grid"></div>
  </main>

  <script>
    const $ = (id) => document.getElementById(id);
    let cat = "Todas";

    function pintarCats() {
      $("cats").innerHTML = ["Todas", ...Object.keys(CATEGORIAS)]
        .map((c) => `<button class="chip2 ${c === cat ? "on" : ""}" data-c="${esc(c)}">${esc(c)}</button>`).join("");
    }
    function pintarGrid() {
      const q = norm($("q").value).trim(), ord = $("ord").value;
      const l = PRODUCTOS.filter((p) => (cat === "Todas" || p.categoria === cat) && (!q || norm(`${p.nombre} ${p.sku} ${p.categoria} ${p.descripcion || ""}`).includes(q)));
      if (ord === "rel") l.sort((a, b) => (Number(!!b.destacado) - Number(!!a.destacado)) || a.sku.localeCompare(b.sku));
      if (ord === "asc") l.sort((a, b) => a.precio - b.precio);
      if (ord === "desc") l.sort((a, b) => b.precio - a.precio);
      if (ord === "az") l.sort((a, b) => a.nombre.localeCompare(b.nombre, "es"));
      $("grid").innerHTML = l.length ? l.map((p) => {
        const st = p.stock <= 0 ? '<span class="badge" style="--c:#ef5b66">Agotado</span>'
          : p.stock <= (p.stockMinimo || 8) ? `<span class="badge" style="--c:#f4b63d">Últimas ${p.stock}</span>` : `<span class="badge" style="--c:#2fc986">Disponible</span>`;
        return `<article class="pc"><div data-ver="${esc(p.sku)}" title="Ver ficha completa">${arteProducto(p)}</div><div class="pc-b"><div style="display:flex;justify-content:space-between;align-items:center"><span class="cat">${esc(p.categoria)}</span>${st}</div>` +
          `<h3 style="cursor:pointer" data-ver="${esc(p.sku)}">${esc(p.nombre)}</h3><div class="pc-f"><div class="price">${fmtQ(conIva(p.precio))}<small>IVA incluido</small></div>` +
          `<button class="btn primary sm" data-add="${esc(p.sku)}" ${p.stock <= 0 ? "disabled" : ""}>${p.stock <= 0 ? "Sin stock" : "+ Agregar"}</button></div></div></article>`;
      }).join("") : '<div class="t-empty" style="grid-column:1/-1">No encontramos productos con ese criterio.</div>';
    }
    pintarTienda("catalogo"); pintarCats(); pintarGrid();
    $("cats").addEventListener("click", (e) => { const b = e.target.closest("[data-c]"); if (b) { cat = b.dataset.c; pintarCats(); pintarGrid(); } });
    $("grid").addEventListener("click", (e) => {
      const b = e.target.closest("[data-add]");
      if (b) { agregarCarrito(b.dataset.add, 1); return; }
      const v = e.target.closest("[data-ver]");
      if (v && typeof verDetalleProducto === "function") verDetalleProducto(v.dataset.ver);
    });
    $("q").addEventListener("input", pintarGrid); $("ord").addEventListener("change", pintarGrid);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

