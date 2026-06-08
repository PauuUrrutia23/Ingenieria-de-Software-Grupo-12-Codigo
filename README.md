# Plataforma Web Ingecon

Sistema web para empresa constructora chilena: portafolio publico de proyectos, certificaciones, formulario de contacto y panel de administracion.

## Stack

| Componente | Version |
|------------|---------|
| Laravel | 11 |
| PHP | 8.2+ (8.3 recomendado) |
| PostgreSQL | 16 |
| Hashing | Argon2id |
| Frontend | Tailwind CSS CDN + Alpine.js 3.14.1 |
| Email | Resend SMTP |

---

## Requisitos previos

- **PHP 8.2+** con extensiones: `pdo_pgsql`, `pgsql`, `fileinfo`, `mbstring`, `openssl`, `tokenizer`
- **Composer 2.x**
- **PostgreSQL 16** corriendo (local o remoto)
- **Node.js + npm** (opcional, solo si usas Vite para compilar assets CSS/JS)
- **Git** (opcional)

---

## Instalacion y ejecucion

### 1. Clonar o copiar el proyecto

```bash
git clone <url-del-repo> ingecon
cd ingecon
```

Si no usas git, copia la carpeta `ingecon-fresh` a donde prefieras y entra en ella.

### 2. Instalar dependencias PHP

```bash
composer install
```

> Para produccion usar `composer install --no-dev --optimize-autoloader`.

### 3. Configurar el archivo `.env`

El proyecto ya incluye un `.env` preconfigurado para desarrollo local. Si necesitas crearlo desde cero:

```bash
cp .env.example .env
```

Variables criticas que debes revisar en `.env`:

```ini
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ingecon_db
DB_USERNAME=postgres         # Cambiar segun tu instalacion PostgreSQL
DB_PASSWORD=123              # Cambiar segun tu instalacion

SESSION_DRIVER=file          # file (desarrollo) | database (produccion)

MAIL_MAILER=log              # log (desarrollo) | smtp (produccion con Resend)
QUEUE_CONNECTION=sync        # sync (desarrollo) | database (produccion)
```

### 4. Generar APP_KEY

```bash
php artisan key:generate
```

> Si el `.env` ya tiene un `APP_KEY`, este paso es opcional.

### 5. Crear la base de datos en PostgreSQL

Abre `psql` como superusuario y ejecuta:

```sql
CREATE DATABASE ingecon_db
    WITH ENCODING 'UTF8'
    LC_COLLATE = 'es_CL.UTF-8'
    LC_CTYPE   = 'es_CL.UTF-8'
    TEMPLATE = template0;
```

> **Nota:** Si usas Windows, los locales `es_CL.UTF-8` pueden no estar disponibles. En ese caso omite `LC_COLLATE` y `LC_CTYPE`, o usa `'Spanish_Chile.1252'`.

Crea un usuario y asigna permisos (o usa tu usuario existente de PostgreSQL):

```sql
CREATE USER ingecon_user WITH ENCRYPTED PASSWORD 'tu_password';
GRANT ALL PRIVILEGES ON DATABASE ingecon_db TO ingecon_user;
\c ingecon_db
GRANT ALL ON SCHEMA public TO ingecon_user;
```

> Ajusta `DB_USERNAME` y `DB_PASSWORD` en `.env` segun el usuario que creaste o el que ya tengas.

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

Esto crea las 9 tablas del sistema y ejecuta el seeder inicial:

| Tabla | Descripcion |
|-------|-------------|
| `administrador` | Cuentas de acceso al panel admin |
| `visitante` | Datos de personas que envian consultas |
| `sesion` | Tokens de sesion activa del admin |
| `consulta` | Mensajes del formulario de contacto |
| `archivo_adjunto` | PDFs adjuntos a consultas (BYTEA) |
| `proyecto` | Obras del portafolio |
| `imagen_proyecto` | Fotografias de proyectos (BYTEA) |
| `certificado` | Certificados de calidad/lote (BYTEA) |
| `colaborador` | Empresas colaboradoras con logotipo (BYTEA) |

> El `AdminSeeder` (ejecutado via `DatabaseSeeder`) crea el administrador inicial:
> - **Email:** `admin@ingecon.cl`
> - **Password:** `Ingecon2024!`
>
> Cambia esta contrasena inmediatamente en produccion.

### 7. Instalar dependencias frontend (opcional)

Si necesitas compilar assets con Vite (Tailwind, JS):

```bash
npm install
npm run build     # compilar para produccion
npm run dev       # hot-reload para desarrollo
```

> Para desarrollo rapido, los layouts usan Tailwind CSS Play CDN y Alpine.js CDN, por lo que **no es obligatorio** ejecutar `npm install`.

### 8. Iniciar el servidor

```bash
php artisan serve
```

Abre `http://localhost:8000` en tu navegador.

---

## Resumen rapido (ya configurado)

Si clonaste el repo y tienes PostgreSQL corriendo con una BD `ingecon_db`:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

---

## Rutas de la aplicacion

### Rutas publicas

| Metodo | URL | Descripcion |
|--------|-----|-------------|
| `GET` | `/` | Pagina principal (one-page scroll con todas las secciones) |
| `POST` | `/contacto` | Envio de formulario de contacto (AJAX) |
| `GET` | `/proyectos/buscar` | Buscar proyectos publicados (JSON, filtros: `?texto=&categoria=`) |
| `GET` | `/proyectos/{id}/detalle` | Detalle de proyecto con imagenes (JSON) |
| `GET` | `/certificaciones` | Pagina de certificaciones activas |
| `GET` | `/certificaciones/{id}/descargar` | Descarga de PDF de certificado |
| `POST` | `/login` | Inicio de sesion admin (JSON, via modal Alpine.js) |

### Rutas protegidas (requieren sesion admin)

| Metodo | URL | Descripcion |
|--------|-----|-------------|
| `POST` | `/logout` | Cerrar sesion |
| `GET` | `/admin/dashboard` | Panel de administracion |
| `GET` | `/admin/proyectos/panel` | Vista HTML del modulo de proyectos |
| `GET` | `/admin/proyectos` | Listar proyectos del admin (JSON) |
| `POST` | `/admin/proyectos` | Crear proyecto con imagenes |
| `PUT` | `/admin/proyectos/{id}` | Editar proyecto (campos + gestion de imagenes) |
| `GET` | `/admin/colaboradores/panel` | Vista HTML del modulo de colaboradores |
| `GET` | `/admin/colaboradores` | Listar colaboradores del admin (JSON) |
| `POST` | `/admin/colaboradores` | Registrar colaborador con logotipo |

---

## Estructura del proyecto

```
├── vistas/                               # Blade templates (V de MVC)
│   ├── layouts/
│   │   ├── admin.blade.php               # Layout del panel administrativo
│   │   └── public.blade.php              # Layout publico (navbar + sidebar + login modal)
│   ├── partials/
│   │   ├── navbar.blade.php              # Barra de navegacion fija
│   │   └── sidebar-menu.blade.php        # Menu lateral deslizante
│   ├── admin/
│   │   ├── dashboard.blade.php           # Dashboard del panel
│   │   ├── proyectos.blade.php           # Modulo gestion de proyectos
│   │   └── colaboradores.blade.php       # Modulo gestion de colaboradores
│   ├── auth/
│   │   ├── login-modal.blade.php         # Modal de login Alpine.js
│   │   └── emails/
│   │       └── cuenta-bloqueada.blade.php # Plantilla email de bloqueo
│   ├── errors/
│   │   └── 404.blade.php                 # Pagina 404 personalizada
│   └── public/
│       ├── index.blade.php               # Pagina principal (one-page)
│       ├── certificaciones.blade.php     # Pagina independiente de certificaciones
│       └── partials/
│           ├── inicio.blade.php          # Seccion Hero/Inicio
│           ├── proyectos.blade.php       # Wrapper que incluye galeria
│           ├── galeria.blade.php         # Galeria con Alpine.js (filtros, busqueda, modal)
│           ├── certificaciones.blade.php # Listado de certificados para descarga
│           └── contacto.blade.php        # Formulario de contacto con Alpine.js
│
├── controlador/                          # Controllers + Middleware (C de MVC)
│   ├── AdminController.php               # CRUD proyectos + colaboradores
│   ├── AuthController.php                # Login/logout con bloqueo
│   ├── ContactoController.php            # Formulario de contacto publico
│   ├── Controller.php                    # Base controller
│   ├── DBRouterController.php            # Intermediario de base de datos
│   ├── InstitucionalCtrl.php             # Pagina principal
│   ├── ProyectoController.php            # Busqueda, detalle, certificaciones
│   └── AdminAuth.php                     # Middleware: verifica cookie de sesion admin
│
├── base_datos/                           # Models + Migrations + Seeders (M de MVC)
│   ├── modelos/                          # 9 modelos Eloquent
│   │   ├── Administrador.php
│   │   ├── ArchivoAdjunto.php
│   │   ├── Certificado.php
│   │   ├── Colaborador.php
│   │   ├── Consulta.php
│   │   ├── ImagenProyecto.php
│   │   ├── Proyecto.php
│   │   ├── Sesion.php
│   │   └── Visitante.php
│   ├── migrations/                       # 10 migraciones (tablas del sistema)
│   ├── seeders/                          # AdminSeeder + DatabaseSeeder
│   └── factories/                        # UserFactory
│
├── documentacion/                        # Documentacion del proyecto
│   ├── 01_setup_laravel_migraciones.md
│   ├── 02_modelos_eloquent.md
│   ├── 02b_dbrouter_controller.md
│   ├── 03_autenticacion.md
│   ├── 04_formulario_contacto.md
│   ├── 05_navegacion_publica.md
│   ├── 06_galeria_proyectos.md
│   ├── 07_certificaciones_publicas.md
│   ├── 08_admin_proyectos.md
│   ├── 09_admin_colaboradores.md
│   ├── 10_integracion_y_pruebas.md
│   ├── PRUEBAS.md
│   └── Ingecon_Como_Funciona.docx
│
├── app/                                  # Jobs, Mail, Providers
│   ├── Jobs/
│   │   └── EnviarEmailBloqueoJob.php     # Job asincrono de email de bloqueo
│   ├── Mail/
│   │   └── CuentaBloqueadaMail.php       # Mailable de cuenta bloqueada
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
├── config/
│   ├── database.php                      # Conexion pgsql + PDO::ATTR_EMULATE_PREPARES
│   └── hashing.php                       # Driver argon2id
├── routes/
│   └── web.php                           # Todas las rutas
├── public/
├── storage/
├── tests/
├── .env                                  # Variables de entorno
├── artisan
├── composer.json
├── package.json
├── INSTALACION.md                        # Guia de instalacion detallada
├── CHANGELOG.md
└── README.md
```

---

## Requerimientos funcionales implementados

### Incremento 1

| RF | Descripcion | Endpoint principal |
|----|-------------|-------------------|
| RF01 | Visitante envia consulta de contacto | `POST /contacto` |
| RF02 | Formulario con 6 campos (nombre, apellido, email, mensaje, fecha, adjunto PDF) | `GET /` |
| RF07 | Validaciones frontend + backend con mensajes en espanol | `POST /contacto` |
| RF12 | Menu lateral deslizante con navegacion | `GET /` |
| RF13 | Barra de navegacion fija con scroll suave | `GET /` |
| RF20 | Filtro de proyectos por texto libre (ILIKE en PostgreSQL) | `GET /proyectos/buscar?texto=` |
| RF21 | Filtro de proyectos por categoria (Habitacional/Industrial/Agricola) | `GET /proyectos/buscar?categoria=` |
| RF24 | Modal detalle proyecto con carrusel de imagenes | `GET /proyectos/{id}/detalle` |
| RF25 | Visualizar certificaciones activas con metadatos | `GET /certificaciones` |
| RF26 | Descargar PDF de certificado desde BYTEA | `GET /certificaciones/{id}/descargar` |
| RF28 | Login admin con credenciales (modal Alpine.js) | `POST /login` |
| RF33 | Logout con invalidacion de sesion | `POST /logout` |
| RF34 | Bloqueo por 5 intentos fallidos (60 min) + notificacion email | `POST /login` |
| RF46 | Registrar colaborador (nombre comercial + logotipo BYTEA) | `POST /admin/colaboradores` |
| RF49 | Crear proyecto con fotografias (estado inicial: borrador) | `POST /admin/proyectos` |
| RF50 | Editar proyecto existente con gestion de imagenes | `PUT /admin/proyectos/{id}` |

---

## Verificacion de funcionamiento

Para probar cada funcionalidad implementada, revisa el archivo [`PRUEBAS.md`](documentacion/PRUEBAS.md) que contiene flujos de prueba paso a paso para cada requerimiento funcional.

---

## Notas tecnicas

### Almacenamiento BYTEA en PostgreSQL

Todas las imagenes, PDFs y logotipos se almacenan como binario (`BYTEA`) directamente en PostgreSQL. Los modelos Eloquent usan accessors que convierten el binario a Data URIs base64 para renderizar en vistas Blade. Los controladores usan `stream_get_contents()` cuando el driver pgsql devuelve el BYTEA como resource stream.

Configuracion relevante en `config/database.php`:

```php
'pgsql' => [
    // ...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
],
```

### Autenticacion

Sistema de sesiones propio (sin Breeze ni Fortify):

- Cookie httpOnly: `ingecon_session` (formato: `id_sesion|token`)
- El token en claro nunca se persiste en BD (se guarda su hash con Argon2id)
- El middleware `AdminAuth` busca la sesion por ID y verifica el token con `Hash::check()`
- Bloqueo de cuenta: 5 intentos fallidos → 60 minutos. Dispara job `EnviarEmailBloqueoJob`

### Frontend

- **Tailwind CSS**: Play CDN en layouts (`public.blade.php` y `admin.blade.php`). En produccion, reemplazar por build con Vite (`npm run build`)
- **Alpine.js 3.14.1**: CDN con version fijada (no `@3.x.x`) para consistencia entre deploys
- Los modales y formularios usan Alpine.js con `fetch()` para comunicacion JSON con el backend
- Validaciones en dos capas: frontend (Alpine.js, antes del fetch) y backend (Laravel validation)

### Email

Configurado para Resend SMTP. En desarrollo se recomienda usar `MAIL_MAILER=log` para ver los correos en `storage/logs/laravel.log` sin necesidad de un servidor SMTP.

### Comandos utiles

```bash
php artisan route:list              # Listar todas las rutas
php artisan route:list --path=admin # Solo rutas del panel admin
php artisan migrate:fresh --seed    # Recrear BD desde cero con datos iniciales

# Limpiar cache (util tras cambios de config)
php artisan route:clear && php artisan config:clear && php artisan cache:clear

# Ver logs
tail -f storage/logs/laravel.log    # Linux/macOS
Get-Content storage/logs/laravel.log -Wait  # PowerShell
```
