@echo off
chcp 65001 >nul
echo ======================================================================
echo  ACTUALIZANDO PRESENTACIÓN PROYECTO FINAL - AVANCE 2
echo ======================================================================
echo.
echo Por favor asegúrese de tener cerrado PowerPoint antes de continuar.
pause

copy /y "c:\xampp\htdocs\telematica\prometheus\proyecto_final-_avance_2_actualizado.pptx" "c:\xampp\htdocs\telematica\prometheus\proyecto_final-_avance_2.pptx"

if %errorlevel% equ 0 (
    echo.
    echo [OK] Presentación proyecto_final-_avance_2.pptx actualizada exitosamente con 19 diapositivas.
) else (
    echo.
    echo [ERROR] No se pudo sobrescribir. Verifique que PowerPoint esté cerrado e intente de nuevo.
)
echo.
pause

