@echo off
REM ============================================================
REM  Instalador automatico - Plataforma Web Ingecon
REM  Laravel 11 + PostgreSQL 16
REM  Ejecutar este archivo dentro de la carpeta ingecon-fresh
REM ============================================================
setlocal enabledelayedexpansion
cd /d "%~dp0"

echo.
echo ============================================================
echo   INSTALADOR - Plataforma Web Ingecon
echo ============================================================
echo.

REM ---- Verificar requisitos ----
echo [1/8] Verificando requisitos...
where php >nul 2>nul
if errorlevel 1 (
    echo   ERROR: PHP no esta instalado o no esta en el PATH.
    echo   Instala PHP 8.2+ ^(recomendado: Laravel Herd^) y vuelve a intentar.
    pause
    exit /b 1
)
where composer >nul 2>nul
if errorlevel 1 (
    echo   ERROR: Composer no esta instalado o no esta en el PATH.
    pause
    exit /b 1
)
php -m | findstr /i pgsql >nul 2>nul
if errorlevel 1 (
    echo   ADVERTENCIA: No se detecto la extension pgsql en PHP.
    echo   Habilita pdo_pgsql y pgsql en php.ini antes de continuar.
    echo.
    set /p CONT="Continuar de todos modos? (S/N): "
    if /i not "!CONT!"=="S" exit /b 1
)
echo   OK
echo.

REM ---- Dependencias PHP ----
echo [2/8] Instalando dependencias PHP (composer install)...
call composer install
if errorlevel 1 ( echo   ERROR en composer install & pause & exit /b 1 )
echo.

REM ---- Crear .env ----
echo [3/8] Configurando archivo .env...
if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo   .env creado desde .env.example
) else (
    echo   .env ya existe, se conserva el actual
)
echo.

REM ---- Generar APP_KEY ----
echo [4/8] Generando APP_KEY...
call php artisan key:generate
echo.

REM ---- Datos de la base de datos ----
echo [5/8] Configuracion de PostgreSQL
echo   Ingresa los datos de tu base de datos PostgreSQL.
set /p DBNAME="   Nombre de la BD [ingecon_db]: "
if "!DBNAME!"=="" set DBNAME=ingecon_db
set /p DBUSER="   Usuario [postgres]: "
if "!DBUSER!"=="" set DBUSER=postgres
set /p DBPASS="   Password: "

REM ---- Actualizar .env con powershell ----
powershell -Command "(Get-Content .env) -replace '^DB_CONNECTION=.*','DB_CONNECTION=pgsql' -replace '^# ?DB_HOST=.*','DB_HOST=127.0.0.1' -replace '^# ?DB_PORT=.*','DB_PORT=5432' -replace '^# ?DB_DATABASE=.*','DB_DATABASE=!DBNAME!' -replace '^# ?DB_USERNAME=.*','DB_USERNAME=!DBUSER!' -replace '^# ?DB_PASSWORD=.*','DB_PASSWORD=!DBPASS!' -replace '^SESSION_DRIVER=.*','SESSION_DRIVER=file' -replace '^QUEUE_CONNECTION=.*','QUEUE_CONNECTION=sync' -replace '^CACHE_STORE=.*','CACHE_STORE=file' -replace '^MAIL_MAILER=.*','MAIL_MAILER=log' | Set-Content .env"
echo   .env actualizado (pgsql, session=file, queue=sync, mail=log)
echo.

REM ---- Crear base de datos ----
echo [6/8] Creando base de datos '!DBNAME!' en PostgreSQL...
where psql >nul 2>nul
if errorlevel 1 (
    echo   ADVERTENCIA: 'psql' no esta en el PATH.
    echo   Crea la BD manualmente con: CREATE DATABASE !DBNAME! WITH ENCODING 'UTF8' TEMPLATE template0;
    set /p OK="   Presiona Enter cuando la BD exista..."
) else (
    set PGPASSWORD=!DBPASS!
    psql -U !DBUSER! -h 127.0.0.1 -c "CREATE DATABASE !DBNAME! WITH ENCODING 'UTF8' TEMPLATE template0;" 2>nul
    if errorlevel 1 (
        echo   Nota: la BD ya existia o hubo un aviso ^(se continua^).
    ) else (
        echo   Base de datos creada.
    )
)
echo.

REM ---- Migraciones + seeders ----
echo [7/8] Ejecutando migraciones y seeders...
call php artisan migrate:fresh --seed
if errorlevel 1 ( echo   ERROR en migraciones. Revisa la conexion a la BD. & pause & exit /b 1 )
echo.

REM ---- Listo ----
echo [8/8] Instalacion completada.
echo.
echo ============================================================
echo   LISTO. Credenciales del panel admin:
echo     Email:    admin@ingecon.cl
echo     Password: Ingecon2024!
echo ============================================================
echo.
set /p RUN="Iniciar el servidor ahora? (S/N): "
if /i "!RUN!"=="S" (
    echo   Servidor en http://localhost:8000  (Ctrl+C para detener)
    php artisan serve
)
endlocal
