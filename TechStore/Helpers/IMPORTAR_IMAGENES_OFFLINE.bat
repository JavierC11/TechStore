@echo off
chcp 65001 >nul
title Importador Offline de Imágenes Docker - Tech Store & Prometheus
echo ============================================================================
echo   IMPORTACIÓN OFFLINE DE CONTENEDORES DOCKER — GRUPO #3 TELEMÁTICA
echo ============================================================================
cd /d "%~dp0"

if not exist imagenes_docker_offline.tar (
    echo ❌ No se encontró el archivo 'imagenes_docker_offline.tar' en esta carpeta.
    echo Si tienes conexión a Internet en esta máquina, puedes omitir este paso
    echo y ejecutar directamente 'INICIAR_PROYECTO.bat', el cual descargará
    echo las imágenes automáticamente desde Docker Hub.
    echo.
    pause
    exit /b 1
)

echo Cargando imágenes Docker desde el archivo local (sin internet)...
docker load -i imagenes_docker_offline.tar

if %errorlevel% neq 0 (
    echo.
    echo ❌ ERROR al cargar imágenes Docker. Asegúrate de que Docker Desktop esté abierto.
    pause
    exit /b 1
)

echo.
echo ============================================================================
echo   ✅ IMÁGENES CARGADAS EXITOSAMENTE EN DOCKER DESKTOP
echo ============================================================================
echo Ya puedes ejecutar 'INICIAR_PROYECTO.bat' para iniciar la tienda y Grafana.
echo ============================================================================
pause

