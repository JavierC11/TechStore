#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "============================================================================"
echo "  PROYECTO: Tech Store Guatemala E-commerce (PHP + MySQL) + Observabilidad"
echo "  Curso: Introducción a la Telemática — Grupo #3"
echo "============================================================================"

if ! docker info >/dev/null 2>&1; then
  echo "[ERROR] Docker Desktop no está en ejecución. Ábrelo primero y vuelve a intentar."
  exit 1
fi

mkdir -p portal/uploads/productos

echo "[1/2] Iniciando Observabilidad (Prometheus + Grafana + Node & E-commerce Exporters)..."
docker compose -f docker-compose.yml up -d --force-recreate

echo "[2/2] Iniciando Portal E-commerce Tech Store (Web PHP + MariaDB + phpMyAdmin)..."
docker compose -f portal/docker-compose.yml up -d --build --force-recreate

echo ""
echo "Esperando a que Portal Web, MySQL y Grafana respondan..."
sleep 4
docker ps --filter "name=techstore-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo ""
echo "============================================================================"
echo "  ACCESOS DIRECTOS DEL PROYECTO:"
echo "============================================================================"
echo "  Tienda Tech Store (Cliente):      http://localhost:8085/tienda"
echo "  Portal Login & Admin:             http://localhost:8085/login  (admin / Admin@2026)"
echo "  Métricas RAW PHP/MySQL:           http://localhost:8085/api.php?action=prometheus"
echo "  Grafana KPIs Reales (K01-K10):    http://localhost:3000/d/ecommerce-grupo3-k01-k07"
echo "  Grafana Tech Store MySQL:         http://localhost:3000/d/portal-computo-mysql-g3"
echo "  Prometheus Targets & Alertas:     http://localhost:9090/targets"
echo "  Exportador MySQL / Simulador:     http://localhost:9101"
echo "  phpMyAdmin (MySQL 3307):          http://localhost:8086  (pulso_user / Pulso@2026)"
echo "============================================================================"
