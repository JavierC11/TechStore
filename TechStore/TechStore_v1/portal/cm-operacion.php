<?php
$pageTitle = 'Operación';
$pageCss = ['css/comercial.css'];
$pageLibs = ['js/logistica.js', 'js/comercial.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Comercial</div><h1>Operación</h1><p>Cómo avanzan los pedidos por el flujo: embudo, cumplimiento del plazo de despacho, tiempos, flota e inventario.</p></div>
      <div class="actions"></div>
    </div>
    <div class="fb" id="fb"></div>
    <div class="hero-k" id="hk"></div>
    <div class="grid2" id="g1"></div>
    <div class="grid2" id="g2"></div>
  </main>
  <script>

  const s = requireModulo("comercial");
  const crumbs = (t) => [{ t: "Inicio", href: "inicio" }, { t: "Comercial", href: "comercial" }, { t }];
  const $ = (id) => document.getElementById(id);
  const cardH = (t, sub) => `<div class="panel-h"><h2>${t}</h2>${sub ? `<span class="sub">${sub}</span>` : ""}</div>`;

  function pintar(f) {
    const w = ventana(f), P = pedidosFiltrados(f, w), card = (t, sub, h) => `<section class="panel">${cardH(t, sub)}<div class="card-b">${h}</div></section>`;
    $("fb-n").textContent = `${P.length} pedidos analizados`;
    const env = P.filter((p) => ["En ruta", "Entregado"].includes(p.estado) && p.fechaEnvio), enP = env.filter(esEnPlazo);
    const vencidos = getPedidos().filter((p) => ["Pendiente de despacho", "En preparación"].includes(p.estado) && msRestantes(p) < 0).length;
    const bajos = PRODUCTOS.filter((p) => p.stock <= STOCK_MINIMO).length;
    const hk = [["Pedidos en proceso (ahora)", getPedidos().filter((p) => ["Pendiente de despacho", "En preparación"].includes(p.estado)).length, '<span class="dl">entre pendiente y preparación</span>'],
      ["Fuera del plazo de 8 h (ahora)", vencidos, `<span class="dl" style="color:${vencidos ? ST.bad : ST.ok}">${vencidos ? "■ requieren atención" : "● al día"}</span>`],
      ["Cumplimiento de despacho", env.length ? (enP.length / env.length * 100).toFixed(1) + "%" : "—", `<span class="dl">${enP.length} de ${env.length} despachados en plazo</span>`],
      ["Productos bajo el mínimo", bajos, `<span class="dl" style="color:${bajos ? ST.warn : ST.ok}">${bajos ? "▲ revisar inventario" : "● sin alertas"}</span>`]];
    $("hk").innerHTML = hk.map(([l, v, d]) => `<div class="hk"><div class="l">${l}</div><div class="v">${v}</div>${d}</div>`).join("");
    const emb = Object.keys(ESTADOS).map((e) => ({ k: e, v: P.filter((p) => p.estado === e).length, color: ESTADOS[e].color }));
    const bins = [["< 2 h", 0, 2], ["2–4 h", 2, 4], ["4–8 h", 4, 8], ["8–24 h", 8, 24], ["> 24 h", 24, 1e9]].map(([k, a, b], i) => ({ k, v: env.filter((p) => { const h = horasDespacho(p); return h >= a && h < b; }).length, color: i < 3 ? ST.ok : i === 3 ? ST.warn : ST.bad }));
    const dep = {}; env.forEach((p) => { const d = p.entrega.departamento, o = dep[d] || (dep[d] = { k: d, v: 0, n: 0 }); o.n++; if (esEnPlazo(p)) o.v++; });
    const depArr = Object.values(dep).map((o) => ({ k: o.k, v: Math.round(o.v / o.n * 1000) / 10, sub: `${o.v} de ${o.n} en plazo`, color: o.v / o.n >= .95 ? ST.ok : o.v / o.n >= .9 ? ST.warn : ST.bad })).sort((a, b) => a.v - b.v);
    $("g1").innerHTML = card("Embudo por estado", "pedidos del periodo", barrasH(emb)) +
      card("Tiempo de pedido a despacho", "pedidos despachados · meta ≤ 8 h", env.length ? columnas(bins) : '<div class="cm-empty">Sin pedidos despachados en este periodo.</div>') +
      card("Cumplimiento del plazo por departamento", "% despachados dentro de 8 h", barrasH(depArr, { fmt: (v) => v + "%" })) +
      card("Cancelación y abandono por canal", "% de pedidos del canal", barrasH(CANALES_TODOS.map((c) => { const t = P.filter((p) => p.canal === c); return { k: c, v: t.length ? Math.round(t.filter((p) => p.estado === "Cancelado" || p.estado === "Borrador").length / t.length * 1000) / 10 : 0, sub: t.length + " pedidos", color: COL_CAT[2] }; }), { fmt: (v) => v + "%" }));
    // flota
    const rd = getRutas().filter((r) => ["Despachada", "Entregada"].includes(r.estado) && new Date(r.despachadaEn || r.creadaEn || r.fecha) >= w.desde);
    const flota = getVehiculos().map((v) => { const rs = rd.filter((r) => r.vehiculoId === v.id); const oc = rs.map((r) => { const c = cargaRuta(r); return Math.max(c.kg / v.capKg, c.m3 / v.capM3) * 100; }); return { k: v.nombre, v: oc.length ? Math.round(oc.reduce((a, b) => a + b, 0) / oc.length) : 0, sub: `${rs.length} rutas despachadas`, color: COL_CAT[0] }; });
    const cats = {}; PRODUCTOS.forEach((p) => { const o = cats[p.categoria] || (cats[p.categoria] = { k: p.categoria, v: 0, color: CATEGORIAS[p.categoria]?.color || COL_CAT[0] }); o.v += p.stock; });
    const alertas = PRODUCTOS.filter((p) => p.stock <= STOCK_MINIMO).sort((a, b) => a.stock - b.stock).slice(0, 8);
    $("g2").innerHTML = card("Ocupación promedio de la flota", "% de la capacidad limitante (peso o volumen)", barrasH(flota, { fmt: (v) => v + "%" })) +
      card("Unidades en inventario por categoría", "stock actual", barrasH(Object.values(cats).sort((a, b) => b.v - a.v), { fmt: (v) => v.toLocaleString("en-US") })) +
      card("Alertas de inventario", "en o bajo el mínimo de " + STOCK_MINIMO, '<table class="sm-t"><thead><tr><th>Producto</th><th>Ubicación</th><th class="n">Stock</th></tr></thead><tbody>' + (alertas.map((p) => `<tr><td>${esc(p.nombre)}</td><td>${esc(ubicacionSku(p.sku))}</td><td class="n"><span class="stb" style="--c:${p.stock ? ST.warn : ST.bad}">${p.stock ? ST_ICO.warn : ST_ICO.bad} ${p.stock}</span></td></tr>`).join("") || '<tr><td colspan="3" class="cm-empty">Sin alertas.</td></tr>') + "</tbody></table>") +
      card("Pedidos pendientes más urgentes", "fuera del plazo o por vencer", '<table class="sm-t"><thead><tr><th>Pedido</th><th>Estado</th><th class="n">Plazo</th></tr></thead><tbody>' + (getPedidos().filter((p) => ["Pendiente de despacho", "En preparación"].includes(p.estado)).sort((a, b) => msRestantes(a) - msRestantes(b)).slice(0, 8).map((p) => { const e = etiquetaPlazo(p); return `<tr><td>${esc(p.id)}<div class="faint" style="font-size:12px">${esc(p.cliente.nombre)}</div></td><td>${esc(p.estado)}</td><td class="n"><span class="badge" style="--c:${e.color}">${esc(e.txt)}</span></td></tr>`; }).join("") || '<tr><td colspan="3" class="cm-empty">Nada pendiente.</td></tr>') + "</tbody></table>");
  }

  if (s && !can("cm.operacion.ver")) location.replace("comercial"); else if (s) {
    pintarShell(s, crumbs("Operación")); if ($("year")) $("year").textContent = new Date().getFullYear(); activarTips();
    barraFiltros("fb", pintar); pintar(getFiltro());
  }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
