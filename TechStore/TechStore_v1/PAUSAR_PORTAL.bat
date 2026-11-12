@echo off
chcp 65001 >nul
title Grupo #3 Telemática - Pausar SOLO el Portal E-commerce
echo ============================================================================
echo   SIMULACIÓN DE CAÍDA / DETENCIÓN DE TECH STORE (E-COMMERCE)
echo ============================================================================
cd /d "%~dp0"
docker compose -f portal\docker-compose.yml stop
echo.
echo El Portal E-commerce (techstore-web, techstore-db, techstore-phpmyadmin) está DETENIDO.
echo Prometheus y Grafana SIGUEN ACTIVOS para mostrar las alertas:
echo   http://localhost:9090/alerts
echo   http://localhost:3000/d/portal-computo-mysql-g3
echo.
echo Para reactivarlo, ejecuta INICIAR_PORTAL.bat
pause
