<?php
$pageTitle = 'Rutas y despachos';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js', 'js/xlsx-mini.js'];
$pageStyles = <<<'CSS'

  .bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 14px 16px; border-bottom: 1px solid var(--border); }
  .bar .search { flex: 1 1 220px; } .bar select.inp, .bar .inp { height: 38px; }
  .bar select.inp { width: auto; }
  .pill { --c: #64748b; height: 30px; padding: 0 12px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: #f1f5f9; border: 1px solid var(--border); }
  .pill.on { color: #0f172a; background: color-mix(in srgb, var(--c) 15%, #ffffff); border-color: color-mix(in srgb, var(--c) 55%, transparent); }
  .mini { min-width: 150px; display: flex; flex-direction: column; gap: 8px; }
  .modal.wide { max-width: 860px; width: calc(100% - 24px); }
  .modal-b .gs { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
  .info { display: flex; gap: 22px; flex-wrap: wrap; margin-bottom: 16px; color: var(--muted); font-size: 13.5px; } .info b { color: #0f172a; display: block; font-size: 15px; }
  .ord button { width: 28px; height: 28px; padding: 0; }
  .empty-r { padding: 40px; text-align: center; color: var(--muted); }
  @media (max-width: 560px) { .modal-b .gs { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Logística</div><h1>Rutas y despachos</h1>
        <p>Historial de rutas: planificadas, despachadas y canceladas.</p></div>
      <div class="actions"><a class="btn" href="log-planificacion">Ir a planificación</a></div>
    </div>
    <section class="panel">
      <div class="bar">
        <div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg><input class="inp" id="f-q" type="search" placeholder="Ruta, piloto, placa o pedido…" autocomplete="off"></div>
        <select class="inp" id="f-veh"></select>
        <div style="display:flex;gap:8px;flex-wrap:wrap" id="pills"></div>
      </div>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Ruta</th><th>Fecha</th><th>Vehículo</th><th>Piloto</th><th>Pedidos</th><th>Ocupación</th><th>Estado</th><th></th></tr></thead><tbody id="tb"></tbody></table></div>
    </section>
  </main>
  <script>

  const s = requireModulo("logistica");
  let f = { q: "", veh: "", est: "" };

  function lista() {
    const t = norm(f.q).trim();
    return [...getRutas()].filter((r) => {
      const v = getVehiculo(r.vehiculoId);
      return (!f.est || r.estado === f.est) && (!f.veh || r.vehiculoId === f.veh) &&
        (!t || norm([r.id, r.piloto, v ? v.placa + " " + v.nombre : "", r.pedidos.join(" ")].join(" ")).includes(t));
    }).sort((a, b) => new Date(b.creadaEn || b.fecha) - new Date(a.creadaEn || a.fecha));
  }
  function render() {
    const all = getRutas();
    document.getElementById("pills").innerHTML = [["", "Todas", "#64748b"], ...Object.entries(ESTADOS_RUTA).map(([k, c]) => [k, k, c])]
      .map(([k, t, c]) => `<button class="pill ${f.est === k ? "on" : ""}" data-est="${k}" style="--c:${c}">${t} <b>${k ? all.filter((r) => r.estado === k).length : all.length}</b></button>`).join("");
    const l = lista();
    document.getElementById("tb").innerHTML = l.length ? l.map((r) => {
      const v = getVehiculo(r.vehiculoId) || { nombre: "—", placa: "", capKg: 1, capM3: 1 }, c = cargaRuta(r);
      return `<tr><td><b>${esc(r.id)}</b></td><td>${fmtFecha(r.creadaEn || r.fecha)}</td><td>${esc(v.nombre)}<div class="sub">${esc(v.placa)}</div></td><td>${esc(r.piloto || "—")}</td><td>${r.pedidos.length}</td>` +
        `<td><div class="mini">${gaugeHTML("Peso", c.kg, v.capKg, "kg", 1)}${gaugeHTML("Vol.", c.m3, v.capM3, "m³", 2)}</div></td>` +
        `<td><span class="badge" style="--c:${ESTADOS_RUTA[r.estado]}">${r.estado}</span></td><td><button class="btn sm" data-ver="${r.id}">Ver</button></td></tr>`;
    }).join("") : '<tr><td colspan="8" class="empty-r">No hay rutas con estos filtros.</td></tr>';
  }

  function detalle(id) {
    const r = getRuta(id); if (!r) return;
    const v = getVehiculo(r.vehiculoId), c = cargaRuta(r), plan = r.estado === "Planificada";
    const ov = document.createElement("div"); ov.className = "overlay";
    const filas = r.pedidos.map((pid, i) => {
      const p = getPedido(pid); if (!p) return "";
      const k = cargaPedido(p);
      return `<tr><td>${i + 1}</td><td><b>${esc(p.id)}</b></td><td>${esc(p.cliente.nombre)}</td><td>${esc(p.entrega.direccion || "")}<div class="sub">${esc(p.entrega.municipio)}, ${esc(p.entrega.departamento)}</div></td><td>${fmtKg(k.kg)}<div class="sub">${fmtM3(k.m3)}</div></td>` +
        (plan && can("log.plan.asignar") && estadoCarga(r) === "Pendiente" ? `<td class="ord" style="white-space:nowrap"><button class="btn sm icon" data-mv="-1" data-p="${esc(p.id)}">↑</button> <button class="btn sm icon" data-mv="1" data-p="${esc(p.id)}">↓</button> <button class="btn sm icon danger" data-q="${esc(p.id)}">✕</button></td>` : "<td></td>") + "</tr>";
    }).join("");
    ov.innerHTML = `<div class="modal wide"><div class="modal-h"><h3>Ruta ${esc(r.id)} <span class="badge" style="--c:${ESTADOS_RUTA[r.estado]};margin-left:8px">${r.estado}</span></h3><button class="btn sm icon ghost" data-x>✕</button></div><div class="modal-b">` +
      `<div class="info"><span>Vehículo<b>${esc(v.nombre)} · ${esc(v.placa)}</b></span><span>Piloto<b>${esc(r.piloto || "—")}</b></span><span>Creada<b>${fmtFecha(r.creadaEn || r.fecha)}</b></span>` +
      (r.despachadaEn ? `<span>Despachada<b>${fmtFecha(r.despachadaEn)}</b></span>` : "") +
      (r.entregadaEn ? `<span>Entregada<b>${fmtFecha(r.entregadaEn)}</b></span>` : "") + `</div>` +
      `<div class="gs">${gaugeHTML("Peso", c.kg, v.capKg, "kg", 1)}${gaugeHTML("Volumen", c.m3, v.capM3, "m³", 2)}</div>` +
      `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>#</th><th>Pedido</th><th>Cliente</th><th>Entrega</th><th>Carga</th><th></th></tr></thead><tbody>${filas || '<tr><td colspan="6" class="empty-r">Sin pedidos.</td></tr>'}</tbody></table></div></div>` +
      `<div class="modal-f">${can("log.exportar") ? '<button class="btn" data-xl>Exportar hoja de ruta</button>' : ""}` +
      (plan && can("log.rutas.crear") ? '<button class="btn danger" data-canc>Cancelar ruta</button>' : "") +
      (plan && can("log.rutas.despachar") ? (estadoCarga(r) === "Cargada" ? '<button class="btn primary" data-desp>Despachar ruta</button>' : '<button class="btn" disabled>Esperando carga de Bodega</button>') : "") +
      (r.estado === "Despachada" && can("log.rutas.entregar") ? '<button class="btn primary" data-ent>Confirmar entrega</button>' : "") + "</div></div>";
    document.body.appendChild(ov);
    ov.addEventListener("click", async (e) => {
      const t = e.target;
      if (t === ov || t.closest("[data-x]")) { ov.remove(); return; }
      const mv = t.closest("[data-mv]"), q = t.closest("[data-q]");
      if (mv) { moverParada(id, mv.dataset.p, Number(mv.dataset.mv)); ov.remove(); detalle(id); render(); }
      else if (q) { quitarPedido(id, q.dataset.q); ov.remove(); if (getRuta(id)) detalle(id); render(); toast("Pedido quitado de la ruta", "ok"); }
      else if (t.closest("[data-xl]")) exportar(r);
      else if (t.closest("[data-canc]")) { if (await confirmar("Cancelar ruta", "Los pedidos volverán a pendientes por asignar.", "Cancelar ruta", true)) { cancelarRuta(id); ov.remove(); render(); toast("Ruta cancelada", "ok"); } }
      else if (t.closest("[data-desp]")) { if (await confirmar("Despachar ruta", `Los ${r.pedidos.length} pedidos pasarán a «En ruta».`, "Despachar")) { const x = despacharRuta(id, s.name); toast(x.ok ? "Ruta despachada" : x.error, x.ok ? "ok" : "bad"); ov.remove(); render(); } }
      else if (t.closest("[data-ent]")) { if (await confirmar("Confirmar entrega", `Los ${r.pedidos.length} pedidos pasarán a «Entregado» y el vehículo quedará disponible.`, "Confirmar entrega")) { const x = entregarRuta(id, s.name); toast(x.ok ? "Ruta marcada como entregada" : x.error, x.ok ? "ok" : "bad"); ov.remove(); render(); } }
    });
  }

  function exportar(r) {
    const v = getVehiculo(r.vehiculoId);
    const cols = [{ titulo: "Parada", ancho: 8, tipo: "entero" }, { titulo: "Pedido", ancho: 14 }, { titulo: "Cliente", ancho: 28 }, { titulo: "Teléfono", ancho: 14 },
      { titulo: "Dirección", ancho: 40 }, { titulo: "Municipio", ancho: 18 }, { titulo: "Departamento", ancho: 18 }, { titulo: "Peso (kg)", ancho: 12, tipo: "decimal" },
      { titulo: "Volumen (m³)", ancho: 13, tipo: "decimal" }, { titulo: "Cobrar contra entrega", ancho: 20, tipo: "moneda" }];
    const filas = r.pedidos.map((id, i) => { const p = getPedido(id), k = cargaPedido(p);
      return [i + 1, p.id, p.cliente.nombre, p.cliente.telefono || "", p.entrega.direccion || "", p.entrega.municipio, p.entrega.departamento, k.kg, k.m3,
        /contra/i.test(p.pago || "") ? p.totales.total : 0]; });
    descargarXlsx(`hoja-ruta-${r.id}.xlsx`, `Ruta ${r.id}`, cols, filas);
    toast("Hoja de ruta exportada", "ok");
  }

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Logística", href: "logistica" }, { t: "Rutas y despachos" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    document.getElementById("f-veh").innerHTML = '<option value="">Todos los vehículos</option>' + getVehiculos().map((v) => `<option value="${v.id}">${esc(v.nombre)}</option>`).join("");
    document.getElementById("f-q").addEventListener("input", (e) => { f.q = e.target.value; render(); });
    document.getElementById("f-veh").addEventListener("change", (e) => { f.veh = e.target.value; render(); });
    document.getElementById("pills").addEventListener("click", (e) => { const b = e.target.closest("[data-est]"); if (b) { f.est = b.dataset.est; render(); } });
    document.getElementById("tb").addEventListener("click", (e) => { const b = e.target.closest("[data-ver]"); if (b) detalle(b.dataset.ver); });
    render();
    const ver = new URLSearchParams(location.search).get("ver"); if (ver) detalle(ver);
  }
  if (s && !can("log.rutas.ver")) location.replace("logistica"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
