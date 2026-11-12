#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
EXPORTADOR PROMETHEUS + PANEL DE CONTROL DE TELEMETRÍA K01–K10 (MYSQL EN VIVO + SIMULADOR)
Proyecto Grupal: Observabilidad de la Gestión de Pedidos E-commerce
Curso: Introducción a la Telemática — Grupo #3
Integrantes:
  1. Carlos Daniel López Hernández (096-19-5203) - Desarrollador / DevOps
  2. Victor Javier Castillo (092-19-7011) - DBA
  3. Oscar Geovany Ortíz Barillas (092-17-6969) - Desarrollador
  4. Emmanuel Alexander Ajsivinac Yoc (092-20-7654) - Implementador y Líder de Requerimiento de Software
"""

import json
import os
import threading
import time
import urllib.parse
import urllib.request
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

PORT = int(os.environ.get("EXPORTER_PORT", "9101"))
PORTAL_URL = os.environ.get("PORTAL_URL", "http://portal-web:80").rstrip("/")
CACHE_TTL_SEC = float(os.environ.get("EXPORTER_CACHE_TTL", "2.0"))

# Integrantes oficiales del Grupo #3 con credenciales unificadas (Portal Web y Grafana)
TEAM_MEMBERS = [
    {
        "nombre": "Carlos Daniel López Hernández",
        "carne": "096-19-5203",
        "usuario": "carlos.lopez",
        "clave": "Dev1@Grupo3",
        "email": "clopez@telematica.edu.gt",
        "rol": "Desarrollador / DevOps",
        "rol_portal": "Administrador",
        "rol_grafana": "Admin",
        "carril_principal": "Infraestructura Docker / Sistema E-commerce",
        "descripcion": "Especialista en infraestructura web, gestión de servidores, seguridad, despliegue con contenedores Docker y comercio electrónico."
    },
    {
        "nombre": "Victor Javier Castillo",
        "carne": "092-19-7011",
        "usuario": "victor.castillo",
        "clave": "Dba2@Grupo3",
        "email": "vcastillo@telematica.edu.gt",
        "rol": "DBA (Administrador de Base de Datos)",
        "rol_portal": "Comercial",
        "rol_grafana": "Admin",
        "carril_principal": "Sistema E-commerce (Inventario y Datos)",
        "descripcion": "Especialista en mantenimiento, seguridad y optimización de arquitecturas de bases de datos con habilidades analíticas orientadas al negocio."
    },
    {
        "nombre": "Oscar Geovany Ortíz Barillas",
        "carne": "092-17-6969",
        "usuario": "oscar.ortiz",
        "clave": "Dev3@Grupo3",
        "email": "oortiz@telematica.edu.gt",
        "rol": "Desarrollador",
        "rol_portal": "Logística",
        "rol_grafana": "Editor",
        "carril_principal": "Cliente & Sistema E-commerce",
        "descripcion": "Experiencia en el ciclo completo de construcción de software (web), diseño de soluciones y soporte de lógica de negocio."
    },
    {
        "nombre": "Emmanuel Alexander Ajsivinac Yoc",
        "carne": "092-20-7654",
        "usuario": "emmanuel.ajsivinac",
        "clave": "Qa4@Grupo3",
        "email": "eajsivinac@telematica.edu.gt",
        "rol": "Implementador y Líder de Requerimiento de Software",
        "rol_portal": "Bodega",
        "rol_grafana": "Editor",
        "carril_principal": "Almacén / Logística & QA Operativo",
        "descripcion": "Encargado de velar que la implementación cumpla con lo esperado por el cliente (QA, Assessment, Certificación y Pase a producción)."
    }
]

# Escenarios opcionales de prueba manual para demostrar disparos de alertas en Prometheus/Grafana
SCENARIOS = {
    "operacion_optima": {
        "nombre": "Simulación: Operación Óptima (Todos K01–K10 en VERDE)",
        "descripcion": "Demanda >= 100, formularios >= 98%, stock >= 97%, pedidos >= 99%, abandono <= 20%, ticket >= Q 250, liquidaciones >= 95%, empaque <= 30m, despacho >= 95%, lead time <= 4h.",
        "p1_solicitudes_registradas": 145,
        "p2_solicitudes_evaluadas": 145,
        "p2_solicitudes_datos_validos": 143,
        "p3_base_validas": 143,
        "p3_solicitudes_con_stock": 140,
        "p4_solicitudes_elegibles": 140,
        "p4_pedidos_creados": 139,
        "checkout_iniciados": 140,
        "checkout_abandonados": 8,
        "monto_facturado_quetzales": 325400.00,
        "pedidos_con_monto": 139,
        "p5_liquidaciones_plazo_vencido": 139,
        "p5_liquidaciones_en_plazo": 136,
        "p6_pedidos_empacados": 135,
        "p6_tiempo_preparacion_empaque_minutos_suma": 2970.0,
        "p7_despachos_plazo_vencido": 135,
        "p7_despachos_en_plazo": 132,
        "despachos_lead_time_count": 132,
        "tiempo_order_to_dispatch_horas_suma": 316.8
    },
    "caso_demostrativo": {
        "nombre": "Simulación: Caso Base Mixto (Verde + Amarillo)",
        "descripcion": "Escenario de referencia mixto: K01=128, K02=96.88%, K03=95.69%, K04=99.10%, K05=6.25%, K06=Q 1,874.12, K07=96.36%, K08=37.0 min, K09=94.29%, K10=3.23 h.",
        "p1_solicitudes_registradas": 128,
        "p2_solicitudes_evaluadas": 128,
        "p2_solicitudes_datos_validos": 124,
        "p3_base_validas": 116,
        "p3_solicitudes_con_stock": 111,
        "p4_solicitudes_elegibles": 111,
        "p4_pedidos_creados": 110,
        "checkout_iniciados": 112,
        "checkout_abandonados": 7,
        "monto_facturado_quetzales": 206153.20,
        "pedidos_con_monto": 110,
        "p5_liquidaciones_plazo_vencido": 110,
        "p5_liquidaciones_en_plazo": 106,
        "p6_pedidos_empacados": 105,
        "p6_tiempo_preparacion_empaque_minutos_suma": 3885.0,
        "p7_despachos_plazo_vencido": 105,
        "p7_despachos_en_plazo": 99,
        "despachos_lead_time_count": 99,
        "tiempo_order_to_dispatch_horas_suma": 319.77
    },
    "quiebre_stock_critico": {
        "nombre": "Simulación Alerta: Quiebre de Stock y Retraso Logístico (ROJO)",
        "descripcion": "Dispara alertas ROJAS en K03 (<90%), K05 (>30%), K07 (<85%), K08 (>45m) y K09 (<85%).",
        "p1_solicitudes_registradas": 115,
        "p2_solicitudes_evaluadas": 115,
        "p2_solicitudes_datos_validos": 112,
        "p3_base_validas": 112,
        "p3_solicitudes_con_stock": 95,
        "p4_solicitudes_elegibles": 95,
        "p4_pedidos_creados": 92,
        "checkout_iniciados": 95,
        "checkout_abandonados": 31,
        "monto_facturado_quetzales": 98400.00,
        "pedidos_con_monto": 92,
        "p5_liquidaciones_plazo_vencido": 92,
        "p5_liquidaciones_en_plazo": 75,
        "p6_pedidos_empacados": 80,
        "p6_tiempo_preparacion_empaque_minutos_suma": 3880.0,
        "p7_despachos_plazo_vencido": 80,
        "p7_despachos_en_plazo": 62,
        "despachos_lead_time_count": 62,
        "tiempo_order_to_dispatch_horas_suma": 410.0
    },
    "alerta_cliente_validacion": {
        "nombre": "Simulación Alerta: Caída de Demanda y Errores de Cliente (ROJO)",
        "descripcion": "Dispara alertas ROJAS en K01 (<70), K02 (<95%), K05 (>30%) y K06 (< Q 150).",
        "p1_solicitudes_registradas": 64,
        "p2_solicitudes_evaluadas": 64,
        "p2_solicitudes_datos_validos": 57,
        "p3_base_validas": 57,
        "p3_solicitudes_con_stock": 56,
        "p4_solicitudes_elegibles": 56,
        "p4_pedidos_creados": 56,
        "checkout_iniciados": 56,
        "checkout_abandonados": 19,
        "monto_facturado_quetzales": 7280.00,
        "pedidos_con_monto": 56,
        "p5_liquidaciones_plazo_vencido": 55,
        "p5_liquidaciones_en_plazo": 54,
        "p6_pedidos_empacados": 54,
        "p6_tiempo_preparacion_empaque_minutos_suma": 1404.0,
        "p7_despachos_plazo_vencido": 54,
        "p7_despachos_en_plazo": 53,
        "despachos_lead_time_count": 53,
        "tiempo_order_to_dispatch_horas_suma": 169.6
    }
}

LOCK = threading.Lock()
STATE = {
    "mode": "mysql_en_vivo",          # Modo por defecto: telemetría real desde MySQL (cero datos ficticios)
    "active_scenario": "mysql_en_vivo",
    "custom_data": dict(SCENARIOS["caso_demostrativo"]),
    "scrapes_total": 0,
    "started_at": time.time(),
    "cache_kpis": None,
    "cache_kpis_ts": 0.0,
    "cache_prom": None,
    "cache_prom_ts": 0.0,
    "last_mysql_error": "",
}


def compute_kpis_from_dict(d):
    p1 = float(d.get("p1_solicitudes_registradas", 0))
    p2_val = float(d.get("p2_solicitudes_datos_validos", 0))
    p2_eval = float(d.get("p2_solicitudes_evaluadas", 0))
    p2_inv = max(p2_eval - p2_val, 0.0)

    p3_base = float(d.get("p3_base_validas", 0))
    p3_stock = float(d.get("p3_solicitudes_con_stock", 0))
    p3_nostock = max(p3_base - p3_stock, 0.0)

    p4_eleg = float(d.get("p4_solicitudes_elegibles", 0))
    p4_creados = float(d.get("p4_pedidos_creados", 0))
    p4_fallidos = max(p4_eleg - p4_creados, 0.0)

    checkout_ini = float(d.get("checkout_iniciados", 0))
    checkout_abn = float(d.get("checkout_abandonados", 0))

    monto_fact = float(d.get("monto_facturado_quetzales", 0.0))
    ped_monto_ref = float(d.get("pedidos_con_monto", p4_creados))

    p5_venc = float(d.get("p5_liquidaciones_plazo_vencido", 0))
    p5_ok = float(d.get("p5_liquidaciones_en_plazo", 0))
    p5_tarde = max(p5_venc - p5_ok, 0.0)

    p6_emp = float(d.get("p6_pedidos_empacados", 0))
    p6_min_sum = float(d.get("p6_tiempo_preparacion_empaque_minutos_suma", 0.0))

    p7_venc = float(d.get("p7_despachos_plazo_vencido", 0))
    p7_ok = float(d.get("p7_despachos_en_plazo", 0))
    p7_tarde = max(p7_venc - p7_ok, 0.0)

    desp_lead_count = float(d.get("despachos_lead_time_count", p7_venc))
    horas_lead_sum = float(d.get("tiempo_order_to_dispatch_horas_suma", 0.0))

    k01 = p1
    k02 = (p2_val / p2_eval) * 100.0 if p2_eval > 0 else 0.0
    k03 = (p3_stock / p3_base) * 100.0 if p3_base > 0 else 0.0
    k04 = (p4_creados / p4_eleg) * 100.0 if p4_eleg > 0 else 0.0
    k05 = (checkout_abn / checkout_ini) * 100.0 if checkout_ini > 0 else 0.0
    k06 = (monto_fact / ped_monto_ref) if ped_monto_ref > 0 else 0.0
    k07 = (p5_ok / p5_venc) * 100.0 if p5_venc > 0 else 0.0
    k08 = (p6_min_sum / p6_emp) if p6_emp > 0 else 0.0
    k09 = (p7_ok / p7_venc) * 100.0 if p7_venc > 0 else 0.0
    k10 = (horas_lead_sum / desp_lead_count) if desp_lead_count > 0 else 0.0

    return {
        "fuente": "simulacion",
        "p1": p1,
        "p1_total": p1,
        "p2_eval": p2_eval,
        "p2_val": p2_val,
        "p2_inv": p2_inv,
        "p3_base": p3_base,
        "p3_stock": p3_stock,
        "p3_nostock": p3_nostock,
        "p4_eleg": p4_eleg,
        "p4_creados": p4_creados,
        "p4_fallidos": p4_fallidos,
        "checkout_ini": checkout_ini,
        "checkout_abn": checkout_abn,
        "monto_fact": monto_fact,
        "ped_monto_ref": ped_monto_ref,
        "p5_venc": p5_venc,
        "p5_ok": p5_ok,
        "p5_tarde": p5_tarde,
        "p6_emp": p6_emp,
        "p6_min_sum": p6_min_sum,
        "p7_venc": p7_venc,
        "p7_ok": p7_ok,
        "p7_tarde": p7_tarde,
        "desp_lead_count": desp_lead_count,
        "horas_lead_sum": horas_lead_sum,
        "k01": k01,
        "k02": k02,
        "k03": k03,
        "k04": k04,
        "k05": k05,
        "k06": k06,
        "k07": k07,
        "k08": k08,
        "k09": k09,
        "k10": k10,
        "sem_k01": 2 if k01 >= 100.0 else (1 if k01 >= 70.0 else 0),
        "sem_k02": 2 if k02 >= 98.0 else (1 if k02 >= 95.0 else 0),
        "sem_k03": 2 if k03 >= 97.0 else (1 if k03 >= 90.0 else 0),
        "sem_k04": 2 if k04 >= 99.0 else (1 if k04 >= 97.0 else 0),
        "sem_k05": 2 if k05 <= 20.0 else (1 if k05 <= 30.0 else 0),
        "sem_k06": 2 if k06 >= 250.0 else (1 if k06 >= 150.0 else 0),
        "sem_k07": 2 if k07 >= 95.0 else (1 if k07 >= 85.0 else 0),
        "sem_k08": 2 if k08 <= 30.0 else (1 if k08 <= 45.0 else 0),
        "sem_k09": 2 if k09 >= 95.0 else (1 if k09 >= 85.0 else 0),
        "sem_k10": 2 if k10 <= 4.0 else (1 if k10 <= 6.0 else 0),
        "updated_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
    }


def fetch_real_kpis_from_portal(force=False):
    now = time.time()
    with LOCK:
        if not force and STATE["cache_kpis"] is not None and (now - STATE["cache_kpis_ts"]) < CACHE_TTL_SEC:
            return STATE["cache_kpis"], STATE["last_mysql_error"]

    err_msg = ""
    data = None
    try:
        url = f"{PORTAL_URL}/api.php?action=kpis_reales"
        req = urllib.request.Request(url, headers={"User-Agent": "TechStoreExporter/4.0"})
        with urllib.request.urlopen(req, timeout=3.0) as resp:
            if resp.status == 200:
                payload = json.loads(resp.read().decode("utf-8"))
                if payload.get("ok") and isinstance(payload.get("kpis"), dict):
                    data = payload["kpis"]
                else:
                    err_msg = str(payload.get("error", "Respuesta inesperada desde api.php?action=kpis_reales"))
            else:
                err_msg = f"HTTP {resp.status} desde {url}"
    except Exception as exc:
        err_msg = str(exc)

    with LOCK:
        STATE["last_mysql_error"] = err_msg
        if data is not None:
            STATE["cache_kpis"] = data
            STATE["cache_kpis_ts"] = now
        elif not err_msg:
            err_msg = "Sin datos disponibles"
    return data, err_msg


def read_portal_mysql_metrics(force=False):
    """Lee métricas operativas generales desde /api.php?action=prometheus."""
    now = time.time()
    with LOCK:
        if not force and STATE["cache_prom"] is not None and (now - STATE["cache_prom_ts"]) < CACHE_TTL_SEC:
            return STATE["cache_prom"]

    res = None
    try:
        url = f"{PORTAL_URL}/api.php?action=prometheus"
        req = urllib.request.Request(url, headers={"User-Agent": "TechStoreExporter/4.0"})
        with urllib.request.urlopen(req, timeout=3.0) as resp:
            if resp.status == 200:
                text = resp.read().decode("utf-8")
                parsed = {}
                for line in text.splitlines():
                    line = line.strip()
                    if not line or line.startswith("#"):
                        continue
                    parts = line.split()
                    if len(parts) >= 2:
                        k = parts[0]
                        try:
                            parsed[k] = float(parts[-1])
                        except ValueError:
                            pass
                res = parsed
    except Exception:
        res = None

    with LOCK:
        if res is not None:
            STATE["cache_prom"] = res
            STATE["cache_prom_ts"] = now
    return res


def empty_kpis_snapshot():
    return {
        "fuente": "mysql_sin_conexion",
        "p1": 0.0, "p1_total": 0.0,
        "p2_eval": 0.0, "p2_val": 0.0, "p2_inv": 0.0,
        "p3_base": 0.0, "p3_stock": 0.0, "p3_nostock": 0.0,
        "p4_eleg": 0.0, "p4_creados": 0.0, "p4_fallidos": 0.0,
        "checkout_ini": 0.0, "checkout_abn": 0.0,
        "monto_fact": 0.0, "ped_monto_ref": 0.0,
        "p5_venc": 0.0, "p5_ok": 0.0, "p5_tarde": 0.0,
        "p6_emp": 0.0, "p6_min_sum": 0.0,
        "p7_venc": 0.0, "p7_ok": 0.0, "p7_tarde": 0.0,
        "desp_lead_count": 0.0, "horas_lead_sum": 0.0,
        "k01": 0.0, "k02": 0.0, "k03": 0.0, "k04": 0.0, "k05": 0.0,
        "k06": 0.0, "k07": 0.0, "k08": 0.0, "k09": 0.0, "k10": 0.0,
        "sem_k01": 0, "sem_k02": 0, "sem_k03": 0, "sem_k04": 0, "sem_k05": 0,
        "sem_k06": 0, "sem_k07": 0, "sem_k08": 0, "sem_k09": 0, "sem_k10": 0,
        "updated_at": "",
    }


def get_effective_state():
    with LOCK:
        mode = STATE["mode"]
        active_sc = STATE["active_scenario"]
        custom = dict(STATE["custom_data"])

    if mode == "mysql_en_vivo":
        real_kpis, err = fetch_real_kpis_from_portal()
        if real_kpis is not None:
            return {
                "mode": "mysql_en_vivo",
                "active_scenario": "mysql_en_vivo",
                "mysql_online": True,
                "error": "",
                "metrics": real_kpis,
            }
        # Fallback transparente cuando MySQL aún está arrancando o detenido: nunca fingir datos demo
        return {
            "mode": "mysql_en_vivo",
            "active_scenario": "mysql_en_vivo",
            "mysql_online": False,
            "error": err,
            "metrics": empty_kpis_snapshot(),
        }

    return {
        "mode": "simulacion",
        "active_scenario": active_sc,
        "mysql_online": True,
        "error": "",
        "metrics": compute_kpis_from_dict(custom),
    }


def generate_prometheus_metrics():
    with LOCK:
        STATE["scrapes_total"] += 1
        scrapes_total = STATE["scrapes_total"]

    eff = get_effective_state()
    m = eff["metrics"]
    active_sc = eff["active_scenario"]
    is_live = (eff["mode"] == "mysql_en_vivo")
    portal = read_portal_mysql_metrics()

    lines = []

    # 1. Integrantes del Grupo #3
    lines.append("# HELP ecommerce_equipo_integrante_info Integrantes oficiales del Grupo #3 - Introduccion a la Telematica")
    lines.append("# TYPE ecommerce_equipo_integrante_info gauge")
    for idx, member in enumerate(TEAM_MEMBERS, start=1):
        lines.append(
            f'ecommerce_equipo_integrante_info{{orden="{idx}",nombre="{member["nombre"]}",'
            f'carne="{member["carne"]}",usuario_grafana="{member["usuario"]}",'
            f'email="{member["email"]}",rol="{member["rol"]}",'
            f'rol_grafana="{member["rol_grafana"]}",carril="{member["carril_principal"]}",'
            f'grupo="Grupo #3",curso="Introduccion a la Telematica"}} 1'
        )

    # 2. Fuente de datos & Escenario activo
    lines.append("# HELP ecommerce_modo_fuente Fuente de datos actualmente activa en el exportador (1=activo)")
    lines.append("# TYPE ecommerce_modo_fuente gauge")
    lines.append(f'ecommerce_modo_fuente{{modo="mysql_en_vivo"}} {1 if is_live else 0}')
    lines.append(f'ecommerce_modo_fuente{{modo="simulacion"}} {0 if is_live else 1}')

    lines.append("# HELP ecommerce_escenario_activo_info Escenario o modo actualmente activo en el exportador")
    lines.append("# TYPE ecommerce_escenario_activo_info gauge")
    lines.append(f'ecommerce_escenario_activo_info{{escenario="mysql_en_vivo",nombre="Datos Reales en Vivo (MySQL)"}} {1 if active_sc == "mysql_en_vivo" else 0}')
    for key, sc in SCENARIOS.items():
        val = 1 if active_sc == key else 0
        lines.append(f'ecommerce_escenario_activo_info{{escenario="{key}",nombre="{sc["nombre"]}"}} {val}')

    # 3. Puntos de Medición Base (P1 - P7)
    lines.append("# HELP ecommerce_p1_solicitudes_registradas_total P1: Solicitudes registradas en ultimas 24h")
    lines.append("# TYPE ecommerce_p1_solicitudes_registradas_total gauge")
    lines.append(f'ecommerce_p1_solicitudes_registradas_total{{punto="P1",carril="Cliente"}} {float(m.get("p1", 0))}')

    lines.append("# HELP ecommerce_p1_solicitudes_acumuladas_total P1 historico: Total acumulado de solicitudes en MySQL")
    lines.append("# TYPE ecommerce_p1_solicitudes_acumuladas_total gauge")
    lines.append(f'ecommerce_p1_solicitudes_acumuladas_total{{punto="P1",carril="Cliente"}} {float(m.get("p1_total", m.get("p1", 0)))}')

    lines.append("# HELP ecommerce_p2_solicitudes_evaluadas_total P2: Solicitudes evaluadas")
    lines.append("# TYPE ecommerce_p2_solicitudes_evaluadas_total gauge")
    lines.append(f'ecommerce_p2_solicitudes_evaluadas_total{{punto="P2",carril="Sistema E-commerce"}} {float(m.get("p2_eval", 0))}')

    lines.append("# HELP ecommerce_p2_solicitudes_datos_validos_total P2: Solicitudes con datos validos")
    lines.append("# TYPE ecommerce_p2_solicitudes_datos_validos_total gauge")
    lines.append(f'ecommerce_p2_solicitudes_datos_validos_total{{punto="P2",carril="Sistema E-commerce"}} {float(m.get("p2_val", 0))}')

    lines.append("# HELP ecommerce_p3_solicitudes_con_stock_total P3: Solicitudes con stock disponible")
    lines.append("# TYPE ecommerce_p3_solicitudes_con_stock_total gauge")
    lines.append(f'ecommerce_p3_solicitudes_con_stock_total{{punto="P3",carril="Sistema E-commerce"}} {float(m.get("p3_stock", 0))}')

    lines.append("# HELP ecommerce_p4_pedidos_creados_total P4: Pedidos creados")
    lines.append("# TYPE ecommerce_p4_pedidos_creados_total gauge")
    lines.append(f'ecommerce_p4_pedidos_creados_total{{punto="P4",carril="Sistema E-commerce"}} {float(m.get("p4_creados", 0))}')

    lines.append("# HELP ecommerce_checkout_iniciados_total Total de checkouts iniciados en tienda web")
    lines.append("# TYPE ecommerce_checkout_iniciados_total gauge")
    lines.append(f'ecommerce_checkout_iniciados_total {float(m.get("checkout_ini", 0))}')

    lines.append("# HELP ecommerce_checkout_abandonados_total Total de checkouts abandonados en tienda web")
    lines.append("# TYPE ecommerce_checkout_abandonados_total gauge")
    lines.append(f'ecommerce_checkout_abandonados_total {float(m.get("checkout_abn", 0))}')

    lines.append("# HELP ecommerce_monto_facturado_quetzales_total Monto total facturado en Quetzales")
    lines.append("# TYPE ecommerce_monto_facturado_quetzales_total gauge")
    lines.append(f'ecommerce_monto_facturado_quetzales_total {float(m.get("monto_fact", 0))}')

    lines.append("# HELP ecommerce_p5_liquidaciones_en_plazo_total P5: Liquidaciones preparadas en plazo (<= 60 min)")
    lines.append("# TYPE ecommerce_p5_liquidaciones_en_plazo_total gauge")
    lines.append(f'ecommerce_p5_liquidaciones_en_plazo_total{{punto="P5",carril="Almacen / Logistica"}} {float(m.get("p5_ok", 0))}')

    lines.append("# HELP ecommerce_p6_pedidos_empacados_total P6: Pedidos empacados")
    lines.append("# TYPE ecommerce_p6_pedidos_empacados_total gauge")
    lines.append(f'ecommerce_p6_pedidos_empacados_total{{punto="P6",carril="Almacen / Logistica"}} {float(m.get("p6_emp", 0))}')

    lines.append("# HELP ecommerce_p7_despachos_en_plazo_total P7: Pedidos despachados en plazo (<= 8h)")
    lines.append("# TYPE ecommerce_p7_despachos_en_plazo_total gauge")
    lines.append(f'ecommerce_p7_despachos_en_plazo_total{{punto="P7",carril="Almacen / Logistica"}} {float(m.get("p7_ok", 0))}')

    # Métricas de fricción, quiebres de stock y retrasos
    lines.append("# HELP ecommerce_p2_solicitudes_datos_invalidos_total P2: Solicitudes rechazadas por datos invalidos")
    lines.append("# TYPE ecommerce_p2_solicitudes_datos_invalidos_total gauge")
    lines.append(f'ecommerce_p2_solicitudes_datos_invalidos_total{{punto="P2",carril="Sistema E-commerce"}} {float(m.get("p2_inv", 0))}')

    lines.append("# HELP ecommerce_p3_solicitudes_sin_stock_total P3: Solicitudes rechazadas por falta de stock")
    lines.append("# TYPE ecommerce_p3_solicitudes_sin_stock_total gauge")
    lines.append(f'ecommerce_p3_solicitudes_sin_stock_total{{punto="P3",carril="Sistema E-commerce"}} {float(m.get("p3_nostock", 0))}')

    lines.append("# HELP ecommerce_p4_pedidos_fallidos_creacion_total P4: Solicitudes elegibles no convertidas en pedido")
    lines.append("# TYPE ecommerce_p4_pedidos_fallidos_creacion_total gauge")
    lines.append(f'ecommerce_p4_pedidos_fallidos_creacion_total{{punto="P4",carril="Sistema E-commerce"}} {float(m.get("p4_fallidos", 0))}')

    lines.append("# HELP ecommerce_p5_liquidaciones_fuera_plazo_total P5: Liquidaciones fuera de plazo (> 60 min)")
    lines.append("# TYPE ecommerce_p5_liquidaciones_fuera_plazo_total gauge")
    lines.append(f'ecommerce_p5_liquidaciones_fuera_plazo_total{{punto="P5",carril="Almacen / Logistica"}} {float(m.get("p5_tarde", 0))}')

    lines.append("# HELP ecommerce_p7_despachos_fuera_plazo_total P7: Despachos fuera de plazo (> 8 horas)")
    lines.append("# TYPE ecommerce_p7_despachos_fuera_plazo_total gauge")
    lines.append(f'ecommerce_p7_despachos_fuera_plazo_total{{punto="P7",carril="Almacen / Logistica"}} {float(m.get("p7_tarde", 0))}')

    # 4. Los 10 Indicadores Clave de Observabilidad de Negocio (K01 a K10)
    k01 = float(m.get("k01", 0))
    k02 = float(m.get("k02", 0))
    k03 = float(m.get("k03", 0))
    k04 = float(m.get("k04", 0))
    k05 = float(m.get("k05", 0))
    k06 = float(m.get("k06", 0))
    k07 = float(m.get("k07", 0))
    k08 = float(m.get("k08", 0))
    k09 = float(m.get("k09", 0))
    k10 = float(m.get("k10", 0))

    lines.append("# HELP ecommerce_kpi_k01_volumen_diario_solicitudes K01: Volumen diario de solicitudes 24h (Meta >= 100)")
    lines.append("# TYPE ecommerce_kpi_k01_volumen_diario_solicitudes gauge")
    lines.append(f'ecommerce_kpi_k01_volumen_diario_solicitudes{{kpi="K01",carril="Cliente",meta=">= 100",unidad="solicitudes/24h"}} {k01:.4f}')

    lines.append("# HELP ecommerce_kpi_k02_porcentaje_datos_validos K02: Calidad de datos del cliente (Meta >= 98%)")
    lines.append("# TYPE ecommerce_kpi_k02_porcentaje_datos_validos gauge")
    lines.append(f'ecommerce_kpi_k02_porcentaje_datos_validos{{kpi="K02",carril="Sistema E-commerce",meta=">= 98%",unidad="%"}} {k02:.4f}')

    lines.append("# HELP ecommerce_kpi_k03_disponibilidad_stock_pct K03: Disponibilidad de stock (Meta >= 97%)")
    lines.append("# TYPE ecommerce_kpi_k03_disponibilidad_stock_pct gauge")
    lines.append(f'ecommerce_kpi_k03_disponibilidad_stock_pct{{kpi="K03",carril="Sistema E-commerce",meta=">= 97%",unidad="%"}} {k03:.4f}')

    lines.append("# HELP ecommerce_kpi_k04_efectividad_creacion_pedidos_pct K04: Efectividad de creacion de pedidos (Meta >= 99%)")
    lines.append("# TYPE ecommerce_kpi_k04_efectividad_creacion_pedidos_pct gauge")
    lines.append(f'ecommerce_kpi_k04_efectividad_creacion_pedidos_pct{{kpi="K04",carril="Sistema E-commerce",meta=">= 99%",unidad="%"}} {k04:.4f}')

    lines.append("# HELP ecommerce_kpi_k05_tasa_abandono_checkout_pct K05: Tasa de abandono en checkout (Meta <= 20%)")
    lines.append("# TYPE ecommerce_kpi_k05_tasa_abandono_checkout_pct gauge")
    lines.append(f'ecommerce_kpi_k05_tasa_abandono_checkout_pct{{kpi="K05",carril="Cliente / E-commerce",meta="<= 20%",unidad="%"}} {k05:.4f}')

    lines.append("# HELP ecommerce_kpi_k06_ticket_promedio_compra_quetzales K06: Ticket promedio de compra (Meta >= Q 250)")
    lines.append("# TYPE ecommerce_kpi_k06_ticket_promedio_compra_quetzales gauge")
    lines.append(f'ecommerce_kpi_k06_ticket_promedio_compra_quetzales{{kpi="K06",carril="Sistema E-commerce",meta=">= Q 250",unidad="GTQ"}} {k06:.2f}')

    lines.append("# HELP ecommerce_kpi_k07_liquidaciones_en_plazo_pct K07: Liquidaciones a tiempo <= 60 min (Meta >= 95%)")
    lines.append("# TYPE ecommerce_kpi_k07_liquidaciones_en_plazo_pct gauge")
    lines.append(f'ecommerce_kpi_k07_liquidaciones_en_plazo_pct{{kpi="K07",carril="Almacen / Logistica",meta=">= 95%",unidad="%"}} {k07:.4f}')

    lines.append("# HELP ecommerce_kpi_k08_tiempo_promedio_empaque_min K08: Tiempo medio de empaque (Meta <= 30 min)")
    lines.append("# TYPE ecommerce_kpi_k08_tiempo_promedio_empaque_min gauge")
    lines.append(f'ecommerce_kpi_k08_tiempo_promedio_empaque_min{{kpi="K08",carril="Almacen / Logistica",meta="<= 30 min",unidad="min"}} {k08:.4f}')

    lines.append("# HELP ecommerce_kpi_k09_cumplimiento_despacho_plazo_pct K09: Cumplimiento de despacho en plazo <= 8h (Meta >= 95%)")
    lines.append("# TYPE ecommerce_kpi_k09_cumplimiento_despacho_plazo_pct gauge")
    lines.append(f'ecommerce_kpi_k09_cumplimiento_despacho_plazo_pct{{kpi="K09",carril="Almacen / Logistica",meta=">= 95%",unidad="%"}} {k09:.4f}')

    lines.append("# HELP ecommerce_kpi_k10_lead_time_order_dispatch_horas K10: Lead Time Order-to-Dispatch (Meta <= 4 h)")
    lines.append("# TYPE ecommerce_kpi_k10_lead_time_order_dispatch_horas gauge")
    lines.append(f'ecommerce_kpi_k10_lead_time_order_dispatch_horas{{kpi="K10",carril="Almacen / Logistica",meta="<= 4 h",unidad="horas"}} {k10:.4f}')

    # 5. Estado de Semáforo por KPI (2 = VERDE, 1 = AMARILLO, 0 = ROJO)
    lines.append("# HELP ecommerce_kpi_semaforo_estado Estado del semaforo del KPI (2=VERDE, 1=AMARILLO, 0=ROJO)")
    lines.append("# TYPE ecommerce_kpi_semaforo_estado gauge")
    kpi_meta_rows = [
        ("K01", "P1", "Cliente", "Volumen diario de solicitudes (24h)", ">= 100", f'{int(round(k01))} en 24h ({int(round(float(m.get("p1_total", k01))))} hist)', int(m.get("sem_k01", 0)), "Revisar campanas y captacion de demanda"),
        ("K02", "P2", "Sistema E-commerce", "Calidad de datos del cliente", ">= 98%", f'{int(round(float(m.get("p2_val", 0))))}/{int(round(float(m.get("p2_eval", 0))))} ({k02:.2f}%)', int(m.get("sem_k02", 0)), "Mejorar validacion de formularios de envio"),
        ("K03", "P3", "Sistema E-commerce", "Disponibilidad de stock", ">= 97%", f'{int(round(float(m.get("p3_stock", 0))))}/{int(round(float(m.get("p3_base", 0))))} ({k03:.2f}%)', int(m.get("sem_k03", 0)), "Revisar punto de reorden y abastecimiento"),
        ("K04", "P4", "Sistema E-commerce", "Efectividad de creacion de pedidos", ">= 99%", f'{int(round(float(m.get("p4_creados", 0))))}/{int(round(float(m.get("p4_eleg", 0))))} ({k04:.2f}%)', int(m.get("sem_k04", 0)), "Auditar pasarela y transacciones de pedido"),
        ("K05", "P5", "Cliente / E-commerce", "Tasa de abandono en checkout", "<= 20%", f'{int(round(float(m.get("checkout_abn", 0))))}/{int(round(float(m.get("checkout_ini", 0))))} ({k05:.2f}%)', int(m.get("sem_k05", 0)), "Optimizar pasos de checkout y metodos de pago"),
        ("K06", "P6", "Sistema E-commerce", "Ticket promedio de compra", ">= Q 250", f'Q {k06:,.2f} ({int(round(float(m.get("ped_monto_ref", 0))))} ped)', int(m.get("sem_k06", 0)), "Promover venta cruzada y bundles de hardware"),
        ("K07", "P7", "Almacen / Logistica", "Liquidaciones a tiempo", ">= 95%", f'{int(round(float(m.get("p5_ok", 0))))}/{int(round(float(m.get("p5_venc", 0))))} ({k07:.2f}%)', int(m.get("sem_k07", 0)), "Agilizar aprobacion y liquidacion en <= 60 min"),
        ("K08", "P8", "Almacen / Logistica", "Tiempo medio de empaque", "<= 30 min", f'{k08:.1f} min ({int(round(float(m.get("p6_emp", 0))))} ped)', int(m.get("sem_k08", 0)), "Reforzar personal en estacion de empaque"),
        ("K09", "P9", "Almacen / Logistica", "Cumplimiento de despacho en plazo", ">= 95%", f'{int(round(float(m.get("p7_ok", 0))))}/{int(round(float(m.get("p7_venc", 0))))} ({k09:.2f}%)', int(m.get("sem_k09", 0)), "Reforzar salidas de unidades de transporte"),
        ("K10", "P10", "Almacen / Logistica", "Lead Time Order-to-Dispatch", "<= 4 h", f'{k10:.2f} h ({int(round(float(m.get("desp_lead_count", 0))))} ped)', int(m.get("sem_k10", 0)), "Optimizar ciclo total entre compra y despacho")
    ]
    for kpi, pto, carril, nombre, meta, detalle, sem, accion in kpi_meta_rows:
        color_txt = "VERDE" if sem == 2 else ("AMARILLO" if sem == 1 else "ROJO")
        lines.append(
            f'ecommerce_kpi_semaforo_estado{{kpi="{kpi}",punto="{pto}",carril="{carril}",'
            f'indicador="{nombre}",meta="{meta}",muestra="{detalle}",'
            f'color="{color_txt}",accion_negocio="{accion}"}} {sem}'
        )

    # 6. Telemetría Real y Enlace MySQL de Tech Store
    mysql_up = portal.get("pulso_mysql_up", 0.0) if portal is not None else 0.0
    lines.append("# HELP techstore_mysql_enlace_activo Enlace activo entre exporter y Portal MySQL Tech Store (1=UP, 0=DOWN)")
    lines.append("# TYPE techstore_mysql_enlace_activo gauge")
    lines.append(f'techstore_mysql_enlace_activo {mysql_up}')

    if portal:
        if "pulso_ventas_quetzales_total" in portal:
            lines.append("# HELP techstore_ventas_quetzales_total Ventas totales acumuladas de Tech Store (GTQ)")
            lines.append("# TYPE techstore_ventas_quetzales_total gauge")
            lines.append(f'techstore_ventas_quetzales_total {portal["pulso_ventas_quetzales_total"]}')
        if "pulso_pedidos_registrados" in portal:
            lines.append("# HELP techstore_pedidos_total Total de pedidos en MySQL Tech Store")
            lines.append("# TYPE techstore_pedidos_total gauge")
            lines.append(f'techstore_pedidos_total {portal["pulso_pedidos_registrados"]}')
        if "pulso_disponibilidad_stock_porcentaje" in portal:
            lines.append("# HELP techstore_disponibilidad_stock_pct Disponibilidad de catalogo real en Tech Store (%)")
            lines.append("# TYPE techstore_disponibilidad_stock_pct gauge")
            lines.append(f'techstore_disponibilidad_stock_pct {portal["pulso_disponibilidad_stock_porcentaje"]}')
        if "pulso_productos_sin_stock_total" in portal:
            lines.append("# HELP techstore_productos_sin_stock Total de productos agotados en Tech Store")
            lines.append("# TYPE techstore_productos_sin_stock gauge")
            lines.append(f'techstore_productos_sin_stock {portal["pulso_productos_sin_stock_total"]}')

    lines.append("# HELP ecommerce_exporter_scrapes_total Total de lecturas realizadas por Prometheus")
    lines.append("# TYPE ecommerce_exporter_scrapes_total counter")
    lines.append(f'ecommerce_exporter_scrapes_total {scrapes_total}')

    return "\n".join(lines) + "\n"


def render_control_panel_html():
    eff = get_effective_state()
    m = eff["metrics"]
    is_live = (eff["mode"] == "mysql_en_vivo")
    active_key = eff["active_scenario"]
    portal_ok = eff["mysql_online"]

    def badge(sem):
        if int(sem) == 2:
            return '<span style="background:#16a34a;color:#fff;padding:4px 10px;border-radius:999px;font-weight:700;font-size:12px;">🟢 VERDE</span>'
        if int(sem) == 1:
            return '<span style="background:#d97706;color:#fff;padding:4px 10px;border-radius:999px;font-weight:700;font-size:12px;">🟡 AMARILLO</span>'
        return '<span style="background:#dc2626;color:#fff;padding:4px 10px;border-radius:999px;font-weight:700;font-size:12px;">🔴 ROJO</span>'

    if is_live and portal_ok:
        banner = f"""
        <div style="background:#ecfdf5;border:1px solid #86efac;border-left:5px solid #16a34a;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:800;color:#166534;font-size:15px;">🟢 MODO ACTIVO: DATOS REALES EN VIVO DESDE MYSQL (telemetria_eventos + pedidos)</div>
                <div style="font-size:12.5px;color:#14532d;margin-top:3px;">Origen: <code>{PORTAL_URL}/api.php?action=kpis_reales</code> · Cada compra, abandono, rechazo, aprobación o despacho en Tech Store actualiza K01–K10 automáticamente.</div>
            </div>
            <span style="background:#16a34a;color:#fff;font-size:12px;font-weight:700;padding:5px 12px;border-radius:999px;">Sin simulación · Producción</span>
        </div>"""
    elif is_live and not portal_ok:
        banner = f"""
        <div style="background:#fef2f2;border:1px solid #fecaca;border-left:5px solid #dc2626;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
            <div style="font-weight:800;color:#991b1b;font-size:15px;">🔴 MODO REAL MYSQL SIN ENLACE (Portal Web o MariaDB no disponibles)</div>
            <div style="font-size:12.5px;color:#7f1d1d;margin-top:3px;">Error al consultar <code>{PORTAL_URL}/api.php?action=kpis_reales</code>: {eff["error"] or "Sin conexión"}</div>
        </div>"""
    else:
        banner = f"""
        <div style="background:#fffbeb;border:1px solid #fde68a;border-left:5px solid #d97706;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:800;color:#92400e;font-size:15px;">🧪 MODO SIMULACIÓN MANUAL ACTIVO: {SCENARIOS.get(active_key, {}).get("nombre", active_key)}</div>
                <div style="font-size:12.5px;color:#78350f;margin-top:3px;">Modo temporal de pruebas para disparar alertas en Prometheus/Grafana. Presiona el botón para regresar a las métricas reales de MySQL.</div>
            </div>
            <form method="POST" action="/scenario" style="margin:0;">
                <input type="hidden" name="scenario" value="mysql_en_vivo">
                <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-weight:700;cursor:pointer;">🔄 Volver a Datos Reales (MySQL)</button>
            </form>
        </div>"""

    team_cards = ""
    for member in TEAM_MEMBERS:
        team_cards += f"""
        <div style="background:#fff;border:1px solid #dce4ec;border-top:4px solid #2563eb;border-radius:10px;padding:14px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-weight:800;color:#0f172a;font-size:15px;">{member["nombre"]}</div>
            <div style="color:#2563eb;font-weight:700;font-size:13px;margin:3px 0;">Carné: {member["carne"]} · {member["rol"]}</div>
            <div style="font-size:12px;color:#475569;margin-bottom:8px;">{member["descripcion"]}</div>
            <div style="background:#f1f5f9;padding:7px 10px;border-radius:6px;font-size:12px;">
                <strong>Usuario Portal &amp; Grafana:</strong> <code>{member["usuario"]}</code> |
                <strong>Clave:</strong> <code>{member["clave"]}</code><br>
                <span style="color:#475569;">Portal: <strong>{member["rol_portal"]}</strong> · Grafana: <strong>{member["rol_grafana"]}</strong></span>
            </div>
        </div>
        """

    real_border = "2px solid #16a34a" if is_live else "1px solid #cbd5e1"
    real_bg = "#ecfdf5" if is_live else "#ffffff"
    scenario_buttons = f"""
    <form method="POST" action="/scenario" style="margin:0;">
        <input type="hidden" name="scenario" value="mysql_en_vivo">
        <button type="submit" style="width:100%;text-align:left;padding:12px 14px;border:{real_border};background:{real_bg};border-radius:10px;cursor:pointer;">
            <div style="font-weight:800;color:#065f46;font-size:14px;">{"✅ " if is_live else ""}📊 Datos Reales en Vivo — MySQL Tech Store</div>
            <div style="font-size:12px;color:#047857;margin-top:4px;">Fuente principal en producción: lee pedidos reales, inventarios, aprobaciones, empaque y despachos de MySQL.</div>
        </button>
    </form>
    """
    for key, sc in SCENARIOS.items():
        is_active = (not is_live and key == active_key)
        border = "2px solid #2563eb" if is_active else "1px solid #cbd5e1"
        bg = "#eff6ff" if is_active else "#ffffff"
        scenario_buttons += f"""
        <form method="POST" action="/scenario" style="margin:0;">
            <input type="hidden" name="scenario" value="{key}">
            <button type="submit" style="width:100%;text-align:left;padding:12px 14px;border:{border};background:{bg};border-radius:10px;cursor:pointer;">
                <div style="font-weight:800;color:#0f172a;font-size:14px;">{"✅ " if is_active else ""}{sc["nombre"]}</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">{sc["descripcion"]}</div>
            </button>
        </form>
        """

    return f"""<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Exportador Prometheus Tech Store | Grupo #3 Telemática</title>
    <style>
        body {{ font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 0; }}
        .header {{ background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 18px 32px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }}
        .container {{ max-width: 1280px; margin: 24px auto; padding: 0 20px; }}
        .btn-link {{ background:#2563eb; color:#fff; text-decoration:none; padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px; display:inline-block; }}
        .btn-link.sec {{ background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; }}
        table {{ width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; }}
        th {{ background:#f1f5f9; color:#334155; text-align:left; padding:11px 14px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #cbd5e1; }}
        td {{ padding:11px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; }}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#2563eb;font-weight:700;">Introducción a la Telemática · Grupo #3</div>
            <h1 style="margin:4px 0 0 0;font-size:22px;color:#0f172a;">Tech Store — Exportador Prometheus &amp; Telemetría K01–K10</h1>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a class="btn-link" href="http://localhost:3000/d/ecommerce-grupo3-k01-k07" target="_blank">🚦 Grafana KPIs K01–K10 (3000)</a>
            <a class="btn-link sec" href="http://localhost:3000/d/portal-computo-mysql-g3" target="_blank">💻 Grafana MySQL (3000)</a>
            <a class="btn-link sec" href="http://localhost:8085/tienda" target="_blank">🛒 Tienda (8085)</a>
            <a class="btn-link sec" href="http://localhost:8085/admin" target="_blank">⚙️ Admin Portal (8085)</a>
            <a class="btn-link sec" href="http://localhost:9090/targets" target="_blank">🔥 Prometheus Targets (9090)</a>
            <a class="btn-link sec" href="/metrics" target="_blank">📄 /metrics</a>
        </div>
    </div>
    <div class="container">
        {banner}

        <h2 style="color:#0f172a;margin-bottom:12px;font-size:18px;">👥 Integrantes del Grupo #3</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:26px;">
            {team_cards}
        </div>

        <h2 style="color:#0f172a;margin-bottom:12px;font-size:18px;">🎛️ Fuente de Telemetría (Producción MySQL vs Simulaciones de Alerta)</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:26px;">
            {scenario_buttons}
        </div>

        <h2 style="color:#0f172a;margin-bottom:12px;font-size:18px;">🚦 Estado Actual de los 10 KPIs de Negocio (K01 a K10)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Indicador</th>
                    <th>Fórmula</th>
                    <th>Meta</th>
                    <th>Valor Actual</th>
                    <th>Muestra</th>
                    <th>Semáforo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>K01</strong></td>
                    <td><strong>Volumen diario de solicitudes (24h)</strong></td>
                    <td>N° solicitudes registradas / últimas 24h</td>
                    <td>&ge; 100</td>
                    <td><strong>{int(round(float(m.get("k01", 0))))}</strong></td>
                    <td>{int(round(float(m.get("p1", 0))))} hoy / {int(round(float(m.get("p1_total", m.get("p1", 0)))))} total MySQL</td>
                    <td>{badge(m.get("sem_k01", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K02</strong></td>
                    <td><strong>Calidad de datos del cliente</strong></td>
                    <td>(Válidas / Evaluadas) × 100</td>
                    <td>&ge; 98%</td>
                    <td><strong>{float(m.get("k02", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("p2_val", 0))))} / {int(round(float(m.get("p2_eval", 0))))}</td>
                    <td>{badge(m.get("sem_k02", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K03</strong></td>
                    <td><strong>Disponibilidad de stock en validación</strong></td>
                    <td>(Con stock / Base evaluada) × 100</td>
                    <td>&ge; 97%</td>
                    <td><strong>{float(m.get("k03", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("p3_stock", 0))))} / {int(round(float(m.get("p3_base", 0))))}</td>
                    <td>{badge(m.get("sem_k03", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K04</strong></td>
                    <td><strong>Efectividad de creación de pedidos</strong></td>
                    <td>(Pedidos creados / Elegibles) × 100</td>
                    <td>&ge; 99%</td>
                    <td><strong>{float(m.get("k04", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("p4_creados", 0))))} / {int(round(float(m.get("p4_eleg", 0))))}</td>
                    <td>{badge(m.get("sem_k04", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K05</strong></td>
                    <td><strong>Tasa de abandono en checkout</strong></td>
                    <td>(Abandonados / Iniciados) × 100</td>
                    <td>&le; 20%</td>
                    <td><strong>{float(m.get("k05", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("checkout_abn", 0))))} / {int(round(float(m.get("checkout_ini", 0))))}</td>
                    <td>{badge(m.get("sem_k05", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K06</strong></td>
                    <td><strong>Ticket promedio de compra</strong></td>
                    <td>Monto facturado / Pedidos activos</td>
                    <td>&ge; Q 250</td>
                    <td><strong>Q {float(m.get("k06", 0)):,.2f}</strong></td>
                    <td>{int(round(float(m.get("ped_monto_ref", 0))))} pedidos (Q {float(m.get("monto_fact", 0)):,.2f})</td>
                    <td>{badge(m.get("sem_k06", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K07</strong></td>
                    <td><strong>Liquidaciones a tiempo (&le; 60 min)</strong></td>
                    <td>(En &le; 60 min / Total liquidados) × 100</td>
                    <td>&ge; 95%</td>
                    <td><strong>{float(m.get("k07", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("p5_ok", 0))))} / {int(round(float(m.get("p5_venc", 0))))}</td>
                    <td>{badge(m.get("sem_k07", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K08</strong></td>
                    <td><strong>Tiempo medio de empaque</strong></td>
                    <td>&sum; minutos empaque / Empacados</td>
                    <td>&le; 30 min</td>
                    <td><strong>{float(m.get("k08", 0)):.1f} min</strong></td>
                    <td>{int(round(float(m.get("p6_emp", 0))))} pedidos empacados</td>
                    <td>{badge(m.get("sem_k08", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K09</strong></td>
                    <td><strong>Cumplimiento de despacho en plazo</strong></td>
                    <td>(Despachados &le; 8 h / Total) × 100</td>
                    <td>&ge; 95%</td>
                    <td><strong>{float(m.get("k09", 0)):.2f}%</strong></td>
                    <td>{int(round(float(m.get("p7_ok", 0))))} / {int(round(float(m.get("p7_venc", 0))))}</td>
                    <td>{badge(m.get("sem_k09", 0))}</td>
                </tr>
                <tr>
                    <td><strong>K10</strong></td>
                    <td><strong>Lead Time Order-to-Dispatch</strong></td>
                    <td>&sum; horas total / Despachados</td>
                    <td>&le; 4 h</td>
                    <td><strong>{float(m.get("k10", 0)):.2f} h</strong></td>
                    <td>{int(round(float(m.get("desp_lead_count", 0))))} despachos</td>
                    <td>{badge(m.get("sem_k10", 0))}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>"""


class ExporterHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        pass

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        if parsed.path == "/metrics":
            body = generate_prometheus_metrics().encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/plain; version=0.0.4; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        elif parsed.path == "/api/status":
            eff = get_effective_state()
            body = json.dumps({
                "mode": eff["mode"],
                "scenario": eff["active_scenario"],
                "mysql_online": eff["mysql_online"],
                "error": eff["error"],
                "metrics": eff["metrics"],
                "team": TEAM_MEMBERS,
            }, ensure_ascii=False).encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        else:
            body = render_control_panel_html().encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        length = int(self.headers.get("Content-Length", "0"))
        raw_body = self.rfile.read(length).decode("utf-8") if length > 0 else ""
        params = urllib.parse.parse_qs(raw_body)

        if parsed.path == "/scenario":
            sc_name = params.get("scenario", ["mysql_en_vivo"])[0]
            with LOCK:
                if sc_name == "mysql_en_vivo":
                    STATE["mode"] = "mysql_en_vivo"
                    STATE["active_scenario"] = "mysql_en_vivo"
                    STATE["cache_kpis_ts"] = 0.0
                elif sc_name in SCENARIOS:
                    STATE["mode"] = "simulacion"
                    STATE["active_scenario"] = sc_name
                    STATE["custom_data"] = dict(SCENARIOS[sc_name])

        self.send_response(303)
        self.send_header("Location", "/")
        self.end_headers()


def run_grafana_provisioning_async():
    try:
        setup_path = "/app/setup_users.py"
        if os.path.exists(setup_path):
            import runpy
            runpy.run_path(setup_path, run_name="__main__")
    except Exception as e:
        print(f"[Grupo #3 Exporter] Aviso en aprovisionamiento Grafana: {e}")


if __name__ == "__main__":
    threading.Thread(target=run_grafana_provisioning_async, daemon=True).start()
    server = ThreadingHTTPServer(("0.0.0.0", PORT), ExporterHandler)
    print(f"[Grupo #3 Exporter] Escuchando en http://0.0.0.0:{PORT} (/metrics en vivo MySQL + panel K01-K10)")
    server.serve_forever()
