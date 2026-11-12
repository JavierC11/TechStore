/* =========================================================
   DigyFenix | Pulso — Logística y Bodega
   Flota, rutas, reglas de capacidad, picking, despacho y entregas.
   Requiere session.js y data.js (almacenamiento MySQL/LS y utilidades).

   Regla central: una ruta NO puede superar la capacidad del
   vehículo ni en peso (kg) ni en volumen (m³).
   ========================================================= */

const PLAZO_DESPACHO_H = 8;   // Compromiso operativo (K09): despachar dentro de 8 h de la creación/aprobación

/* Pedidos listos en cola de despacho para asignar a una ruta */
const ESTADOS_ASIGNABLES = ["Pendiente de despacho"];

const PILOTOS = [
  "Ramiro Ajanel",
  "Selvin Orozco",
  "Baudilio Pérez",
  "Cristian Gómez",
  "Kevin Xol",
  "Édgar Tucubal",
];

const TIPOS_VEHICULO = {
  "Panel":          "#8b7cf6",
  "Camión 3.5 t":   "#16a34a",
  "Pick-up":        "#0284c7",
  "Motocicleta":    "#d97706",
  "Moto":           "#d97706",
  "Pickup":         "#0284c7",
  "Camión liviano": "#16a34a",
  "Camión mediano": "#ea580c",
};

const ESTADOS_VEHICULO = {
  "Disponible":    "#16a34a",
  "En ruta":       "#0284c7",
  "Mantenimiento": "#dc2626",
};

const ESTADOS_RUTA = {
  "Planificada": "#d97706",
  "Despachada":  "#0284c7",
  "Entregada":   "#16a34a",
  "Cancelada":   "#dc2626",
};

/* Capacidades semilla coherentes con MySQL */
const VEHICULOS_SEED = [
  { id: "VEH-01", placa: "P-412JKL", tipo: "Panel",        marca: "Toyota Hiace 2025",  nombre: "Toyota Hiace 2025",  piloto: "Ramiro Ajanel",  pilotoHabitual: "Ramiro Ajanel",  tel: "5514-2201", capKg: 1200, capM3: 6.5, estado: "En ruta" },
  { id: "VEH-02", placa: "C-893MNP", tipo: "Camión 3.5 t", marca: "Hino Serie 300",     nombre: "Hino Serie 300",     piloto: "Selvin Orozco",  pilotoHabitual: "Selvin Orozco",  tel: "5623-8910", capKg: 3500, capM3: 18.0, estado: "Disponible" },
  { id: "VEH-03", placa: "P-204RST", tipo: "Pick-up",      marca: "Toyota Hilux 4x4",   nombre: "Toyota Hilux 4x4",   piloto: "Baudilio Pérez", pilotoHabitual: "Baudilio Pérez", tel: "4712-3309", capKg: 950,  capM3: 3.2, estado: "Disponible" },
  { id: "VEH-04", placa: "C-118XYZ", tipo: "Panel",        marca: "Hyundai H1 Cargo",   nombre: "Hyundai H1 Cargo",   piloto: "Cristian Gómez", pilotoHabitual: "Cristian Gómez", tel: "5298-4412", capKg: 1100, capM3: 5.8, estado: "Disponible" },
];

/* ---------- Utilidades ---------- */
const jclone = (o) => JSON.parse(JSON.stringify(o));
const r1 = (n) => Math.round(n * 10) / 10;
const r2l = (n) => Math.round(n * 100) / 100;
const r3 = (n) => Math.round(n * 1000) / 1000;
const fmtKg = (n) => Number(n || 0).toLocaleString("en-US", { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + " kg";
const fmtM3 = (n) => Number(n || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " m³";

function cargaPedido(p) {
  let kg = 0, m3 = 0;
  (p?.items || []).forEach((i) => {
    const prod = PRODUCTOS.find((x) => x.sku === i.sku);
    const uKg = Number(i.kg ?? prod?.kg ?? 1.0);
    const uM3 = Number(i.m3 ?? prod?.m3 ?? 0.01);
    const cant = Number(i.cantidad) || 0;
    kg += uKg * cant;
    m3 += uM3 * cant;
  });
  return { kg: r3(kg), m3: r3(m3) };
}

/* Plazo de despacho: 8 h desde creación o aprobación (K09 / K10) */
function msRestantes(p) {
  return new Date(p.fechaAprobacion || p.fecha).getTime() + PLAZO_DESPACHO_H * 3600000 - Date.now();
}
function fmtDuracion(ms) {
  const m = Math.floor(Math.abs(ms) / 60000), d = Math.floor(m / 1440), h = Math.floor((m % 1440) / 60), mm = m % 60;
  return d ? `${d} d ${h} h` : h ? `${h} h ${p2(mm)} min` : `${mm} min`;
}
function etiquetaPlazo(p) {
  const ms = msRestantes(p);
  if (ms < 0) return { txt: "Vencido hace " + fmtDuracion(ms), color: "#dc2626", nivel: "bad" };
  if (ms < 3 * 3600000) return { txt: "Vence en " + fmtDuracion(ms), color: "#d97706", nivel: "warn" };
  return { txt: "A tiempo · " + fmtDuracion(ms), color: "#16a34a", nivel: "ok" };
}

/* ---------- Almacenamiento sincronizado con MySQL (LS + api.php) ---------- */
let _vehCache = null;
let _rutCache = null;

function normalizarVehiculo(v) {
  const nom = v.nombre || v.marca || v.placa || "Vehículo";
  const pil = v.pilotoHabitual || v.piloto || "";
  return {
    ...v,
    nombre: nom,
    marca: v.marca || nom,
    pilotoHabitual: pil,
    piloto: v.piloto || pil,
    capKg: Number(v.capKg) || 1000,
    capM3: Number(v.capM3) || 5.0,
    estado: v.estado || "Disponible",
  };
}

function getVehiculos() {
  if (!_vehCache) {
    let raw = leerLS(LS_VEHICULOS);
    if (!Array.isArray(raw) || !raw.length) {
      raw = jclone(VEHICULOS_SEED);
      guardarLS(LS_VEHICULOS, raw);
    }
    _vehCache = raw.map(normalizarVehiculo);
  }
  return _vehCache;
}
function guardarVehiculos() {
  const lista = getVehiculos().map(normalizarVehiculo);
  _vehCache = lista;
  guardarLS(LS_VEHICULOS, lista);
}
function getVehiculo(id) { return getVehiculos().find((v) => v.id === id) || null; }
function saveVehiculo(v) {
  const lista = getVehiculos();
  if (!v.id) {
    const maxId = lista.reduce((m, x) => Math.max(m, Number(String(x.id || "").replace(/\D/g, "")) || 0), 0);
    v.id = "VEH-" + String(maxId + 1).padStart(2, "0");
  }
  const nv = normalizarVehiculo(v);
  const i = lista.findIndex((x) => x.id === nv.id);
  if (i >= 0) lista[i] = nv; else lista.push(nv);
  guardarVehiculos();
  return nv;
}

function getRutas() {
  if (!_rutCache) {
    let raw = leerLS(LS_RUTAS);
    if (!Array.isArray(raw)) {
      _rutCache = [];
      sembrarRutas();
      guardarLS(LS_RUTAS, _rutCache);
    } else {
      _rutCache = raw;
    }
  }
  return _rutCache;
}
function guardarRutas() {
  guardarLS(LS_RUTAS, getRutas());
}
function getRuta(id) { return getRutas().find((r) => r.id === id) || null; }
function rutaPlanificada(vehId) { return getRutas().find((r) => r.vehiculoId === vehId && r.estado === "Planificada") || null; }
function nextRutaId() {
  const maxN = getRutas().reduce((m, r) => Math.max(m, Number(String(r.id || "").replace(/\D/g, "")) || 0), 0);
  return "RUT-" + String(maxN + 1).padStart(4, "0");
}

function cargaRuta(ruta) {
  let kg = 0, m3 = 0;
  (ruta?.pedidos || []).forEach((id) => {
    const p = getPedido(id);
    if (p) { const c = cargaPedido(p); kg += c.kg; m3 += c.m3; }
  });
  return { kg: r3(kg), m3: r3(m3) };
}
function pedidosPorAsignar() {
  return getPedidos().filter((p) => ESTADOS_ASIGNABLES.includes(p.estado) && !p.rutaId);
}

/* ---------- Regla de capacidad ---------- */
function cabe(veh, ruta, pedido, extra) {
  const usado = ruta ? cargaRuta(ruta) : { kg: 0, m3: 0 };
  const base = extra || { kg: 0, m3: 0 };
  const c = cargaPedido(pedido);
  const libreKg = r3(veh.capKg - usado.kg - base.kg), libreM3 = r3(veh.capM3 - usado.m3 - base.m3);
  const okKg = c.kg <= libreKg + 1e-9, okM3 = c.m3 <= libreM3 + 1e-9;
  const partes = [];
  if (!okKg) partes.push(`peso (+${r1(c.kg - libreKg)} kg)`);
  if (!okM3) partes.push(`volumen (+${r2l(c.m3 - libreM3)} m³)`);
  return { ok: okKg && okM3, okKg, okM3, carga: c, usado, libreKg, libreM3, motivo: partes.length ? "Excede " + partes.join(" y ") : "" };
}

/* ---------- Acciones operativas ---------- */
/* Estado de preparación en Bodega: Pendiente | En picking | Cargada */
function estadoCarga(r) { return (r && r.carga && r.carga.estado) || "Pendiente"; }

function asignarPedido(pedidoId, vehId, quien) {
  const p = getPedido(pedidoId), v = getVehiculo(vehId);
  if (!p) return { ok: false, error: "El pedido no existe." };
  if (!ESTADOS_ASIGNABLES.includes(p.estado)) return { ok: false, error: `${p.id} está en «${p.estado}» y no se puede asignar.` };
  if (p.rutaId) return { ok: false, error: `${p.id} ya está en la ruta ${p.rutaId}.` };
  if (!v) return { ok: false, error: "El vehículo no existe." };
  if (v.estado !== "Disponible") return { ok: false, error: `${v.nombre} no está disponible (${v.estado}).` };
  let ruta = rutaPlanificada(v.id);
  if (ruta && estadoCarga(ruta) !== "Pendiente") return { ok: false, error: `${v.nombre}: Bodega ya inició la preparación de la ruta ${ruta.id}; no se pueden agregar pedidos.` };
  const c = cabe(v, ruta, p);
  if (!c.ok) return { ok: false, error: `${p.id} no cabe en ${v.nombre}: ${c.motivo}.`, capacidad: c };
  const ahoraIso = new Date().toISOString();
  if (!ruta) {
    const rid = nextRutaId();
    const zonaStr = [p.entrega?.departamento, p.entrega?.municipio].filter(Boolean).join(" · ") || "Guatemala";
    ruta = {
      id: rid,
      codigo: rid,
      nombre: `Ruta ${rid}`,
      zona: zonaStr,
      fecha: fechaInput(),
      franja: p.franja || FRANJAS[0],
      vehiculoId: v.id,
      piloto: v.pilotoHabitual || v.piloto || PILOTOS[0],
      telPiloto: v.tel || "",
      pedidos: [],
      estado: "Planificada",
      notas: "",
      creadaPor: quien || "",
      creadaEn: ahoraIso,
      despachadaEn: null,
      carga: { estado: "Pendiente", bultos: 0 },
    };
    getRutas().push(ruta);
  }
  ruta.pedidos.push(p.id);
  if (ruta.carga) ruta.carga.bultos = ruta.pedidos.length;
  p.rutaId = ruta.id;
  p.actualizado = ahoraIso;
  guardarRutas();
  savePedido(p);
  return { ok: true, ruta };
}

function quitarPedido(rutaId, pedidoId) {
  const r = getRuta(rutaId), p = getPedido(pedidoId);
  if (!r || r.estado !== "Planificada") return { ok: false, error: "Solo se pueden quitar pedidos de una ruta planificada." };
  if (estadoCarga(r) !== "Pendiente") return { ok: false, error: "Bodega ya inició la preparación de esta ruta; no se pueden quitar pedidos." };
  r.pedidos = r.pedidos.filter((x) => x !== pedidoId);
  if (p) { delete p.rutaId; p.actualizado = new Date().toISOString(); savePedido(p); }
  if (!r.pedidos.length) _rutCache = getRutas().filter((x) => x.id !== r.id);
  guardarRutas();
  return { ok: true };
}

function moverParada(rutaId, pedidoId, delta) {
  const r = getRuta(rutaId);
  if (!r || r.estado !== "Planificada" || estadoCarga(r) === "Cargada") return false;
  const i = r.pedidos.indexOf(pedidoId), j = i + delta;
  if (i < 0 || j < 0 || j >= r.pedidos.length) return false;
  [r.pedidos[i], r.pedidos[j]] = [r.pedidos[j], r.pedidos[i]];
  guardarRutas();
  return true;
}

function cambiarPiloto(rutaId, piloto) {
  const r = getRuta(rutaId);
  if (!r || r.estado !== "Planificada") return;
  r.piloto = piloto;
  guardarRutas();
}

function despacharRuta(rutaId, quien) {
  const r = getRuta(rutaId);
  if (!r) return { ok: false, error: "La ruta no existe." };
  if (r.estado !== "Planificada") return { ok: false, error: "Solo se puede despachar una ruta planificada." };
  if (!r.pedidos.length) return { ok: false, error: "La ruta no tiene pedidos." };
  if (!r.piloto) return { ok: false, error: "Asigna un piloto antes de despachar." };
  if (estadoCarga(r) !== "Cargada") return { ok: false, error: "Bodega aún no confirma la carga de esta ruta." };
  const v = getVehiculo(r.vehiculoId);
  if (!v || v.estado !== "Disponible") return { ok: false, error: "El vehículo ya no está disponible." };
  const ahora = new Date();
  const ahoraIso = ahora.toISOString();
  r.estado = "Despachada";
  r.despachadaEn = ahoraIso;
  r.despachadaPor = quien || "";
  r.pedidos.forEach((id) => {
    const p = getPedido(id); if (!p) return;
    const hDesp = round2(Math.max(0.05, (ahora.getTime() - new Date(p.fecha).getTime()) / 3600000));
    p.estado = "En ruta";
    p.fechaEnvio = ahoraIso;
    p.horasDespacho = hDesp;
    p.enPlazo = ahora.getTime() <= new Date(p.fechaAprobacion || p.fecha).getTime() + PLAZO_DESPACHO_H * 3600000;
    p.actualizado = ahoraIso;
    savePedido(p);
  });
  v.estado = "En ruta";
  guardarVehiculos();
  guardarRutas();
  return { ok: true, ruta: r };
}

function entregarRuta(rutaId, quien, receptor) {
  const r = getRuta(rutaId);
  if (!r || r.estado !== "Despachada") return { ok: false, error: "Solo se puede confirmar entrega de una ruta despachada." };
  const ahoraIso = new Date().toISOString();
  r.estado = "Entregada";
  r.entregadaEn = ahoraIso;
  r.entregadaPor = quien || "";
  r.pedidos.forEach((id) => {
    const p = getPedido(id); if (!p) return;
    p.estado = "Entregado";
    p.entregadoEn = ahoraIso;
    p.recibidoPor = receptor || p.entrega?.contacto || p.cliente?.nombre || "Cliente";
    p.aTiempo = p.enPlazo !== false;
    p.actualizado = ahoraIso;
    savePedido(p);
  });
  const v = getVehiculo(r.vehiculoId);
  if (v && v.estado === "En ruta") {
    v.estado = "Disponible";
    guardarVehiculos();
  }
  guardarRutas();
  return { ok: true, ruta: r };
}

function cancelarRuta(rutaId) {
  const r = getRuta(rutaId);
  if (!r || r.estado !== "Planificada") return { ok: false, error: "Solo se puede cancelar una ruta planificada." };
  r.pedidos.forEach((id) => {
    const p = getPedido(id);
    if (p) { delete p.rutaId; p.actualizado = new Date().toISOString(); savePedido(p); }
  });
  if (estadoCarga(r) === "Cargada") {
    lineasPicking(r).forEach((l) => moverStock(l.sku, l.cant, "Entrada", `Ruta ${r.id} cancelada`, "Sistema", r.id));
  }
  delete r.carga;
  r.pedidos = [];
  r.estado = "Cancelada";
  r.canceladaEn = new Date().toISOString();
  guardarRutas();
  return { ok: true };
}

function marcarRegreso(vehId) {
  const v = getVehiculo(vehId);
  if (!v || v.estado !== "En ruta") return false;
  v.estado = "Disponible";
  guardarVehiculos();
  return true;
}

/* ---------- Sugerencia automática ---------- */
function ordenUrgencia(a, b) {
  return (b.prioridad === "Urgente") - (a.prioridad === "Urgente") || msRestantes(a) - msRestantes(b);
}
function sugerirAsignacion() {
  const vehs = getVehiculos().filter((v) => v.estado === "Disponible").map((v) => {
    const ruta = rutaPlanificada(v.id);
    const usado = ruta ? cargaRuta(ruta) : { kg: 0, m3: 0 };
    const deptos = new Set(ruta ? ruta.pedidos.map((id) => (getPedido(id) || { entrega: {} }).entrega.departamento) : []);
    return { v, kg: usado.kg, m3: usado.m3, deptos, nuevos: [] };
  });
  const sin = [];
  pedidosPorAsignar().sort(ordenUrgencia).forEach((p) => {
    const c = cargaPedido(p);
    const cand = vehs.filter((x) => c.kg <= x.v.capKg - x.kg + 1e-9 && c.m3 <= x.v.capM3 - x.m3 + 1e-9);
    if (!cand.length) { sin.push(p); return; }
    cand.sort((a, b) => {
      const sa = (a.deptos.has(p.entrega.departamento) ? 0 : 1), sb = (b.deptos.has(p.entrega.departamento) ? 0 : 1);
      if (sa !== sb) return sa - sb;
      const ua = (a.kg + a.m3) > 0 ? 0 : 1, ub = (b.kg + b.m3) > 0 ? 0 : 1;
      if (ua !== ub) return ua - ub;
      return a.v.capKg - b.v.capKg;
    });
    const x = cand[0];
    x.kg += c.kg; x.m3 += c.m3; x.deptos.add(p.entrega.departamento); x.nuevos.push(p.id);
  });
  return { plan: vehs.filter((x) => x.nuevos.length), sin };
}

/* ---------- Semilla de respaldo (solo si no hay rutas en MySQL ni LS) ---------- */
function sembrarRutas() {
  const veh = getVehiculos();
  const peds = getPedidos();
  if (!_rutCache) _rutCache = [];
  const despachados = peds.filter((p) => ["En ruta", "Entregado"].includes(p.estado) && !p.rutaId).sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
  const ciclo = ["VEH-03", "VEH-02", "VEH-04"];
  let n = 1, i = 0;
  const hist = [];
  while (i < despachados.length) {
    const v = veh.find((x) => x.id === ciclo[(n - 1) % ciclo.length]) || veh[0];
    const grupo = [];
    let kg = 0, m3 = 0;
    while (i < despachados.length && grupo.length < 4) {
      const c = cargaPedido(despachados[i]);
      if (kg + c.kg > v.capKg || m3 + c.m3 > v.capM3) break;
      if (grupo.length && new Date(despachados[i].fecha) - new Date(grupo[0].fecha) > 3 * 3600000) break;
      grupo.push(despachados[i]); kg += c.kg; m3 += c.m3; i++;
    }
    if (!grupo.length) { i++; continue; }
    hist.push({ v, grupo });
    n++;
  }
  hist.forEach((h, k) => {
    const ultimo = h.grupo[h.grupo.length - 1];
    const sal = new Date(new Date(ultimo.fecha).getTime() + (1.5 + (k % 3)) * 3600000);
    const id = "RUT-" + String(k + 1).padStart(4, "0");
    const todosEntregados = h.grupo.every((p) => p.estado === "Entregado");
    const ruta = {
      id, codigo: id, nombre: `Ruta ${id}`, zona: "Guatemala", fecha: fechaInput(sal), franja: FRANJAS[0],
      vehiculoId: h.v.id, piloto: h.v.pilotoHabitual || PILOTOS[0], telPiloto: h.v.tel || "",
      pedidos: h.grupo.map((p) => p.id), estado: todosEntregados ? "Entregada" : "Despachada",
      creadaPor: "Sofía Castellanos", creadaEn: new Date(sal.getTime() - 3600000).toISOString(),
      despachadaEn: sal.toISOString(), despachadaPor: "Sofía Castellanos",
    };
    _rutCache.push(ruta);
    h.grupo.forEach((p) => { p.rutaId = ruta.id; });
  });
  guardarLS(LS_PEDIDOS, peds);
}

/* ---------- Medidor de capacidad (HTML) ---------- */
function gaugeHTML(etiqueta, usado, cap, unidad, dec) {
  const pct = cap > 0 ? (usado / cap) * 100 : 0;
  const nivel = pct >= 100 - 1e-9 ? "bad" : pct >= 80 ? "warn" : "ok";
  const f = (n) => Number(n).toLocaleString("en-US", { minimumFractionDigits: dec, maximumFractionDigits: dec });
  return `<div class="gauge" data-lv="${nivel}"><div class="g-h"><span>${etiqueta}</span><b>${f(usado)} / ${f(cap)} ${unidad} · ${Math.round(pct)} %</b></div>` +
    `<div class="g-bar"><i style="width:${Math.min(100, pct)}%"></i></div></div>`;
}
const ICONO_CAMION = '<svg viewBox="0 0 24 24"><path d="M1 6h13v10H1z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>';

/* ---------- Picking y Bodega ---------- */
const PASILLOS = {
  "Laptops y Portátiles":     "A",
  "Monitores y Pantallas":    "B",
  "Componentes y Hardware":   "C",
  "Periféricos y Accesorios": "D",
  "Redes y Servidores":       "E",
};
const STOCK_MINIMO = 8;

function ubicacionSku(sku) {
  const p = PRODUCTOS.find((x) => x.sku === sku); if (!p) return "—";
  const idx = PRODUCTOS.filter((x) => x.categoria === p.categoria).indexOf(p);
  return `${PASILLOS[p.categoria] || "Z"}-${String(idx + 1).padStart(2, "0")}-${(idx % 3) + 1}`;
}

/* Líneas consolidadas de una ruta, ordenadas por ubicación */
function lineasPicking(r) {
  const m = {};
  (r?.pedidos || []).forEach((pid) => {
    const p = getPedido(pid); if (!p) return;
    (p.items || []).forEach((i) => {
      const l = m[i.sku] || (m[i.sku] = { sku: i.sku, nombre: i.nombre, ubic: ubicacionSku(i.sku), cant: 0, pedidos: [] });
      l.cant += i.cantidad;
      l.pedidos.push({ id: p.id, cant: i.cantidad });
    });
  });
  return Object.values(m).sort((a, b) => a.ubic.localeCompare(b.ubic));
}
function stockDe(sku) { const p = PRODUCTOS.find((x) => x.sku === sku); return p ? p.stock : 0; }
function pickingCompleto(r) { return !!r.carga && lineasPicking(r).every((l) => ((r.carga.picks || {})[l.sku] || 0) === l.cant); }
function cargaCompleta(r) { return !!r.carga && r.pedidos.every((id) => (r.carga.cargados || []).includes(id)); }

function iniciarPicking(rutaId, quien) {
  const r = getRuta(rutaId);
  if (!r || r.estado !== "Planificada" || !r.pedidos.length) return { ok: false, error: "La ruta no está lista para preparar." };
  if (estadoCarga(r) !== "Pendiente") return { ok: true };
  r.carga = { estado: "En picking", picks: {}, cargados: [], bultos: r.pedidos.length, inicio: new Date().toISOString(), por: quien || "" };
  r.pedidos.forEach((id) => {
    const p = getPedido(id);
    if (p && p.estado === "Pendiente de despacho") {
      p.estado = "En preparación";
      p.actualizado = new Date().toISOString();
      savePedido(p);
    }
  });
  guardarRutas();
  return { ok: true };
}
function setPick(rutaId, sku, n) {
  const r = getRuta(rutaId); if (!r || estadoCarga(r) !== "En picking") return false;
  if (!r.carga.picks) r.carga.picks = {};
  const l = lineasPicking(r).find((x) => x.sku === sku); if (!l) return false;
  r.carga.picks[sku] = Math.max(0, Math.min(l.cant, Math.floor(Number(n) || 0)));
  guardarRutas();
  return true;
}
function marcarTodoPick(rutaId) {
  const r = getRuta(rutaId); if (!r || estadoCarga(r) !== "En picking") return false;
  if (!r.carga.picks) r.carga.picks = {};
  lineasPicking(r).forEach((l) => { r.carga.picks[l.sku] = l.cant; });
  guardarRutas();
  return true;
}
function toggleCargado(rutaId, pedidoId) {
  const r = getRuta(rutaId); if (!r || estadoCarga(r) !== "En picking" || !pickingCompleto(r)) return false;
  if (!Array.isArray(r.carga.cargados)) r.carga.cargados = [];
  const c = r.carga.cargados, i = c.indexOf(pedidoId);
  if (i >= 0) c.splice(i, 1); else c.push(pedidoId);
  guardarRutas();
  return true;
}
function confirmarCarga(rutaId, quien) {
  const r = getRuta(rutaId);
  if (!r || estadoCarga(r) !== "En picking") return { ok: false, error: "La ruta no está en picking." };
  if (!pickingCompleto(r)) return { ok: false, error: "Falta completar el picking." };
  if (!cargaCompleta(r)) return { ok: false, error: "Falta marcar todos los pedidos como cargados." };
  const lineas = lineasPicking(r);
  const falta = lineas.filter((l) => stockDe(l.sku) < l.cant);
  if (falta.length) return { ok: false, error: "Stock insuficiente: " + falta.map((l) => `${l.sku} (hay ${stockDe(l.sku)}, se necesitan ${l.cant})`).join(", ") + ". Registra una entrada en Inventario." };
  lineas.forEach((l) => moverStock(l.sku, -l.cant, "Salida", `Carga de la ruta ${r.id}`, quien, r.id));

  const finIso = new Date().toISOString();
  const minEmp = r.carga.inicio ? round2(Math.max(1, (new Date(finIso) - new Date(r.carga.inicio)) / 60000)) : 20;
  r.carga.estado = "Cargada";
  r.carga.fin = finIso;
  r.carga.confirmadaPor = quien || "";

  r.pedidos.forEach((pid) => {
    const p = getPedido(pid);
    if (!p) return;
    p.minEmpaque = minEmp;
    p.fechaEmpaque = finIso;
    p.empacadoPor = quien || p.empacadoPor || "Bodega";
    p.actualizado = finIso;
    savePedido(p);
  });

  guardarRutas();
  return { ok: true };
}
