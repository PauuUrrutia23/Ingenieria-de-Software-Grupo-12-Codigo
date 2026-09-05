@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul
title Ingecon - Instalar y Levantar

rem =============================================================
rem  INGECON - script unico de arranque
rem
rem  Revisa si el equipo tiene todo lo necesario para correr la
rem  aplicacion (Scoop, PHP 8.1, Composer, Node/npm, dependencias,
rem  base de datos). Lo que falte lo instala solo; lo que ya este
rem  lo deja intacto. Al final deja el servidor corriendo y abre
rem  el navegador en la pantalla de inicio de sesion.
rem
rem  Requisitos: Windows 10/11, conexion a internet la primera vez
rem  (para descargar Scoop/PHP/Composer/Node si no estan).
rem =============================================================

set "PROJECT_DIR=%~dp0"
set "WEB_DIR=%PROJECT_DIR%web"
set "NUCLEO_DIR=%WEB_DIR%\nucleo"
set "SQLITE_DB=%WEB_DIR%\base_datos\database.sqlite"
set "SCRIPTS_DIR=%PROJECT_DIR%scripts"
set "APP_PORT=8000"
set "APP_URL=http://127.0.0.1:%APP_PORT%"
set "SCOOP_SHIMS=%USERPROFILE%\scoop\shims"

rem Por si Scoop ya estaba instalado antes de abrir esta consola: sus
rem programas no quedan en el PATH del sistema, solo en el del usuario,
rem y a veces esta consola no lo hereda todavia.
set "PATH=%SCOOP_SHIMS%;%PATH%"

echo ============================================================
echo   INGECON - Verificacion, instalacion y arranque automatico
echo ============================================================
echo.

if not exist "%NUCLEO_DIR%\artisan" (
    echo [ERROR] No se encontro "%NUCLEO_DIR%\artisan".
    echo Este archivo .bat debe quedar en la raiz del proyecto, junto a la carpeta "web".
    echo.
    pause
    exit /b 1
)

rem -------------------------------------------------------------
echo [1/8] Gestor de paquetes Scoop
rem -------------------------------------------------------------
where scoop >nul 2>nul
if errorlevel 1 (
    echo   No encontrado. Instalando Scoop...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Set-ExecutionPolicy RemoteSigned -Scope CurrentUser -Force; Invoke-RestMethod get.scoop.sh | Invoke-Expression"
    set "PATH=!SCOOP_SHIMS!;%PATH%"
    where scoop >nul 2>nul
    if errorlevel 1 (
        echo [ERROR] No se pudo instalar Scoop automaticamente.
        echo Instalalo manualmente desde https://scoop.sh y vuelve a ejecutar este archivo.
        echo.
        pause
        exit /b 1
    )
    echo   OK: Scoop instalado.
) else (
    echo   OK: ya estaba instalado.
)
echo.

rem -------------------------------------------------------------
echo [2/8] PHP 8.1
rem -------------------------------------------------------------
set "PHP_CMD="
where php >nul 2>nul
if errorlevel 1 (
    if exist "!SCOOP_SHIMS!\php.exe" set "PHP_CMD=!SCOOP_SHIMS!\php.exe"
) else (
    set "PHP_CMD=php"
)

if not defined PHP_CMD (
    echo   No encontrado. Instalando PHP 8.1 con Scoop...
    call scoop bucket add versions >nul 2>nul
    call scoop install php81
    if exist "!SCOOP_SHIMS!\php.exe" (
        set "PHP_CMD=!SCOOP_SHIMS!\php.exe"
        echo   OK: PHP instalado.
    ) else (
        echo [ERROR] No se pudo instalar PHP automaticamente.
        echo Instalalo manualmente: https://windows.php.net/download/
        echo.
        pause
        exit /b 1
    )
) else (
    echo   OK: usando "!PHP_CMD!"
)
"!PHP_CMD!" -v
echo.

rem -------------------------------------------------------------
echo [3/8] Extensiones de PHP requeridas
rem -------------------------------------------------------------
if exist "%SCRIPTS_DIR%\habilitar-extensiones-php.ps1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPTS_DIR%\habilitar-extensiones-php.ps1" -PhpExe "!PHP_CMD!"
) else (
    echo   [AVISO] No se encontro el script de extensiones; se omite este paso.
)
echo.

rem -------------------------------------------------------------
echo [4/8] Composer
rem -------------------------------------------------------------
where composer >nul 2>nul
if errorlevel 1 (
    echo   No encontrado. Instalando Composer con Scoop...
    call scoop install composer
    set "PATH=!SCOOP_SHIMS!;%PATH%"
)
where composer >nul 2>nul
if errorlevel 1 (
    echo [ERROR] No se pudo instalar Composer automaticamente.
    echo Instalalo manualmente: https://getcomposer.org/download/
    echo.
    pause
    exit /b 1
)
echo   OK: composer disponible.
echo.

rem -------------------------------------------------------------
echo [5/8] Node.js y npm
rem -------------------------------------------------------------
where npm >nul 2>nul
if errorlevel 1 (
    echo   No encontrado. Instalando Node.js LTS con Scoop...
    call scoop install nodejs-lts
    set "PATH=!SCOOP_SHIMS!;%PATH%"
)
where npm >nul 2>nul
if errorlevel 1 (
    echo [ERROR] No se pudo instalar Node.js automaticamente.
    echo Instalalo manualmente: https://nodejs.org/
    echo.
    pause
    exit /b 1
)
echo   OK: npm disponible.
echo.

cd /d "%NUCLEO_DIR%"

rem -------------------------------------------------------------
echo [6/8] Dependencias del proyecto (Composer y npm)
rem -------------------------------------------------------------
if not exist "vendor\autoload.php" (
    echo   Instalando dependencias PHP: composer install...
    call composer install --no-interaction --prefer-dist
) else (
    echo   OK: vendor\ ya existe.
)

if not exist "node_modules" (
    echo   Instalando dependencias de JavaScript: npm install...
    call npm install
) else (
    echo   OK: node_modules\ ya existe.
)

if not exist "public\build\manifest.json" (
    echo   Compilando estilos y scripts: npm run build...
    call npm run build
) else (
    echo   OK: assets ya compilados.
)
echo.

rem -------------------------------------------------------------
echo [7/8] Configuracion, base de datos y almacenamiento
rem -------------------------------------------------------------
if not exist ".env" (
    echo   Creando archivo .env desde .env.example...
    copy /y ".env.example" ".env" >nul
)

findstr /b /c:"APP_KEY=base64:" ".env" >nul
if errorlevel 1 (
    echo   Generando clave de la aplicacion...
    "!PHP_CMD!" artisan key:generate --force
) else (
    echo   OK: clave de la aplicacion ya configurada.
)

if not exist "%SQLITE_DB%" (
    echo   Creando base de datos SQLite vacia...
    type nul > "%SQLITE_DB%"
)

echo   Aplicando migraciones...
"!PHP_CMD!" artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Las migraciones fallaron. Revisa el mensaje de arriba.
    echo.
    pause
    exit /b 1
)

echo   Sembrando datos base (administrador y ejemplos, si faltan)...
"!PHP_CMD!" artisan db:seed --force

if not exist "public\storage" (
    echo   Enlazando almacenamiento publico...
    "!PHP_CMD!" artisan storage:link
)

echo   Limpiando caches de configuracion, rutas y vistas...
"!PHP_CMD!" artisan config:clear >nul
"!PHP_CMD!" artisan route:clear >nul
"!PHP_CMD!" artisan view:clear >nul
echo.

rem -------------------------------------------------------------
echo [8/8] Levantando el servidor
rem -------------------------------------------------------------
start "Ingecon - Servidor (no cerrar mientras uses la pagina)" cmd /k ""!PHP_CMD!" artisan serve --port=%APP_PORT%"

echo   Esperando que el servidor arranque...
timeout /t 3 /nobreak >nul

echo   Abriendo Ingecon en el navegador...
start "" "%APP_URL%/login"

rem Desactivar expansion retardada: de aqui en adelante solo texto fijo, y
rem asi el "!" de la contrasena se imprime tal cual en vez de ser tragado.
setlocal DisableDelayedExpansion

echo.
echo ============================================================
echo   Listo. Ingecon esta corriendo en %APP_URL%
echo.
echo   Credenciales de administrador:
echo     Correo:      admin@ingecon.cl
echo     Contrasena:  Admin123!
echo.
echo   Quedaron 2 ventanas abiertas:
echo     1) El servidor de Ingecon (NO la cierres mientras uses la pagina)
echo     2) Tu navegador, en la pantalla de inicio de sesion
echo.
echo   Para apagar el servidor: cierra su ventana o presiona Ctrl+C ahi.
echo ============================================================
echo.
pause
