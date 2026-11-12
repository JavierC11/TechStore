<?php
$pageTitle = 'Tablero de KPIs';
$pageCss = ['css/comercial.css'];
$pageLibs = ['js/logistica.js', 'js/comercial.js', 'js/xlsx-mini.js'];
$pageStyles = <<<'CSS'

  .sum3 { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
  .kc { padding: 18px; border-radius: 18px; background: var(--surface); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 6px; border-top: 3px solid var(--c); }
  .kgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; margin-bottom: 20px; }
  .kc .id { color: var(--faint); font-size: 12px; font-weight: 700; letter-spacing: .6px; } .kc .n { font-weight: 600; min-height: 38px; line-height: 1.3; }
  .kc .v { font-size: 30px; font-weight: 800; letter-spacing: -1px; font-variant-numeric: tabular-nums; } .kc .row { display: flex; justify-content: space-between; align-items: center; gap: 8px; } .kc .mt { color: var(--muted); font-size: 12.5px; }
  .ef { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; } .frm .field label { font-size: 12px; } .frm-k { display: grid; grid-template-columns: 56px 1fr 110px 110px; gap: 10px; align-items: center; margin-bottom: 10px; font-size: 13.5px; } .frm-k .inp { height: 36px; }
  .modal.wide { max-width: 760px; width: calc(100% - 24px); }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div><div class="eyebrow">Comercial</div><h1>Tablero de KPIs</h1><p>Indicadores K01 a K10 con semáforo, tendencia y comparación con el periodo anterior.</p></div>
      <div class="actions"><button class="btn" id="b-xl" hidden>Exportar Excel</button><button class="btn" id="b-metas" hidden>Editar metas</button></div>
    </div>
    <div class="fb" id="fb"></div>
    <div class="sum3" id="sum"></div>
    <div id="kg" class="kgrid"></div>
    <section class="panel"><div id="det"></div></section>
  </main>
  <script>

  const s = requireModulo("comercial");
  const crumbs = (t) => [{ t: "Inicio", href: "inicio" }, { t: "Comercial", href: "comercial" }, { t }];
  const $ = (id) => document.getElementById(id);
  const cardH = (t, sub) => `<div class="panel-h"><h2>${t}</h2>${sub ? `<span class="sub">${sub}</span>` : ""}</div>`;

  let K = [];
  function pintar(f) {
    const w = ventana(f), P = pedidosFiltrados(f, w), pv = pedidosPrevios(f, w);
    K = calcularKpis(P, w.dias, pv, w.dias, f);
    $("fb-n").textContent = `${P.length} pedidos analizados`;
    const cnt = (e) => K.filter((k) => k.estado === e).length;
    $("sum").innerHTML = ["ok", "warn", "bad"].map((e) => `<span class="stb" style="--c:${ST[e]}">${ST_ICO[e]} ${cnt(e)} ${ST_TXT[e]}${cnt(e) === 1 ? "" : "s"}</span>`).join("") + `<span class="muted" style="align-self:center;font-size:13px">de ${K.length} indicadores</span>`;
    $("kg").innerHTML = K.map((k) => {
      const c = k.estado ? ST[k.estado] : "#94a3b8";
      return `<article class="kc" style="--c:${c}"><div class="row"><span class="id">${k.id}</span>${k.estado ? `<span class="stb" style="--c:${c}">${ST_ICO[k.estado]} ${ST_TXT[k.estado]}</span>` : '<span class="stb" style="--c:#94a3b8">Sin datos</span>'}</div>` +
        `<div class="n">${esc(k.nombre)}</div><div class="v">${fmtKpi(k, k.valor)}</div>` +
        `<div class="row"><span class="mt">Meta ${fmtMeta(k)}</span>${spark(k.serie, c)}</div>${delta(k.valor, k.previo, k) || '<span class="dl" style="color:#64748b">' + (f.rango === "todo" ? "Sin comparación (todo el historial)" : "") + "</span>"}</article>`;
    }).join("");
    $("det").innerHTML = cardH("Definición de cada indicador", "Semáforo: verde cumple la meta · amarillo dentro de la tolerancia · rojo fuera") +
      '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>ID</th><th>Indicador</th><th>Fórmula</th><th>Meta</th><th>Tolerancia (amarillo)</th><th>Valor</th><th>Semáforo</th></tr></thead><tbody>' +
      K.map((k) => `<tr><td><b>${k.id}</b></td><td>${esc(k.nombre)}</td><td class="muted">${esc(k.formula)}</td><td>${fmtMeta(k)}</td><td>${k.dir === ">=" ? "≥ " : "≤ "}${k.amarillo}${k.unidad === "%" ? "%" : ""}</td><td><b>${fmtKpi(k, k.valor)}</b></td>` +
        `<td>${k.estado ? `<span class="stb" style="--c:${ST[k.estado]}">${ST_ICO[k.estado]} ${ST_TXT[k.estado]}</span>` : "—"}</td></tr>`).join("") + "</tbody></table></div>" +
      '<div class="card-b muted" style="font-size:13px">Todos los indicadores K01–K10 se calculan en vivo desde MySQL (<code>pedidos</code> + <code>telemetria_eventos</code>). Los usuarios con permiso pueden editar metas y tolerancias.</div>';
  }
  const xl = $("b-xl"), mt = $("b-metas");
  xl.hidden = !can("cm.exportar"); mt.hidden = !can("cm.metas.editar");
  xl.onclick = () => descargarXlsx("kpis-comercial.xlsx", "KPIs", [{ titulo: "ID", ancho: 8 }, { titulo: "Indicador", ancho: 38 }, { titulo: "Fórmula", ancho: 44 }, { titulo: "Meta", ancho: 12 }, { titulo: "Valor", ancho: 14 }, { titulo: "Semáforo", ancho: 12 }],
    K.map((k) => [k.id, k.nombre, k.formula, fmtMeta(k), fmtKpi(k, k.valor), k.estado ? ST_TXT[k.estado] : "Sin datos"]));
  mt.onclick = () => {
    const M = getMetas(), ov = document.createElement("div"); ov.className = "overlay";
    ov.innerHTML = '<div class="modal wide"><div class="modal-h"><h3>Metas y tolerancias</h3><button class="btn sm icon ghost" data-x>✕</button></div><div class="modal-b"><p class="muted" style="margin-top:0">Verde si el valor cumple la <b>meta</b>; amarillo si cumple la <b>tolerancia</b>; rojo si no cumple ninguna.</p>' +
      '<div class="frm-k" style="color:var(--faint);font-size:11.5px"><span>ID</span><span>Indicador</span><span>Meta</span><span>Tolerancia</span></div>' +
      M.map((k) => `<div class="frm-k"><b>${k.id}</b><span>${esc(k.nombre)} <span class="muted">(${k.dir === ">=" ? "≥" : "≤"} ${k.unidad})</span></span><input class="inp" type="number" step="any" min="0" data-m="${k.id}" value="${k.meta}"><input class="inp" type="number" step="any" min="0" data-a="${k.id}" value="${k.amarillo}"></div>`).join("") +
      '<div class="err" id="err" style="color:#dc2626;font-size:13px;min-height:18px"></div></div><div class="modal-f"><button class="btn ghost" id="rs">Valores originales</button><button class="btn ghost" data-x>Cancelar</button><button class="btn primary" id="ok">Guardar</button></div></div>';
    document.body.appendChild(ov);
    ov.addEventListener("click", (e) => {
      if (e.target === ov || e.target.closest("[data-x]")) { ov.remove(); return; }
      if (e.target.closest("#rs")) { guardarLS(CM_KEYS.metas, {}); auditar("Metas de KPI", "Restablecidas a los valores originales"); ov.remove(); pintar(getFiltro()); toast("Metas restablecidas", "ok"); return; }
      if (!e.target.closest("#ok")) return;
      const N = M.map((k) => ({ ...k, meta: Number(ov.querySelector(`[data-m="${k.id}"]`).value), amarillo: Number(ov.querySelector(`[data-a="${k.id}"]`).value) }));
      const mal = N.find((k) => !(k.meta >= 0) || !(k.amarillo >= 0) || (k.dir === ">=" ? k.amarillo > k.meta : k.amarillo < k.meta));
      if (mal) { ov.querySelector("#err").textContent = `${mal.id}: la tolerancia debe ser ${mal.dir === ">=" ? "menor o igual" : "mayor o igual"} que la meta.`; return; }
      guardarMetas(N); auditar("Metas de KPI", "Metas y tolerancias actualizadas"); ov.remove(); pintar(getFiltro()); toast("Metas guardadas", "ok");
    });
  };

  if (s && !can("cm.tablero.ver")) location.replace("comercial"); else if (s) {
    pintarShell(s, crumbs("Tablero de KPIs")); if ($("year")) $("year").textContent = new Date().getFullYear(); activarTips();
    barraFiltros("fb", pintar); pintar(getFiltro());
  }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
