#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Deteniendo Portal E-commerce Tech Store..."
docker compose -f portal/docker-compose.yml stop

echo "Deteniendo Observabilidad (Prometheus + Grafana + Exporters)..."
docker compose -f docker-compose.yml stop

echo "Servicios de Tech Store detenidos correctamente."
