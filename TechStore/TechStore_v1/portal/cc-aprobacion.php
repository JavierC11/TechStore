<?php
$pageTitle = 'Pedidos web por aprobar';
$pageStyles = <<<'CSS'

  .modal.wide { max-width: 860px; width: calc(100% - 24px); }
  .selbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 10px 16px; background: rgba(249,115,22,.14); border-bottom: 1px solid rgba(249,115,22,.4); font-size: 14px; }
  .selbar .sp { margin-left: auto; display: flex; gap: 8px; }
  .chk { display: inline-flex; gap: 5px; align-items: center; font-size: 12.5px; font-weight: 600; padding: 3px 9px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--c) 55%, transparent); background: color-mix(in srgb, var(--c) 16%, transparent); white-space: nowrap; }
  .grid-d { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; } .grid-d h4 { margin: 0 0 6px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: var(--faint); } .grid-d p { margin: 0; line-height: 1.55; font-size: 14px; color: #334155; }
  .iss { margin-bottom: 14px; padding: 11px 14px; border-radius: 12px; font-size: 13.5px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
  .iss.w { background: #fefce8; border-color: #fef08a; color: #854d0e; }
  .rej { display: none; margin-top: 14px; padding: 14px; border-radius: 14px; background: #f8fafc; border: 1px solid var(--border); } .rej.on { display: block; } .rej .field { margin-bottom: 10px; }
  td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; } .empty-r { padding: 44px; text-align: center; color: var(--muted); }
  @media (max-width: 640px) { .grid-d { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Contact Center</div><h1>Pedidos web por aprobar</h1><p>Pedidos que los clientes hicieron en la tienda. Al aprobarlos pasan a «Pendiente de despacho»; al rechazarlos se cancelan y el cliente ve el motivo.</p></div>
    </div>
    <section class="kpis" id="kpis"></section>
    <section class="panel" style="margin-bottom:18px">
      <div class="panel-h"><h2>Cola de revisión</h2><span class="sub" id="sub"></span></div>
      <div class="selbar" id="selbar" hidden></div>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th style="width:36px"></th><th>Pedido</th><th>Recibido</th><th>Cliente</th><th>Entrega</th><th>Pago</th><th class="n">Total</th><th>Verificación</th><th></th></tr></thead><tbody id="tb"></tbody></table></div>
    </section>
    <section class="panel"><div class="panel-h"><h2>Resueltos recientemente</h2><span class="sub">pedidos web</span></div><div class="tbl-wrap"><table class="tbl"><thead><tr><th>Pedido</th><th>Cliente</th><th>Resultado</th><th>Por</th><th>Cuándo</th></tr></thead><tbody id="hist"></tbody></table></div></section>
  </main>
  <script>

  const s = requireModulo("contact_center");
  const sel = new Set();
  const MOTIVOS = ["Datos de contacto o dirección incorrectos", "Producto sin disponibilidad", "No pudimos validar al cliente", "Pedido duplicado", "Otro motivo"];
  const cola = () => getPedidos().filter((p) => p.estado === "Por aprobar").sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
  const prod = (sku) => PRODUCTOS.find((x) => x.sku === sku);
  function revisar(p) {
    const faltantes = p.items.filter((i) => !prod(i.sku) || prod(i.sku).stock < i.cantidad);
    const datos = [];
    if (!/^\d{4}-?\d{4}$/.test(String(p.cliente.telefono || "").trim())) datos.push("teléfono del cliente");
    if (!/^\S+@\S+\.\S+$/.test(String(p.cliente.email || ""))) datos.push("correo del cliente");
    if (!String(p.entrega.direccion || "").trim() || !p.entrega.departamento || !p.entrega.municipio) datos.push("dirección de entrega");
    if (!String(p.cliente.nit || "").trim()) datos.push("NIT");
    return { stockOk: !faltantes.length, faltantes, datosOk: !datos.length, datos };
  }
  const hace = (iso) => { const m = Math.max(0, Math.floor((Date.now() - new Date(iso)) / 60000)); return m < 60 ? `hace ${m} min` : m < 1440 ? `hace ${Math.floor(m / 60)} h` : `hace ${Math.floor(m / 1440)} d`; };
  const chk = (ok, t) => `<span class="chk" style="--c:${ok ? "#2fc986" : "#ef5b66"}">${ok ? "●" : "■"} ${t}</span>`;

  function render() {
    const c = cola(), tot = c.reduce((a, p) => a + p.totales.total, 0), viejo = c[0];
    document.getElementById("kpis").innerHTML = [[c.length, "Por aprobar", "#e0709f"], [viejo ? hace(viejo.fecha).replace("hace ", "") : "—", "Espera del más antiguo", "#f4b63d"], [c.filter((p) => { const r = revisar(p); return r.stockOk && r.datosOk; }).length, "Listos para aprobar", "#2fc986"], [fmtQ(tot), "Valor en revisión", "#22b8cf"]]
      .map(([v, l, col]) => `<div class="kpi" style="--c:${col}"><div class="v">${v}</div><div class="l">${l}</div><div class="bar"></div></div>`).join("");
    document.getElementById("sub").textContent = c.length + " pedidos";
    for (const id of [...sel]) if (!c.some((p) => p.id === id)) sel.delete(id);
    const pa = can("cc.web.aprobar");
    document.getElementById("tb").innerHTML = c.length ? c.map((p) => { const r = revisar(p);
      return `<tr><td>${pa && r.stockOk && r.datosOk ? `<input type="checkbox" data-sel="${esc(p.id)}" ${sel.has(p.id) ? "checked" : ""} style="accent-color:#f97316;width:17px;height:17px">` : ""}</td><td><b>${esc(p.id)}</b></td><td>${hace(p.fecha)}<div class="sub">${fmtFecha(p.fecha)}</div></td>` +
        `<td><b style="font-weight:600">${esc(p.cliente.nombre)}</b><div class="sub">${esc(p.cliente.tipo)} · ${esc(p.cliente.telefono)}</div></td><td>${esc(p.entrega.municipio)}<div class="sub">${esc(p.entrega.departamento)}</div></td><td>${esc(p.pago)}</td><td class="n"><b>${fmtQ(p.totales.total)}</b></td>` +
        `<td>${chk(r.datosOk, "Datos")} ${chk(r.stockOk, "Stock")}</td><td><button class="btn sm primary" data-rev="${esc(p.id)}">Revisar</button></td></tr>`; }).join("")
      : '<tr><td colspan="9" class="empty-r">No hay pedidos web pendientes de revisión.</td></tr>';
    const sb = document.getElementById("selbar");
    if (sel.size && pa) { sb.hidden = false; sb.innerHTML = `<span><b>${sel.size}</b> seleccionados</span><span class="sp"><button class="btn sm primary" data-bulk>Aprobar seleccionados</button><button class="btn sm" data-clr>Limpiar</button></span>`; } else sb.hidden = true;
    const h = getPedidos().filter((p) => (p.canal === "Tienda web" || p.origen === "web") && (p.aprobadoPor || p.rechazadoPor)).sort((a, b) => new Date(b.actualizado) - new Date(a.actualizado)).slice(0, 8);
    document.getElementById("hist").innerHTML = h.length ? h.map((p) => `<tr><td><b>${esc(p.id)}</b></td><td>${esc(p.cliente.nombre)}</td><td>${p.aprobadoPor ? '<span class="badge" style="--c:#2fc986">Aprobado</span>' : `<span class="badge" style="--c:#ef5b66">Rechazado</span> <span class="sub">${esc(p.motivoRechazo || "")}</span>`}</td><td>${esc(p.aprobadoPor || p.rechazadoPor)}</td><td>${fmtFecha(p.fechaAprobacion || p.actualizado)}</td></tr>`).join("") : '<tr><td colspan="5" class="empty-r">Aún no hay pedidos resueltos.</td></tr>';
  }

  function aprobar(p) {
    const r = revisar(p);
    if (!r.stockOk) return { ok: false, error: `${p.id}: stock insuficiente (${r.faltantes.map((i) => i.sku).join(", ")}).` };
    const ahora = new Date().toISOString();
    p.estado = "Pendiente de despacho"; p.fechaAprobacion = ahora; p.aprobadoPor = s.name; p.actualizado = ahora;
    p.minLiquidacion = Math.max(0.5, round2((new Date(ahora) - new Date(p.fecha)) / 60000));
    savePedido(p);
    auditar("Pedido web aprobado", p.id); return { ok: true };
  }
  function modal(p) {
    const r = revisar(p), pa = can("cc.web.aprobar"), pr = can("cc.web.aprobar"), ov = document.createElement("div"); ov.className = "overlay";
    ov.innerHTML = `<div class="modal wide"><div class="modal-h"><h3>Revisar ${esc(p.id)} <span class="muted" style="font-weight:400;font-size:14px">· ${hace(p.fecha)}</span></h3><button class="btn sm icon ghost" data-x>✕</button></div><div class="modal-b">` +
      (r.stockOk ? "" : `<div class="iss"><b>Stock insuficiente:</b> ${r.faltantes.map((i) => `${esc(i.nombre)} (pide ${i.cantidad}, hay ${prod(i.sku) ? prod(i.sku).stock : 0})`).join("; ")}. No se puede aprobar hasta que haya existencias.</div>`) +
      (r.datosOk ? "" : `<div class="iss w"><b>Datos por verificar:</b> ${r.datos.join(", ")}. Contacta al cliente antes de aprobar.</div>`) +
      `<div class="grid-d"><div><h4>Cliente</h4><p><b>${esc(p.cliente.nombre)}</b> (${esc(p.cliente.tipo)})<br>NIT ${esc(p.cliente.nit)}<br>${esc(p.cliente.telefono)} · ${esc(p.cliente.email)}</p></div>` +
      `<div><h4>Entrega</h4><p>${esc(p.entrega.contacto)} · ${esc(p.entrega.telefono)}<br>${esc(p.entrega.direccion)}${p.entrega.zona ? ", zona " + esc(p.entrega.zona) : ""}<br>${esc(p.entrega.municipio)}, ${esc(p.entrega.departamento)}<br>Deseada: ${esc(p.fechaEntrega)} · ${esc(p.franja)}${p.obsCliente ? "<br><i>“" + esc(p.obsCliente) + "”</i>" : ""}</p></div>` +
      `<div><h4>Facturación</h4><p>${esc(p.facturacion.nombre)}<br>NIT ${esc(p.facturacion.nit)}<br>${esc(p.facturacion.direccion)}</p></div><div><h4>Pago</h4><p>${esc(p.pago)}<br><b>${fmtQ(p.totales.total)}</b> (IVA incl.)</p></div></div>` +
      `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Producto</th><th class="n">Pide</th><th class="n">Stock</th><th class="n">Total</th></tr></thead><tbody>${p.items.map((i) => { const st = prod(i.sku) ? prod(i.sku).stock : 0; return `<tr><td>${esc(i.nombre)}<div class="sub">${esc(i.sku)}</div></td><td class="n">${i.cantidad}</td><td class="n" style="color:${st >= i.cantidad ? "inherit" : "#dc2626"}">${st}</td><td class="n">${fmtQ(totalLinea(i).conIva)}</td></tr>`; }).join("")}</tbody></table></div>` +
      `<div class="rej" id="rej"><div class="field"><label>Motivo del rechazo (lo verá el cliente)</label><select class="inp" id="rm">${MOTIVOS.map((m) => `<option>${m}</option>`).join("")}</select></div><div class="field"><label>Detalle (opcional)</label><input class="inp" id="rd" maxlength="120"></div><button class="btn danger" id="rok">Confirmar rechazo</button></div></div>` +
      `<div class="modal-f"><button class="btn ghost" data-x>Cerrar</button>${pr ? '<button class="btn danger ghost" id="rb">Rechazar…</button>' : ""}${pa ? `<button class="btn primary" id="ap" ${r.stockOk ? "" : "disabled"}>Aprobar pedido</button>` : ""}</div></div>`;
    document.body.appendChild(ov);
    ov.addEventListener("click", (e) => {
      if (e.target === ov || e.target.closest("[data-x]")) { ov.remove(); return; }
      if (e.target.closest("#rb")) ov.querySelector("#rej").classList.toggle("on");
      else if (e.target.closest("#ap")) { const x = aprobar(p); toast(x.ok ? `${p.id} aprobado: pasa a «Pendiente de despacho»` : x.error, x.ok ? "ok" : "bad"); if (x.ok) { ov.remove(); render(); } }
      else if (e.target.closest("#rok")) {
        const m = ov.querySelector("#rm").value, d = ov.querySelector("#rd").value.trim(), ahora = new Date().toISOString();
        p.estado = "Cancelado"; p.motivoRechazo = m + (d ? ": " + d : ""); p.rechazadoPor = s.name; p.actualizado = ahora; savePedido(p);
        if (!r.datosOk) pulsoTrack("validacion_error", { canal: p.canal || "Tienda web", ref_id: p.id, valido: 0, once: true, detalle: "Rechazado: " + r.datos.join(", ") });
        if (!r.stockOk) pulsoTrack("stock_fallo", { canal: p.canal || "Tienda web", ref_id: p.id, valido: 0, once: true, detalle: "Rechazado por stock" });
        auditar("Pedido web rechazado", `${p.id} · ${p.motivoRechazo}`); ov.remove(); render(); toast(`${p.id} rechazado`, "ok");
      }
    });
  }

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Contact Center", href: "contact-center" }, { t: "Pedidos web por aprobar" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    document.addEventListener("click", async (e) => {
      const v = e.target.closest("[data-rev]"), b = e.target.closest("[data-bulk]");
      if (v) modal(getPedido(v.dataset.rev));
      else if (e.target.closest("[data-clr]")) { sel.clear(); render(); }
      else if (b) {
        if (await confirmar("Aprobar pedidos", `Se aprobarán ${sel.size} pedidos y pasarán a «Pendiente de despacho».`, "Aprobar")) {
          let ok = 0; [...sel].forEach((id) => { if (aprobar(getPedido(id)).ok) ok++; }); sel.clear(); toast(`${ok} pedidos aprobados`, "ok"); render();
        }
      }
    });
    document.getElementById("tb").addEventListener("change", (e) => { const c = e.target.closest("[data-sel]"); if (c) { c.checked ? sel.add(c.dataset.sel) : sel.delete(c.dataset.sel); render(); } });
    render();
    const ver = new URLSearchParams(location.search).get("ver"); const p = ver && getPedido(ver); if (p && p.estado === "Por aprobar") modal(p);
  }
  if (s && !can("cc.web.aprobar")) location.replace("contact-center"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
