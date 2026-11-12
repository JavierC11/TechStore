@echo off
chcp 65001 >nul
title Grupo #3 Telemática - Iniciar SOLO el Portal E-commerce
echo ============================================================================
echo   REANUDACIÓN DE TECH STORE (E-COMMERCE)
echo ============================================================================
cd /d "%~dp0"
docker compose -f portal\docker-compose.yml start
echo.
echo Portal E-commerce activo:
echo   Tienda:  http://localhost:8085/tienda
echo   Grafana: http://localhost:3000/d/portal-computo-mysql-g3
pause
