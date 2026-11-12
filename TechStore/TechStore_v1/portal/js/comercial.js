/* =========================================================
   DigyFenix | Pulso — Comercial (100% Datos Reales MySQL + Telemetría)
   Indicadores K01–K10, filtros y gráficos SVG/HTML sin librerías.
   Requiere session.js, data.js y logistica.js.
   ========================================================= */

getRutas();   // Asegura hidratación de rutas antes de calcular métricas
const DAY = 86400000;
const COL_CAT = ["#0284c7", "#7c3aed", "#2563eb", "#d97706", "#059669"];
const ST = { ok: "#16a34a", warn: "#d97706", bad: "#dc2626" };
const ST_TXT = { ok: "Verde", warn: "Amarillo", bad: "Rojo" };
const ST_ICO = { ok: "●", warn: "▲", bad: "■" };

/* ---------- Metas editables (sincronizadas con MySQL kpi_metas) ---------- */
const KPI_DEF = [
  { id: "K01", nombre: "Volumen diario de solicitudes", formula: "N.º de pedidos registrados / día", unidad: "ped/día", dir: ">=", meta: 100, amarillo: 80, dec: 1, real: true },
  { id: "K02", nombre: "Calidad de datos del cliente", formula: "(Válidas / Evaluadas) × 100", unidad: "%", dir: ">=", meta: 98, amarillo: 95, dec: 2, real: true },
  { id: "K03", nombre: "Disponibilidad de stock", formula: "(Con stock / Válidas) × 100", unidad: "%", dir: ">=", meta: 97, amarillo: 93, dec: 2, real: true },
  { id: "K04", nombre: "Efectividad de creación de pedidos", formula: "(Pedidos creados / Elegibles) × 100", unidad: "%", dir: ">=", meta: 99, amarillo: 95, dec: 2, real: true },
  { id: "K05", nombre: "Tasa de abandono en checkout", formula: "(Abandonados / Iniciados) × 100", unidad: "%", dir: "<=", meta: 20, amarillo: 30, dec: 2, real: true },
  { id: "K06", nombre: "Ticket promedio de compra", formula: "Monto facturado / Pedidos creados", unidad: "Q", dir: ">=", meta: 250, amarillo: 200, dec: 2, real: true },
  { id: "K07", nombre: "Liquidaciones a tiempo", formula: "(Liquidadas en ≤ 60 min / Liquidadas) × 100", unidad: "%", dir: ">=", meta: 95, amarillo: 90, dec: 2, real: true },
  { id: "K08", nombre: "Tiempo medio de empaque", formula: "Σ minutos / Pedidos empacados", unidad: "min", dir: "<=", meta: 30, amarillo: 45, dec: 1, real: true },
  { id: "K09", nombre: "Cumplimiento de despacho en plazo", formula: "(Despachados ≤ 8 h / Despachados) × 100", unidad: "%", dir: ">=", meta: 95, amarillo: 90, dec: 2, real: true },
  { id: "K10", nombre: "Lead time Order-to-Dispatch", formula: "Σ horas totales / Despachados", unidad: "h", dir: "<=", meta: 4, amarillo: 6, dec: 2, real: true },
];
const CM_KEYS = { metas: "df_metas_v1", filtro: "df_cm_filtro_v1" };
function getMetas() {
  const g = leerLS(CM_KEYS.metas) || leerLS("df_cm_metas_v1") || {};
  return KPI_DEF.map((k) => ({ ...k, ...(g[k.id] || {}) }));
}
function guardarMetas(arr) {
  const obj = Object.fromEntries(arr.map((k) => [k.id, { meta: k.meta, amarillo: k.amarillo }]));
  guardarLS(CM_KEYS.metas, obj);
}
function semaforo(k, v) {
  if (v == null) return null;
  const ok = k.dir === ">=" ? v >= k.meta : v <= k.meta;
  if (ok) return "ok";
  return (k.dir === ">=" ? v >= k.amarillo : v <= k.amarillo) ? "warn" : "bad";
}
function fmtKpi(k, v) {
  if (v == null) return "—";
  if (k.unidad === "Q") return fmtQ(v);
  const n = Number(v).toLocaleString("en-US", { minimumFractionDigits: k.dec, maximumFractionDigits: k.dec });
  return k.unidad === "%" ? n + "%" : k.unidad === "h" ? n + " h" : k.unidad === "min" ? n + " min" : n;
}
function fmtMeta(k) { return (k.dir === ">=" ? "≥ " : "≤ ") + (k.unidad === "Q" ? "Q " + k.meta : k.meta + (k.unidad === "%" ? "%" : " " + (k.unidad === "ped/día" ? "" : k.unidad))).trim(); }

/* ---------- Filtros globales ---------- */
function getFiltro() { return { rango: "todo", canal: "", ...(leerLS(CM_KEYS.filtro) || {}) }; }
function setFiltro(f) { guardarLS(CM_KEYS.filtro, f); }
function ventana(f) {
  const hasta = Date.now();
  if (f.rango === "todo") {
    const ps = getPedidos(); const min = ps.length ? Math.min(...ps.map((p) => +new Date(p.fecha))) : hasta - 30 * DAY;
    return { desde: min, hasta, dias: Math.max(1, Math.ceil((hasta - min) / DAY)) };
  }
  const d = Number(f.rango); return { desde: hasta - d * DAY, hasta, dias: d };
}
function pedidosFiltrados(f, w) {
  w = w || ventana(f);
  return getPedidos().filter((p) => { const t = +new Date(p.fecha); return t >= w.desde && t <= w.hasta && (!f.canal || p.canal === f.canal); });
}
function pedidosPrevios(f, w) {
  if (f.rango === "todo") return null;
  const ant = { desde: w.desde - (w.hasta - w.desde), hasta: w.desde, dias: w.dias };
  return pedidosFiltrados(f, ant);
}

/* ---------- Reglas reales de negocio (K01–K10 sin datos simulados) ---------- */
const VENDIDO = ["Pendiente de despacho", "En preparación", "En ruta", "Entregado"];
const EN_PROCESO_DESPACHO = ["Pendiente de despacho", "En preparación"];
const DESPACHADO = ["En ruta", "Entregado"];
const esVenta = (p) => VENDIDO.includes(p.estado);

function datosValidos(p) {
  const c = p.cliente || {}, e = p.entrega || {};
  return /^\d{4}-?\d{4}$/.test(String(c.telefono || "").trim()) && /^\S+@\S+\.\S+$/.test(String(c.email || "")) && !!String(c.nit || "").trim() &&
    !!String(e.direccion || "").trim() && !!e.departamento && !!e.municipio;
}
function conStock(p) {
  return (p.items || []).every((i) => {
    const x = PRODUCTOS.find((y) => y.sku === i.sku);
    return x && x.stock >= i.cantidad;
  });
}
function minLiquidacion(p) {
  if (p.minLiquidacion != null) return Number(p.minLiquidacion);
  if (p.fechaAprobacion) return Math.max(0.5, (new Date(p.fechaAprobacion) - new Date(p.fecha)) / 60000);
  return null;
}
function minEmpaque(p) {
  if (p.minEmpaque != null) return Number(p.minEmpaque);
  const r = p.rutaId && getRuta(p.rutaId);
  if (r && r.carga && r.carga.inicio && r.carga.fin) {
    return Math.max(1, (new Date(r.carga.fin) - new Date(r.carga.inicio)) / 60000);
  }
  return null;
}
function horasDespacho(p) {
  if (p.horasDespacho != null) return Number(p.horasDespacho);
  if (p.fechaEnvio) return Math.max(0.05, (new Date(p.fechaEnvio) - new Date(p.fecha)) / 3600000);
  return 0;
}
function esEnPlazo(p) {
  if (typeof p.enPlazo === "boolean") return p.enPlazo;
  return horasDespacho(p) <= PLAZO_DESPACHO_H;
}
const pct = (a, b) => (b ? (a / b) * 100 : null);
const prom = (arr) => (arr.length ? arr.reduce((x, y) => x + y, 0) / arr.length : null);

/* Cada fórmula opera sobre pedidos reales y telemetría real registrada */
const KPI_FN = {
  K01: (P, dias) => (P.length ? P.length / Math.max(1, dias) : null),
  K02: (P) => pct(P.filter(datosValidos).length, P.length),
  K03: (P) => { const v = P.filter(datosValidos); return pct(v.filter(conStock).length, v.length); },
  K04: (P) => { const el = P.filter((p) => datosValidos(p) && p.estado !== "Borrador"); return pct(el.filter(esVenta).length, el.length); },
  K05: (P) => pct(P.filter((p) => p.estado === "Borrador" || p.estado === "Cancelado").length, P.length),
  K06: (P) => prom(P.filter(esVenta).map((p) => p.totales.total)),
  K07: (P) => {
    const l = P.filter((p) => VENDIDO.includes(p.estado) && minLiquidacion(p) != null);
    return pct(l.filter((p) => minLiquidacion(p) <= 60).length, l.length);
  },
  K08: (P) => prom(P.map(minEmpaque).filter((m) => m != null)),
  K09: (P) => {
    const e = P.filter((p) => DESPACHADO.includes(p.estado) && p.fechaEnvio);
    return pct(e.filter(esEnPlazo).length, e.length);
  },
  K10: (P) => prom(P.filter((p) => DESPACHADO.includes(p.estado) && p.fechaEnvio).map(horasDespacho)),
};

function calcularKpis(P, dias, prev, diasPrev, filtro) {
  const f = filtro || null;
  const sinFiltro = f ? (f.rango === "todo" && !f.canal) : (prev == null && P.length === getPedidos().length);
  const kr = (window.__PULSO_DB__ && window.__PULSO_DB__.kpisReales) ? window.__PULSO_DB__.kpisReales : null;

  return getMetas().map((k) => {
    let v = KPI_FN[k.id](P, dias);
    if (sinFiltro && kr) {
      const mk = kr[k.id.toLowerCase()];
      if (mk !== undefined && mk !== null) {
        v = Number(mk);
      }
    }
    const vp = prev ? KPI_FN[k.id](prev, diasPrev) : null;
    const ts = P.map((p) => +new Date(p.fecha));
    const t0 = ts.length ? Math.min(...ts) : 0, t1 = Date.now(), n = 8, paso = (t1 - t0) / n;
    const serie = ts.length ? Array.from({ length: n }, (_, i) => KPI_FN[k.id](P.filter((p) => {
      const t = +new Date(p.fecha);
      return t >= t0 + i * paso && t < t0 + (i + 1) * paso + (i === n - 1 ? 1 : 0);
    }), Math.max(1, paso / DAY))) : [];
    return { ...k, valor: v, previo: vp, estado: semaforo(k, v), serie };
  });
}

/* ---------- Agrupaciones ---------- */
function agrupar(P, clave, medida) {
  const m = new Map();
  P.forEach((p) => { const ks = [].concat(clave(p)); ks.forEach((k) => { const o = m.get(k.k ?? k) || { k: k.k ?? k, v: 0, n: 0 }; o.v += medida(p, k); o.n += 1; m.set(o.k, o); }); });
  return [...m.values()].sort((a, b) => b.v - a.v);
}
const dia = (iso) => { const d = new Date(iso); return `${d.getFullYear()}-${p2(d.getMonth() + 1)}-${p2(d.getDate())}`; };

/* ---------- Gráficos adaptados a tema claro ---------- */
function fmtCorto(n) { return n >= 1e6 ? (n / 1e6).toFixed(1) + " M" : n >= 1e3 ? (n / 1e3).toFixed(n >= 1e4 ? 0 : 1) + " mil" : String(Math.round(n)); }
function barrasH(items, o = {}) {
  const max = Math.max(1, ...items.map((i) => i.v)), fmt = o.fmt || ((v) => String(v)), tot = items.reduce((a, i) => a + i.v, 0);
  if (!items.length) return '<div class="cm-empty">Sin datos en este periodo.</div>';
  return '<div class="bh">' + items.map((i) => {
    const c = i.color || o.color || COL_CAT[0];
    return `<div class="bh-r" data-tip="${esc(i.k)}||${esc(fmt(i.v))}${tot ? " · " + Math.round(i.v / tot * 100) + " %" : ""}${i.sub ? "||" + esc(i.sub) : ""}"><span class="bh-l">${esc(i.k)}</span><span class="bh-t"><i style="width:${Math.max(1.5, i.v / max * 100)}%;background:${c}"></i></span><b>${esc(fmt(i.v))}</b></div>`;
  }).join("") + "</div>";
}
function donut(items, o = {}) {
  const tot = items.reduce((a, i) => a + i.v, 0), R = 54, C = 2 * Math.PI * R, fmt = o.fmt || String;
  if (!tot) return '<div class="cm-empty">Sin datos en este periodo.</div>';
  let acc = 0;
  const arcs = items.map((i, ix) => { const len = i.v / tot * C, d = `<circle cx="70" cy="70" r="${R}" fill="none" stroke="${COL_CAT[ix % COL_CAT.length]}" stroke-width="18" stroke-dasharray="${Math.max(0, len - 2)} ${C}" stroke-dashoffset="${-acc}" transform="rotate(-90 70 70)" data-tip="${esc(i.k)}||${esc(fmt(i.v))} · ${Math.round(i.v / tot * 100)} %"/>`; acc += len; return d; }).join("");
  return `<div class="dn"><svg viewBox="0 0 140 140" role="img" aria-label="${esc(o.titulo || "Distribución")}"><circle cx="70" cy="70" r="${R}" fill="none" stroke="rgba(15,23,42,.08)" stroke-width="18"/>${arcs}<text x="70" y="66" text-anchor="middle" fill="#0f172a" font-size="16" font-weight="800">${esc(o.centro != null ? o.centro : fmt(tot))}</text><text x="70" y="84" text-anchor="middle" fill="#64748b" font-size="9.5">${esc(o.sub || "total")}</text></svg>` +
    `<ul class="lg">${items.map((i, ix) => `<li><i style="background:${COL_CAT[ix % COL_CAT.length]}"></i><span>${esc(i.k)}</span><b>${Math.round(i.v / tot * 100)}%</b></li>`).join("")}</ul></div>`;
}
function areaTiempo(puntos, o = {}) {
  if (puntos.length < 2) return '<div class="cm-empty">Se necesitan al menos 2 días con datos.</div>';
  const W = 640, H = 220, L = 46, Rr = 12, T = 12, B = 26, fmt = o.fmt || String, col = o.color || COL_CAT[0];
  const max = Math.max(...puntos.map((p) => p.v)) * 1.12 || 1, x = (i) => L + (W - L - Rr) * i / (puntos.length - 1), y = (v) => T + (H - T - B) * (1 - v / max);
  const linea = puntos.map((p, i) => `${i ? "L" : "M"}${x(i).toFixed(1)},${y(p.v).toFixed(1)}`).join(" ");
  const grid = [0, .25, .5, .75, 1].map((f) => `<line x1="${L}" x2="${W - Rr}" y1="${y(max * f)}" y2="${y(max * f)}" stroke="rgba(15,23,42,.08)"/><text x="${L - 8}" y="${y(max * f) + 4}" text-anchor="end" fill="#64748b" font-size="10.5">${fmtCorto(max * f)}</text>`).join("");
  const paso = Math.ceil(puntos.length / 7), ejes = puntos.map((p, i) => i % paso === 0 ? `<text x="${x(i)}" y="${H - 6}" text-anchor="middle" fill="#64748b" font-size="10.5">${p.k.slice(8)}/${p.k.slice(5, 7)}</text>` : "").join("");
  const hit = puntos.map((p, i) => `<rect x="${x(i) - (W - L) / puntos.length / 2}" y="${T}" width="${(W - L) / puntos.length}" height="${H - T - B}" fill="transparent" data-tip="${fmtFecha(p.k + "T12:00:00").slice(0, 10)}||${esc(fmt(p.v))}" data-px="${x(i)}" data-py="${y(p.v)}"/>`).join("");
  return `<svg class="ar" viewBox="0 0 ${W} ${H}" role="img" aria-label="${esc(o.titulo || "Serie diaria")}"><defs><linearGradient id="g${o.id || 0}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${col}" stop-opacity=".28"/><stop offset="1" stop-color="${col}" stop-opacity="0"/></linearGradient></defs>${grid}${ejes}<path d="${linea} L${x(puntos.length - 1)},${y(0)} L${x(0)},${y(0)}Z" fill="url(#g${o.id || 0})"/><path d="${linea}" fill="none" stroke="${col}" stroke-width="2" stroke-linejoin="round"/><circle class="dot" r="4.5" fill="${col}" stroke="#ffffff" stroke-width="2" opacity="0"/>${hit}</svg>`;
}
function spark(vals, color) {
  const v = vals.filter((x) => x != null); if (v.length < 2) return "";
  const W = 90, H = 28, mn = Math.min(...v), mx = Math.max(...v), r = mx - mn || 1;
  const pts = vals.map((x, i) => x == null ? null : `${(i / (vals.length - 1) * W).toFixed(1)},${(H - 3 - (x - mn) / r * (H - 6)).toFixed(1)}`).filter(Boolean);
  return `<svg class="sp" viewBox="0 0 ${W} ${H}" aria-hidden="true"><polyline points="${pts.join(" ")}" fill="none" stroke="${color}" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>`;
}
function columnas(items, o = {}) {
  const max = Math.max(1, ...items.map((i) => i.v));
  return '<div class="cv">' + items.map((i) => `<div class="cv-c" data-tip="${esc(i.k)}||${esc((o.fmt || String)(i.v))}"><b>${i.v}</b><span style="height:${Math.max(3, i.v / max * 100)}%;background:${i.color || COL_CAT[0]}"></span><em>${esc(i.k)}</em></div>`).join("") + "</div>";
}
function delta(v, prev, k) {
  if (v == null || prev == null || prev === 0) return "";
  const d = (v - prev) / Math.abs(prev) * 100, bueno = k ? (k.dir === ">=") === (d >= 0) : d >= 0;
  if (Math.abs(d) < 0.5) return '<span class="dl" style="color:#64748b">▬ sin cambio</span>';
  return `<span class="dl" style="color:${bueno ? ST.ok : ST.bad}">${d > 0 ? "▲" : "▼"} ${Math.abs(d).toFixed(1)} % vs. periodo anterior</span>`;
}

/* ---------- Tooltip global y barra de filtros ---------- */
function activarTips() {
  let t = document.getElementById("cm-tip");
  if (!t) { t = document.createElement("div"); t.id = "cm-tip"; document.body.appendChild(t); }
  document.addEventListener("pointermove", (e) => {
    const el = e.target.closest && e.target.closest("[data-tip]");
    document.querySelectorAll(".ar .dot").forEach((d) => (d.style.opacity = 0));
    if (!el) { t.style.opacity = 0; return; }
    const [a, b, c] = el.dataset.tip.split("||");
    t.innerHTML = `<b>${a}</b><br>${b || ""}${c ? `<br><span>${c}</span>` : ""}`;
    t.style.opacity = 1; t.style.left = Math.min(innerWidth - 220, e.clientX + 14) + "px"; t.style.top = (e.clientY + 16) + "px";
    if (el.dataset.px) { const d = el.ownerSVGElement.querySelector(".dot"); d.setAttribute("cx", el.dataset.px); d.setAttribute("cy", el.dataset.py); d.style.opacity = 1; }
  });
}
function barraFiltros(id, alCambiar) {
  const f = getFiltro(), box = document.getElementById(id);
  const R = [["7", "7 días"], ["30", "30 días"], ["90", "90 días"], ["todo", "Todo el historial"]];
  box.innerHTML = `<div class="fb-r">${R.map(([k, t]) => `<button type="button" class="pill ${f.rango === k ? "on" : ""}" data-r="${k}">${t}</button>`).join("")}</div>` +
    `<select class="inp" id="fb-c" aria-label="Canal"><option value="">Todos los canales</option>${CANALES_TODOS.map((c) => `<option ${f.canal === c ? "selected" : ""}>${c}</option>`).join("")}</select><span class="fb-n" id="fb-n"></span>`;
  box.addEventListener("click", (e) => { const b = e.target.closest("[data-r]"); if (!b) return; const n = { ...getFiltro(), rango: b.dataset.r }; setFiltro(n); box.querySelectorAll("[data-r]").forEach((x) => x.classList.toggle("on", x === b)); alCambiar(n); });
  box.querySelector("#fb-c").addEventListener("change", (e) => { const n = { ...getFiltro(), canal: e.target.value }; setFiltro(n); alCambiar(n); });
}
