#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Reanudando Portal E-commerce Tech Store (techstore-db, techstore-web, techstore-phpmyadmin)..."
docker compose -f portal/docker-compose.yml start

echo ""
echo "Portal reactivado:"
echo "  Tienda:  http://localhost:8085/tienda"
echo "  Grafana: http://localhost:3000/d/portal-computo-mysql-g3"
