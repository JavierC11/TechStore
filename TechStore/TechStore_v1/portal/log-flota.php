<?php
$pageTitle = 'Flota';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js'];
$pageStyles = <<<'CSS'

  .fl { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
  .vc { border: 1px solid var(--border); border-radius: 18px; padding: 18px; background: #ffffff; display: flex; flex-direction: column; gap: 14px; }
  .vc-h { display: flex; align-items: center; gap: 12px; } .vc-h b { display: block; font-size: 16px; } .vc-h span.s { color: var(--muted); font-size: 12.5px; }
  .vc-h .badge { margin-left: auto; }
  .caps { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; } .caps div { padding: 10px 12px; border-radius: 12px; background: #f8fafc; font-size: 12px; color: var(--muted); } .caps b { display: block; color: #0f172a; font-size: 16px; }
  .vc p { margin: 0; font-size: 13px; color: var(--muted); } .vc-f { display: flex; gap: 8px; margin-top: auto; }
  .frm { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; } .frm .full { grid-column: 1 / -1; } .err { color: #dc2626; font-size: 13px; min-height: 18px; }
  @media (max-width: 560px) { .frm { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Logística</div><h1>Flota</h1><p>Vehículos, capacidades y disponibilidad. Las capacidades son valores de demostración: edítalas a tu flota real.</p></div>
      <div class="actions"><button class="btn primary" id="btn-add" hidden>+ Agregar vehículo</button></div>
    </div>
    <section class="fl" id="fl"></section>
  </main>
  <script>

  const s = requireModulo("logistica");
  const edita = () => can("log.flota.gestionar");

  function render() {
    document.getElementById("fl").innerHTML = getVehiculos().map((v) => {
      const pl = rutaPlanificada(v.id);
      const act = getRutas().filter((r) => r.vehiculoId === v.id && r.estado === "Despachada").sort((a, b) => new Date(b.despachadaEn || b.creadaEn) - new Date(a.despachadaEn || a.creadaEn))[0];
      const info = pl ? `Ruta planificada ${pl.id} · ${pl.pedidos.length} pedidos` : v.estado === "En ruta" && act ? `En ruta ${act.id} desde ${fmtFecha(act.despachadaEn || act.creadaEn)}` : "Sin ruta activa";
      return `<article class="vc"><div class="vc-h"><div class="vico" style="--c:${TIPOS_VEHICULO[v.tipo] || "#22b8cf"}">${ICONO_CAMION}</div><div><b>${esc(v.nombre)}</b><span class="s">${esc(v.placa)} · ${esc(v.tipo)}</span></div><span class="badge" style="--c:${ESTADOS_VEHICULO[v.estado]}">${v.estado}</span></div>` +
        `<div class="caps"><div>Capacidad de peso<b>${fmtKg(v.capKg)}</b></div><div>Capacidad de volumen<b>${fmtM3(v.capM3)}</b></div></div>` +
        `<p>Piloto habitual: <b style="color:#0f172a">${esc(v.pilotoHabitual || "—")}</b><br>${esc(info)}</p>` +
        `<div class="vc-f">${edita() ? `<button class="btn sm" data-ed="${v.id}">Editar</button>` : ""}${edita() && v.estado === "En ruta" ? `<button class="btn sm" data-rg="${v.id}">Marcar regreso</button>` : ""}</div></article>`;
    }).join("");
  }

  function form(id) {
    const v = id ? { ...getVehiculo(id) } : { nombre: "", placa: "", tipo: "Pick-up", capKg: "", capM3: "", pilotoHabitual: PILOTOS[0], estado: "Disponible" };
    const ov = document.createElement("div"); ov.className = "overlay";
    const opt = (arr, sel) => arr.map((x) => `<option ${x === sel ? "selected" : ""}>${esc(x)}</option>`).join("");
    ov.innerHTML = `<div class="modal"><div class="modal-h"><h3>${id ? "Editar" : "Agregar"} vehículo</h3><button class="btn sm icon ghost" data-x>✕</button></div><div class="modal-b"><div class="frm">` +
      `<div class="field full"><label>Nombre</label><input class="inp" id="v-n" value="${esc(v.nombre)}"></div>` +
      `<div class="field"><label>Placa</label><input class="inp" id="v-p" value="${esc(v.placa)}"></div>` +
      `<div class="field"><label>Tipo</label><select class="inp" id="v-t">${opt(Object.keys(TIPOS_VEHICULO), v.tipo)}</select></div>` +
      `<div class="field"><label>Capacidad de peso (kg)</label><input class="inp" id="v-kg" type="number" min="0" step="any" value="${v.capKg}"></div>` +
      `<div class="field"><label>Capacidad de volumen (m³)</label><input class="inp" id="v-m3" type="number" min="0" step="any" value="${v.capM3}"></div>` +
      `<div class="field"><label>Piloto habitual</label><select class="inp" id="v-pi">${opt(PILOTOS, v.pilotoHabitual)}</select></div>` +
      `<div class="field"><label>Estado</label><select class="inp" id="v-e">${opt(Object.keys(ESTADOS_VEHICULO), v.estado)}</select></div>` +
      `<div class="err full" id="err"></div></div></div><div class="modal-f"><button class="btn ghost" data-x>Cancelar</button><button class="btn primary" id="ok">Guardar</button></div></div>`;
    document.body.appendChild(ov);
    ov.addEventListener("click", (e) => {
      if (e.target === ov || e.target.closest("[data-x]")) { ov.remove(); return; }
      if (!e.target.closest("#ok")) return;
      const g = (i) => ov.querySelector("#" + i).value.trim();
      const n = { ...v, nombre: g("v-n"), placa: g("v-p").toUpperCase(), tipo: g("v-t"), capKg: Number(g("v-kg")), capM3: Number(g("v-m3")), pilotoHabitual: g("v-pi"), estado: g("v-e") };
      let err = "";
      if (!n.nombre) err = "El nombre es obligatorio.";
      else if (!n.placa) err = "La placa es obligatoria.";
      else if (getVehiculos().some((x) => x.id !== n.id && x.placa === n.placa)) err = "Ya existe un vehículo con esa placa.";
      else if (!(n.capKg > 0) || !(n.capM3 > 0)) err = "Las capacidades deben ser mayores que cero.";
      else if (id && n.estado === "Mantenimiento" && rutaPlanificada(id)) err = "Tiene una ruta planificada: cancélala o vacíala antes de enviarlo a mantenimiento.";
      else if (id && rutaPlanificada(id)) {
        const c = cargaRuta(rutaPlanificada(id));
        if (c.kg > n.capKg || c.m3 > n.capM3) err = "La ruta planificada actual excedería la nueva capacidad.";
      }
      if (err) { ov.querySelector("#err").textContent = err; return; }
      saveVehiculo(n); ov.remove(); render(); toast("Vehículo guardado", "ok");
    });
  }

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Logística", href: "logistica" }, { t: "Flota" }]);
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();
    const add = document.getElementById("btn-add"); add.hidden = !edita(); add.onclick = () => form();
    document.getElementById("fl").addEventListener("click", (e) => {
      const ed = e.target.closest("[data-ed]"), rg = e.target.closest("[data-rg]");
      if (ed) form(ed.dataset.ed); else if (rg) { marcarRegreso(rg.dataset.rg); render(); toast("Vehículo disponible de nuevo", "ok"); }
    });
    render();
  }
  if (s && !can("log.flota.ver")) location.replace("logistica"); else if (s) iniciar(s);
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
