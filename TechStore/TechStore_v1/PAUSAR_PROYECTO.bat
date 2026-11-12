@echo off
chcp 65001 >nul
title Grupo #3 Telemática - Detener contenedores Tech Store
echo ============================================================================
echo   Deteniendo contenedores del proyecto Tech Store...
echo ============================================================================
cd /d "%~dp0"

echo Deteniendo Portal E-commerce Tech Store...
docker compose -f portal\docker-compose.yml stop

echo Deteniendo Observabilidad (Prometheus + Grafana + Exporters)...
docker compose -f docker-compose.yml stop

echo.
echo Todos los servicios de Tech Store han sido detenidos. Puedes reanudarlos con INICIAR_PROYECTO.bat
pause
