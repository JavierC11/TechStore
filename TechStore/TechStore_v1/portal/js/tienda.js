/* DigyFenix | Pulso — Tienda pública del cliente (cuentas, carrito y pedidos web) */
const LS_CTA = "df_cuentas_v1", LS_CAR = "df_carrito_v1", SS_CLI = "df_cliente_sesion";
const _TCFG = (window.__PULSO_DB__ && window.__PULSO_DB__.config) ? window.__PULSO_DB__.config : {};
const ENVIO_BASE = Number(_TCFG.envio_base) >= 0 ? Number(_TCFG.envio_base) : 35;
const ENVIO_GRATIS_DESDE = Number(_TCFG.envio_gratis_desde) > 0 ? Number(_TCFG.envio_gratis_desde) : 500;

function getCuentas() {
  let c = leerLS(LS_CTA);
  const hashDemo = "c383f8002fb5d3448518471a4527097d458b7e4661e89259bcdc44ed07d1094b"; // Cliente@2026
  if (!Array.isArray(c) || !c.length) {
    c = [{ email: "cliente@demo.com", clienteId: "CLI-0001", nombre: "Ana Lucía Morales Pérez",
      hash: hashDemo, creadoEn: "2026-02-14T15:20:00Z" }];
    guardarLS(LS_CTA, c);
  } else {
    const demo = c.find((x) => x.email === "cliente@demo.com");
    if (demo && demo.hash !== hashDemo) {
      demo.hash = hashDemo;
      guardarLS(LS_CTA, c);
    }
  }
  return c;
}
function getCliSesion() { try { return JSON.parse(sessionStorage.getItem(SS_CLI) || "null"); } catch { return null; } }
function setCliSesion(s) { sessionStorage.setItem(SS_CLI, JSON.stringify(s)); }
function salirCliente() { sessionStorage.removeItem(SS_CLI); location.href = "tienda"; }
function requireCliente() {
  const s = getCliSesion();
  if (!s) {
    const page = location.pathname.split("/").pop().replace(/\.php$|\.html$/i, "") || "tienda";
    location.replace("t-cuenta?next=" + encodeURIComponent(page + location.search));
    return null;
  }
  return s;
}

async function ingresarCliente(email, clave) {
  const e = String(email || "").trim().toLowerCase(), c = getCuentas().find((x) => x.email === e);
  const claveStr = String(clave || "").trim();
  const h = await sha256(claveStr);
  if (!c || h !== c.hash) return { ok: false, error: "Correo o contraseña incorrectos." };
  const s = { email: c.email, clienteId: c.clienteId, nombre: c.nombre }; setCliSesion(s); return { ok: true, sesion: s };
}
async function registrarCliente(d) {
  const email = String(d.email || "").trim().toLowerCase(), nombre = String(d.nombre || "").trim();
  const tel = fmtTel(d.telefono), nit = String(d.nit || "CF").trim().toUpperCase() || "CF", clave = String(d.clave || "");
  if (nombre.length < 3) return { ok: false, error: "Escribe tu nombre completo." };
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return { ok: false, error: "Escribe un correo válido." };
  if (soloDigitos(tel).length !== 8) return { ok: false, error: "El teléfono debe tener 8 dígitos." };
  if (clave.length < 8 || !/[A-Z]/.test(clave) || !/[a-z]/.test(clave) || !/\d/.test(clave))
    return { ok: false, error: "La contraseña debe tener 8+ caracteres con mayúscula, minúscula y número." };
  const ctas = getCuentas(); if (ctas.some((x) => x.email === email)) return { ok: false, error: "Ese correo ya tiene una cuenta." };
  let cli = getClientes().find((x) => (x.email || "").toLowerCase() === email || (nit !== "CF" && x.nit === nit));
  if (!cli) cli = saveCliente({ nombre, tipo: d.tipo || "Individual", nit, telefono: tel, email,
    entrega: { contacto: nombre, telefono: tel, departamento: "Guatemala", municipio: "Guatemala", zona: "", direccion: "", referencia: "" },
    facturacion: { nombre, nit, direccion: "Ciudad", email } });
  ctas.push({ email, clienteId: cli.id, nombre: cli.nombre, hash: await sha256(clave), creadoEn: new Date().toISOString() });
  guardarLS(LS_CTA, ctas); const s = { email, clienteId: cli.id, nombre: cli.nombre }; setCliSesion(s); return { ok: true, sesion: s };
}

/* ---------- Carrito ---------- */
const prodDe = (sku) => PRODUCTOS.find((p) => p.sku === sku);
function getCarrito() { return (leerLS(LS_CAR) || []).filter((x) => prodDe(x.sku) && x.cant > 0); }
function setCarrito(it) { guardarLS(LS_CAR, it, true); pintarBadgeCarrito(); }
function cantCarrito() { return getCarrito().reduce((a, x) => a + x.cant, 0); }
function agregarCarrito(sku, n) {
  const p = prodDe(sku); if (!p || p.stock <= 0) { toast("Producto agotado", "bad"); return false; }
  const c = getCarrito(), i = c.findIndex((x) => x.sku === sku), act = i >= 0 ? c[i].cant : 0;
  const nv = Math.min(p.stock, act + (Number(n) || 1));
  if (nv === act) { toast(`Solo hay ${p.stock} unidades disponibles`, "warn"); return false; }
  if (i >= 0) c[i].cant = nv; else c.push({ sku, cant: nv });
  setCarrito(c); toast(`${p.nombre} agregado al carrito`, "ok"); return true;
}
function cambiarCarrito(sku, cant) {
  const p = prodDe(sku), c = getCarrito().filter((x) => x.sku !== sku);
  const n = Math.max(0, Math.min(p ? p.stock : 0, Math.floor(Number(cant) || 0)));
  if (n > 0) c.push({ sku, cant: n }); setCarrito(c);
}
function itemsCarrito() {
  return getCarrito().map((x) => { const p = prodDe(x.sku); return { sku: p.sku, nombre: p.nombre, categoria: p.categoria, precio: p.precio, cantidad: x.cant, descuento: 0 }; });
}
function totalesCarrito(items) {
  const it = items || itemsCarrito(), sub = it.reduce((a, x) => a + conIva(x.precio) * x.cantidad, 0);
  const env = !it.length || sub >= ENVIO_GRATIS_DESDE ? 0 : ENVIO_BASE;
  return { ...calcTotales(it, env), subConIva: round2(sub), gratis: sub >= ENVIO_GRATIS_DESDE, faltaGratis: Math.max(0, round2(ENVIO_GRATIS_DESDE - sub)) };
}

/* ---------- Crear pedido desde la tienda (queda "Por aprobar") ---------- */
function crearPedidoWeb(datos) {
  const s = getCliSesion(), cli = s && getCliente(s.clienteId), items = itemsCarrito();
  if (!cli || !items.length) return { ok: false, error: "Tu carrito está vacío." };
  for (const it of items) { const p = prodDe(it.sku); if (!p || it.cantidad > p.stock) return { ok: false, error: `Stock insuficiente de ${it.nombre}.` }; }
  const t = totalesCarrito(items), ahora = new Date().toISOString();
  cli.entrega = { ...datos.entrega }; cli.facturacion = { ...datos.facturacion }; saveCliente(cli);
  const ped = savePedido({
    id: nextId(), estado: "Por aprobar", fecha: ahora, actualizado: ahora,
    clienteId: cli.id, cliente: { nombre: cli.nombre, nit: cli.nit, telefono: cli.telefono, email: cli.email, tipo: cli.tipo },
    entrega: datos.entrega, facturacion: datos.facturacion, items, envio: t.envio, totales: t,
    canal: "Tienda web", pago: datos.pago, prioridad: "Normal", franja: datos.franja, fechaEntrega: datos.fechaEntrega,
    obsCliente: datos.obs || "", notasInternas: "Pedido ingresado por el cliente en la tienda en línea.", creadoPor: "Tienda web (cliente)",
    checkoutRefId: datos.checkoutRefId || "",
  });
  guardarLS(LS_CAR, [], true); return { ok: true, pedido: ped };
}
function misPedidos(clienteId) { return getPedidos().filter((p) => p.clienteId === clienteId && p.estado !== "Borrador"); }

/* ---------- Estado amigable para el cliente ---------- */
const PASOS = ["En revisión", "Confirmado", "Preparando", "En camino", "Entregado"];
function estadoCliente(p) {
  const m = {
    "Por aprobar":           { paso: 1, txt: "En revisión",         color: "#a78bfa", desc: "Recibimos tu pedido. Nuestro equipo está validando los datos para confirmarlo." },
    "Pendiente de despacho": { paso: 2, txt: "Pedido confirmado",   color: "#f4b63d", desc: "Tu pedido fue aprobado y está en cola para prepararse en bodega." },
    "En preparación":        { paso: 3, txt: "Preparando tu orden", color: "#38bdf8", desc: "Estamos empacando tus productos en bodega." },
    "En ruta":               { paso: 4, txt: "En camino",           color: "#fb923c", desc: "Tu pedido salió a reparto hacia tu dirección." },
    "Entregado":             { paso: 5, txt: "Entregado",           color: "#2fc986", desc: "Pedido entregado. ¡Gracias por tu compra!" },
    "Cancelado":             { paso: 0, txt: "Cancelado",           color: "#ef5b66", desc: p.motivoRechazo ? "Motivo: " + p.motivoRechazo : "Este pedido fue cancelado." },
  };
  return m[p.estado] || m["Por aprobar"];
}

/* ---------- Encabezado e ilustraciones de la tienda ---------- */
function arteProducto(p) {
  const c = CATEGORIAS[p.categoria] || { color: "#22b8cf", icono: "" };
  const dest = p.destacado ? `<em class="art-tag">Destacado</em>` : "";
  if (p.imagen) {
    return `<div class="art has-img" style="--c:${c.color}"><img src="${esc(p.imagen)}" alt="${esc(p.nombre)}" loading="lazy">${dest}<span>${esc(p.sku)}</span></div>`;
  }
  return `<div class="art" style="--c:${c.color}"><svg viewBox="0 0 24 24">${c.icono}</svg>${dest}<span>${esc(p.sku)}</span></div>`;
}
function pintarBadgeCarrito() { const b = document.getElementById("cart-n"); if (b) b.textContent = cantCarrito(); }
function pintarTienda(activa) {
  const s = getCliSesion(), staff = typeof getSession === "function" ? getSession() : null;
  const staffHref = staff ? "inicio" : "login";
  const staffTxt  = staff ? `Panel Interno (${staff.name.split(" ")[0]})` : "Acceso Administrativo";
  document.getElementById("top").innerHTML =
    `<div class="left"><a class="brand" href="tienda"><span class="chip">💻</span><b>${esc(APP.empresa)}</b></a>` +
    `<nav class="tnav"><a class="${activa === "catalogo" ? "on" : ""}" href="tienda">Catálogo</a>` +
    (s ? `<a class="${activa === "pedidos" ? "on" : ""}" href="t-pedidos">Mis pedidos</a>` : "") + `</nav></div>` +
    `<div class="who">` +
    `<a class="btn sm" href="${staffHref}" title="Ir al panel administrativo">${esc(staffTxt)}</a>` +
    (s ? `<span class="nm">Hola, ${esc(s.nombre.split(" ")[0])}</span><button class="btn sm ghost" id="cli-out">Salir</button>`
       : `<a class="btn sm ghost" href="t-cuenta">Ingresar / Crear cuenta</a>`) +
    `<a class="btn primary cartb" href="t-carrito"><svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1.5"/><circle cx="19" cy="21" r="1.5"/><path d="M2.5 3h2.7l2.2 11.5a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6L23 6H6"/></svg><span>Carrito</span><b class="cn" id="cart-n">0</b></a></div>`;
  const o = document.getElementById("cli-out"); if (o) o.onclick = salirCliente;
  pintarBadgeCarrito();
}

function verDetalleProducto(sku) {
  const p = prodDe(sku);
  if (!p) return;
  const cat = CATEGORIAS[p.categoria] || { color: "#22b8cf", icono: "" };
  const ov = document.createElement("div");
  ov.className = "overlay";
  ov.innerHTML =
    `<div class="modal" style="max-width:580px" role="dialog" aria-modal="true">` +
      `<div class="modal-h"><h3>${esc(p.nombre)}</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
      `<div class="modal-b">` +
        `<div style="border-radius:16px;overflow:hidden;margin-bottom:16px;border:1px solid var(--border)">${arteProducto(p)}</div>` +
        `<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">` +
          `<span class="badge" style="--c:${cat.color}">${esc(p.categoria)} · ${esc(p.sku)}</span>` +
          (p.stock <= 0 ? `<span class="badge" style="--c:#ef5b66">Agotado</span>` : p.stock <= 8 ? `<span class="badge" style="--c:#f4b63d">¡Últimas ${p.stock} unidades!</span>` : `<span class="badge" style="--c:#2fc986">${p.stock} disponibles en bodega</span>`) +
        `</div>` +
        `<p style="margin:0 0 16px;color:var(--muted);line-height:1.6">${esc(p.descripcion || "Producto original garantizado con despacho directo desde nuestra bodega central.")}</p>` +
        `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:12px;border-radius:12px;background:#f8fafc;border:1px solid var(--border);font-size:13px">` +
          `<div><span style="color:var(--faint);display:block">Precio s/IVA</span><b>${fmtQ(p.precio)}</b></div>` +
          `<div><span style="color:var(--faint);display:block">Precio c/IVA</span><b style="color:var(--orange-soft)">${fmtQ(conIva(p.precio))}</b></div>` +
          `<div><span style="color:var(--faint);display:block">Peso / Volumen</span><b>${Number(p.kg || 1).toFixed(1)} kg · ${Number(p.m3 || 0.01).toFixed(3)} m³</b></div>` +
        `</div>` +
      `</div>` +
      `<div class="modal-f">` +
        `<button class="btn" type="button" data-x>Cerrar</button>` +
        `<button class="btn primary" type="button" id="m-add" ${p.stock <= 0 ? "disabled" : ""}>${p.stock <= 0 ? "Sin existencias" : "+ Agregar al carrito"}</button>` +
      `</div>` +
    `</div>`;
  document.body.appendChild(ov);
  ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });
  const btn = ov.querySelector("#m-add");
  if (btn) btn.addEventListener("click", () => { agregarCarrito(p.sku, 1); ov.remove(); });
}
