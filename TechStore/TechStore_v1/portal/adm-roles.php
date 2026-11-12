<?php
$pageTitle  = 'Administración · Roles y permisos';
$pageStyles = <<<'CSS'
  .layout { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 20px; align-items: start; }
  .rlist { display: flex; flex-direction: column; gap: 8px; }
  .ritem {
    display: flex; flex-direction: column; gap: 4px; padding: 14px 16px; border-radius: 14px;
    border: 1px solid var(--border); background: #f8fafc; cursor: pointer; text-align: left;
  }
  .ritem:hover { background: #f1f5f9; }
  .ritem.on { border-color: #93c5fd; background: #eff6ff; }
  .ritem .top-r { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
  .ritem b { font-size: 15px; }
  .ritem span { font-size: 12.5px; color: var(--muted); }

  .mod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; }
  .mod-chip {
    display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 12px;
    border: 1px solid var(--border); background: #f8fafc; cursor: pointer;
  }
  .mod-chip:has(input:checked) { border-color: #93c5fd; background: #eff6ff; }
  .perm-group { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
  .perm-group h4 { margin: 0 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .9px; color: var(--orange-soft); display: flex; justify-content: space-between; }
  .perm-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px 14px; }
  @media (max-width: 960px) { .layout { grid-template-columns: 1fr; } }
CSS;
require_once __DIR__ . '/includes/header.php';
?>
  <main class="wrap">
    <div class="page-title">
      <div>
        <div class="eyebrow">Administración</div>
        <h1>Roles y matriz de permisos</h1>
        <p>Define a qué módulos y a qué acciones específicas tiene acceso cada perfil del sistema.</p>
      </div>
      <div class="actions">
        <a class="btn" href="admin"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/></svg>Administración</a>
        <button class="btn primary" type="button" id="btn-nuevo"><svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>Nuevo rol</button>
      </div>
    </div>

    <div class="layout">
      <aside class="panel"><div class="panel-h"><h2>Perfiles</h2></div><div class="panel-b"><div class="rlist" id="rlist"></div></div></aside>
      <section class="panel" id="editor"></section>
    </div>
  </main>

  <script>
    const s = requireModulo("admin");
    if (s && !can("admin.roles.ver")) location.replace("admin");
    let actual = "Administrador";

    function renderLista() {
      const roles = getRoles();
      const users = Object.values(getUsers());
      if (!roles[actual]) actual = Object.keys(roles)[0];
      document.getElementById("rlist").innerHTML = Object.entries(roles).map(([nombre, r]) => {
        const cnt = users.filter((u) => u.role === nombre).length;
        return `<button type="button" class="ritem ${nombre === actual ? "on" : ""}" data-rol="${esc(nombre)}">` +
          `<div class="top-r"><b>${esc(nombre)}</b><span class="badge plain" style="--c:${r.base ? "#22b8cf" : "#a78bfa"}">${r.base ? "Base" : "Personalizado"}</span></div>` +
          `<span>${(r.modulos || []).length} módulos · ${(r.permisos || []).length} permisos · ${cnt} usuario${cnt === 1 ? "" : "s"}</span>` +
        `</button>`;
      }).join("");
    }

    function renderEditor() {
      const roles = getRoles();
      const r = roles[actual];
      if (!r) return;
      const esAdminBase = actual === "Administrador";
      const puedeEditar = can("admin.roles.editar") && !esAdminBase;
      const puedeDel = can("admin.roles.eliminar") && !r.base;

      const modsHTML = Object.values(MODULOS).map((m) => {
        const on = (r.modulos || []).includes(m.id);
        return `<label class="mod-chip"><input type="checkbox" data-mod="${m.id}" ${on ? "checked" : ""} ${puedeEditar ? "" : "disabled"}><div><b>${esc(m.nombre)}</b></div></label>`;
      }).join("");

      const permsHTML = Object.entries(PERMISOS).map(([modId, lista]) => {
        const mod = MODULOS[modId];
        return `<div class="perm-group">` +
          `<h4><span>${esc(mod.nombre)}</span>` +
          (puedeEditar ? `<button class="btn sm ghost" type="button" data-all="${modId}">Marcar todo</button>` : "") +
          `</h4>` +
          `<div class="perm-list">` +
            lista.map((p) => {
              const on = (r.permisos || []).includes(p.id);
              return `<label class="check"><input type="checkbox" data-perm="${p.id}" ${on ? "checked" : ""} ${puedeEditar ? "" : "disabled"}><span>${esc(p.nombre)}</span></label>`;
            }).join("") +
          `</div></div>`;
      }).join("");

      document.getElementById("editor").innerHTML =
        `<div class="panel-h"><h2>${esc(actual)}</h2><span class="sub">${esAdminBase ? "El rol Administrador conserva todos los permisos por seguridad" : "Marca los módulos y permisos y pulsa Guardar"}</span></div>` +
        `<div class="panel-b">` +
          `<div class="field" style="margin-bottom:16px"><label>Descripción del perfil</label><input class="inp" id="r-desc" value="${esc(r.descripcion || "")}" ${puedeEditar ? "" : "disabled"}></div>` +
          `<h3 style="margin:0 0 10px;font-size:15px">1. Módulos visibles en el portal</h3>` +
          `<div class="mod-grid">${modsHTML}</div>` +
          `<h3 style="margin:22px 0 4px;font-size:15px">2. Permisos por módulo y submódulo</h3>` +
          permsHTML +
          `<div style="display:flex;justify-content:space-between;gap:10px;margin-top:22px;padding-top:16px;border-top:1px solid var(--border)">` +
            `<div>${puedeDel ? `<button class="btn danger" type="button" id="r-del">Eliminar rol</button>` : ""}</div>` +
            `<div>${puedeEditar ? `<button class="btn primary" type="button" id="r-save">Guardar cambios del rol</button>` : ""}</div>` +
          `</div>` +
        `</div>`;
    }

    function modalNuevoRol() {
      const ov = document.createElement("div");
      ov.className = "overlay";
      ov.innerHTML =
        `<div class="modal" style="max-width:440px" role="dialog" aria-modal="true">` +
          `<div class="modal-h"><h3>Nuevo rol</h3><button class="btn sm icon ghost" type="button" data-x>✕</button></div>` +
          `<div class="modal-b">` +
            `<div class="field" style="margin-bottom:12px"><label>Nombre del rol *</label><input class="inp" id="nr-nom" placeholder="Ej. Supervisor de Operaciones"></div>` +
            `<div class="field"><label>Descripción</label><input class="inp" id="nr-desc" placeholder="Responsabilidades principales"></div>` +
            `<div class="err-msg" id="nr-err" style="margin-top:8px"></div>` +
          `</div>` +
          `<div class="modal-f"><button class="btn" type="button" data-x>Cancelar</button><button class="btn primary" type="button" id="nr-ok">Crear rol</button></div>` +
        `</div>`;
      document.body.appendChild(ov);
      ov.addEventListener("click", (e) => { if (e.target === ov || e.target.closest("[data-x]")) ov.remove(); });
      ov.querySelector("#nr-ok").addEventListener("click", () => {
        const nom = ov.querySelector("#nr-nom").value.trim();
        const desc = ov.querySelector("#nr-desc").value.trim();
        const roles = getRoles();
        if (nom.length < 3) { ov.querySelector("#nr-err").textContent = "Escribe un nombre de al menos 3 caracteres."; return; }
        if (roles[nom]) { ov.querySelector("#nr-err").textContent = "Ya existe un rol con ese nombre."; return; }
        roles[nom] = { descripcion: desc || "Perfil personalizado", base: false, modulos: [], permisos: [] };
        saveRoles(roles);
        audit("rol.crear", `Creado rol «${nom}»`);
        actual = nom;
        ov.remove();
        renderLista();
        renderEditor();
        toast(`Rol «${nom}» creado. Configura ahora sus permisos.`, "ok");
      });
    }

    if (s) {
      pintarShell(s, [{ t: "Administración", href: "admin" }, { t: "Roles y permisos" }]);
      if (!can("admin.roles.crear")) document.getElementById("btn-nuevo").hidden = true;
      renderLista();
      renderEditor();

      document.getElementById("btn-nuevo").addEventListener("click", modalNuevoRol);
      document.getElementById("rlist").addEventListener("click", (e) => {
        const b = e.target.closest("[data-rol]");
        if (!b) return;
        actual = b.dataset.rol;
        renderLista();
        renderEditor();
      });

      document.getElementById("editor").addEventListener("click", async (e) => {
        const allBtn = e.target.closest("[data-all]");
        if (allBtn) {
          const modId = allBtn.dataset.all;
          const ids = (PERMISOS[modId] || []).map((p) => p.id);
          const checks = Array.from(document.querySelectorAll("#editor [data-perm]")).filter((c) => ids.includes(c.dataset.perm));
          const todos = checks.every((c) => c.checked);
          checks.forEach((c) => (c.checked = !todos));
          const modCheck = document.querySelector(`#editor [data-mod="${modId}"]`);
          if (modCheck && !todos) modCheck.checked = true;
          return;
        }
        if (e.target.id === "r-save") {
          const roles = getRoles();
          const modulos = Array.from(document.querySelectorAll("#editor [data-mod]:checked")).map((c) => c.dataset.mod);
          const permisos = Array.from(document.querySelectorAll("#editor [data-perm]:checked")).map((c) => c.dataset.perm);
          const descripcion = document.getElementById("r-desc").value.trim();
          roles[actual] = { ...roles[actual], descripcion, modulos, permisos };
          saveRoles(roles);
          audit("rol.editar", `${actual} (${modulos.length} módulos · ${permisos.length} permisos)`);
          renderLista();
          toast("Permisos del rol actualizados", "ok");
        }
        if (e.target.id === "r-del") {
          const enUso = Object.values(getUsers()).some((u) => u.role === actual);
          if (enUso) { toast("No puedes eliminar un rol asignado a usuarios activos.", "bad"); return; }
          if (await confirmar("Eliminar rol", `¿Seguro que deseas eliminar el rol «${actual}»?`, "Eliminar", true)) {
            const roles = getRoles();
            delete roles[actual];
            saveRoles(roles);
            audit("rol.eliminar", `Eliminado rol «${actual}»`);
            actual = "Administrador";
            renderLista();
            renderEditor();
            toast("Rol eliminado", "ok");
          }
        }
      });
    }
  </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

