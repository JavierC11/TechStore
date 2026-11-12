<?php
$pageTitle = 'Pedido';
$pageStyles = <<<'CSS'

  .layout { display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: 22px; align-items: start; }
  .col-main { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
  .side { position: sticky; top: 84px; }
  .muted { color: var(--muted); } .faint { color: var(--faint); }

  .banner { margin-bottom: 18px; padding: 14px 18px; border-radius: 14px; display: flex; gap: 12px; align-items: center;
    background: #fefce8; border: 1px solid #fef08a; color: #854d0e; line-height: 1.45; }

  /* ----- Cliente ----- */
  #sec-cliente { position: relative; z-index: 6; }   /* para que la lista de resultados quede por encima de las demás secciones */
  .cli-row { display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap; }
  .cli-find { position: relative; flex: 1 1 320px; }
  .dd { position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 40; max-height: 330px; overflow: auto;
    background: #ffffff; border: 1px solid var(--border-2); border-radius: 14px; box-shadow: 0 20px 50px rgba(15,23,42,.16); padding: 6px; }
  .dd-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px; border: 0; border-radius: 10px; background: transparent; text-align: left; cursor: pointer; color: #0f172a; }
  .dd-item:hover, .dd-item.act { background: #f1f5f9; }
  .dd-item .avatar { width: 38px; height: 38px; border-radius: 12px; font-size: 13px; }
  .dd-item b { display: block; font-size: 14.5px; color: #0f172a; } .dd-item span { color: var(--muted); font-size: 12.5px; }
  .dd-empty { padding: 14px; color: var(--muted); font-size: 14px; }
  .cli-card { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
  .cli-card .avatar { width: 56px; height: 56px; font-size: 19px; border-radius: 17px; }
  .cli-card .info { flex: 1 1 280px; min-width: 0; }
  .cli-card h3 { margin: 0 0 4px; font-size: 19px; letter-spacing: -.3px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .cli-meta { display: flex; flex-wrap: wrap; gap: 6px 18px; color: var(--muted); font-size: 13.5px; }
  .cli-meta b { color: #0f172a; font-weight: 600; }

  /* ----- Direcciones ----- */
  .two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
  .addr { border: 1px solid var(--border); border-radius: 16px; background: #f8fafc; padding: 16px; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
  .addr-h { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .addr-h h3 { margin: 0; font-size: 15px; display: flex; align-items: center; gap: 9px; }
  .addr-h h3 svg { width: 18px; height: 18px; stroke: var(--orange-soft); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .addr-h .sp { margin-left: auto; display: flex; gap: 6px; }
  .addr-view { line-height: 1.6; font-size: 14.5px; }
  .addr-view .l1 { font-weight: 700; }
  .addr-view .ref { color: var(--muted); font-size: 13.5px; }
  .addr-warn { color: #b45309; font-size: 13.5px; }
  .addr .grid-form { grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
  .addr .grid-form .c3 { grid-column: span 3; } .addr .grid-form .c2 { grid-column: span 2; } .addr .grid-form .c6 { grid-column: span 6; } .addr .grid-form .c4 { grid-column: span 4; }
  .addr .links { display: flex; gap: 14px; flex-wrap: wrap; }
  .lnk { background: none; border: 0; padding: 0; color: var(--orange-soft); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }
  .lnk:hover { color: #1d4ed8; }

  /* ----- Catálogo ----- */
  .cat-top { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 12px; }
  .cat-top .search { flex: 1 1 280px; }
  .chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .chip2 { height: 34px; padding: 0 13px; border-radius: 999px; border: 1px solid var(--border); background: #f1f5f9; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; }
  .chip2:hover { color: #0f172a; background: #e2e8f0; }
  .chip2.on { color: #1e40af; background: #eff6ff; border-color: #93c5fd; }
  .cat-list { max-height: 352px; overflow-y: auto; border: 1px solid var(--border); border-radius: 14px; background: #ffffff; }
  .prow { display: grid; grid-template-columns: 44px minmax(0, 1fr) 120px 150px 124px; gap: 14px; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--border); }
  .prow:last-child { border-bottom: 0; }
  .prow:hover { background: #f8fafc; }
  .prow.out { opacity: .55; }
  .pic { --c: #0284c7; width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; background: color-mix(in srgb, var(--c) 12%, #ffffff); border: 1px solid color-mix(in srgb, var(--c) 35%, transparent); }
  .pic svg { width: 22px; height: 22px; stroke: var(--c); fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .pname { font-weight: 700; font-size: 14.5px; line-height: 1.25; }
  .psku { color: var(--faint); font-size: 12.5px; margin-top: 2px; }
  .pprice { text-align: right; line-height: 1.3; font-variant-numeric: tabular-nums; }
  .pprice b { font-size: 15px; } .pprice span { display: block; color: var(--muted); font-size: 12.5px; }
  .pact { display: flex; justify-content: flex-end; }
  .stepper { display: inline-flex; align-items: center; border: 1px solid var(--border-2); border-radius: 10px; overflow: hidden; background: #f8fafc; }
  .stepper button { width: 32px; height: 34px; border: 0; background: transparent; cursor: pointer; font-size: 18px; line-height: 1; color: #0f172a; }
  .stepper button:hover:not(:disabled) { background: #e2e8f0; }
  .stepper button:disabled { opacity: .35; cursor: not-allowed; }
  .stepper input { width: 46px; height: 34px; border: 0; background: transparent; text-align: center; font-weight: 700; outline: none; -moz-appearance: textfield; font-variant-numeric: tabular-nums; }
  .stepper input::-webkit-outer-spin-button, .stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  .stepper.sm button { width: 28px; height: 30px; } .stepper.sm input { width: 42px; height: 30px; }
  .hintbar { margin: 10px 2px 0; font-size: 12.5px; color: var(--faint); }

  .lines-h { display: flex; align-items: center; justify-content: space-between; margin: 24px 0 10px; }
  .lines-h h3 { margin: 0; font-size: 16px; }
  .tbl.lines td, .tbl.lines th { padding: 10px 12px; }
  .tbl.lines input.d { width: 64px; height: 34px; text-align: center; padding: 0 6px; }
  .tbl.lines tr.bad td { background: #fef2f2; }
  .tbl.lines tr.flash td { animation: glow 1.6s ease 1; }
  @keyframes glow { 0% { background: rgba(249,115,22,.3); } 100% { background: transparent; } }
  .empty-lines { padding: 30px 16px; text-align: center; color: var(--muted); border: 1px dashed var(--border-2); border-radius: 14px; }
  .warn-line { color: #dc2626; font-size: 12px; margin-top: 3px; }

  /* ----- Resumen ----- */
  .side .panel-b { display: flex; flex-direction: column; gap: 16px; }
  .sum-id { font-size: 13px; color: var(--muted); }
  .sum-block { padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid var(--border); font-size: 14px; line-height: 1.5; }
  .sum-block .t { font-size: 11.5px; letter-spacing: .9px; text-transform: uppercase; color: var(--faint); font-weight: 700; margin-bottom: 3px; }
  .minis { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .mini { padding: 12px 14px; border-radius: 10px; background: #f8fafc; border: 1px solid var(--border); }
  .mini b { display: block; font-size: 24px; letter-spacing: -.5px; line-height: 1.1; color: #0f172a; } .mini span { color: var(--muted); font-size: 12.5px; }
  .totals { display: flex; flex-direction: column; gap: 9px; font-size: 14.5px; font-variant-numeric: tabular-nums; }
  .totals .r { display: flex; justify-content: space-between; gap: 10px; color: var(--muted); }
  .totals .r b { color: #0f172a; font-weight: 600; }
  .totals .r.neg b { color: #16a34a; }
  .totals .grand { margin-top: 4px; padding-top: 14px; border-top: 1px dashed var(--border-2); display: flex; justify-content: space-between; align-items: baseline; }
  .totals .grand span { font-weight: 700; color: #0f172a; } .totals .grand b { font-size: 28px; letter-spacing: -1px; color: #2563eb; }
  .checks { display: flex; flex-direction: column; gap: 8px; font-size: 13.5px; }
  .checks .c { display: flex; align-items: flex-start; gap: 9px; color: var(--muted); }
  .checks .c i { flex: none; width: 18px; height: 18px; border-radius: 50%; display: grid; place-items: center; font-style: normal; font-size: 11px; font-weight: 800; background: #f1f5f9; border: 1px solid var(--border-2); color: transparent; margin-top: 1px; }
  .checks .c.ok { color: #166534; } .checks .c.ok i { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
  .checks .c.bad { color: #991b1b; } .checks .c.bad i { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  .side .acts { display: flex; flex-direction: column; gap: 10px; }
  .side .acts .btn { height: 46px; font-size: 15px; }

  .overlay .modal .grid-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .overlay .modal .grid-form .c6 { grid-column: span 1; } .overlay .modal .grid-form .c12 { grid-column: span 2; }

  @media (max-width: 1180px) { .layout { grid-template-columns: 1fr; } .side { position: static; } }
  @media (max-width: 860px) {
    .two { grid-template-columns: 1fr; }
    .prow { grid-template-columns: 44px minmax(0, 1fr) 110px; }
    .prow .pstock { display: none; } .prow .pact { grid-column: 3; } .prow .pprice { grid-column: 2; text-align: left; }
  }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Contact Center</div>
        <h1 id="titulo">Pedido</h1>
        <p id="subt"></p>
      </div>
      <div class="actions">
        <a class="btn" href="cc-pedidos"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Lista de pedidos</a>
      </div>
    </div>

    <div id="banner"></div>
    <div id="noenc" hidden class="panel panel-b" style="text-align:center;padding:50px 20px">
      <h2 style="margin-top:0">No encontramos ese pedido</h2>
      <p class="muted">Puede que haya sido eliminado o que el enlace sea incorrecto.</p>
      <a class="btn primary" href="cc-pedidos">Volver a la lista</a>
    </div>

    <fieldset id="fs">
      <div class="layout" id="layout">
        <div class="col-main">

          <!-- 1. Cliente -->
          <section class="panel" id="sec-cliente">
            <div class="panel-h"><span class="n">1</span><h2>Cliente</h2><span class="sub" id="cli-sub"></span></div>
            <div class="panel-b" id="cli-body"></div>
          </section>

          <!-- 2. Direcciones -->
          <section class="panel" id="sec-dir">
            <div class="panel-h"><span class="n">2</span><h2>Entrega y facturación</h2><span class="sub">Los cambios aplican solo a este pedido</span></div>
            <div class="panel-b"><div class="two" id="dir-body"></div></div>
          </section>

          <!-- 3. Productos -->
          <section class="panel" id="sec-prod">
            <div class="panel-h"><span class="n">3</span><h2>Productos</h2><span class="sub" id="prod-sub"></span></div>
            <div class="panel-b">
              <div class="cat-top">
                <div class="search">
                  <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                  <input class="inp" id="prod-q" type="search" placeholder="Buscar producto por nombre o SKU…" autocomplete="off">
                </div>
                <div class="chips" id="chips"></div>
              </div>
              <div class="cat-list" id="cat-list"></div>
              <div class="hintbar">Pulsa <b>Agregar</b> para sumar un producto. Con el cursor en el buscador, <b>Enter</b> agrega el producto si el resultado es único o si escribes el SKU exacto.</div>

              <div class="lines-h"><h3>Productos del pedido</h3><span class="muted" id="lines-count"></span></div>
              <div id="lines"></div>
            </div>
          </section>

          <!-- 4. Detalles -->
          <section class="panel" id="sec-det">
            <div class="panel-h"><span class="n">4</span><h2>Detalles del pedido</h2></div>
            <div class="panel-b"><div class="grid-form" id="det-body"></div></div>
          </section>
        </div>

        <aside class="side"><div class="panel" id="resumen"></div></aside>
      </div>
    </fieldset>
  </main>
  <script>

  const s = requireModulo("contact_center");
  const params = new URLSearchParams(location.search);

  let P = null;                 // pedido que se está editando (copia de trabajo)
  let modo = "nuevo";           // nuevo | editar | ver
  let dirty = false, mostrarErrores = false;
  let editEnt = false, editFac = false;
  let catFiltro = "Todas", ultimo = null;
  const RESERVADO = {};         // sku -> cantidad que este pedido ya tenía reservada

  const SVG = {
    pin: '<svg viewBox="0 0 24 24"><path d="M12 21s7-5.6 7-11a7 7 0 0 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    doc: '<svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
    search: '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>',
    trash: '<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
  };

  /* ---------- Utilidades de estado ---------- */
  const clon = (o) => JSON.parse(JSON.stringify(o));
  const blankEntrega = () => ({ contacto: "", telefono: "", departamento: "", municipio: "", zona: "", direccion: "", referencia: "" });
  const blankFact = () => ({ nombre: "", nit: "", direccion: "", email: "" });
  function nuevoPedido() {
    return { id: null, estado: "Borrador", fecha: null, clienteId: null, cliente: null, entrega: blankEntrega(), facturacion: blankFact(), items: [],
      envio: 0, canal: "Teléfono", pago: "Contra entrega", prioridad: "Normal", franja: FRANJAS[0], fechaEntrega: "", obsCliente: "", notasInternas: "" };
  }
  function getP(path) { return path.split(".").reduce((o, k) => (o == null ? "" : o[k]), P); }
  function setP(path, v) { const a = path.split("."); const k = a.pop(); a.reduce((o, x) => o[x], P)[k] = v; }
  const prod = (sku) => PRODUCTOS.find((p) => p.sku === sku);
  const disponible = (sku) => ((prod(sku) || {}).stock || 0) + (RESERVADO[sku] || 0);
  const editable = () => modo !== "ver";
  const marcar = () => { dirty = true; };

  /* ---------- Validación ---------- */
  const telOk = (t) => soloDigitos(t).length === 8;
  function entregaCompleta(e) { return !!(e.contacto && telOk(e.telefono) && e.departamento && e.municipio && e.direccion); }
  function factCompleta(f) { return !!(f.nombre && f.nit && f.direccion); }
  function validar() {
    const sinStock = P.items.filter((i) => i.cantidad < 1 || i.cantidad > disponible(i.sku));
    return [
      { id: "cliente", ok: !!P.clienteId, label: "Cliente seleccionado" },
      { id: "entrega", ok: entregaCompleta(P.entrega), label: "Dirección de entrega completa" },
      { id: "fact", ok: factCompleta(P.facturacion), label: "Datos de facturación completos" },
      { id: "items", ok: P.items.length > 0, label: "Al menos un producto" },
      { id: "stock", ok: sinStock.length === 0, label: sinStock.length ? "Stock insuficiente: " + sinStock.map((i) => i.sku).join(", ") : "Stock suficiente" },
    ];
  }
  const diferenteDeCliente = (tipo) => {
    const c = P.clienteId ? getCliente(P.clienteId) : null;
    if (!c) return false;
    const a = P[tipo], b = c[tipo];
    return Object.keys(a).some((k) => (a[k] || "") !== (b[k] || ""));
  };

  /* ---------- Cabecera de página ---------- */
  function renderTitulo() {
    const t = document.getElementById("titulo"), st = document.getElementById("subt");
    if (modo === "nuevo") { t.textContent = "Nuevo pedido"; st.textContent = "Busca al cliente, agrega productos y confirma las direcciones."; }
    else {
      t.textContent = (modo === "editar" ? "Editar pedido " : "Pedido ") + P.id;
      st.textContent = `Creado el ${fmtFecha(P.fecha)} por ${P.creadoPor}`;
    }
    document.title = `${APP.empresa} | ${APP.producto} · ${t.textContent}`;
  }
  function renderBanner() {
    const b = document.getElementById("banner");
    if (modo !== "ver") { b.innerHTML = ""; return; }
    const motivo = !can("cc.pedidos.editar")
      ? "No tienes permiso para editar pedidos. Solo puedes consultarlo."
      : P.estado === "Por aprobar" ? "Este pedido viene de la tienda web y está esperando revisión. Apruébalo o recházalo en «Pedidos web por aprobar»."
      : P.rutaId ? `Este pedido ya está asignado a la ruta ${P.rutaId} en Logística. Quítalo de la ruta para poder editarlo.`
      : `Este pedido está en «${P.estado}» y ya no se puede modificar. Solo puedes consultarlo.`;
    b.innerHTML = `<div class="banner"><span>🔒</span><span>${esc(motivo)}</span></div>`;
  }

  /* ---------- 1. Cliente ---------- */
  function renderCliente() {
    const body = document.getElementById("cli-body");
    const sub = document.getElementById("cli-sub");
    const puedeCambiar = editable() && (modo === "nuevo" || P.estado === "Borrador");
    if (!P.clienteId) {
      sub.textContent = "Búscalo antes de levantar el pedido";
      body.innerHTML =
        '<div class="cli-row"><div class="cli-find"><div class="search">' + SVG.search +
        '<input class="inp ' + (mostrarErrores ? "err" : "") + '" id="cli-q" type="search" placeholder="Buscar cliente por nombre, NIT o teléfono…" autocomplete="off"></div>' +
        '<div class="dd" id="cli-dd" hidden></div></div>' +
        (can("cc.clientes.crear") ? '<button class="btn" type="button" data-act="cli-nuevo"><svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Cliente nuevo</button>' : "") + "</div>";
      const q = document.getElementById("cli-q");
      q.addEventListener("input", () => buscarCliente(q.value));
      q.addEventListener("focus", () => buscarCliente(q.value));
      q.addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); const f = document.querySelector("#cli-dd .dd-item"); if (f) f.click(); } });
      return;
    }
    const c = P.cliente;
    const previos = getPedidos().filter((p) => p.clienteId === P.clienteId && p.id !== P.id).length;
    sub.textContent = "";
    body.innerHTML =
      `<div class="cli-card"><div class="avatar">${esc(iniciales(c.nombre))}</div><div class="info">` +
      `<h3>${esc(c.nombre)} <span class="badge plain" style="--c:${c.tipo === "Empresa" ? "#8b7cf6" : "#22b8cf"}">${esc(c.tipo || "Individual")}</span></h3>` +
      `<div class="cli-meta"><span>NIT <b>${esc(c.nit)}</b></span><span>Tel. <b>${esc(c.telefono)}</b></span><span>${esc(c.email || "Sin correo")}</span><span><b>${previos}</b> pedido${previos === 1 ? "" : "s"} anterior${previos === 1 ? "" : "es"}</span></div></div>` +
      (puedeCambiar ? '<button class="btn sm" type="button" data-act="cli-cambiar">Cambiar cliente</button>' : "") + "</div>";
  }

  function buscarCliente(texto) {
    const dd = document.getElementById("cli-dd");
    const t = norm(texto).trim();
    const lista = getClientes().filter((c) => !t || norm([c.nombre, c.nit, c.telefono, c.email].join(" ")).includes(t)).slice(0, 7);
    dd.hidden = false;
    dd.innerHTML = lista.length
      ? lista.map((c) => `<button type="button" class="dd-item" data-act="cli-elegir" data-id="${esc(c.id)}"><span class="avatar">${esc(iniciales(c.nombre))}</span><span><b>${esc(c.nombre)}</b><span>NIT ${esc(c.nit)} · ${esc(c.telefono)} · ${esc(c.entrega.municipio || "")}</span></span></button>`).join("")
      : `<div class="dd-empty">No hay clientes con «${esc(texto)}». ${can("cc.clientes.crear") ? "Puedes crearlo con «Cliente nuevo»." : ""}</div>`;
  }

  function elegirCliente(id) {
    const c = getCliente(id);
    if (!c) return;
    P.clienteId = c.id;
    P.cliente = { nombre: c.nombre, nit: c.nit, telefono: c.telefono, email: c.email, tipo: c.tipo };
    P.entrega = { ...blankEntrega(), ...clon(c.entrega) };
    P.facturacion = { ...blankFact(), ...clon(c.facturacion) };
    editEnt = !entregaCompleta(P.entrega); editFac = !factCompleta(P.facturacion);
    marcar(); renderCliente(); renderDir(); renderResumen();
  }

  function modalCliente() {
    const ov = document.createElement("div");
    ov.className = "overlay";
    ov.innerHTML =
      '<div class="modal" role="dialog" aria-modal="true"><div class="modal-h"><h3>Cliente nuevo</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>' +
      '<div class="modal-b"><div class="grid-form">' +
      '<div class="field c12"><label>Nombre completo o razón social *</label><input class="inp" id="n-nombre" autocomplete="off"></div>' +
      '<div class="field c6"><label>NIT * <span class="faint">(o CF)</span></label><input class="inp" id="n-nit" autocomplete="off"></div>' +
      '<div class="field c6"><label>Teléfono * <span class="faint">(8 dígitos)</span></label><input class="inp" id="n-tel" inputmode="numeric" autocomplete="off"></div>' +
      '<div class="field c6"><label>Correo</label><input class="inp" id="n-mail" type="email" autocomplete="off"></div>' +
      '<div class="field c6"><label>Tipo</label><select class="inp" id="n-tipo"><option>Individual</option><option>Empresa</option></select></div>' +
      '</div><div class="err-msg" id="n-err" style="margin-top:12px"></div></div>' +
      '<div class="modal-f"><button class="btn" type="button" data-x>Cancelar</button><button class="btn primary" type="button" id="n-ok">Guardar y seleccionar</button></div></div>';
    document.body.appendChild(ov);
    const cerrar = () => ov.remove();
    ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) cerrar(); });
    const tel = ov.querySelector("#n-tel");
    tel.addEventListener("input", () => { tel.value = fmtTel(tel.value); });
    ov.querySelector("#n-nombre").focus();
    ov.querySelector("#n-ok").addEventListener("click", () => {
      const nombre = ov.querySelector("#n-nombre").value.trim();
      const nit = ov.querySelector("#n-nit").value.trim().toUpperCase();
      const tl = tel.value, mail = ov.querySelector("#n-mail").value.trim();
      const err = ov.querySelector("#n-err");
      if (!nombre) { err.textContent = "Escribe el nombre del cliente."; return; }
      if (!nit) { err.textContent = "Escribe el NIT (o CF si es consumidor final)."; return; }
      if (!telOk(tl)) { err.textContent = "El teléfono debe tener 8 dígitos."; return; }
      if (getClientes().some((c) => c.nit === nit && nit !== "CF")) { err.textContent = "Ya existe un cliente con ese NIT. Búscalo en la lista."; return; }
      const c = saveCliente({
        id: null, nombre, tipo: ov.querySelector("#n-tipo").value, nit, telefono: tl, email: mail,
        entrega: { ...blankEntrega(), contacto: nombre, telefono: tl },
        facturacion: { nombre, nit, direccion: "", email: mail },
      });
      cerrar(); elegirCliente(c.id); toast("Cliente creado y seleccionado", "ok");
    });
  }

  /* ---------- 2. Direcciones ---------- */
  function fld(label, path, o) {
    o = o || {};
    const v = getP(path) ?? "";
    const malo = mostrarErrores && o.req && (o.tel ? !telOk(v) : !String(v).trim());
    const cls = "inp" + (malo ? " err" : "");
    let ctl;
    if (o.opts) {
      ctl = `<select class="${cls}" data-k="${path}" ${o.dis ? "disabled" : ""}><option value="">${esc(o.ph || "Selecciona…")}</option>` +
        o.opts.map((x) => `<option ${x === v ? "selected" : ""}>${esc(x)}</option>`).join("") + "</select>";
    } else if (o.area) {
      ctl = `<textarea class="${cls}" data-k="${path}" rows="2" placeholder="${esc(o.ph || "")}">${esc(v)}</textarea>`;
    } else {
      ctl = `<input class="${cls}" data-k="${path}" ${o.tel ? 'data-fmt="tel" inputmode="numeric"' : ""} ${o.dis ? "disabled" : ""} type="${o.type || "text"}" value="${esc(v)}" placeholder="${esc(o.ph || "")}" autocomplete="off" ${o.min ? `min="${o.min}"` : ""}>`;
    }
    return `<div class="field ${o.c || "c6"}"><label>${label}${o.req ? " *" : ""}</label>${ctl}${malo ? '<span class="err-msg">Revisa este dato</span>' : ""}</div>`;
  }

  function renderDir() {
    const e = P.entrega, f = P.facturacion;
    const puede = editable() && can("cc.direcciones.editar");
    const modE = diferenteDeCliente("entrega"), modF = diferenteDeCliente("facturacion");
    const sinCliente = !P.clienteId;

    /* Entrega */
    let htmlE;
    if (sinCliente) htmlE = '<div class="addr-warn">Selecciona primero al cliente para cargar su dirección.</div>';
    else if (editEnt && puede) {
      htmlE = '<div class="grid-form">' +
        fld("Quién recibe", "entrega.contacto", { c: "c3", req: true }) + fld("Teléfono de contacto", "entrega.telefono", { c: "c3", req: true, tel: true, ph: "5555-1234" }) +
        fld("Departamento", "entrega.departamento", { c: "c3", req: true, opts: Object.keys(UBICACIONES) }) +
        fld("Municipio", "entrega.municipio", { c: "c3", req: true, opts: UBICACIONES[e.departamento] || [], dis: !e.departamento, ph: e.departamento ? "Selecciona…" : "Elige el departamento" }) +
        fld("Zona", "entrega.zona", { c: "c2", ph: "Opcional" }) + fld("Dirección", "entrega.direccion", { c: "c4", req: true, ph: "Calle, avenida, número, colonia…" }) +
        fld("Referencias / indicaciones", "entrega.referencia", { c: "c6", area: true, ph: "Color del portón, punto de referencia, horario…" }) + "</div>" +
        '<div class="links"><button class="lnk" type="button" data-act="ok-ent">Listo</button>' +
        (modE ? '<button class="lnk" type="button" data-act="reset-ent">Restablecer la del cliente</button>' : "") + "</div>";
    } else {
      htmlE = entregaCompleta(e)
        ? `<div class="addr-view"><div class="l1">${esc(e.contacto)} · ${esc(e.telefono)}</div><div>${esc(e.direccion)}</div><div>${e.zona ? "Zona " + esc(e.zona) + ", " : ""}${esc(e.municipio)}, ${esc(e.departamento)}</div>${e.referencia ? `<div class="ref">Ref.: ${esc(e.referencia)}</div>` : ""}</div>`
        : '<div class="addr-warn">Falta completar la dirección de entrega.</div>';
    }

    /* Facturación */
    let htmlF;
    const cf = String(f.nit).toUpperCase() === "CF";
    if (sinCliente) htmlF = '<div class="addr-warn">Selecciona primero al cliente para cargar sus datos de facturación.</div>';
    else if (editFac && puede) {
      htmlF = '<div class="grid-form">' +
        fld("Nombre a facturar", "facturacion.nombre", { c: "c6", req: true }) +
        fld("NIT", "facturacion.nit", { c: "c3", req: true, dis: cf }) +
        `<div class="field c3" style="justify-content:flex-end"><label class="check" style="height:42px"><input type="checkbox" data-act="cf" ${cf ? "checked" : ""}> Consumidor final (CF)</label></div>` +
        fld("Dirección fiscal", "facturacion.direccion", { c: "c6", req: true }) +
        fld("Correo para la factura", "facturacion.email", { c: "c6", type: "email" }) + "</div>" +
        '<div class="links"><button class="lnk" type="button" data-act="ok-fac">Listo</button>' +
        '<button class="lnk" type="button" data-act="fac-igual">Usar la dirección de entrega</button>' +
        (modF ? '<button class="lnk" type="button" data-act="reset-fac">Restablecer los del cliente</button>' : "") + "</div>";
    } else {
      htmlF = factCompleta(f)
        ? `<div class="addr-view"><div class="l1">${esc(f.nombre)}</div><div>NIT ${esc(f.nit)}</div><div>${esc(f.direccion)}</div>${f.email ? `<div class="ref">${esc(f.email)}</div>` : ""}</div>`
        : '<div class="addr-warn">Faltan datos de facturación.</div>';
    }

    const acciones = (tipo, mod, abierto) => puede && !sinCliente
      ? `<span class="sp">${mod ? '<span class="badge plain" style="--c:#f97316">Modificada para este pedido</span>' : ""}${abierto ? "" : `<button class="btn sm" type="button" data-act="edit-${tipo}">Editar</button>`}</span>` : "";

    document.getElementById("dir-body").innerHTML =
      `<div class="addr"><div class="addr-h"><h3>${SVG.pin}Dirección de entrega</h3>${acciones("ent", modE, editEnt)}</div>${htmlE}</div>` +
      `<div class="addr"><div class="addr-h"><h3>${SVG.doc}Facturación</h3>${acciones("fac", modF, editFac)}</div>${htmlF}</div>`;
  }

  /* ---------- 3. Productos ---------- */
  function renderChips() {
    const cats = ["Todas", ...Object.keys(CATEGORIAS)];
    document.getElementById("chips").innerHTML = cats.map((c) => `<button type="button" class="chip2 ${c === catFiltro ? "on" : ""}" data-cat="${esc(c)}">${esc(c)}</button>`).join("");
  }
  function productosVisibles() {
    const t = norm(document.getElementById("prod-q").value).trim();
    return PRODUCTOS.filter((p) => (catFiltro === "Todas" || p.categoria === catFiltro) && (!t || norm(p.nombre + " " + p.sku + " " + p.categoria).includes(t)));
  }
  function renderCatalogo() {
    const box = document.getElementById("cat-list");
    const sc = box.scrollTop;
    const lista = productosVisibles();
    box.innerHTML = lista.length ? lista.map((p) => {
      const disp = disponible(p.sku);
      const it = P.items.find((x) => x.sku === p.sku);
      const cat = CATEGORIAS[p.categoria] || { color: "#22b8cf", icono: "" };
      const stock = disp <= 0 ? '<span class="badge" style="--c:#ef5b66">Sin stock</span>'
        : disp <= 10 ? `<span class="badge" style="--c:#f4b63d">Quedan ${disp}</span>` : `<span class="badge" style="--c:#2fc986">Stock ${disp}</span>`;
      const act = !editable() ? "" : it
        ? `<div class="stepper"><button type="button" data-act="dec" data-sku="${p.sku}" ${it.cantidad <= 1 ? "disabled" : ""}>−</button><input type="number" min="1" max="${disp}" value="${it.cantidad}" data-cat-qty="${p.sku}"><button type="button" data-act="inc" data-sku="${p.sku}" ${it.cantidad >= disp ? "disabled" : ""}>+</button></div>`
        : `<button class="btn sm primary" type="button" data-act="add" data-sku="${p.sku}" ${disp <= 0 ? "disabled" : ""}>Agregar</button>`;
      return `<div class="prow ${disp <= 0 ? "out" : ""}"><div class="pic" style="--c:${cat.color}"><svg viewBox="0 0 24 24">${cat.icono}</svg></div>` +
        `<div><div class="pname">${esc(p.nombre)}</div><div class="psku">${p.sku} · ${esc(p.categoria)}</div></div>` +
        `<div class="pstock">${stock}</div>` +
        `<div class="pprice"><b>${fmtQ(p.precio)}</b><span>${fmtQ(conIva(p.precio))} con IVA</span></div><div class="pact">${act}</div></div>`;
    }).join("") : '<div class="empty-lines" style="border:0">No hay productos con esa búsqueda.</div>';
    box.scrollTop = sc;
  }

  function agregar(sku, n) {
    const p = prod(sku); if (!p) return;
    const disp = disponible(sku);
    let it = P.items.find((x) => x.sku === sku);
    if (disp <= 0) { toast(`${p.nombre}: sin stock`, "warn"); return; }
    if (it) {
      if (it.cantidad + (n || 1) > disp) { it.cantidad = disp; toast(`Solo hay ${disp} unidades disponibles de ${p.nombre}`, "warn"); }
      else it.cantidad += n || 1;
    } else {
      P.items.push({ sku, nombre: p.nombre, categoria: p.categoria, precio: p.precio, cantidad: Math.min(n || 1, disp), descuento: 0 });
    }
    ultimo = sku; marcar(); renderLineas(); renderCatalogo(); renderResumen();
  }
  function fijarCantidad(sku, v) {
    const it = P.items.find((x) => x.sku === sku); if (!it) return;
    const disp = disponible(sku);
    let n = Math.floor(Number(v));
    if (!n || n < 1) n = 1;
    if (n > disp) { n = disp; toast(`Solo hay ${disp} unidades disponibles de ${it.nombre}`, "warn"); }
    it.cantidad = n; marcar(); renderLineas(); renderCatalogo(); renderResumen();
  }
  function quitar(sku) {
    P.items = P.items.filter((x) => x.sku !== sku);
    marcar(); renderLineas(); renderCatalogo(); renderResumen();
  }

  function renderLineas() {
    const box = document.getElementById("lines");
    const t = calcTotales(P.items, P.envio);
    document.getElementById("lines-count").textContent = P.items.length ? `${t.lineas} producto${t.lineas === 1 ? "" : "s"} · ${t.unidades} unidad${t.unidades === 1 ? "" : "es"}` : "";
    document.getElementById("prod-sub").textContent = P.items.length ? `${t.unidades} unidades en el pedido` : "";
    if (!P.items.length) {
      box.innerHTML = `<div class="empty-lines ${mostrarErrores ? "bad" : ""}" ${mostrarErrores ? 'style="border-color:var(--bad);color:#991b1b"' : ""}>Aún no has agregado productos. Búscalos arriba y pulsa <b>Agregar</b>.</div>`;
      return;
    }
    const puedeDesc = editable() && can("cc.pedidos.descuento");
    box.innerHTML = '<div class="tbl-wrap"><table class="tbl lines"><thead><tr><th>Producto</th><th class="num">Precio s/IVA</th><th class="num">Precio c/IVA</th><th>Cantidad</th><th>Desc. %</th><th class="num">Total s/IVA</th><th class="num">Total c/IVA</th><th></th></tr></thead><tbody>' +
      P.items.map((it, i) => {
        const disp = disponible(it.sku), mal = it.cantidad > disp || it.cantidad < 1;
        const tl = totalLinea(it);
        return `<tr class="${mal ? "bad" : ""} ${it.sku === ultimo ? "flash" : ""}" data-sku="${it.sku}">` +
          `<td><div class="strong">${esc(it.nombre)}</div><div class="sub">${esc(it.sku)}</div>${mal ? `<div class="warn-line">Solo hay ${Math.max(disp, 0)} disponibles</div>` : ""}</td>` +
          `<td class="num">${fmtQ(it.precio)}</td><td class="num">${fmtQ(conIva(it.precio))}</td>` +
          `<td><div class="stepper sm"><button type="button" data-act="dec" data-sku="${it.sku}" ${it.cantidad <= 1 || !editable() ? "disabled" : ""}>−</button><input type="number" min="1" max="${disp}" value="${it.cantidad}" data-f="cantidad" data-i="${i}" ${editable() ? "" : "disabled"}><button type="button" data-act="inc" data-sku="${it.sku}" ${it.cantidad >= disp || !editable() ? "disabled" : ""}>+</button></div></td>` +
          `<td><input class="inp d" type="number" min="0" max="100" step="1" value="${it.descuento || 0}" data-f="descuento" data-i="${i}" ${puedeDesc ? "" : "disabled"}></td>` +
          `<td class="num strong" data-c="si">${fmtQ(tl.sinIva)}</td><td class="num strong" data-c="ci">${fmtQ(tl.conIva)}</td>` +
          `<td>${editable() ? `<button class="btn sm icon danger ghost" type="button" data-act="del" data-sku="${it.sku}" title="Quitar">${SVG.trash}</button>` : ""}</td></tr>`;
      }).join("") + "</tbody></table></div>";
    ultimo = null;
  }

  /* ---------- 4. Detalles ---------- */
  function renderDetalles() {
    document.getElementById("det-body").innerHTML =
      fld("Canal de atención", "canal", { c: "c4", opts: CANALES }) + fld("Forma de pago", "pago", { c: "c4", opts: PAGOS }) +
      fld("Prioridad", "prioridad", { c: "c4", opts: PRIORIDADES }) + fld("Franja de entrega", "franja", { c: "c4", opts: FRANJAS }) +
      fld("Fecha de entrega deseada", "fechaEntrega", { c: "c4", type: "date", min: modo === "nuevo" ? fechaInput() : "" }) +
      fld("Costo de envío (Q, sin IVA)", "envio", { c: "c4", type: "number", min: "0" }) +
      fld("Observaciones del cliente", "obsCliente", { c: "c6", area: true, ph: "Lo que el cliente pidió tener en cuenta" }) +
      fld("Notas internas", "notasInternas", { c: "c6", area: true, ph: "Solo visibles para el equipo" });
    // los selects de opciones simples no necesitan texto "Selecciona…" vacío
    document.querySelectorAll("#det-body select").forEach((sel) => { const o = sel.querySelector('option[value=""]'); if (o) o.remove(); });
  }

  /* ---------- Resumen y acciones ---------- */
  function renderResumen() {
    const t = calcTotales(P.items, P.envio);
    const chk = validar();
    const mostrar = mostrarErrores;
    const nuevoOBorr = modo === "nuevo" || P.estado === "Borrador";
    const puedeGuardar = modo === "nuevo" ? can("cc.pedidos.crear") : can("cc.pedidos.editar");
    const est = ESTADOS[P.estado];
    const e = P.entrega;

    const acciones = modo === "ver" || !puedeGuardar ? "" :
      '<div class="acts">' +
      (nuevoOBorr
        ? '<button class="btn primary" type="button" data-act="crear">Crear pedido</button><button class="btn" type="button" data-act="borrador">Guardar borrador</button>'
        : '<button class="btn primary" type="button" data-act="cambios">Guardar cambios</button>') +
      '<button class="btn ghost" type="button" data-act="cancelar">Cancelar</button></div>';

    document.getElementById("resumen").innerHTML =
      `<div class="panel-h"><h2>Resumen del pedido</h2><span class="sub"><span class="badge" style="--c:${est.color}">${esc(P.estado)}</span></span></div>` +
      '<div class="panel-b">' +
      `<div class="sum-id">${P.id ? esc(P.id) : "Pedido nuevo · se numera al crearlo"}</div>` +
      `<div class="sum-block"><div class="t">Cliente</div>${P.clienteId ? `<b>${esc(P.cliente.nombre)}</b><div class="muted">NIT ${esc(P.cliente.nit)}</div>` : '<span class="muted">Sin seleccionar</span>'}</div>` +
      `<div class="sum-block"><div class="t">Entrega</div>${e.municipio ? `${esc(e.municipio)}, ${esc(e.departamento)}${e.zona ? " · zona " + esc(e.zona) : ""}` : '<span class="muted">Sin dirección</span>'}<div class="muted">${esc(P.franja)}${P.fechaEntrega ? " · " + fmtFechaCorta(P.fechaEntrega + "T12:00:00") : ""}</div></div>` +
      `<div class="minis"><div class="mini"><b>${t.lineas}</b><span>Productos distintos</span></div><div class="mini"><b>${t.unidades}</b><span>Unidades</span></div></div>` +
      '<div class="totals">' +
      `<div class="r"><span>Subtotal (sin IVA)</span><b>${fmtQ(t.subtotal)}</b></div>` +
      (t.descuento ? `<div class="r neg"><span>Descuentos</span><b>− ${fmtQ(t.descuento)}</b></div>` : "") +
      (t.envio ? `<div class="r"><span>Envío (sin IVA)</span><b>${fmtQ(t.envio)}</b></div>` : "") +
      `<div class="r"><span>IVA (${Math.round(IVA * 100)} %)</span><b>${fmtQ(t.iva)}</b></div>` +
      `<div class="grand"><span>Total a pagar</span><b>${fmtQ(t.total)}</b></div></div>` +
      '<div class="checks">' + chk.map((c) => {
        const ok = c.ok && !(c.id === "stock" && !P.items.length);   // sin productos, "stock" aún no aplica
        return `<div class="c ${ok ? "ok" : !c.ok && mostrar ? "bad" : ""}"><i>${ok ? "✓" : !c.ok && mostrar ? "!" : ""}</i><span>${esc(c.label)}</span></div>`;
      }).join("") + "</div>" +
      acciones + "</div>";
  }

  /* ---------- Guardado ---------- */
  function guardar(tipo) {
    if (tipo === "borrador") {
      if (!P.clienteId) { mostrarErrores = true; renderCliente(); toast("Selecciona al cliente para guardar el borrador", "warn"); return; }
    } else {
      const chk = validar();
      const falla = chk.find((c) => !c.ok);
      if (falla) {
        if (typeof pulsoTrack === "function") {
          pulsoTrack(falla.id === "stock" ? "stock_fallo" : "validacion_error", {
            canal: P.canal || "Teléfono",
            ref_id: P.id || ("TMP-" + Date.now()),
            valido: 0,
            detalle: falla.label
          });
        }
        mostrarErrores = true; renderCliente(); renderDir(); renderLineas(); renderResumen();
        const dest = { cliente: "sec-cliente", entrega: "sec-dir", fact: "sec-dir", items: "sec-prod", stock: "sec-prod" }[falla.id];
        document.getElementById(dest).scrollIntoView({ behavior: "smooth", block: "center" });
        if (falla.id === "entrega") editEnt = true; if (falla.id === "fact") editFac = true; renderDir();
        toast("Completa los datos marcados antes de guardar", "bad");
        return;
      }
    }
    const nuevo = !P.id;
    const out = clon(P);
    if (nuevo) { out.id = nextId(); out.fecha = new Date().toISOString(); out.creadoPor = s.name; }
    out.estado = tipo === "borrador" ? "Borrador" : (out.estado === "Borrador" ? "Pendiente de despacho" : out.estado);
    out.envio = round2(Number(out.envio) || 0);
    out.totales = calcTotales(out.items, out.envio);
    out.actualizado = new Date().toISOString();
    out.modificadoPor = s.name;
    savePedido(out);
    dirty = false;
    window.location.href = `cc-pedidos?ok=${encodeURIComponent(out.id)}&m=${tipo === "borrador" ? "borrador" : nuevo || P.estado === "Borrador" ? "crear" : "cambios"}`;
  }

  /* ---------- Eventos ---------- */
  function ligarEventos() {
    // Entradas de formulario con data-k
    const fs = document.getElementById("fs");
    fs.addEventListener("input", (e) => {
      const t = e.target;
      if (t.dataset.fmt === "tel") t.value = fmtTel(t.value);
      if (t.dataset.k) {
        let v = t.value;
        if (t.dataset.k === "envio") v = Math.max(0, Number(v) || 0);
        setP(t.dataset.k, v); marcar(); t.classList.remove("err");
        if (t.dataset.k === "entrega.departamento") return;
        renderResumen(); return;
      }
      if (t.dataset.f) {                       // cantidad / descuento en la tabla
        const i = Number(t.dataset.i), it = P.items[i]; if (!it) return;
        if (t.dataset.f === "descuento") it.descuento = Math.min(100, Math.max(0, Number(t.value) || 0));
        else { const n = Math.floor(Number(t.value)); if (n >= 1) it.cantidad = n; }
        marcar();
        const tl = totalLinea(it), tr = t.closest("tr");
        tr.querySelector('[data-c="si"]').textContent = fmtQ(tl.sinIva); tr.querySelector('[data-c="ci"]').textContent = fmtQ(tl.conIva);
        renderResumen();
      }
    });
    fs.addEventListener("change", (e) => {
      const t = e.target;
      if (t.dataset.k === "entrega.departamento") { setP("entrega.municipio", ""); renderDir(); renderResumen(); }
      if (t.dataset.f === "cantidad") fijarCantidad(P.items[Number(t.dataset.i)].sku, t.value);
      if (t.dataset.f === "descuento") { renderLineas(); renderResumen(); }
      if (t.dataset.catQty) fijarCantidad(t.dataset.catQty, t.value);
      if (t.dataset.act === "cf") {
        if (t.checked) { P.facturacion.nit = "CF"; } else if (P.facturacion.nit === "CF") { P.facturacion.nit = ""; }
        marcar(); renderDir(); renderResumen();
      }
    });
    // Clics
    document.addEventListener("click", async (e) => {
      const b = e.target.closest("[data-act]");
      const chip = e.target.closest("[data-cat]");
      if (chip) { catFiltro = chip.dataset.cat; renderChips(); renderCatalogo(); return; }
      if (!e.target.closest(".cli-find")) { const dd = document.getElementById("cli-dd"); if (dd) dd.hidden = true; }
      if (!b || b.type === "checkbox") return;
      const a = b.dataset.act, sku = b.dataset.sku;
      if (a === "add") agregar(sku, 1);
      else if (a === "inc") agregar(sku, 1);
      else if (a === "dec") { const it = P.items.find((x) => x.sku === sku); if (it && it.cantidad > 1) fijarCantidad(sku, it.cantidad - 1); }
      else if (a === "del") quitar(sku);
      else if (a === "cli-elegir") elegirCliente(b.dataset.id);
      else if (a === "cli-nuevo") modalCliente();
      else if (a === "cli-cambiar") {
        if (await confirmar("Cambiar de cliente", "Se reemplazarán las direcciones del pedido por las del nuevo cliente. Los productos se conservan.", "Cambiar")) {
          P.clienteId = null; P.cliente = null; P.entrega = blankEntrega(); P.facturacion = blankFact(); editEnt = editFac = false;
          marcar(); renderCliente(); renderDir(); renderResumen();
          const q = document.getElementById("cli-q"); if (q) q.focus();
        }
      }
      else if (a === "edit-ent") { editEnt = true; renderDir(); }
      else if (a === "ok-ent") { editEnt = false; renderDir(); renderResumen(); }
      else if (a === "reset-ent") { P.entrega = { ...blankEntrega(), ...clon(getCliente(P.clienteId).entrega) }; marcar(); renderDir(); renderResumen(); }
      else if (a === "edit-fac") { editFac = true; renderDir(); }
      else if (a === "ok-fac") { editFac = false; renderDir(); renderResumen(); }
      else if (a === "reset-fac") { P.facturacion = { ...blankFact(), ...clon(getCliente(P.clienteId).facturacion) }; marcar(); renderDir(); renderResumen(); }
      else if (a === "fac-igual") {
        const e2 = P.entrega;
        P.facturacion.direccion = [e2.direccion, e2.zona ? "zona " + e2.zona : "", e2.municipio, e2.departamento].filter(Boolean).join(", ");
        marcar(); renderDir(); renderResumen();
      }
      else if (a === "crear") guardar("crear");
      else if (a === "borrador") guardar("borrador");
      else if (a === "cambios") guardar("cambios");
      else if (a === "cancelar") {
        if (!dirty || await confirmar("Descartar cambios", "Tienes cambios sin guardar. Si sales, se perderán.", "Descartar", true)) { dirty = false; window.location.href = "cc-pedidos"; }
      }
    });

    // Buscador de productos
    const q = document.getElementById("prod-q");
    q.addEventListener("input", renderCatalogo);
    q.addEventListener("keydown", (e) => {
      if (e.key !== "Enter" || !editable()) return;
      e.preventDefault();
      const txt = q.value.trim().toUpperCase();
      const exacto = PRODUCTOS.find((p) => p.sku === txt);
      const v = productosVisibles();
      if (exacto) { agregar(exacto.sku, 1); q.value = ""; renderCatalogo(); }
      else if (v.length === 1) { agregar(v[0].sku, 1); q.value = ""; renderCatalogo(); }
      else toast(v.length ? "Hay varios resultados: pulsa Agregar en el que quieras" : "No hay productos con esa búsqueda", "warn");
    });

    window.addEventListener("beforeunload", (e) => { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
  }

  /* ---------- Arranque ---------- */
  function iniciar(s) {
    const id = params.get("id");
    const crumbBase = [{ t: "Inicio", href: "inicio" }, { t: "Contact Center", href: "contact-center" }, { t: "Lista de pedidos", href: "cc-pedidos" }];
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();

    if (id) {
      const ex = getPedido(id);
      if (!ex) {
        pintarShell(s, [...crumbBase, { t: "No encontrado" }]);
        document.getElementById("titulo").textContent = "Pedido no encontrado";
        document.getElementById("layout").hidden = true; document.getElementById("noenc").hidden = false;
        return;
      }
      if (!can("cc.pedidos.ver")) { window.location.replace("contact-center"); return; }
      P = clon(ex);
      modo = can("cc.pedidos.editar") && ESTADOS_EDITABLES.includes(P.estado) && !P.rutaId ? "editar" : "ver";
      if (modo === "editar" && P.estado !== "Borrador") P.items.forEach((i) => { RESERVADO[i.sku] = (RESERVADO[i.sku] || 0) + i.cantidad; });
      pintarShell(s, [...crumbBase, { t: P.id }]);
    } else {
      if (!can("cc.pedidos.crear")) { window.location.replace("contact-center"); return; }
      P = nuevoPedido(); modo = "nuevo";
      pintarShell(s, [...crumbBase, { t: "Nuevo pedido" }]);
    }

    if (modo === "ver") document.getElementById("fs").disabled = true;
    editEnt = false; editFac = false;
    renderTitulo(); renderBanner(); renderCliente(); renderDir(); renderChips(); renderCatalogo(); renderLineas(); renderDetalles(); renderResumen();
    ligarEventos();
    if (modo === "nuevo") { const q = document.getElementById("cli-q"); if (q) q.focus(); }
  }
  if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
