<?php
$pageTitle  = 'Tienda · Mis pedidos';
$pageLayout = 'tienda';
$pageCss    = ['css/tienda.css'];
$pageLibs   = ['js/tienda.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="tw">
    <div id="main"></div>
  </main>
  <script>
    const $ = (id) => document.getElementById(id);

    const ses = requireCliente();
    if (ses) {
      pintarTienda("pedidos");
      const params = new URLSearchParams(location.search), id = params.get("id");
      function lista() {
        const l = misPedidos(ses.clienteId);
        $("main").innerHTML = '<div class="page-title"><div><div class="eyebrow">Tu cuenta</div><h1>Mis pedidos</h1></div><a class="btn primary" href="tienda">Seguir comprando</a></div>' +
          (l.length ? '<section class="panel">' + l.map((p) => { const e = estadoCliente(p); return `<a class="ped" href="t-pedidos?id=${esc(p.id)}"><div><b>${esc(p.id)}</b> <span class="badge" style="--c:${e.color}">${e.txt}</span><br><small>${fmtFecha(p.fecha)} · ${p.totales.unidades} producto${p.totales.unidades === 1 ? "" : "s"} · ${esc(p.entrega.municipio)}</small></div><b style="font-size:18px">${fmtQ(p.totales.total)}</b></a>`; }).join("") + "</section>"
            : '<div class="t-empty">Todavía no has hecho pedidos.<br><br><a class="btn primary" href="tienda">Ir a la tienda</a></div>');
      }
      function detalle(p) {
        const e = estadoCliente(p), t = p.totales;
        $("main").innerHTML = '<div class="page-title"><div><div class="eyebrow"><a href="t-pedidos" style="color:inherit">← Mis pedidos</a></div><h1>Pedido ' + esc(p.id) + '</h1><p>' + fmtFecha(p.fecha) + '</p></div><span class="badge" style="--c:' + e.color + ';font-size:14px;padding:8px 14px">' + e.txt + "</span></div>" +
          (params.get("nuevo") ? '<div class="okb"><b>¡Recibimos tu pedido!</b>Está en revisión. Cuando lo aprobemos, empezaremos a prepararlo.</div>' : "") +
          '<div class="two"><div><section class="panel" style="margin-bottom:18px"><div class="panel-h"><h2>Seguimiento</h2></div><div style="padding:18px 20px 22px">' +
          (e.paso ? `<div class="tl">${PASOS.map((s, i) => `<div class="${i + 1 <= e.paso ? "done" : ""} ${i + 1 === e.paso ? "now" : ""}">${s}</div>`).join("")}</div>` : "") +
          `<p style="margin:14px 0 0;color:${p.estado === "Cancelado" ? "#dc2626" : "var(--muted)"}">${esc(e.desc)}</p>` +
          (p.estado === "Por aprobar" ? '<button class="btn danger" id="canc" style="margin-top:14px">Cancelar pedido</button>' : "") + "</div></section>" +
          '<section class="panel"><div class="panel-h"><h2>Productos</h2></div>' + p.items.map((i) => `<div class="line" style="grid-template-columns:1fr auto"><div><h4>${esc(i.nombre)}</h4><small>${i.cantidad} × ${fmtQ(conIva(i.precio))}</small></div><b>${fmtQ(totalLinea(i).conIva)}</b></div>`).join("") + "</section></div>" +
          '<aside><section class="panel" style="margin-bottom:18px"><div class="panel-h"><h2>Resumen</h2></div><div style="padding:6px 20px 18px"><div class="sum-r"><span>Subtotal + descuentos</span><b>' + fmtQ(round2(t.base - t.envio)) + '</b></div><div class="sum-r"><span>IVA</span><b>' + fmtQ(t.iva) + '</b></div><div class="sum-r"><span>Envío</span><b>' + (t.envio ? fmtQ(conIva(t.envio)) : "Gratis") + '</b></div><div class="sum-t"><span>Total</span><span>' + fmtQ(t.total) + "</span></div></div></section>" +
          '<section class="panel"><div class="panel-h"><h2>Entrega y pago</h2></div><div style="padding:6px 20px 18px;font-size:14px;line-height:1.7;color:var(--muted)"><b style="color:#0f172a">' + esc(p.entrega.contacto) + "</b><br>" + esc(p.entrega.direccion) + ", " + esc(p.entrega.municipio) + ", " + esc(p.entrega.departamento) + "<br>Deseada: " + esc(p.fechaEntrega) + " · " + esc(p.franja) + "<br>Pago: " + esc(p.pago) + "</div></section></aside></div>";
        const c = $("canc"); if (c) c.onclick = async () => { if (await confirmar("Cancelar pedido", "Tu pedido aún no ha sido aprobado, así que puedes cancelarlo sin costo.", "Cancelar pedido", true)) { p.estado = "Cancelado"; p.motivoRechazo = "Cancelado por el cliente"; p.actualizado = new Date().toISOString(); savePedido(p); detalle(p); toast("Pedido cancelado", "ok"); } };
      }
      const p = id && getPedido(id);
      if (p && p.clienteId === ses.clienteId && p.estado !== "Borrador") detalle(p); else lista();
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

