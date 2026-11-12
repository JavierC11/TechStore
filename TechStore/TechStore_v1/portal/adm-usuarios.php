<?php
$pageTitle  = 'Administración · Usuarios';
$pageStyles = <<<'CSS'
  .bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
  .bar .search { flex: 1 1 280px; }
  .bar select.inp { width: auto; min-width: 180px; }
  .u-cell { display: flex; align-items: center; gap: 12px; }
  .u-cell .avatar { width: 38px; height: 38px; border-radius: 12px; font-size: 13px; }
  .row-acts { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Administración</div>
        <h1>Usuarios y cuentas</h1>
        <p>Crea cuentas, asigna roles, activa o desactiva accesos y restablece contraseñas.</p>
      </div>
      <div class="actions">
        <a class="btn" href="admin"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Administración</a>
        <button class="btn primary" type="button" id="btn-nuevo"><svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Nuevo usuario</button>
      </div>
    </div>

    <section class="panel">
      <div class="panel-b">
        <div class="bar">
          <div class="search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input class="inp" id="q" type="search" placeholder="Buscar por nombre, usuario o correo…" autocomplete="off">
          </div>
          <select class="inp" id="f-rol"><option value="">Todos los roles</option></select>
          <select class="inp" id="f-est">
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol asignado</th>
                <th>Módulos con acceso</th>
                <th>Estado</th>
                <th class="num">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <script>
    const s = requireModulo("admin");
    if (s && !can("admin.usuarios.ver")) location.replace("admin");

    function poblarFiltroRoles() {
      const sel = document.getElementById("f-rol");
      const val = sel.value;
      sel.innerHTML = `<option value="">Todos los roles</option>` +
        Object.keys(getRoles()).map((r) => `<option ${r === val ? "selected" : ""}>${esc(r)}</option>`).join("");
    }

    function render() {
      poblarFiltroRoles();
      const q = norm(document.getElementById("q").value).trim();
      const rol = document.getElementById("f-rol").value;
      const est = document.getElementById("f-est").value;
      const roles = getRoles();
      const lista = Object.entries(getUsers())
        .map(([username, u]) => ({ username, ...u }))
        .filter((u) => {
          if (rol && u.role !== rol) return false;
          if (est !== "" && String(u.active !== false ? 1 : 0) !== est) return false;
          if (q && !norm(`${u.name} ${u.username} ${u.email} ${u.role}`).includes(q)) return false;
          return true;
        });

      const tb = document.getElementById("tbody");
      const puedeEditar = can("admin.usuarios.editar");
      const puedePass = can("admin.usuarios.password");
      const puedeDel = can("admin.usuarios.eliminar");

      tb.innerHTML = lista.map((u) => {
        const r = roles[u.role] || { modulos: [], permisos: [] };
        const mods = (r.modulos || []).map((m) => MODULOS[m]?.nombre || m).join(", ") || "Ninguno";
        const activo = u.active !== false;
        const esYo = u.username === s.user;
        return `<tr>` +
          `<td><div class="u-cell"><span class="avatar">${esc(iniciales(u.name))}</span><div><div class="strong">${esc(u.name)} ${esYo ? '<span class="badge plain" style="--c:#22b8cf">Tú</span>' : ""}</div><div class="sub">${esc(u.email || "Sin correo")}</div></div></div></td>` +
          `<td><code>${esc(u.username)}</code></td>` +
          `<td><span class="badge plain" style="--c:#f97316">${esc(u.role)}</span></td>` +
          `<td><span class="sub">${esc(mods)}</span></td>` +
          `<td><span class="badge" style="--c:${activo ? "#2fc986" : "#ef5b66"}">${activo ? "Activo" : "Inactivo"}</span></td>` +
          `<td><div class="row-acts">` +
            (puedeEditar ? `<button class="btn sm" type="button" data-act="edit" data-u="${esc(u.username)}">Editar</button>` : "") +
            (puedePass ? `<button class="btn sm" type="button" data-act="pass" data-u="${esc(u.username)}">Contraseña</button>` : "") +
            (puedeEditar && !esYo ? `<button class="btn sm" type="button" data-act="toggle" data-u="${esc(u.username)}">${activo ? "Desactivar" : "Activar"}</button>` : "") +
            (puedeDel && !u.base && !esYo ? `<button class="btn sm danger ghost" type="button" data-act="del" data-u="${esc(u.username)}">Eliminar</button>` : "") +
          `</div></td>` +
        `</tr>`;
      }).join("");
    }

    function modalUsuario(usernameEditar) {
      const users = getUsers();
      const u = usernameEditar ? users[usernameEditar] : null;
      const roles = Object.keys(getRoles());
      const ov = document.createElement("div");
      ov.className = "overlay";
      ov.innerHTML =
        `<div class="modal" role="dialog" aria-modal="true">` +
          `<div class="modal-h"><h3>${u ? "Editar usuario" : "Nuevo usuario"}</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
          `<div class="modal-b"><div class="grid-form">` +
            `<div class="field c6"><label>Usuario *</label><input class="inp" id="m-user" value="${esc(usernameEditar || "")}" ${u ? "disabled" : ""} placeholder="ej. df_operador"></div>` +
            `<div class="field c6"><label>Rol *</label><select class="inp" id="m-role">${roles.map((r) => `<option ${u && u.role === r ? "selected" : ""}>${esc(r)}</option>`).join("")}</select></div>` +
            `<div class="field c12"><label>Nombre completo *</label><input class="inp" id="m-name" value="${esc(u ? u.name : "")}"></div>` +
            `<div class="field c12"><label>Correo electrónico *</label><input class="inp" id="m-mail" type="email" value="${esc(u ? u.email : "")}"></div>` +
            (u ? "" : `<div class="field c12"><label>Contraseña inicial * <span class="sub">(8+ caracteres)</span></label><input class="inp" id="m-pass" type="password" value="Fenix@2026"></div>`) +
          `</div><div class="err-msg" id="m-err" style="margin-top:10px"></div></div>` +
          `<div class="modal-f"><button class="btn" type="button" data-x>Cancelar</button><button class="btn primary" type="button" id="m-ok">Guardar</button></div>` +
        `</div>`;
      document.body.appendChild(ov);
      ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });
      ov.querySelector("#m-ok").addEventListener("click", async () => {
        const un = (u ? usernameEditar : ov.querySelector("#m-user").value).trim().toLowerCase();
        const name = ov.querySelector("#m-name").value.trim();
        const email = ov.querySelector("#m-mail").value.trim();
        const role = ov.querySelector("#m-role").value;
        const err = ov.querySelector("#m-err");
        if (!/^[a-z0-9._-]{3,28}$/.test(un)) { err.textContent = "El usuario debe tener entre 3 y 28 caracteres (letras, números, punto o guion)."; return; }
        if (!u && users[un]) { err.textContent = "Ya existe una cuenta con ese nombre de usuario."; return; }
        if (name.length < 3) { err.textContent = "Escribe el nombre completo."; return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { err.textContent = "Escribe un correo válido."; return; }
        if (!u) {
          const pw = ov.querySelector("#m-pass").value;
          if (pw.length < 8) { err.textContent = "La contraseña debe tener al menos 8 caracteres."; return; }
          users[un] = { hash: await sha256(pw), name, email, role, active: true, base: false };
          saveUsers(users);
          audit("usuario.crear", `Creado ${un} (${role})`);
          toast(`Usuario ${un} creado`, "ok");
        } else {
          users[un] = { ...users[un], name, email, role };
          saveUsers(users);
          audit("usuario.editar", `Actualizado ${un} (${role})`);
          toast("Cambios guardados", "ok");
        }
        ov.remove();
        render();
      });
    }

    function modalPassword(username) {
      const users = getUsers();
      const u = users[username];
      if (!u) return;
      const ov = document.createElement("div");
      ov.className = "overlay";
      ov.innerHTML =
        `<div class="modal" style="max-width:440px" role="dialog" aria-modal="true">` +
          `<div class="modal-h"><h3>Restablecer contraseña</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
          `<div class="modal-b">` +
            `<p style="margin:0 0 12px;color:var(--muted);font-size:14px">Cuenta: <b style="color:#0f172a">${esc(u.name)}</b> (<code>${esc(username)}</code>)</p>` +
            `<div class="field"><label>Nueva contraseña *</label><input class="inp" id="p-new" type="password" placeholder="Mínimo 8 caracteres"></div>` +
            `<div class="err-msg" id="p-err" style="margin-top:8px"></div>` +
          `</div>` +
          `<div class="modal-f"><button class="btn" type="button" data-x>Cancelar</button><button class="btn primary" type="button" id="p-ok">Actualizar contraseña</button></div>` +
        `</div>`;
      document.body.appendChild(ov);
      ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });
      ov.querySelector("#p-ok").addEventListener("click", async () => {
        const pw = ov.querySelector("#p-new").value;
        if (pw.length < 8) { ov.querySelector("#p-err").textContent = "Escribe al menos 8 caracteres."; return; }
        users[username].hash = await sha256(pw);
        saveUsers(users);
        audit("usuario.password", `Contraseña restablecida para ${username}`);
        ov.remove();
        toast("Contraseña actualizada", "ok");
      });
    }

    if (s) {
      pintarShell(s, [{ t: "Administración", href: "admin" }, { t: "Usuarios y cuentas" }]);
      if (!can("admin.usuarios.crear")) document.getElementById("btn-nuevo").hidden = true;
      render();

      document.getElementById("q").addEventListener("input", render);
      document.getElementById("f-rol").addEventListener("change", render);
      document.getElementById("f-est").addEventListener("change", render);
      document.getElementById("btn-nuevo").addEventListener("click", () => modalUsuario(null));

      document.getElementById("tbody").addEventListener("click", async (e) => {
        const b = e.target.closest("[data-act]");
        if (!b) return;
        const un = b.dataset.u, act = b.dataset.act;
        const users = getUsers();
        if (act === "edit") modalUsuario(un);
        else if (act === "pass") modalPassword(un);
        else if (act === "toggle") {
          users[un].active = users[un].active === false;
          saveUsers(users);
          audit("usuario.estado", `${un} → ${users[un].active ? "Activo" : "Inactivo"}`);
          render();
          toast(`Cuenta ${users[un].active ? "activada" : "desactivada"}`, "ok");
        } else if (act === "del") {
          if (await confirmar("Eliminar usuario", `¿Seguro que deseas eliminar la cuenta «${un}»?`, "Eliminar", true)) {
            delete users[un];
            saveUsers(users);
            audit("usuario.eliminar", `Eliminado ${un}`);
            render();
            toast("Usuario eliminado", "ok");
          }
        }
      });

      if (new URLSearchParams(location.search).get("nuevo") === "1" && can("admin.usuarios.crear")) {
        modalUsuario(null);
      }
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

