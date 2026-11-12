<?php
$pageTitle  = 'Tienda · Tu cuenta';
$pageLayout = 'tienda';
$pageCss    = ['css/tienda.css'];
$pageLibs   = ['js/tienda.js'];
require_once __DIR__ . '/includes/header.php';
?>
  <main class="tw">
    <section class="panel auth"><div class="tabs2"><button id="t1" class="on">Ingresar</button><button id="t2">Crear cuenta</button></div>
      <form id="f1" novalidate>
        <div class="field"><label>Correo electrónico</label><input class="inp" id="l-e" type="email" autocomplete="username"></div>
        <div class="field"><label>Contraseña</label><input class="inp" id="l-p" type="password" autocomplete="current-password"></div>
        <div class="err-f" id="e1"></div>
        <button class="btn primary" style="width:100%">Ingresar</button>
        <div style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--border);text-align:center">
          <button type="button" class="btn sm" id="btn-demo-cli" style="width:100%">👤 Cargar cuenta demo (Ana Lucía)</button>
        </div>
      </form>
      <form id="f2" novalidate hidden><div class="field"><label>Nombre completo o razón social</label><input class="inp" id="r-n" autocomplete="name"></div>
        <div class="field"><label>Tipo de cliente</label><select class="inp" id="r-t"><option>Individual</option><option>Empresa</option></select></div>
        <div class="field"><label>Correo electrónico</label><input class="inp" id="r-e" type="email" autocomplete="email"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="field"><label>Teléfono</label><input class="inp" id="r-tel" inputmode="numeric" placeholder="5555-1234" autocomplete="tel"></div><div class="field"><label>NIT</label><input class="inp" id="r-nit" placeholder="CF si no tienes"></div></div>
        <div class="field"><label>Contraseña</label><input class="inp" id="r-p" type="password" autocomplete="new-password"><small class="muted">Mínimo 8 caracteres, con mayúscula, minúscula y número.</small></div>
        <div class="err-f" id="e2"></div><button class="btn primary" style="width:100%">Crear mi cuenta</button></form></section>
  </main>
  <script>
    const $ = (id) => document.getElementById(id);

    pintarTienda("tienda");
    const rawNext = new URLSearchParams(location.search).get("next") || "tienda";
    const cleanNext = rawNext.replace(/\.html($|\?)/i, "$1");
    const seguro = /^[a-z0-9\-]+(\?.*)?$/i.test(cleanNext) ? cleanNext : "tienda";
    if (getCliSesion()) location.replace(seguro);
    const tab = (n) => { $("f1").hidden = n !== 1; $("f2").hidden = n !== 2; $("t1").classList.toggle("on", n === 1); $("t2").classList.toggle("on", n === 2); };
    $("t1").onclick = () => tab(1); $("t2").onclick = () => tab(2); if (location.hash === "#registro") tab(2);
    const demoBtn = $("btn-demo-cli");
    if (demoBtn) {
      demoBtn.addEventListener("click", () => {
        $("l-e").value = "cliente@demo.com";
        $("l-p").value = "Cliente@2026";
        $("f1").requestSubmit();
      });
    }
    $("f1").addEventListener("submit", async (e) => { e.preventDefault(); const r = await ingresarCliente($("l-e").value, $("l-p").value); if (r.ok) location.href = seguro; else $("e1").textContent = r.error; });
    $("f2").addEventListener("submit", async (e) => { e.preventDefault(); const r = await registrarCliente({ nombre: $("r-n").value, tipo: $("r-t").value, email: $("r-e").value, telefono: $("r-tel").value, nit: $("r-nit").value, clave: $("r-p").value }); if (r.ok) location.href = seguro; else $("e2").textContent = r.error; });
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

