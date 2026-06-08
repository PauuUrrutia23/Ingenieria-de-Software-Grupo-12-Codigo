# Historial de Cambios y Guía de Instalación — Ingecon

## Cambios realizados

### Reorganización de carpetas (Commits 1-5)

El proyecto se reorganizó en 4 carpetas principales para claridad MVC:

| Carpeta | Contenido | Equivalente Laravel |
|---------|-----------|-------------------|
| `vistas/` | 17 Blade templates | `resources/views/` |
| `controlador/` | 7 controladores + 1 middleware + 1 DBRouter | `app/Http/Controllers/` + `app/Http/Middleware/` |
| `base_datos/` | 9 modelos + 10 migraciones + 2 seeders + 2 SQL referencia | `app/Models/` + `database/` |
| `documentacion/` | 13 archivos de documentación | — |

**Archivos modificados para la reorganización:**
- `composer.json` — nuevos paths PSR-4 para que los namespaces resuelvan desde las nuevas carpetas
- `app/Providers/AppServiceProvider.php` — redirige vistas a `vistas/` y migraciones a `base_datos/migrations/`
- `README.md` — actualizado con la nueva estructura

**Sin cambios en namespaces ni Blade** — los `@extends`, `@include`, `namespace`, `use` funcionan idéntico gracias a PSR-4 y `View::getFinder()`.

### Consolidación del proyecto

- Se unificó `ingecon-fresh/` como raíz del repositorio
- Se eliminaron las carpetas duplicadas `routes/routes/` y `database/database/`
- Se eliminó la carpeta obsoleta `ingecon-app/`

### DBRouterController

El proyecto usa el patrón **DBRouterController** (`controlador/DBRouterController.php`), documentado en `documentacion/02b_dbrouter_controller.md`.

- Es la **única clase autorizada** para invocar Eloquent directamente
- Todos los controladores (`AdminController`, `AuthController`, `ContactoController`, etc.) reciben una instancia por inyección de dependencias y delegan toda lectura/escritura de BD
- La migración `000010` agrega la columna `tipo_mime` en `colaborador` requerida por este patrón

### Corrección de navegación

- **Sidebar y navbar** usan links reales (`<a href="{{ route(...) }}">`) en vez de `scrollIntoView`
- **Certificaciones** (`/certificaciones`) es una página independiente con layout completo, no un scroll dentro del index
- Las demás secciones (Proyectos, Contacto) usan hash `/#seccion` para navegar dentro de la página de inicio

### Archivos SQL de referencia

- `base_datos/01_crear_base_datos.sql` — script SQL para crear la base de datos
- `base_datos/02_crear_tablas.sql` — script SQL con el esquema completo de tablas

---

## Requisitos

| Software | Versión | Obligatorio | Notas |
|----------|---------|-------------|-------|
| PHP | 8.2+ | Sí | Extensiones: `pdo_pgsql`, `pgsql`, `fileinfo`, `mbstring`, `openssl`, `tokenizer` |
| Composer | 2.x | Sí | Gestor de dependencias PHP |
| PostgreSQL | 16+ | Sí | La app usa `ILIKE`, `NULLS LAST` y `BYTEA`. SQLite no sirve. |
| Node.js + npm | 18+ | No | Solo si compilas assets con Vite. La app funciona con CDN sin esto. |
| Git | — | No | Solo para clonar el repo |

---

## Instalación paso a paso (Linux)

### 1. Clonar el repositorio

```bash
git clone https://github.com/PauuUrrutia23/Ingenieria-de-Software-Grupo-12-Codigo.git ingecon
cd ingecon
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar el archivo `.env`

```bash
cp .env.example .env
```

Editar `.env` con tus datos de PostgreSQL:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ingecon_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

> Si PostgreSQL corre en Docker, usa la IP del contenedor o `127.0.0.1` si mapeaste el puerto.

### 4. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 5. Crear la base de datos en PostgreSQL

```bash
# Conéctate a PostgreSQL y crea la BD
psql -h 127.0.0.1 -U tu_usuario -d postgres -c "CREATE DATABASE ingecon_db ENCODING 'UTF8';"
```

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

Esto crea **10 tablas** y el administrador inicial.

### 7. (Opcional) Instalar dependencias frontend

```bash
npm install
npm run build    # compilar para producción
npm run dev      # hot-reload para desarrollo
```

> Para desarrollo rápido **no es obligatorio**: la app usa Tailwind CSS CDN y Alpine.js CDN.

### 8. Iniciar el servidor

```bash
php artisan serve
```

Abrir **http://localhost:8000** en el navegador.

---

## Instalación paso a paso (Windows)

Mismos pasos que Linux, con estas diferencias:

- Usar **Laravel Herd** (https://herd.laravel.com) para PHP + Composer en un solo instalador
- PostgreSQL: descargar de https://www.postgresql.org/download/windows/
- Verificar extensiones PHP: `php -m | findstr pgsql`
- Crear `.env`: `copy .env.example .env`
- Al crear la BD en PostgreSQL, omitir `LC_COLLATE`/`LC_CTYPE` (no disponibles en Windows)

---

## Tablas de la base de datos

| # | Tabla | Descripción |
|---|-------|-------------|
| 1 | `administrador` | Cuentas de acceso al panel admin |
| 2 | `visitante` | Datos de personas que envían consultas |
| 3 | `sesion` | Tokens de sesión activa del admin |
| 4 | `consulta` | Mensajes del formulario de contacto |
| 5 | `archivo_adjunto` | PDFs adjuntos a consultas (BYTEA) |
| 6 | `proyecto` | Obras del portafolio |
| 7 | `imagen_proyecto` | Fotografías de proyectos (BYTEA) |
| 8 | `certificado` | Certificados de calidad/lote (BYTEA) |
| 9 | `colaborador` | Empresas colaboradoras con logotipo (BYTEA) |

---

## Credenciales del panel admin

- **Email:** `admin@ingecon.cl`
- **Password:** `Ingecon2024!`

> Cambiar esta contraseña en producción.

---

## Estructura actual del proyecto

```
ingecon/
├── vistas/                    # Blade templates (V de MVC)
│   ├── admin/                 # Panel de administración
│   ├── auth/                  # Login y emails
│   ├── errors/                # Página 404
│   ├── layouts/               # Layouts público y admin
│   ├── partials/              # Navbar, sidebar-menu
│   └── public/                # Páginas públicas + partials
│
├── controlador/               # Controllers + Middleware + DBRouter
│   ├── AdminController.php
│   ├── AuthController.php
│   ├── ContactoController.php
│   ├── Controller.php
│   ├── DBRouterController.php   # Intermediario único de BD
│   ├── InstitucionalCtrl.php
│   ├── ProyectoController.php
│   └── AdminAuth.php            # Middleware de autenticación
│
├── base_datos/                # Models + Migrations + Seeders
│   ├── modelos/               # 9 modelos Eloquent
│   ├── migrations/            # 10 migraciones
│   ├── seeders/               # AdminSeeder + DatabaseSeeder
│   ├── factories/             # UserFactory
│   ├── 01_crear_base_datos.sql
│   └── 02_crear_tablas.sql
│
├── documentacion/             # Documentación del proyecto
│   ├── 01_setup_laravel_migraciones.md
│   ├── ...                    # (11 docs de incrementos)
│   ├── PRUEBAS.md
│   └── Ingecon_Como_Funciona.docx
│
├── app/                       # Jobs, Mail, Providers
├── bootstrap/
├── config/
├── routes/
├── public/
├── storage/
├── tests/
├── artisan
├── composer.json
├── package.json
├── INSTALACION.md
├── README.md
└── PLAN_DE_REORGANIZACION.md
```

---

## Comandos útiles

```bash
php artisan route:list              # Listar todas las rutas
php artisan route:list --path=admin # Solo rutas del panel admin
php artisan migrate:fresh --seed    # Recrear BD desde cero
php artisan view:clear              # Limpiar caché de vistas
php artisan config:clear            # Limpiar caché de configuración
composer dump-autoload              # Regenerar autoload PSR-4

# Limpiar todo el caché
php artisan route:clear && php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

---

## Errores comunes

| Problema | Solución |
|----------|----------|
| `could not find driver` / `pdo_pgsql` | Instalar/habilitar extensiones `pdo_pgsql` y `pgsql` en `php.ini` |
| `SQLSTATE[08006] connection refused` | PostgreSQL no está corriendo. Verificar con `systemctl status postgresql` o `pg_isready` |
| `password authentication failed` | `DB_USERNAME` / `DB_PASSWORD` en `.env` no coinciden con PostgreSQL |
| `Table ... not found` | Asegurar `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file` en `.env` |
| `Class "DBRouterController" not found` | Ejecutar `composer dump-autoload` |
| `View [admin.dashboard] not found` | Ejecutar `php artisan view:clear` |
| Error de boolean en seeder | La app usa `PDO::ATTR_EMULATE_PREPARES => true` en `config/database.php` para BYTEA |
