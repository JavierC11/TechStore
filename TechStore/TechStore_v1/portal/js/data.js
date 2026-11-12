/* =========================================================
   DigyFenix | Pulso — Datos, Catálogo, Clientes, Pedidos y Stock
   Sincronizado con MySQL (config/db.php + api.php).
   ========================================================= */

const _SNAP = window.__PULSO_DB__ || {};
const _CFG  = (_SNAP && _SNAP.config) ? _SNAP.config : {};

const IVA = (Number(_CFG.iva_porcentaje) > 0 ? Number(_CFG.iva_porcentaje) : 12) / 100;
const MONEDA = "Q";

const CANALES = ["Teléfono", "WhatsApp", "Tienda web", "Correo", "Mostrador"];
const CANALES_TODOS = CANALES;
const PAGOS = ["Contra entrega", "Transferencia bancaria", "Tarjeta (enlace de pago)", "Crédito empresarial"];
const PRIORIDADES = ["Normal", "Alta", "Urgente"];
const FRANJAS = ["Mañana (8:00 – 12:00)", "Tarde (13:00 – 17:00)", "Día completo (8:00 – 17:00)"];

const ESTADOS = {
  "Por aprobar":           { color: "#a78bfa", orden: 0 },
  "Borrador":              { color: "#94a3b8", orden: 1 },
  "Pendiente de despacho": { color: "#f4b63d", orden: 2 },
  "En preparación":        { color: "#38bdf8", orden: 3 },
  "En ruta":               { color: "#fb923c", orden: 4 },
  "Entregado":             { color: "#2fc986", orden: 5 },
  "Cancelado":             { color: "#ef5b66", orden: 6 },
};
const ESTADOS_EDITABLES  = ["Borrador", "Pendiente de despacho"];
const ESTADOS_DESCUENTAN = ["Por aprobar", "Pendiente de despacho", "En preparación", "En ruta", "Entregado"];

const UBICACIONES = {
  "Guatemala":      ["Guatemala", "Mixco", "Villa Nueva", "Santa Catarina Pinula", "San Miguel Petapa", "Villa Canales", "Amatitlán", "Chinautla"],
  "Sacatepéquez":   ["Antigua Guatemala", "San Lucas Sacatepéquez", "Ciudad Vieja", "Jocotenango", "Sumpango"],
  "Escuintla":      ["Escuintla", "Santa Lucía Cotzumalguapa", "Palín", "Puerto San José"],
  "Quetzaltenango": ["Quetzaltenango", "Coatepeque", "Salcajá", "La Esperanza"],
  "Alta Verapaz":   ["Cobán", "San Pedro Carchá", "San Juan Chamelco"],
  "Chimaltenango":  ["Chimaltenango", "Tecpán Guatemala", "El Tejar", "Patzicía"],
  "Suchitepéquez":  ["Mazatenango", "Cuyotenango", "San Antonio Suchitepéquez"],
  "Izabal":         ["Puerto Barrios", "Morales", "Livingston"],
  "Petén":          ["Flores", "San Benito", "Melchor de Mencos"],
  "Jutiapa":        ["Jutiapa", "Asunción Mita", "El Progreso"],
};

const CATEGORIAS_DEFAULT = {
  "Laptops y Portátiles":    { color: "#3b82f6", icono: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>' },
  "Monitores y Pantallas":   { color: "#06b6d4", icono: '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>' },
  "Componentes y Hardware":  { color: "#8b5cf6", icono: '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>' },
  "Periféricos y Accesorios":{ color: "#f97316", icono: '<rect x="2" y="4" width="20" height="12" rx="2"/><path d="M12 16v4"/>' },
  "Redes y Servidores":      { color: "#10b981", icono: '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/>' },
};

const CATEGORIAS = (_SNAP.connected && _SNAP.categorias && Object.keys(_SNAP.categorias).length)
  ? { ...CATEGORIAS_DEFAULT, ..._SNAP.categorias }
  : CATEGORIAS_DEFAULT;

/* Precios base sin IVA (en Quetzales) — Catálogo oficial de Cómputo Tech Store */
const PRODUCTOS_DEFAULT = [
  { sku: "DF-LAP-01", nombre: "Apple MacBook Pro 16\" M3 Pro (36GB RAM, 512GB SSD, Space Black)", categoria: "Laptops y Portátiles", precio: 23499.00, stock: 14, kg: 2.15, m3: 0.008 },
  { sku: "DF-LAP-02", nombre: "ASUS ROG Zephyrus G16 OLED (Ultra 9 185H, RTX 4080, 32GB DDR5, 1TB)", categoria: "Laptops y Portátiles", precio: 19850.00, stock: 8, kg: 1.95, m3: 0.007 },
  { sku: "DF-LAP-03", nombre: "Lenovo ThinkPad X1 Carbon Gen 12 (Ultra 7 155H, 32GB RAM, 1TB SSD)", categoria: "Laptops y Portátiles", precio: 16200.00, stock: 18, kg: 1.09, m3: 0.005 },
  { sku: "DF-LAP-04", nombre: "Dell XPS 14 9440 (Intel Core Ultra 7, RTX 4050, 16GB RAM, 1TB SSD)", categoria: "Laptops y Portátiles", precio: 14750.00, stock: 3, kg: 1.68, m3: 0.006 },
  { sku: "DF-LAP-05", nombre: "HP Victus Gaming 15.6\" (Ryzen 5 8645HS, RTX 4050, 16GB, 512GB)", categoria: "Laptops y Portátiles", precio: 6890.00, stock: 26, kg: 2.29, m3: 0.009 },
  { sku: "DF-LAP-06", nombre: "Acer Swift Go 14 OLED (Core Ultra 5 125H, 16GB RAM, 512GB SSD)", categoria: "Laptops y Portátiles", precio: 5450.00, stock: 0, kg: 1.32, m3: 0.005 },

  { sku: "DF-MON-01", nombre: "Samsung Odyssey Neo G9 49\" Curvo Dual QHD (240Hz, Mini-LED, 1ms)", categoria: "Monitores y Pantallas", precio: 13999.00, stock: 4, kg: 14.50, m3: 0.145 },
  { sku: "DF-MON-02", nombre: "LG UltraGear 34\" Curvo WQHD 34GP83A-B (Nano IPS, 160Hz, G-Sync)", categoria: "Monitores y Pantallas", precio: 6450.00, stock: 11, kg: 7.60, m3: 0.068 },
  { sku: "DF-MON-03", nombre: "Dell UltraSharp 27\" 4K USB-C Hub U2723QE (IPS Black, 90W PD)", categoria: "Monitores y Pantallas", precio: 4890.00, stock: 16, kg: 6.64, m3: 0.042 },
  { sku: "DF-MON-04", nombre: "ASUS TUF Gaming 27\" QHD VG27AQ3A (Fast IPS, 180Hz, 1ms, ELMB)", categoria: "Monitores y Pantallas", precio: 2450.00, stock: 22, kg: 4.80, m3: 0.035 },
  { sku: "DF-MON-05", nombre: "ViewSonic ColorPro 32\" 4K VP3268a-4K (Pantone Validated, 100% sRGB)", categoria: "Monitores y Pantallas", precio: 5900.00, stock: 0, kg: 10.30, m3: 0.075 },

  { sku: "DF-CMP-01", nombre: "ASUS TUF Gaming GeForce RTX 4080 Super 16GB GDDR6X", categoria: "Componentes y Hardware", precio: 9850.00, stock: 7, kg: 1.97, m3: 0.012 },
  { sku: "DF-CMP-02", nombre: "MSI Gaming X Slim GeForce RTX 4070 Super 12GB GDDR6X", categoria: "Componentes y Hardware", precio: 5990.00, stock: 15, kg: 1.25, m3: 0.009 },
  { sku: "DF-CMP-03", nombre: "AMD Ryzen 7 7800X3D (8 Núcleos / 16 Hilos, 5.0GHz, 104MB Cache)", categoria: "Componentes y Hardware", precio: 3850.00, stock: 24, kg: 0.35, m3: 0.002 },
  { sku: "DF-CMP-04", nombre: "Intel Core i7-14700K (20 Núcleos / 28 Hilos, hasta 5.6GHz, LGA1700)", categoria: "Componentes y Hardware", precio: 3690.00, stock: 19, kg: 0.32, m3: 0.002 },
  { sku: "DF-CMP-05", nombre: "Corsair Dominator Titanium RGB DDR5 32GB (2x16GB) 6000MHz CL30", categoria: "Componentes y Hardware", precio: 1450.00, stock: 35, kg: 0.28, m3: 0.001 },
  { sku: "DF-CMP-06", nombre: "SSD NVMe M.2 Samsung 990 Pro 2TB PCIe 4.0 con Disipador", categoria: "Componentes y Hardware", precio: 1650.00, stock: 42, kg: 0.15, m3: 0.001 },
  { sku: "DF-CMP-07", nombre: "Fuente Corsair RM850x Shift 850W 80+ Gold Modular ATX 3.0", categoria: "Componentes y Hardware", precio: 1350.00, stock: 2, kg: 2.80, m3: 0.010 },
  { sku: "DF-CMP-08", nombre: "NVIDIA GeForce RTX 4090 Founders Edition 24GB GDDR6X", categoria: "Componentes y Hardware", precio: 18900.00, stock: 0, kg: 2.20, m3: 0.015 },

  { sku: "DF-PRF-01", nombre: "Teclado Mecánico Custom Keychron Q1 Pro Wireless QMK/VIA", categoria: "Periféricos y Accesorios", precio: 1750.00, stock: 18, kg: 1.75, m3: 0.006 },
  { sku: "DF-PRF-02", nombre: "Mouse Inalámbrico Logitech G Pro X Superlight 2 (60g, 32K HERO 2)", categoria: "Periféricos y Accesorios", precio: 1190.00, stock: 28, kg: 0.25, m3: 0.002 },
  { sku: "DF-PRF-03", nombre: "Headset Gamer HyperX Cloud III Wireless (120h Batería, DTS:X)", categoria: "Periféricos y Accesorios", precio: 1250.00, stock: 21, kg: 0.72, m3: 0.005 },
  { sku: "DF-PRF-04", nombre: "Micrófono Profesional Shure MV7+ Podcast XLR / USB-C con DSP", categoria: "Periféricos y Accesorios", precio: 2490.00, stock: 9, kg: 0.95, m3: 0.004 },
  { sku: "DF-PRF-05", nombre: "Cámara Web Elgato Facecam Pro 4K60 UHD True Color", categoria: "Periféricos y Accesorios", precio: 2650.00, stock: 6, kg: 0.45, m3: 0.003 },
  { sku: "DF-PRF-06", nombre: "Silla Ergonómica Herman Miller Aeron Remastered Talla B", categoria: "Periféricos y Accesorios", precio: 11500.00, stock: 0, kg: 19.50, m3: 0.280 },

  { sku: "DF-NET-01", nombre: "Router Gaming ASUS ROG Rapture GT-AX11000 Pro WiFi 6 Tri-Banda", categoria: "Redes y Servidores", precio: 3450.00, stock: 13, kg: 2.10, m3: 0.014 },
  { sku: "DF-NET-02", nombre: "Servidor NAS Synology DiskStation DS224+ 2-Bahías Gigabit", categoria: "Redes y Servidores", precio: 3290.00, stock: 8, kg: 2.20, m3: 0.012 },
  { sku: "DF-NET-03", nombre: "Switch Gestionable Ubiquiti UniFi Pro Max 16 PoE (2.5GbE)", categoria: "Redes y Servidores", precio: 3850.00, stock: 2, kg: 3.10, m3: 0.016 },
];

const PRODUCTOS = (_SNAP.connected && Array.isArray(_SNAP.productos) && _SNAP.productos.length)
  ? _SNAP.productos.map((p) => ({ ...p }))
  : PRODUCTOS_DEFAULT.map((p) => ({ ...p }));

// Asegurar que toda categoría referenciada por productos exista en CATEGORIAS
PRODUCTOS.forEach((p) => {
  if (!CATEGORIAS[p.categoria]) {
    CATEGORIAS[p.categoria] = { color: "#22b8cf", icono: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/>' };
  }
});

const CLIENTES_SEED = [
  {
    id: "CLI-0001", nombre: "Ana Lucía Morales Pérez", tipo: "Individual", nit: "4829103-7",
    telefono: "5512-8840", email: "ana.morales@gmail.com",
    entrega: { contacto: "Ana Lucía Morales Pérez", telefono: "5512-8840", departamento: "Guatemala", municipio: "Guatemala", zona: "10", direccion: "6a Avenida 12-45, Edificio Reforma Of. 402", referencia: "Frente a la parada del Transmetro, recepción en planta baja" },
    facturacion: { nombre: "Ana Lucía Morales Pérez", nit: "4829103-7", direccion: "6a Avenida 12-45, zona 10, Guatemala", email: "ana.morales@gmail.com" },
    creadoEn: "2026-02-14T15:20:00Z",
  },
  {
    id: "CLI-0002", nombre: "Comercializadora El Quetzal, S.A.", tipo: "Empresa", nit: "7745120-K",
    telefono: "2367-9100", email: "compras@elquetzal.com.gt",
    entrega: { contacto: "Ing. Roberto Castañeda (Bodega)", telefono: "5489-3301", departamento: "Guatemala", municipio: "Villa Nueva", zona: "2", direccion: "Km 17.5 Carretera al Pacífico, Complejo Industrial Sur, Bodega 8", referencia: "Garita azul, anunciarse con recepción de mercadería" },
    facturacion: { nombre: "Comercializadora El Quetzal, S.A.", nit: "7745120-K", direccion: "12 Calle 1-25, Géminis 10 Torre Norte Of. 1104, zona 10, Guatemala", email: "facturacion@elquetzal.com.gt" },
    creadoEn: "2026-01-22T10:05:00Z",
  },
  {
    id: "CLI-0003", nombre: "Juan Carlos Méndez Estrada", tipo: "Individual", nit: "3190482-5",
    telefono: "4218-7765", email: "jcmendez@outlook.com",
    entrega: { contacto: "Juan Carlos Méndez", telefono: "4218-7765", departamento: "Guatemala", municipio: "Mixco", zona: "4", direccion: "Condominio Bosques de San Nicolás, Casa 34", referencia: "Portón gris, llamar al llegar a garita" },
    facturacion: { nombre: "Juan Carlos Méndez Estrada", nit: "3190482-5", direccion: "Condominio Bosques de San Nicolás, Casa 34, zona 4, Mixco", email: "jcmendez@outlook.com" },
    creadoEn: "2026-03-03T17:40:00Z",
  },
  {
    id: "CLI-0004", nombre: "Ferretería y Suministros La Reforma, S.A.", tipo: "Empresa", nit: "9021845-3",
    telefono: "2251-4400", email: "pedidos@ferreteriareforma.gt",
    entrega: { contacto: "Héctor Samayoa", telefono: "5890-1122", departamento: "Guatemala", municipio: "Guatemala", zona: "9", direccion: "7a Avenida 14-19", referencia: "Portón metálico lateral sobre la 14 calle" },
    facturacion: { nombre: "Ferretería y Suministros La Reforma, S.A.", nit: "9021845-3", direccion: "7a Avenida 14-19, zona 9, Guatemala", email: "contabilidad@ferreteriareforma.gt" },
    creadoEn: "2026-01-11T09:00:00Z",
  },
  {
    id: "CLI-0005", nombre: "María Fernanda Cabrera Orantes", tipo: "Individual", nit: "6350219-1",
    telefono: "5701-9934", email: "mfcabrera@yahoo.com",
    entrega: { contacto: "María Fernanda Cabrera", telefono: "5701-9934", departamento: "Sacatepéquez", municipio: "Antigua Guatemala", zona: "", direccion: "4a Calle Oriente N.º 28", referencia: "Casa amarilla de esquina, portón de madera" },
    facturacion: { nombre: "María Fernanda Cabrera Orantes", nit: "6350219-1", direccion: "4a Calle Oriente N.º 28, Antigua Guatemala", email: "mfcabrera@yahoo.com" },
    creadoEn: "2026-03-19T14:15:00Z",
  },
  {
    id: "CLI-0006", nombre: "Café y Pastelería Los Altos", tipo: "Empresa", nit: "5510394-8",
    telefono: "7761-2280", email: "administracion@cafelosaltos.gt",
    entrega: { contacto: "Gabriela Quemé", telefono: "4602-8811", departamento: "Quetzaltenango", municipio: "Quetzaltenango", zona: "3", direccion: "14 Avenida 4-32", referencia: "A media cuadra del Parque Benito Juárez" },
    facturacion: { nombre: "Inversiones Gastronómicas de Occidente, S.A.", nit: "5510394-8", direccion: "14 Avenida 4-32, zona 3, Quetzaltenango", email: "facturas@cafelosaltos.gt" },
    creadoEn: "2026-02-01T11:30:00Z",
  },
  {
    id: "CLI-0007", nombre: "Luis Pedro Alvarado Girón", tipo: "Individual", nit: "CF",
    telefono: "3345-6090", email: "lpalvarado@gmail.com",
    entrega: { contacto: "Luis Pedro Alvarado", telefono: "3345-6090", departamento: "Guatemala", municipio: "Santa Catarina Pinula", zona: "1", direccion: "Residenciales Las Luces, Km 13.5 Carretera a El Salvador, Lote 19", referencia: "Garita principal, indicar visita a familia Alvarado" },
    facturacion: { nombre: "Consumidor Final", nit: "CF", direccion: "Ciudad", email: "lpalvarado@gmail.com" },
    creadoEn: "2026-04-01T16:00:00Z",
  },
  {
    id: "CLI-0008", nombre: "Clínica Dental Sonrisa Clara", tipo: "Empresa", nit: "8412760-9",
    telefono: "2334-5520", email: "recepcion@sonrisaclara.gt",
    entrega: { contacto: "Dra. Claudia Santizo", telefono: "5219-4408", departamento: "Guatemala", municipio: "Guatemala", zona: "14", direccion: "5a Avenida 15-45, Centro Empresarial Torre II, Clínica 605", referencia: "Subir por elevadores pares al sexto nivel" },
    facturacion: { nombre: "Servicios Odontológicos Santizo, S.A.", nit: "8412760-9", direccion: "5a Avenida 15-45, zona 14, Guatemala", email: "contabilidad@sonrisaclara.gt" },
    creadoEn: "2026-02-18T13:10:00Z",
  },
  {
    id: "CLI-0009", nombre: "Estuardo Javier Pineda Choc", tipo: "Individual", nit: "2984710-4",
    telefono: "4812-3095", email: "epineda@gmail.com",
    entrega: { contacto: "Estuardo Pineda", telefono: "4812-3095", departamento: "Escuintla", municipio: "Escuintla", zona: "1", direccion: "4a Avenida 6-18, Colonia El Recreo", referencia: "Casa de dos niveles color crema, frente a tienda La Bendición" },
    facturacion: { nombre: "Estuardo Javier Pineda Choc", nit: "2984710-4", direccion: "4a Avenida 6-18, zona 1, Escuintla", email: "epineda@gmail.com" },
    creadoEn: "2026-03-25T18:05:00Z",
  },
  {
    id: "CLI-0010", nombre: "Hotel Posada del Bosquecito, S.A.", tipo: "Empresa", nit: "6198430-2",
    telefono: "7951-0840", email: "compras@posadadelbosquecito.com",
    entrega: { contacto: "Erwin Caal (Mantenimiento)", telefono: "5540-7712", departamento: "Alta Verapaz", municipio: "Cobán", zona: "2", direccion: "3a Calle 5-90", referencia: "Portón de servicio al costado del parqueo" },
    facturacion: { nombre: "Hotel Posada del Bosquecito, S.A.", nit: "6198430-2", direccion: "3a Calle 5-90, zona 2, Cobán, Alta Verapaz", email: "facturas@posadadelbosquecito.com" },
    creadoEn: "2026-03-08T09:45:00Z",
  },
  {
    id: "CLI-0011", nombre: "Paola Beatriz Barrios Rodas", tipo: "Individual", nit: "5478123-6",
    telefono: "3091-2240", email: "paola.barrios@gmail.com",
    entrega: { contacto: "Paola Barrios", telefono: "3091-2240", departamento: "Guatemala", municipio: "Guatemala", zona: "15", direccion: "20 Calle 2-40, Vista Hermosa II, Apartamento 5B", referencia: "Edificio Terracota, dejar con conserje si no responde" },
    facturacion: { nombre: "Paola Beatriz Barrios Rodas", nit: "5478123-6", direccion: "20 Calle 2-40, zona 15, Guatemala", email: "paola.barrios@gmail.com" },
    creadoEn: "2026-04-04T12:30:00Z",
  },
  {
    id: "CLI-0012", nombre: "Despacho Contable Orozco & Asociados", tipo: "Empresa", nit: "4401928-1",
    telefono: "2230-1188", email: "info@orozcoasociados.gt",
    entrega: { contacto: "Licda. Miriam Orozco", telefono: "5110-6674", departamento: "Guatemala", municipio: "Guatemala", zona: "4", direccion: "Ruta 2, 4-63, Edificio Cuatro Grados, Oficina 304", referencia: "Frente a plazuela, timbre 304" },
    facturacion: { nombre: "Orozco & Asociados Contadores Públicos, S.A.", nit: "4401928-1", direccion: "Ruta 2, 4-63, zona 4, Guatemala", email: "administracion@orozcoasociados.gt" },
    creadoEn: "2026-02-27T16:50:00Z",
  },
  {
    id: "CLI-0013", nombre: "Carlos Humberto Xiloj Tzul", tipo: "Individual", nit: "7123904-8",
    telefono: "4509-8821", email: "cxiloj@gmail.com",
    entrega: { contacto: "Carlos Xiloj", telefono: "4509-8821", departamento: "Chimaltenango", municipio: "Chimaltenango", zona: "2", direccion: "2a Calle 7-55", referencia: "A una cuadra de los Bomberos Voluntarios" },
    facturacion: { nombre: "Carlos Humberto Xiloj Tzul", nit: "7123904-8", direccion: "2a Calle 7-55, zona 2, Chimaltenango", email: "cxiloj@gmail.com" },
    creadoEn: "2026-03-30T15:00:00Z",
  },
  {
    id: "CLI-0014", nombre: "Colegio Bilingüe Monte Verde", tipo: "Empresa", nit: "3891045-K",
    telefono: "6634-9000", email: "compras@colegiomonteverde.edu.gt",
    entrega: { contacto: "Prof. Byron Velásquez", telefono: "5923-7014", departamento: "Guatemala", municipio: "San Miguel Petapa", zona: "7", direccion: "Avenida Principal 10-00, Prados de Villa Hermosa", referencia: "Entrada por portón 2 de proveeduría, horario de 7:30 a 14:30" },
    facturacion: { nombre: "Centro Educativo Monte Verde, S.A.", nit: "3891045-K", direccion: "Avenida Principal 10-00, zona 7, San Miguel Petapa", email: "tesoreria@colegiomonteverde.edu.gt" },
    creadoEn: "2026-01-29T08:20:00Z",
  },
];

/* ---------- Utilidades de formato y cálculo ---------- */
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
const conIva = (n) => round2(Number(n) * (1 + IVA));

function fmtQ(n) {
  const v = round2(Number(n) || 0);
  return MONEDA + " " + v.toLocaleString("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtFecha(iso) {
  if (!iso) return "—";
  const d = new Date(iso);
  return d.toLocaleDateString("es-GT", { day: "2-digit", month: "short", year: "numeric" }) +
    " · " + d.toLocaleTimeString("es-GT", { hour: "2-digit", minute: "2-digit" });
}
function fmtFechaCorta(iso) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("es-GT", { day: "2-digit", month: "short", year: "numeric" });
}
function fechaInput(d) {
  const x = d ? new Date(d) : new Date();
  const y = x.getFullYear();
  const m = String(x.getMonth() + 1).padStart(2, "0");
  const day = String(x.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}
function esc(s) {
  return String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}
function norm(s) {
  return String(s ?? "").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
function iniciales(nombre) {
  return String(nombre || "").trim().split(/\s+/).slice(0, 2).map((p) => p[0] || "").join("").toUpperCase() || "CL";
}
function soloDigitos(s) { return String(s || "").replace(/\D/g, ""); }
function fmtTel(s) {
  const d = soloDigitos(s).slice(0, 8);
  return d.length > 4 ? d.slice(0, 4) + "-" + d.slice(4) : d;
}

function totalLinea(it) {
  const cant = Math.max(0, Number(it.cantidad) || 0);
  const desc = Math.min(100, Math.max(0, Number(it.descuento) || 0));
  const bruto = round2(it.precio * cant);
  const rebaja = round2(bruto * (desc / 100));
  const sinIva = round2(bruto - rebaja);
  return { bruto, rebaja, sinIva, conIva: conIva(sinIva) };
}
function calcTotales(items, envio) {
  let subtotal = 0, descuento = 0, unidades = 0;
  for (const it of items) {
    const t = totalLinea(it);
    subtotal = round2(subtotal + t.bruto);
    descuento = round2(descuento + t.rebaja);
    unidades += Math.max(0, Number(it.cantidad) || 0);
  }
  const env = round2(Math.max(0, Number(envio) || 0));
  const base = round2(subtotal - descuento + env);
  const iva = round2(base * IVA);
  const total = round2(base + iva);
  return { lineas: items.length, unidades, subtotal, descuento, envio: env, base, iva, total };
}

/* ---------- Almacenamiento y semillas ---------- */
const LS_PEDIDOS   = "df_pedidos_v1";
const LS_CLIENTES  = "df_clientes_v1";
const LS_SEQ       = "df_seq_v1";
const LS_VEHICULOS = "df_vehiculos_v1";
const LS_RUTAS     = "df_rutas_v1";
const LS_STOCK     = "df_stock_v1";
const LS_MOVS      = "df_movs_v1";

function leerLS(k) { try { const r = localStorage.getItem(k); return r ? JSON.parse(r) : null; } catch { return null; } }
function guardarLS(k, v, skipSync) {
  try { localStorage.setItem(k, JSON.stringify(v)); } catch {}
  if (!skipSync && typeof window.pulsoSync === "function") {
    window.pulsoSync(k, v);
  }
}

// Hidratar caches locales desde MySQL cuando hay conexión activa
if (_SNAP && _SNAP.connected) {
  if (_SNAP.stockMap && Object.keys(_SNAP.stockMap).length) guardarLS(LS_STOCK, _SNAP.stockMap, true);
  if (Array.isArray(_SNAP.clientes) && _SNAP.clientes.length) guardarLS(LS_CLIENTES, _SNAP.clientes, true);
  if (Array.isArray(_SNAP.cuentas) && _SNAP.cuentas.length) guardarLS("df_cuentas_v1", _SNAP.cuentas, true);
  if (Array.isArray(_SNAP.pedidos) && _SNAP.pedidos.length) guardarLS(LS_PEDIDOS, _SNAP.pedidos, true);
  if (typeof _SNAP.seq === "number" && _SNAP.seq > 0) guardarLS(LS_SEQ, _SNAP.seq, true);
  if (Array.isArray(_SNAP.vehiculos) && _SNAP.vehiculos.length) guardarLS(LS_VEHICULOS, _SNAP.vehiculos, true);
  if (Array.isArray(_SNAP.rutas) && _SNAP.rutas.length) guardarLS(LS_RUTAS, _SNAP.rutas, true);
  if (Array.isArray(_SNAP.movs) && _SNAP.movs.length) guardarLS(LS_MOVS, _SNAP.movs, true);
  if (_SNAP.metas && typeof _SNAP.metas === "object") guardarLS("df_metas_v1", _SNAP.metas, true);
}

/* ---------- Stock vivo en bodega ---------- */
const STOCK_INICIAL = Object.fromEntries(PRODUCTOS.map((p) => [p.sku, p.stock]));

function getStockMap() {
  let m = leerLS(LS_STOCK);
  if (!m || typeof m !== "object") {
    m = { ...STOCK_INICIAL };
    guardarLS(LS_STOCK, m, true);
  } else {
    let changed = false;
    for (const p of PRODUCTOS) {
      if (typeof m[p.sku] !== "number") { m[p.sku] = p.stock; changed = true; }
    }
    if (changed) guardarLS(LS_STOCK, m, true);
  }
  return m;
}
function saveStockMap(m) {
  guardarLS(LS_STOCK, m);
  sincronizarStockProductos(m);
}
function sincronizarStockProductos(m) {
  const map = m || getStockMap();
  for (const p of PRODUCTOS) {
    if (typeof map[p.sku] === "number") p.stock = map[p.sku];
  }
}
sincronizarStockProductos();

function getMovimientos() { return leerLS(LS_MOVS) || []; }
function getMovs() {
  return getMovimientos().map((m) => {
    const p = PRODUCTOS.find((x) => x.sku === m.sku);
    return {
      ...m,
      fecha: m.fecha || m.ts,
      nombre: m.nombre || (p ? p.nombre : m.sku),
      cant: m.cant !== undefined ? m.cant : (m.delta ?? 0),
      saldo: m.saldo !== undefined ? m.saldo : (m.stockDespues ?? 0),
      quien: m.quien || m.usuario || "Sistema",
    };
  });
}
function registrarMovimiento(mov) {
  const m = getStockMap();
  const actual = Number(m[mov.sku] ?? 0);
  const delta = Number(mov.delta) || 0;
  const nuevo = actual + delta;
  if (nuevo < 0) return null;
  m[mov.sku] = nuevo;
  saveStockMap(m);
  const s = typeof getSession === "function" ? getSession() : null;
  const prodObj = PRODUCTOS.find((x) => x.sku === mov.sku);
  const lista = getMovimientos();
  const ahoraIso = new Date().toISOString();
  const usr = mov.usuario || (s ? s.name : "Sistema");
  const reg = {
    id: "MOV-" + Date.now() + "-" + Math.floor(Math.random() * 900 + 100),
    ts: ahoraIso,
    fecha: ahoraIso,
    sku: mov.sku,
    nombre: prodObj ? prodObj.nombre : mov.sku,
    tipo: mov.tipo || (delta >= 0 ? "Entrada" : "Salida"),
    delta: nuevo - actual,
    cant: nuevo - actual,
    stockAntes: actual,
    stockDespues: nuevo,
    saldo: nuevo,
    motivo: mov.motivo || "",
    ref: mov.ref || "",
    usuario: usr,
    quien: usr,
  };
  lista.unshift(reg);
  if (lista.length > 400) lista.length = 400;
  guardarLS(LS_MOVS, lista);
  if (typeof audit === "function") audit("inventario." + String(reg.tipo).toLowerCase(), `${reg.sku} (${reg.delta >= 0 ? "+" : ""}${reg.delta}) → ${reg.stockDespues}`);
  return reg;
}
function moverStock(sku, delta, tipo, motivo, quien, ref) {
  const d = Number(delta) || 0;
  const cur = Number(getStockMap()[sku] ?? 0);
  if (cur + d < 0) {
    return { ok: false, error: `Stock insuficiente en ${sku} (actual: ${cur}, requerido: ${Math.abs(d)}).` };
  }
  const mov = registrarMovimiento({ sku, delta: d, tipo, motivo, usuario: quien, ref });
  if (!mov) return { ok: false, error: "No se pudo registrar el movimiento." };
  return { ok: true, saldo: mov.stockDespues, mov };
}

function getClientes() {
  let c = leerLS(LS_CLIENTES);
  if (!Array.isArray(c) || !c.length) {
    c = JSON.parse(JSON.stringify(CLIENTES_SEED));
    guardarLS(LS_CLIENTES, c);
  }
  return c;
}
function getCliente(id) { return getClientes().find((c) => c.id === id) || null; }
function saveCliente(cli) {
  const lista = getClientes();
  if (!cli.id) {
    const max = lista.reduce((m, x) => Math.max(m, Number((x.id || "").split("-")[1]) || 0), 0);
    cli.id = "CLI-" + String(max + 1).padStart(4, "0");
    cli.creadoEn = new Date().toISOString();
    lista.unshift(cli);
  } else {
    const i = lista.findIndex((x) => x.id === cli.id);
    if (i >= 0) lista[i] = cli; else lista.unshift(cli);
  }
  guardarLS(LS_CLIENTES, lista);
  if (typeof audit === "function") audit("cliente.guardar", `${cli.id} · ${cli.nombre}`);
  return cli;
}

function seedPedidos() {
  const clis = getClientes();
  const ahora = Date.now();
  const h = (horas) => new Date(ahora - horas * 3600 * 1000).toISOString();
  const dFutura = (dias) => {
    const d = new Date(ahora + dias * 86400 * 1000);
    return fechaInput(d);
  };
  const armar = (num, cliIdx, estado, haceHoras, diasEnt, canal, pago, prio, envio, defs, obs, notas, extra) => {
    const c = clis[cliIdx % clis.length];
    const items = defs.map(([sku, cantidad, descuento]) => {
      const p = PRODUCTOS.find((x) => x.sku === sku) || PRODUCTOS[0];
      return { sku: p.sku, nombre: p.nombre, categoria: p.categoria, precio: p.precio, cantidad, descuento: descuento || 0 };
    });
    const f = h(haceHoras);
    return {
      id: `PED-2026-${String(num).padStart(4, "0")}`,
      estado,
      fecha: f,
      clienteId: c.id,
      cliente: { nombre: c.nombre, nit: c.nit, telefono: c.telefono, email: c.email, tipo: c.tipo },
      entrega: { ...c.entrega },
      facturacion: { ...c.facturacion },
      items,
      envio,
      totales: calcTotales(items, envio),
      canal,
      pago,
      prioridad: prio,
      franja: FRANJAS[num % FRANJAS.length],
      fechaEntrega: dFutura(diasEnt),
      obsCliente: obs || "",
      notasInternas: notas || "",
      creadoPor: canal === "Tienda web" ? "Tienda web (autoatención)" : (num % 2 === 0 ? "Diego Herrera" : "Valeria Morales"),
      actualizado: f,
      ...(extra || {}),
    };
  };

  const base = [
    armar(48, 0,  "Por aprobar",           0.2,  1, "Tienda web", "Tarjeta (enlace de pago)", "Alta",    0,  [["DF-LAP-01", 1, 0], ["DF-PRF-02", 1, 0]], "Llamar 15 minutos antes de llegar.", "Cliente frecuente · pago con tarjeta solicitado en tienda web"),
    armar(47, 4,  "Por aprobar",           0.5,  2, "Tienda web", "Transferencia bancaria",   "Normal", 45,  [["DF-PRF-01", 2, 0], ["DF-PRF-05", 1, 0]], "Dejar en recepción si no estoy.", ""),
    armar(46, 10, "Por aprobar",           0.8,  1, "Tienda web", "Contra entrega",           "Normal", 35,  [["DF-CMP-05", 1, 0], ["DF-PRF-03", 2, 0]], "Entregar por la tarde por favor.", ""),
    armar(45, 5,  "Por aprobar",           1.3,  3, "Tienda web", "Transferencia bancaria",   "Alta",   65,  [["DF-MON-04", 2, 0], ["DF-CMP-06", 2, 0]], "Empacar bien para transporte a Xela.", "Solicita confirmación de cuenta bancaria por correo"),
    armar(44, 1,  "Pendiente de despacho", 3,    1, "Teléfono",   "Crédito empresarial",      "Urgente", 0,  [["DF-MON-03", 4, 5], ["DF-LAP-03", 2, 5], ["DF-NET-01", 2, 0]], "Entregar en bodega 8 antes de las 11:00.", "OC-88412 autorizada por gerencia", { minLiquidacion: 18, fechaAprobacion: h(2.7) }),
    armar(43, 2,  "Pendiente de despacho", 5,    1, "WhatsApp",   "Contra entrega",           "Normal", 35,  [["DF-CMP-03", 1, 0], ["DF-CMP-05", 1, 0]], "Avisar en garita.", "", { minLiquidacion: 24, fechaAprobacion: h(4.6) }),
    armar(42, 3,  "En preparación",        9,    1, "Teléfono",   "Crédito empresarial",      "Alta",    0,  [["DF-LAP-05", 3, 8], ["DF-MON-04", 3, 5]], "Recibe Héctor Samayoa en portón lateral.", "Cliente mayorista", { picking: { "DF-LAP-05": 3, "DF-MON-04": 2 }, minLiquidacion: 15, fechaAprobacion: h(8.75), rutaId: "RUT-0001" }),
    armar(41, 7,  "Pendiente de despacho", 14,   2, "Correo",     "Transferencia bancaria",   "Normal", 35,  [["DF-LAP-03", 2, 0], ["DF-MON-03", 2, 0]], "Subir al sexto nivel por elevadores pares.", "Transferencia verificada #994102", { minLiquidacion: 32, fechaAprobacion: h(13.4) }),
    armar(40, 6,  "Borrador",              18,   3, "WhatsApp",   "Contra entrega",           "Normal", 35,  [["DF-PRF-04", 1, 0]], "", "Pendiente que el cliente confirme si agrega webcam"),
    armar(39, 13, "En preparación",        22,   1, "Correo",     "Crédito empresarial",      "Alta",    0,  [["DF-MON-04", 4, 10], ["DF-NET-02", 2, 5]], "Entregar en proveeduría antes de las 14:00.", "Contrato anual vigente", { picking: { "DF-MON-04": 4, "DF-NET-02": 2 }, minLiquidacion: 20, fechaAprobacion: h(21.6), rutaId: "RUT-0002" }),
    armar(38, 11, "En ruta",               27,   0, "Teléfono",   "Transferencia bancaria",   "Normal", 25,  [["DF-CMP-06", 2, 0], ["DF-PRF-05", 2, 0]], "Timbre 304.", "Ruta metropolitana mañana", { rutaId: "RUT-0003", empacadoPor: "Marco Tulio Reyes", minLiquidacion: 22, fechaAprobacion: h(26.6), minEmpaque: 19, fechaEmpaque: h(25.5), fechaEnvio: h(24.2), horasDespacho: 2.8, enPlazo: true }),
    armar(37, 8,  "Pendiente de despacho", 31,   2, "WhatsApp",   "Contra entrega",           "Normal", 50,  [["DF-LAP-05", 1, 0], ["DF-PRF-02", 2, 0]], "", "", { minLiquidacion: 28, fechaAprobacion: h(30.5) }),
    armar(36, 9,  "En ruta",               46,   0, "Teléfono",   "Transferencia bancaria",   "Alta",   75,  [["DF-NET-01", 2, 5], ["DF-MON-04", 2, 5]], "Portón de servicio al costado del parqueo.", "Envío departamental norte", { rutaId: "RUT-0004", empacadoPor: "Marco Tulio Reyes", minLiquidacion: 19, fechaAprobacion: h(45.7), minEmpaque: 24, fechaEmpaque: h(44.5), fechaEnvio: h(43.0), horasDespacho: 3.0, enPlazo: true }),
    armar(35, 0,  "Entregado",             54,  -1, "Tienda web", "Tarjeta (enlace de pago)", "Normal",  0,  [["DF-PRF-01", 1, 0], ["DF-PRF-02", 1, 0]], "Entregar en recepción.", "", { minLiquidacion: 25, fechaAprobacion: h(53.5), minEmpaque: 18, fechaEmpaque: h(52.2), fechaEnvio: h(51.0), horasDespacho: 3.0, enPlazo: true, entregadoEn: h(30), recibidoPor: "Recepción Edificio Reforma" }),
    armar(34, 4,  "Entregado",             72,  -2, "WhatsApp",   "Contra entrega",           "Normal", 45,  [["DF-LAP-05", 1, 0]], "", "", { minLiquidacion: 30, fechaAprobacion: h(71.5), minEmpaque: 22, fechaEmpaque: h(70.0), fechaEnvio: h(68.5), horasDespacho: 3.5, enPlazo: true, entregadoEn: h(49), recibidoPor: "María Fernanda Cabrera" }),
    armar(33, 5,  "Entregado",             95,  -3, "Teléfono",   "Transferencia bancaria",   "Normal", 60,  [["DF-MON-02", 1, 0], ["DF-CMP-03", 1, 0], ["DF-PRF-03", 2, 5]], "", "", { minLiquidacion: 21, fechaAprobacion: h(94.6), minEmpaque: 25, fechaEmpaque: h(93.0), fechaEnvio: h(91.2), horasDespacho: 3.8, enPlazo: true, entregadoEn: h(68), recibidoPor: "Gabriela Quemé" }),
    armar(32, 12, "Cancelado",             110, -3, "Teléfono",   "Contra entrega",           "Normal", 55,  [["DF-CMP-02", 1, 0]], "", "Cliente solicitó cancelar por cambio de presupuesto"),
    armar(31, 1,  "Entregado",             138, -4, "Correo",     "Crédito empresarial",      "Normal",  0,  [["DF-LAP-03", 2, 10], ["DF-MON-03", 3, 10]], "Entregar en bodega 8.", "Proyecto ampliación oficinas", { minLiquidacion: 17, fechaAprobacion: h(137.7), minEmpaque: 26, fechaEmpaque: h(136.0), fechaEnvio: h(134.8), horasDespacho: 3.2, enPlazo: true, entregadoEn: h(112), recibidoPor: "Ing. Roberto Castañeda" }),
  ];

  const canalesHist = ["Teléfono", "WhatsApp", "Tienda web", "Correo", "Mostrador"];
  const pagosHist = ["Contra entrega", "Transferencia bancaria", "Tarjeta (enlace de pago)", "Crédito empresarial"];
  const skus = PRODUCTOS.filter((x) => x.stock > 0).map((p) => p.sku);
  for (let n = 30; n >= 1; n--) {
    const cliIdx = (n * 3 + 1) % clis.length;
    const c = clis[cliIdx];
    const haceH = 150 + (30 - n) * 42;
    const canal = canalesHist[n % canalesHist.length];
    const pago = c.tipo === "Empresa" && n % 2 === 0 ? "Crédito empresarial" : pagosHist[n % 3];
    const estado = n === 19 || n === 7 ? "Cancelado" : "Entregado";
    const s1 = skus[(n * 2) % skus.length];
    const s2 = skus[(n * 5 + 3) % skus.length];
    const cant1 = (n % 2) + 1;
    const cant2 = 1;
    const defs = s1 === s2 ? [[s1, cant1 + 1, 0]] : [[s1, cant1, n % 4 === 0 ? 5 : 0], [s2, cant2, 0]];
    const envio = c.entrega.departamento === "Guatemala" ? (n % 3 === 0 ? 0 : 35) : 55;
    const aTiempo = n % 12 !== 0;
    const hDesp = aTiempo ? round2(2.1 + (n % 4) * 0.7) : round2(8.5 + (n % 3) * 0.6);
    const minLiq = (n % 14 === 0) ? 68 : (14 + (n % 28));
    const minEmp = 16 + (n % 13);
    const entH = haceH - hDesp - 12;
    base.push(armar(
      n, cliIdx, estado, haceH, -Math.ceil(haceH / 24) + 1, canal, pago,
      n % 6 === 0 ? "Alta" : "Normal", envio, defs, "", "",
      estado === "Entregado" ? {
        minLiquidacion: minLiq,
        fechaAprobacion: h(haceH - (minLiq / 60)),
        minEmpaque: minEmp,
        fechaEmpaque: h(Math.max(5, haceH - hDesp + 0.5)),
        fechaEnvio: h(Math.max(4.5, haceH - hDesp)),
        horasDespacho: hDesp,
        enPlazo: aTiempo,
        entregadoEn: h(Math.max(2, entH)),
        recibidoPor: c.entrega.contacto,
        aTiempo,
      } : {}
    ));
  }
  return base;
}

function getPedidos() {
  let p = leerLS(LS_PEDIDOS);
  if (!Array.isArray(p) || !p.length) {
    p = seedPedidos();
    guardarLS(LS_PEDIDOS, p);
    guardarLS(LS_SEQ, 48);
    guardarLS(LS_STOCK, getStockMap());
  }
  return p;
}
function getPedido(id) { return getPedidos().find((p) => p.id === id) || null; }

function mapaCantidades(pedido) {
  const m = {};
  if (!pedido || !ESTADOS_DESCUENTAN.includes(pedido.estado)) return m;
  for (const it of pedido.items || []) m[it.sku] = (m[it.sku] || 0) + (Number(it.cantidad) || 0);
  return m;
}

function savePedido(ped) {
  const lista = getPedidos();
  const i = lista.findIndex((x) => x.id === ped.id);
  const previo = i >= 0 ? lista[i] : null;

  const antes = mapaCantidades(previo);
  const despues = mapaCantidades(ped);
  const skus = new Set([...Object.keys(antes), ...Object.keys(despues)]);
  const sm = getStockMap();
  let stockCambio = false;
  for (const sku of skus) {
    const diff = (despues[sku] || 0) - (antes[sku] || 0);
    if (diff !== 0) {
      sm[sku] = Math.max(0, (Number(sm[sku]) || 0) - diff);
      stockCambio = true;
    }
  }
  if (stockCambio) saveStockMap(sm);

  if (i >= 0) lista[i] = ped; else lista.unshift(ped);
  guardarLS(LS_PEDIDOS, lista);
  if (typeof audit === "function") audit(previo ? "pedido.actualizar" : "pedido.crear", `${ped.id} · ${ped.estado} · ${fmtQ(ped.totales?.total || 0)}`);
  return ped;
}
function nextId() {
  const n = (Number(leerLS(LS_SEQ)) || 48) + 1;
  guardarLS(LS_SEQ, n);
  return `PED-2026-${String(n).padStart(4, "0")}`;
}
async function resetDatosDemo() {
  [LS_PEDIDOS, LS_CLIENTES, LS_SEQ, LS_VEHICULOS, LS_RUTAS, LS_STOCK, LS_MOVS, "df_cuentas_v1", "df_carrito_v1", "df_metas_v1"].forEach((k) => localStorage.removeItem(k));
  for (const [sku, cant] of Object.entries(STOCK_INICIAL)) {
    const p = PRODUCTOS.find((x) => x.sku === sku);
    if (p) p.stock = cant;
  }
  try {
    await fetch("api.php?action=reset_demo", { method: "POST" });
  } catch (e) {}
  if (typeof audit === "function") audit("sistema.reset", "Restauración de datos de demostración");
}
window.resetDemo = resetDatosDemo;
