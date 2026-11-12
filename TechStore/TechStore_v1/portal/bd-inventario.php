<?php
$pageTitle = 'Inventario';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js', 'js/xlsx-mini.js'];
$pageStyles = <<<'CSS'

  .tools { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 14px 16px; border-bottom: 1px solid var(--border); }
  .tools .search { flex: 1 1 220px; } .tools .inp { height: 38px; } .tools select.inp { width: auto; }
  .pill { --c: #64748b; height: 30px; padding: 0 12px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: #f1f5f9; border: 1px solid var(--border); }
  .pill.on { color: #0f172a; background: color-mix(in srgb, var(--c) 15%, #ffffff); border-color: color-mix(in srgb, var(--c) 55%, transparent); }
  .tabs { display: flex; gap: 8px; margin-bottom: 16px; }
  .ubic { font-family: ui-monospace, Menlo, monospace; font-weight: 700; padding: 4px 9px; border-radius: 8px; background: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; white-space: nowrap; }
  td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
  .frm { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; } .frm .full { grid-column: 1 / -1; } .err { color: #dc2626; font-size: 13px; min-height: 18px; }
  .seg { display: flex; gap: 8px; } .seg .pill { flex: 1; justify-content: center; height: 38px; }
  .empty-r { padding: 40px; text-align: center; color: var(--muted); }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Bodega</div><h1>Inventario</h1><p>Existencias físicas por producto. «Comprometido» son las unidades de pedidos aún no enviados.</p></div>
      <div class="actions"><button class="btn" id="btn-xl" hidden>Exportar Excel</button></div>
    </div>
    <section class="kpis" id="kpis"></section>
    <div class="tabs"><button class="pill on" data-tab="ex">Existencias</button><button class="pill" data-tab="mv">Movimientos</button></div>
    <section class="panel" id="p-ex">
      <div class="tools">
        <div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg><input class="inp" id="f-q" type="search" placeholder="SKU, producto o ubicación…" autocomplete="off"></div>
        <select class="inp" id="f-cat"></select>
        <div id="pills" style="display:flex;gap:8px;flex-wrap:wrap"></div>
      </div>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Ubicación</th><th>SKU</th><th>Producto</th><th>Categoría</th><th class="num">Físico</th><th class="num">Comprometido</th><th class="num">Disponible</th><th>Estado</th><th></th></tr></thead><tbody id="tb"></tbody></table></div>
    </section>
    <section class="panel" id="p-mv" hidden>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Fecha</th><th>SKU</th><th>Producto</th><th>Tipo</th><th class="num">Cantidad</th><th class="num">Saldo</th><th>Motivo / ref.</th><th>Usuario</th></tr></thead><tbody id="tm"></tbody></table></div>
    </section>
  </main>
  <script>

  const s = requireModulo("bodega");
  let f = { q: "", cat: "", est: "" };
  const COMPROMETE = ["Por aprobar", "Pendiente de despacho", "En preparación"];

  function comprometido() {
    const m = {};
    getPedidos().filter((p) => COMPROMETE.includes(p.estado)).forEach((p) => p.items.forEach((i) => { m[i.sku] = (m[i.sku] || 0) + i.cantidad; }));
    return m;
  }
  const estadoStock = (p) => p.stock <= 0 ? ["Sin stock", "#ef5b66"] : p.stock <= STOCK_MINIMO ? ["Bajo mínimo", "#f4b63d"] : ["Normal", "#2fc986"];
  function filas() {
    const t = norm(f.q).trim();
    return PRODUCTOS.filter((p) => (!f.cat || p.categoria === f.cat) && (!f.est || estadoStock(p)[0] === f.est) &&
      (!t || norm([p.sku, p.nombre, ubicacionSku(p.sku)].join(" ")).includes(t))).sort((a, b) => ubicacionSku(a.sku).localeCompare(ubicacionSku(b.sku)));
  }
  function render() {
    const com = comprometido();
    document.getElementById("kpis").innerHTML = [
      [PRODUCTOS.length, "Productos", "#22b8cf"], [PRODUCTOS.reduce((a, p) => a + p.stock, 0).toLocaleString("en-US"), "Unidades en bodega", "#8b7cf6"],
      [PRODUCTOS.filter((p) => p.stock > 0 && p.stock <= STOCK_MINIMO).length, `Bajo el mínimo (≤ ${STOCK_MINIMO})`, "#f4b63d"], [PRODUCTOS.filter((p) => p.stock <= 0).length, "Sin stock", "#ef5b66"]]
      .map(([v, l, c]) => `<div class="kpi" style="--c:${c}"><div class="v">${v}</div><div class="l">${l}</div><div class="bar"></div></div>`).join("");
    document.getElementById("pills").innerHTML = [["", "Todos", "#64748b"], ["Normal", "Normal", "#2fc986"], ["Bajo mínimo", "Bajo mínimo", "#f4b63d"], ["Sin stock", "Sin stock", "#ef5b66"]]
      .map(([k, t, c]) => `<button class="pill ${f.est === k ? "on" : ""}" data-est="${k}" style="--c:${c}">${t}</button>`).join("");
    const aj = can("bd.inventario.ajustar"), l = filas();
    document.getElementById("tb").innerHTML = l.length ? l.map((p) => {
      const c = com[p.sku] || 0, e = estadoStock(p), disp = p.stock - c;
      return `<tr><td><span class="ubic">${esc(ubicacionSku(p.sku))}</span></td><td>${esc(p.sku)}</td><td>${esc(p.nombre)}</td><td>${esc(p.categoria)}</td><td class="num"><b>${p.stock}</b></td><td class="num">${c}</td>` +
        `<td class="num" style="color:${disp < 0 ? "#dc2626" : "inherit"}">${disp}</td><td><span class="badge" style="--c:${e[1]}">${e[0]}</span></td><td>${aj ? `<button class="btn sm" data-aj="${p.sku}">Ajustar</button>` : ""}</td></tr>`;
    }).join("") : '<tr><td colspan="9" class="empty-r">Sin resultados.</td></tr>';
    const mv = getMovs();
    document.getElementById("tm").innerHTML = mv.length ? mv.slice(0, 150).map((m) => `<tr><td>${fmtFecha(m.fecha)}</td><td>${esc(m.sku)}</td><td>${esc(m.nombre)}</td><td><span class="badge" style="--c:${m.cant > 0 ? "#2fc986" : "#f4b63d"}">${esc(m.tipo)}</span></td><td class="num">${m.cant > 0 ? "+" : ""}${m.cant}</td><td class="num">${m.saldo}</td><td>${esc(m.motivo)}</td><td>${esc(m.quien)}</td></tr>`).join("")
      : '<tr><td colspan="8" class="empty-r">Aún no hay movimientos. Se registran entradas, ajustes y salidas por carga de ruta.</td></tr>';
  }

  function modalAjuste(sku) {
    const p = PRODUCTOS.find((x) => x.sku === sku);
    const ov = document.createElement("div"); ov.className = "overlay";
    let tipo = "Entrada";
    ov.innerHTML = `<div class="modal"><div class="modal-h"><h3>Ajustar inventario</h3><button class="btn sm icon ghost" data-x>✕</button></div><div class="modal-b">` +
      `<p style="margin-top:0"><b>${esc(p.sku)}</b> · ${esc(p.nombre)}<br><span class="muted">Stock actual: <b style="color:#0f172a">${p.stock}</b> · Ubicación ${esc(ubicacionSku(p.sku))}</span></p>` +
      `<div class="frm"><div class="field full"><label>Tipo de movimiento</label><div class="seg" id="seg">` + ["Entrada", "Salida", "Conteo físico"].map((t, i) => `<button type="button" class="pill ${i ? "" : "on"}" data-t="${t}">${t}</button>`).join("") + `</div></div>` +
      `<div class="field"><label id="lq">Cantidad que entra</label><input class="inp" id="q" type="number" min="0" step="1"></div>` +
      `<div class="field"><label>Motivo</label><select class="inp" id="m"></select></div><div class="err full" id="err"></div></div></div>` +
      `<div class="modal-f"><button class="btn ghost" data-x>Cancelar</button><button class="btn primary" id="ok">Registrar</button></div></div>`;
    document.body.appendChild(ov);
    const MOT = { "Entrada": ["Compra a proveedor", "Devolución de cliente", "Transferencia recibida"], "Salida": ["Merma o daño", "Producto vencido", "Muestra / uso interno"], "Conteo físico": ["Conteo cíclico", "Inventario general"] };
    const LAB = { "Entrada": "Cantidad que entra", "Salida": "Cantidad que sale", "Conteo físico": "Cantidad contada (stock real)" };
    const setT = (t) => { tipo = t; ov.querySelector("#lq").textContent = LAB[t]; ov.querySelector("#m").innerHTML = MOT[t].map((x) => `<option>${x}</option>`).join(""); ov.querySelectorAll("[data-t]").forEach((b) => b.classList.toggle("on", b.dataset.t === t)); };
    setT("Entrada");
    ov.addEventListener("click", (e) => {
      if (e.target === ov || e.target.closest("[data-x]")) { ov.remove(); return; }
      const tb = e.target.closest("[data-t]"); if (tb) { setT(tb.dataset.t); return; }
      if (!e.target.closest("#ok")) return;
      const q = Number(ov.querySelector("#q").value), mot = ov.querySelector("#m").value, er = ov.querySelector("#err");
      if (!Number.isInteger(q) || q < 0 || (tipo !== "Conteo físico" && q === 0)) { er.textContent = "Escribe una cantidad entera válida."; return; }
      const delta = tipo === "Entrada" ? q : tipo === "Salida" ? -q : q - p.stock;
      if (delta === 0) { er.textContent = "El conteo coincide con el stock actual; no hay nada que ajustar."; return; }
      const r = moverStock(sku, delta, tipo === "Conteo físico" ? "Ajuste" : tipo, mot, s.name, "");
      if (!r.ok) { er.textContent = r.error; return; }
      ov.remove(); render(); toast(`${sku}: nuevo stock ${r.saldo}`, "ok");
    });
  }

  function exportar() {
    const com = comprometido();
    descargarXlsx("inventario.xlsx", "Inventario", [{ titulo: "Ubicación", ancho: 12 }, { titulo: "SKU", ancho: 12 }, { titulo: "Producto", ancho: 42 }, { titulo: "Categoría", ancho: 14 }, { titulo: "Físico", ancho: 10, tipo: "entero" }, { titulo: "Comprometido", ancho: 14, tipo: "entero" }, { titulo: "Disponible", ancho: 12, tipo: "entero" }, { titulo: "Estado", ancho: 14 }],
      filas().map((p) => [ubicacionSku(p.sku), p.sku, p.nombre, p.categoria, p.stock, com[p.sku] || 0, p.stock - (com[p.sku] || 0), estadoStock(p)[0]]));
    toast("Inventario exportado", "ok");
  }

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Bodega", href: "bodega" }, { t: "Inventario" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    document.getElementById("f-cat").innerHTML = '<option value="">Todas las categorías</option>' + Object.keys(CATEGORIAS).map((c) => `<option>${c}</option>`).join("");
    const xl = document.getElementById("btn-xl"); xl.hidden = !can("bd.inventario.exportar"); xl.onclick = exportar;
    document.getElementById("f-q").addEventListener("input", (e) => { f.q = e.target.value; render(); });
    document.getElementById("f-cat").addEventListener("change", (e) => { f.cat = e.target.value; render(); });
    document.getElementById("pills").addEventListener("click", (e) => { const b = e.target.closest("[data-est]"); if (b) { f.est = b.dataset.est; render(); } });
    document.getElementById("tb").addEventListener("click", (e) => { const b = e.target.closest("[data-aj]"); if (b) modalAjuste(b.dataset.aj); });
    document.querySelector(".tabs").addEventListener("click", (e) => { const b = e.target.closest("[data-tab]"); if (!b) return;
      document.querySelectorAll(".tabs .pill").forEach((x) => x.classList.toggle("on", x === b));
      document.getElementById("p-ex").hidden = b.dataset.tab !== "ex"; document.getElementById("p-mv").hidden = b.dataset.tab !== "mv"; });
    render();
  }
  if (s && !can("bd.inventario.ver")) location.replace("bodega"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
