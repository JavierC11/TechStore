<?php
$pageTitle = 'Picking y carga';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js', 'js/xlsx-mini.js'];
$pageStyles = <<<'CSS'

  .sum { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 20px; padding: 18px 20px; align-items: center; }
  .sum .who { display: flex; gap: 14px; align-items: center; } .sum .who b { display: block; font-size: 17px; } .sum .who span { color: var(--muted); font-size: 13px; }
  .steps { display: flex; gap: 10px; margin: 18px 0; flex-wrap: wrap; }
  .st { flex: 1 1 180px; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--border); background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 14px; }
  .st i { font-style: normal; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: #e2e8f0; color: #334155; font-size: 13px; }
  .st.on { color: #4c1d95; border-color: #8b7cf6; background: #f5f3ff; } .st.on i { background: #8b7cf6; color: #fff; }
  .st.ok { color: #166534; border-color: #86efac; background: #f0fdf4; } .st.ok i { background: #16a34a; color: #fff; }
  .panel + .panel { margin-top: 18px; }
  .pbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); }
  .pbar .sp { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
  .ubic { font-family: ui-monospace, Menlo, monospace; font-weight: 700; padding: 4px 9px; border-radius: 8px; background: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; white-space: nowrap; }
  .qty { display: inline-flex; align-items: center; gap: 6px; } .qty input { width: 64px; height: 34px; text-align: center; }
  tr.done td { opacity: .55; } tr.warn td:first-child { box-shadow: inset 3px 0 #ef5b66; }
  .stop { display: flex; gap: 14px; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); cursor: pointer; }
  .stop input { width: 20px; height: 20px; accent-color: #16a34a; } .stop .n { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: #0f172a; font-weight: 700; flex: none; }
  .stop .t { flex: 1; min-width: 0; } .stop .t b { display: block; } .stop .t span { color: var(--muted); font-size: 13px; }
  .stop.done { background: #f0fdf4; }
  .hint { padding: 12px 18px; color: var(--muted); font-size: 13.5px; border-bottom: 1px solid var(--border); }
  .lock { padding: 40px 20px; text-align: center; color: var(--muted); }
  .final { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 18px 20px; }
  .final .msg { flex: 1 1 260px; color: var(--muted); } .final .msg b { color: #0f172a; }
  .ok-b { background: #f0fdf4; border: 1px solid #86efac; border-radius: 16px; }
  @media (max-width: 800px) { .sum { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap" id="main"></main>
  <script>

  const s = requireModulo("bodega");
  const rutaId = new URLSearchParams(location.search).get("ruta");
  const R = () => getRuta(rutaId);

  function imprimir(r) {
    const v = getVehiculo(r.vehiculoId) || {}, lin = lineasPicking(r), c = cargaRuta(r);
    const th = "padding:6px 8px;border:1px solid #999;text-align:left;font-size:12px";
    const filas = lin.map((l) => `<tr><td style="${th}"><b>${esc(l.ubic)}</b></td><td style="${th}">${esc(l.sku)}</td><td style="${th}">${esc(l.nombre)}</td><td style="${th};text-align:center"><b>${l.cant}</b></td><td style="${th};text-align:center">☐</td></tr>`).join("");
    const paradas = r.pedidos.map((id, i) => { const p = getPedido(id); if (!p) return "";
      return `<div style="margin:10px 0;padding:8px;border:1px solid #999;page-break-inside:avoid"><b>Parada ${i + 1} · ${esc(p.id)}</b> — ${esc(p.cliente.nombre)} · Tel. ${esc(p.cliente.telefono || "")}<br>${esc(p.entrega.direccion || "")}, ${esc(p.entrega.municipio)}, ${esc(p.entrega.departamento)}` +
        `<br><span style="font-size:12px">${p.items.map((x) => `${x.cantidad} × ${esc(x.nombre)}`).join(" · ")}</span>${/contra/i.test(p.pago || "") ? `<br><b>Cobrar contra entrega: ${fmtQ(p.totales.total)}</b>` : ""}</div>`; }).join("");
    const w = window.open("", "_blank");
    if (!w) { toast("El navegador bloqueó la ventana de impresión", "warn"); return; }
    w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Picking list ${esc(r.id)}</title></head><body style="font-family:Arial,sans-serif;color:#000;margin:24px">` +
      `<h2 style="margin:0">${esc(APP.empresa)} | ${esc(APP.producto)} — Picking list</h2><p style="margin:4px 0 14px">Ruta <b>${esc(r.id)}</b> · ${esc(v.nombre)} (${esc(v.placa)}) · Piloto: <b>${esc(r.piloto || "—")}</b> · ${fmtFecha(new Date().toISOString())}<br>Carga: ${fmtKg(c.kg)} · ${fmtM3(c.m3)} · ${r.pedidos.length} pedidos</p>` +
      `<table style="border-collapse:collapse;width:100%"><thead><tr><th style="${th}">Ubicación</th><th style="${th}">SKU</th><th style="${th}">Producto</th><th style="${th}">Cant.</th><th style="${th}">✓</th></tr></thead><tbody>${filas}</tbody></table>` +
      `<h3 style="margin-top:22px">Entrega por parada (orden de ruta)</h3>${paradas}` +
      `<p style="margin-top:30px">Preparó (Bodega): ______________________ &nbsp;&nbsp; Recibe (Piloto): ______________________ &nbsp;&nbsp; Hora: ________</p></body></html>`);
    w.document.close(); w.focus(); setTimeout(() => w.print(), 300);
  }
  function xlsx(r) {
    descargarXlsx(`picking-${r.id}.xlsx`, `Picking ${r.id}`, [{ titulo: "Ubicación", ancho: 12 }, { titulo: "SKU", ancho: 12 }, { titulo: "Producto", ancho: 42 }, { titulo: "Cantidad", ancho: 10, tipo: "entero" }, { titulo: "Pedidos", ancho: 36 }],
      lineasPicking(r).map((l) => [l.ubic, l.sku, l.nombre, l.cant, l.pedidos.map((x) => `${x.id} (${x.cant})`).join(", ")]));
    toast("Picking list exportado", "ok");
  }

  function render() {
    const r = R(), main = document.getElementById("main");
    if (!r || !["Planificada", "Despachada", "Entregada"].includes(r.estado)) { main.innerHTML = '<div class="lock"><h2>Ruta no disponible</h2><p>La ruta no existe o fue cancelada.</p><a class="btn" href="bd-cargas">Volver</a></div>'; return; }
    const v = getVehiculo(r.vehiculoId) || { nombre: "—", placa: "", tipo: "", capKg: 1, capM3: 1 }, c = cargaRuta(r);
    const desp = r.estado === "Despachada" || r.estado === "Entregada", e = desp ? "Cargada" : estadoCarga(r);
    const lin = lineasPicking(r);
    const preparar = can("bd.picking.ejecutar"), editable = preparar && e === "En picking";
    const pickOk = e !== "Pendiente" && (desp || pickingCompleto(r)), cargaOk = desp || e === "Cargada";
    const picksMap = (r.carga && r.carga.picks) ? r.carga.picks : {};
    const cargadosArr = (r.carga && Array.isArray(r.carga.cargados)) ? r.carga.cargados : [];
    const hechas = r.carga ? lin.filter((l) => (picksMap[l.sku] || 0) === l.cant).length : 0;
    const orden = [...r.pedidos].reverse();   // LIFO: la última parada se carga primero

    let h = `<div class="page-title"><div><div class="eyebrow"><a href="bd-cargas" style="color:inherit">← Preparación y carga</a></div><h1>Ruta ${esc(r.id)}</h1><p>${desp ? "Esta ruta ya salió. Solo consulta." : "Sigue los tres pasos: picking, carga en el camión y salida."}</p></div></div>`;
    h += `<section class="panel sum"><div class="who"><div class="vico" style="--c:${TIPOS_VEHICULO[v.tipo] || "#8b7cf6"}">${ICONO_CAMION}</div><div><b>${esc(v.nombre)}</b><span>${esc(v.placa)} · Piloto ${esc(r.piloto || "—")} · ${r.pedidos.length} pedidos</span></div></div>${gaugeHTML("Peso", c.kg, v.capKg, "kg", 1)}${gaugeHTML("Volumen", c.m3, v.capM3, "m³", 2)}</section>`;
    h += `<div class="steps"><div class="st ${pickOk ? "ok" : "on"}"><i>${pickOk ? "✓" : "1"}</i>Picking</div><div class="st ${cargaOk ? "ok" : pickOk ? "on" : ""}"><i>${cargaOk ? "✓" : "2"}</i>Carga en el camión</div><div class="st ${desp ? "ok" : cargaOk ? "on" : ""}"><i>${desp ? "✓" : "3"}</i>Salida</div></div>`;

    // Paso 1
    h += `<section class="panel"><div class="panel-h"><span class="n">1</span><h2>Picking list</h2><span class="sub">${lin.length} líneas · ordenadas por ubicación${r.carga ? ` · ${hechas}/${lin.length} listas` : ""}</span></div>`;
    h += `<div class="pbar">` + (e === "Pendiente" && preparar ? '<button class="btn primary" data-act="iniciar">Iniciar picking</button><span class="muted">Al iniciar, los pedidos pasan a «En preparación» y Logística ya no podrá modificar la ruta.</span>' : "") +
      `<div class="sp">${editable ? '<button class="btn sm" data-act="todo">Marcar todo recogido</button>' : ""}${can("bd.picking.ver") ? '<button class="btn sm" data-act="print">Imprimir picking list</button><button class="btn sm" data-act="xlsx">Exportar Excel</button>' : ""}</div></div>`;
    h += `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Ubicación</th><th>SKU</th><th>Producto</th><th>Pedidos</th><th>Pedir</th><th>En stock</th><th>Recogido</th></tr></thead><tbody>` + lin.map((l) => {
      const got = r.carga ? (picksMap[l.sku] || 0) : 0, st = stockDe(l.sku), falta = !desp && e !== "Cargada" && st < l.cant;
      return `<tr class="${got === l.cant && r.carga ? "done" : ""} ${falta ? "warn" : ""}"><td><span class="ubic">${esc(l.ubic)}</span></td><td>${esc(l.sku)}</td><td>${esc(l.nombre)}</td><td class="sub">${l.pedidos.map((x) => `${esc(x.id.slice(-4))}×${x.cant}`).join(" · ")}</td><td><b>${l.cant}</b></td>` +
        `<td>${desp || e === "Cargada" ? "—" : `<span class="badge" style="--c:${falta ? "#ef5b66" : "#2fc986"}">${st}${falta ? " · insuficiente" : ""}</span>`}</td>` +
        `<td>${editable ? `<span class="qty"><input class="inp" type="number" min="0" max="${l.cant}" value="${got}" data-pick="${l.sku}"><button class="btn sm icon" data-full="${l.sku}" title="Completo">✓</button></span>` : (r.carga ? `${got} / ${l.cant}` : "—")}</td></tr>`;
    }).join("") + `</tbody></table></div></section>`;

    // Paso 2
    h += `<section class="panel"><div class="panel-h"><span class="n">2</span><h2>Carga en el camión</h2><span class="sub">${r.carga ? cargadosArr.length + "/" + r.pedidos.length + " pedidos" : ""}</span></div>`;
    if (!pickOk) h += '<div class="lock">Completa el picking para habilitar la carga.</div>';
    else {
      h += '<div class="hint">Carga en este orden: <b style="color:#0f172a">la última parada va primero</b>, para que el piloto pueda sacar los pedidos conforme avanza la ruta.</div>';
      h += orden.map((id) => { const p = getPedido(id), n = r.pedidos.indexOf(id) + 1, ok = desp || cargadosArr.includes(id), k = cargaPedido(p);
        return `<label class="stop ${ok ? "done" : ""}"><input type="checkbox" data-load="${esc(id)}" ${ok ? "checked" : ""} ${editable ? "" : "disabled"}><span class="n">${n}</span><div class="t"><b>${esc(p.id)} · ${esc(p.cliente.nombre)}</b><span>${esc(p.entrega.municipio)} · ${p.items.map((x) => `${x.cantidad}× ${esc(x.nombre)}`).join(", ")}</span></div><span class="muted">${fmtKg(k.kg)}</span></label>`; }).join("");
    }
    h += `</section>`;

    // Paso 3
    h += `<section class="panel"><div class="panel-h"><span class="n">3</span><h2>Salida</h2></div>`;
    if (desp) h += `<div class="final"><div class="msg"><b>Ruta ${r.estado === "Entregada" ? "entregada" : "despachada"}</b> el ${fmtFecha(r.despachadaEn || r.creadaEn)}. Los pedidos están en «${r.estado === "Entregada" ? "Entregado" : "En ruta"}».</div></div>`;
    else if (e === "Cargada") h += `<div class="final"><div class="msg"><b>Carga confirmada</b> por ${esc((r.carga && r.carga.confirmadaPor) || "Bodega")} el ${fmtFecha((r.carga && r.carga.fin) || r.despachadaEn || r.creadaEn)}. El inventario ya se descontó.</div>${can("log.rutas.despachar") ? '<button class="btn primary" data-act="despachar">Liberar salida del camión</button>' : '<span class="muted">Logística despachará la ruta.</span>'}</div>`;
    else if (e === "En picking") {
      const listo = pickOk && cargaCompleta(r);
      h += `<div class="final"><div class="msg">${listo ? "Todo recogido y cargado. <b>Confirma</b> para descontar el inventario." : "Completa el picking y marca cada pedido como cargado para poder confirmar."}</div>${can("bd.cargas.confirmar") ? `<button class="btn primary" data-act="confirmar" ${listo ? "" : "disabled"}>Confirmar carga</button>` : ""}</div>`;
    } else h += '<div class="lock">Inicia el picking para continuar.</div>';
    h += `</section>`;
    const y = scrollY; main.innerHTML = h; scrollTo(0, y);
  }

  function ligar() {
    const main = document.getElementById("main"), user = () => s.name;
    main.addEventListener("click", async (e) => {
      const b = e.target.closest("[data-act]"), fu = e.target.closest("[data-full]"), r = R();
      if (fu) { setPick(rutaId, fu.dataset.full, lineasPicking(r).find((l) => l.sku === fu.dataset.full).cant); render(); return; }
      if (!b) return;
      const a = b.dataset.act;
      if (a === "iniciar") { iniciarPicking(rutaId, user()); toast("Picking iniciado. Pedidos en «En preparación»", "ok"); render(); }
      else if (a === "todo") { marcarTodoPick(rutaId); render(); }
      else if (a === "print") imprimir(r);
      else if (a === "xlsx") xlsx(r);
      else if (a === "confirmar") {
        if (await confirmar("Confirmar carga", `Se descontarán del inventario ${lineasPicking(r).reduce((x, l) => x + l.cant, 0)} unidades de ${lineasPicking(r).length} productos.`, "Confirmar carga")) {
          const x = confirmarCarga(rutaId, user()); toast(x.ok ? "Carga confirmada" : x.error, x.ok ? "ok" : "bad"); render();
        }
      } else if (a === "despachar") {
        if (await confirmar("Liberar salida", "El camión sale ahora: los pedidos pasarán a «En ruta».", "Liberar salida")) {
          const x = despacharRuta(rutaId, user()); toast(x.ok ? "Ruta despachada" : x.error, x.ok ? "ok" : "bad"); render();
        }
      }
    });
    main.addEventListener("change", (e) => {
      const p = e.target.closest("[data-pick]"), l = e.target.closest("[data-load]");
      if (p) { setPick(rutaId, p.dataset.pick, p.value); render(); }
      if (l) { toggleCargado(rutaId, l.dataset.load); render(); }
    });
  }
  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Bodega", href: "bodega" }, { t: "Preparación y carga", href: "bd-cargas" }, { t: rutaId || "Ruta" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    ligar(); render();
  }
  if (s && !can("bd.cargas.ver")) location.replace("bodega"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
