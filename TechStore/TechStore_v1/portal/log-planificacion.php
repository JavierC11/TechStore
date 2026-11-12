<?php
$pageTitle = 'Planificación de despacho';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js'];
$pageStyles = <<<'CSS'

  .board { display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(0, 1fr); gap: 20px; align-items: start; }
  .pane { position: sticky; top: 78px; max-height: calc(100vh - 96px); display: flex; flex-direction: column; min-width: 0; }
  .pane-scroll { overflow-y: auto; flex: 1; min-height: 0; }
  .pane .panel-h .sub b { color: #0f172a; }

  /* ----- Pedidos por asignar ----- */
  .tools { padding: 14px 16px; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px; }
  .tools .r { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .tools .search { flex: 1 1 200px; }
  .tools select.inp { width: auto; height: 38px; }
  .tools .inp { height: 38px; }
  .pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .pill { --c: #64748b; height: 30px; padding: 0 12px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--muted); background: #f1f5f9; border: 1px solid var(--border); }
  .pill:hover { color: #0f172a; background: #e2e8f0; }
  .pill.on { color: #0f172a; background: color-mix(in srgb, var(--c) 15%, #ffffff); border-color: color-mix(in srgb, var(--c) 55%, transparent); }
  .selbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 10px 16px; background: rgba(249,115,22,.12); border-bottom: 1px solid rgba(249,115,22,.35); font-size: 14px; }
  .selbar b { color: #0f172a; } .selbar .sp { margin-left: auto; display: flex; gap: 8px; }

  .pi { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px 16px; border-bottom: 1px solid var(--border); cursor: grab; transition: background .15s, opacity .15s; }
  .pi:hover { background: #f8fafc; }
  .pi.drag { opacity: .45; cursor: grabbing; }
  .pi input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--orange); cursor: pointer; }
  .pi-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 14.5px; }
  .pi-cli { color: #334155; font-size: 14px; margin-top: 2px; }
  .pi-meta { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 4px; font-size: 12.5px; color: var(--muted); font-variant-numeric: tabular-nums; }
  .pi-meta b { color: #0f172a; font-weight: 600; }
  .pool-empty { padding: 44px 20px; text-align: center; color: var(--muted); }
  .pool-empty h3 { margin: 0 0 6px; color: #0f172a; }

  /* ----- Vehículos ----- */
  .fleet { padding: 16px; display: flex; flex-direction: column; gap: 14px; }
  .vc { position: relative; border: 1px solid var(--border); border-radius: 18px; padding: 16px; background: #ffffff; display: flex; flex-direction: column; gap: 14px; transition: border-color .2s, box-shadow .2s, background .2s, transform .2s; }
  .vc.off { opacity: .6; padding: 12px 14px; flex-direction: row; align-items: center; gap: 12px; }
  .vc-h { display: flex; align-items: center; gap: 12px; }
  .vc-t { min-width: 0; flex: 1; } .vc-t b { display: block; font-size: 15.5px; } .vc-t span { color: var(--muted); font-size: 12.5px; }
  .vc-g { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .vc-drop { text-align: center; padding: 12px; border: 1.5px dashed var(--border-2); border-radius: 12px; color: var(--faint); font-size: 13px; }
  .chips { display: flex; flex-direction: column; gap: 6px; }
  .ochip { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 8px 7px 12px; border-radius: 10px; background: #f8fafc; border: 1px solid var(--border); font-size: 13px; }
  .ochip b { font-variant-numeric: tabular-nums; } .ochip span { color: var(--muted); }
  .ochip button { width: 26px; height: 26px; border-radius: 8px; border: 0; background: transparent; color: var(--muted); cursor: pointer; font-size: 14px; }
  .ochip button:hover { background: #fee2e2; color: #b91c1c; }
  .vc-f { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .vc-f select.inp { flex: 1 1 150px; height: 34px; font-size: 13.5px; padding: 0 8px; }

  /* arrastrar y soltar */
  .fleet.dragging .vc:not(.off) { border-style: dashed; }
  .vc[data-fit="1"] { border-color: rgba(47,201,134,.7); background: rgba(47,201,134,.07); }
  .vc[data-fit="0"] { border-color: rgba(239,91,102,.7); background: rgba(239,91,102,.07); }
  .vc.over[data-fit="1"] { box-shadow: 0 0 0 3px rgba(47,201,134,.35), 0 16px 40px rgba(0,0,0,.4); transform: scale(1.015); }
  .vc.over[data-fit="0"] { box-shadow: 0 0 0 3px rgba(239,91,102,.35); }
  .vc.shake { animation: shake .4s; }
  @keyframes shake { 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
  .fit-tag { display: none; position: absolute; top: 12px; right: 14px; }
  .fleet.dragging .vc[data-fit] .fit-tag { display: inline-flex; }
  .fleet.dragging .vc[data-fit] .vc-h .badge.est { display: none; }

  .modal.wide { max-width: 640px; }
  .opt { display: flex; align-items: center; gap: 14px; width: 100%; padding: 12px 14px; margin-bottom: 10px; text-align: left; border-radius: 14px; border: 1px solid var(--border-2); background: #ffffff; color: #0f172a; cursor: pointer; }
  .opt:hover:not(:disabled) { background: #f8fafc; border-color: var(--orange); }
  .opt:disabled { opacity: .5; cursor: not-allowed; }
  .opt .t { flex: 1; min-width: 0; } .opt .t b { display: block; } .opt .t span { color: var(--muted); font-size: 13px; }

  @media (max-width: 1100px) { .board { grid-template-columns: 1fr; } .pane { position: static; max-height: none; } .pane-scroll { max-height: 560px; } }
  @media (max-width: 560px) { .vc-g { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Logística</div>
        <h1>Planificación de despacho</h1>
        <p>Arrastra cada pedido a un vehículo, o usa «Asignar». Ninguna ruta puede pasar su capacidad de peso ni de volumen.</p>
      </div>
      <div class="actions">
        <button class="btn" id="btn-sugerir" type="button" hidden>
          <svg viewBox="0 0 24 24"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>Sugerir asignación
        </button>
        <a class="btn" href="log-rutas">Ver rutas y despachos</a>
      </div>
    </div>

    <section class="kpis" id="kpis"></section>

    <div class="board">
      <!-- Pedidos por asignar -->
      <section class="panel pane" id="pane-pool">
        <div class="panel-h"><span class="n">1</span><h2>Pedidos por asignar</h2><span class="sub" id="pool-sub"></span></div>
        <div class="tools">
          <div class="r">
            <div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input class="inp" id="f-q" type="search" placeholder="Pedido, cliente o destino…" autocomplete="off"></div>
            <select class="inp" id="f-depto" title="Departamento"></select>
            <select class="inp" id="f-orden" title="Ordenar">
              <option value="plazo">Más urgentes primero</option><option value="peso">Más pesados primero</option><option value="cliente">Cliente (A-Z)</option>
            </select>
          </div>
          <div class="r pills" id="pills"></div>
        </div>
        <div class="selbar" id="selbar" hidden></div>
        <div class="pane-scroll" id="pool"></div>
      </section>

      <!-- Vehículos y rutas -->
      <section class="panel pane" id="pane-fleet">
        <div class="panel-h"><span class="n">2</span><h2>Vehículos y rutas</h2><span class="sub" id="fleet-sub"></span></div>
        <div class="pane-scroll"><div class="fleet" id="fleet"></div></div>
      </section>
    </div>
  </main>
  <script>

  const s = requireModulo("logistica");

  let filtro = { q: "", depto: "", modo: "todos" };
  let orden = "plazo";
  const sel = new Set();
  let dragId = null;
  const PIN = '<svg viewBox="0 0 24 24" style="width:13px;height:13px;vertical-align:-2px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="M12 21s7-5.6 7-11a7 7 0 0 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>';

  /* ---------- Pedidos por asignar ---------- */
  function listaPool() {
    const t = norm(filtro.q).trim();
    const l = pedidosPorAsignar().filter((p) =>
      (!t || norm([p.id, p.cliente.nombre, p.entrega.municipio, p.entrega.departamento].join(" ")).includes(t)) &&
      (!filtro.depto || p.entrega.departamento === filtro.depto) &&
      (filtro.modo === "todos" || (filtro.modo === "urgentes" && p.prioridad === "Urgente") || (filtro.modo === "vencidos" && msRestantes(p) < 0)));
    if (orden === "plazo") l.sort(ordenUrgencia);
    else if (orden === "peso") l.sort((a, b) => cargaPedido(b).kg - cargaPedido(a).kg);
    else l.sort((a, b) => norm(a.cliente.nombre).localeCompare(norm(b.cliente.nombre)));
    return l;
  }

  function renderKpis() {
    const pool = pedidosPorAsignar();
    const kg = pool.reduce((a, p) => a + cargaPedido(p).kg, 0);
    const k = [
      { v: pool.length, l: "Pedidos por asignar", c: "#2fc986" },
      { v: pool.filter((p) => msRestantes(p) < 0).length, l: `Fuera del plazo de ${PLAZO_DESPACHO_H} h`, c: "#ef5b66" },
      { v: fmtKg(kg), l: "Carga pendiente de asignar", c: "#f4b63d" },
      { v: getVehiculos().filter((v) => v.estado === "Disponible").length, l: "Vehículos disponibles", c: "#22b8cf" },
    ];
    document.getElementById("kpis").innerHTML = k.map((x) => `<div class="kpi" style="--c:${x.c}"><div class="v">${x.v}</div><div class="l">${x.l}</div><div class="bar"></div></div>`).join("");
  }

  function renderPills() {
    const todos = pedidosPorAsignar();
    const def = [["todos", "Todos", todos.length, "#64748b"], ["urgentes", "Urgentes", todos.filter((p) => p.prioridad === "Urgente").length, "#ef5b66"],
      ["vencidos", "Fuera de plazo", todos.filter((p) => msRestantes(p) < 0).length, "#f4b63d"]];
    document.getElementById("pills").innerHTML = def.map(([k, t, n, c]) => `<button type="button" class="pill ${filtro.modo === k ? "on" : ""}" data-modo="${k}" style="--c:${c}">${t} <b>${n}</b></button>`).join("");
  }

  function renderPool() {
    const l = listaPool();
    for (const id of [...sel]) if (!l.some((p) => p.id === id) && !pedidosPorAsignar().some((p) => p.id === id)) sel.delete(id);
    document.getElementById("pool-sub").innerHTML = `<b>${l.length}</b> pedidos`;
    const puede = can("log.plan.asignar");
    const box = document.getElementById("pool");
    const sc = box.scrollTop;
    box.innerHTML = l.length ? l.map((p) => {
      const c = cargaPedido(p), pl = etiquetaPlazo(p);
      const urg = p.prioridad === "Urgente" ? '<span class="badge plain" style="--c:#ef5b66">Urgente</span>' : "";
      return `<article class="pi" ${puede ? 'draggable="true"' : ""} data-id="${esc(p.id)}">` +
        `<label>${puede ? `<input type="checkbox" data-sel="${esc(p.id)}" ${sel.has(p.id) ? "checked" : ""}>` : ""}</label>` +
        `<div><div class="pi-top"><b>${esc(p.id)}</b>${urg}<span class="badge" style="--c:${pl.color}">${esc(pl.txt)}</span></div>` +
        `<div class="pi-cli">${esc(p.cliente.nombre)}</div>` +
        `<div class="pi-meta"><span>${PIN} ${esc(p.entrega.municipio)}, ${esc(p.entrega.departamento)}</span><span><b>${fmtKg(c.kg)}</b></span><span><b>${fmtM3(c.m3)}</b></span><span>${p.totales.unidades} uds.</span><span>${esc(p.estado)}</span></div></div>` +
        (puede ? `<button class="btn sm" type="button" data-act="asignar" data-id="${esc(p.id)}">Asignar</button>` : "<span></span>") + "</article>";
    }).join("") : `<div class="pool-empty"><h3>${pedidosPorAsignar().length ? "Sin resultados" : "Todo asignado 🎉"}</h3><p>${pedidosPorAsignar().length ? "Prueba con otros filtros." : "No quedan pedidos pendientes de asignar."}</p></div>`;
    box.scrollTop = sc;

    const sb = document.getElementById("selbar");
    if (sel.size && puede) {
      const peds = [...sel].map(getPedido).filter(Boolean);
      const kg = peds.reduce((a, p) => a + cargaPedido(p).kg, 0), m3 = peds.reduce((a, p) => a + cargaPedido(p).m3, 0);
      sb.hidden = false;
      sb.innerHTML = `<span><b>${sel.size}</b> seleccionados · ${fmtKg(kg)} · ${fmtM3(m3)}</span><span class="sp"><button class="btn sm primary" type="button" data-act="asignar-sel">Asignar a…</button><button class="btn sm" type="button" data-act="limpiar-sel">Limpiar</button></span>`;
    } else sb.hidden = true;
    renderPills(); renderKpis();
  }

  /* ---------- Vehículos y rutas ---------- */
  function renderFleet() {
    const orden_ = { "Disponible": 0, "En ruta": 1, "Mantenimiento": 2 };
    const vs = [...getVehiculos()].sort((a, b) => orden_[a.estado] - orden_[b.estado] || a.nombre.localeCompare(b.nombre));
    const box = document.getElementById("fleet");
    const sc = box.parentElement.scrollTop;
    const puedeAsig = can("log.plan.asignar"), puedeDesp = can("log.rutas.despachar"), puedeCanc = can("log.rutas.crear");
    box.innerHTML = vs.map((v) => {
      const ico = `<div class="vico" style="--c:${TIPOS_VEHICULO[v.tipo] || "#22b8cf"}">${ICONO_CAMION}</div>`;
      if (v.estado !== "Disponible") {
        return `<article class="vc off" data-off="1">${ico}<div class="vc-t"><b>${esc(v.nombre)}</b><span>${esc(v.placa)} · ${esc(v.tipo)} · ${fmtKg(v.capKg)}</span></div>` +
          `<span class="badge" style="--c:${ESTADOS_VEHICULO[v.estado]}">${esc(v.estado)}</span>` +
          (v.estado === "En ruta" && can("log.flota.gestionar") ? `<button class="btn sm" type="button" data-act="regreso" data-v="${v.id}">Marcar regreso</button>` : "") + "</article>";
      }
      const ruta = rutaPlanificada(v.id);
      const u = ruta ? cargaRuta(ruta) : { kg: 0, m3: 0 };
      const chips = ruta ? ruta.pedidos.map((id) => {
        const p = getPedido(id); if (!p) return "";
        return `<div class="ochip"><span><b>${esc(p.id)}</b> · ${esc(p.entrega.municipio)} · ${fmtKg(cargaPedido(p).kg)}</span>` +
          (puedeAsig && estadoCarga(ruta) === "Pendiente" ? `<button type="button" data-act="quitar" data-r="${ruta.id}" data-p="${esc(p.id)}" title="Quitar de la ruta">✕</button>` : "") + "</div>";
      }).join("") : "";
      const piloto = ruta
        ? `<select class="inp" data-piloto="${ruta.id}" ${puedeAsig ? "" : "disabled"} title="Piloto">${PILOTOS.map((n) => `<option ${n === ruta.piloto ? "selected" : ""}>${esc(n)}</option>`).join("")}</select>` : "";
      return `<article class="vc" data-v="${v.id}">` +
        `<span class="badge fit-tag" data-fittag></span>` +
        `<div class="vc-h">${ico}<div class="vc-t"><b>${esc(v.nombre)}</b><span>${esc(v.placa)} · ${esc(v.tipo)}</span></div>` +
        `<span class="badge est" style="--c:${ruta ? "#f4b63d" : "#2fc986"}">${ruta ? "Ruta " + ruta.id : "Disponible"}</span></div>` +
        `<div class="vc-g">${gaugeHTML("Peso", u.kg, v.capKg, "kg", 1)}${gaugeHTML("Volumen", u.m3, v.capM3, "m³", 2)}</div>` +
        (ruta ? `<div class="chips"><span class="badge" style="--c:${{ "Pendiente": "#94a3b8", "En picking": "#22b8cf", "Cargada": "#2fc986" }[estadoCarga(ruta)]}">Bodega: ${estadoCarga(ruta) === "Pendiente" ? "sin iniciar" : estadoCarga(ruta).toLowerCase()}</span>${chips}</div>` : '<div class="vc-drop">Suelta aquí un pedido para iniciar la ruta</div>') +
        (ruta ? `<div class="vc-f">${piloto}${puedeDesp ? (estadoCarga(ruta) === "Cargada" ? `<button class="btn sm primary" type="button" data-act="despachar" data-r="${ruta.id}">Despachar ruta</button>` : `<button class="btn sm" type="button" disabled title="Bodega debe confirmar la carga primero">Esperando carga</button>`) : ""}` +
          `<a class="btn sm ghost" href="log-rutas?ver=${ruta.id}">Detalle</a>${puedeCanc ? `<button class="btn sm danger ghost" type="button" data-act="cancelar" data-r="${ruta.id}">Cancelar</button>` : ""}</div>` : "") +
        "</article>";
    }).join("");
    box.parentElement.scrollTop = sc;
    const disp = vs.filter((v) => v.estado === "Disponible").length;
    document.getElementById("fleet-sub").textContent = `${disp} disponible${disp === 1 ? "" : "s"} de ${vs.length}`;
  }

  const renderTodo = () => { renderPool(); renderFleet(); };

  /* ---------- Asignación ---------- */
  function asignarLote(ids, vehId) {
    const l = ids.map(getPedido).filter(Boolean).sort(ordenUrgencia);
    let ok = 0; const fallos = [];
    l.forEach((p) => { const r = asignarPedido(p.id, vehId, s.name); if (r.ok) ok++; else fallos.push(r.error); });
    return { ok, fallos };
  }
  function resultado(r, veh) {
    if (r.ok && !r.fallos.length) toast(`${r.ok === 1 ? "1 pedido asignado" : r.ok + " pedidos asignados"} a ${veh.nombre}`, "ok");
    else if (r.ok) toast(`${r.ok} asignados a ${veh.nombre}. ${r.fallos.length} no cupieron: ${r.fallos[0]}`, "warn");
    else toast(r.fallos[0] || "No se pudo asignar", "bad");
  }

  function modalAsignar(ids) {
    const ped = ids.map(getPedido).filter(Boolean).sort(ordenUrgencia);
    if (!ped.length) return;
    const ov = document.createElement("div");
    ov.className = "overlay";
    const total = ped.reduce((a, p) => ({ kg: a.kg + cargaPedido(p).kg, m3: a.m3 + cargaPedido(p).m3 }), { kg: 0, m3: 0 });
    const filas = getVehiculos().map((v) => {
      const ico = `<div class="vico" style="--c:${TIPOS_VEHICULO[v.tipo] || "#22b8cf"}">${ICONO_CAMION}</div>`;
      if (v.estado !== "Disponible") return `<button class="opt" disabled>${ico}<div class="t"><b>${esc(v.nombre)}</b><span>${esc(v.placa)}</span></div><span class="badge" style="--c:${ESTADOS_VEHICULO[v.estado]}">${esc(v.estado)}</span></button>`;
      const ruta = rutaPlanificada(v.id), extra = { kg: 0, m3: 0 };
      let caben = 0;
      ped.forEach((p) => { const c = cabe(v, ruta, p, extra); if (c.ok) { extra.kg += c.carga.kg; extra.m3 += c.carga.m3; caben++; } });
      const u = ruta ? cargaRuta(ruta) : { kg: 0, m3: 0 };
      const tag = caben === ped.length ? `<span class="badge" style="--c:#2fc986">${ped.length === 1 ? "Cabe" : "Caben todos"}</span>`
        : caben ? `<span class="badge" style="--c:#f4b63d">Caben ${caben} de ${ped.length}</span>` : '<span class="badge" style="--c:#ef5b66">No cabe</span>';
      return `<button class="opt" data-v="${v.id}" ${caben ? "" : "disabled"}>${ico}<div class="t"><b>${esc(v.nombre)}</b><span>${esc(v.placa)} · libre ${fmtKg(v.capKg - u.kg)} y ${fmtM3(v.capM3 - u.m3)}${ruta ? " · ruta " + ruta.id : ""}</span></div>${tag}</button>`;
    }).join("");
    ov.innerHTML = '<div class="modal wide" role="dialog" aria-modal="true"><div class="modal-h"><h3>' +
      (ped.length === 1 ? `Asignar ${esc(ped[0].id)}` : `Asignar ${ped.length} pedidos`) + '</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>' +
      `<div class="modal-b"><p class="muted" style="margin-top:0">Carga a asignar: <b style="color:#0f172a">${fmtKg(total.kg)}</b> · <b style="color:#0f172a">${fmtM3(total.m3)}</b>. Elige el vehículo:</p>${filas}</div></div>`;
    document.body.appendChild(ov);
    ov.addEventListener("click", (e) => {
      if (e.target === ov || e.target.closest("[data-x]")) { ov.remove(); return; }
      const b = e.target.closest(".opt[data-v]");
      if (!b) return;
      const v = getVehiculo(b.dataset.v);
      resultado(asignarLote(ids, v.id), v);
      ids.forEach((i) => sel.delete(i));
      ov.remove(); renderTodo();
    });
  }

  /* ---------- Eventos ---------- */
  function ligar() {
    let deb;
    document.getElementById("f-q").addEventListener("input", (e) => { clearTimeout(deb); deb = setTimeout(() => { filtro.q = e.target.value; renderPool(); }, 150); });
    document.getElementById("f-depto").addEventListener("change", (e) => { filtro.depto = e.target.value; renderPool(); });
    document.getElementById("f-orden").addEventListener("change", (e) => { orden = e.target.value; renderPool(); });
    document.getElementById("pills").addEventListener("click", (e) => { const b = e.target.closest("[data-modo]"); if (b) { filtro.modo = b.dataset.modo; renderPool(); } });

    document.getElementById("pool").addEventListener("change", (e) => {
      const c = e.target.closest("[data-sel]"); if (!c) return;
      if (c.checked) sel.add(c.dataset.sel); else sel.delete(c.dataset.sel);
      renderPool();
    });
    document.addEventListener("click", async (e) => {
      const b = e.target.closest("[data-act]"); if (!b) return;
      const a = b.dataset.act;
      if (a === "asignar") modalAsignar([b.dataset.id]);
      else if (a === "asignar-sel") modalAsignar([...sel]);
      else if (a === "limpiar-sel") { sel.clear(); renderPool(); }
      else if (a === "quitar") { const r = quitarPedido(b.dataset.r, b.dataset.p); if (r.ok) toast(`${b.dataset.p} quitado de la ruta`, "ok"); else toast(r.error, "bad"); renderTodo(); }
      else if (a === "despachar") {
        const ruta = getRuta(b.dataset.r), v = getVehiculo(ruta.vehiculoId), c = cargaRuta(ruta);
        if (await confirmar("Despachar ruta", `Se despachará ${ruta.id} en ${v.nombre} con piloto ${ruta.piloto}: ${ruta.pedidos.length} pedido${ruta.pedidos.length === 1 ? "" : "s"}, ${fmtKg(c.kg)} y ${fmtM3(c.m3)}. Los pedidos pasarán a «En ruta».`, "Despachar")) {
          const r = despacharRuta(ruta.id, s.name);
          toast(r.ok ? `Ruta ${ruta.id} despachada. Pedidos enviados.` : r.error, r.ok ? "ok" : "bad"); renderTodo();
        }
      }
      else if (a === "cancelar") {
        if (await confirmar("Cancelar ruta", "Los pedidos volverán a la lista de pendientes por asignar.", "Cancelar ruta", true)) {
          const r = cancelarRuta(b.dataset.r); toast(r.ok ? "Ruta cancelada" : r.error, r.ok ? "ok" : "bad"); renderTodo();
        }
      }
      else if (a === "regreso") { if (marcarRegreso(b.dataset.v)) toast("Vehículo disponible de nuevo", "ok"); renderTodo(); }
    });
    document.getElementById("fleet").addEventListener("change", (e) => {
      const p = e.target.closest("[data-piloto]"); if (p) { cambiarPiloto(p.dataset.piloto, p.value); toast("Piloto actualizado", "ok"); }
    });
    document.getElementById("btn-sugerir").addEventListener("click", async () => {
      const { plan, sin } = sugerirAsignacion();
      const total = plan.reduce((a, x) => a + x.nuevos.length, 0);
      if (!total) { toast("No hay pedidos que quepan en los vehículos disponibles", "warn"); return; }
      const txt = `Se asignarán ${total} de ${pedidosPorAsignar().length} pedidos: ` + plan.map((x) => `${x.nuevos.length} a ${x.v.nombre}`).join(", ") +
        (sin.length ? `. ${sin.length} no caben en ningún vehículo disponible.` : ".") + " Podrás ajustar cualquier asignación después.";
      if (await confirmar("Sugerir asignación", txt, "Aplicar sugerencia")) {
        plan.forEach((x) => x.nuevos.forEach((id) => asignarPedido(id, x.v.id, s.name)));
        toast(`${total} pedidos asignados`, "ok"); renderTodo();
      }
    });

    /* Arrastrar y soltar */
    const fleet = document.getElementById("fleet");
    function marcarEncaje(on) {
      fleet.classList.toggle("dragging", on);
      fleet.querySelectorAll(".vc[data-v]").forEach((c) => {
        if (!on) { c.removeAttribute("data-fit"); c.classList.remove("over"); return; }
        const v = getVehiculo(c.dataset.v), r = cabe(v, rutaPlanificada(v.id), getPedido(dragId));
        c.dataset.fit = r.ok ? "1" : "0";
        c.querySelector("[data-fittag]").textContent = r.ok ? "Cabe" : "No cabe";
        c.querySelector("[data-fittag]").style.setProperty("--c", r.ok ? "#2fc986" : "#ef5b66");
      });
    }
    document.getElementById("pool").addEventListener("dragstart", (e) => {
      const a = e.target.closest(".pi"); if (!a) return;
      dragId = a.dataset.id; e.dataTransfer.setData("text/plain", dragId); e.dataTransfer.effectAllowed = "move";
      a.classList.add("drag"); marcarEncaje(true);
    });
    document.getElementById("pool").addEventListener("dragend", () => {
      document.querySelectorAll(".pi.drag").forEach((x) => x.classList.remove("drag")); marcarEncaje(false); dragId = null;
    });
    fleet.addEventListener("dragover", (e) => { const c = e.target.closest(".vc[data-v]"); if (c && dragId) { e.preventDefault(); c.classList.add("over"); } });
    fleet.addEventListener("dragleave", (e) => { const c = e.target.closest(".vc[data-v]"); if (c && !c.contains(e.relatedTarget)) c.classList.remove("over"); });
    fleet.addEventListener("drop", (e) => {
      const c = e.target.closest(".vc[data-v]"); if (!c) return;
      e.preventDefault();
      const id = dragId || e.dataTransfer.getData("text/plain");
      const v = getVehiculo(c.dataset.v);
      const r = asignarPedido(id, v.id, s.name);
      dragId = null;
      if (r.ok) { toast(`${id} asignado a ${v.nombre}`, "ok"); sel.delete(id); renderTodo(); }
      else { toast(r.error, "bad"); marcarEncaje(false); c.classList.add("shake"); setTimeout(() => c.classList.remove("shake"), 450); }
    });
  }

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Logística", href: "logistica" }, { t: "Planificación de despacho" }]);
    document.title = `${APP.empresa} | ${APP.producto} · Planificación de despacho`;
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    document.getElementById("btn-sugerir").hidden = !can("log.plan.asignar");
    document.getElementById("f-depto").innerHTML = '<option value="">Todos los departamentos</option>' + Object.keys(UBICACIONES).map((d) => `<option>${esc(d)}</option>`).join("");
    ligar();
    renderTodo();
  }

  if (s && !can("log.plan.ver")) window.location.replace("logistica");
  else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
