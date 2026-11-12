<?php
$pageTitle      = 'Inicio · Centro de Operaciones';
$pageLayout     = 'inicio';
$includeDarkCss = false;
$pageCss        = ['css/dark.css'];
$pageStyles     = <<<'CSS'
  :root {
    --font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }
  * { box-sizing: border-box; }
  html, body { min-height: 100%; }
  body { margin: 0; font-family: var(--font); color: #0f172a; background: #f8fafc; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
  button { font-family: inherit; }
  .bg, #embers { display: none !important; }
  .page { position: relative; min-height: 100vh; display: flex; flex-direction: column; background: #f8fafc; }
  header.top {
    position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between;
    gap: 16px; padding: 12px clamp(16px, 3vw, 36px); background: #ffffff;
    border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }
  .brand { display: flex; align-items: center; gap: 12px; }
  .brand .logo-chip { width: 36px; height: 36px; border-radius: 8px; background: #2563eb; color: #fff; display: grid; place-items: center; font-size: 18px; }
  .brand .title { display: flex; flex-direction: column; line-height: 1.15; }
  .brand .title b { font-size: 16px; letter-spacing: -.2px; color: #0f172a; font-weight: 700; }
  .brand .title span { font-size: 12px; color: #64748b; }
  .who { display: flex; align-items: center; gap: 10px; font-size: 13.5px; }
  .who .name { font-weight: 600; color: #1e293b; }
  .who .role { padding: 4px 10px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 600; }
  .logout { height: 34px; padding: 0 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background .15s; }
  .logout:hover { background: #f1f5f9; color: #0f172a; }
  .wrap { width: 100%; max-width: 1320px; margin: 0 auto; padding: 28px clamp(16px, 3vw, 36px) 56px; flex: 1; }
  .hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 24px 28px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; }
  .hero .eyebrow { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #16a34a; }
  .hero .eyebrow .dot { width: 7px; height: 7px; border-radius: 50%; background: #16a34a; }
  .hero h1 { margin: 6px 0 6px; font-size: clamp(24px, 3vw, 32px); letter-spacing: -.5px; line-height: 1.2; color: #0f172a; font-weight: 700; }
  .hero h1 span { color: #2563eb; }
  .hero p { margin: 0; color: #475569; font-size: 14.5px; max-width: 620px; line-height: 1.55; }
  .summary { display: flex; gap: 12px; flex-wrap: wrap; }
  .stat { min-width: 140px; padding: 12px 16px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; }
  .stat .num { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.1; }
  .stat .lbl { font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500; }
  .notice { display: none; align-items: center; gap: 12px; margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 13.5px; }
  .notice.show { display: flex; }
  .section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 16px 0 16px; }
  .section-head h2 { margin: 0; font-size: 13.5px; text-transform: uppercase; letter-spacing: .8px; color: #475569; font-weight: 700; }
  .section-head .legend { display: flex; align-items: center; gap: 16px; font-size: 12.5px; color: #64748b; }
  .section-head .legend i { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
  .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
  .card {
    --accent: #2563eb; position: relative; display: flex; flex-direction: column; border-radius: 12px; padding: 22px;
    background: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform .18s, border-color .18s, box-shadow .18s;
  }
  .card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--accent); }
  .card.allowed:hover { transform: translateY(-4px); border-color: #cbd5e1; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
  .card-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
  .icon-box { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; background: #eff6ff; border: 1px solid #dbeafe; }
  .icon-box svg { width: 22px; height: 22px; stroke: var(--accent); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
  .pill.ok { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
  .pill.ok::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }
  .pill.lock { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
  .card h3 { margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #0f172a; }
  .card p { margin: 0 0 14px; color: #475569; font-size: 13.5px; line-height: 1.5; }
  .tasks { list-style: none; margin: 0 0 16px; padding: 10px 12px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12.5px; color: #334155; display: grid; gap: 6px; }
  .tasks li { display: flex; align-items: center; gap: 8px; }
  .tasks li::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: var(--accent); flex: none; }
  .card-foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--accent); padding-top: 4px; }
  .card.allowed:hover .card-foot span:last-child { transform: translateX(3px); }
  .card-foot span:last-child { transition: transform .15s; }
  .card.locked { opacity: .65; filter: grayscale(.3); cursor: not-allowed; background: #f8fafc; }
  .card.locked .card-foot { color: #64748b; font-weight: 500; }
  @media (max-width: 680px) { .who .name { display: none; } .summary { width: 100%; } .stat { flex: 1; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <section class="hero">
      <div>
        <span class="eyebrow"><span class="dot"></span> Sesión activa</span>
        <h1>Hola, <span id="greet-name"></span></h1>
        <p id="role-desc"></p>
      </div>
      <div class="summary">
        <div class="stat">
          <div class="num" id="stat-mod">0 / 5</div>
          <div class="lbl">Módulos habilitados</div>
        </div>
        <div class="stat">
          <div class="num" id="stat-time">--:--</div>
          <div class="lbl">Ingreso de sesión</div>
        </div>
      </div>
    </section>

    <div class="notice" id="notice" role="status">
      <span>🔒</span>
      <span id="notice-text"></span>
    </div>

    <div class="section-head">
      <h2>Módulos del sistema</h2>
      <div class="legend">
        <span><i style="background:#22c55e"></i>Habilitado para tu rol</span>
        <span><i style="background:#64748b"></i>Restringido</span>
      </div>
    </div>

    <section class="grid" id="grid" aria-label="Módulos"></section>
  </main>

  <script>
    const s = requireSession();

    const META = {
      admin: {
        accent: "#f97316",
        icon: '<svg viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>',
        tasks: ["Catálogo de productos y fotos en MySQL", "Personalización de página, usuarios y roles"],
      },
      contact_center: {
        accent: "#38bdf8",
        icon: '<svg viewBox="0 0 24 24"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
        tasks: ["Captura y edición de pedidos", "Aprobación de pedidos de tienda web"],
      },
      bodega: {
        accent: "#fbbf24",
        icon: '<svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        tasks: ["Recepción de cargas asignadas", "Picking, empaque y control de inventario"],
      },
      logistica: {
        accent: "#34d399",
        icon: '<svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        tasks: ["Planificación de cargas y rutas", "Asignación de transporte y entregas"],
      },
      comercial: {
        accent: "#a78bfa",
        icon: '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        tasks: ["Seguimiento de ventas y clientes", "Indicadores clave del negocio (KPIs)"],
      },
    };

    if (s) {
      const nm = (s.name || s.user || "Usuario");
      const elWhoName = document.getElementById("who-name");
      if (elWhoName) elWhoName.textContent = nm;
      const elWhoRole = document.getElementById("who-role");
      if (elWhoRole) elWhoRole.textContent = s.role || "Usuario";
      const elGreet = document.getElementById("greet-name");
      if (elGreet) elGreet.textContent = nm.split(" ")[0];
      const elDesc = document.getElementById("role-desc");
      if (elDesc) {
        const desc = (ROLES && ROLES[s.role] && ROLES[s.role].descripcion) || "";
        elDesc.textContent = desc;
      }
      const elStatMod = document.getElementById("stat-mod");
      if (elStatMod) elStatMod.textContent = `${(s.modules || []).length} / ${Object.keys(MODULOS).length}`;
      const elStatTime = document.getElementById("stat-time");
      if (elStatTime) {
        const d = s.loginAt ? new Date(s.loginAt) : new Date();
        elStatTime.textContent = d.toLocaleTimeString("es-GT", { hour: "2-digit", minute: "2-digit" });
      }

      const elLogout = document.getElementById("logout");
      if (elLogout) elLogout.addEventListener("click", logout);

      const den = new URLSearchParams(location.search).get("denegado");
      if (den && MODULOS[den]) {
        const n = document.getElementById("notice");
        document.getElementById("notice-text").textContent =
          `Tu rol (${s.role}) no tiene permisos para acceder al módulo «${MODULOS[den].nombre}».`;
        n.classList.add("show");
      }

      const grid = document.getElementById("grid");
      const lista = Object.values(MODULOS).sort(
        (a, b) => Number(s.modules.includes(b.id)) - Number(s.modules.includes(a.id))
      );

      lista.forEach((m, idx) => {
        const permitido = s.modules.includes(m.id);
        const meta = META[m.id];
        const el = document.createElement(permitido ? "a" : "div");
        el.className = "card " + (permitido ? "allowed" : "locked");
        el.style.setProperty("--accent", meta.accent);
        el.style.animationDelay = (0.12 + idx * 0.07) + "s";
        if (permitido) el.href = m.href;

        const items = meta.tasks.map((t) => `<li>${t}</li>`).join("");
        el.innerHTML =
          `<div class="card-top">` +
            `<div class="icon-box">${meta.icon}</div>` +
            `<span class="pill ${permitido ? "ok" : "lock"}">${permitido ? "Disponible" : "Sin acceso"}</span>` +
          `</div>` +
          `<h3>${m.nombre}</h3>` +
          `<p>${m.resumen}</p>` +
          `<ul class="tasks">${items}</ul>` +
          `<div class="card-foot">` +
            `<span>${permitido ? "Entrar al módulo" : "Requiere rol autorizado"}</span>` +
            `<span>${permitido ? "→" : "🔒"}</span>` +
          `</div>`;
        grid.appendChild(el);
      });
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

