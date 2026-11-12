#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Deteniendo únicamente el Portal E-commerce (simulación de caída MySQL/Web)..."
docker compose -f portal/docker-compose.yml stop

echo ""
echo "El Portal Tech Store está DETENIDO mientras Prometheus y Grafana siguen activos."
echo "Verifica la alerta en:"
echo "  http://localhost:9090/alerts"
echo "  http://localhost:3000/d/portal-computo-mysql-g3"
echo ""
echo "Para reactivarlo ejecuta: ./iniciar_portal.sh"
