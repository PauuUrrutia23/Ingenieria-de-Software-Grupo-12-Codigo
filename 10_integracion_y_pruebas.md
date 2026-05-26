# 10 — Integración y Pruebas — Incremento 1
> Plataforma web Ingecon · Laravel 11 · PHP 8.3 · PostgreSQL 16

---

## 1. ARCHIVO `routes/web.php` COMPLETO

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstitucionalCtrl;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// ─────────────────────────────────────────────
// Rutas públicas
// ─────────────────────────────────────────────

Route::get('/', [InstitucionalCtrl::class, 'index'])->name('home');

Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');

Route::get('/proyectos/buscar', [ProyectoController::class, 'buscar'])->name('proyectos.buscar');

Route::get('/proyectos/{id}/detalle', [ProyectoController::class, 'detalle'])->name('proyectos.detalle');

Route::get('/certificaciones', [ProyectoController::class, 'certificaciones'])->name('certificaciones.index');

Route::get('/certificaciones/{id}/descargar', [ProyectoController::class, 'descargarCertificado'])
    ->name('certificaciones.descargar');

// ─────────────────────────────────────────────
// Rutas de autenticación
// ─────────────────────────────────────────────

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// ─────────────────────────────────────────────
// Rutas del panel admin (protegidas por middleware 'admin.auth')
// ─────────────────────────────────────────────

Route::prefix('admin')->middleware('admin.auth')->name('admin.')->group(function () {

    // Proyectos
    Route::get('/proyectos', [AdminController::class, 'indexProyectos'])->name('proyectos.index');
    Route::post('/proyectos', [AdminController::class, 'storeProyecto'])->name('proyectos.store');
    Route::put('/proyectos/{id}', [AdminController::class, 'updateProyecto'])->name('proyectos.update');

    // Colaboradores
    Route::get('/colaboradores', [AdminController::class, 'indexColaboradores'])->name('colaboradores.index');
    Route::post('/colaboradores', [AdminController::class, 'storeColaborador'])->name('colaboradores.store');
});
```

---

## 2. REGISTRO DEL MIDDLEWARE `admin.auth`

En Laravel 11 no existe `app/Http/Kernel.php`. El middleware se registra en `bootstrap/app.php` usando el método `withMiddleware()`:

```php
<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Registrar alias de middleware personalizado
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
        ]);

        // Excluir rutas de la verificación CSRF si se usa fetch desde Alpine.js
        // (alternativa: leer el token desde la meta tag y enviarlo en el header)
        // $middleware->validateCsrfTokens(except: [
        //     '/api/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### Implementación del middleware `app/Http/Middleware/AdminAuth.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin_id')) {
            // Solicitudes AJAX reciben 401; peticiones web redirigen al inicio
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            return redirect('/')->with('error', 'Acceso restringido. Inicie sesión.');
        }

        return $next($request);
    }
}
```

---

## 3. CHECKLIST DE VERIFICACIÓN POR RF

| RF | Descripción corta | Método HTTP + Endpoint | Cómo verificarlo manualmente |
|----|-------------------|------------------------|------------------------------|
| RF01 | Visitante envía consulta de contacto | `POST /contacto` | Completar el formulario, enviar y confirmar que aparece un registro nuevo en la tabla `consultas` de la BD. |
| RF02 | Formulario con campos: Nombre, Apellido, Email, Mensaje, Fecha, Adjunto | `GET /` (formulario visible en la vista) | Inspeccionar el HTML del formulario y comprobar que existen los 6 inputs definidos. |
| RF07 | Mensaje de error cuando campos no cumplen validaciones | `POST /contacto` (respuesta de error) | Enviar el formulario vacío o con email inválido y verificar que aparece el mensaje de validación correspondiente. |
| RF12 | Icono de menú despliega menú lateral | `GET /` (frontend Alpine.js) | Hacer clic en el ícono de hamburguesa y comprobar que el menú lateral se abre con los enlaces de navegación. |
| RF13 | Barra de navegación fija con scroll suave | `GET /` (frontend CSS/JS) | Hacer scroll vertical en la página y verificar que la barra permanece visible; hacer clic en un enlace y confirmar el desplazamiento suave a la sección. |
| RF20 | Filtrar proyectos por texto (nombre/ubicación) | `GET /proyectos/buscar?q=texto` | Ingresar una palabra clave en el buscador y verificar que la galería muestra sólo proyectos con coincidencias en nombre u ubicación. |
| RF21 | Filtrar proyectos por categoría (Habitacional/Industrial/Agrícola) | `GET /proyectos/buscar?categoria=Habitacional` | Seleccionar cada categoría en el desplegable y confirmar que sólo se muestran proyectos de esa categoría. |
| RF24 | Modal con especificaciones técnicas del proyecto | `GET /proyectos/{id}/detalle` (AJAX/fetch) | Hacer clic sobre la imagen de un proyecto y verificar que la ventana modal muestra Nombre, Descripción y Ubicación correctos. |
| RF25 | Visualizar certificaciones en formato PDF | `GET /certificaciones` | Navegar a `/certificaciones` y confirmar que se lista al menos un certificado con nombre y vista previa. |
| RF26 | Descargar certificado en formato PDF | `GET /certificaciones/{id}/descargar` | Hacer clic en el botón de descarga y verificar que el navegador descarga un archivo `.pdf` válido. |
| RF28 | Login admin mediante modal con credenciales | `POST /login` | Abrir el modal de inicio de sesión, ingresar credenciales correctas y verificar la redirección al panel de administración. |
| RF33 | Logout invalida sesión y redirige al inicio | `POST /logout` | Estando en el panel admin, cerrar sesión y confirmar la redirección a `/`; intentar acceder a `/admin/proyectos` y verificar el rechazo. |
| RF34 | Bloqueo por 60 min tras 5 intentos fallidos y notificación email | `POST /login` (5 veces con credenciales incorrectas) | Intentar login 5 veces con contraseña incorrecta; al sexto intento comprobar el mensaje de bloqueo y revisar el correo institucional. |
| RF46 | Registrar colaborador (Nombre Comercial + Logotipo) desde modal | `POST /admin/colaboradores` | En el panel admin, abrir el modal "Agregar Colaborador", completar los campos y guardar; verificar que aparece en la lista. |
| RF49 | Crear proyecto (Nombre + Fotografías) con estado Borrador | `POST /admin/proyectos` | Hacer clic en "Nuevo Proyecto", completar el formulario modal y guardar; verificar que la tarjeta aparece con estado `borrador` en el panel. |
| RF50 | Editar proyecto existente desde modal con datos precargados | `PUT /admin/proyectos/{id}` | Hacer clic en "Editar Información" de un proyecto, verificar que los datos actuales aparecen precargados, modificarlos y guardar; confirmar los cambios en la tarjeta. |

---

## 4. FLUJOS DE PRUEBA MANUAL PASO A PASO

### FLUJO A — Visitante envía consulta (RF01, RF02, RF07)

1. Abrir el navegador y navegar a `http://localhost:8000`.
2. Localizar la sección del **Formulario de Contacto** en la página de inicio.
3. Verificar que existen los campos: **Nombre**, **Apellido**, **Email**, **Mensaje**, **Fecha** y **Adjunto**.
4. **Prueba de validación vacía:** Hacer clic en "Enviar" sin rellenar ningún campo. Confirmar que aparecen mensajes de error en cada campo obligatorio.
5. **Prueba de email inválido:** Escribir `noesunmail` en el campo Email y enviar. Confirmar el mensaje de error específico para el formato de email.
6. **Prueba de adjunto inválido:** Intentar adjuntar un archivo `.exe` o un archivo mayor al límite. Confirmar que el sistema lo rechaza con mensaje de error.
7. **Envío correcto:** Rellenar todos los campos con datos válidos. Adjuntar un PDF menor a 5 MB. Hacer clic en "Enviar".
8. Verificar que aparece un **mensaje de confirmación** en la interfaz.
9. Acceder a la base de datos y ejecutar:
   ```sql
   SELECT * FROM consultas ORDER BY id_consulta DESC LIMIT 1;
   ```
10. Confirmar que el registro aparece con los datos ingresados.

---

### FLUJO B — Navegación pública (RF12, RF13)

1. Navegar a `http://localhost:8000`.
2. **Menú lateral (RF12):** Localizar el ícono de menú (hamburguesa) en la barra de navegación. Hacer clic sobre él. Verificar que el menú lateral se despliega sobre el contenido y muestra enlaces a las secciones disponibles. Hacer clic fuera del menú y confirmar que se cierra.
3. **Barra fija (RF13):** Hacer scroll hacia abajo en la página. Verificar que la barra de navegación permanece visible en la parte superior de la pantalla durante todo el desplazamiento.
4. Hacer clic en cada enlace de la barra de navegación y confirmar que la vista se desplaza suavemente hacia la sección correspondiente dentro de la misma página.
5. Hacer clic en un enlace de la sección en la que ya se encuentra el usuario y confirmar que el sistema no realiza ninguna acción (excepción definida en CU 2.3).

---

### FLUJO C — Filtrado de proyectos (RF20, RF21, RF24)

1. Navegar a la sección de proyectos desde la página de inicio.
2. **Filtro por texto (RF20):** Escribir el nombre parcial de un proyecto en el campo de búsqueda. Verificar que la galería se actualiza mostrando sólo proyectos con coincidencias en Nombre de la Obra o Ubicación Geográfica. Borrar el texto y comprobar que se restaura la vista completa.
3. Ingresar una cadena sin coincidencias (ej.: `xyzzzz`). Confirmar que la galería se vacía y aparece el mensaje "No hay resultados".
4. **Filtro por categoría (RF21):** Abrir el menú desplegable de categorías. Seleccionar "Habitacional". Confirmar que sólo se muestran proyectos de tipo Habitacional. Repetir con "Industrial" y "Agrícola".
5. **Modal de detalle (RF24):** Hacer clic sobre la imagen de cualquier proyecto de la galería filtrada. Verificar que se abre una ventana modal con:
   - Nombre de la Obra
   - Descripción Técnica
   - Ubicación Geográfica
6. Cerrar el modal y confirmar que la galería mantiene el filtro activo previamente aplicado.

---

### FLUJO D — Certificaciones (RF25, RF26)

1. Navegar a `http://localhost:8000/certificaciones`.
2. Verificar que se lista al menos un certificado con nombre visible (ej.: "Coprof", "Norma Chilena").
3. **Descarga (RF26):** Hacer clic en el botón de descarga de algún certificado. Confirmar que el navegador inicia la descarga de un archivo `.pdf`.
4. Abrir el archivo descargado y verificar que el contenido del PDF es legible y corresponde al certificado seleccionado.
5. **Error esperado:** En caso de que no existan certificados cargados en la BD, confirmar que el sistema muestra el mensaje "Aún no hay certificaciones disponibles" (excepción 2 del CU 4.1).

---

### FLUJO E — Autenticación admin (RF28, RF33, RF34)

1. Navegar a `http://localhost:8000`.
2. **Login exitoso (RF28):** Abrir la ventana modal de "Iniciar Sesión". Ingresar las credenciales correctas del administrador (obtenidas del seeder). Hacer clic en "Ingresar". Verificar la redirección al panel `/admin/proyectos`.
3. **Logout (RF33):** Desde el panel admin, hacer clic en el botón de cerrar sesión. Confirmar la redirección a la página de inicio (`/`). Intentar acceder directamente a `http://localhost:8000/admin/proyectos` y verificar que el sistema rechaza el acceso.
4. **Bloqueo por intentos fallidos (RF34):** Abrir el modal de login. Ingresar credenciales incorrectas **5 veces consecutivas**. En el sexto intento (o tras el quinto), verificar:
   - Aparece un mensaje de bloqueo indicando que la cuenta ha sido bloqueada por 60 minutos.
   - Se envía un email de notificación a la dirección del administrador (revisar bandeja de entrada o logs de Resend).
5. Verificar en la BD el estado del bloqueo:
   ```sql
   SELECT email, locked_until, intentos_fallidos FROM administradores;
   ```
6. Esperar el tiempo de bloqueo (o modificar `locked_until` en la BD para pruebas rápidas) y confirmar que el login vuelve a estar disponible.

---

### FLUJO F — Gestión proyectos admin (RF49, RF50)

1. Autenticarse en el panel admin (ver Flujo E).
2. **Crear proyecto (RF49):** Navegar al módulo de Proyectos (`/admin/proyectos`). Hacer clic en "Nuevo Proyecto". En el modal, ingresar:
   - Nombre del Proyecto
   - Cargar entre 1 y 15 fotografías en formatos permitidos (jpg, png, webp).
3. Hacer clic en "Guardar". Verificar que:
   - El modal se cierra automáticamente.
   - La nueva tarjeta de proyecto aparece en la lista con estado `Borrador`.
4. **Validación de límite de fotos:** Intentar subir más de 15 fotografías y confirmar que el sistema bloquea el envío y muestra el mensaje de error.
5. Verificar en la BD:
   ```sql
   SELECT * FROM proyectos ORDER BY id_proyecto DESC LIMIT 1;
   ```
6. **Editar proyecto (RF50):** Localizar la tarjeta del proyecto recién creado. Hacer clic en "Editar Información". Verificar que el modal se abre con los datos actuales precargados. Modificar el nombre del proyecto y agregar una fotografía adicional (sin superar el límite). Hacer clic en "Guardar". Confirmar que la tarjeta refleja los cambios inmediatamente.

---

### FLUJO G — Gestión colaboradores admin (RF46)

1. Autenticarse en el panel admin (ver Flujo E).
2. Navegar al módulo de Colaboradores (`/admin/colaboradores`).
3. Hacer clic en el botón "Agregar Colaborador".
4. Verificar que se despliega un modal con los campos **Nombre Comercial** y **Logotipo**.
5. **Validación de campos vacíos:** Intentar guardar con el campo Nombre Comercial vacío. Confirmar que el sistema resalta el campo con error y no guarda.
6. **Validación de formato de logotipo:** Intentar subir un archivo `.pdf` como logotipo. Confirmar el mensaje de formato no permitido.
7. **Guardado correcto:** Ingresar un nombre comercial válido y cargar una imagen en formato permitido (jpg, png, svg, webp). Hacer clic en "Guardar".
8. Verificar que:
   - El modal se cierra automáticamente.
   - El nuevo colaborador aparece en el listado del módulo.
9. Confirmar en la BD:
   ```sql
   SELECT * FROM colaboradores ORDER BY id_colaborador DESC LIMIT 1;
   ```

---

## 5. COMANDOS ÚTILES

```bash
# ─────────────────────────────────────────────
# Setup inicial completo
# ─────────────────────────────────────────────
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve

# ─────────────────────────────────────────────
# Desarrollo diario
# ─────────────────────────────────────────────

# Listar todas las rutas registradas
php artisan route:list

# Listar rutas filtradas por prefijo admin
php artisan route:list --path=admin

# Limpiar caché de rutas, configuración y aplicación
php artisan route:clear && php artisan config:clear && php artisan cache:clear

# Regenerar autoload de Composer (tras crear nuevas clases)
composer dump-autoload

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# ─────────────────────────────────────────────
# Verificar BD directamente (PostgreSQL)
# ─────────────────────────────────────────────

# Últimas 5 consultas de contacto
psql -U postgres -d ingecon_db -c "SELECT * FROM consultas ORDER BY id_consulta DESC LIMIT 5;"

# Estado de administradores (bloqueos, intentos)
psql -U postgres -d ingecon_db -c "SELECT * FROM administradores;"

# Últimos proyectos creados
psql -U postgres -d ingecon_db -c "SELECT id_proyecto, nombre, estado, created_at FROM proyectos ORDER BY id_proyecto DESC LIMIT 5;"

# Certificaciones almacenadas
psql -U postgres -d ingecon_db -c "SELECT id_certificacion, nombre, created_at FROM certificaciones;"

# Colaboradores registrados
psql -U postgres -d ingecon_db -c "SELECT * FROM colaboradores ORDER BY id_colaborador DESC LIMIT 5;"

# ─────────────────────────────────────────────
# Almacenamiento
# ─────────────────────────────────────────────

# Crear enlace simbólico public/storage → storage/app/public
php artisan storage:link

# Verificar permisos de la carpeta storage
chmod -R 775 storage bootstrap/cache
```

---

## 6. VARIABLES DE ENTORNO COMPLETAS (`.env`)

```dotenv
# ─────────────────────────────────────────────
# Configuración de la aplicación
# ─────────────────────────────────────────────
APP_NAME="Ingecon"                        # Nombre visible de la aplicación
APP_ENV=local                             # Entorno: local | staging | production
APP_KEY=                                  # Generada automáticamente con php artisan key:generate
APP_DEBUG=true                            # true en desarrollo, false en producción
APP_URL=http://localhost:8000             # URL base de la aplicación

# ─────────────────────────────────────────────
# Base de datos — PostgreSQL 16
# ─────────────────────────────────────────────
DB_CONNECTION=pgsql                       # Driver de base de datos
DB_HOST=127.0.0.1                         # Host de PostgreSQL
DB_PORT=5432                              # Puerto por defecto de PostgreSQL
DB_DATABASE=ingecon_db                    # Nombre de la base de datos
DB_USERNAME=postgres                      # Usuario de PostgreSQL
DB_PASSWORD=secret                        # Contraseña del usuario PostgreSQL

# ─────────────────────────────────────────────
# Hashing — Argon2id (requerido por RF34)
# ─────────────────────────────────────────────
HASH_DRIVER=argon2id                      # Algoritmo de hashing para contraseñas

# ─────────────────────────────────────────────
# Email — Resend (requerido para notificación de bloqueo RF34)
# ─────────────────────────────────────────────
MAIL_MAILER=resend                        # Driver de correo: resend
MAIL_FROM_ADDRESS=noreply@ingecon.cl      # Dirección remitente de los emails
MAIL_FROM_NAME="${APP_NAME}"              # Nombre remitente (usa APP_NAME)
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxx    # API Key obtenida en dashboard.resend.com

# ─────────────────────────────────────────────
# Sesión
# ─────────────────────────────────────────────
SESSION_DRIVER=database                   # Driver: database | file | cookie | redis
SESSION_LIFETIME=120                      # Duración de la sesión en minutos
SESSION_COOKIE=ingecon_session            # Nombre de la cookie de sesión
SESSION_SECURE_COOKIE=false              # true en producción con HTTPS
SESSION_HTTP_ONLY=true                    # La cookie no es accesible desde JS (seguridad)
SESSION_SAME_SITE=lax                     # Protección CSRF: lax | strict | none

# ─────────────────────────────────────────────
# Almacenamiento de archivos
# ─────────────────────────────────────────────
FILESYSTEM_DISK=public                    # Disco por defecto: public | local | s3

# ─────────────────────────────────────────────
# Cola de trabajos (para envío de emails asíncrono)
# ─────────────────────────────────────────────
QUEUE_CONNECTION=sync                     # sync (síncrono) | database | redis
```

---

## 7. ERRORES COMUNES Y SOLUCIONES

### Error 1 — BYTEA/binary en PostgreSQL con Eloquent

**Síntoma:**
```
PDOException: SQLSTATE[22021]: Character not in repertoire: 7 ERROR: invalid byte sequence for encoding "UTF8"
```

**Causa:** Se intenta guardar directamente el contenido binario de un archivo (PDF, imagen) como `string` en un campo `TEXT` o `VARCHAR` de PostgreSQL. PostgreSQL rechaza bytes no UTF-8 en columnas de texto.

**Solución:** Guardar archivos en `storage/app/public` y almacenar únicamente la ruta relativa en la BD. Para binarios en BD, usar columna `BYTEA` y codificar en base64 antes de insertar:

```php
// ✅ Correcto — guardar en disco y almacenar ruta
$ruta = $request->file('certificado')->store('certificaciones', 'public');
Certificacion::create(['nombre' => $request->nombre, 'ruta' => $ruta]);

// ✅ Correcto — si se requiere almacenar binario en BD (columna bytea)
$binario = file_get_contents($request->file('certificado')->getRealPath());
DB::table('certificaciones')->insert([
    'nombre'    => $request->nombre,
    'contenido' => base64_encode($binario), // o usar pg_escape_bytea
]);
```

---

### Error 2 — CSRF token mismatch en `fetch` desde Alpine.js

**Síntoma:** `419 | Page Expired` al hacer `fetch('POST /contacto')` desde un componente Alpine.

**Causa:** Laravel espera el token CSRF en cada petición POST. Las peticiones `fetch` nativas no envían la cookie `XSRF-TOKEN` automáticamente.

**Solución:** Leer el token desde la meta tag en `<head>` de la plantilla Blade e incluirlo en los headers de cada `fetch`:

```html
{{-- En resources/views/layouts/app.blade.php --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
```

```javascript
// En el componente Alpine.js
async enviarFormulario() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const response = await fetch('/contacto', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify(this.formData),
    });
}
```

---

### Error 3 — Modal que no responde al evento `Alpine dispatch`

**Síntoma:** Se ejecuta `$dispatch('abrir-modal', { id: proyecto.id })` pero el componente modal no reacciona.

**Causa:** El componente que escucha el evento no está en el mismo árbol DOM que el que lo emite, o el nombre del evento tiene diferencias de mayúsculas/minúsculas.

**Solución:** Usar `window.dispatchEvent` para eventos globales, ya que `$dispatch` en Alpine.js sólo sube por el árbol DOM (bubble):

```html
<!-- Componente que emite el evento -->
<button @click="$dispatch('open-modal', { id: proyecto.id })">Ver detalle</button>

<!-- Componente receptor (puede estar en otro lugar del DOM) -->
<div x-data="{ abierto: false, proyectoId: null }"
     @open-modal.window="abierto = true; proyectoId = $event.detail.id">
    <template x-if="abierto">
        <div class="modal">...</div>
    </template>
</div>
```

> Nota el sufijo `.window` en el listener: `@open-modal.window`. Esto indica a Alpine que escuche el evento en el objeto `window` global.

---

### Error 4 — Imágenes base64 que no cargan en `<img src>`

**Síntoma:** La imagen no se muestra; el navegador muestra el ícono de imagen rota. En la consola: `GET data:image/png;base64,... net::ERR_INVALID_URL`.

**Causa:** El string base64 almacenado en la BD contiene saltos de línea (`\n`) que se insertan por defecto en `base64_encode()` de PHP cuando se procesa con algunas funciones, o se está concatenando mal el prefijo `data:image/...`.

**Solución:**

```php
// En el controlador, al retornar la imagen como data URL:
$imagenBase64 = base64_encode(file_get_contents(storage_path('app/public/' . $logo->ruta)));
$mimeType     = mime_content_type(storage_path('app/public/' . $logo->ruta)); // ej: image/png

$dataUrl = 'data:' . $mimeType . ';base64,' . $imagenBase64;
```

```html
{{-- En Blade --}}
<img src="{{ $dataUrl }}" alt="Logotipo">
```

> Alternativa recomendada: usar `asset(Storage::url($logo->ruta))` en lugar de data URLs para imágenes almacenadas en disco.

---

### Error 5 — Cookie `httpOnly` no se envía en `fetch`

**Síntoma:** Las rutas protegidas por `admin.auth` devuelven 401 o redirigen al inicio aunque el usuario esté autenticado.

**Causa:** `fetch` no envía cookies por defecto. La sesión de Laravel depende de la cookie `ingecon_session` que está marcada como `httpOnly`.

**Solución:** Incluir `credentials: 'same-origin'` en todas las llamadas `fetch` que requieran autenticación:

```javascript
const response = await fetch('/admin/proyectos', {
    method: 'GET',
    credentials: 'same-origin', // ← Envía cookies de sesión automáticamente
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
});
```

---

### Error 6 — Error 419 en `POST` sin `@csrf` en formularios Blade

**Síntoma:** Al enviar un formulario HTML (`<form method="POST">`), se obtiene la pantalla de error `419 | Page Expired`.

**Causa:** Laravel verifica el token CSRF en todas las peticiones POST/PUT/PATCH/DELETE. Si el formulario no incluye el campo `_token`, el middleware `VerifyCsrfToken` rechaza la petición.

**Solución:** Agregar la directiva `@csrf` dentro de cualquier formulario Blade que use métodos distintos a GET:

```html
<form action="{{ route('contacto.store') }}" method="POST" enctype="multipart/form-data">
    @csrf   {{-- Genera <input type="hidden" name="_token" value="..."> --}}

    <input type="text" name="nombre" required>
    <input type="text" name="apellido" required>
    <input type="email" name="email" required>
    <textarea name="mensaje" required></textarea>
    <input type="date" name="fecha" required>
    <input type="file" name="adjunto" accept=".pdf,.doc,.docx">

    <button type="submit">Enviar consulta</button>
</form>
```

> Para formularios de edición que usan PUT/PATCH, agregar también `@method('PUT')` después de `@csrf`.
