<?php
$pageTitle  = 'Administración · Bitácora';
$pageStyles = <<<'CSS'
  .cards { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
  .card-s { padding: 14px 16px; }
  .card-s b { display: block; font-size: 24px; letter-spacing: -.5px; }
  .card-s span { font-size: 12px; color: var(--muted); }
  .bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
  .bar .search { flex: 1 1 260px; }
  @media (max-width: 960px) { .cards { grid-template-columns: repeat(2, 1fr); } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Administración</div>
        <h1>Bitácora y estado del sistema</h1>
        <p>Auditoría de inicios de sesión, cambios operativos y mantenimiento de datos en MySQL.</p>
      </div>
      <div class="actions">
        <a class="btn" href="admin"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Administración</a>
        <button class="btn" type="button" id="btn-csv">Exportar bitácora CSV</button>
        <button class="btn danger" type="button" id="btn-reset">Restaurar datos de demostración</button>
      </div>
    </div>

    <section class="cards" id="stats"></section>

    <section class="panel">
      <div class="panel-h"><h2>Registro de eventos</h2><span class="sub" id="cnt"></span></div>
      <div class="panel-b">
        <div class="bar">
          <div class="search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input class="inp" id="q" type="search" placeholder="Filtrar por usuario, acción o detalle…">
          </div>
          <button class="btn sm" type="button" id="btn-clear">Limpiar bitácora</button>
        </div>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <script>
    const s = requireModulo("admin");
    if (s && !can("admin.bitacora.ver")) location.replace("admin");

    function render() {
      const peds = getPedidos();
      const clis = getClientes();
      const movs = getMovimientos();
      const users = Object.keys(getUsers());
      const auditList = getAudit();

      document.getElementById("stats").innerHTML =
        `<div class="panel card-s"><b>${peds.length}</b><span>Pedidos en sistema</span></div>` +
        `<div class="panel card-s"><b>${clis.length}</b><span>Clientes registrados</span></div>` +
        `<div class="panel card-s"><b>${PRODUCTOS.length}</b><span>SKUs en catálogo</span></div>` +
        `<div class="panel card-s"><b>${movs.length}</b><span>Movimientos kardex</span></div>` +
        `<div class="panel card-s"><b>${users.length}</b><span>Cuentas de usuario</span></div>`;

      const q = norm(document.getElementById("q").value).trim();
      const filtrada = auditList.filter((a) => !q || norm(`${a.user} ${a.action} ${a.detail}`).includes(q));
      document.getElementById("cnt").textContent = `${filtrada.length} evento${filtrada.length === 1 ? "" : "s"}`;
      document.getElementById("tbody").innerHTML = filtrada.length
        ? filtrada.map((a) => `<tr><td class="sub">${fmtFecha(a.ts)}</td><td><code>${esc(a.user)}</code></td><td><span class="badge plain" style="--c:#22b8cf">${esc(a.action)}</span></td><td>${esc(a.detail)}</td></tr>`).join("")
        : `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:28px">Sin eventos que coincidan.</td></tr>`;
    }

    if (s) {
      pintarShell(s, [{ t: "Administración", href: "admin" }, { t: "Bitácora y estado" }]);
      if (!can("admin.sistema.reset")) document.getElementById("btn-reset").hidden = true;
      render();

      document.getElementById("q").addEventListener("input", render);
      document.getElementById("btn-csv").addEventListener("click", () => {
        const rows = getAudit().map((a) => [fmtFecha(a.ts), a.user, a.action, a.detail]);
        descargarCSV("bitacora_pulso", ["Fecha", "Usuario", "Acción", "Detalle"], rows);
      });
      document.getElementById("btn-clear").addEventListener("click", async () => {
        if (await confirmar("Limpiar bitácora", "Se borrarán los eventos registrados hasta ahora en el navegador.", "Limpiar", true)) {
          localStorage.removeItem("df_audit_v1");
          audit("bitacora.limpiar", "Bitácora reiniciada por el administrador");
          render();
          toast("Bitácora reiniciada", "ok");
        }
      });
      document.getElementById("btn-reset").addEventListener("click", async () => {
        if (await confirmar("Restaurar datos de demostración", "Se restablecerán pedidos, clientes, rutas, stock y metas a su estado inicial tanto en el navegador como en MySQL.", "Restaurar todo", true)) {
          await resetDatosDemo();
          render();
          toast("Datos de demostración restaurados", "ok");
          location.reload();
        }
      });
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

