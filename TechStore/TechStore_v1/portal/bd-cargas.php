<?php
$pageTitle = 'Preparación y carga';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js'];
$pageStyles = <<<'CSS'

  .tools { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px; }
  .tools .search { flex: 1 1 240px; } .tools .inp { height: 40px; }
  .pill { --c: #64748b; height: 32px; padding: 0 13px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: #f1f5f9; border: 1px solid var(--border); }
  .pill.on { color: #0f172a; background: color-mix(in srgb, var(--c) 15%, #ffffff); border-color: color-mix(in srgb, var(--c) 55%, transparent); }
  .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 18px; }
  .rc { border: 1px solid var(--border); border-radius: 18px; padding: 18px; background: #ffffff; display: flex; flex-direction: column; gap: 14px; transition: border-color .2s, transform .2s; }
  .rc:hover { border-color: var(--border-2); transform: translateY(-3px); }
  .rc-h { display: flex; align-items: center; gap: 12px; } .rc-h b { display: block; font-size: 16px; } .rc-h span.s { color: var(--muted); font-size: 12.5px; } .rc-h .badge { margin-left: auto; }
  .rc-g { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .prog { height: 8px; border-radius: 6px; background: #e2e8f0; overflow: hidden; } .prog i { display: block; height: 100%; background: #0284c7; border-radius: 6px; }
  .meta { display: flex; justify-content: space-between; font-size: 13px; color: var(--muted); } .meta b { color: #0f172a; }
  .rc .btn { margin-top: auto; justify-content: center; }
  .empty { padding: 50px; text-align: center; color: var(--muted); border: 1px dashed var(--border-2); border-radius: 16px; }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Bodega</div><h1>Preparación y carga</h1>
        <p>Rutas armadas por Logística. Prepara el picking, carga el camión y libera la salida.</p></div>
    </div>
    <section class="kpis" id="kpis"></section>
    <div class="tools">
      <div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg><input class="inp" id="f-q" type="search" placeholder="Ruta, vehículo, placa, piloto o pedido…" autocomplete="off"></div>
      <div id="pills" style="display:flex;gap:8px;flex-wrap:wrap"></div>
    </div>
    <section class="grid" id="grid"></section>
  </main>
  <script>

  const s = requireModulo("bodega");
  const COL = { "Pendiente": "#f4b63d", "En picking": "#22b8cf", "Cargada": "#2fc986", "Despachada": "#64748b", "Entregada": "#16a34a" };
  let f = { q: "", est: "activas" };
  const estBod = (r) => (r.estado === "Despachada" || r.estado === "Entregada") ? r.estado : estadoCarga(r);
  const esCerrada = (e) => e === "Despachada" || e === "Entregada";

  function lista() {
    const t = norm(f.q).trim();
    return getRutas().filter((r) => ["Planificada", "Despachada", "Entregada"].includes(r.estado)).filter((r) => {
      const v = getVehiculo(r.vehiculoId) || {};
      const e = estBod(r);
      return (f.est === "activas" ? !esCerrada(e) : f.est === "todas" ? true : f.est === "Despachada" ? esCerrada(e) : e === f.est) &&
        (!t || norm([r.id, r.piloto, v.nombre, v.placa, r.pedidos.join(" ")].join(" ")).includes(t));
    }).sort((a, b) => esCerrada(estBod(a)) - esCerrada(estBod(b)) || new Date(b.creadaEn || b.fecha) - new Date(a.creadaEn || a.fecha)).slice(0, 40);
  }
  function render() {
    const todas = getRutas().filter((r) => ["Planificada", "Despachada", "Entregada"].includes(r.estado));
    const n = (e) => todas.filter((r) => estBod(r) === e).length;
    const nCerradas = todas.filter((r) => esCerrada(estBod(r))).length;
    document.getElementById("pills").innerHTML = [["activas", "Activas", todas.length - nCerradas, "#64748b"], ["Pendiente", "Por preparar", n("Pendiente"), COL.Pendiente], ["En picking", "En picking", n("En picking"), COL["En picking"]], ["Cargada", "Cargadas", n("Cargada"), COL.Cargada], ["Despachada", "Despachadas", nCerradas, COL.Despachada]]
      .map(([k, t, c, col]) => `<button class="pill ${f.est === k ? "on" : ""}" data-est="${k}" style="--c:${col}">${t} <b>${c}</b></button>`).join("");
    const puede = can("bd.picking.ejecutar");
    const l = lista();
    document.getElementById("grid").innerHTML = l.length ? l.map((r) => {
      const v = getVehiculo(r.vehiculoId) || { nombre: "—", placa: "", capKg: 1, capM3: 1, tipo: "" }, c = cargaRuta(r), e = estBod(r);
      const lin = esCerrada(e) ? [] : lineasPicking(r);
      const hechas = (r.carga && r.carga.picks) ? lin.filter((x) => (r.carga.picks[x.sku] || 0) === x.cant).length : 0;
      const pct = lin.length ? Math.round(hechas / lin.length * 100) : (e === "Cargada" || esCerrada(e) ? 100 : 0);
      const uds = r.pedidos.reduce((a, id) => { const p = getPedido(id); return a + (p ? p.totales.unidades : 0); }, 0);
      const txt = e === "Pendiente" ? (puede ? "Preparar ruta" : "Ver") : e === "En picking" ? "Continuar" : "Ver detalle";
      return `<article class="rc"><div class="rc-h"><div class="vico" style="--c:${TIPOS_VEHICULO[v.tipo] || "#8b7cf6"}">${ICONO_CAMION}</div><div><b>${esc(r.id)} · ${esc(v.nombre)}</b><span class="s">${esc(v.placa)} · Piloto ${esc(r.piloto || "—")}</span></div><span class="badge" style="--c:${COL[e]}">${e}</span></div>` +
        `<div class="rc-g">${gaugeHTML("Peso", c.kg, v.capKg, "kg", 1)}${gaugeHTML("Volumen", c.m3, v.capM3, "m³", 2)}</div>` +
        `<div class="meta"><span><b>${r.pedidos.length}</b> pedidos</span><span><b>${uds}</b> unidades</span><span><b>${esCerrada(e) ? "—" : lin.length}</b> líneas</span></div>` +
        `<div><div class="meta" style="margin-bottom:6px"><span>Picking</span><b>${pct} %</b></div><div class="prog"><i style="width:${pct}%"></i></div></div>` +
        `<a class="btn ${e === "Pendiente" && puede ? "primary" : ""}" href="bd-picking?ruta=${r.id}">${txt}</a></article>`;
    }).join("") : '<div class="empty">No hay rutas en esta vista. Logística las crea en «Planificación de despacho».</div>';
  }
  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Bodega", href: "bodega" }, { t: "Preparación y carga" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    const rs = getRutas().filter((r) => r.estado === "Planificada");
    document.getElementById("kpis").innerHTML = [
      [rs.filter((r) => estadoCarga(r) === "Pendiente").length, "Por preparar", "#f4b63d"], [rs.filter((r) => estadoCarga(r) === "En picking").length, "En picking", "#22b8cf"],
      [rs.filter((r) => estadoCarga(r) === "Cargada").length, "Listas para salir", "#2fc986"],
      [rs.reduce((a, r) => a + r.pedidos.length, 0), "Pedidos por despachar", "#8b7cf6"]].map(([v, l, c]) => `<div class="kpi" style="--c:${c}"><div class="v">${v}</div><div class="l">${l}</div><div class="bar"></div></div>`).join("");
    document.getElementById("f-q").addEventListener("input", (e) => { f.q = e.target.value; render(); });
    document.getElementById("pills").addEventListener("click", (e) => { const b = e.target.closest("[data-est]"); if (b) { f.est = b.dataset.est; render(); } });
    render();
  }
  if (s && !can("bd.cargas.ver")) location.replace("bodega"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
