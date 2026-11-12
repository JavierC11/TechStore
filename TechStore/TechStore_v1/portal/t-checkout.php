<?php
$pageTitle  = 'Tienda · Finalizar pedido';
$pageLayout = 'tienda';
$pageCss    = ['css/tienda.css'];
$pageLibs   = ['js/tienda.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="tw">
    <div class="page-title"><div><div class="eyebrow">Último paso</div><h1>Finalizar pedido</h1></div><a class="btn" href="t-carrito">← Volver al carrito</a></div>
    <div class="two"><div id="form"></div><aside class="sticky"><section class="panel"><div class="panel-h"><h2>Tu pedido</h2></div><div style="padding:6px 20px 20px" id="res"></div></section></aside></div>
  </main>
  <script>
    const $ = (id) => document.getElementById(id);

    const ses = requireCliente();
    if (ses) {
      pintarTienda("tienda");
      if (!getCarrito().length) location.replace("t-carrito");
      const checkoutRef = "CHK-" + Date.now() + "-" + Math.random().toString(36).slice(2, 7);
      let pedidoEnviado = false;
      if (getCarrito().length && typeof pulsoTrack === "function") {
        pulsoTrack("checkout_iniciado", { canal: "Tienda web", ref_id: checkoutRef, valido: 1, once: true, detalle: "Inicio checkout web" });
      }
      window.addEventListener("pagehide", () => {
        if (!pedidoEnviado && getCarrito().length && typeof pulsoTrack === "function") {
          pulsoTrack("checkout_abandono", { canal: "Tienda web", ref_id: checkoutRef, valido: 0, once: true, beacon: true, detalle: "Abandono checkout web" });
        }
      });
      const cli = getCliente(ses.clienteId), E = { ...{ contacto: cli.nombre, telefono: cli.telefono, departamento: "", municipio: "", zona: "", direccion: "", referencia: "" }, ...(cli.entrega || {}) };
      const F = { ...{ nombre: cli.nombre, nit: cli.nit, direccion: "", email: cli.email }, ...(cli.facturacion || {}) };
      const maniana = new Date(Date.now() + 86400000), pagos = PAGOS.filter((p) => cli.tipo === "Empresa" || p !== "Crédito empresarial");
      const nota = { "Contra entrega": "Pagas al recibir tu pedido.", "Transferencia bancaria": "Te enviaremos los datos bancarios cuando aprobemos tu pedido.", "Tarjeta (enlace de pago)": "Te enviaremos un enlace de pago seguro cuando aprobemos tu pedido.", "Crédito empresarial": "Se factura a tu línea de crédito." };
      const fld = (id, label, val, cls, extra) => `<div class="field ${cls}"><label>${label}</label><input class="inp" id="${id}" value="${esc(val)}" ${extra || ""}><div class="err-f" id="er-${id}"></div></div>`;
      const dep = (v) => `<option value="">Selecciona…</option>` + Object.keys(UBICACIONES).map((d) => `<option ${d === v ? "selected" : ""}>${d}</option>`).join("");
      const mun = (d, v) => `<option value="">Selecciona…</option>` + (UBICACIONES[d] || []).map((m) => `<option ${m === v ? "selected" : ""}>${m}</option>`).join("");
      $("form").innerHTML = `<section class="panel" style="margin-bottom:18px"><div class="panel-h"><span class="n">1</span><h2>Dirección de entrega</h2></div><div class="frm">` +
        fld("e-c", "Quién recibe", E.contacto, "c6") + fld("e-t", "Teléfono de contacto", E.telefono, "c6", 'inputmode="numeric"') +
        `<div class="field c4"><label>Departamento</label><select class="inp" id="e-d">${dep(E.departamento)}</select><div class="err-f" id="er-e-d"></div></div><div class="field c4"><label>Municipio</label><select class="inp" id="e-m">${mun(E.departamento, E.municipio)}</select><div class="err-f" id="er-e-m"></div></div>` + fld("e-z", "Zona", E.zona, "c4") +
        fld("e-dir", "Dirección", E.direccion, "c12") + fld("e-r", "Referencia (opcional)", E.referencia, "c12") + `</div></section>` +
        `<section class="panel" style="margin-bottom:18px"><div class="panel-h"><span class="n">2</span><h2>Facturación</h2></div><div class="frm">` + fld("f-n", "Nombre en la factura", F.nombre, "c6") + fld("f-nit", "NIT", F.nit, "c6") +
        `<label class="c12" style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" id="f-igual" checked style="accent-color:#f97316;width:17px;height:17px"> La dirección de facturación es la misma de entrega</label>` + `<div id="f-dirw" class="c12" hidden>${fld("f-dir", "Dirección de facturación", F.direccion, "")}</div></div></section>` +
        `<section class="panel" style="margin-bottom:18px"><div class="panel-h"><span class="n">3</span><h2>Entrega deseada</h2></div><div class="frm"><div class="field c6"><label>Fecha</label><input class="inp" id="d-f" type="date" min="${fechaInput(maniana)}" value="${fechaInput(maniana)}"><div class="err-f" id="er-d-f"></div></div><div class="field c6"><label>Horario</label><select class="inp" id="d-h">${FRANJAS.map((f) => `<option>${f}</option>`).join("")}</select></div>` +
        `<div class="field c12"><label>Indicaciones para la entrega (opcional)</label><textarea class="inp" id="d-o" rows="2" style="height:auto" maxlength="200"></textarea></div></div></section>` +
        `<section class="panel"><div class="panel-h"><span class="n">4</span><h2>Forma de pago</h2></div><div class="frm">${pagos.map((p, i) => `<label class="opt-p"><input type="radio" name="pago" value="${esc(p)}" ${i === 0 ? "checked" : ""}><div><b>${esc(p)}</b><span>${esc(nota[p] || "")}</span></div></label>`).join("")}</div></section>`;
      const t = totalesCarrito();
      $("res").innerHTML = itemsCarrito().map((i) => `<div class="sum-r"><span>${i.cantidad} × ${esc(i.nombre)}</span><b>${fmtQ(totalLinea(i).conIva)}</b></div>`).join("") +
        `<div class="sum-r"><span>Envío</span><b>${t.gratis ? "Gratis" : fmtQ(conIva(t.envio))}</b></div><div class="sum-t"><span>Total (IVA incl.)</span><span>${fmtQ(t.total)}</span></div>` +
        '<p class="muted" style="font-size:13px;margin:12px 0">Tu pedido quedará <b style="color:#0f172a">en revisión</b>: nuestro equipo lo validará y te confirmará.</p><button class="btn primary" id="go" style="width:100%">Realizar pedido</button><div class="err-f" id="er-go" style="margin-top:8px"></div>';
      $("e-d").addEventListener("change", (e) => { $("e-m").innerHTML = mun(e.target.value, ""); });
      $("f-igual").addEventListener("change", (e) => { $("f-dirw").hidden = e.target.checked; });
      $("go").addEventListener("click", () => {
        document.querySelectorAll(".err-f").forEach((x) => (x.textContent = "")); document.querySelectorAll(".inp.bad").forEach((x) => x.classList.remove("bad"));
        const v = (id) => $(id).value.trim(), bad = (id, m) => { $("er-" + id).textContent = m; $(id).classList.add("bad"); return 1; };
        let n = 0;
        if (v("e-c").length < 3) n += bad("e-c", "Indica quién recibe.");
        if (soloDigitos(v("e-t")).length !== 8) n += bad("e-t", "Teléfono de 8 dígitos.");
        if (!v("e-d")) n += bad("e-d", "Elige el departamento."); if (!v("e-m")) n += bad("e-m", "Elige el municipio.");
        if (v("e-dir").length < 5) n += bad("e-dir", "Escribe la dirección completa.");
        if (v("f-n").length < 3) n += bad("f-n", "Nombre para la factura."); if (!v("f-nit")) n += bad("f-nit", "Escribe tu NIT o CF.");
        const igual = $("f-igual").checked; if (!igual && v("f-dir").length < 5) n += bad("f-dir", "Dirección de facturación.");
        if (!v("d-f") || v("d-f") < fechaInput(maniana)) n += bad("d-f", "Elige una fecha desde mañana.");
        if (n) {
          if (typeof pulsoTrack === "function") {
            pulsoTrack("validacion_error", { canal: "Tienda web", ref_id: checkoutRef, valido: 0, detalle: "Error validación checkout" });
          }
          $("er-go").textContent = "Revisa los campos marcados."; return;
        }
        const ent = { contacto: v("e-c"), telefono: fmtTel(v("e-t")), departamento: v("e-d"), municipio: v("e-m"), zona: v("e-z"), direccion: v("e-dir"), referencia: v("e-r") };
        const dirF = igual ? `${ent.direccion}${ent.zona ? ", zona " + ent.zona : ""}, ${ent.municipio}, ${ent.departamento}` : v("f-dir");
        const r = crearPedidoWeb({ entrega: ent, facturacion: { nombre: v("f-n"), nit: v("f-nit").toUpperCase(), direccion: dirF, email: cli.email }, pago: document.querySelector("[name=pago]:checked").value, franja: $("d-h").value, fechaEntrega: v("d-f"), obs: v("d-o"), checkoutRefId: checkoutRef });
        if (!r.ok) {
          if (typeof pulsoTrack === "function") {
            pulsoTrack("stock_fallo", { canal: "Tienda web", ref_id: checkoutRef, valido: 0, detalle: r.error });
          }
          $("er-go").textContent = r.error; return;
        }
        pedidoEnviado = true;
        location.href = "t-pedidos?id=" + encodeURIComponent(r.pedido.id) + "&nuevo=1";
      });
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

