# Ingecon — Arquitectura, Modularidad y Plan de Trabajo (hasta Incremento 2)

> Complementa a [`REQUISITOS.md`](REQUISITOS.md) (fuente de verdad de RF/RNF/CU). Este
> documento define **cómo** se construye: arquitectura, estructura de carpetas, modelo de
> datos, módulos, flujo de trabajo Git y un plan de fases paso a paso — cada fase con sus
> tareas técnicas, archivos a crear y **tests** — cubriendo el 100% de Incremento 1 + 2
> (38 RF / 64 CU) sin saltarse nada.
>
> El diagrama de componentes (§1.1) y el modelo de datos (§3) están tomados directamente de
> los diagramas oficiales del equipo: `Ingecon_Diagrama_Componentes_hosting_cPanel.drawio` y
> `DiagramasBD_completos_proyecto_v4 MYSQL(1).drawio` — no son una propuesta nueva, son la
> arquitectura y el esquema de BD ya definidos, traducidos aquí a estructura Laravel concreta.
>
> **Nota de ruta:** el código vive repartido en 4 carpetas dentro de [`web/`](web/):
> `web/nucleo/` (esqueleto de Laravel — ahí están `composer.json` y `artisan`, pararse ahí
> para correr comandos), `web/controlador/` (Controllers/Middleware/Requests),
> `web/base_datos/` (Models/migrations/factories/seeders) y `web/vista/` (Blade views). Ese
> reparto funciona por mapeos PSR-4 en `nucleo/composer.json` + rutas custom en
> `nucleo/config/view.php` y `nucleo/app/Providers/AppServiceProvider.php` — ver el detalle en
> [`README.md`](README.md#estructura-del-proyecto). Las rutas de este documento (§2 y las
> fases) usan los nombres de carpeta Laravel convencionales (`app/Http/Controllers`,
> `resources/views`, etc.) por claridad de arquitectura — mentalmente traducilos a la carpeta
> real usando esa tabla.

---

## 1. Arquitectura general

**Monolito Laravel 10, MVC clásico, sobre hosting compartido cPanel (sin colas, sin websockets, sin procesos daemon).**

```
┌─────────────────────────────────────────────────────────────────┐
│  Capa de Presentación (Blade + Tailwind CSS + Alpine.js)         │
│  - Vistas públicas (Visitante)                                  │
│  - Vistas del Panel de Gestión (Personal de Administración)     │
│  - Componentes Blade reutilizables + x-data de Alpine (modales) │
└───────────────────────────▲───────────────────────────────────-┘
                             │ requests HTTP (rutas web, sin API pública)
┌───────────────────────────┴───────────────────────────────────-┐
│  Capa de Lógica de Negocio (Laravel: Controllers, FormRequests, │
│  Policies/Middleware, Mail, Services)                            │
│  - Controllers delgados, validación en FormRequest               │
│  - Reglas de negocio (límites, hashing, tokens) en Services      │
│  - Middleware: auth admin, throttle, verificación de sesión      │
└───────────────────────────▲───────────────────────────────────-┘
                             │ Eloquent ORM
┌───────────────────────────┴───────────────────────────────────-┐
│  Capa de Datos (MySQL 5.6, Eloquent Models, Migrations, Seeders)│
└──────────────────────────────────────────────────────────────-─┘
```

**Decisiones de arquitectura obligatorias (de la Dimensión Técnica):**
- Laravel 10, PHP ≤ 8.1 (compatibilidad con el hosting).
- Blade + Tailwind + Alpine.js — **no** SPA, **no** Vue/React, **no** API REST separada (todo server-rendered).
- Auth de administración con **Laravel's built-in session auth** (guard `web`, no Sanctum/Passport — no hay API pública que lo justifique).
- Sin colas (`QUEUE_CONNECTION=sync`): el hosting no permite workers persistentes. Los correos se envían de forma síncrona vía `sendmail`.
- Sin Job scheduler persistente: los respaldos van por **Cron Job de cPanel** ejecutando un Artisan Command (`php artisan backup:run`), no por el scheduler de Laravel corriendo en background.
- Todas las subidas de archivos (imágenes, PDFs) van a `storage/app/public` con symlink (`php artisan storage:link`), nunca a la BD como BLOB (excepto restricciones RT-02).

### 1.1 Diagrama de Componentes oficial (`Ingecon_Diagrama_Componentes_hosting_cPanel.drawio`)

El equipo ya definió la arquitectura de componentes en detalle (notación UML `«Boundary»` /
`«Control»` / `«Entity»`). Este es el diagrama de referencia — todo controller/servicio que se
cree en el código **debe calzar con uno de estos componentes**, no inventar nombres nuevos.

```
Visitante ─┐                                      ┌─▶ OpenStreetMap (tiles, GET /tiles/...)
           ▼                                       │
     ┌─────────────── Cliente Web (Boundary) ──────┴───┐
     │  Vistas Blade ◀── Tailwind CSS (estilos)         │
     │             ◀── Alpine.js (reactividad)          │
     │             ◀── Leaflet.js (mapas) ───────────────┘
     └───────────────────────┬───────────────────────────┘
                              │ HTTPS
     ┌────────────────────── Servidor Web ───────────────────────────────┐
     │  Apache ──reenvía──▶ Router HTTP ──▶ Controladores (Control):     │
     │                                        · AuthController           │
     │                                        · InstitucionalController  │
     │                                        · ContactoController       │
     │                                        · ProyectoController       │
     │                                        · CrmController            │
     │                                        · AdminController          │
     │                                              │                    │
     │      Servicios (Control/Entity/Boundary):    ▼                    │
     │      NotificationService ──▶ SendmailAdapter (/usr/sbin/sendmail) │
     │      Argon2id (hash, fallback bcrypt)                             │
     │      FinfoValidator (valida MIME real de PDFs)                    │
     │      StorageAdapter (archivos en storage/, fuera de BD)           │
     │      Spatie Excel (exportación XLSX)                              │
     │                                              │                    │
     │      DBRouterController (Control) ──▶ Eloquent ORM (Entity)       │
     └──────────────────────────┬──────────────────┬─────────────────---┘
                                 │ TCP:3306         │ I/O
     ┌────────────────────── Servidor BD (Entity) ──┴──────────────────┐
     │  MySQL 5.6  ◀── mysqldump ── Cron Jobs ──▶ Filesystem (backups) │
     │  Filesystem  ◀────────────────────────────── I/O archivos/PDF   │
     └───────────────────────────────────────────────────────────────-┘
```

**Mapeo componente oficial → controller Laravel (carpeta §2):**

| Componente oficial | Controllers Laravel equivalentes |
|---|---|
| `AuthController` | `Auth/LoginController`, `Auth/LogoutController`, `Auth/PasswordController` |
| `InstitucionalController` | `Public/HomeController` (navegación, banner, footer, T&C) |
| `ContactoController` | `Public/ContactoController` |
| `ProyectoController` | `Public/ProyectoPublicoController` (galería) **+** `Admin/ProyectoController` (CRUD) — mismo componente lógico del diagrama, separado en dos controllers Laravel por guard (público vs `auth:admin`) |
| `CertificadoController` | Certificaciones completas: listado público y descarga del PDF (RF24/RF25) + CRUD del Panel de Gestión (RF26). Agregado al Diagrama de Componentes v3. |
| `AdminController` | Dashboard, Colaboradores, Contenido multimedia y Consultas Comerciales (RF36/39/41) — el módulo comercial no tiene componente propio en el diagrama |
| `NotificationService` / `SendmailAdapter` | `app/Services/*` + `app/Mail/*` (ya definidos en §2) |
| `FinfoValidator` | `app/Services/PdfValidationService.php` |
| `Argon2id` | Config `hashing.php` (`driver => argon2id`, fallback `bcrypt`) — no es un service propio, es config nativa de Laravel |
| `StorageAdapter` | `app/Services/ImageUploadService.php` + filesystem `public` |
| `DBRouterController` | Los **Models Eloquent** (`app/Models/*`) — el diagrama lo representa como un componente intermedio, en Laravel es transparente vía Eloquent, no requiere una clase propia |

**Nota de alcance:** el diagrama es la arquitectura **completa del proyecto** (incluye
`CrmController`/reportes y `Spatie Excel` que cubren RF42, Prioridad 3 — fuera de Incremento
1-2). Se documenta igual para que la base de código ya calce con el destino final y no haya
que refactorizar componentes en Incremento 3.

---

## 2. Estructura de carpetas (Laravel, por módulo funcional)

Todo lo siguiente vive dentro de `web/` (raíz del repo → `web/app/`, `web/resources/`, etc.):

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Public/                     # Visitante — sin auth
│   │   │   ├── HomeController.php
│   │   │   ├── ContactoController.php       # RF01,04,05,06,08,09
│   │   │   ├── ProyectoPublicoController.php# RF19,20,23,18,21
│   │   │   └── CertificacionPublicaController.php # RF24,25
│   │   ├── Auth/
│   │   │   ├── LoginController.php          # RF27, RF33
│   │   │   ├── LogoutController.php         # RF32
│   │   │   └── PasswordController.php       # RF28,29,30,31
│   │   └── Admin/                      # Personal de Administración — auth:admin
│   │       ├── DashboardController.php      # RF34
│   │       ├── ColaboradorController.php    # RF45,46,47
│   │       ├── ProyectoController.php       # RF48,49,50,51
│   │       ├── CertificadoController.php    # RF26
│   │       ├── ConsultaController.php       # RF36,39,41
│   │       └── ContenidoController.php      # RF43,44
│   ├── Middleware/
│   │   ├── EnsureAdminAuthenticated.php     # RNF03
│   │   └── RedirectIfLockedOut.php          # RF33
│   ├── Requests/
│   │   ├── Public/StoreConsultaRequest.php  # DS-39..DS-43
│   │   ├── Admin/StoreProyectoRequest.php   # DS-44..DS-49, RNF17
│   │   ├── Admin/StoreColaboradorRequest.php# DS-68,DS-69
│   │   ├── Admin/StoreCertificadoRequest.php
│   │   └── Auth/ChangePasswordRequest.php   # DS-51
├── Models/
│   ├── Visitante.php
│   ├── Consulta.php
│   ├── Administrador.php
│   ├── Sesion.php                (si se modela aparte de sessions de Laravel)
│   ├── RecuperacionPassword.php
│   ├── Proyecto.php
│   ├── ImagenProyecto.php
│   ├── Certificado.php
│   ├── Colaborador.php
│   └── Contenido.php
├── Services/
│   ├── ConsultaLimiteService.php      # CU1.1 Excepción 3 (máx 5/24h)
│   ├── PasswordPolicyService.php      # DS-51, CU28.2/CU31.2
│   ├── PdfValidationService.php       # finfo — RNF04/06
│   ├── ImageUploadService.php         # RNF17, límites 5MB/15 imgs
│   └── LoginAttemptService.php        # RF33, CU27.2, CU33.1
├── Mail/
│   ├── ConsultaRecibidaAdmin.php      # CU34.2 / alerta a admin (RF35 fuera de alcance* ver nota)
│   ├── PasswordResetLink.php          # CU30.1
│   └── AccountLocked.php              # CU33.1
└── Console/Commands/
    └── BackupDatabase.php             # Cron + mysqldump

resources/
├── views/
│   ├── layouts/ (app.blade.php público, admin.blade.php panel)
│   ├── public/  (home, proyectos, certificaciones, colaboradores)
│   ├── auth/    (login modal parcial, recuperar, restablecer)
│   └── admin/   (dashboard, proyectos, colaboradores, certificados, comercial, contenido)
├── css/app.css (Tailwind entry)
└── js/app.js (Alpine entry)

database/
├── migrations/
├── factories/
├── seeders/

tests/
├── Feature/
│   ├── Public/  (uno por CU público)
│   ├── Auth/
│   └── Admin/
└── Unit/
    └── Services/
```

> Nota: RF35 ("Alertando Nueva Consulta Comercial", CU 35.1) aparece en `Documento_0.docx`
> pero **no está en la lista de RF de Incremento 1 ni 2** — no se incluye como fase obligatoria,
> pero se deja el `Mail` preparado porque CU 34.2 (Módulo comercial) lo da por hecho. Confirmar
> con el equipo si se adelanta o se deja fuera.

---

## 3. Modelo de datos (esquema oficial — `DiagramasBD_completos_proyecto_v4_MYSQL.drawio`)

El equipo ya diseñó el modelo físico completo (vista de tabla + ERD notación Chen). Es la
fuente de verdad para las migraciones — **usar exactamente estos nombres de columna y
tipos**, no los que se puedan inferir de las DS-39..DS-71 a mano.

### 3.1 Esquema de columnas por tabla

| Tabla (migración, plural snake_case) | Columna | Tipo MySQL | Clave |
|---|---|---|---|
| **`administradores`** (`ADMINISTRADOR`) | id_admin | BIGINT AUTO_INCREMENT | PK |
| | correo | VARCHAR(150) | UK |
| | password_hash | VARCHAR(255) | |
| | rol | VARCHAR(20) | |
| | intentos_fallidos | SMALLINT | |
| | bloqueado_hasta | DATETIME | NULL |
| | activo | BOOLEAN | |
| **`sesiones`** (`SESION`) | id_sesion | BIGINT AUTO_INCREMENT | PK |
| | token_hash | VARCHAR(255) | |
| | fecha_inicio | DATETIME | |
| | estado | VARCHAR(20) | |
| | id_admin | BIGINT | FK → administradores |
| **`recuperaciones_password`** (`RECUPERACION_PASSWORD`) | id_recuperacion | BIGINT AUTO_INCREMENT | PK |
| | id_admin | BIGINT | FK → administradores |
| | token_hash | VARCHAR(255) | |
| | expira_en | DATETIME | |
| | usado_en | DATETIME | NULL |
| | created_at | DATETIME | |
| **`visitantes`** (`VISITANTE`) | id_visitante | BIGINT AUTO_INCREMENT | PK |
| | nombre | VARCHAR(80) | |
| | apellido | VARCHAR(80) | NULL |
| | email | VARCHAR(150) | UK |
| **`colaboradores`** (`COLABORADOR`) | id_colaborador | BIGINT AUTO_INCREMENT | PK |
| | nombre_comercial | VARCHAR(120) | |
| | logotipo | VARCHAR(255) | |
| | tipo_mime | VARCHAR(80) | NULL |
| | id_admin | BIGINT | FK → administradores |
| **`consultas`** (`CONSULTA`) | id_consulta | BIGINT AUTO_INCREMENT | PK |
| | mensaje | TEXT | |
| | fecha_consulta | DATE | NULL |
| | created_at | DATETIME | |
| | estado | VARCHAR(20) | |
| | prioridad | VARCHAR(10) | *(RF40, Prioridad 3 — columna reservada, no se usa en Incr. 1-2)* |
| | id_visitante | BIGINT | FK → visitantes |
| | id_admin_responsable | BIGINT | FK → administradores, NULL |
| **`contenidos`** (`CONTENIDO`) | id_contenido | BIGINT AUTO_INCREMENT | PK |
| | seccion | VARCHAR(30) | (`faq` / `opiniones` / `banner` / `fases_industriales`) |
| | titulo | VARCHAR(200) | NULL |
| | cuerpo | TEXT | NULL |
| | archivo | VARCHAR(255) | NULL |
| | tipo_mime | VARCHAR(80) | NULL |
| | enlace | VARCHAR(300) | NULL |
| | orden | SMALLINT | |
| | activo | BOOLEAN | |
| | id_admin | BIGINT | FK → administradores |
| **`proyectos`** (`PROYECTO`) | id_proyecto | BIGINT AUTO_INCREMENT | PK |
| | nombre_obra | VARCHAR(150) | |
| | descripcion_tecnica | TEXT | |
| | region | VARCHAR(80) | |
| | ubicacion_geografica | VARCHAR(150) | |
| | latitud | DECIMAL(9,6) | NULL *(para Leaflet/mapa — CU22.1, fuera de Incr. 1-2 pero columna ya prevista)* |
| | longitud | DECIMAL(9,6) | NULL |
| | anio_ejecucion | SMALLINT | |
| | estado_publicacion | VARCHAR(20) | (`borrador` / `publicado`) |
| | categoria | VARCHAR(50) | (`construccion` / `industrial` / `terminaciones`) |
| | id_admin | BIGINT | FK → administradores |
| **`imagenes_proyecto`** (`IMAGEN_PROYECTO`) | id_imagen | BIGINT AUTO_INCREMENT | PK |
| | imagen | VARCHAR(255) | |
| | nombre_archivo | VARCHAR(180) | |
| | tipo_mime | VARCHAR(80) | |
| | id_proyecto | BIGINT | FK → proyectos |
| **`certificados`** (`CERTIFICADO`) | id_certificado | BIGINT AUTO_INCREMENT | PK |
| | codigo | VARCHAR(80) | UK |
| | nombre | VARCHAR(200) | |
| | descripcion | TEXT | NULL |
| | imagen | VARCHAR(255) | NULL |
| | tipo_mime | VARCHAR(80) | NULL |
| | archivo_pdf | VARCHAR(255) | NULL |
| | fecha_emision | DATE | |
| | estado | VARCHAR(20) | (vigente / no vigente — RF24) |
| | organismo | VARCHAR(120) | |
| | url_organismo | VARCHAR(300) | NULL |
| | id_admin | BIGINT | FK → administradores, NULL |

> Todas las tablas llevan además `created_at`/`updated_at` estándar de Eloquent salvo donde
> el diagrama ya declaró su propio `created_at` explícito (`recuperaciones_password`,
> `consultas`) — respetarlo tal cual, no duplicar.

### 3.2 Relaciones (ERD Chen, página 2 del diagrama)

| Relación | Cardinalidad | Traducción a FK |
|---|---|---|
| Administrador **Inicia** Sesión | 1:N | `sesiones.id_admin` |
| Administrador **Solicita** Recuperación_Password | 1:N | `recuperaciones_password.id_admin` |
| Administrador **Crea** Colaborador | 1:N | `colaboradores.id_admin` |
| Administrador **Gestiona** Proyecto | 1:N | `proyectos.id_admin` |
| Administrador **Gestiona** Consulta | 1:N | `consultas.id_admin_responsable` |
| Administrador **Publica** Certificado | 1:N | `certificados.id_admin` |
| Administrador **Publica** Contenido | 1:N | `contenidos.id_admin` |
| Visitante **Envía** Consulta | 1:N | `consultas.id_visitante` |
| Proyecto **Tiene** Imagen_Proyecto | 1:N | `imagenes_proyecto.id_proyecto` |

**Reglas de integridad (3FN, según lo declarado en Incremento 2 §7.2):**
- `imagenes_proyecto.id_proyecto` — nunca duplicar imágenes dentro de `proyectos`.
- `recuperaciones_password.id_admin` — nunca guardar tokens en `administradores`.
- `consultas.id_visitante` — no repetir datos del visitante en cada consulta.

**Migraciones a crear (orden por dependencias FK):**
1. `create_visitantes_table`
2. `create_administradores_table`
3. `create_sesiones_table` (FK id_admin)
4. `create_recuperaciones_password_table` (FK id_admin)
5. `create_consultas_table` (FK id_visitante, id_admin_responsable nullable)
6. `create_proyectos_table` (FK id_admin)
7. `create_imagenes_proyecto_table` (FK id_proyecto)
8. `create_certificados_table` (FK id_admin nullable)
9. `create_colaboradores_table` (FK id_admin)
10. `create_contenidos_table` (FK id_admin)
11. Seeder: `AdminJefeSeeder` (cuenta sembrada directo en BD, DS-03 — fuera del alcance de CU pero necesaria para poder loguearse en dev/prod)

---

## 4. Modularidad — 7 módulos funcionales

Cada módulo se implementa, testea y da por cerrado de forma independiente (permite trabajo
paralelo en el equipo de 6 personas):

| # | Módulo | RF que cubre | Actor |
|---|---|---|---|
| M1 | **Formulario de Contacto** | RF01,02,04,05,06,08,09 | Visitante |
| M2 | **Navegación pública** | RF10,11,12 | Visitante |
| M3 | **Galería de Proyectos (pública)** | RF18,19,20,21,23 | Visitante |
| M4 | **Certificaciones (pública)** | RF24,25 | Visitante |
| M5 | **Autenticación y seguridad de cuenta** | RF27,28,29,30,31,32,33 | Personal de Administración |
| M6 | **Panel de Gestión — Colaboradores y Proyectos** | RF34,45,46,47,48,49,50,51 | Personal de Administración |
| M7 | **Panel de Gestión — Comercial, Certificados y Contenido** | RF26,34,36,39,41,43,44 | Personal de Administración |

M1–M4 no requieren autenticación → se pueden construir y testear sin depender de M5. M6/M7
dependen de M5 (todo Personal de Administración pasa por `CU 27.1`).

---

## 5. Flujo de trabajo Git

- **Rama base:** `main` (protegida, solo vía PR).
- **Ramas de trabajo:** `feature/m<N>-<slug>` por módulo/CU, ej. `feature/m1-formulario-contacto`.
- **Un PR por módulo** (o por CU si el módulo es grande, ej. M6 se puede partir en
  `feature/m6-colaboradores` y `feature/m6-proyectos`).
- **Commit por caso de uso** cuando sea posible: `feat(CU1.1): registrar consulta de contacto`,
  `test(CU1.1): validar excepciones de formulario`.
- **PR checklist obligatorio:** migraciones corren limpio (`php artisan migrate:fresh --seed`),
  `php artisan test` en verde, `npm run build` sin errores, sin `dd()`/`dump()` olvidados.
- Todo el equipo comitea seguido (la Dimensión Técnica exige trazabilidad de commits por
  integrante — Scrum++ / RT auditable).

---

## 6. Estrategia de testing

- **Framework:** Pest (sobre PHPUnit, viene con Laravel 10) o PHPUnit puro — usar `php artisan test`.
- **Tipos de test por capa:**
  - **Feature tests** (`tests/Feature/...`): uno por caso de uso, cubriendo el flujo principal
    **y cada excepción listada en REQUISITOS.md** como su propio `it(...)`/método de test.
  - **Unit tests** (`tests/Unit/...`): para `Services` (ej. `ConsultaLimiteService`,
    `PasswordPolicyService`, `PdfValidationService`) — lógica pura, sin BD real (o con
    `RefreshDatabase` mínima).
- **Base de datos de test:** SQLite en memoria (`:memory:`) para velocidad, salvo que se use
  alguna sintaxis específica de MySQL (evitar; si aparece, usar MySQL de test real).
- **Factories:** una por modelo (`VisitanteFactory`, `ConsultaFactory`, `ProyectoFactory`,
  `AdministradorFactory`, etc.) para poblar rápido los escenarios de excepción (ej. crear
  5 consultas pendientes para disparar CU1.1 Excepción 3).
- **Convención de nombre de test → trazabilidad con CU:**
  `test_cu1_1_envia_consulta_correctamente()`,
  `test_cu1_1_excepcion_3_bloquea_tras_5_consultas_pendientes_en_24h()`.
- **Mocks:** `Mail::fake()` para CU30.1/CU33.1 (no enviar correos reales en test),
  `Storage::fake('public')` para todo lo que suba archivos (proyectos, colaboradores,
  certificados, contenido).
- **Meta de cobertura:** 100% de los *happy path* + 100% de las excepciones documentadas por
  CU (no cobertura de líneas arbitraria — cobertura **funcional** contra REQUISITOS.md).

---

## 7. Plan de fases (paso a paso)

> Cada fase indica: objetivo, RF/CU cubiertos, tareas técnicas, archivos a crear y tests a
> escribir. Seguir el orden — cada fase depende de la anterior salvo que se indique lo
> contrario.

### Fase 0 — Setup del proyecto ✅ (ya hecho — queda como referencia histórica)
**Objetivo:** repo Laravel funcional, conectado a MySQL, con Tailwind/Alpine y CI local.
1. `composer create-project laravel/laravel:^10.0 web` dentro del repo clonado (todo el
   proyecto Laravel vive en la subcarpeta `web/`, no en la raíz del repo).
2. Configurar `.env` → `DB_CONNECTION=mysql`, `DB_DATABASE=ingecon_dev`, `MAIL_MAILER=sendmail`
   (o `log` en local).
3. Instalar Tailwind CSS + Alpine.js (`npm install -D tailwindcss postcss autoprefixer`,
   `npm install alpinejs`), configurar `tailwind.config.js` con los `content` paths de Blade.
4. Instalar `spatie/simple-excel` (para RF42/exportación — Incremento 3, pero se deja listo).
5. `php artisan storage:link`.
6. Crear `layouts/app.blade.php` y `layouts/admin.blade.php` base (header, footer, slots).
7. Configurar Pest o dejar PHPUnit (`php artisan test` corriendo el test de ejemplo).
8. Commit inicial: `chore: bootstrap proyecto Laravel + Tailwind + Alpine`.
- **Test:** `php artisan test` pasa con el test dummy de Laravel.

### Fase 1 — Modelo de datos
**Objetivo:** todas las tablas y modelos listos, con factories y seeders.
1. Crear las 10 migraciones listadas en la §3.2, en el orden indicado, con los nombres de
   columna exactos del esquema oficial (`id_admin`, `id_visitante`, `id_proyecto`, etc.).
2. Crear los 10 modelos Eloquent con sus `$fillable`, `casts` (`estado`/`categoria` como
   string-enum vía `casts`, `activo` boolean) y relaciones (`belongsTo`/`hasMany`) según la
   tabla de relaciones de §3.2.
3. Crear factories para cada modelo.
4. Crear `AdminJefeSeeder` (cuenta con `rol=admin_jefe`, password hasheado con Argon2id/bcrypt).
5. `php artisan migrate:fresh --seed` corre sin errores.
- **Tests:**
  - `tests/Unit/Models/*Test.php`: relaciones Eloquent (`Proyecto::imagenes()`,
    `Consulta::visitante()`, etc.) devuelven lo esperado.
  - Test de que el seeder crea exactamente 1 Administrador Jefe.

### Fase 2 — M1: Formulario de Contacto (RF01,02,04,05,06,08,09 · CU1.1,2.1,4.1,5.1,6.1,8.1,9.1)
1. `StoreConsultaRequest` con reglas DS-39..DS-43 (nombre/apellido alfabético + mayúscula
   inicial, email formato válido, mensaje 10-1000 chars, `acepta_terminos` required|accepted).
2. `ConsultaLimiteService::puedeEnviar(Visitante $v): bool` — cuenta consultas `pendiente` en
   BD de las últimas 24h por email, límite 5 (CU1.1 Excepción 3).
3. `ContactoController@store`: valida → `ConsultaLimiteService` → crea/reusa `Visitante` →
   crea `Consulta` → retorna JSON/redirect con `consulta_id` para que el frontend confirme
   coincidencia de ID (CU9.1).
4. Vista `public/home.blade.php` con el formulario + Alpine para: casilla T&C que habilita
   "Enviar" (CU4.1), modal de confirmación antes de enviar (CU5.1), botón "Limpiar" (CU8.1),
   modal de éxito tras confirmar ID (CU9.1).
5. Ruta pública `GET /terminos-y-condiciones` (o enlace a doc estático) — CU2.1.
6. `resources/views/legal/terminos.blade.php` o redirect a asset PDF/estático.
- **Tests (Feature, `tests/Feature/Public/ContactoTest.php`):**
  - Envío exitoso registra Visitante + Consulta (CU1.1 flujo principal).
  - Excepción 1: campos vacíos → 422 + mensaje.
  - Excepción 2: mensaje < 10 caracteres → error.
  - Excepción 3: 6ta consulta pendiente en 24h → bloqueada.
  - Excepción 4/5: email con dominio o formato inválido → error.
  - CU4.1: envío sin `acepta_terminos` → rechazado.
  - CU6.1: respuesta trae mensajes por campo.
  - CU8.1: limpiar no debe hacer request al servidor (test de Alpine con Dusk/manual, o al menos
    que el botón no dispare POST).
  - CU9.1: confirmación solo se muestra si el ID retornado coincide (mockear mismatch → no modal).

### Fase 3 — M2: Navegación pública (RF10,11,12 · CU10.1,11.1,11.2,12.1)
1. `HomeController@index` carga proyectos/certificaciones/colaboradores destacados para el
   Menú Lateral y la Barra de Acceso Rápido.
2. Componente Blade `x-menu-lateral` (Alpine `x-data="{ open: false }"`) con enlaces a
   Proyectos/Certificaciones/Colaboradores.
3. Componente `x-barra-navegacion-fija` con scroll-spy (Alpine + `IntersectionObserver`).
4. Enlace de documentación técnica en el header, apuntando a URL configurable (`.env` o tabla
   `contenidos`/config) — CU10.1.
5. Página dedicada `public/colaboradores.blade.php` — CU11.2.
- **Tests:**
  - Home carga sin proyectos/colaboradores/certificados (estado vacío) sin romper (Excepciones
    de BD vacía en CU11.1/CU11.2/CU12.1).
  - Enlace de documentación técnica responde 200/redirect válido; si la URL configurada es
    nula, no rompe la página (CU10.1 Excepción 2).

### Fase 4 — M3: Galería de Proyectos pública (RF18,19,20,21,23 · CU18.1,19.1,20.1,21.1,23.1)
1. `ProyectoPublicoController@index` — solo `estado=publicado`.
2. Filtros: texto (`LIKE` sobre nombre+ubicación), categoría (`WHERE categoria=`), ubicación
   (`WHERE ubicacion_geografica=`), combinables (CU21.1) vía query string.
3. `ProyectoPublicoController@show` — ficha técnica en modal (Alpine `x-data` + fetch parcial
   o Blade component con datos ya cargados) — CU23.1, oculta si `estado != publicado`.
4. Vista con menú desplegable de ubicación + botón "Aplicar Filtros" + input de texto +
   chips de categoría, todos combinables sin recargar (fetch AJAX a una ruta que retorna
   partial Blade o JSON).
- **Tests:**
  - Filtro por texto: coincidencia parcial en nombre y ubicación (CU19.1).
  - Filtro por categoría sin resultados → estado vacío con opción "ver todos" (CU20.1 Exc.1).
  - Filtro por ubicación sin proyectos → mensaje "no se encontraron" (CU18.1 Exc.3).
  - Filtros combinados: texto + categoría + ubicación → intersección correcta (CU21.1).
  - Proyecto en Borrador no aparece en listado ni es accesible por ficha técnica (CU23.1 Exc.1).

### Fase 5 — M4: Certificaciones pública (RF24,25 · CU24.1,25.1,25.2)
1. `CertificacionPublicaController@index` — solo `estado_vigente=true`.
2. `CertificacionPublicaController@previsualizar($id)` — stream del PDF con
   `Content-Disposition: inline` (CU25.2); 404 controlado si no existe o está vacío.
3. Enlace de descarga condicional: se oculta si `archivo_pdf` es null (RF25).
- **Tests:**
  - Sin certificaciones vigentes → estado vacío (CU24.1 Exc.1).
  - Certificación sin PDF → no se renderiza botón de descarga.
  - `previsualizar` de un certificado inexistente → 404 controlado, no 500 (CU25.2 Exc.1).

### Fase 6 — M5: Autenticación y seguridad (RF27,28,29,30,31,32,33 · CU27.1,27.2,28.1,28.2,29.1,30.1,31.1,31.2,32.1,33.1)
1. `LoginController@store`: valida credenciales, chequea `bloqueado_hasta` antes de intentar
   (CU27.1 Exc.2), usa `Hash::check` (Argon2id/bcrypt).
2. `LoginAttemptService::registrarFallo(Administrador $a)`: incrementa contador; al llegar a 5,
   setea `bloqueado_hasta = now()->addMinutes(60)` y dispara `AccountLocked` mail (CU33.1).
3. `LoginController@store` éxito: resetea contador, crea sesión Laravel, `intended()` redirect.
4. `PasswordController@editRequestForm` (CU29.1) → `@sendResetLink` (CU30.1, genera token
   firmado con expiración, `Mail::to()->send(new PasswordResetLink(...))`).
5. `PasswordController@validateToken($token)` (CU31.1) → `@reset` (CU31.2), aplica
   `PasswordPolicyService::validar()` (mín 8, mayús, minús, número [+ especial]).
6. `PasswordController@changeForm` / `@change` (estando autenticado, CU28.1/28.2) — valida
   contraseña actual con `Hash::check`.
7. `LogoutController@destroy` — invalida sesión, `Auth::guard('admin')->logout()`,
   `session()->invalidate()` (CU32.1).
8. Middleware `EnsureAdminAuthenticated` en `routes/web.php` (`Route::middleware('auth:admin')`)
   protegiendo todo `admin.*`.
- **Tests:**
  - Login exitoso crea sesión y redirige al Panel (CU27.1).
  - Credenciales incorrectas incrementan contador sin revelar si el correo existe (CU27.2).
  - 5to intento fallido bloquea 60 min + envía `AccountLocked` (`Mail::fake()->assertSent(...)`, CU33.1).
  - Login con cuenta bloqueada → rechazado con tiempo restante (CU27.1 Exc.2).
  - Recuperación: token inválido/expirado → rechazado, redirige a solicitar nuevo (CU31.1 Exc.1).
  - Token ya usado → rechazado (CU31.1 Exc.2).
  - Cambio de contraseña: falla si actual es incorrecta, si nueva no coincide con confirmación,
    o si no cumple política (CU28.2, 3 tests independientes).
  - Logout invalida sesión: request posterior a ruta admin → redirect a login (CU32.1).
  - Middleware bloquea acceso a rutas `admin.*` sin sesión (RNF03).

### Fase 7 — M6: Panel — Colaboradores y Proyectos (RF34,45,46,47,48,49,50,51 · CU34.1-34.6,45.1,45.2,46.1,47.1,48.1,48.2,48.3,49.1,50.1,51.1)
1. `DashboardController@index` — Menú Lateral con los 5 accesos (RF34), respetando permisos
   (CU34.1 Exc.1 — para Incremento 1-2 todo Personal de Administración ve todo; el filtrado
   fino de permisos por rol es Incremento 3+, dejar el hook preparado).
2. `ColaboradorController`: `index` (CU34.4/45.2), `store` (CU45.1), `update` (CU46.1),
   `destroy` (CU47.1) — todo con `StoreColaboradorRequest` (nombre requerido, logotipo
   imagen ≤500KB).
3. `ProyectoController`: `index` (CU34.3/48.3), `store` (CU48.1, estado inicial `borrador`,
   máx 15 imágenes vía `ImageUploadService`), `update` (CU49.1), `updateVisibilidad` (CU50.1,
   toggle borrador/publicado = CU48.2 "Publicando"), `destroy` (CU51.1, borra imágenes del
   storage + registros).
4. `ImageUploadService::guardar(UploadedFile[] $files, string $carpeta): array` — valida
   ≤5MB c/u, ≤15 total, formatos JPG/PNG/WebP, revierte si ninguna se pudo procesar.
5. Vistas admin con Ventanas Modal (Alpine) para cada Formulario (Colaborador, Proyecto),
   confirmación de eliminación en modal.
- **Tests:**
  - CU45.1: registro exitoso + 4 excepciones (nombre vacío, formato de logo inválido, fallo
    de BD, sesión expirada).
  - CU46.1: edición precarga datos correctamente; 4 excepciones.
  - CU47.1: eliminación exitosa + colaborador ya eliminado (idempotencia) + fallo BD.
  - CU48.1: registro como borrador + las 7 excepciones (nombre vacío, imagen >5MB, >15
    imágenes, formato inválido, fallo BD, ninguna imagen procesable, sesión expirada).
  - CU48.2/CU50.1: publicar/despublicar cambia visibilidad; verificar que solo `publicado`
    aparece en la galería pública (test de integración cruzando M3 + M6).
  - CU49.1: edición actualiza campos e imágenes; proyecto inexistente → cancela.
  - CU51.1: elimina proyecto + sus imágenes del storage (assert `Storage::disk('public')->missing(...)`).
  - CU34.2–34.6: cada submódulo responde 200 solo autenticado, 302→login si no.

### Fase 8 — M7: Panel — Comercial, Certificados y Contenido (RF26,36,39,41,43,44 · CU26.1,36.1,36.2,39.1,41.1,43.1-43.9,44.1-44.5)
1. `CertificadoController@store` (CU26.1) — `StoreCertificadoRequest` (nombre normativa +
   organismo requeridos, imagen validada, PDF opcional validado con `PdfValidationService`
   vía `finfo`, RNF04/06).
2. `ConsultaController@index` — paginación Laravel nativa `paginate(10)` (CU36.1/36.2 se
   resuelven "gratis" con el paginador de Eloquent; documentar el mapeo).
3. `ConsultaController@show($id)` — retorna partial/modal con el detalle (CU39.1).
4. `ConsultaController@updateEstado($id)` — valida transición, actualiza `estado` (CU41.1).
5. `ContenidoController` con acciones por sección (`faq`, `opiniones`, `banner`,
   `fases-industriales`) × (`store`, `update`, `destroy`) — implementar como un único
   controller con `$seccion` en la ruta en vez de 9 controllers separados, para no duplicar
   código (los CU 43.2-43.9 y 44.2-44.5 comparten patrón idéntico por sección).
6. Confirmación en modal antes de cada `destroy` (CU44.1).
- **Tests:**
  - CU26.1: registro exitoso; 4 excepciones (campos obligatorios, imagen inválida, PDF dañado,
    fallo BD).
  - CU36.1/36.2: primera página trae 10 registros; página fuera de rango cae a la más cercana
    válida; con <10 registros no muestra paginación.
  - CU39.1: detalle de consulta eliminada → informa no disponible.
  - CU41.1: transición de estado válida se persiste; mismo estado no genera update innecesario.
  - CU43.2-43.9 (parametrizar el test con un `dataset` por sección FAQ/Banner/Fases/Opiniones):
    alta exitosa + campos vacíos + archivo inválido + fallo BD.
  - CU44.1-44.5 (mismo patrón parametrizado): eliminación exitosa + ya eliminado + fallo BD.

### Fase 9 — Integración, RNF transversales y QA final
1. **RNF08** (responsivo): revisar cada vista en breakpoints móvil/tablet/desktop (Tailwind
   `sm:`/`md:`/`lg:`).
2. **RNF09/15** (rendimiento): revisar N+1 queries con `DB::enableQueryLog()` o Laravel
   Debugbar en dev; eager-load (`with(...)`) en listados de proyectos/certificados.
3. **RNF05/13** (sanitización): confirmar que todo input pasa por FormRequest, ningún
   `Blade::raw` sin `e()`/`{{ }}` en contenido de usuario.
4. **RNF10**: revisar que ninguna vista muestre stack traces (config `APP_DEBUG=false` en prod,
   páginas 404/500 personalizadas).
5. **RNF14**: `BackupDatabase` command probado manualmente (`php artisan backup:run` genera
   dump en carpeta fuera de `public_html`); documentar el Cron Job de cPanel.
6. **RNF11**: helper/blade directive que calcule `now()->year - 1994` para "años de
   experiencia" en el layout público.
7. Ejecutar `php artisan test` completo — **todas** las 64 CU con verde.
8. Revisión manual cruzada contra la tabla de RF de `REQUISITOS.md` (checklist §8 de este
   documento).
9. `npm run build` para producción, verificar assets versionados (Vite manifest).
10. Deploy: `composer install --no-dev --optimize-autoloader`, `php artisan config:cache`,
    `php artisan route:cache`, subir por FTP/Administrador de Archivos cPanel, configurar
    Cron Jobs (backup) y AutoSSL.

---

## 8. Checklist de aceptación (contra `REQUISITOS.md`)

Antes de dar por "completo hasta Incremento 2":

- [ ] Los 16 RF de Incremento 1 tienen su ruta + controller + vista + test en verde.
- [ ] Los 22 RF de Incremento 2 tienen su ruta + controller + vista + test en verde.
- [ ] Las 22 CU de Incremento 1 tienen al menos un test por excepción documentada.
- [ ] Las 42 CU de Incremento 2 tienen al menos un test por excepción documentada.
- [ ] Los 17 RNF están verificados (ver Fase 9).
- [ ] `php artisan migrate:fresh --seed` reconstruye la BD desde cero sin intervención manual.
- [ ] Ningún RF de Prioridad 3/4 (RF07, RF13-17, RF52-53) fue implementado a costa de tiempo
      de Incremento 1-2 (alcance cerrado, no "de una vez ya que estamos").
- [ ] `git log` muestra commits de los 6 integrantes con trazabilidad por módulo/CU.

---

## 9. Próximo paso sugerido

Empezar por **Fase 0 + Fase 1** (setup + modelo de datos) — es la base de la que dependen
todos los módulos y permite que el equipo se reparta M1-M4 (públicos, sin dependencias entre
sí) en paralelo apenas esté lista la Fase 1.
