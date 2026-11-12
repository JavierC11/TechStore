#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
APROVISIONAMIENTO AUTOMÁTICO DE USUARIOS, ROLES, EQUIPO Y PREFERENCIAS EN GRAFANA
Proyecto Grupal: Observabilidad de la Gestión de Pedidos E-commerce — Tech Store
Curso: Introducción a la Telemática — Grupo #3
"""

import base64
import json
import os
import time
import urllib.error
import urllib.request

GRAFANA_URL = os.environ.get("GRAFANA_URL", "http://grafana:3000")
ADMIN_USER = os.environ.get("GF_SECURITY_ADMIN_USER", "admin")
ADMIN_PASS = os.environ.get("GF_SECURITY_ADMIN_PASSWORD", "admin123")

# Usuarios oficiales del Grupo #3 con credenciales unificadas (idénticas al Portal Web)
PROJECT_USERS = [
    {
        "name": "Carlos Daniel López Hernández (096-19-5203) - Desarrollador / DevOps",
        "email": "clopez@telematica.edu.gt",
        "login": "carlos.lopez",
        "password": "Dev1@Grupo3",
        "orgRole": "Admin",
        "isGrafanaAdmin": True
    },
    {
        "name": "Victor Javier Castillo (092-19-7011) - DBA",
        "email": "vcastillo@telematica.edu.gt",
        "login": "victor.castillo",
        "password": "Dba2@Grupo3",
        "orgRole": "Admin",
        "isGrafanaAdmin": True
    },
    {
        "name": "Oscar Geovany Ortíz Barillas (092-17-6969) - Desarrollador",
        "email": "oortiz@telematica.edu.gt",
        "login": "oscar.ortiz",
        "password": "Dev3@Grupo3",
        "orgRole": "Editor",
        "isGrafanaAdmin": False
    },
    {
        "name": "Emmanuel Alexander Ajsivinac Yoc (092-20-7654) - Implementador & QA Lead",
        "email": "eajsivinac@telematica.edu.gt",
        "login": "emmanuel.ajsivinac",
        "password": "Qa4@Grupo3",
        "orgRole": "Editor",
        "isGrafanaAdmin": False
    }
]


def api_request(method, path, payload=None):
    url = f"{GRAFANA_URL}{path}"
    auth_str = f"{ADMIN_USER}:{ADMIN_PASS}".encode("utf-8")
    b64_auth = base64.b64encode(auth_str).decode("ascii")
    headers = {
        "Authorization": f"Basic {b64_auth}",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            raw = resp.read().decode("utf-8")
            return resp.status, (json.loads(raw) if raw else {})
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8", errors="ignore")
        try:
            return e.code, json.loads(raw)
        except Exception:
            return e.code, {"message": raw}
    except Exception as e:
        return 0, {"message": str(e)}


def wait_for_grafana():
    print("[Grafana Init] Esperando a que Grafana esté disponible...")
    for _ in range(45):
        status, body = api_request("GET", "/api/health")
        if status == 200 and body.get("database") == "ok":
            print("[Grafana Init] Grafana está operativo y saludable.")
            return True
        time.sleep(2)
    return False


def main():
    if not wait_for_grafana():
        print("[Grafana Init] No fue posible conectar con Grafana.")
        return

    # 1. Renombrar Organización Principal
    api_request("PUT", "/api/org", {
        "name": "Grupo #3 - Telemática | Observabilidad Tech Store"
    })
    print("[Grafana Init] Organización configurada: 'Grupo #3 - Telemática | Observabilidad Tech Store'")

    # 2. Actualizar nombre visible del usuario admin principal
    api_request("PUT", "/api/users/1", {
        "name": "Administrador General — Tech Store (Grupo #3)",
        "email": "admin@techstore.com.gt",
        "login": ADMIN_USER
    })

    # 3. Crear equipo oficial "Grupo #3 - Introducción a la Telemática"
    team_id = None
    status, search = api_request("GET", "/api/teams/search?name=Grupo%20%233%20-%20Introducci%C3%B3n%20a%20la%20Telem%C3%A1tica")
    if status == 200 and search.get("teams"):
        team_id = search["teams"][0]["id"]
    else:
        status, created_team = api_request("POST", "/api/teams", {
            "name": "Grupo #3 - Introducción a la Telemática",
            "email": "grupo3@telematica.edu.gt"
        })
        if status in (200, 201):
            team_id = created_team.get("teamId")

    # 4. Crear o sincronizar cada integrante oficial del proyecto con su rol y clave unificada
    for u in PROJECT_USERS:
        status, res = api_request("POST", "/api/admin/users", {
            "name": u["name"],
            "email": u["email"],
            "login": u["login"],
            "password": u["password"],
            "OrgId": 1
        })
        user_id = res.get("id")
        if status == 412 or not user_id:
            st_lookup, lookup = api_request("GET", f"/api/users/lookup?loginOrEmail={u['login']}")
            if st_lookup == 200:
                user_id = lookup.get("id")

        if user_id:
            # Sincronizar nombre, correo, contraseña y permisos aunque el volumen Grafana ya existiera
            api_request("PUT", f"/api/users/{user_id}", {
                "name": u["name"],
                "email": u["email"],
                "login": u["login"]
            })
            api_request("PUT", f"/api/admin/users/{user_id}/password", {
                "password": u["password"]
            })
            api_request("PATCH", f"/api/orgs/1/users/{user_id}", {
                "role": u["orgRole"]
            })
            api_request("PUT", f"/api/admin/users/{user_id}/permissions", {
                "isGrafanaAdmin": bool(u.get("isGrafanaAdmin"))
            })
            if team_id:
                api_request("POST", f"/api/teams/{team_id}/members", {
                    "userId": user_id
                })
            print(f"[Grafana Init] Usuario sincronizado: {u['login']} ({u['name']}) -> Rol: {u['orgRole']}")

    # 5. Configurar el Dashboard principal del proyecto (K01-K10) como Home Dashboard
    for _ in range(12):
        st_dash, _ = api_request("GET", "/api/dashboards/uid/ecommerce-grupo3-k01-k07")
        if st_dash == 200:
            api_request("PUT", "/api/org/preferences", {
                "homeDashboardUID": "ecommerce-grupo3-k01-k07",
                "theme": "dark",
                "timezone": "browser"
            })
            print("[Grafana Init] Dashboard 'ecommerce-grupo3-k01-k07' fijado como pantalla de inicio (Home).")
            break
        time.sleep(2)

    print("[Grafana Init] Aprovisionamiento de usuarios y entorno finalizado exitosamente.")


if __name__ == "__main__":
    main()
