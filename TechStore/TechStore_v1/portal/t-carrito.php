<?php
$pageTitle  = 'Tienda · Carrito';
$pageLayout = 'tienda';
$pageCss    = ['css/tienda.css'];
$pageLibs   = ['js/tienda.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="tw">
    <div class="page-title"><div><div class="eyebrow">Tu compra</div><h1>Carrito</h1></div><a class="btn" href="tienda">← Seguir comprando</a></div>
    <div class="two"><section class="panel" id="lineas"></section><aside class="sticky"><section class="panel"><div class="panel-h"><h2>Resumen</h2></div><div style="padding:6px 20px 20px" id="res"></div></section></aside></div>
  </main>
  <script>
    const $ = (id) => document.getElementById(id);

    function pintar() {
      const it = itemsCarrito(), t = totalesCarrito(it);
      $("lineas").innerHTML = it.length ? it.map((i) => { const p = prodDe(i.sku);
        return `<div class="line">${arteProducto(p)}<div><h4>${esc(i.nombre)}</h4><small>${fmtQ(conIva(i.precio))} c/u · ${p.stock} disponibles</small></div>` +
          `<div class="step"><button data-m="${i.sku}" data-d="-1" aria-label="Menos">−</button><b>${i.cantidad}</b><button data-m="${i.sku}" data-d="1" aria-label="Más">+</button></div>` +
          `<div class="lt" style="text-align:right"><b>${fmtQ(totalLinea(i).conIva)}</b><br><a href="#" data-x="${i.sku}" style="font-size:12.5px;color:#ff8b95">Quitar</a></div></div>`; }).join("")
        : '<div class="t-empty" style="border:0">Tu carrito está vacío.<br><br><a class="btn primary" href="tienda">Ver productos</a></div>';
      $("res").innerHTML = it.length ? `<div class="sum-r"><span>Subtotal</span><b>${fmtQ(round2(t.base - t.envio))}</b></div><div class="sum-r"><span>IVA (${Math.round(IVA * 100)} %)</span><b>${fmtQ(t.iva)}</b></div>` +
        `<div class="sum-r"><span>Envío</span><b>${t.gratis ? "Gratis" : fmtQ(conIva(t.envio))}</b></div>` +
        (t.gratis ? "" : `<div class="free"><i style="width:${Math.min(100, (1 - t.faltaGratis / ENVIO_GRATIS_DESDE) * 100)}%"></i></div><small class="muted">Agrega ${fmtQ(t.faltaGratis)} más y el envío es gratis</small>`) +
        `<div class="sum-t"><span>Total</span><span>${fmtQ(t.total)}</span></div><a class="btn primary" style="width:100%;margin-top:14px" href="t-checkout">Continuar con el pedido</a>` : '<p class="muted">Agrega productos para ver el resumen.</p>';
    }
    pintarTienda("tienda");
    $("lineas").addEventListener("click", (e) => { const m = e.target.closest("[data-m]"), x = e.target.closest("[data-x]"); if (x) { e.preventDefault(); cambiarCarrito(x.dataset.x, 0); }
      else if (m) { const l = getCarrito().find((z) => z.sku === m.dataset.m); const n = l.cant + Number(m.dataset.d); if (n > prodDe(l.sku).stock) toast("No hay más unidades disponibles", "warn"); cambiarCarrito(l.sku, n); } pintar(); });
    pintar();
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

