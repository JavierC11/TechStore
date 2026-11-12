<?php
$pageTitle = 'Lista de pedidos';
$pageLibs = ['js/xlsx-mini.js'];
$pageStyles = <<<'CSS'

  .pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
  .pill {
    --c: #64748b;
    display: inline-flex; align-items: center; gap: 8px; height: 34px; padding: 0 14px; border-radius: 999px; cursor: pointer;
    background: #f1f5f9; border: 1px solid var(--border); font-size: 13.5px; font-weight: 600; color: var(--muted);
    transition: background .15s, border-color .15s, color .15s;
  }
  .pill:hover { background: #e2e8f0; color: #0f172a; }
  .pill i { width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
  .pill b { color: #0f172a; font-variant-numeric: tabular-nums; }
  .pill.on { background: color-mix(in srgb, var(--c) 15%, #ffffff); border-color: color-mix(in srgb, var(--c) 55%, transparent); color: #0f172a; }

  .filters { margin-bottom: 18px; }
  .filters .row1 { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 14px; }
  .filters .row1 .search { flex: 1 1 320px; }
  .filters .adv { padding: 4px 16px 18px; border-top: 1px solid var(--border); }
  .filters .adv .grid-form { margin-top: 14px; grid-template-columns: repeat(5, minmax(0, 1fr)); }
  .filters .adv .grid-form > * { grid-column: auto; }
  .dot { display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; background: var(--orange); color: #fff; font-size: 11px; font-weight: 800; }

  .resumen { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 18px; border-bottom: 1px solid var(--border); color: var(--muted); font-size: 13.5px; }
  .resumen b { color: #0f172a; }

  .pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 18px; }
  .pager .pgs { display: flex; gap: 6px; align-items: center; }
  .pager .pg { min-width: 34px; height: 34px; padding: 0 8px; border-radius: 9px; border: 1px solid var(--border); background: #ffffff; color: #334155; cursor: pointer; font-weight: 600; font-variant-numeric: tabular-nums; }
  .pager .pg:hover:not([disabled]) { background: #f1f5f9; }
  .pager .pg.on { background: linear-gradient(180deg, var(--orange-2), #ea580c); border-color: transparent; color: #fff; }
  .pager .pg[disabled] { opacity: .4; cursor: not-allowed; }
  .pager select.inp { width: auto; height: 34px; padding: 0 10px; }

  .empty { padding: 56px 20px; text-align: center; color: var(--muted); }
  .empty h3 { margin: 0 0 6px; color: #0f172a; font-size: 18px; }
  tr.nuevo td { animation: glow 2.4s ease 1; }
  @keyframes glow { 0%, 60% { background: rgba(249,115,22,.22); } 100% { background: transparent; } }
  .reset { margin-top: 18px; text-align: center; }
  .reset button { background: none; border: 0; color: var(--faint); cursor: pointer; font-size: 12.5px; text-decoration: underline; }
  .reset button:hover { color: var(--muted); }
  @media (max-width: 1100px) { .filters .adv .grid-form { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  @media (max-width: 640px) { .filters .adv .grid-form { grid-template-columns: repeat(1, minmax(0, 1fr)); } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Contact Center</div>
        <h1>Lista de pedidos</h1>
        <p>Todos los pedidos con sus filtros. Entra a cualquiera para editarlo.</p>
      </div>
      <div class="actions">
        <button class="btn" id="btn-export" type="button" hidden>
          <svg viewBox="0 0 24 24"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>Exportar a Excel
        </button>
        <a class="btn primary" id="btn-nuevo" href="cc-pedido?nuevo=1" hidden>
          <svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Nuevo pedido
        </a>
      </div>
    </div>

    <div class="pills" id="pills"></div>

    <section class="panel filters">
      <div class="row1">
        <div class="search">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input class="inp" id="f-q" type="search" placeholder="Buscar por No. de pedido, cliente, NIT, teléfono, correo o producto…" autocomplete="off">
        </div>
        <button class="btn" id="btn-adv" type="button">
          <svg viewBox="0 0 24 24"><path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/></svg>Filtros <span class="dot" id="adv-count" hidden>0</span>
        </button>
        <button class="btn ghost" id="btn-clear" type="button">Limpiar</button>
      </div>
      <div class="adv" id="adv" hidden>
        <div class="grid-form">
          <div class="field"><label for="f-estado">Estado</label><select class="inp" id="f-estado"></select></div>
          <div class="field"><label for="f-canal">Canal</label><select class="inp" id="f-canal"></select></div>
          <div class="field"><label for="f-pago">Forma de pago</label><select class="inp" id="f-pago"></select></div>
          <div class="field"><label for="f-prioridad">Prioridad</label><select class="inp" id="f-prioridad"></select></div>
          <div class="field"><label for="f-depto">Departamento de entrega</label><select class="inp" id="f-depto"></select></div>
          <div class="field"><label for="f-desde">Fecha desde</label><input class="inp" id="f-desde" type="date"></div>
          <div class="field"><label for="f-hasta">Fecha hasta</label><input class="inp" id="f-hasta" type="date"></div>
          <div class="field"><label for="f-min">Monto mínimo (Q)</label><input class="inp" id="f-min" type="number" min="0" step="0.01" placeholder="0.00"></div>
          <div class="field"><label for="f-max">Monto máximo (Q)</label><input class="inp" id="f-max" type="number" min="0" step="0.01" placeholder="Sin límite"></div>
          <div class="field"><label for="f-agente">Creado por</label><select class="inp" id="f-agente"></select></div>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="resumen" id="resumen"></div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead id="thead"></thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
      <div id="empty" class="empty" hidden>
        <h3>No encontramos pedidos con esos filtros</h3>
        <p>Prueba con otra búsqueda o quita algún filtro.</p>
        <button class="btn" type="button" onclick="limpiar()">Limpiar filtros</button>
      </div>
      <div class="pager" id="pager"></div>
    </section>

    <div class="reset"><button type="button" id="btn-reset">Restablecer datos de demostración</button></div>
  </main>
  <script>

  const s = requireModulo("contact_center");

  const F0 = { q: "", estado: "", canal: "", pago: "", prioridad: "", depto: "", desde: "", hasta: "", min: "", max: "", agente: "" };
  let F = { ...F0 };
  let sort = { col: "fecha", dir: "desc" };
  let page = 1, size = 10;
  let resaltar = null;

  const COLS = [
    { k: "id", t: "Pedido", sort: true },
    { k: "fecha", t: "Fecha", sort: true },
    { k: "cliente", t: "Cliente", sort: true },
    { k: "tel", t: "Teléfono" },
    { k: "entrega", t: "Entrega" },
    { k: "uds", t: "Uds.", num: true, sort: true },
    { k: "total", t: "Total", num: true, sort: true },
    { k: "estado", t: "Estado", sort: true },
    { k: "canal", t: "Canal" },
    { k: "acc", t: "" },
  ];

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Contact Center", href: "contact-center" }, { t: "Lista de pedidos" }]);
    document.title = `${APP.empresa} | ${APP.producto} · Lista de pedidos`;
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();

    document.getElementById("btn-export").hidden = !can("cc.pedidos.exportar");
    document.getElementById("btn-nuevo").hidden = !can("cc.pedidos.crear");
    document.getElementById("btn-reset").hidden = !can("admin.sistema.reset");

    // Opciones de los filtros
    const opts = (id, lista, todos) => {
      document.getElementById(id).innerHTML = `<option value="">${todos}</option>` + lista.map((v) => `<option>${esc(v)}</option>`).join("");
    };
    opts("f-estado", Object.keys(ESTADOS), "Todos");
    opts("f-canal", CANALES, "Todos");
    opts("f-pago", PAGOS, "Todas");
    opts("f-prioridad", PRIORIDADES, "Todas");
    opts("f-depto", Object.keys(UBICACIONES), "Todos");
    opts("f-agente", [...new Set(getPedidos().map((p) => p.creadoPor))].sort(), "Todos");

    // Eventos
    let deb;
    document.getElementById("f-q").addEventListener("input", (e) => {
      clearTimeout(deb);
      deb = setTimeout(() => { F.q = e.target.value; page = 1; render(); }, 150);
    });
    [["estado", "f-estado"], ["canal", "f-canal"], ["pago", "f-pago"], ["prioridad", "f-prioridad"], ["depto", "f-depto"],
     ["desde", "f-desde"], ["hasta", "f-hasta"], ["min", "f-min"], ["max", "f-max"], ["agente", "f-agente"]].forEach(([k, id]) => {
      document.getElementById(id).addEventListener("input", (e) => { F[k] = e.target.value; page = 1; render(); });
    });
    document.getElementById("btn-adv").addEventListener("click", () => {
      const a = document.getElementById("adv"); a.hidden = !a.hidden;
    });
    document.getElementById("btn-clear").addEventListener("click", limpiar);
    document.getElementById("btn-export").addEventListener("click", exportar);
    document.getElementById("btn-reset").addEventListener("click", async () => {
      if (await confirmar("Restablecer datos de demostración", "Se restablecerán pedidos, clientes, rutas, stock y metas a su estado inicial tanto en el navegador como en MySQL.", "Restablecer", true)) {
        await resetDatosDemo(); window.location.href = "cc-pedidos";
      }
    });

    // Aviso al volver de guardar
    const qs = new URLSearchParams(location.search);
    if (qs.get("ok")) {
      resaltar = qs.get("ok");
      const m = { crear: "creado", borrador: "guardado como borrador", cambios: "actualizado" }[qs.get("m")] || "guardado";
      toast(`Pedido ${resaltar} ${m}`, "ok");
      history.replaceState(null, "", "cc-pedidos");
    }
    render();
  }

  function limpiar() {
    F = { ...F0 }; page = 1;
    document.getElementById("f-q").value = "";
    ["estado", "canal", "pago", "prioridad", "depto", "desde", "hasta", "min", "max", "agente"].forEach((k) => { document.getElementById("f-" + k).value = ""; });
    render();
  }

  /* ---------- Filtrado y orden ---------- */
  function coincide(p, omitirEstado) {
    if (F.q) {
      const hay = norm([p.id, p.cliente.nombre, p.cliente.nit, p.cliente.telefono, p.cliente.email, p.entrega.municipio, p.entrega.departamento,
        ...p.items.map((i) => i.nombre + " " + i.sku)].join(" "));
      if (!F.q.trim().split(/\s+/).every((t) => hay.includes(norm(t)))) return false;
    }
    if (!omitirEstado && F.estado && p.estado !== F.estado) return false;
    if (F.canal && p.canal !== F.canal) return false;
    if (F.pago && p.pago !== F.pago) return false;
    if (F.prioridad && p.prioridad !== F.prioridad) return false;
    if (F.depto && p.entrega.departamento !== F.depto) return false;
    if (F.agente && p.creadoPor !== F.agente) return false;
    const dia = fechaInput(new Date(p.fecha));
    if (F.desde && dia < F.desde) return false;
    if (F.hasta && dia > F.hasta) return false;
    if (F.min !== "" && p.totales.total < Number(F.min)) return false;
    if (F.max !== "" && p.totales.total > Number(F.max)) return false;
    return true;
  }

  const ORDEN = {
    id: (p) => p.id,
    fecha: (p) => new Date(p.fecha).getTime(),
    cliente: (p) => norm(p.cliente.nombre),
    uds: (p) => p.totales.unidades,
    total: (p) => p.totales.total,
    estado: (p) => Object.keys(ESTADOS).indexOf(p.estado),
  };

  function filtrados() {
    const f = getPedidos().filter((p) => coincide(p, false));
    const g = ORDEN[sort.col];
    f.sort((a, b) => { const x = g(a), y = g(b); return (x < y ? -1 : x > y ? 1 : 0) * (sort.dir === "asc" ? 1 : -1); });
    return f;
  }

  /* ---------- Pintado ---------- */
  function render() {
    // Píldoras de estado (cuentan con todos los filtros salvo el de estado)
    const base = getPedidos().filter((p) => coincide(p, true));
    const pills = [`<button class="pill ${F.estado === "" ? "on" : ""}" data-e="" style="--c:#64748b"><i></i>Todos <b>${base.length}</b></button>`]
      .concat(Object.entries(ESTADOS).map(([e, v]) =>
        `<button class="pill ${F.estado === e ? "on" : ""}" data-e="${esc(e)}" style="--c:${v.color}"><i></i>${esc(e)} <b>${base.filter((p) => p.estado === e).length}</b></button>`));
    const pe = document.getElementById("pills");
    pe.innerHTML = pills.join("");
    pe.querySelectorAll(".pill").forEach((b) => b.addEventListener("click", () => {
      F.estado = b.dataset.e; document.getElementById("f-estado").value = F.estado; page = 1; render();
    }));

    // Contador de filtros avanzados activos
    const n = ["estado", "canal", "pago", "prioridad", "depto", "desde", "hasta", "min", "max", "agente"].filter((k) => F[k] !== "").length;
    const dot = document.getElementById("adv-count"); dot.hidden = !n; dot.textContent = n;

    const lista = filtrados();
    const paginas = Math.max(1, Math.ceil(lista.length / size));
    if (page > paginas) page = paginas;
    const ini = (page - 1) * size;
    const vista = lista.slice(ini, ini + size);
    const valor = lista.filter((p) => p.estado !== "Cancelado").reduce((a, p) => a + p.totales.total, 0);

    document.getElementById("resumen").innerHTML = lista.length
      ? `<span>Mostrando <b>${ini + 1}–${ini + vista.length}</b> de <b>${lista.length}</b> pedidos</span><span>Valor de los pedidos (sin cancelados): <b>${fmtQ(valor)}</b></span>`
      : `<span><b>0</b> pedidos</span>`;

    // Encabezado
    document.getElementById("thead").innerHTML = "<tr>" + COLS.map((c) => {
      const on = sort.col === c.k;
      const ar = c.sort ? `<span class="ar">${on ? (sort.dir === "asc" ? "▲" : "▼") : "↕"}</span>` : "";
      return `<th class="${c.sort ? "sortable" : ""} ${on ? "on" : ""} ${c.num ? "num" : ""}" ${c.sort ? `data-k="${c.k}"` : ""}>${c.t}${ar}</th>`;
    }).join("") + "</tr>";
    document.querySelectorAll("#thead th.sortable").forEach((th) => th.addEventListener("click", () => {
      const k = th.dataset.k;
      sort = sort.col === k ? { col: k, dir: sort.dir === "asc" ? "desc" : "asc" } : { col: k, dir: k === "cliente" || k === "id" ? "asc" : "desc" };
      render();
    }));

    // Filas
    const puedeEditar = can("cc.pedidos.editar");
    document.getElementById("tbody").innerHTML = vista.map((p) => {
      const editable = puedeEditar && ESTADOS_EDITABLES.includes(p.estado) && !p.rutaId;
      const urg = p.prioridad === "Urgente" ? ' <span class="badge plain" style="--c:#ef5b66">Urgente</span>' : "";
      return `<tr class="${p.id === resaltar ? "nuevo" : ""}">
        <td><span class="strong">${esc(p.id)}</span>${urg}</td>
        <td>${fmtFechaCorta(p.fecha)}<div class="sub">${fmtFecha(p.fecha).slice(11)}</div></td>
        <td><div class="strong">${esc(p.cliente.nombre)}</div><div class="sub">NIT ${esc(p.cliente.nit)}</div></td>
        <td>${esc(p.cliente.telefono)}</td>
        <td>${esc(p.entrega.municipio)}<div class="sub">${esc(p.entrega.departamento)}</div></td>
        <td class="num">${p.totales.unidades}</td>
        <td class="num strong">${fmtQ(p.totales.total)}</td>
        <td><span class="badge" style="--c:${ESTADOS[p.estado].color}">${esc(p.estado)}</span>${p.rutaId ? `<div class="sub">Ruta ${esc(p.rutaId)}</div>` : ""}</td>
        <td>${esc(p.canal)}</td>
        <td><a class="btn sm ${editable ? "" : "ghost"}" href="cc-pedido?id=${encodeURIComponent(p.id)}">${editable ? "Editar" : "Ver"}</a></td>
      </tr>`;
    }).join("");
    document.getElementById("empty").hidden = lista.length > 0;

    // Paginación
    const pg = [];
    const quiero = new Set([1, paginas, page - 1, page, page + 1].filter((x) => x >= 1 && x <= paginas));
    let prev = 0;
    [...quiero].sort((a, b) => a - b).forEach((x) => {
      if (x - prev > 1) pg.push('<span style="color:var(--faint)">…</span>');
      pg.push(`<button class="pg ${x === page ? "on" : ""}" data-p="${x}">${x}</button>`);
      prev = x;
    });
    document.getElementById("pager").innerHTML =
      `<div class="field" style="flex-direction:row;align-items:center;gap:8px"><label style="margin:0">Filas por página</label>
        <select class="inp" id="f-size">${[10, 25, 50].map((v) => `<option ${v === size ? "selected" : ""}>${v}</option>`).join("")}</select></div>` +
      `<div class="pgs"><button class="pg" data-p="${page - 1}" ${page <= 1 ? "disabled" : ""}>‹</button>${pg.join("")}<button class="pg" data-p="${page + 1}" ${page >= paginas ? "disabled" : ""}>›</button></div>`;
    document.querySelectorAll("#pager .pg").forEach((b) => b.addEventListener("click", () => { page = Number(b.dataset.p); render(); }));
    document.getElementById("f-size").addEventListener("change", (e) => { size = Number(e.target.value); page = 1; render(); });
  }

  /* ---------- Exportar a Excel (todo lo que cumple los filtros) ---------- */
  function exportar() {
    if (!can("cc.pedidos.exportar")) return;
    const lista = filtrados();
    if (!lista.length) { toast("No hay pedidos para exportar con estos filtros", "warn"); return; }
    const columnas = [
      { titulo: "No. pedido", ancho: 14 }, { titulo: "Fecha", ancho: 18, tipo: "fecha" },
      { titulo: "Cliente", ancho: 30 }, { titulo: "NIT", ancho: 13 }, { titulo: "Teléfono", ancho: 12 }, { titulo: "Correo", ancho: 28 },
      { titulo: "Departamento", ancho: 16 }, { titulo: "Municipio", ancho: 20 }, { titulo: "Dirección de entrega", ancho: 40 },
      { titulo: "Líneas", ancho: 8, tipo: "entero" }, { titulo: "Unidades", ancho: 10, tipo: "entero" },
      { titulo: "Subtotal sin IVA", ancho: 16, tipo: "moneda" }, { titulo: "Descuento", ancho: 12, tipo: "moneda" },
      { titulo: "Envío sin IVA", ancho: 14, tipo: "moneda" }, { titulo: "IVA (12 %)", ancho: 12, tipo: "moneda" }, { titulo: "Total", ancho: 14, tipo: "moneda" },
      { titulo: "Estado", ancho: 22 }, { titulo: "Prioridad", ancho: 11 }, { titulo: "Canal", ancho: 12 }, { titulo: "Forma de pago", ancho: 24 },
      { titulo: "Entrega deseada", ancho: 15 }, { titulo: "Creado por", ancho: 22 },
    ];
    const filas = lista.map((p) => [
      p.id, p.fecha, p.cliente.nombre, p.cliente.nit, p.cliente.telefono, p.cliente.email,
      p.entrega.departamento, p.entrega.municipio, [p.entrega.direccion, p.entrega.zona ? "zona " + p.entrega.zona : ""].filter(Boolean).join(", "),
      p.totales.lineas, p.totales.unidades, p.totales.subtotal, p.totales.descuento, p.totales.envio, p.totales.iva, p.totales.total,
      p.estado, p.prioridad, p.canal, p.pago, p.fechaEntrega ? fmtFechaCorta(p.fechaEntrega + "T12:00:00") : "", p.creadoPor,
    ]);
    const d = new Date();
    const nombre = `pedidos_${d.getFullYear()}${p2(d.getMonth() + 1)}${p2(d.getDate())}_${p2(d.getHours())}${p2(d.getMinutes())}.xlsx`;
    descargarXlsx(nombre, "Pedidos", columnas, filas);
    toast(`Exportados ${lista.length} pedidos a Excel`, "ok");
  }

  /* Arranque (al final para que todas las variables ya estén definidas) */
  if (s && !can("cc.pedidos.ver")) window.location.replace("contact-center");
  else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
