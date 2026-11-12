#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

docker network inspect techstore_observability_net >/dev/null 2>&1 || docker network create techstore_observability_net >/dev/null
echo "Reanudando Portal E-commerce Tech Store (techstore-db, techstore-web, techstore-phpmyadmin)..."
docker compose -f portal/docker-compose.yml up -d --force-recreate

echo ""
echo "Portal reactivado:"
echo "  Tienda:  http://localhost:8085/tienda"
echo "  Grafana: http://localhost:3000/d/portal-computo-mysql-g3"
