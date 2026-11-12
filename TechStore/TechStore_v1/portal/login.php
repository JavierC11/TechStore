<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar sesión si se solicita logout explícito
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Si ya existe sesión PHP activa y no se solicitó logout, redirigir a inicio
if (!isset($_GET['logout']) && !empty($_SESSION['df_session']) && is_array($_SESSION['df_session']) && !empty($_SESSION['df_session']['user'])) {
    header('Location: inicio');
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user']) && isset($_POST['pass'])) {
    require_once __DIR__ . '/config/db.php';
    $userIn = trim((string)$_POST['user']);
    $passIn = trim((string)$_POST['pass']);

    $pdo = getPDO();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT `username`, `hash_clave`, `nombre`, `email`, `rol_id`, `activo` FROM `usuarios` WHERE LOWER(`username`) = LOWER(?)");
        $stmt->execute([$userIn]);
        $u = $stmt->fetch();
        if ($u) {
            if ((int)$u['activo'] === 0) {
                $loginError = 'Esta cuenta está desactivada. Contacta al administrador.';
            } else {
                $passHash = hash('sha256', $passIn);
                $valid = hash_equals(strtolower((string)$u['hash_clave']), strtolower($passHash));

                if ($valid) {
                    $stmtR = $pdo->prepare("SELECT `modulos_json`, `permisos_json` FROM `roles` WHERE `id` = ?");
                    $stmtR->execute([$u['rol_id']]);
                    $r = $stmtR->fetch() ?: ['modulos_json' => '[]', 'permisos_json' => '[]'];
                    $session = [
                        'user'     => $u['username'],
                        'name'     => $u['nombre'],
                        'email'    => $u['email'],
                        'role'     => $u['rol_id'],
                        'modules'  => json_decode($r['modulos_json'], true) ?: [],
                        'permisos' => json_decode($r['permisos_json'], true) ?: [],
                        'loginAt'  => date('c'),
                    ];
                    $_SESSION['df_session'] = $session;
                    $_SESSION['df_user']    = $session['user'];

                    $bStmt = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, ?, ?)");
                    $bStmt->execute([date('Y-m-d H:i:s'), $u['username'], 'login', 'Inicio de sesión exitoso por formulario POST (' . $u['rol_id'] . ')']);

                    header('Location: inicio');
                    exit;
                } else {
                    $loginError = 'Usuario o contraseña incorrectos.';
                }
            }
        } else {
            $loginError = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $loginError = 'No fue posible conectar con la base de datos MySQL.';
    }
}

$pageTitle      = 'Iniciar sesión';
$pageLayout     = 'login';
$includeDarkCss = false;
$pageCss        = ['css/theme.css'];
$pageStyles     = <<<'CSS'
  body {
    background: #f8fafc;
    color: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    margin: 0;
  }
  .bg, #embers { display: none !important; }
  .page-login {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 36px 18px;
    width: 100%;
    box-sizing: border-box;
  }
  .login-container {
    width: 100%;
    max-width: 1040px;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 48px;
    align-items: center;
    margin: 0 auto;
  }
  .login-hero {
    padding-right: 12px;
  }
  .hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12.5px;
    font-weight: 600;
    margin-bottom: 22px;
  }
  .hero-pill .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
  }
  .brand-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
  }
  .brand-logo-box {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: #2563eb;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 24px;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
  }
  .brand-name {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
    line-height: 1.1;
  }
  .brand-subtitle {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
  }
  .hero-title {
    font-size: clamp(26px, 3.2vw, 36px);
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
    letter-spacing: -0.6px;
    margin: 0 0 16px;
  }
  .hero-title span {
    color: #2563eb;
  }
  .hero-desc {
    font-size: 15px;
    line-height: 1.6;
    color: #475569;
    margin: 0 0 26px;
    max-width: 480px;
  }
  .features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 26px;
  }
  .feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .feature-item svg {
    width: 17px;
    height: 17px;
    stroke: #2563eb;
    stroke-width: 2;
    fill: none;
    flex-shrink: 0;
  }
  .store-link-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13.5px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    transition: color .15s;
  }
  .store-link-back:hover {
    color: #1d4ed8;
    text-decoration: underline;
  }

  /* Login Card */
  .login-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 34px 30px 26px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    width: 100%;
    box-sizing: border-box;
  }
  .card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
  }
  .card-eyebrow {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #2563eb;
  }
  .card-badge {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 3px 9px;
    border-radius: 6px;
  }
  .card-title {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    margin: 4px 0 6px;
  }
  .card-desc {
    font-size: 13.5px;
    color: #64748b;
    margin: 0 0 20px;
  }
  .form-group {
    margin-bottom: 16px;
  }
  .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
  }
  .input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }
  .input-wrapper > svg {
    position: absolute;
    left: 13px;
    width: 17px;
    height: 17px;
    stroke: #94a3b8;
    fill: none;
    stroke-width: 2;
    pointer-events: none;
    transition: stroke .15s;
  }
  .input-wrapper input {
    width: 100%;
    height: 44px;
    padding: 0 40px 0 40px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14.5px;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    font-family: inherit;
    box-sizing: border-box;
  }
  .input-wrapper input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }
  .input-wrapper:focus-within > svg {
    stroke: #2563eb;
  }
  .btn-toggle-eye {
    position: absolute;
    right: 8px;
    width: 32px;
    height: 32px;
    border: 0;
    background: transparent;
    color: #94a3b8;
    border-radius: 6px;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: color .15s, background .15s;
  }
  .btn-toggle-eye:hover {
    background: #f1f5f9;
    color: #0f172a;
  }
  .btn-toggle-eye svg {
    width: 17px;
    height: 17px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }
  .caps-warning {
    display: none;
    margin-top: 6px;
    font-size: 12px;
    color: #d97706;
    font-weight: 500;
  }
  .caps-warning.show { display: block; }
  .alert-box {
    display: none;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding: 10px 13px;
    border-radius: 8px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 13px;
    font-weight: 500;
  }
  .alert-box.show { display: flex; }
  .alert-box svg {
    flex-shrink: 0;
    width: 17px;
    height: 17px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }
  .btn-submit {
    width: 100%;
    height: 44px;
    margin-top: 4px;
    border: 1px solid #1d4ed8;
    border-radius: 8px;
    background: #2563eb;
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    transition: background .15s, box-shadow .15s, transform .05s;
    font-family: inherit;
    box-sizing: border-box;
  }
  .btn-submit:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
  }
  .btn-submit:active {
    transform: translateY(1px);
  }
  .btn-submit svg {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2.2;
  }
  .quick-access-box {
    margin-top: 22px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
  }
  .quick-access-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #64748b;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .quick-access-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .quick-pill {
    font-size: 12px;
    font-weight: 500;
    padding: 5px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
  }
  .quick-pill:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
  }
  .card-footer {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: #64748b;
  }

  @media (max-width: 900px) {
    .login-container {
      grid-template-columns: 1fr;
      max-width: 440px;
      gap: 32px;
    }
    .login-hero {
      text-align: center;
      padding-right: 0;
    }
    .brand-header {
      justify-content: center;
    }
    .hero-desc {
      margin-left: auto;
      margin-right: auto;
    }
    .features-grid {
      grid-template-columns: 1fr;
    }
  }
CSS;

require_once __DIR__ . '/includes/header.php';
?>
  <div class="login-container">
    <!-- Columna Izquierda: Presentación Corporativa -->
    <div class="login-hero">
      <div class="hero-pill">
        <span class="dot"></span>
        <span>Plataforma Operativa & Administrativa</span>
      </div>

      <div class="brand-header">
        <div class="brand-logo-box">💻</div>
        <div>
          <div class="brand-name">Tech Store Guatemala</div>
          <div class="brand-subtitle">Cómputo, Hardware & Comercio Electrónico</div>
        </div>
      </div>

      <h1 class="hero-title">Gestión integral de pedidos,<br><span>en tiempo real.</span></h1>
      <p class="hero-desc">
        Control unificado desde la recepción del pedido en línea o contact center hasta la preparación en bodega, despacho en ruta y analítica telemática con Prometheus y Grafana.
      </p>

      <div class="features-grid" aria-label="Módulos del sistema">
        <div class="feature-item">
          <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <span>Contact Center</span>
        </div>
        <div class="feature-item">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          <span>Bodega & Picking</span>
        </div>
        <div class="feature-item">
          <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          <span>Rutas & Logística</span>
        </div>
        <div class="feature-item">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <span>Panel Comercial</span>
        </div>
      </div>

      <a href="tienda" class="store-link-back">
        <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Ir a la tienda virtual de clientes</span>
      </a>
    </div>

    <!-- Columna Derecha: Tarjeta de Acceso -->
    <div class="login-card">
      <div class="card-top">
        <span class="card-eyebrow">Acceso administrativo</span>
        <span class="card-badge">Grupo #3</span>
      </div>

      <h2 class="card-title">Iniciar sesión</h2>
      <p class="card-desc">Ingresa tus credenciales asignadas para acceder al panel.</p>

      <form id="login-form" method="POST" action="login" autocomplete="off" novalidate>
        <div class="form-group">
          <label for="user">Usuario</label>
          <div class="input-wrapper">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input id="user" name="user" type="text" inputmode="text" autocapitalize="none" spellcheck="false" placeholder="Ingresa tu usuario" value="<?= htmlspecialchars($_POST['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label for="pass">Contraseña</label>
          <div class="input-wrapper">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input id="pass" name="pass" type="password" placeholder="••••••••••••" required>
            <button type="button" class="btn-toggle-eye" id="toggle" aria-label="Mostrar u ocultar contraseña" title="Mostrar u ocultar contraseña">
              <svg id="eye-open" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="eye-off" viewBox="0 0 24 24" style="display:none"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.16 4.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div id="caps" class="caps-warning">Bloq Mayús está activado.</div>
        </div>

        <div id="err" class="alert-box <?= $loginError ? 'show' : '' ?>" role="alert">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span id="err-text"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <button class="btn-submit" id="submit-btn" type="submit">
          <span>Entrar al sistema</span>
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>

      <!-- Accesos Rápidos para Demostración -->
      <div class="quick-access-box">
        <div class="quick-access-label">
          <span>Acceso rápido (1 clic para demo):</span>
        </div>
        <div class="quick-access-pills">
          <button type="button" class="quick-pill" data-u="admin" data-p="Admin@2026">⚙️ Admin General</button>
          <button type="button" class="quick-pill" data-u="carlos.lopez" data-p="Dev1@Grupo3">🎓 Carlos López (G3)</button>
          <button type="button" class="quick-pill" data-u="victor.castillo" data-p="Dba2@Grupo3">🎓 Victor Castillo (G3)</button>
          <button type="button" class="quick-pill" data-u="oscar.ortiz" data-p="Dev3@Grupo3">🎓 Oscar Ortíz (G3)</button>
          <button type="button" class="quick-pill" data-u="emmanuel.ajsivinac" data-p="Qa4@Grupo3">🎓 Emmanuel Ajsivinac (G3)</button>
          <button type="button" class="quick-pill" data-u="df_admin" data-p="Fenix@Admin26">👑 Administrador</button>
          <button type="button" class="quick-pill" data-u="df_contact" data-p="Fenix@Contact26">📞 Contact Center</button>
          <button type="button" class="quick-pill" data-u="df_bodega" data-p="Fenix@Bodega26">📦 Bodega</button>
          <button type="button" class="quick-pill" data-u="df_logistica" data-p="Fenix@Logis26">🚚 Logística</button>
          <button type="button" class="quick-pill" data-u="df_comercial" data-p="Fenix@Comercial26">📈 Comercial</button>
        </div>
      </div>

      <div class="card-footer">
        <span>© <?= date('Y') ?> Tech Store Guatemala</span>
        <a href="tienda" style="color:#2563eb;font-weight:600;text-decoration:none">Ver Tienda →</a>
      </div>
    </div>
  </div>
</div>

<script>
  if (getSession() && !location.search.includes("logout")) {
    window.location.replace("inicio");
  }

  const form = document.getElementById("login-form");
  const user = document.getElementById("user");
  const pass = document.getElementById("pass");
  const err = document.getElementById("err");
  const errText = document.getElementById("err-text");
  const caps = document.getElementById("caps");
  const toggle = document.getElementById("toggle");
  const eyeOpen = document.getElementById("eye-open");
  const eyeOff = document.getElementById("eye-off");
  const submitBtn = document.getElementById("submit-btn");

  toggle.addEventListener("click", () => {
    const visible = pass.type === "text";
    pass.type = visible ? "password" : "text";
    eyeOpen.style.display = visible ? "" : "none";
    eyeOff.style.display = visible ? "none" : "";
    pass.focus();
  });

  const checkCaps = (e) => {
    if (typeof e.getModifierState === "function") {
      caps.classList.toggle("show", e.getModifierState("CapsLock"));
    }
  };
  pass.addEventListener("keydown", checkCaps);
  pass.addEventListener("keyup", checkCaps);

  async function ejecutarIngreso(uVal, pVal) {
    err.classList.remove("show");
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>Verificando credenciales...</span>';

    try {
      const res = await login(uVal, pVal);
      if (!res || !res.ok) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span>Entrar al sistema</span><svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
        errText.textContent = (res && res.error) ? res.error : "Usuario o contraseña incorrectos.";
        void err.offsetWidth;
        err.classList.add("show");
        pass.value = "";
        pass.focus();
        return;
      }

      submitBtn.innerHTML = '<span>¡Bienvenido! Entrando...</span>';
      window.location.href = "inicio";
    } catch (errEx) {
      // Si la API fetch da error de red, fallback al formulario POST nativo
      form.submit();
    }
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    ejecutarIngreso(user.value, pass.value);
  });

  document.querySelectorAll("[data-u]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      user.value = btn.dataset.u;
      pass.value = btn.dataset.p;
      ejecutarIngreso(btn.dataset.u, btn.dataset.p);
    });
  });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
