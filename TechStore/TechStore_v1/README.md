# 🚀 Tech Store Guatemala — E-commerce & Observabilidad PromQL/Grafana (Grupo #3)

**Curso:** Introducción a la Telemática  
**Grupo:** Grupo #3  
**Etapa del Ciclo de Negocio:** Gestión u Operación  
**Proceso Monitoreado:** Gestión operativa end-to-end de pedidos E-commerce (`Cliente / Tienda Web` &rarr; `Sistema / Contact Center` &rarr; `Bodega / Logística`) con métricas reales en MariaDB (`telemetria_eventos` + `pedidos`) exportadas a **Prometheus** y visualizadas en **Grafana**.

---

## 🏗️ Arquitectura General (100% Datos Reales en MySQL)

1. **Tienda & Portal Operativo (`portal/` — PHP 8.2 + Apache + MariaDB 10.11):**
   - Registra eventos reales del flujo de compra en la tabla `telemetria_eventos` (`checkout_iniciado`, `checkout_abandono`, `solicitud_recibida`, `validacion_ok`, `validacion_error`, `stock_ok`, `stock_fallo`, `pedido_creado`, `liquidacion`, `empaque`, `despacho`) y transacciona catálogo, inventario, clientes, pedidos y rutas en MySQL.
   - Expone métricas en tiempo real en `/api.php?action=prometheus` y cálculo de KPIs K01–K10 en `/api.php?action=kpis_reales`.
2. **Exportador K01–K10 (`exporter/exporter.py` — Puerto `9101`):**
   - Opera por defecto en modo **`mysql_en_vivo`**: consulta `/api.php?action=kpis_reales` y `/api.php?action=prometheus` y publica los 10 KPIs + puntos de medición `P1–P7` + estado de enlace MySQL para Prometheus.
   - Incluye un selector manual de escenarios de falla únicamente para demostración de alertas (`K01`–`K10` en Rojo/Amarillo) y botón para regresar a datos reales de MySQL.
3. **Prometheus (`prometheus.yml` + `rules/ecommerce_rules.yml` — Puerto `9090`):**
   - Raspa 5 objetivos (`prometheus`, `node`, `ecommerce-pedidos`, `portal-pulso-mysql`, `grafana`) y evalúa reglas PromQL y alertas para `K01`–`K10`, `PortalWeb_Caido`, `MySQL_Caido` y `Exporter_Caido`.
4. **Grafana (`grafana/` — Puerto `3000`):**
   - 3 dashboards preaprovisionados:
     - `Observabilidad de la Gestión de Pedidos E-commerce — Grupo #3 (K01–K10)` (`ecommerce-grupo3-k01-k07`) — *Home Dashboard*
     - `Tech Store — Observabilidad E-commerce de Cómputo` (`portal-computo-mysql-g3`)
     - `Manual Técnico Prometheus, Node Exporter y Benchmarking Avalanche` (`prometheus-manual-tecnico-g3`)

---

## 👥 Credenciales Oficiales (Portal Web y Grafana Unificados)

| Usuario / Rol | Portal Web (`:8085/login`) | Grafana (`:3000`) | Contraseña | Permisos |
| :--- | :---: | :---: | :---: | :--- |
| **Administrador General** | `admin` | `admin` | `Admin@2026` *(Portal)* / `admin123` *(Grafana)* | Control total Portal & Grafana |
| **Carlos Daniel López** (`096-19-5203`) | `carlos.lopez` | `carlos.lopez` | `Dev1@Grupo3` | Portal: Administrador · Grafana: **Admin** |
| **Victor Javier Castillo** (`092-19-7011`) | `victor.castillo` | `victor.castillo` | `Dba2@Grupo3` | Portal: Comercial · Grafana: **Admin** |
| **Oscar Geovany Ortíz** (`092-17-6969`) | `oscar.ortiz` | `oscar.ortiz` | `Dev3@Grupo3` | Portal: Logística · Grafana: **Editor** |
| **Emmanuel Ajsivinac** (`092-20-7654`) | `emmanuel.ajsivinac` | `emmanuel.ajsivinac` | `Qa4@Grupo3` | Portal: Bodega · Grafana: **Editor** |
| **Cliente Demo Tienda** (`:8085/t-cuenta`) | `cliente@demo.com` | — | `Cliente@2026` | Cuenta cliente de prueba en Tienda Web |

---

## 🌐 Servicios Desplegados y Puertos

| Servicio | Contenedor | URL Local | Descripción |
| :--- | :---: | :--- | :--- |
| **Tienda en Línea Tech Store** | `techstore-web` | [http://localhost:8085/tienda](http://localhost:8085/tienda) | Catálogo público, carrito, checkout y rastreo |
| **Portal Operativo & Admin** | `techstore-web` | [http://localhost:8085/login](http://localhost:8085/login) | Aprobaciones, Bodega, Logística, Comercial y Admin |
| **Métricas RAW PHP + MySQL** | `techstore-web` | [http://localhost:8085/api.php?action=prometheus](http://localhost:8085/api.php?action=prometheus) | Endpoint crudo raspado por Prometheus |
| **Grafana (Home K01–K10)** | `techstore-grafana` | [http://localhost:3000/d/ecommerce-grupo3-k01-k07](http://localhost:3000/d/ecommerce-grupo3-k01-k07) | Dashboards ejecutivos K01–K10 y MySQL |
| **Grafana (Tech Store MySQL)** | `techstore-grafana` | [http://localhost:3000/d/portal-computo-mysql-g3](http://localhost:3000/d/portal-computo-mysql-g3) | Ventas en Quetzales, catálogo, stock y rutas |
| **Prometheus (Targets & Alertas)** | `techstore-prometheus` | [http://localhost:9090/targets](http://localhost:9090/targets) | Targets (5/5 UP), alertas PromQL y TSDB |
| **Exportador MySQL K01–K10** | `techstore-ecommerce-exporter` | [http://localhost:9101](http://localhost:9101) | Panel de telemetría real + simulador de alertas |
| **phpMyAdmin (MariaDB 10.11)** | `techstore-phpmyadmin` | [http://localhost:8086](http://localhost:8086) | Usuario `pulso_user`, clave `Pulso@2026`, host `portal-db` |

---

## 🛠️ Despliegue Rápido (macOS / Linux / Windows)

### macOS / Linux
```bash
chmod +x *.sh
./iniciar.sh
```
Detener todo: `./pausar.sh`  
Simular caída/recuperación solo del portal web + MySQL: `./pausar_portal.sh` y `./iniciar_portal.sh`.

### Windows
- Iniciar proyecto completo: `INICIAR_PROYECTO.bat`
- Pausar proyecto completo: `PAUSAR_PROYECTO.bat`
- Simular caída / reanudación del portal: `PAUSAR_PORTAL.bat` / `INICIAR_PORTAL.bat`

### Validación técnica y Benchmark (Avalanche ~10,000 series)
```bash
# Validar configuración y reglas PromQL
docker exec techstore-prometheus promtool check config /etc/prometheus/prometheus.yml
docker exec techstore-prometheus promtool check rules /etc/prometheus/rules/ecommerce_rules.yml

# Iniciar carga sintética Avalanche
docker compose -f docker-compose.yml -f docker-compose.benchmark.yml up -d avalanche

# Detener y eliminar Avalanche sin afectar la observabilidad
docker compose -f docker-compose.yml -f docker-compose.benchmark.yml rm -sf avalanche
```
