@echo off
chcp 65001 >nul
title Grupo #3 Telemática - Tech Store + Prometheus + Grafana
echo ============================================================================
echo   PROYECTO: Tech Store Guatemala E-commerce (PHP + MySQL) + Observabilidad
echo   Curso: Introducción a la Telemática — Grupo #3
echo ============================================================================
cd /d "%~dp0"

docker info >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker Desktop no esta en ejecucion. Abrelo primero y vuelve a intentar.
    pause
    exit /b 1
)

if not exist "portal\uploads\productos" mkdir "portal\uploads\productos"

echo [1/2] Iniciando Observabilidad (Prometheus + Grafana + Exporters)...
docker compose -f docker-compose.yml up -d --force-recreate

echo [2/2] Iniciando Portal E-commerce Tech Store (Web + MariaDB MySQL + phpMyAdmin)...
docker compose -f portal\docker-compose.yml up -d --build --force-recreate

echo Esperando a que Portal Web, MySQL y Grafana respondan...
timeout /t 4 /nobreak >nul

echo.
echo ============================================================================
echo   CONTENEDORES ACTIVOS (TECHSTORE-*):
echo ============================================================================
docker ps --filter "name=techstore-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo.
echo ============================================================================
echo   ACCESOS DIRECTOS DEL PROYECTO:
echo ============================================================================
echo   Tienda en Línea Tech Store:      http://localhost:8085/tienda
echo   Portal Login & Admin:            http://localhost:8085/login   (admin / Admin@2026)
echo   Grafana KPIs Reales (K01-K10):   http://localhost:3000/d/ecommerce-grupo3-k01-k07
echo   Grafana Tech Store MySQL:        http://localhost:3000/d/portal-computo-mysql-g3
echo   Exportador MySQL / Simulador:    http://localhost:9101
echo   Consola Prometheus (Targets):    http://localhost:9090/targets
echo   phpMyAdmin (MySQL 3307):         http://localhost:8086   (pulso_user / Pulso@2026)
echo ============================================================================
pause
