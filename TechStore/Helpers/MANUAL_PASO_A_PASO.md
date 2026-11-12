# 📘 Manual de Operación, Exposición & Portabilidad: Tech Store + Prometheus + Grafana

**Proyecto:** Observabilidad Telemática de la Gestión de Pedidos E-commerce & Portal Operativo  
**Tienda Oficial:** **Tech Store Guatemala** (`http://localhost:8085/tienda`)  
**Curso:** Introducción a la Telemática — **Grupo #3**  
**Integrantes:**
- Carlos Daniel López Hernández (`096-19-5203`) — Desarrollador / DevOps & Infraestructura
- Victor Javier Castillo (`092-19-7011`) — DBA (Administrador de Base de Datos)
- Oscar Geovany Ortíz Barillas (`092-17-6969`) — Desarrollador (Frontend & Lógica de Negocio)
- Emmanuel Alexander Ajsivinac Yoc (`092-20-7654`) — Implementador & Líder de Requerimiento / QA

---

## 🔍 1. ¿Cómo saber que la tienda Tech Store está conectada con Prometheus y Grafana?

Para responder y demostrar ante el jurado o docente que **la tienda en línea está 100% conectada en tiempo real con Prometheus y Grafana**, dispones de **4 evidencias irrefutables**:

### A. La Arquitectura de Telemetría (¿Cómo viajan los datos?)
```
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │  CAPA 1: APLICACIÓN & CLIENTES                                                         │
 │  [Cliente en /tienda]  ──(Compra en línea)──>  [Apache + PHP 8.2 (portal-web:8085)]    │
 └───────────────────────────────────────────────────────────────┬────────────────────────┘
                                                                 │ Guarda pedido y stock
                                                                 ▼
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │  CAPA 2: PERSISTENCIA RELACIONAL                                                       │
 │  [MariaDB 10.11 / MySQL (portal-db:3307)] ──(BD: digyfenix_pulso)                     │
 └───────────────────────────────────────────────────────────────┬────────────────────────┘
                                                                 │ Consulta cada 15 seg
                                                                 ▼
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │  CAPA 3: RECOLECCIÓN & SCRAPING TELEMÁTICO                                             │
 │  [Endpoint RAW: /api.php?action=prometheus]  <──(HTTP Scrape)── [Servidor Prometheus]  │
 │  [Exportador de Negocio Python K01-K10: 9101] <──(HTTP Scrape)── [Puerto 9090]         │
 └───────────────────────────────────────────────────────────────┬────────────────────────┘
                                                                 │ Consulta TSDB PromQL
                                                                 ▼
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │  CAPA 4: VISUALIZACIÓN EJECUTIVA EN TIEMPO REAL                                        │
 │  [Grafana Dashboard 3000]                                                              │
 │   • Tablero 1: Tech Store (Ventas Q 497K, 35 pedidos, 371 hardware, stock)             │
 │   • Tablero 2: Matriz K01 a K10 (Semáforos Verde / Amarillo / Rojo según PPT)          │
 └────────────────────────────────────────────────────────────────────────────────────────┘
```

### B. Evidencia 1: El Endpoint de Métricas de la Tienda
Si abres en tu navegador:
👉 **[http://localhost:8085/api.php?action=prometheus](http://localhost:8085/api.php?action=prometheus)**
Verás las métricas crudas en formato oficial de Prometheus emitidas por la tienda en vivo:
* `pulso_mysql_up 1` *(Conexión viva a MySQL)*
* `techstore_info{tienda="Tech Store Guatemala",bd="digyfenix_pulso",puerto="8085",moneda="GTQ"} 1`
* `pulso_ventas_quetzales_total 497626.40`
* `pulso_pedidos_registrados 35`
* `pulso_inventario_unidades_total 371`
* `pulso_disponibilidad_stock_porcentaje 85.71`

### C. Evidencia 2: El Target Activo en Prometheus (Estado UP)
Si abres la consola de Prometheus:
👉 **[http://localhost:9090/targets](http://localhost:9090/targets)**
Verás el trabajo de recolección **`portal-pulso-mysql`** con estado **`UP`** (en verde), scraping cada 15 segundos hacia `http://portal-web:80/api.php?action=prometheus`.

### D. Evidencia 3: Coincidencia Numérica Exacta al Centavo
* En el **Panel Admin** ([http://localhost:8085/admin](http://localhost:8085/admin)): Ventas = **`Q 497,626.40`** | Pedidos = **`35`**.
* En el **Tablero Grafana** ([http://localhost:3000/d/portal-computo-mysql-g3](http://localhost:3000/d/portal-computo-mysql-g3)): Ventas = **`Q 497,626.40`** | Pedidos = **`35`**.
* Coinciden exactamente porque Grafana lee la base de datos de Tech Store a través de Prometheus.

### E. Evidencia 4: Demostración en Vivo para la Exposición
Para callar cualquier duda de que está conectado:
1. Abre [http://localhost:8085/tienda](http://localhost:8085/tienda) y [http://localhost:3000/d/portal-computo-mysql-g3](http://localhost:3000/d/portal-computo-mysql-g3) en pantallas divididas.
2. Compra un producto en la tienda (o cambia el stock de un producto en el Admin).
3. Espera 10-15 segundos (el ciclo de scrape).
4. **Verás cómo la gráfica de Grafana sube y registra el nuevo pedido automáticamente sin refrescar la página.**

---

## 🚦 2. Matriz Oficial de los 10 KPIs de Negocio (K01 a K10)

Los 10 indicadores clave solicitados están configurados tanto en el **Exportador Prometheus** como en **Grafana** ([http://localhost:3000/d/ecommerce-grupo3-k01-k07](http://localhost:3000/d/ecommerce-grupo3-k01-k07)) y en el **Panel de Administración** ([http://localhost:8085/admin](http://localhost:8085/admin)):

| ID | Indicador | Carril BPMN | Fórmula | Meta | Valor Demo | Semáforo | Decisión / Acción de Negocio |
| :---: | :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **K01** | **Volumen diario de solicitudes** | Cliente | $N^\circ \text{ solicitudes registradas / día}$ | $\ge 100$ | **128** | 🟢 **VERDE** | Mantener pautas y campañas de captación en línea. |
| **K02** | **Calidad de datos del cliente** | Sistema E-commerce | $(\text{Válidas} / \text{Evaluadas}) \times 100$ | $\ge 98\%$ | **96.88%** | 🟡 **AMARILLO** | Ajustar máscaras de validación en NIT, teléfono y dirección. |
| **K03** | **Disponibilidad de stock** | Sistema E-commerce | $(\text{Con stock} / \text{Válidas}) \times 100$ | $\ge 97\%$ | **95.69%** | 🟡 **AMARILLO** | Generar orden de compra de reposición a proveedores. |
| **K04** | **Efectividad de creación de pedidos** | Sistema E-commerce | $(\text{Pedidos creados} / \text{Elegibles}) \times 100$ | $\ge 99\%$ | **99.10%** | 🟢 **VERDE** | Transaccionalidad de pasarela y BD operando al 100%. |
| **K05** | **Tasa de abandono en checkout** *(Nuevo)* | Cliente / E-commerce | $(\text{Abandonados} / \text{Iniciados}) \times 100$ | $\le 20\%$ | **6.25%** | 🟢 **VERDE** | Embudo de compra fluido con mínima fricción de pago. |
| **K06** | **Ticket promedio de compra** *(Nuevo)* | Sistema E-commerce | $\text{Monto facturado} / \text{Pedidos creados}$ | $\ge \text{Q } 250$ | **Q 1,874.12** | 🟢 **VERDE** | Venta de componentes de alto valor (GPUs, procesadores). |
| **K07** | **Liquidaciones a tiempo** | Almacén / Logística | $(\text{En } \le 60\text{ min} / \text{Vencidos}) \times 100$ | $\ge 95\%$ | **96.36%** | 🟢 **VERDE** | Facturación y expediente fiscal expedito en bodega. |
| **K08** | **Tiempo medio de empaque** | Almacén / Logística | $\sum\text{minutos} / \text{Empacados}$ | $\le 30\text{ min}$ | **37.0 min** | 🟡 **AMARILLO** | Reforzar personal en mesas de picking y embalaje. |
| **K09** | **Cumplimiento de despacho en plazo** | Almacén / Logística | $(\text{Despachados } \le 8\text{ h} / \text{Total}) \times 100$ | $\ge 95\%$ | **94.29%** | 🟡 **AMARILLO** | Aumentar frecuencia de salida de rutas de transporte. |
| **K10** | **Lead Time Order-to-Dispatch** *(Nuevo)* | Almacén / Logística | $\sum\text{horas total} / \text{Despachados}$ | $\le 4\text{ h}$ | **3.23 h** | 🟢 **VERDE** | Tiempo total ciclo orden-despacho dentro del SLA. |

---

## 📦 3. Preparación para Exportar los 2 Proyectos a otra PC (Con y Sin Internet)

La estructura del proyecto es **100% autosuficiente y portátil**. Dentro de la carpeta `prometheus/` ya está contenida la tienda `portal/`, de modo que copiando esa sola carpeta te llevas todo.

### Opción A: Despliegue en una PC con Conexión a Internet
1. Copia la carpeta `prometheus/` a tu memoria USB o laptop.
2. Abre **Docker Desktop** en la máquina de destino.
3. Haz doble clic en el archivo:
   👉 **`INICIAR_PROYECTO.bat`**
4. El script creará la red compartida `prometheus_observability_net`, levantará los contenedores de MySQL, Apache, Prometheus, Grafana y Exporter, y abrirá automáticamente el navegador con la tienda y los tableros.

### Opción B: Despliegue en una PC SIN Internet (100% Offline para la Universidad)
Si en el aula o laboratorio no hay internet o está bloqueado:
1. **En esta computadora (donde ya están las imágenes):**
   Haz doble clic en:
   👉 **`EXPORTAR_IMAGENES_OFFLINE.bat`**
   *Este script empaquetará las 7 imágenes de Docker en un archivo llamado `imagenes_docker_offline.tar` (~1.2 GB).*
2. **Copia la carpeta completa `prometheus/`** (incluyendo el archivo `.tar` generado) a tu memoria USB.
3. **En la PC de la exposición:**
   * Abre Docker Desktop.
   * Haz doble clic en:
     👉 **`IMPORTAR_IMAGENES_OFFLINE.bat`**
     *(Cargará las imágenes a Docker en segundos sin descargar nada de internet).*
   * Haz doble clic en:
     👉 **`INICIAR_PROYECTO.bat`**
   * ¡Listo! Todo arrancará de inmediato sin conexión.

---

## 🐳 4. Los Dos Proyectos en Docker Desktop

En la pestaña **Containers** de Docker Desktop verás **DOS grupos independientes**:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 📁 digyfenix-pulso  [▶ Play / ⏸ Pause / ■ Stop]                              │
│   ├── portal-web         (Puerto 8085 -> 80)     Apache + PHP 8.2 (Tech Store)
│   ├── portal-db          (Puerto 3307 -> 3306)   MariaDB 10.11 (MySQL)      │
│   └── portal-phpmyadmin  (Puerto 8086 -> 80)     phpMyAdmin Gestor Visual   │
├─────────────────────────────────────────────────────────────────────────────┤
│ 📁 prometheus        [▶ Play / ⏸ Pause / ■ Stop]                              │
│   ├── grafana            (Puerto 3000 -> 3000)   Tableros Ejecutivos        │
│   ├── prometheus         (Puerto 9090 -> 9090)   Servidor TSDB & PromQL     │
│   ├── ecommerce-exporter (Puerto 9101 -> 9101)   Exportador Negocio K01-K10 │
│   └── node-exporter      (Puerto 9100 -> 9100)   Métricas del Sistema Host  │
└─────────────────────────────────────────────────────────────────────────────┘
```

> [!TIP]
> **Demostración de Resiliencia / Tolerancia a Fallos en Vivo:**
> 1. En Docker Desktop, haz clic en el botón **Stop (■)** del grupo `digyfenix-pulso` (o ejecuta `PAUSAR_PORTAL.bat`).
> 2. Pasa a Grafana ([http://localhost:3000/d/portal-computo-mysql-g3](http://localhost:3000/d/portal-computo-mysql-g3)).
> 3. En menos de 15 segundos, la tarjeta superior cambia a:
>    **🔴 PORTAL DESCONECTADO / DOCKER PAUSADO** y se dispara la alerta de caída en Prometheus.
> 4. Haz clic en **Start (▶)** en Docker Desktop (o ejecuta `INICIAR_PORTAL.bat`).
> 5. Grafana detecta la recuperación y regresa a **🟢 CONECTADO A MYSQL (UP)**.

---

## 🎤 5. Guión Paso a Paso para la Exposición (Estructura de 10 Minutos)

Sigue este guión exacto para impresionar al catedrático y responder con solvencia técnica:

### Minuto 0 a 2: Introducción y Planteamiento del Problema
* *"Buenos días ingeniero y compañeros. Somos el Grupo #3 del curso Introducción a la Telemática. Nuestro proyecto aborda la **Observabilidad Telemática de la Gestión de Pedidos E-commerce** aplicado a una tienda real de cómputo y hardware llamada **Tech Store**."*
* *"En telemática no basta con que un sistema funcione; debemos medir continuamente el estado de la red, los servicios y el impacto en el negocio a través de señales medibles."*

### Minuto 2 a 4: Demostración de la Tienda Tech Store y MySQL
* Abre [http://localhost:8085/tienda](http://localhost:8085/tienda):
  * Muestra el catálogo de 28 productos reales de cómputo (laptops, GPUs RTX 4080, procesadores Ryzen).
  * Explica que el backend está desacoplado en contenedores Docker: Apache PHP 8.2 comunicándose por red interna con MariaDB 10.11 en el puerto 3307.
* Abre [http://localhost:8085/admin](http://localhost:8085/admin):
  * Muestra el panel de administración con telemetría en tiempo real: Ventas acumuladas de Q 497,626.40, 35 pedidos y 371 unidades de hardware.

### Minuto 4 a 7: Demostración de Prometheus y Grafana (Conexión en Vivo)
* Abre [http://localhost:3000/d/portal-computo-mysql-g3](http://localhost:3000/d/portal-computo-mysql-g3):
  * *"Aquí vemos el tablero de Grafana conectado directamente a Tech Store. Observen cómo las ventas totales de Q 497,626.40 y el inventario de Q 1.91M coinciden con la base de datos."*
  * *"Prometheus realiza un raspado (scrape) HTTP cada 15 segundos al endpoint `/api.php?action=prometheus`, convirtiendo las filas de MySQL en series de tiempo TSDB."*
* Abre [http://localhost:3000/d/ecommerce-grupo3-k01-k07](http://localhost:3000/d/ecommerce-grupo3-k01-k07):
  * *"En este segundo tablero visualizamos la **Matriz Oficial de los 10 KPIs** de nuestro diagrama BPMN: K01 a K10."*
  * Muestra los semáforos: K01 verde (128 solicitudes), K02 amarillo (96.88% datos válidos), K03 amarillo (95.69% stock), K05 verde (6.25% abandono), K06 verde (ticket Q 1,874.12) y K10 verde (lead time 3.23 h).

### Minuto 7 a 9: La Prueba de Fuego (Simulación de Caída)
* Ejecuta `PAUSAR_PORTAL.bat` o dale Stop a `digyfenix-pulso` en Docker Desktop.
* Regresa a Grafana:
  * *"Observen la pantalla. Hemos detenido la tienda. En la siguiente ventana de evaluación, Prometheus detecta que el socket cerró conexión y la métrica `pulso_mysql_up` pasa a 0."*
  * Se enciende la alerta roja en Grafana.
* Ejecuta `INICIAR_PORTAL.bat`.
  * La alerta se resuelve sola y regresa a verde.

### Minuto 9 a 10: Conclusión
* *"Con este proyecto demostramos cómo la integración de Prometheus, Grafana, exporters en Python y contenedores Docker permite una observabilidad de 360 grados: desde el paquete TCP a nivel de red hasta las ventas y la logística de entrega de un e-commerce real. Muchas gracias."*

---

## 🔐 6. Credenciales de Acceso Verificadas (100% Funcionales)

### A. Acceso a Módulos del Personal ([http://localhost:8085/login](http://localhost:8085/login))
*(En la pantalla dispones de botones de 1 clic para autocompletar)*:

| Rol / Módulo | Usuario | Contraseña | Nombre Asignado |
| :--- | :---: | :---: | :--- |
| **Administrador General** | **`df_admin`** | **`Fenix@Admin26`** | Valeria Morales |
| **Contact Center** | **`df_contact`** | **`Fenix@Contact26`** | Diego Herrera |
| **Bodega** | **`df_bodega`** | **`Fenix@Bodega26`** | Marco Tulio Reyes |
| **Logística** | **`df_logistica`** | **`Fenix@Logis26`** | Sofía Castellanos |
| **Comercial** | **`df_comercial`** | **`Fenix@Comercial26`** | Andrés Solórzano |
| **Admin Grupo #3** | **`carlos.lopez`** | **`Fenix@Admin26`** | Carlos Daniel López |
| **Comercial Grupo #3** | **`victor.castillo`** | **`Fenix@Admin26`** | Víctor Javier Castillo |
| **Logística Grupo #3** | **`oscar.ortiz`** | **`Fenix@Admin26`** | Oscar Geovany Ortiz |
| **Bodega Grupo #3** | **`emmanuel.ajsivinac`** | **`Fenix@Admin26`** | Emmanuel Ajsivinac |
| **Admin Respaldo** | **`admin`** | **`admin123`** | Administrador General |

### B. Grafana ([http://localhost:3000](http://localhost:3000))
* **Usuario:** `admin` | **Contraseña:** `admin123`
* *(O ingresa con los usuarios nominales `carlos.lopez`, `victor.castillo`, `oscar.ortiz`, `emmanuel.ajsivinac` con clave `Telematica2026*`)*.

### C. phpMyAdmin Gestor Visual MySQL ([http://localhost:8086](http://localhost:8086))
* **Servidor:** `portal-db`
* **Usuario:** `pulso_user` | **Contraseña:** `Pulso@2026`
* **Base de datos:** `digyfenix_pulso`

---

## ⚡ 7. Resumen de Scripts Batch

| Archivo | Función |
| :--- | :--- |
| **`INICIAR_PROYECTO.bat`** | Inicia ambos stacks de Docker y abre la tienda y los tableros en el navegador. |
| **`PAUSAR_PORTAL.bat`** | Detiene **únicamente** la tienda e-commerce para demostrar la detección de caída en Grafana. |
| **`INICIAR_PORTAL.bat`** | Reanuda la tienda e-commerce y restablece las métricas en verde. |
| **`PAUSAR_PROYECTO.bat`** | Detiene todos los contenedores de Docker. |
| **`EXPORTAR_IMAGENES_OFFLINE.bat`** | Empaqueta las imágenes Docker en un archivo `.tar` para llevar a otra PC sin internet. |
| **`IMPORTAR_IMAGENES_OFFLINE.bat`** | Carga las imágenes Docker en la nueva PC sin usar conexión a internet. |
