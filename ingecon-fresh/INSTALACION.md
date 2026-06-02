# Guía de Instalación — Plataforma Web Ingecon

Guía paso a paso para ejecutar el proyecto **desde cero** en Windows (sin nada instalado).

> Stack: Laravel 11 · PHP 8.2+ · PostgreSQL 16

---

## 1. Requisitos a instalar

| Software | Para qué | Dónde |
|----------|----------|-------|
| **PHP 8.2+** con extensiones `pdo_pgsql`, `pgsql`, `fileinfo`, `mbstring`, `openssl`, `tokenizer` | Ejecutar Laravel | [Laravel Herd](https://herd.laravel.com) (recomendado: trae PHP + Composer) |
| **Composer** | Dependencias PHP | Incluido en Herd, o [getcomposer.org](https://getcomposer.org) |
| **PostgreSQL 16** | Base de datos (obligatorio) | [postgresql.org/download/windows](https://www.postgresql.org/download/windows/) |
| **Node.js** (opcional) | Compilar assets (Tailwind/Vite) | [nodejs.org](https://nodejs.org) |

> ⚠️ **PostgreSQL es obligatorio.** La app usa `ILIKE`, `NULLS LAST` y columnas `BYTEA`, que SQLite no soporta. No uses el `DB_CONNECTION=sqlite` que trae `.env.example`.
>
> ℹ️ Node.js es **opcional**: los layouts cargan Tailwind y Alpine.js por CDN, así que la app funciona sin compilar assets.

Verifica que PHP tiene la extensión de PostgreSQL:

```bash
php -m | findstr pgsql
```

Debe listar `pdo_pgsql` y `pgsql`. Si no aparecen, habilítalas en tu `php.ini` (quita el `;` de `extension=pdo_pgsql` y `extension=pgsql`).

---

## 2. Configuración del proyecto

Abre una terminal dentro de la carpeta `ingecon-fresh` y ejecuta:

```bash
# Dependencias PHP
composer install

# Crear el archivo de entorno
copy .env.example .env

# Generar la clave de la aplicación
php artisan key:generate
```

---

## 3. Editar el archivo `.env`

Abre `.env` y ajusta estas variables (lo más importante):

```ini
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ingecon_db
DB_USERNAME=postgres
DB_PASSWORD=tu_password_de_postgres

SESSION_DRIVER=file        # NO uses "database"
QUEUE_CONNECTION=sync      # NO uses "database"
MAIL_MAILER=log
```

> **Por qué `file` / `sync` / `log`:** el proyecto solo incluye las 9 migraciones del negocio, **no** las tablas por defecto de Laravel (`sessions`, `cache`, `jobs`). Con estos valores evitas errores de "tabla no encontrada". Los correos de bloqueo de cuenta se escriben en `storage/logs/laravel.log` en lugar de enviarse.

---

## 4. Crear la base de datos

Abre `psql` o pgAdmin y ejecuta:

```sql
CREATE DATABASE ingecon_db WITH ENCODING 'UTF8' TEMPLATE template0;
```

> En Windows, omite los `LC_COLLATE`/`LC_CTYPE` con locale `es_CL.UTF-8` (suelen no estar disponibles).

---

## 5. Crear tablas y datos iniciales

```bash
php artisan migrate:fresh --seed
```

Esto crea las 9 tablas (`administrador`, `visitante`, `sesion`, `consulta`, `archivo_adjunto`, `proyecto`, `imagen_proyecto`, `certificado`, `colaborador`) y el administrador inicial.

---

## 6. (Opcional) Compilar assets

```bash
npm install
npm run build
```

---

## 7. Iniciar el servidor

```bash
php artisan serve
```

Abre **http://localhost:8000** en tu navegador.

---

## Credenciales del panel admin

El seeder crea este usuario:

- **Email:** `admin@ingecon.cl`
- **Password:** `Ingecon2024!`

> Cambia esta contraseña en producción.

---

## Errores comunes

| Problema | Solución |
|----------|----------|
| `could not find driver` / `pdo_pgsql` | Habilita las extensiones `pdo_pgsql` y `pgsql` en `php.ini` y reinicia la terminal. |
| `SQLSTATE[08006] connection refused` | PostgreSQL no está corriendo o el host/puerto del `.env` es incorrecto. |
| `password authentication failed` | El `DB_USERNAME` / `DB_PASSWORD` del `.env` no coincide con tu instalación de PostgreSQL. |
| `Table ... sessions/cache/jobs not found` | Asegúrate de tener `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file` en el `.env`. |
| Estilos no cargan | Normal sin `npm run build`: la app usa Tailwind/Alpine por CDN; verifica tu conexión a internet. |
