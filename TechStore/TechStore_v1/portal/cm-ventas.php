<?php
$pageTitle = 'Ventas';
$pageCss = ['css/comercial.css'];
$pageLibs = ['js/logistica.js', 'js/comercial.js', 'js/xlsx-mini.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Comercial</div><h1>Ventas</h1><p>Ingresos y pedidos segmentados por canal, pago, categoría, zona, producto, cliente y agente. Considera pedidos con venta (pendientes de despacho en adelante, con IVA).</p></div>
      <div class="actions"><button class="btn" id="b-xl" hidden>Exportar pedidos a Excel</button></div>
    </div>
    <div class="fb" id="fb"></div>
    <div class="hero-k" id="hk"></div>
    <section class="panel" style="margin-bottom:18px"><div id="c-dia"></div></section>
    <div class="grid2" id="g1"></div>
    <div class="grid2" id="g2"></div>
  </main>
  <script>

  const s = requireModulo("comercial");
  const crumbs = (t) => [{ t: "Inicio", href: "inicio" }, { t: "Comercial", href: "comercial" }, { t }];
  const $ = (id) => document.getElementById(id);
  const cardH = (t, sub) => `<div class="panel-h"><h2>${t}</h2>${sub ? `<span class="sub">${sub}</span>` : ""}</div>`;

  let V = [];
  function pintar(f) {
    const w = ventana(f), P = pedidosFiltrados(f, w), pv = pedidosPrevios(f, w);
    V = P.filter(esVenta); const Vp = pv ? pv.filter(esVenta) : null;
    $("fb-n").textContent = `${V.length} pedidos con venta`;
    const tot = (a) => a.reduce((x, p) => x + p.totales.total, 0), uds = (a) => a.reduce((x, p) => x + p.totales.unidades, 0);
    const hk = [["Ventas totales (con IVA)", fmtQ(tot(V)), delta(tot(V), Vp && tot(Vp))], ["Pedidos con venta", V.length, delta(V.length, Vp && Vp.length)],
      ["Ticket promedio", V.length ? fmtQ(tot(V) / V.length) : "—", delta(V.length ? tot(V) / V.length : null, Vp && Vp.length ? tot(Vp) / Vp.length : null)], ["Unidades vendidas", uds(V), delta(uds(V), Vp && uds(Vp))]];
    $("hk").innerHTML = hk.map(([l, v, d]) => `<div class="hk"><div class="l">${l}</div><div class="v">${v}</div>${d || '<span class="dl">&nbsp;</span>'}</div>`).join("");
    // por día (incluye días sin venta para que la serie sea continua)
    const m = {}; V.forEach((p) => { const d = dia(p.fecha); m[d] = (m[d] || 0) + p.totales.total; });
    const pts = []; for (let t = Math.floor(w.desde / DAY) * DAY; t <= w.hasta; t += DAY) { const d = dia(new Date(t + 12 * 3600000).toISOString()); pts.push({ k: d, v: Math.round((m[d] || 0) * 100) / 100 }); }
    const ult = pts.length > 62 ? pts.slice(-62) : pts;
    $("c-dia").innerHTML = cardH("Ventas por día", "Q con IVA") + '<div class="card-b">' + areaTiempo(ult, { fmt: fmtQ, titulo: "Ventas por día" }) + "</div>";
    const mon = (p) => p.totales.total, prod = {};
    V.forEach((p) => p.items.forEach((i) => { const o = prod[i.sku] || (prod[i.sku] = { k: i.nombre, sku: i.sku, u: 0, v: 0 }); o.u += i.cantidad; o.v += totalLinea(i).conIva; }));
    const cat = {}; V.forEach((p) => p.items.forEach((i) => { cat[i.categoria] = (cat[i.categoria] || 0) + totalLinea(i).conIva; }));
    const card = (t, sub, h, cls) => `<section class="panel ${cls || ""}">${cardH(t, sub)}<div class="card-b">${h}</div></section>`;
    const top = (arr, n) => arr.slice(0, n);
    $("g1").innerHTML = card("Por canal", "Q", barrasH(agrupar(V, (p) => p.canal, mon), { fmt: fmtQ })) +
      card("Por forma de pago", "Q", donut(agrupar(V, (p) => p.pago, mon), { fmt: fmtQ, centro: fmtCorto(tot(V)) })) +
      card("Por categoría de producto", "Q con IVA", barrasH(Object.entries(cat).map(([k, v]) => ({ k, v, color: CATEGORIAS[k]?.color || COL_CAT[0] })).sort((a, b) => b.v - a.v), { fmt: fmtQ })) +
      card("Por departamento de entrega", "Q", barrasH(top(agrupar(V, (p) => p.entrega.departamento, mon), 8), { fmt: fmtQ, color: COL_CAT[1] }));
    const prodArr = Object.values(prod).sort((a, b) => b.v - a.v).slice(0, 8);
    const cli = agrupar(V, (p) => p.cliente.nombre, mon).slice(0, 8);
    $("g2").innerHTML = card("Productos más vendidos", "Top 8 por ingreso", '<table class="sm-t"><thead><tr><th>Producto</th><th class="n">Uds.</th><th class="n">Ingreso</th></tr></thead><tbody>' + (prodArr.map((p) => `<tr><td>${esc(p.k)}<div class="faint" style="font-size:12px">${p.sku}</div></td><td class="n">${p.u}</td><td class="n">${fmtQ(p.v)}</td></tr>`).join("") || '<tr><td colspan="3" class="cm-empty">Sin datos.</td></tr>') + "</tbody></table>") +
      card("Mejores clientes", "Top 8 por compras", '<table class="sm-t"><thead><tr><th>Cliente</th><th class="n">Pedidos</th><th class="n">Compras</th></tr></thead><tbody>' + (cli.map((c) => `<tr><td>${esc(c.k)}</td><td class="n">${c.n}</td><td class="n">${fmtQ(c.v)}</td></tr>`).join("") || '<tr><td colspan="3" class="cm-empty">Sin datos.</td></tr>') + "</tbody></table>") +
      card("Por agente de Contact Center", "Q", barrasH(agrupar(V, (p) => p.creadoPor, mon).map((x) => ({ ...x, sub: x.n + " pedidos" })), { fmt: fmtQ, color: COL_CAT[2] })) +
      card("Tipo de cliente y prioridad", "pedidos", donut(agrupar(V, (p) => p.cliente.tipo, () => 1), { centro: V.length, sub: "pedidos" }) + '<div style="height:16px"></div>' + donut(agrupar(V, (p) => p.prioridad, () => 1), { centro: V.filter((p) => p.prioridad === "Urgente").length, sub: "urgentes" }));
  }
  const xl = $("b-xl"); xl.hidden = !can("cm.exportar");
  xl.onclick = () => descargarXlsx("ventas.xlsx", "Ventas", [{ titulo: "Pedido", ancho: 14 }, { titulo: "Fecha", ancho: 18, tipo: "fecha" }, { titulo: "Cliente", ancho: 30 }, { titulo: "Canal", ancho: 12 }, { titulo: "Pago", ancho: 24 }, { titulo: "Departamento", ancho: 16 }, { titulo: "Agente", ancho: 22 }, { titulo: "Estado", ancho: 20 }, { titulo: "Total (Q)", ancho: 14, tipo: "moneda" }],
    V.map((p) => [p.id, p.fecha, p.cliente.nombre, p.canal, p.pago, p.entrega.departamento, p.creadoPor, p.estado, p.totales.total]));

  if (s && !can("cm.ventas.ver")) location.replace("comercial"); else if (s) {
    pintarShell(s, crumbs("Ventas")); if ($("year")) $("year").textContent = new Date().getFullYear(); activarTips();
    barraFiltros("fb", pintar); pintar(getFiltro());
  }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
