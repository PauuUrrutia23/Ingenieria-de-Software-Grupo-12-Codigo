# Plataforma Web Ingecon

## Stack

Laravel 11 · PHP 8.2+ · PostgreSQL 16 · Argon2id · Tailwind CSS · Alpine.js 3.14

---

## Requisitos

- **PHP 8.2 o superior** (extensiones: `pdo_pgsql`, `pgsql`, `fileinfo`)
- **Composer 2.x**
- **PostgreSQL 16** corriendo en `localhost:5432`
- **Git** (opcional)

---

## Paso a paso para ejecutar

### 1. Clonar o copiar el proyecto

```bash
git clone <url-del-repo> ingecon
cd ingecon
```

Si no usas git, copia la carpeta `ingecon-fresh` a donde prefieras.

### 2. Instalar dependencias PHP

```bash
composer install --no-dev
```

### 3. Configurar variables de entorno

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

Edita `.env` y asegurate que estos valores sean correctos:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ingecon_db
DB_USERNAME=ingecon_user
DB_PASSWORD=secret_password
```

### 4. Generar la clave de la aplicacion

```bash
php artisan key:generate
```

### 5. Crear la base de datos en PostgreSQL

Abre `psql` como superusuario y ejecuta:

```sql
CREATE DATABASE ingecon_db
    WITH ENCODING 'UTF8'
    LC_COLLATE = 'es_CL.UTF-8'
    LC_CTYPE   = 'es_CL.UTF-8'
    TEMPLATE = template0;

CREATE USER ingecon_user WITH ENCRYPTED PASSWORD 'secret_password';
GRANT ALL PRIVILEGES ON DATABASE ingecon_db TO ingecon_user;

\c ingecon_db
GRANT ALL ON SCHEMA public TO ingecon_user;
```

### 6. Ejecutar migraciones

```bash
php artisan migrate:fresh
```

Esto crea las 9 tablas del sistema: `administrador`, `visitante`, `sesion`, `consulta`, `archivo_adjunto`, `proyecto`, `imagen_proyecto`, `certificado`, `colaborador`.

### 7. Crear el administrador inicial

```bash
php artisan db:seed --class=AdminSeeder
```

Credenciales: `admin@ingecon.cl` / `Ingecon2024!`

### 8. Iniciar el servidor

```bash
php artisan serve
```

Abre `http://localhost:8000` en tu navegador.

---

## Resumen rapido (ya configurado)

```bash
composer install --no-dev
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

---

## Rutas principales

| URL | Descripcion | Auth |
|-----|-------------|------|
| `GET /` | Pagina principal (scroll one-page) | No |
| `POST /contacto` | Formulario de contacto publico | No |
| `GET /certificaciones` | Listado de certificados | No |
| `GET /certificaciones/{id}/descargar` | Descargar PDF de certificado | No |
| `POST /login` | Iniciar sesion (modal) | No |
| `POST /logout` | Cerrar sesion | Si |
| `GET /admin/dashboard` | Panel de administracion | Si |
| `GET /admin/proyectos/panel` | Gestion de proyectos | Si |
| `GET /admin/colaboradores/panel` | Gestion de colaboradores | Si |

---

## Estructura del proyecto

```
ingecon/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php
│   │   │   ├── AuthController.php
│   │   │   ├── ContactoController.php
│   │   │   ├── InstitucionalCtrl.php
│   │   │   └── ProyectoController.php
│   │   └── Middleware/
│   │       └── AdminAuth.php
│   ├── Jobs/
│   │   └── EnviarEmailBloqueoJob.php
│   ├── Mail/
│   │   └── CuentaBloqueadaMail.php
│   └── Models/
│       ├── Administrador.php
│       ├── ArchivoAdjunto.php
│       ├── Certificado.php
│       ├── Colaborador.php
│       ├── Consulta.php
│       ├── ImagenProyecto.php
│       ├── Proyecto.php
│       ├── Sesion.php
│       └── Visitante.php
├── resources/views/
│   ├── layouts/
│   │   ├── admin.blade.php
│   │   └── public.blade.php
│   ├── partials/
│   │   ├── navbar.blade.php
│   │   └── sidebar-menu.blade.php
│   ├── admin/
│   │   ├── proyectos.blade.php
│   │   └── colaboradores.blade.php
│   ├── auth/
│   │   ├── login-modal.blade.php
│   │   └── emails/
│   │       └── cuenta-bloqueada.blade.php
│   ├── errors/
│   │   └── 404.blade.php
│   └── public/
│       ├── index.blade.php
│       ├── certificaciones.blade.php
│       └── partials/
│           ├── inicio.blade.php
│           ├── proyectos.blade.php
│           ├── galeria.blade.php
│           ├── certificaciones.blade.php
│           └── contacto.blade.php
├── database/
│   ├── migrations/
│   │   └── 2024_01_01_000001 ... 000009
│   └── seeders/
│       ├── AdminSeeder.php
│       └── DatabaseSeeder.php
├── routes/
│   └── web.php
├── config/
│   └── hashing.php         (driver argon2id)
├── bootstrap/
│   └── app.php             (middleware admin.auth registrado)
├── PRUEBAS.md              (checklist de verificacion)
└── .env
```

## Requerimientos funcionales implementados (Incremento 1)

| RF | Descripcion |
|----|-------------|
| RF01 | Visitante envia consulta de contacto |
| RF02 | Formulario con 6 campos (nombre, apellido, email, mensaje, fecha, adjunto PDF) |
| RF07 | Validaciones frontend + backend con mensajes en espanol |
| RF12 | Menu lateral deslizante con navegacion |
| RF13 | Barra de navegacion fija con scroll suave |
| RF20 | Filtro de proyectos por texto (ILIKE) |
| RF21 | Filtro de proyectos por categoria |
| RF24 | Modal detalle proyecto con carrusel de imagenes |
| RF25 | Visualizar certificaciones activas |
| RF26 | Descargar PDF de certificado (BYTEA) |
| RF28 | Login admin con credenciales Argon2id |
| RF33 | Logout con invalidacion de sesion |
| RF34 | Bloqueo por 5 intentos fallidos (60 min) + email |
| RF46 | Registrar colaborador con logotipo |
| RF49 | Crear proyecto con fotografias (estado borrador) |
| RF50 | Editar proyecto con gestion de imagenes |

## Notas

- **Alpine.js**: cargado via CDN en layouts. Version fijada a 3.14.1 en `public.blade.php`.
- **Tailwind CSS**: Play CDN para desarrollo. En produccion reemplazar por build con Vite.
- **BYTEA**: imagenes y PDFs se almacenan como binario en PostgreSQL. Los controladores convierten `resource stream` a string con `stream_get_contents()`.
- **Sesiones**: autenticacion via cookie `ingecon_session` con formato `id_sesion|token`. El middleware `AdminAuth` busca la sesion por ID y verifica con `Hash::check()`.
- **email**: configurado para Resend SMTP en `.env`. Para desarrollo usar `MAIL_MAILER=log`.
