# Tech Store — Resumen de Auditoría, Correcciones y Telemetría Real en MySQL

**Proyecto Final de Telemática — Grupo #3 (Universidad Mariano Gálvez)**  
**Carpeta de producción:** `../TechStore_v1/` (estrictamente limpia para despliegue y control de versiones).

---

## 1. Arquitectura Final (100 % Datos Reales en Vivo)

```text
  [Tienda / Portal Web PHP :8085]  ──(PDO SQL)──▶  [MariaDB 10.11 :3307 (host interno portal-db:3306)]
        │  │                                              ├─ productos (28 SKUs de cómputo)
        │  │                                              ├─ pedidos (48+ registros reales)
        │  │                                              ├─ rutas, vehiculos, clientes, usuarios, bitacora
        │  │                                              └─ telemetria_eventos (P1–P7 y checkout)
        │  │
        │  ├── GET /api.php?action=prometheus  ──▶ Scraped cada 5s por Prometheus (job: portal-pulso-mysql)
        │  └── GET /api.php?action=kpis_reales
        │               ▲
        ▼               │ (Poll cada 2s)
  [Exporter Python :9101] (modo por defecto: mysql_en_vivo)
        │
        └── GET /metrics  ──▶ Scraped cada 5s por Prometheus (job: ecommerce-pedidos)
                                    │
                                    ▼ (31 reglas PromQL en rules/ecommerce_rules.yml)
                             [Prometheus :9090] ──▶ [Grafana :3000]
                                                       ├─ ecommerce-grupo3-k01-k07 (Home, K01–K10)
                                                       ├─ portal-computo-mysql-g3 (MySQL & Portal)
                                                       └─ prometheus-manual-tecnico-g3 (TSDB & Host)
```

---

## 2. Problemas Detectados y Solucionados

1. **Telemetría y KPIs simulados (`exporter/exporter.py`, `state.json`, `comercial.js`)**:
   - Eliminada la lectura de archivos JSON estáticos de XAMPP (`state.json` archivado en `Helpers/xampp_storage/`).
   - Implementada la tabla `telemetria_eventos` en MySQL + librería `portal/config/telemetry.php` + funciones `recordTelemetryEvent()` y `computeRealKpisFromPdo()` en `portal/config/db.php`.
   - `exporter/exporter.py` inicia ahora en `mode = "mysql_en_vivo"` leyendo `PORTAL_URL/api.php?action=kpis_reales` y `action=prometheus`, conservando los presets de simulación únicamente como pruebas temporales de alertas con botón de retorno a **Datos Reales (MySQL)**.

2. **Seguridad y control de acceso (`api.php`, `.htaccess`, `bd-inventario.php`, `adm-*`, `portal/uploads/.htaccess`)**:
   - `reset_demo` en `api.php` ahora exige sesión PHP activa con rol `Administrador` (devuelve HTTP `403` sin autorización).
   - Validación server-side de estructura y extensión de imágenes subidas en `portal/uploads/productos/` y bloqueo explícito de ejecución PHP dentro de `uploads/` mediante `portal/uploads/.htaccess`.
   - Bloqueo HTTP `403` mediante `portal/.htaccess` para archivos sensibles (`/config/*`, `/sql/*`, `Dockerfile`, `.htaccess`, archivos ocultos).
   - Respaldo real en MySQL para ajustes de inventario en `bd-inventario.php`.

3. **Unificación de credenciales Grupo #3 y corrección de hash (`db.php`, `session.js`, `grafana/init/setup_users.py`)**:
   - Unificadas las contraseñas en Portal Web (`:8085`) y Grafana (`:3000`): `Dev1@Grupo3`, `Dba2@Grupo3`, `Dev3@Grupo3`, `Qa4@Grupo3` (+ `admin` / `Admin@2026` en Portal y `admin` / `admin123` en Grafana).
   - Corregido el hash SHA-256 truncado a 63 caracteres de `victor.castillo` (faltaba el último `1`) en `db.php` y `session.js`, más migración automática para volúmenes existentes.
   - `setup_users.py` sincroniza también contraseñas y roles de usuarios preexistentes en Grafana y establece `ecommerce-grupo3-k01-k07` como dashboard inicial.

4. **Correcciones de interfaz, contraste y errores JS en tiempo de ejecución**:
   - Restaurado el contraste legible (tema claro limpio + variables CSS coherentes) en las vistas de tienda, login, bodega, logística, contact center, comercial y administración.
   - Corregido el error `TypeError` en `cm-tablero.php`, `cm-ventas.php` y `cm-operacion.php` provocado por `$("year").textContent` antes de renderizar `includes/footer.php`.
   - Corregido el error `TypeError: Cannot read properties of undefined` sobre `r.carga.picks` en `bd-cargas.php` y `bd-picking.php`, y normalizada la hidratación de `picks` y `cargados` de `rutas` en `portal/config/db.php`.

5. **Infraestructura Docker, Prometheus y Grafana**:
   - Renombrados todos los contenedores del proyecto a `techstore-*` con `aliases` internos para evitar colisiones con otros proyectos/contenedores previos en Docker.
   - Corregido el mapeo invertido `0/1/2` en el Panel 13 de `ecommerce_negocio_k01_k07.json` (`0 = ROJO`, `1 = AMARILLO`, `2 = VERDE`), eliminados IDs duplicados de paneles y ajustado el Panel 13 de `portal_computo_observabilidad.json` a `bargauge` para mostrar los estados operativos con su nombre correcto.
   - Reglas de Prometheus ampliadas a **31 reglas** (K01–K10 + salud de portal/MySQL/exporter) con `scrape_interval: 5s`.

---

## 3. Verificación Final Realizada

- **Contenedores activos (`techstore-*`)**: 7/7 levantados y saludables.
- **Prometheus**: 5/5 targets `UP` y 31/31 reglas evaluadas sin error (`promtool check config` y `promtool check rules` superados).
- **Sintaxis**: 100 % de archivos `.php` (`php -l`), `.js` (`node --check`), `.py` (`py_compile`) y `.json` (`json.load`) validados sin errores.
- **Seguridad y Autenticación**: 5/5 inicios de sesión en Portal Web + 5/5 en Grafana verificados; bloqueo HTTP `403` en rutas sensibles y `reset_demo` sin sesión verificado.
- **Auditoría completa en navegador**: 24 rutas internas del portal + storefront público (`/tienda`, `/t-carrito`, `/t-cuenta`) + tableros de Grafana verificados con **0 errores de consola JS**.
