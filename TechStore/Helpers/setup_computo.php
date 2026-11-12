<?php
/**
 * DigyFenix | Pulso — Migración y Siembra de Datos Reales de Cómputo & Hardware
 * Popula el catálogo con 28 productos de cómputo, 5 categorías tecnológicas,
 * órdenes de compra/venta, estados de stock (normal, bajo, sin stock / agotado),
 * clientes, rutas logísticas y métricas para Prometheus + Grafana.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$pdo = getPDO();
if (!$pdo) {
    die("ERROR: No se pudo conectar a MySQL.\n");
}

echo "=== INICIANDO SIEMBRA DE DATOS DE CÓMPUTO & HARDWARE EN MYSQL ===\n";

// 1. Configuración del sitio orientada a Cómputo (Tech Store)
$configs = [
    ['empresa', 'Tech Store', 'Nombre comercial de la empresa'],
    ['producto', 'Guatemala', 'Nombre de la plataforma operativa'],
    ['lema', 'Tecnología, Cómputo y Observabilidad E-commerce', 'Subtítulo corporativo del sistema'],
    ['hero_eyebrow', 'Cómputo & Hardware de Alto Rendimiento · Despacho en toda Guatemala', 'Texto superior del banner en tienda'],
    ['hero_titulo_1', 'Equipa tu oficina y estación de trabajo', 'Título principal de la tienda'],
    ['hero_titulo_acento', 'con tecnología garantizada', 'Texto resaltado en la tienda'],
    ['hero_subtitulo', 'Catálogo en tiempo real sincronizado con bodega central. Laptops, monitores, tarjetas gráficas, procesadores y periféricos con garantía y despacho inmediato.', 'Descripción principal del banner de tienda'],
    ['perk_1', 'Garantía oficial y factura con IVA incluido', 'Beneficio destacado 1'],
    ['perk_2', 'Envío gratis en compras mayores a Q 500', 'Beneficio destacado 2'],
    ['perk_3', 'Monitoreo y observabilidad en tiempo real con Prometheus', 'Beneficio destacado 3'],
    ['anuncio_activo', '1', 'Mostrar cintillo promocional superior en la tienda (1=Sí, 0=No)'],
    ['anuncio_texto', 'Equipos de cómputo con stock en bodega · Despacho con flota propia en toda Guatemala · Métricas en vivo en Grafana', 'Texto del cintillo promocional'],
    ['iva_porcentaje', '12', 'Porcentaje de IVA aplicable (%)'],
    ['envio_base', '35', 'Costo base de envío sin IVA (Q)'],
    ['envio_gratis_desde', '500', 'Monto mínimo para envío gratuito (Q)'],
    ['color_primario', '#2563eb', 'Color acento principal del portal'],
    ['telefono_soporte', '+502 2290-4000', 'Teléfono de contacto y atención al cliente'],
    ['correo_soporte', 'contacto@techstore.com.gt', 'Correo electrónico de soporte'],
    ['pie_pagina', 'Tech Store Guatemala · E-commerce & Hardware Solutions — Grupo #3 Telemática', 'Texto de pie de página'],
    ['seq_pedidos', '55', 'Secuencia correlativa de pedidos'],
];

$stmtCfg = $pdo->prepare("INSERT INTO `configuracion_sitio` (`clave`, `valor`, `descripcion`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `descripcion` = VALUES(`descripcion`)");
foreach ($configs as $c) {
    $stmtCfg->execute($c);
}
echo "✓ Configuración del sitio actualizada a tienda de cómputo.\n";

// 2. Categorías tecnológicas
$pdo->exec("DELETE FROM `categorias`");
$categorias = [
    ['Laptops y Portátiles', '#3b82f6', '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>', 1],
    ['Monitores y Pantallas', '#06b6d4', '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>', 2],
    ['Componentes y Hardware', '#8b5cf6', '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>', 3],
    ['Periféricos y Accesorios', '#f97316', '<rect x="2" y="4" width="20" height="12" rx="2"/><line x1="6" y1="8" x2="6.01" y2="8"/><line x1="10" y1="8" x2="10.01" y2="8"/><line x1="14" y1="8" x2="14.01" y2="8"/><line x1="18" y1="8" x2="18.01" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><path d="M12 16v4"/>', 4],
    ['Redes y Servidores', '#10b981', '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>', 5],
];
$stmtCat = $pdo->prepare("INSERT INTO `categorias` (`nombre`, `color`, `icono`, `orden`, `activo`) VALUES (?, ?, ?, ?, 1)");
foreach ($categorias as $cat) {
    $stmtCat->execute($cat);
}
echo "✓ 5 Categorías de cómputo registradas.\n";

// 3. Catálogo de 28 Productos de Cómputo Reales
$pdo->exec("DELETE FROM `productos`");
$productos = [
    // Laptops y Portátiles
    [
        'DF-LAP-01',
        'Apple MacBook Pro 16" M3 Pro (36GB RAM, 512GB SSD, Space Black)',
        'Chip Apple M3 Pro con CPU de 12 núcleos y GPU de 18 núcleos, 36 GB de memoria unificada, pantalla Liquid Retina XDR de 16.2 pulgadas con ProMotion 120Hz, 3 puertos Thunderbolt 4, HDMI y hasta 22 horas de autonomía.',
        'Laptops y Portátiles',
        23499.00,
        14,
        4,
        2.15,
        0.008,
        'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-LAP-02',
        'ASUS ROG Zephyrus G16 OLED (Ultra 9 185H, RTX 4080, 32GB DDR5, 1TB)',
        'Laptop gaming ultradelgada de alta gama con procesador Intel Core Ultra 9 185H, NVIDIA GeForce RTX 4080 de 12GB GDDR6, 32GB LPDDR5X, pantalla ROG Nebula OLED 2.5K a 240Hz y chasis mecanizado de aluminio CNC.',
        'Laptops y Portátiles',
        19850.00,
        8,
        3,
        1.95,
        0.007,
        'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-LAP-03',
        'Lenovo ThinkPad X1 Carbon Gen 12 (Ultra 7 155H, 32GB RAM, 1TB SSD)',
        'Portátil empresarial insignia en fibra de carbono ultraligera. Intel Core Ultra 7 155H con aceleración por NPU para IA, 32GB LPDDR5X, pantalla 14" 2.8K OLED antirreflejo y certificación militar MIL-STD-810H.',
        'Laptops y Portátiles',
        16200.00,
        18,
        5,
        1.09,
        0.005,
        'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-LAP-04',
        'Dell XPS 14 9440 (Intel Core Ultra 7, RTX 4050, 16GB RAM, 1TB SSD)',
        'Diseño minimalista premium en aluminio mecanizado con touchpad de cristal háptico continuo, pantalla táctil InfinityEdge 3.2K OLED de 120Hz y tarjeta gráfica dedicada NVIDIA GeForce RTX 4050 de 6GB.',
        'Laptops y Portátiles',
        14750.00,
        3, // STOCK BAJO
        5,
        1.68,
        0.006,
        'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-LAP-05',
        'HP Victus Gaming 15.6" (Ryzen 5 8645HS, RTX 4050, 16GB, 512GB)',
        'Rendimiento gamer balanceado con procesador AMD Zen 4 de 6 núcleos y 12 hilos, gráficos NVIDIA GeForce RTX 4050 con DLSS 3, 16GB de memoria DDR5 a 5600MHz y panel IPS Full HD de 144Hz con microbordes.',
        'Laptops y Portátiles',
        6890.00,
        26,
        8,
        2.29,
        0.009,
        'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-LAP-06',
        'Acer Swift Go 14 OLED (Core Ultra 5 125H, 16GB RAM, 512GB SSD)',
        'Movilidad extrema con panel OLED 2.8K 90Hz certificado VESA DisplayHDR True Black 500, procesador con motor Intel AI Boost, cámara web QHD 1440p con reducción de ruido y chasis delgado de 14.9 mm.',
        'Laptops y Portátiles',
        5450.00,
        0, // AGOTADO / SIN STOCK
        6,
        1.32,
        0.005,
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],

    // Monitores y Pantallas
    [
        'DF-MON-01',
        'Samsung Odyssey Neo G9 49" Curvo Dual QHD (240Hz, Mini-LED, 1ms)',
        'Monitor curvo panorámico 32:9 de 49 pulgadas equivalente a dos monitores de 27", curvatura 1000R inmersiva, tecnología Quantum Matrix Mini-LED con 2,048 zonas de atenuación local y HDR2000.',
        'Monitores y Pantallas',
        13999.00,
        4,
        2,
        14.50,
        0.145,
        'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-MON-02',
        'LG UltraGear 34" Curvo WQHD 34GP83A-B (Nano IPS, 160Hz, G-Sync)',
        'Resolución 3440x1440 con panel Nano IPS de 1ms de respuesta nativo (GtG), cobertura 98% DCI-P3, frecuencia overclockeable a 160Hz, HDR400 y compatibilidad oficial certificada con NVIDIA G-Sync.',
        'Monitores y Pantallas',
        6450.00,
        11,
        4,
        7.60,
        0.068,
        'https://images.unsplash.com/photo-1585792180666-f75a7c527e74?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-MON-03',
        'Dell UltraSharp 27" 4K USB-C Hub U2723QE (IPS Black, 90W PD)',
        'Monitor profesional 4K UHD 3840x2160 con tecnología IPS Black con contraste 2000:1, cobertura 98% DCI-P3, hub USB-C integrado con suministro de energía de 90W, RJ45 Ethernet y conmutador KVM.',
        'Monitores y Pantallas',
        4890.00,
        16,
        5,
        6.64,
        0.042,
        'https://images.unsplash.com/photo-1547119957-637f8679db1e?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-MON-04',
        'ASUS TUF Gaming 27" QHD VG27AQ3A (Fast IPS, 180Hz, 1ms, ELMB)',
        'Panel Fast IPS de 2560x1440 con frecuencia de refresco de 180Hz, 1ms MPRT, tecnología Extreme Low Motion Blur Sync, 130% sRGB y soporte HDR10 para partidas competitivas ultra fluidas.',
        'Monitores y Pantallas',
        2450.00,
        22,
        6,
        4.80,
        0.035,
        'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-MON-05',
        'ViewSonic ColorPro 32" 4K VP3268a-4K (Pantone Validated, 100% sRGB)',
        'Monitor profesional de diseño gráfico y corrección de color calibrado de fábrica con Delta E < 2, tabla 3D LUT de 14 bits, uniformidad de panel del 95% y puerto USB-C con 90W de carga.',
        'Monitores y Pantallas',
        5900.00,
        0, // AGOTADO / SIN STOCK
        3,
        10.30,
        0.075,
        'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],

    // Componentes y Hardware
    [
        'DF-CMP-01',
        'ASUS TUF Gaming GeForce RTX 4080 Super 16GB GDDR6X',
        'GPU con arquitectura Ada Lovelace, 10,240 núcleos CUDA, DLSS 3.5 con Ray Reconstruction, rodamientos de ventilador de doble bola y disipador de 3.65 ranuras con condensadores militares de larga vida.',
        'Componentes y Hardware',
        9850.00,
        7,
        3,
        1.97,
        0.012,
        'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-CMP-02',
        'MSI Gaming X Slim GeForce RTX 4070 Super 12GB GDDR6X',
        'Diseño térmico TRI FROZR 3 con aspas TORX Fan 5.0, base de cobre niquelado, backplate de aluminio ventilado y excelente rendimiento en gaming 1440p con trazado de rayos a altas tasas de cuadros.',
        'Componentes y Hardware',
        5990.00,
        15,
        4,
        1.25,
        0.009,
        'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-CMP-03',
        'AMD Ryzen 7 7800X3D (8 Núcleos / 16 Hilos, 5.0GHz, 104MB Cache)',
        'El procesador para gaming más rápido y eficiente con tecnología 3D V-Cache de segunda generación, zócalo AM5 compatible con DDR5 y PCIe 5.0, arquitectura Zen 4 de 5nm y TDP contenido de 120W.',
        'Componentes y Hardware',
        3850.00,
        24,
        6,
        0.35,
        0.002,
        'https://images.unsplash.com/photo-1555680202-c86f0e12f086?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-CMP-04',
        'Intel Core i7-14700K (20 Núcleos / 28 Hilos, hasta 5.6GHz, LGA1700)',
        'Procesador de 14ª generación con 8 Performance-cores y 12 Efficient-cores, 33MB Intel Smart Cache, compatibilidad con placas base Z790/B760 y soporte para memoria DDR5 y carriles PCIe 5.0.',
        'Componentes y Hardware',
        3690.00,
        19,
        5,
        0.32,
        0.002,
        'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-CMP-05',
        'Corsair Dominator Titanium RGB DDR5 32GB (2x16GB) 6000MHz CL30',
        'Kit de memoria RAM de alto rendimiento con chips seleccionados a mano, iluminación RGB personalizable de 11 zonas por módulo, perfiles Intel XMP 3.0 / AMD EXPO y barra superior intercambiable.',
        'Componentes y Hardware',
        1450.00,
        35,
        10,
        0.28,
        0.001,
        'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-CMP-06',
        'SSD NVMe M.2 Samsung 990 Pro 2TB PCIe 4.0 con Disipador',
        'Almacenamiento sólido con velocidades de lectura secuencial de hasta 7,450 MB/s y escritura de 6,900 MB/s, disipador de calor integrado de bajo perfil compatible con PlayStation 5 y computadoras de escritorio.',
        'Componentes y Hardware',
        1650.00,
        42,
        12,
        0.15,
        0.001,
        'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-CMP-07',
        'Fuente Corsair RM850x Shift 850W 80+ Gold Modular ATX 3.0',
        'Fuente modular de última generación con panel lateral de conectores de fácil acceso, certificación Cybenetics Gold, condensadores 100% japoneses y conector nativo PCIe 5.0 12VHPWR para tarjetas serie RTX 40.',
        'Componentes y Hardware',
        1350.00,
        2, // STOCK BAJO
        6,
        2.80,
        0.010,
        'https://images.unsplash.com/photo-1587202372616-b43abea06c2a?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-CMP-08',
        'NVIDIA GeForce RTX 4090 Founders Edition 24GB GDDR6X',
        'La tarjeta gráfica más potente para creadores, render 3D y modelos de IA, con 16,384 núcleos CUDA, 24 GB de memoria de video a 21 Gbps, 4ª generación de núcleos Tensor y sistema de enfriamiento de doble flujo.',
        'Componentes y Hardware',
        18900.00,
        0, // AGOTADO / SIN STOCK
        2,
        2.20,
        0.015,
        'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],

    // Periféricos y Accesorios
    [
        'DF-PRF-01',
        'Teclado Mecánico Custom Keychron Q1 Pro Wireless QMK/VIA',
        'Chasis de aluminio 6063 mecanizado por CNC, factor de forma 75% con perilla rotatoria multimedia programable, switches mecánicos lubricados de fábrica, montura doble de junta y conectividad Bluetooth / USB-C.',
        'Periféricos y Accesorios',
        1750.00,
        18,
        5,
        1.75,
        0.006,
        'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-PRF-02',
        'Mouse Inalámbrico Logitech G Pro X Superlight 2 (60g, 32K HERO 2)',
        'Mouse ultraligero preferido en esports profesionales con peso de 60 gramos, interruptores híbridos óptico-mecánicos LIGHTFORCE, sensor HERO 2 de 32,000 DPI con tasa de sondeo inalámbrico de 4000Hz.',
        'Periféricos y Accesorios',
        1190.00,
        28,
        8,
        0.25,
        0.002,
        'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-PRF-03',
        'Headset Gamer HyperX Cloud III Wireless (120h Batería, DTS:X)',
        'Audífonos inalámbricos de confort prolongado con almohadillas viscoelásticas, transductores angulados de 53 mm rediseñados, audio espacial DTS Headphone:X de por vida y micrófono de 10 mm con filtro antipop.',
        'Periféricos y Accesorios',
        1250.00,
        21,
        6,
        0.72,
        0.005,
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-PRF-04',
        'Micrófono Profesional Shure MV7+ Podcast XLR / USB-C con DSP',
        'Micrófono dinámico con tecnología de aislamiento por voz de Shure, panel táctil LED personalizable, procesador DSP con reductor de ruido en tiempo real, filtro Digital Popper Stopper y salida de audífonos.',
        'Periféricos y Accesorios',
        2490.00,
        9,
        4,
        0.95,
        0.004,
        'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-PRF-05',
        'Cámara Web Elgato Facecam Pro 4K60 UHD True Color',
        'Primera webcam con transmisión 4K a 60 fps sin compresión, óptica de 21 mm con apertura f/2.0 y enfoque automático, sensor Sony STARVIS de gran formato y memoria interna para almacenar perfiles de imagen.',
        'Periféricos y Accesorios',
        2650.00,
        6,
        3,
        0.45,
        0.003,
        'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
    [
        'DF-PRF-06',
        'Silla Ergonómica Herman Miller Aeron Remastered Talla B',
        'Silla ergonómica de oficina con malla Pellicle 8Z que elimina puntos de presión corporal, soporte lumbar PostureFit SL ajustable para columna sacra y mecanismos reclinables de precisión equilibrada.',
        'Periféricos y Accesorios',
        11500.00,
        0, // AGOTADO / SIN STOCK
        2,
        19.50,
        0.280,
        'https://images.unsplash.com/photo-1580481077195-c277724a737a?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],

    // Redes y Servidores
    [
        'DF-NET-01',
        'Router Gaming ASUS ROG Rapture GT-AX11000 Pro WiFi 6 Tri-Banda',
        'Router tribanda WiFi 6 de alto rendimiento con velocidades combinadas de hasta 11,000 Mbps, doble puerto multigigabit de 10G y 2.5G, procesador quad-core de 64 bits a 2.0GHz y compatibilidad ASUS AiMesh.',
        'Redes y Servidores',
        3450.00,
        13,
        4,
        2.10,
        0.014,
        'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-NET-02',
        'Servidor NAS Synology DiskStation DS224+ 2-Bahías Gigabit',
        'Servidor de almacenamiento en red para copias de seguridad centralizadas y nube privada empresarial. Procesador Intel Celeron J4125 de 4 núcleos, 2 puertos LAN GbE con tolerancia a fallos y sistema DSM 7.2.',
        'Redes y Servidores',
        3290.00,
        8,
        3,
        2.20,
        0.012,
        'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=800&q=80',
        1,
        1
    ],
    [
        'DF-NET-03',
        'Switch Gestionable Ubiquiti UniFi Pro Max 16 PoE (2.5GbE)',
        'Switch de nivel empresarial capa 3 con 16 puertos (4 puertos 2.5GbE PoE++, 12 puertos 1GbE PoE+), 2 enlaces ascendentes SFP+ 10G, presupuesto PoE total de 180W e iluminación Etherlighting para puertos.',
        'Redes y Servidores',
        3850.00,
        2, // STOCK BAJO
        4,
        3.10,
        0.016,
        'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=800&q=80',
        0,
        1
    ],
];

$stmtProd = $pdo->prepare("
    INSERT INTO `productos` (`sku`, `nombre`, `descripcion`, `categoria`, `precio`, `stock`, `stock_minimo`, `kg`, `m3`, `imagen`, `destacado`, `activo`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($productos as $p) {
    $stmtProd->execute($p);
}
echo "✓ 28 Productos de cómputo insertados exitosamente (4 sin stock, 4 stock bajo, 20 en existencia normal).\n";

// 4. Actualizar contraseñas de usuarios en MySQL
$usuarios = [
    ['df_admin', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Valeria Morales', 'vmorales@digyfenix.com', 'Administrador', 1, 1],
    ['df_contact', 'd0ec30a68c45c786c252bd384fea800debd4e0b25ee752670ad8bcddee2d7b2d', 'Diego Herrera', 'dherrera@digyfenix.com', 'Contact Center', 1, 1],
    ['df_bodega', '0ba7383ea34b483e49c019270543f32d3dbca09795c24050e8a746b70f0e5644', 'Marco Tulio Reyes', 'mreyes@digyfenix.com', 'Bodega', 1, 1],
    ['df_logistica', 'dbfadc6cec5de2fa4c566dd1f909d1577be428a3f0f8f85b2d498fd797a0027e', 'Sofía Castellanos', 'scastellanos@digyfenix.com', 'Logística', 1, 1],
    ['df_comercial', '898ac5b60209a642ad45c3b553c5401800acf775e4a992d289f26f1b416675dd', 'Andrés Solórzano', 'asolorzano@digyfenix.com', 'Comercial', 1, 1],
    ['carlos.lopez', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Carlos Daniel López (Grupo #3)', 'carlos.lopez@digyfenix.com', 'Administrador', 1, 0],
    ['victor.castillo', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Victor Javier Castillo (Grupo #3)', 'victor.castillo@digyfenix.com', 'Comercial', 1, 0],
    ['oscar.ortiz', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Oscar Geovany Ortíz (Grupo #3)', 'oscar.ortiz@digyfenix.com', 'Logística', 1, 0],
    ['emmanuel.ajsivinac', '01629aa8178594f50fe0f996276825eb3be41ee5d6d50d0ddc4b8fafce6bc841', 'Emmanuel Alexander Ajsivinac (Grupo #3)', 'emmanuel.ajsivinac@digyfenix.com', 'Bodega', 1, 0],
    ['admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Administrador General', 'admin@digyfenix.com', 'Administrador', 1, 1],
];
$stmtUsr = $pdo->prepare("
    INSERT INTO `usuarios` (`username`, `hash_clave`, `nombre`, `email`, `rol_id`, `activo`, `base`)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `hash_clave` = VALUES(`hash_clave`), `nombre` = VALUES(`nombre`), `email` = VALUES(`email`), `rol_id` = VALUES(`rol_id`), `activo` = 1
");
foreach ($usuarios as $u) {
    $stmtUsr->execute($u);
}
echo "✓ Hashes SHA-256 de usuarios actualizados en MySQL.\n";

// 5. Cliente demo tienda
$pdo->exec("
    INSERT INTO `cuentas_tienda` (`email`, `cliente_id`, `nombre`, `hash_clave`, `creado_en`)
    VALUES ('cliente@demo.com', 'CLI-0001', 'Ana Lucía Morales Pérez', 'c383f8002fb5d3448518471a4527097d458b7e4661e89259bcdc44ed07d1094b', '2026-02-14 15:20:00')
    ON DUPLICATE KEY UPDATE `hash_clave` = VALUES(`hash_clave`)
");
echo "✓ Cuenta demo de cliente (cliente@demo.com / Cliente@2026) configurada.\n";

// 6. Regenerar pedidos reales con productos de cómputo
$pdo->exec("DELETE FROM `pedidos`");
$pdo->exec("DELETE FROM `rutas`");
$pdo->exec("DELETE FROM `movimientos_inventario`");

$cliRows = $pdo->query("SELECT * FROM `clientes` ORDER BY `id` ASC")->fetchAll();
$prodMap = [];
foreach ($productos as $pr) {
    $prodMap[$pr[0]] = [
        'sku'       => $pr[0],
        'nombre'    => $pr[1],
        'categoria' => $pr[3],
        'precio'    => $pr[4],
        'kg'        => $pr[7],
        'm3'        => $pr[8],
    ];
}

$now = time();
$pedidosData = [];

// Helper para crear pedido estructurado
$crearPedido = function(
    int $num,
    int $cliIdx,
    string $estado,
    float $horasAtras,
    int $diasEntrega,
    string $canal,
    string $pago,
    string $prioridad,
    float $envio,
    array $itemDefs,
    string $obs = '',
    string $notas = '',
    array $extra = []
) use ($cliRows, $prodMap, $now): array {
    $c = $cliRows[$cliIdx % count($cliRows)];
    $items = [];
    $subtotal = 0.0;
    $descuento = 0.0;
    $unidades = 0;
    $pesoKg = 0.0;
    $volM3 = 0.0;

    foreach ($itemDefs as $def) {
        $sku = $def[0];
        $cant = (int)$def[1];
        $pctDesc = (float)($def[2] ?? 0);
        $p = $prodMap[$sku] ?? reset($prodMap);
        $precio = (float)$p['precio'];
        $bruto = round($precio * $cant, 2);
        $rebaja = round($bruto * ($pctDesc / 100), 2);
        $subtotal = round($subtotal + $bruto, 2);
        $descuento = round($descuento + $rebaja, 2);
        $unidades += $cant;
        $pesoKg += round(($p['kg'] ?? 1.0) * $cant, 2);
        $volM3 += round(($p['m3'] ?? 0.01) * $cant, 3);

        $items[] = [
            'sku'       => $p['sku'],
            'nombre'    => $p['nombre'],
            'categoria' => $p['categoria'],
            'precio'    => $precio,
            'cantidad'  => $cant,
            'descuento' => $pctDesc,
        ];
    }

    $base = round($subtotal - $descuento + $envio, 2);
    $iva = round($base - ($base / 1.12), 2);
    $totales = [
        'subtotal'  => $subtotal,
        'descuento' => $descuento,
        'envio'     => $envio,
        'iva'       => $iva,
        'total'     => $base,
        'unidades'  => $unidades,
        'pesoKg'    => round($pesoKg, 2),
        'volM3'     => round($volM3, 3),
    ];

    $fechaTs = $now - (int)($horasAtras * 3600);
    $fechaIso = date('Y-m-d H:i:s', $fechaTs);
    $fechaEntrega = date('Y-m-d', $now + $diasEntrega * 86400);
    $id = sprintf('PED-2026-%04d', $num);

    $franjas = ['Mañana (8:00 – 12:00)', 'Tarde (13:00 – 17:00)', 'Día completo (8:00 – 17:00)'];
    $franja = $franjas[$num % count($franjas)];

    $cliData = [
        'id'       => $c['id'],
        'nombre'   => $c['nombre'],
        'tipo'     => $c['tipo'],
        'nit'      => $c['nit'],
        'telefono' => $c['telefono'],
        'email'    => $c['email'] ?? '',
    ];
    $entData = json_decode($c['entrega_json'], true) ?: [];
    $facData = json_decode($c['facturacion_json'], true) ?: [];

    return [
        'id'               => $id,
        'estado'           => $estado,
        'fecha'            => $fechaIso,
        'cliente_id'       => $c['id'],
        'cliente_json'     => json_encode($cliData, JSON_UNESCAPED_UNICODE),
        'entrega_json'     => json_encode($entData, JSON_UNESCAPED_UNICODE),
        'facturacion_json' => json_encode($facData, JSON_UNESCAPED_UNICODE),
        'items_json'       => json_encode($items, JSON_UNESCAPED_UNICODE),
        'envio'            => $envio,
        'totales_json'     => json_encode($totales, JSON_UNESCAPED_UNICODE),
        'canal'            => $canal,
        'pago'             => $pago,
        'prioridad'        => $prioridad,
        'franja'           => $franja,
        'fecha_entrega'    => $fechaEntrega,
        'obs_cliente'      => $obs,
        'notas_internas'   => $notas,
        'creado_por'       => ($canal === 'Tienda web') ? 'Tienda en línea' : 'Diego Herrera',
        'actualizado'      => date('Y-m-d H:i:s', $now - (int)(max(1, $horasAtras - 2) * 3600)),
        'ruta_id'          => $extra['rutaId'] ?? null,
        'picking_json'     => isset($extra['picking']) ? json_encode($extra['picking'], JSON_UNESCAPED_UNICODE) : null,
        'extra_json'       => count($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
    ];
};

// Generar 35 pedidos representativos con hardware de cómputo
$pedidosRaw = [
    // Recientes (en curso en diferentes fases)
    $crearPedido(55, 0,  'Ingresado',      0.5,  2, 'Tienda web', 'Tarjeta de crédito', 'Normal', 0,   [['DF-LAP-01', 1, 0], ['DF-PRF-02', 1, 0]], 'Dejar en recepción de torre Reforma.', 'Cliente web nuevo'),
    $crearPedido(54, 1,  'Ingresado',      1.2,  2, 'Tienda web', 'Transferencia bancaria', 'Alta', 0, [['DF-CMP-01', 2, 0], ['DF-CMP-03', 2, 0], ['DF-CMP-05', 2, 0]], 'Solicita confirmación de cuenta para transferencia.', 'Proyecto render 3D'),
    $crearPedido(53, 2,  'Ingresado',      2.4,  3, 'WhatsApp',   'Contra entrega', 'Normal', 35,     [['DF-MON-04', 1, 0], ['DF-PRF-01', 1, 0]], 'Llamar antes de entregar.', ''),
    $crearPedido(52, 3,  'Aprobado',       3.1,  1, 'Teléfono',   'Transferencia bancaria', 'Alta', 0, [['DF-LAP-02', 1, 5], ['DF-MON-02', 1, 0]], 'Anunciarse con garita principal.', 'Aprobado por Contact Center'),
    $crearPedido(51, 4,  'Aprobado',       4.5,  2, 'Tienda web', 'Tarjeta de crédito', 'Normal', 0,   [['DF-CMP-02', 1, 0], ['DF-CMP-06', 1, 0]], '', ''),
    $crearPedido(50, 5,  'Aprobado',       5.2,  2, 'Correo',     'Crédito empresarial', 'Normal', 0,  [['DF-NET-01', 2, 5], ['DF-NET-02', 1, 0]], 'Entregar en departamento de IT.', 'Orden de compra PO-2026-88'),
    $crearPedido(49, 6,  'En preparación', 6.0,  1, 'Tienda web', 'Tarjeta de crédito', 'Normal', 0,   [['DF-LAP-05', 1, 0], ['DF-PRF-03', 1, 0]], '', 'En picking por Marco Tulio', ['picking' => ['DF-LAP-05' => 1, 'DF-PRF-03' => 1]]),
    $crearPedido(48, 7,  'En preparación', 7.5,  1, 'Teléfono',   'Transferencia bancaria', 'Alta', 0, [['DF-MON-01', 1, 0], ['DF-CMP-01', 1, 0]], 'Cuidado al manipular, equipo delicado.', 'Cubicaje especial', ['picking' => ['DF-MON-01' => 1, 'DF-CMP-01' => 1]]),
    $crearPedido(47, 8,  'Empacado',       9.0,  1, 'Tienda web', 'Tarjeta de crédito', 'Normal', 0,   [['DF-LAP-03', 1, 0], ['DF-PRF-04', 1, 0]], '', 'Empacado en caja reforzada', ['empacadoPor' => 'Marco Tulio Reyes']),
    $crearPedido(46, 9,  'Empacado',      10.5,  1, 'Teléfono',   'Transferencia bancaria', 'Alta', 0, [['DF-NET-02', 2, 0], ['DF-CMP-06', 4, 5]], 'Entregar en recepción de bodegas.', 'Listo para ruta norte', ['empacadoPor' => 'Marco Tulio Reyes']),
    $crearPedido(45, 10, 'En ruta',       12.0,  0, 'Tienda web', 'Tarjeta de crédito', 'Alta', 0,     [['DF-LAP-01', 1, 0], ['DF-MON-03', 1, 0]], 'Timbre 5B, dejar con conserje.', 'En reparto móvil VEH-01', ['rutaId' => 'RUT-2026-001', 'empacadoPor' => 'Marco Tulio Reyes']),
    $crearPedido(44, 11, 'En ruta',       13.5,  0, 'Teléfono',   'Transferencia bancaria', 'Normal', 0,[['DF-CMP-03', 1, 0], ['DF-CMP-05', 2, 0], ['DF-CMP-06', 1, 0]], 'Edificio Cuatro Grados Of. 304.', 'En reparto móvil VEH-01', ['rutaId' => 'RUT-2026-001', 'empacadoPor' => 'Marco Tulio Reyes']),
    $crearPedido(43, 12, 'En ruta',       14.0,  0, 'WhatsApp',   'Contra entrega', 'Normal', 55,      [['DF-MON-04', 1, 0], ['DF-PRF-02', 1, 0]], 'A media cuadra de bomberos.', 'Ruta Chimaltenango VEH-02', ['rutaId' => 'RUT-2026-002', 'empacadoPor' => 'Marco Tulio Reyes']),
    $crearPedido(42, 13, 'En ruta',       15.2,  0, 'Correo',     'Crédito empresarial', 'Normal', 0,  [['DF-LAP-05', 3, 8], ['DF-PRF-03', 3, 5]], 'Portón 2 de proveeduría de 7:30 a 14:30.', 'Ruta Sur VEH-03', ['rutaId' => 'RUT-2026-003', 'empacadoPor' => 'Marco Tulio Reyes']),
];

// Pedidos históricos entregados y cancelados
for ($i = 41; $i >= 21; $i--) {
    $cliIdx = $i % count($cliRows);
    $hAtras = 18 + (41 - $i) * 16;
    $canal = ($i % 3 === 0) ? 'Tienda web' : (($i % 2 === 0) ? 'Teléfono' : 'WhatsApp');
    $pago = ($i % 2 === 0) ? 'Transferencia bancaria' : 'Tarjeta de crédito';
    $estado = ($i === 38 || $i === 24) ? 'Cancelado' : 'Entregado';
    $sku1 = array_keys($prodMap)[($i * 3) % count($prodMap)];
    $sku2 = array_keys($prodMap)[($i * 7 + 2) % count($prodMap)];
    $extra = ($estado === 'Entregado') ? [
        'entregadoEn' => date('Y-m-d H:i:s', $now - (int)($hAtras * 3600 - 18000)),
        'recibidoPor' => $cliRows[$cliIdx]['nombre'],
        'aTiempo'     => ($i % 7 !== 0),
    ] : ['motivoCancelacion' => 'Cliente canceló por cambio de requerimiento técnico.'];

    $pedidosRaw[] = $crearPedido(
        $i,
        $cliIdx,
        $estado,
        $hAtras,
        -1,
        $canal,
        $pago,
        'Normal',
        0,
        [[$sku1, 1, 0], [$sku2, ($i % 2) + 1, 0]],
        '',
        '',
        $extra
    );
}

$stmtPed = $pdo->prepare("
    INSERT INTO `pedidos` (
        `id`, `estado`, `fecha`, `cliente_id`, `cliente_json`, `entrega_json`, `facturacion_json`,
        `items_json`, `envio`, `totales_json`, `canal`, `pago`, `prioridad`, `franja`,
        `fecha_entrega`, `obs_cliente`, `notas_internas`, `creado_por`, `actualizado`,
        `ruta_id`, `picking_json`, `extra_json`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($pedidosRaw as $p) {
    $stmtPed->execute(array_values($p));
}
echo "✓ 35 Pedidos reales de cómputo generados (Ingresados, Aprobados, En preparación, Empacados, En ruta y Entregados).\n";

// 7. Generar rutas activas
$rutas = [
    [
        'RUT-2026-001', 'RUT-2026-001', 'Ruta Metropolitana 1 (Zonas 10, 14, 15)', 'Guatemala - Zona 10, 14, 15',
        date('Y-m-d'), 'Mañana (8:00 – 12:00)', 'VEH-01', 'Ramiro Ajanel', '5514-2201', 'En ruta',
        json_encode(['PED-2026-0045', 'PED-2026-0044'], JSON_UNESCAPED_UNICODE),
        'Cargar primero pedidos de zona 15 y entregar zona 10 de último.', 'Sofía Castellanos', date('Y-m-d 07:30:00'), null
    ],
    [
        'RUT-2026-002', 'RUT-2026-002', 'Ruta Occidente (Mixco, Chimaltenango)', 'Mixco / Chimaltenango',
        date('Y-m-d'), 'Día completo (8:00 – 17:00)', 'VEH-02', 'Selvin Orozco', '5623-8910', 'En ruta',
        json_encode(['PED-2026-0043'], JSON_UNESCAPED_UNICODE),
        'Tomar Carretera Interamericana temprano.', 'Sofía Castellanos', date('Y-m-d 08:00:00'), null
    ],
    [
        'RUT-2026-003', 'RUT-2026-003', 'Ruta Sur Empresarial (Villa Nueva, Petapa)', 'Villa Nueva / San Miguel Petapa',
        date('Y-m-d'), 'Tarde (13:00 – 17:00)', 'VEH-03', 'Baudilio Pérez', '4712-3309', 'En ruta',
        json_encode(['PED-2026-0042'], JSON_UNESCAPED_UNICODE),
        'Entrega en complejo industrial y colegio.', 'Sofía Castellanos', date('Y-m-d 09:15:00'), null
    ],
];
$stmtRut = $pdo->prepare("
    INSERT INTO `rutas` (`id`, `codigo`, `nombre`, `zona`, `fecha`, `franja`, `vehiculo_id`, `piloto`, `tel_piloto`, `estado`, `pedidos_json`, `notas`, `creada_por`, `creada_en`, `extra_json`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($rutas as $r) {
    $stmtRut->execute($r);
}
echo "✓ 3 Rutas activas de distribución logística registradas.\n";

// 8. Kardex de movimientos de inventario de cómputo
$movimientos = [
    ['MOV-1001', date('Y-m-d H:i:s', $now - 72000), 'DF-LAP-01', 'Ingreso por compra', 15, 0, 15, 'Recepción contenedor importación Apple M3', 'OC-2026-440', 'Marco Tulio Reyes'],
    ['MOV-1002', date('Y-m-d H:i:s', $now - 68000), 'DF-CMP-01', 'Ingreso por compra', 8, 0, 8, 'Recepción mayorista ASUS TUF RTX 4080', 'OC-2026-441', 'Marco Tulio Reyes'],
    ['MOV-1003', date('Y-m-d H:i:s', $now - 64000), 'DF-CMP-03', 'Ingreso por compra', 25, 0, 25, 'Ingreso procesadores AMD Ryzen 7 7800X3D', 'OC-2026-442', 'Marco Tulio Reyes'],
    ['MOV-1004', date('Y-m-d H:i:s', $now - 50000), 'DF-MON-01', 'Ingreso por compra', 5, 0, 5, 'Ingreso monitores Samsung Neo G9 49"', 'OC-2026-443', 'Marco Tulio Reyes'],
    ['MOV-1005', date('Y-m-d H:i:s', $now - 40000), 'DF-LAP-06', 'Venta y despacho', -6, 6, 0, 'Despacho masivo corporativo (Agotado)', 'PED-2026-0039', 'Marco Tulio Reyes'],
    ['MOV-1006', date('Y-m-d H:i:s', $now - 35000), 'DF-MON-05', 'Venta y despacho', -3, 3, 0, 'Despacho estudio de diseño (Agotado)', 'PED-2026-0036', 'Marco Tulio Reyes'],
    ['MOV-1007', date('Y-m-d H:i:s', $now - 30000), 'DF-CMP-08', 'Venta y despacho', -2, 2, 0, 'Despacho clientes entusiastas RTX 4090 (Agotado)', 'PED-2026-0034', 'Marco Tulio Reyes'],
    ['MOV-1008', date('Y-m-d H:i:s', $now - 25000), 'DF-PRF-06', 'Venta y despacho', -2, 2, 0, 'Despacho sillas Herman Miller (Agotado)', 'PED-2026-0031', 'Marco Tulio Reyes'],
    ['MOV-1009', date('Y-m-d H:i:s', $now - 12000), 'DF-LAP-01', 'Despacho de pedido', -1, 15, 14, 'Despacho pedido cliente PED-2026-0045', 'PED-2026-0045', 'Marco Tulio Reyes'],
    ['MOV-1010', date('Y-m-d H:i:s', $now - 10000), 'DF-CMP-01', 'Despacho de pedido', -1, 8, 7, 'Despacho pedido cliente PED-2026-0048', 'PED-2026-0048', 'Marco Tulio Reyes'],
    ['MOV-1011', date('Y-m-d H:i:s', $now - 8000),  'DF-CMP-07', 'Ajuste inventario', -4, 6, 2, 'Reserva para ensambles de taller (Stock bajo)', 'AJ-2026-09', 'Marco Tulio Reyes'],
    ['MOV-1012', date('Y-m-d H:i:s', $now - 4000),  'DF-LAP-04', 'Despacho de pedido', -2, 5, 3, 'Despacho pedido cliente PED-2026-0041', 'PED-2026-0041', 'Marco Tulio Reyes'],
];
$stmtMov = $pdo->prepare("
    INSERT INTO `movimientos_inventario` (`id`, `ts`, `sku`, `tipo`, `delta`, `stock_antes`, `stock_despues`, `motivo`, `ref`, `usuario`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($movimientos as $m) {
    $stmtMov->execute($m);
}
echo "✓ Kardex de movimientos de inventario de cómputo actualizado.\n";

// 9. Metas de negocio actualizadas
$metas = [
    'ingresosMes'   => 250000,
    'pedidosMes'    => 40,
    'ticketMedio'   => 4500,
    'conversionWeb' => 78,
    'otif'          => 92,
    'horasCiclo'    => 24
];
$stmtMeta = $pdo->prepare("INSERT INTO `kpi_metas` (`id`, `metas_json`) VALUES (1, ?) ON DUPLICATE KEY UPDATE `metas_json` = VALUES(`metas_json`)");
$stmtMeta->execute([json_encode($metas, JSON_UNESCAPED_UNICODE)]);

// 10. Bitácora de evento
$bStmt = $pdo->prepare("INSERT INTO `bitacora` (`ts`, `user`, `action`, `detail`) VALUES (?, ?, ?, ?)");
$bStmt->execute([
    date('Y-m-d H:i:s'),
    'sistema',
    'catalog.migrated',
    'Catálogo migrado a Cómputo & Hardware con 28 productos reales, pedidos, stock, kardex y métricas Prometheus.'
]);

echo "=== MIGRACIÓN A CÓMPUTO & HARDWARE COMPLETADA CON ÉXITO ===\n";

