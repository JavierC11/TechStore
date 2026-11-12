@echo off
chcp 65001 >nul
title Exportador Offline de Imágenes Docker - Tech Store & Prometheus
echo ============================================================================
echo   EXPORTACIÓN OFFLINE DE CONTENEDORES DOCKER — GRUPO #3 TELEMÁTICA
echo ============================================================================
echo Este script empaquetará las 7 imágenes Docker en un solo archivo:
echo   -> imagenes_docker_offline.tar (~1.2 GB comprimible)
echo.
echo Esto te permitirá llevar el proyecto en una memoria USB o laptop
echo y levantarlo en otra computadora SIN necesidad de conexión a Internet.
echo ============================================================================
cd /d "%~dp0"

echo Guardando imagenes Docker... (esto puede tardar 1-2 minutos)
docker save -o imagenes_docker_offline.tar prom/prometheus:latest grafana/grafana:latest prom/node-exporter:latest python:3.11-alpine mariadb:10.11 phpmyadmin:5.2 digyfenix-pulso-portal-web:latest

if %errorlevel% neq 0 (
    echo.
    echo ❌ ERROR al exportar imágenes Docker. Verifica que Docker Desktop esté activo.
    pause
    exit /b 1
)

echo.
echo ============================================================================
echo   ✅ EXPORTACIÓN EXITOSA
echo ============================================================================
echo Archivo generado: imagenes_docker_offline.tar
echo.
echo Para llevarte este proyecto a otra PC:
echo 1. Copia toda esta carpeta "prometheus" a tu USB o laptop.
echo 2. En la otra PC, instala o abre Docker Desktop.
echo 3. Ejecuta el archivo "IMPORTAR_IMAGENES_OFFLINE.bat".
echo 4. Ejecuta "INICIAR_PROYECTO.bat" y todo arrancará de inmediato sin internet.
echo ============================================================================
pause

