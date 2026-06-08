# Plan de Reorganización — Ingecon

> **Objetivo:** Reorganizar el proyecto en 4 carpetas principales:
> `vistas/` · `controlador/` · `base_datos/` · `documentacion/`
>
> **Repositorio:** `https://github.com/PauuUrrutia23/Ingenieria-de-Software-Grupo-12-Codigo.git`

---

## Situación Actual

El repositorio contiene:

| Elemento | Descripción |
|----------|-------------|
| `ingecon-app/` | Laravel 11 parcial (solo archivos custom, faltan vendor/, artisan, etc.) |
| `ingecon-fresh/` | Laravel 11 completo (con vendor/, composer.json, artisan, tests/, public/, storage/) |
| 19 archivos `.md` sueltos | Documentación de cada incremento/caso de uso |
| `Ingecon_Como_Funciona.docx` | Documento descriptivo del sistema |
| `README.md` (vacío) | Sin contenido |

**Problemas detectados:**

1. **Duplicados en `ingecon-fresh/`:** `routes/routes/web.php` y `database/database/` están duplicados
2. **Documentación dispersa:** 19 `.md` sueltos en la raíz, difíciles de navegar
3. **Dos versiones del proyecto:** `ingecon-app/` (incompleto) vs `ingecon-fresh/` (completo)
4. **README vacío:** No describe el proyecto
5. **Estructura no intuitiva:** Para quien revisa el repo, no es claro dónde está cada capa (MVC)

---

## Estructura Objetivo

```
Ingenieria-de-Software-Grupo-12-Codigo/
├── vistas/                          # Blade templates (resources/views/)
│   ├── admin/
│   │   ├── colaboradores.blade.php
│   │   ├── dashboard.blade.php
│   │   └── proyectos.blade.php
│   ├── auth/
│   │   ├── emails/
│   │   │   └── cuenta-bloqueada.blade.php
│   │   └── login-modal.blade.php
│   ├── errors/
│   │   └── 404.blade.php
│   ├── layouts/
│   │   ├── admin.blade.php
│   │   └── public.blade.php
│   ├── partials/
│   │   ├── navbar.blade.php
│   │   └── sidebar-menu.blade.php
│   └── public/
│       ├── certificaciones.blade.php
│       ├── index.blade.php
│       └── partials/
│           ├── certificaciones.blade.php
│           ├── contacto.blade.php
│           ├── galeria.blade.php
│           ├── inicio.blade.php
│           └── proyectos.blade.php
│
├── controlador/                     # Controllers + Middleware (app/Http/)
│   ├── AdminController.php
│   ├── AuthController.php
│   ├── ContactoController.php
│   ├── Controller.php               (base)
│   ├── DBRouterController.php       (intermediario BD)
│   ├── InstitucionalCtrl.php
│   ├── ProyectoController.php
│   └── AdminAuth.php                (middleware)
│
├── base_datos/                      # database/ + app/Models/
│   ├── migrations/                  (10 archivos)
│   │   ├── 2024_01_01_000001_create_administradores_table.php
│   │   ├── 2024_01_01_000002_create_visitantes_table.php
│   │   ├── 2024_01_01_000003_create_sesiones_table.php
│   │   ├── 2024_01_01_000004_create_consultas_table.php
│   │   ├── 2024_01_01_000005_create_archivos_adjuntos_table.php
│   │   ├── 2024_01_01_000006_create_proyectos_table.php
│   │   ├── 2024_01_01_000007_create_imagenes_proyecto_table.php
│   │   ├── 2024_01_01_000008_create_certificados_table.php
│   │   ├── 2024_01_01_000009_create_colaboradores_table.php
│   │   └── 2024_01_01_000010_add_tipo_mime_to_colaborador_table.php
│   ├── seeders/                     (2 archivos)
│   │   ├── AdminSeeder.php
│   │   └── DatabaseSeeder.php
│   ├── factories/
│   │   └── UserFactory.php
│   └── modelos/                     (9 archivos — app/Models/)
│       ├── Administrador.php
│       ├── ArchivoAdjunto.php
│       ├── Certificado.php
│       ├── Colaborador.php
│       ├── Consulta.php
│       ├── ImagenProyecto.php
│       ├── Proyecto.php
│       ├── Sesion.php
│       └── Visitante.php
│
├── documentacion/                   # Documentación del proyecto
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
├── app/                             # Jobs, Mail, Providers (app/)
│   ├── Jobs/
│   │   └── EnviarEmailBloqueoJob.php
│   ├── Mail/
│   │   └── CuentaBloqueadaMail.php
│   └── Providers/
│       └── AppServiceProvider.php
│
├── bootstrap/
├── config/
├── routes/
├── public/
├── storage/
├── tests/
├── .env.example
├── .editorconfig
├── .gitattributes
├── .gitignore
├── .styleci.yml
├── artisan
├── composer.json                   ← MODIFICADO
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── postcss.config.js
├── tailwind.config.js
├── vite.config.js
├── CHANGELOG.md
├── INSTALACION.md
└── README.md                       ← NUEVO (completo)
```

---

## Cambios al Código Necesarios

### A. `composer.json` — Actualizar PSR-4 autoload

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "App\\Http\\Controllers\\": "controlador/",
        "App\\Http\\Middleware\\": "controlador/",
        "App\\Models\\": "base_datos/modelos/",
        "Database\\Factories\\": "base_datos/factories/",
        "Database\\Seeders\\": "base_datos/seeders/"
    }
}
```

Después de modificar, ejecutar: `composer dump-autoload`

> **Explicación:** Los controladores mantienen el namespace `App\Http\Controllers\` pero el autoloader los busca en `controlador/`. Lo mismo para middleware y modelos. Esto evita modificar los `namespace` dentro de cada archivo PHP.

### B. `app/Providers/AppServiceProvider.php` — Nuevas rutas de vistas y migraciones

Agregar al método `boot()`:

```php
public function boot(): void
{
    // Redirigir vistas a la carpeta vistas/
    View::getFinder()->setPaths([base_path('vistas')]);

    // Cargar migraciones desde base_datos/migrations/
    $this->loadMigrationsFrom(base_path('base_datos/migrations'));
}
```

Agregar el `use` al inicio:
```php
use Illuminate\Support\Facades\View;
```

### C. `config/database.php` — Actualizar ruta de migraciones (si aplica)

Verificar que no haya referencias hardcodeadas a `database/migrations/`. Normalmente no las hay porque Laravel usa la convención, pero con `loadMigrationsFrom()` ya queda cubierto.

### D. Blade Templates — Sin cambios necesarios

Los `@extends('layouts.public')` y `@include('partials.navbar')` usan notación de punto relativa al path de vistas configurado. Al cambiar el path a `vistas/`, las rutas relativas se mantienen idénticas. **No hay que modificar ningún `.blade.php`.**

### E. Namespaces — Sin cambios necesarios

Al mapear PSR-4 con los nuevos paths, los `namespace App\Http\Controllers;` y `use App\Models\Proyecto;` siguen funcionando sin modificar.

---

## Plan de Ejecución en 5 Commits

### Commit 1: Consolidar proyectos y eliminar duplicados

```
git commit -m "Consolidar: unificar ingecon-fresh como proyecto principal y eliminar duplicados"
```

**Acciones:**
1. Mover `ingecon-app/nginx-ingecon.conf` → `ingecon-fresh/nginx-ingecon.conf`
2. Mover `ingecon-app/.env` → `ingecon-fresh/.env` (si es útil)
3. Eliminar `ingecon-fresh/routes/routes/` (duplicado)
4. Eliminar `ingecon-fresh/database/database/` (duplicado, AdminSeeder diferente)
5. Mover todo el contenido de `ingecon-fresh/` a la raíz del repo
6. Eliminar carpetas vacías `ingecon-fresh/` e `ingecon-app/`

### Commit 2: Mover vistas → `vistas/`

```
git commit -m "Mover vistas a vistas/ y configurar path de Blade"
```

**Acciones:**
1. Crear carpeta `vistas/`
2. Mover todo `resources/views/*` → `vistas/*`
3. Eliminar `resources/views/` (queda vacío)
4. Modificar `app/Providers/AppServiceProvider.php`: agregar `View::getFinder()->setPaths([base_path('vistas')])` en `boot()`

### Commit 3: Mover controladores y middleware → `controlador/`

```
git commit -m "Mover controladores y middleware a controlador/"
```

**Acciones:**
1. Crear carpeta `controlador/`
2. Mover todos los `.php` de `app/Http/Controllers/` → `controlador/`
3. Mover `app/Http/Middleware/AdminAuth.php` → `controlador/AdminAuth.php`
4. Eliminar `app/Http/` (vacío)
5. Actualizar `composer.json` PSR-4: agregar entradas para `App\Http\Controllers\` y `App\Http\Middleware\` apuntando a `controlador/`
6. Ejecutar `composer dump-autoload`

### Commit 4: Mover modelos, migraciones y seeders → `base_datos/`

```
git commit -m "Mover modelos, migraciones y seeders a base_datos/"
```

**Acciones:**
1. Crear `base_datos/modelos/`, `base_datos/migrations/`, `base_datos/seeders/`, `base_datos/factories/`
2. Mover `app/Models/*.php` → `base_datos/modelos/`
3. Mover `database/migrations/*.php` → `base_datos/migrations/`
4. Mover `database/seeders/*.php` → `base_datos/seeders/`
5. Mover `database/factories/UserFactory.php` → `base_datos/factories/` (opcional, no se usa)
6. Eliminar `app/Models/` y `database/` (ya vacíos)
7. Actualizar `composer.json` PSR-4: agregar entradas para `App\Models\`, `Database\Factories\`, `Database\Seeders\`
8. Agregar `$this->loadMigrationsFrom(base_path('base_datos/migrations'))` en `AppServiceProvider::boot()`
9. Ejecutar `composer dump-autoload`

### Commit 5: Organizar documentación y README

```
git commit -m "Organizar documentacion/ y crear README principal"
```

**Acciones:**
1. Crear carpeta `documentacion/`
2. Mover los 19 archivos `.md` a `documentacion/`
3. Mover `Ingecon_Como_Funciona.docx` a `documentacion/`
4. Mantener solo una versión de cada doc (eliminar los duplicados `(1)`)
   - `03_autenticacion (1).md` vs `03_autenticacion.md` → conservar el que usa DBRouter (el `(1)`)
   - Misma lógica para `04`, `06`, `07`, `08`, `09`, `10`
5. Crear `README.md` nuevo con:
   - Descripción del proyecto (plataforma web Ingecon)
   - Stack tecnológico
   - Estructura de carpetas
   - Requisitos funcionales implementados (RF01-RF50)
   - Instrucciones de instalación rápidas
   - Enlace a la documentación detallada en `documentacion/`
6. Eliminar `PRUEBAS.md` y `INSTALACION.md` del root (ya quedan en `documentacion/`)
   - O mantener `INSTALACION.md` en root para referencia rápida

---

## Resumen de Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `composer.json` | Nuevos paths PSR-4: controlador, modelos, seeders, factories |
| `app/Providers/AppServiceProvider.php` | `boot()`: configurar path de vistas y migraciones |
| `README.md` | Reescrito completamente |
| `.blade.php` (17 archivos) | **Sin cambios** — paths de include relativos funcionan igual |
| `.php` controladores (7 archivos) | **Sin cambios** — namespace resuelto por PSR-4 |
| `.php` modelos (9 archivos) | **Sin cambios** — namespace resuelto por PSR-4 |
| `.php` migraciones (10 archivos) | **Sin cambios** |
| `routes/web.php` | **Sin cambios** — los `use` apuntan a namespaces que PSR-4 resuelve |

---

## Verificación Post-Reorganización

```bash
# 1. Regenerar autoload
composer dump-autoload

# 2. Verificar que no hay errores de sintaxis
php artisan --version

# 3. Verificar que las rutas cargan
php artisan route:list

# 4. Verificar vistas (si hay errores de path saldrán aquí)
php artisan view:clear

# 5. Verificar migraciones (debe listar las 10)
php artisan migrate:status

# 6. Correr tests si existen
php artisan test
```

---

## Notas Importantes

- **El proyecto sigue siendo un proyecto Laravel 11 funcional.** Solo se reorganizaron las ubicaciones físicas de los archivos, los namespaces y la lógica son idénticos.
- **Las rutas de `@include` y `@extends` en Blade no cambian** porque son relativas al path de vistas configurado.
- **No se modifican namespaces dentro de archivos PHP** — composer PSR-4 resuelve los paths automáticamente.
- **`composer dump-autoload` es obligatorio** después de cada cambio en `composer.json`.
