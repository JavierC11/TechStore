/* ==========================================================================
   DigyFenix | Pulso — Sesión, usuarios, roles, permisos y bitácora
   Sincronizado en tiempo real con MySQL (config/db.php + api.php).
   ========================================================================== */

const DB_SNAP = window.__PULSO_DB__ || {};
const DB_CFG  = (DB_SNAP && DB_SNAP.config) ? DB_SNAP.config : {};

const APP = {
  empresa: DB_CFG.empresa || "Tech Store",
  producto: DB_CFG.producto || "Portal",
  lema: DB_CFG.lema || "Tu tienda de cómputo, componentes y tecnología",
  version: "3.0 MySQL + Prometheus",
};

// Puente de sincronización asíncrona con MySQL (api.php)
window.pulsoSync = function (key, value) {
  try {
    fetch("api.php?action=sync", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "sync", key: key, value: value }),
    }).catch(() => {});
  } catch (e) {}
};

window.pulsoAudit = function (user, auditAction, detail) {
  try {
    fetch("api.php?action=audit", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "audit", user: user, auditAction: auditAction, detail: detail }),
    }).catch(() => {});
  } catch (e) {}
};

window.pulsoTrack = function (tipo, opts) {
  try {
    const payload = JSON.stringify({ action: "track_event", tipo, ...(opts || {}) });
    if (opts && opts.beacon && navigator.sendBeacon) {
      const blob = new Blob([payload], { type: "application/json" });
      navigator.sendBeacon("api.php?action=track_event", blob);
      return Promise.resolve({ ok: true });
    }
    return fetch("api.php?action=track_event", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: payload,
      keepalive: !!(opts && opts.beacon),
    }).then((r) => r.json()).catch(() => null);
  } catch (e) {
    return Promise.resolve(null);
  }
};

/* ---------- Módulos principales del sistema ---------- */
const MODULOS = {
  admin:          { id: "admin",          nombre: "Administración", href: "admin",          resumen: "Catálogo de productos, personalización de página, usuarios, roles y bitácora." },
  contact_center: { id: "contact_center", nombre: "Contact Center", href: "contact-center", resumen: "Atención al cliente, creación y aprobación de pedidos." },
  bodega:         { id: "bodega",         nombre: "Bodega",         href: "bodega",         resumen: "Cargas asignadas, picking, empaque e inventario." },
  logistica:      { id: "logistica",      nombre: "Logística",      href: "logistica",      resumen: "Planificación de cargas, rutas de entrega y flota." },
  comercial:      { id: "comercial",      nombre: "Comercial",      href: "comercial",      resumen: "Ventas, clientes e indicadores clave del negocio." },
};

/* ---------- Catálogo de permisos granulares por módulo ---------- */
const PERMISOS = {
  admin: [
    { id: "admin.usuarios.ver",      nombre: "Ver usuarios" },
    { id: "admin.usuarios.crear",    nombre: "Crear usuarios" },
    { id: "admin.usuarios.editar",   nombre: "Editar usuarios y activar/desactivar" },
    { id: "admin.usuarios.eliminar", nombre: "Eliminar usuarios" },
    { id: "admin.usuarios.password", nombre: "Restablecer contraseñas" },
    { id: "admin.roles.ver",         nombre: "Ver roles y permisos" },
    { id: "admin.roles.crear",       nombre: "Crear nuevos roles" },
    { id: "admin.roles.editar",      nombre: "Editar permisos de roles" },
    { id: "admin.roles.eliminar",    nombre: "Eliminar roles personalizados" },
    { id: "admin.bitacora.ver",      nombre: "Consultar bitácora de accesos y cambios" },
    { id: "admin.sistema.reset",     nombre: "Restaurar datos de demostración" },
  ],
  contact_center: [
    { id: "cc.pedidos.ver",        nombre: "Ver lista y detalle de pedidos" },
    { id: "cc.pedidos.crear",      nombre: "Crear pedidos nuevos" },
    { id: "cc.pedidos.editar",     nombre: "Editar pedidos existentes" },
    { id: "cc.pedidos.descuento",  nombre: "Aplicar descuentos en líneas" },
    { id: "cc.pedidos.cancelar",   nombre: "Cancelar pedidos" },
    { id: "cc.pedidos.exportar",   nombre: "Exportar pedidos (CSV / Excel)" },
    { id: "cc.clientes.crear",     nombre: "Registrar clientes nuevos" },
    { id: "cc.direcciones.editar", nombre: "Modificar direcciones de entrega y facturación" },
    { id: "cc.web.aprobar",        nombre: "Aprobar o rechazar pedidos de la tienda web" },
  ],
  bodega: [
    { id: "bd.cargas.ver",          nombre: "Ver cargas y pedidos asignados" },
    { id: "bd.cargas.confirmar",    nombre: "Confirmar recepción de carga" },
    { id: "bd.picking.ver",         nombre: "Ver listas de picking" },
    { id: "bd.picking.ejecutar",    nombre: "Marcar productos recolectados y empacar" },
    { id: "bd.inventario.ver",      nombre: "Consultar inventario y kardex" },
    { id: "bd.inventario.ajustar",  nombre: "Registrar entradas y ajustes de stock" },
    { id: "bd.inventario.exportar", nombre: "Exportar inventario (CSV / Excel)" },
  ],
  logistica: [
    { id: "log.plan.ver",        nombre: "Ver planificación de cargas" },
    { id: "log.plan.asignar",    nombre: "Asignar pedidos a rutas y vehículos" },
    { id: "log.rutas.ver",       nombre: "Ver rutas de entrega" },
    { id: "log.rutas.crear",     nombre: "Crear y editar rutas" },
    { id: "log.rutas.despachar", nombre: "Despachar rutas a reparto" },
    { id: "log.rutas.entregar",  nombre: "Confirmar entregas e incidencias" },
    { id: "log.flota.ver",       nombre: "Ver flota y pilotos" },
    { id: "log.flota.gestionar", nombre: "Crear y editar vehículos y pilotos" },
    { id: "log.exportar",        nombre: "Exportar manifiestos y rutas" },
  ],
  comercial: [
    { id: "cm.tablero.ver",   nombre: "Ver tablero ejecutivo de KPIs" },
    { id: "cm.metas.editar",  nombre: "Configurar metas comerciales" },
    { id: "cm.ventas.ver",    nombre: "Ver análisis de ventas, productos y clientes" },
    { id: "cm.operacion.ver", nombre: "Ver indicadores operativos y de cumplimiento" },
    { id: "cm.exportar",      nombre: "Exportar reportes comerciales" },
  ],
};

const TODOS_LOS_PERMISOS = Object.values(PERMISOS).flat().map((p) => p.id);

/* ---------- Submódulos por área (tarjetas del panel de cada módulo) ---------- */
const SUBMODULOS = {
  admin: [
    {
      id: "adm_productos",
      nombre: "Catálogo y productos (MySQL)",
      href: "adm-productos",
      permiso: "admin.usuarios.ver",
      etiqueta: "E-commerce & Stock",
      desc: "Sube productos con fotografía, edita precios, stock, dimensiones (kg/m³), SKU y categorías directamente en la base de datos.",
      icono: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7L12 12l8.7-5"/><path d="M12 22V12"/>',
    },
    {
      id: "adm_pagina",
      nombre: "Personalizar página y parámetros",
      href: "adm-pagina",
      permiso: "admin.usuarios.ver",
      etiqueta: "CMS & Tienda",
      desc: "Modifica textos de portada, cintillo promocional, umbral de envío gratis, IVA, colores corporativos y pie de página.",
      icono: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
    },
    {
      id: "adm_usuarios",
      nombre: "Usuarios y cuentas",
      href: "adm-usuarios",
      permiso: "admin.usuarios.ver",
      etiqueta: "Seguridad",
      desc: "Crea cuentas, asigna roles, activa o desactiva accesos y restablece contraseñas.",
      icono: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    },
    {
      id: "adm_roles",
      nombre: "Roles y permisos",
      href: "adm-roles",
      permiso: "admin.roles.ver",
      etiqueta: "Control de acceso",
      desc: "Define qué módulos y qué acciones específicas puede ejecutar cada perfil del sistema.",
      icono: '<path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/><path d="M9 12l2 2 4-4"/>',
    },
    {
      id: "adm_bitacora",
      nombre: "Bitácora y estado del sistema",
      href: "adm-bitacora",
      permiso: "admin.bitacora.ver",
      etiqueta: "Auditoría",
      desc: "Revisa inicios de sesión, cambios administrativos y el estado de los datos del sistema.",
      icono: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h4"/>',
    },
  ],
  contact_center: [
    {
      id: "cc_pedidos",
      nombre: "Gestión de pedidos",
      href: "cc-pedidos",
      permiso: "cc.pedidos.ver",
      etiqueta: "Operación diaria",
      desc: "Consulta, filtra, crea, edita y exporta los pedidos recibidos por todos los canales.",
      icono: '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h4"/>',
    },
    {
      id: "cc_nuevo",
      nombre: "Levantar pedido telefónico",
      href: "cc-pedido",
      permiso: "cc.pedidos.crear",
      etiqueta: "Captura rápida",
      desc: "Selecciona o crea al cliente, confirma dirección y facturación y agrega productos validando stock.",
      icono: '<path d="M12 5v14"/><path d="M5 12h14"/><circle cx="12" cy="12" r="9"/>',
    },
    {
      id: "cc_aprobacion",
      nombre: "Pedidos web por aprobar",
      href: "cc-aprobacion",
      permiso: "cc.web.aprobar",
      etiqueta: "Tienda en línea",
      desc: "Revisa los pedidos ingresados por clientes en la tienda web, valida datos y apruébalos para despacho.",
      icono: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
    },
  ],
  bodega: [
    {
      id: "bd_cargas",
      nombre: "Cargas y pedidos asignados",
      href: "bd-cargas",
      permiso: "bd.cargas.ver",
      etiqueta: "Recepción",
      desc: "Pedidos aprobados que entran a cola de bodega, ordenados por prioridad, fecha de entrega y ruta.",
      icono: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7L12 12l8.7-5"/><path d="M12 22V12"/>',
    },
    {
      id: "bd_picking",
      nombre: "Picking y empaque",
      href: "bd-picking",
      permiso: "bd.picking.ver",
      etiqueta: "Preparación",
      desc: "Lista de recolección por ubicación de pasillo/estante, verificación de unidades y cierre de empaque.",
      icono: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    },
    {
      id: "bd_inventario",
      nombre: "Inventario y entradas de mercadería",
      href: "bd-inventario",
      permiso: "bd.inventario.ver",
      etiqueta: "Control de stock",
      desc: "Existencias actuales, alertas de reposición, ingresos de compra, ajustes y kardex de movimientos.",
      icono: '<path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="7"/><rect x="12" y="6" width="3" height="11"/><rect x="17" y="13" width="3" height="4"/>',
    },
  ],
  logistica: [
    {
      id: "log_plan",
      nombre: "Planificación de cargas",
      href: "log-planificacion",
      permiso: "log.plan.ver",
      etiqueta: "Cubicaje y zonas",
      desc: "Agrupa pedidos listos por departamento/zona, calcula peso y volumen y asígnalos a un vehículo.",
      icono: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M9 4v16"/>',
    },
    {
      id: "log_rutas",
      nombre: "Rutas y despacho",
      href: "log-rutas",
      permiso: "log.rutas.ver",
      etiqueta: "En calle",
      desc: "Secuencia de paradas, hoja de ruta imprimible, salida a reparto y confirmación de entregas.",
      icono: '<circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M12 19h4.5a3.5 3.5 0 0 0 0-7h-8a3.5 3.5 0 0 1 0-7H12"/>',
    },
    {
      id: "log_flota",
      nombre: "Flota y pilotos",
      href: "log-flota",
      permiso: "log.flota.ver",
      etiqueta: "Recursos",
      desc: "Vehículos disponibles, capacidad en kg y m³, pilotos asignados y estado operativo.",
      icono: '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    },
  ],
  comercial: [
    {
      id: "cm_tablero",
      nombre: "Tablero ejecutivo de KPIs",
      href: "cm-tablero",
      permiso: "cm.tablero.ver",
      etiqueta: "Dirección",
      desc: "Indicadores clave frente a meta: ingresos, ticket promedio, conversión web, OTIF y nivel de servicio.",
      icono: '<path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>',
    },
    {
      id: "cm_ventas",
      nombre: "Análisis de ventas y clientes",
      href: "cm-ventas",
      permiso: "cm.ventas.ver",
      etiqueta: "Ingresos",
      desc: "Desempeño por categoría, ranking de productos, canales de venta, departamentos y mejores clientes.",
      icono: '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    },
    {
      id: "cm_operacion",
      nombre: "Desempeño operativo y SLA",
      href: "cm-operacion",
      permiso: "cm.operacion.ver",
      etiqueta: "Cumplimiento",
      desc: "Embudo del ciclo de pedido, tiempos de atención, efectividad de entrega y rotación de inventario.",
      icono: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    },
  ],
};

/* ---------- Roles semilla ---------- */
const ROLES_SEED = {
  "Administrador": {
    descripcion: "Control total del sistema, usuarios, roles y todos los módulos.",
    base: true,
    modulos: ["admin", "contact_center", "bodega", "logistica", "comercial"],
    permisos: [...TODOS_LOS_PERMISOS],
  },
  "Contact Center": {
    descripcion: "Atención al cliente, captura de pedidos, direcciones y aprobación de pedidos web.",
    base: true,
    modulos: ["contact_center"],
    permisos: PERMISOS.contact_center.map((p) => p.id),
  },
  "Bodega": {
    descripcion: "Recepción de cargas, preparación de pedidos (picking y empaque) y control de inventario.",
    base: true,
    modulos: ["bodega"],
    permisos: PERMISOS.bodega.map((p) => p.id),
  },
  "Logística": {
    descripcion: "Planificación de cargas, asignación de rutas de reparto, entregas y control de flota.",
    base: true,
    modulos: ["logistica"],
    permisos: PERMISOS.logistica.map((p) => p.id),
  },
  "Comercial": {
    descripcion: "Seguimiento de ventas, clientes, metas e indicadores clave del negocio (KPIs).",
    base: true,
    modulos: ["comercial"],
    permisos: PERMISOS.comercial.map((p) => p.id),
  },
};

/* ---------- Usuarios semilla con hashes SHA-256 verificados ---------- */
const USERS_SEED = {
  df_admin: {
    hash: "01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841",
    name: "Valeria Morales",
    email: "vmorales@digyfenix.com",
    role: "Administrador",
    active: true,
    base: true,
  },
  df_contact: {
    hash: "d0ec30a68c45c786c252bd384fea800debd4e0b25ee752670ad8bcddee2d7b2d",
    name: "Diego Herrera",
    email: "dherrera@digyfenix.com",
    role: "Contact Center",
    active: true,
    base: true,
  },
  df_bodega: {
    hash: "0ba7383ea34b483e49c019270543f32d3dbca09795c24050e8a746b70f0e5644",
    name: "Marco Tulio Reyes",
    email: "mreyes@digyfenix.com",
    role: "Bodega",
    active: true,
    base: true,
  },
  df_logistica: {
    hash: "dbfadc6cec5de2fa4c566dd1f909d1577be428a3f0f8f85b2d498fd797a0027e",
    name: "Sofía Castellanos",
    email: "scastellanos@digyfenix.com",
    role: "Logística",
    active: true,
    base: true,
  },
  df_comercial: {
    hash: "898ac5b60209a642ad45c3b553c5401800acf775e4a992d289f26f1b416675dd",
    name: "Andrés Solórzano",
    email: "asolorzano@digyfenix.com",
    role: "Comercial",
    active: true,
    base: true,
  },
  "carlos.lopez": {
    hash: "104937774c7abf0d900bd80332f1e3815b9a052c4b00fab4d271263bb2423689",
    name: "Carlos Daniel López (Grupo #3)",
    email: "clopez@telematica.edu.gt",
    role: "Administrador",
    active: true,
    base: true,
  },
  "victor.castillo": {
    hash: "b6d5af78d1361603dfb57e5782d36ebeda101619f6e06edcdd041bb00b5092a1",
    name: "Victor Javier Castillo (Grupo #3)",
    email: "vcastillo@telematica.edu.gt",
    role: "Comercial",
    active: true,
    base: true,
  },
  "oscar.ortiz": {
    hash: "dd07d83636335b578ad481994a5fd68a5d9f53b4a0d505cce562cfbc69cd0838",
    name: "Oscar Geovany Ortíz (Grupo #3)",
    email: "oortiz@telematica.edu.gt",
    role: "Logística",
    active: true,
    base: true,
  },
  "emmanuel.ajsivinac": {
    hash: "acdf94c530b9e70ba13ec06182a4482eb28d984ae102b20445dab495c9fcec76",
    name: "Emmanuel Alexander Ajsivinac (Grupo #3)",
    email: "eajsivinac@telematica.edu.gt",
    role: "Bodega",
    active: true,
    base: true,
  },
  admin: {
    hash: "a36aef5a11c4073fbe60314fc9df530a9d5f986533594d1f5190742ff9e0e408",
    name: "Administrador General",
    email: "admin@techstore.com.gt",
    role: "Administrador",
    active: true,
    base: true,
  },
};

const KEY = "df_session";
const LS_ROLES = "df_roles_v1";
const LS_USERS = "df_users_v1";
const LS_AUDIT = "df_audit_v1";

function accLeer(k) { try { const r = localStorage.getItem(k); return r ? JSON.parse(r) : null; } catch { return null; } }
function accGuardar(k, v, skipSync) {
  try { localStorage.setItem(k, JSON.stringify(v)); } catch {}
  if (!skipSync && typeof window.pulsoSync === "function") {
    window.pulsoSync(k, v);
  }
}

// Hidratar desde MySQL si hay snapshot activo
if (DB_SNAP && DB_SNAP.connected) {
  if (DB_SNAP.roles && Object.keys(DB_SNAP.roles).length) accGuardar(LS_ROLES, DB_SNAP.roles, true);
  if (DB_SNAP.users && Object.keys(DB_SNAP.users).length) accGuardar(LS_USERS, DB_SNAP.users, true);
  if (Array.isArray(DB_SNAP.audit)) accGuardar(LS_AUDIT, DB_SNAP.audit, true);
}

function getRoles() {
  let r = accLeer(LS_ROLES);
  if (!r || typeof r !== "object") {
    r = JSON.parse(JSON.stringify(ROLES_SEED));
    accGuardar(LS_ROLES, r, true);
  } else {
    let changed = false;
    for (const [k, v] of Object.entries(ROLES_SEED)) {
      if (!r[k]) { r[k] = JSON.parse(JSON.stringify(v)); changed = true; }
    }
    if (r["Administrador"] && r["Administrador"].permisos.length !== TODOS_LOS_PERMISOS.length) {
      r["Administrador"].permisos = [...TODOS_LOS_PERMISOS];
      r["Administrador"].modulos = Object.keys(MODULOS);
      changed = true;
    }
    if (changed) accGuardar(LS_ROLES, r, true);
  }
  return r;
}
function saveRoles(r) { accGuardar(LS_ROLES, r); }

function getUsers() {
  let u = accLeer(LS_USERS);
  if (!u || typeof u !== "object") {
    u = JSON.parse(JSON.stringify(USERS_SEED));
    accGuardar(LS_USERS, u, true);
  } else if (!(DB_SNAP && DB_SNAP.connected)) {
    let changed = false;
    for (const [k, v] of Object.entries(USERS_SEED)) {
      if (!u[k]) {
        u[k] = JSON.parse(JSON.stringify(v));
        changed = true;
      }
    }
    if (changed) accGuardar(LS_USERS, u, true);
  }
  return u;
}
function saveUsers(u) { accGuardar(LS_USERS, u); }

const ROLES = getRoles();
const USERS = getUsers();

/* ---------- Bitácora ---------- */
function getAudit() { return accLeer(LS_AUDIT) || []; }
function audit(action, detail, who) {
  const s = who || getSession();
  const list = getAudit();
  const usr = s ? (s.user || s.name || "sistema") : "sistema";
  list.unshift({
    ts: new Date().toISOString(),
    user: usr,
    action,
    detail: detail || "",
  });
  if (list.length > 200) list.length = 200;
  accGuardar(LS_AUDIT, list, true);
  if (typeof window.pulsoAudit === "function") {
    window.pulsoAudit(usr, action, detail || "");
  }
}
function auditar(action, detail, who) { return audit(action, detail, who); }

/* ---------- Hash SHA-256 ---------- */
async function sha256(text) {
  if (window.crypto && window.crypto.subtle) {
    const buf = new TextEncoder().encode(text);
    const digest = await crypto.subtle.digest("SHA-256", buf);
    return Array.from(new Uint8Array(digest)).map((b) => b.toString(16).padStart(2, "0")).join("");
  }
  return sha256Fallback(text);
}

function sha256Fallback(ascii) {
  function rightRotate(v, a) { return (v >>> a) | (v << (32 - a)); }
  const mathPow = Math.pow, maxWord = mathPow(2, 32);
  let result = "";
  const words = [], asciiBitLength = ascii.length * 8;
  let hash = [], k = [];
  let primeCounter = 0;
  const isComposite = {};
  for (let candidate = 2; primeCounter < 64; candidate++) {
    if (!isComposite[candidate]) {
      for (let i = 0; i < 313; i += candidate) isComposite[i] = candidate;
      hash[primeCounter] = (mathPow(candidate, 0.5) * maxWord) | 0;
      k[primeCounter++] = (mathPow(candidate, 1 / 3) * maxWord) | 0;
    }
  }
  ascii += "\x80";
  while ((ascii.length % 64) - 56) ascii += "\x00";
  for (let i = 0; i < ascii.length; i++) {
    const j = ascii.charCodeAt(i);
    words[i >> 2] |= j << (((3 - i) % 4) * 8);
  }
  words[words.length] = (asciiBitLength / maxWord) | 0;
  words[words.length] = asciiBitLength;
  for (let j = 0; j < words.length; ) {
    const w = words.slice(j, (j += 16));
    const oldHash = hash.slice(0);
    for (let i = 0; i < 64; i++) {
      const w15 = w[i - 15], w2 = w[i - 2];
      const a = hash[0], e = hash[4];
      const t1 = hash[7] + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25)) + ((e & hash[5]) ^ (~e & hash[6])) + k[i] +
        (w[i] = i < 16 ? w[i] : (w[i - 16] + (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15 >>> 3)) + w[i - 7] + (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2 >>> 10))) | 0);
      const t2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22)) + ((a & hash[1]) ^ (a & hash[2]) ^ (hash[1] & hash[2]));
      hash = [(t1 + t2) | 0].concat(hash);
      hash[4] = (hash[4] + t1) | 0;
      hash.pop();
    }
    for (let i = 0; i < 8; i++) hash[i] = (hash[i] + oldHash[i]) | 0;
  }
  for (let i = 0; i < 8; i++) {
    for (let j = 3; j + 1; j--) {
      const b = (hash[i] >> (j * 8)) & 255;
      result += (b < 16 ? 0 : "") + b.toString(16);
    }
  }
  return result;
}

/* ---------- Inicio y cierre de sesión ---------- */
async function login(username, password) {
  const key = String(username || "").trim().toLowerCase();
  const passStr = String(password || "").trim();

  // 1. Intentar autenticación directa y segura contra la API MySQL
  try {
    const formData = new FormData();
    formData.append("user", key);
    formData.append("pass", passStr);
    const resp = await fetch("api.php?action=login", {
      method: "POST",
      body: formData,
    });
    if (resp.ok) {
      const data = await resp.json();
      if (data && data.ok && data.session) {
        sessionStorage.setItem(KEY, JSON.stringify(data.session));
        try {
          const users = getUsers();
          if (users && users[key]) {
            users[key].hash = await sha256(passStr);
            saveUsers(users);
          }
        } catch (e) {}
        return { ok: true, session: data.session };
      } else if (data && data.error && !data.error.includes("conectar")) {
        return { ok: false, error: data.error };
      }
    }
  } catch (netErr) {
    // Si hay error de red o modo offline, continuar con validación local de contingencia
  }

  // 2. Validación local de contingencia
  const users = getUsers();
  let u = users ? users[key] : null;
  if (!u && USERS_SEED[key]) {
    u = USERS_SEED[key];
  }
  if (!u) {
    // Búsqueda case-insensitive
    for (const [k, v] of Object.entries(users || USERS_SEED)) {
      if (k.toLowerCase() === key) { u = v; break; }
    }
  }
  if (!u) return { ok: false, error: "Usuario o contraseña incorrectos." };
  if (u.active === false) return { ok: false, error: "Esta cuenta está desactivada. Contacta al administrador." };

  const h = await sha256(passStr);
  const esCorrecto = (h === String(u.hash || "").toLowerCase());

  if (!esCorrecto) {
    return { ok: false, error: "Usuario o contraseña incorrectos." };
  }

  const roles = getRoles();
  const r = roles[u.role] || { modulos: [], permisos: [] };
  const session = {
    user: key,
    name: u.name,
    email: u.email,
    role: u.role,
    modules: r.modulos || [],
    permisos: r.permisos || [],
    loginAt: new Date().toISOString(),
  };
  sessionStorage.setItem(KEY, JSON.stringify(session));
  audit("login", `Inicio de sesión (${u.role})`, session);
  return { ok: true, session };
}

function getSession() {
  try {
    let s = null;
    const raw = sessionStorage.getItem(KEY);
    if (raw) {
      try { s = JSON.parse(raw); } catch (e) {}
    }
    // Hidratar desde la sesión PHP del servidor si el navegador no tiene sessionStorage
    if ((!s || !s.user) && window.__PHP_SESSION__ && window.__PHP_SESSION__.user) {
      s = window.__PHP_SESSION__;
      try { sessionStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
    }
    if (!s || !s.user) return null;

    // Completar módulos y permisos de rol si hicieran falta
    const roles = (typeof getRoles === "function") ? getRoles() : ROLES_SEED;
    const r = (s.role && roles && roles[s.role]) ? roles[s.role] : { modulos: s.modules || [], permisos: s.permisos || [] };
    if (!s.modules || !Array.isArray(s.modules) || !s.modules.length) {
      s.modules = r.modulos || [];
    }
    if (!s.permisos || !Array.isArray(s.permisos) || !s.permisos.length) {
      s.permisos = r.permisos || [];
    }
    return s;
  } catch (e) {
    return (window.__PHP_SESSION__ && window.__PHP_SESSION__.user) ? window.__PHP_SESSION__ : null;
  }
}

function logout() {
  const s = getSession();
  if (s) audit("logout", "Cierre de sesión", s);
  try { sessionStorage.removeItem(KEY); } catch (e) {}
  try { fetch("api.php?action=logout").catch(() => {}); } catch (e) {}
  window.location.href = "login?logout=1";
}

function requireSession() {
  const s = getSession();
  if (!s) { window.location.replace("login"); return null; }
  return s;
}

function requireModulo(moduloId) {
  const s = requireSession();
  if (!s) return null;
  if (!s.modules.includes(moduloId)) {
    window.location.replace("inicio?denegado=" + encodeURIComponent(moduloId));
    return null;
  }
  return s;
}

function can(permisoId) {
  const s = getSession();
  if (!s) return false;
  if (s.role === "Administrador") return true;
  return Array.isArray(s.permisos) && s.permisos.includes(permisoId);
}

function canAny(lista) { return lista.some((p) => can(p)); }

function submodulosVisibles(moduloId) {
  const todos = SUBMODULOS[moduloId] || [];
  return todos.filter((sm) => !sm.permiso || can(sm.permiso));
}
