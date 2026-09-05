# Plataforma Web Ingecon

Sistema web para Ingecon (industrialización de la madera y construcción prefabricada):
portafolio público de proyectos, certificaciones, colaboradores, formulario de contacto y
Panel de Gestión administrativo. Implementa el **100% de los Incrementos 1 y 2** definidos en
[`REQUISITOS.md`](REQUISITOS.md) (38 Requerimientos Funcionales, 17 No Funcionales y 64 Casos
de Uso) — ver también [`ARQUITECTURA.md`](ARQUITECTURA.md) para el modelo de datos y el plan de
fases. Este README cubre cómo instalar y correr el proyecto tal como está.

## Stack

| Componente | Versión |
|---|---|
| Laravel | 10.10 |
| PHP | 8.1+ |
| Base de datos (dev) | SQLite |
| Base de datos (prod) | MySQL 5.6 (hosting compartido cPanel) |
| Hashing | Argon2id (fallback `bcrypt` si `sodium` no está disponible) |
| Frontend | Tailwind CSS 4 + Alpine.js 3, compilados con Vite |
| Email | `log` en desarrollo · `sendmail` en producción |
| Validación de archivos | `finfo` (MIME real) + verificación de cabecera para PDF |

> El stack de producción (MySQL, sendmail, límites de hosting compartido) está fijado en la
> Dimensión Técnica del proyecto — no cambiar a Postgres, colas, Sanctum/API pública, etc. sin
> actualizar ese documento primero.

---

## Puesta en marcha rápida (Windows)

Para no instalar nada a mano: ejecuta **[`INICIAR_INGECON.bat`](INICIAR_INGECON.bat)** desde la
raíz del proyecto. El script:

1. Revisa si están Scoop, PHP 8.1, Composer y Node/npm — instala automáticamente lo que falte.
2. Habilita las extensiones de PHP necesarias (`gd`, `fileinfo`, `pdo_sqlite`, etc.) si vienen
   comentadas en el `php.ini`.
3. Instala dependencias (`composer install`, `npm install`) y compila los assets solo si hace
   falta — en una segunda ejecución en la misma máquina es prácticamente instantáneo.
4. Crea `.env`, genera `APP_KEY`, crea la base SQLite, corre migraciones y siembra los datos
   base (idempotente: no duplica nada si ya existen).
5. Levanta `php artisan serve` y abre el navegador en la pantalla de inicio de sesión.

Al terminar, quedan dos ventanas abiertas: el servidor (no cerrarla mientras se use la página)
y el navegador. Las credenciales de administrador se muestran al final del script (ver también
más abajo).

Si prefieres instalar todo manualmente, o estás en Linux/Mac, sigue la sección siguiente.

---

## Estructura del proyecto

El código Laravel está repartido en 4 carpetas dentro de [`web/`](web/), separadas por
responsabilidad (M-V-C + núcleo del framework):

```
web/
├── nucleo/                 # Esqueleto de Laravel: bootstrap, config, public, routes,
│                           # storage, tests, vendor, node_modules, composer.json, .env,
│                           # y lo que Laravel espera en su propio app/ (Mail, Providers,
│                           # Console, Exceptions, Http/Kernel.php)
│
├── controlador/            # App\Http\Controllers\*, App\Rules\*
│   ├── Middleware/         # App\Http\Middleware\*
│   ├── Requests/           # App\Http\Requests\*
│   └── Rules/              # PdfValido (RNF04/06), DominioCorreoValido (CU 1.1 exc. 4)
│
├── base_datos/             # App\Models\* + migraciones + factories + seeders
│   ├── modelos/            # Visitante, Administrador, Sesion, RecuperacionPassword, Proyecto,
│   │                       # ImagenProyecto, Certificado, Colaborador, Contenido
│   ├── migrations/         # 10 migraciones (ver ARQUITECTURA.md §3 para el esquema completo)
│   ├── factories/
│   ├── seeders/            # AdminJefeSeeder, EjemploDatosSeeder, DatabaseSeeder
│   └── database.sqlite     # BD local de desarrollo (no versionada)
│
└── vista/                  # resources/views de Laravel, movida aquí completa
    ├── components/         # app-layout (público), admin-layout (panel), modal (Ventana Modal)
    ├── public/              # index, proyectos, certificaciones, colaboradores, producto
    │   └── partials/        # fragmento de galería reutilizado por el filtrado dinámico (RF21)
    ├── admin/               # dashboard, proyectos, certificados, colaboradores, consultas,
    │                        # contenido (FAQ/Opiniones/Banner/Fases), password, qa
    ├── auth/                # login (Ventana Modal), reset-password
    ├── legal/               # términos y condiciones
    └── emails/              # consulta_recibida, cuenta_bloqueada, recuperacion_password

scripts/                      # helpers de INICIAR_INGECON.bat (habilitar extensiones de PHP)
REQUISITOS.md                 # requerimientos y casos de uso (documentación, fuera de web/)
ARQUITECTURA.md               # arquitectura y plan de fases (documentación, fuera de web/)
INICIAR_INGECON.bat           # instalador + arranque de un solo clic (Windows)
```

**Cómo funciona esto por dentro:** `controlador/`, `base_datos/modelos/`, `base_datos/factories/`
y `base_datos/seeders/` NO son carpetas de Laravel por convención — existen porque
`web/nucleo/composer.json` mapea esos namespaces PHP (`App\Http\Controllers\`, `App\Models\`,
`App\Rules\`, `Database\Factories\`, `Database\Seeders\`) a esas rutas físicas vía PSR-4.
`vista/` funciona porque `web/nucleo/config/view.php` le dice a Blade que busque ahí. Las
migraciones se cargan desde `base_datos/migrations` porque `AppServiceProvider::boot()` lo
registra explícitamente. Si mueves o renombras algo dentro de estas carpetas, **tenés que
actualizar esos archivos** (`composer.json`, `config/view.php`, `AppServiceProvider.php`) o
Laravel no va a encontrar las clases/vistas.

---

## Requisitos previos

- **PHP 8.1+** con extensiones: `pdo_sqlite` (dev) o `pdo_mysql` (prod), `fileinfo`, `gd`, `mbstring`, `openssl`, `tokenizer`
- **Composer 2.x**
- **Node.js + npm** (para compilar Tailwind/Alpine con Vite)
- **Git**

En Windows, `INICIAR_INGECON.bat` instala y configura todo esto automáticamente.

---

## Instalación y ejecución manual (desarrollo local)

Los comandos de Composer/Artisan/npm se corren parado dentro de `web/nucleo/` (ahí vive
`composer.json` y `artisan`):

```bash
cd web/nucleo

composer install
npm install

cp .env.example .env      # si no existe ya un .env
php artisan key:generate  # si el .env no trae APP_KEY

php artisan migrate
php artisan db:seed       # idempotente: crea el admin y datos de ejemplo si no existen
php artisan storage:link

npm run build      # o `npm run dev` para hot-reload
php artisan serve
```

Abre `http://localhost:8000`.

El `.env` de desarrollo ya trae `DB_CONNECTION=sqlite`, `MAIL_MAILER=log` y
`QUEUE_CONNECTION=sync` — no requiere un servidor de base de datos ni de correo aparte.

> **Si algo tira "Class not found" o "View not found"**: corré `composer dump-autoload`, o
> revisá que no haya un typo en las rutas relativas de `nucleo/composer.json` /
> `nucleo/config/view.php`.

### Cuenta de Administrador Jefe sembrada

El seeder `AdminJefeSeeder` (llamado desde `DatabaseSeeder`) crea:

- **Correo:** `admin@ingecon.cl`
- **Contraseña:** `Admin123!`

Cambiar esta contraseña antes de cualquier despliegue real.

---

## Rutas de la aplicación

### Públicas

| Método | URL | Descripción |
|---|---|---|
| `GET` | `/` | Página de inicio (proyectos recientes, certificados, colaboradores, formulario de contacto) |
| `GET` | `/proyectos` | Galería pública de proyectos con filtros combinados (texto, categoría, ubicación) |
| `GET` | `/producto` | Ficha de Conectores Metálicos |
| `GET` | `/certificaciones` | Listado completo de certificaciones vigentes |
| `GET` | `/certificaciones/{certificado}/descargar` | Descarga del PDF con nombre de archivo seguro |
| `GET` | `/colaboradores` | Listado público de colaboradores |
| `GET` | `/conectores/documentacion` | Redirección a la documentación técnica (URL resuelta desde BD) |
| `POST` | `/contacto` | Envío del formulario de contacto |
| `GET` | `/terminos` | Términos y Condiciones / Política de Privacidad |
| `GET` / `POST` | `/login` | Ventana Modal e inicio de sesión del Personal de Administración |
| `POST` | `/password/email` | Solicitud de enlace de recuperación de contraseña |
| `GET` | `/password/restablecer/{token}` | Formulario de restablecimiento |
| `POST` | `/password/restablecer` | Confirmación del restablecimiento |

### Panel de Gestión (requieren sesión de administrador)

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/logout` | Cerrar sesión |
| `GET` | `/admin/dashboard` | Panel principal |
| `resource` | `/admin/proyectos` | CRUD de proyectos + `PATCH /admin/proyectos/{p}/visibilidad` (Borrador ⇄ Publicado) + `DELETE /admin/imagenes/{imagen}` |
| `resource` | `/admin/certificados` | CRUD de certificados |
| `resource` | `/admin/colaboradores` | CRUD de colaboradores |
| `resource` (parcial) | `/admin/consultas` | Listado paginado, detalle y actualización de estado |
| `resource` (parcial) | `/admin/contenido` | Gestión de FAQ, Opiniones, Banner de Inicio y Fases Industriales |
| `GET` / `PUT` | `/admin/password` | Cambio de contraseña del administrador autenticado |
| `GET` | `/admin/qa` + `/admin/qa/stream` | Bitácora de pruebas automáticas en vivo (solo entorno `local`) |

Ver `web/nucleo/routes/web.php` para el detalle exacto, o `php artisan route:list` (parado en
`web/nucleo/`).

---

## Estado de la implementación

Los 38 Requerimientos Funcionales, los 17 No Funcionales y los 64 Casos de Uso de
`REQUISITOS.md` (Incrementos 1 y 2 completos) están implementados y verificados en vivo. Los
requisitos No Funcionales de infraestructura (RNF07 disponibilidad, RNF14 respaldos, RNF15
concurrencia) son responsabilidad del hosting cPanel de producción y no aplican al código.

---

## Tests

```bash
cd web/nucleo
php artisan test
```

70 tests / 182 aserciones, cubriendo formulario de contacto, autenticación y bloqueo de cuenta,
filtros de proyectos, certificaciones, gestión de colaboradores/proyectos/certificados,
recuperación de contraseña, gestión de contenido multimedia y reglas de negocio (límites de
archivos, validación de PDF real, dominios de correo, etc.). Corren contra una base SQLite en
memoria — la base de datos de desarrollo no se toca. También se pueden ejecutar en vivo, viendo
cada test aparecer en tiempo real, desde **Panel de Gestión → Bitácora de Pruebas** (solo en
entorno `local`).

---

## Comandos útiles

Todos se ejecutan parado dentro de `web/nucleo/`:

```bash
cd web/nucleo

php artisan route:list                 # listar todas las rutas
php artisan migrate:fresh --seed       # recrear la BD desde cero
php artisan test                       # correr la suite de tests
php artisan route:clear && php artisan config:clear && php artisan cache:clear
```
