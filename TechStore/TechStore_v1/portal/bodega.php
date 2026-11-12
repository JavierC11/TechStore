<?php
$pageTitle = 'Bodega';
$pageCss   = ['css/logistica.css'];
$pageLibs  = ['js/logistica.js'];
$pageStyles = <<<'CSS'

  .hero { padding: 8px 0 16px; }
  .hero h1 { margin: 6px 0 8px; font-size: clamp(26px, 3.5vw, 36px); letter-spacing: -.6px; color: #0f172a; font-weight: 700; }
  .hero h1 span { color: #2563eb; }
  .hero p { margin: 0; color: #475569; max-width: 580px; line-height: 1.55; font-size: 15px; }
  .kpis { margin: 20px 0 24px; }

  .grid { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 16px; }
  .mod {
    --accent: #2563eb;
    position: relative; width: 340px; min-height: 220px; padding: 24px; display: flex; flex-direction: column;
    text-decoration: none; color: #0f172a; border-radius: 12px; overflow: hidden;
    background: #ffffff; border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: transform .2s, border-color .2s, box-shadow .2s;
  }
  .mod::after { content: ""; position: absolute; left: 0; top: 0; right: 0; height: 3px; background: var(--accent); }
  .mod:hover, .mod:focus-visible { transform: translateY(-4px); outline: none; border-color: #cbd5e1; box-shadow: 0 10px 24px rgba(0,0,0,0.07); }
  .mod .ico { width: 48px; height: 48px; border-radius: 10px; display: grid; place-items: center; background: #eff6ff; border: 1px solid #dbeafe; transition: transform .2s; }
  .mod:hover .ico { transform: scale(1.06); }
  .mod .ico svg { width: 24px; height: 24px; stroke: var(--accent); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .mod h2 { margin: 16px 0 6px; font-size: 18px; font-weight: 700; color: #0f172a; }
  .mod p { margin: 0; color: #475569; font-size: 14px; line-height: 1.5; flex: 1; }
  .mod .go { margin-top: 16px; display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--accent); }
  .mod .go svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: transform .2s; }
  .mod:hover .go svg { transform: translateX(4px); }
  .empty { margin-top: 20px; padding: 24px; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b; background: #ffffff; }
  @media (max-width: 700px) { .mod { width: 100%; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <section class="hero">
      <div class="eyebrow">Módulo</div>
      <h1><span>Bodega</span></h1>
      <p>Prepara el picking de cada ruta, carga el camión en el orden correcto y controla las existencias.</p>
    </section>

    <section class="kpis" id="kpis"></section>
    <section class="grid" id="grid"></section>
  </main>
  <script>

  const s = requireModulo("bodega");
  if (s) iniciar(s);

  function iniciar(s) {
    pintarShell(s, [{ t: "Inicio", href: "inicio" }, { t: "Bodega" }]);
    document.title = `${APP.empresa} | ${APP.producto} · Bodega`;
    if (document.getElementById("year")) document.getElementById("year").textContent = new Date().getFullYear();

    const rs = getRutas().filter((r) => r.estado === "Planificada");
    const low = PRODUCTOS.filter((p) => p.stock <= STOCK_MINIMO);
    const kpis = [
      { v: rs.filter((r) => estadoCarga(r) === "Pendiente").length, l: "Rutas por preparar", c: "#f4b63d" },
      { v: rs.filter((r) => estadoCarga(r) === "En picking").length, l: "En picking", c: "#22b8cf" },
      { v: rs.filter((r) => estadoCarga(r) === "Cargada").length, l: "Cargadas, listas para salir", c: "#2fc986" },
      { v: low.length, l: "Productos en o bajo el mínimo", c: "#ef5b66" },
    ];
    document.getElementById("kpis").innerHTML = kpis.map((k) =>
      `<div class="kpi" style="--c:${k.c}"><div class="v">${k.v}</div><div class="l">${k.l}</div><div class="bar"></div></div>`).join("");

    const flecha = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>';
    const grid = document.getElementById("grid");
    const subs = submodulosVisibles("bodega");
    if (!subs.length) { grid.innerHTML = '<div class="empty">Tu rol todavía no tiene submódulos habilitados en Bodega.</div>'; return; }
    subs.forEach((m, i) => {
      const a = document.createElement("a");
      a.className = "mod"; a.href = m.href;
      a.style.setProperty("--accent", m.color || "#2563eb"); a.style.setProperty("--i", i);
      a.innerHTML = `<div class="ico"><svg viewBox="0 0 24 24" aria-hidden="true">${m.icono}</svg></div><h2></h2><p></p><span class="go">Abrir ${flecha}</span>`;
      a.querySelector("h2").textContent = m.nombre;
      a.querySelector("p").textContent = m.desc;
      a.addEventListener("pointermove", (e) => {
        const r = a.getBoundingClientRect();
        a.style.setProperty("--mx", (e.clientX - r.left) + "px"); a.style.setProperty("--my", (e.clientY - r.top) + "px");
      });
      grid.appendChild(a);
    });
  }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
