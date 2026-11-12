/* =========================================================
   DigyFenix | Pulso — Componentes de interfaz compartidos
   Encabezado, migas de pan, avisos (toasts), diálogo de confirmación y exportación.
   ========================================================= */

function p2(n) { return String(n).padStart(2, "0"); }

function cleanUrl(href) {
  if (!href) return "";
  return String(href).replace(/\.html($|\?|#)/i, "$1");
}

function pintarShell(s, crumbs) {
  const top = document.getElementById("top");
  if (!top) return;
  const trail = (crumbs || [])
    .map((c, i) => {
      const last = i === crumbs.length - 1;
      const url = cleanUrl(c.href);
      return `<span class="sep">/</span>` +
        (last || !url ? `<span class="cur">${esc(c.t)}</span>` : `<a href="${esc(url)}">${esc(c.t)}</a>`);
    })
    .join("");

  const dbConnected = window.__PULSO_DB__ && window.__PULSO_DB__.connected;
  const dbBadge = dbConnected
    ? `<span class="badge" style="--c:#16a34a" title="Conectado en tiempo real a MySQL">● MySQL</span>`
    : "";

  top.innerHTML =
    `<div class="left">` +
      `<a class="brand" href="inicio" title="Volver al inicio">` +
        `<span class="chip">💻</span>` +
        `<b>${esc(APP.empresa)} · ${esc(APP.producto)}</b>` +
      `</a>` +
      `<nav class="crumbs" aria-label="Ruta de navegación"><a href="inicio">Inicio</a>${trail}</nav>` +
    `</div>` +
    `<div class="who">` +
      dbBadge +
      `<a class="btn sm" href="tienda" title="Ver tienda pública">Ver Tienda</a>` +
      `<span class="nm">${esc(s.name)}</span>` +
      `<span class="rl">${esc(s.role)}</span>` +
      `<button class="btn sm" type="button" id="shell-out">Salir</button>` +
    `</div>`;

  document.getElementById("shell-out").addEventListener("click", logout);
}

function toast(texto, tipo) {
  let box = document.getElementById("toasts");
  if (!box) {
    box = document.createElement("div");
    box.id = "toasts";
    document.body.appendChild(box);
  }
  const el = document.createElement("div");
  el.className = "toast " + (tipo || "ok");
  el.textContent = texto;
  box.appendChild(el);
  setTimeout(() => {
    el.style.opacity = "0";
    el.style.transition = "opacity .25s";
    setTimeout(() => el.remove(), 260);
  }, 3000);
}

function confirmar(titulo, mensaje, textoOk, peligro) {
  return new Promise((resolve) => {
    const ov = document.createElement("div");
    ov.className = "overlay";
    ov.innerHTML =
      `<div class="modal" style="max-width:440px" role="dialog" aria-modal="true">` +
      `<div class="modal-h"><h3>${esc(titulo)}</h3></div>` +
      `<div class="modal-b"><p style="margin:0;color:var(--muted);line-height:1.55">${esc(mensaje)}</p></div>` +
      `<div class="modal-f">` +
      `<button class="btn" type="button" data-r="0">Cancelar</button>` +
      `<button class="btn ${peligro ? "danger" : "primary"}" type="button" data-r="1">${esc(textoOk || "Confirmar")}</button>` +
      `</div>` +
      `</div>`;
    document.body.appendChild(ov);
    const fin = (v) => { ov.remove(); resolve(v); };
    ov.addEventListener("click", (e) => {
      if (e.target === ov) fin(false);
      const b = e.target.closest("[data-r]");
      if (b) fin(b.dataset.r === "1");
    });
    const onKey = (e) => { if (e.key === "Escape") { document.removeEventListener("keydown", onKey); fin(false); } };
    document.addEventListener("keydown", onKey);
    ov.querySelector('[data-r="1"]').focus();
  });
}

/* ---------- Exportación CSV y Excel ---------- */
function descargarCSV(nombreArchivo, encabezados, filas) {
  const escCSV = (v) => `"${String(v ?? "").replace(/"/g, '""')}"`;
  const csv = "\uFEFF" + [encabezados, ...filas].map((r) => r.map(escCSV).join(",")).join("\r\n");
  dispararDescarga(new Blob([csv], { type: "text/csv;charset=utf-8;" }), nombreArchivo.endsWith(".csv") ? nombreArchivo : nombreArchivo + ".csv");
}

function descargarXLS(nombreArchivo, tituloHoja, encabezados, filas) {
  const ths = encabezados.map((h) => `<th style="background:#0a2351;color:#ffffff;padding:8px 10px;border:1px solid #cbd5e1;text-align:left">${esc(h)}</th>`).join("");
  const trs = filas.map((r) => `<tr>` + r.map((c) => {
    const esNum = typeof c === "number" && !Number.isNaN(c);
    return `<td style="padding:6px 10px;border:1px solid #cbd5e1;${esNum ? "mso-number-format:'0.00';text-align:right" : ""}">${esc(c)}</td>`;
  }).join("") + `</tr>`).join("");
  const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><title>${esc(tituloHoja || "Reporte")}</title></head><body><table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`;
  dispararDescarga(new Blob(["\uFEFF" + html], { type: "application/vnd.ms-excel;charset=utf-8;" }), nombreArchivo.endsWith(".xls") ? nombreArchivo : nombreArchivo + ".xls");
}

function dispararDescarga(blob, nombre) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
