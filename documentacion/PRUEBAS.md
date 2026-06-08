# PRUEBAS — Incremento 1
## Plataforma Web Ingecon · Laravel 11 · PHP 8.3 · PostgreSQL 16

---

## CHECKLIST DE VERIFICACION POR RF

| RF | Descripcion corta | Metodo HTTP + Endpoint | Como verificarlo manualmente |
|----|-------------------|------------------------|------------------------------|
| RF01 | Visitante envia consulta de contacto | `POST /contacto` | Completar el formulario, enviar y confirmar que aparece un registro nuevo en la tabla `consulta` de la BD. |
| RF02 | Formulario con campos: Nombre, Apellido, Email, Mensaje, Fecha, Adjunto | `GET /` (formulario visible en la vista) | Inspeccionar el HTML del formulario y comprobar que existen los 6 inputs definidos. |
| RF07 | Mensaje de error cuando campos no cumplen validaciones | `POST /contacto` (respuesta de error) | Enviar el formulario vacio o con email invalido y verificar que aparece el mensaje de validacion correspondiente. |
| RF12 | Icono de menu despliega menu lateral | `GET /` (frontend Alpine.js) | Hacer clic en el icono de hamburguesa y comprobar que el menu lateral se abre con los enlaces de navegacion. |
| RF13 | Barra de navegacion fija con scroll suave | `GET /` (frontend CSS/JS) | Hacer scroll vertical en la pagina y verificar que la barra permanece visible; hacer clic en un enlace y confirmar el desplazamiento suave a la seccion. |
| RF20 | Filtrar proyectos por texto (nombre/ubicacion) | `GET /proyectos/buscar?texto=` | Ingresar una palabra clave en el buscador y verificar que la galeria muestra solo proyectos con coincidencias en nombre u ubicacion. |
| RF21 | Filtrar proyectos por categoria (Habitacional/Industrial/Agricola) | `GET /proyectos/buscar?categoria=Habitacional` | Seleccionar cada categoria en el desplegable y confirmar que solo se muestran proyectos de esa categoria. |
| RF24 | Modal con especificaciones tecnicas del proyecto | `GET /proyectos/{id}/detalle` (AJAX/fetch) | Hacer clic sobre la imagen de un proyecto y verificar que la ventana modal muestra Nombre, Descripcion y Ubicacion correctos. |
| RF25 | Visualizar certificaciones en formato PDF | `GET /certificaciones` | Navegar a `/certificaciones` y confirmar que se lista al menos un certificado con nombre y vista previa. |
| RF26 | Descargar certificado en formato PDF | `GET /certificaciones/{id}/descargar` | Hacer clic en el boton de descarga y verificar que el navegador descarga un archivo `.pdf` valido. |
| RF28 | Login admin mediante modal con credenciales | `POST /login` | Abrir el modal de inicio de sesion, ingresar credenciales correctas y verificar la redireccion al panel de administracion. |
| RF33 | Logout invalida sesion y redirige al inicio | `POST /logout` | Estando en el panel admin, cerrar sesion y confirmar la redireccion a `/`; intentar acceder a `/admin/proyectos/panel` y verificar el rechazo. |
| RF34 | Bloqueo por 60 min tras 5 intentos fallidos y notificacion email | `POST /login` (5 veces con credenciales incorrectas) | Intentar login 5 veces con contrasena incorrecta; al sexto intento comprobar el mensaje de bloqueo y revisar el correo institucional. |
| RF46 | Registrar colaborador (Nombre Comercial + Logotipo) desde modal | `POST /admin/colaboradores` | En el panel admin, abrir el modal "Agregar Colaborador", completar los campos y guardar; verificar que aparece en la lista. |
| RF49 | Crear proyecto (Nombre + Fotografias) con estado Borrador | `POST /admin/proyectos` | Hacer clic en "Nuevo Proyecto", completar el formulario modal y guardar; verificar que la tarjeta aparece con estado `borrador` en el panel. |
| RF50 | Editar proyecto existente desde modal con datos precargados | `PUT /admin/proyectos/{id}` | Hacer clic en "Editar Informacion" de un proyecto, verificar que los datos actuales aparecen precargados, modificarlos y guardar; confirmar los cambios en la tarjeta. |

---

## FLUJOS DE PRUEBA MANUAL PASO A PASO

### FLUJO A — Visitante envia consulta (RF01, RF02, RF07)

1. Abrir el navegador y navegar a `http://localhost:8000`.
2. Localizar la seccion del **Formulario de Contacto** en la pagina de inicio.
3. Verificar que existen los campos: **Nombre**, **Apellido**, **Email**, **Mensaje**, **Fecha** y **Adjunto**.
4. **Prueba de validacion vacia:** Hacer clic en "Enviar" sin rellenar ningun campo. Confirmar que aparecen mensajes de error en cada campo obligatorio.
5. **Prueba de email invalido:** Escribir `noesunmail` en el campo Email y enviar. Confirmar el mensaje de error especifico para el formato de email.
6. **Prueba de adjunto invalido:** Intentar adjuntar un archivo `.exe` o un archivo mayor a 10 MB. Confirmar que el sistema lo rechaza con mensaje de error.
7. **Envio correcto:** Rellenar todos los campos con datos validos. Adjuntar un PDF menor a 10 MB. Hacer clic en "Enviar".
8. Verificar que aparece un **mensaje de confirmacion** en la interfaz.
9. Acceder a la base de datos y ejecutar:
   ```sql
   SELECT * FROM consulta ORDER BY id_consulta DESC LIMIT 1;
   ```
10. Confirmar que el registro aparece con los datos ingresados.

---

### FLUJO B — Navegacion publica (RF12, RF13)

1. Navegar a `http://localhost:8000`.
2. **Menu lateral (RF12):** Localizar el icono de menu (hamburguesa) en la barra de navegacion. Hacer clic sobre el. Verificar que el menu lateral se despliega sobre el contenido y muestra enlaces a las secciones disponibles. Hacer clic fuera del menu y confirmar que se cierra.
3. **Barra fija (RF13):** Hacer scroll hacia abajo en la pagina. Verificar que la barra de navegacion permanece visible en la parte superior de la pantalla durante todo el desplazamiento.
4. Hacer clic en cada enlace de la barra de navegacion y confirmar que la vista se desplaza suavemente hacia la seccion correspondiente dentro de la misma pagina.
5. Hacer clic en un enlace de la seccion en la que ya se encuentra el usuario y confirmar que el scroll se ejecuta sin errores.

---

### FLUJO C — Filtrado de proyectos (RF20, RF21, RF24)

1. Navegar a la seccion de proyectos desde la pagina de inicio.
2. **Filtro por texto (RF20):** Escribir el nombre parcial de un proyecto en el campo de busqueda. Verificar que la galeria se actualiza mostrando solo proyectos con coincidencias en Nombre de la Obra o Ubicacion Geografica. Borrar el texto y comprobar que se restaura la vista completa.
3. Ingresar una cadena sin coincidencias (ej.: `xyzzzz`). Confirmar que la galeria se vacia y aparece el mensaje "No se encontraron proyectos".
4. **Filtro por categoria (RF21):** Abrir el menu desplegable de categorias. Seleccionar "Habitacional". Confirmar que solo se muestran proyectos de tipo Habitacional. Repetir con "Industrial" y "Agricola".
5. **Modal de detalle (RF24):** Hacer clic sobre la imagen de cualquier proyecto de la galeria filtrada. Verificar que se abre una ventana modal con:
   - Nombre de la Obra
   - Descripcion Tecnica
   - Ubicacion Geografica
   - Carrusel de imagenes con controles prev/next
6. Cerrar el modal y confirmar que la galeria mantiene el filtro activo previamente aplicado.

---

### FLUJO D — Certificaciones (RF25, RF26)

1. Navegar a `http://localhost:8000/certificaciones`.
2. Verificar que se lista al menos un certificado con nombre visible.
3. **Descarga (RF26):** Hacer clic en el boton de descarga de algun certificado. Confirmar que el navegador inicia la descarga de un archivo `.pdf`.
4. Abrir el archivo descargado y verificar que el contenido del PDF es legible y corresponde al certificado seleccionado.
5. **Error esperado:** En caso de que no existan certificados cargados en la BD, confirmar que el sistema muestra el mensaje "No hay certificaciones disponibles por el momento" (excepcion 2 del CU 4.1).

---

### FLUJO E — Autenticacion admin (RF28, RF33, RF34)

1. Navegar a `http://localhost:8000`.
2. **Login exitoso (RF28):** Abrir la ventana modal de "Panel de Gestion". Ingresar las credenciales correctas del administrador (`admin@ingecon.cl` / `Ingecon2024!`). Hacer clic en "Ingresar". Verificar la redireccion al panel `/admin/dashboard`.
3. **Logout (RF33):** Desde el panel admin, hacer clic en el boton de cerrar sesion. Confirmar la redireccion a la pagina de inicio (`/`). Intentar acceder directamente a `http://localhost:8000/admin/proyectos/panel` y verificar que el sistema rechaza el acceso.
4. **Bloqueo por intentos fallidos (RF34):** Abrir el modal de login. Ingresar credenciales incorrectas **5 veces consecutivas**. En el sexto intento, verificar:
   - Aparece un mensaje de bloqueo indicando que la cuenta ha sido bloqueada por 60 minutos.
   - Se envia un email de notificacion a la direccion del administrador (revisar bandeja de entrada o logs).
5. Verificar en la BD el estado del bloqueo:
   ```sql
   SELECT correo, bloqueado_hasta, intentos_fallidos FROM administrador;
   ```
6. Para pruebas rapidas, desbloquear manualmente:
   ```sql
   UPDATE administrador SET bloqueado_hasta = NULL, intentos_fallidos = 0 WHERE correo = 'admin@ingecon.cl';
   ```

---

### FLUJO F — Gestion proyectos admin (RF49, RF50)

1. Autenticarse en el panel admin (ver Flujo E).
2. **Crear proyecto (RF49):** Navegar al modulo de Proyectos (`/admin/proyectos/panel`). Hacer clic en "Nuevo Proyecto". En el modal, ingresar:
   - Nombre de la Obra
   - Cargar entre 1 y 15 fotografias en formatos permitidos (jpg, png, webp).
3. Hacer clic en "Crear Proyecto". Verificar que:
   - El modal se cierra automaticamente.
   - La nueva tarjeta de proyecto aparece en la lista con estado `Borrador`.
4. **Validacion de limite de fotos:** Intentar subir mas de 15 fotografias y confirmar que el sistema bloquea el envio y muestra el mensaje de error.
5. Verificar en la BD:
   ```sql
   SELECT * FROM proyecto ORDER BY id_proyecto DESC LIMIT 1;
   ```
6. **Editar proyecto (RF50):** Localizar la tarjeta del proyecto recien creado. Hacer clic en "Editar Informacion". Verificar que el modal se abre con los datos actuales precargados. Modificar el nombre del proyecto y agregar una fotografia adicional (sin superar el limite). Hacer clic en "Guardar Cambios". Confirmar que la tarjeta refleja los cambios inmediatamente.

---

### FLUJO G — Gestion colaboradores admin (RF46)

1. Autenticarse en el panel admin (ver Flujo E).
2. Navegar al modulo de Colaboradores (`/admin/colaboradores/panel`).
3. Hacer clic en el boton "Agregar Colaborador".
4. Verificar que se despliega un modal con los campos **Nombre Comercial** y **Logotipo**.
5. **Validacion de campos vacios:** Intentar guardar con el campo Nombre Comercial vacio. Confirmar que el sistema resalta el campo con error y no guarda.
6. **Validacion de formato de logotipo:** Intentar subir un archivo `.pdf` como logotipo. Confirmar el mensaje de formato no permitido.
7. **Guardado correcto:** Ingresar un nombre comercial valido y cargar una imagen en formato permitido (jpg, png, svg, webp). Hacer clic en "Guardar".
8. Verificar que:
   - El modal se cierra automaticamente.
   - El nuevo colaborador aparece en el listado del modulo.
9. Confirmar en la BD:
   ```sql
   SELECT * FROM colaborador ORDER BY id_colaborador DESC LIMIT 1;
   ```

---

## COMANDOS UTILES

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

# Limpiar cache de rutas, configuracion y aplicacion
php artisan route:clear && php artisan config:clear && php artisan cache:clear

# Regenerar autoload de Composer (tras crear nuevas clases)
composer dump-autoload

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# ─────────────────────────────────────────────
# Verificar BD directamente (PostgreSQL)
# ─────────────────────────────────────────────

# Ultimas 5 consultas de contacto
psql -U ingecon_user -d ingecon_db -c "SELECT * FROM consulta ORDER BY id_consulta DESC LIMIT 5;"

# Estado de administradores (bloqueos, intentos)
psql -U ingecon_user -d ingecon_db -c "SELECT correo, intentos_fallidos, bloqueado_hasta, activo FROM administrador;"

# Ultimos proyectos creados
psql -U ingecon_user -d ingecon_db -c "SELECT id_proyecto, nombre_obra, estado_publicacion FROM proyecto ORDER BY id_proyecto DESC LIMIT 5;"

# Certificados almacenados
psql -U ingecon_user -d ingecon_db -c "SELECT id_certificado, codigo_lote, fecha_emision, estado FROM certificado;"

# Colaboradores registrados
psql -U ingecon_user -d ingecon_db -c "SELECT * FROM colaborador ORDER BY id_colaborador DESC LIMIT 5;"

# ─────────────────────────────────────────────
# Almacenamiento
# ─────────────────────────────────────────────

# Crear enlace simbolico public/storage → storage/app/public
php artisan storage:link

# Verificar permisos de la carpeta storage
chmod -R 775 storage bootstrap/cache
```

---

## ERRORES COMUNES Y SOLUCIONES

### Error 1 — BYTEA/binary en PostgreSQL con Eloquent

**Sintoma:**
```
PDOException: SQLSTATE[22021]: Character not in repertoire: 7 ERROR: invalid byte sequence for encoding "UTF8"
```

**Causa:** Se intenta guardar directamente el contenido binario de un archivo (PDF, imagen) como `string` en un campo `TEXT` o `VARCHAR` de PostgreSQL. PostgreSQL rechaza bytes no UTF-8 en columnas de texto.

**Solucion:** Usar columnas `BYTEA` para almacenar contenido binario. Eloquent con el driver `pgsql` maneja correctamente `BYTEA`. Configurar `PDO::ATTR_EMULATE_PREPARES => true` en `config/database.php` para que BYTEA se devuelva como string en lugar de resource stream.

```php
// config/database.php — dentro de 'connections' => ['pgsql' => [...]]
'options' => [
    PDO::ATTR_EMULATE_PREPARES => true,
],
```

---

### Error 2 — CSRF token mismatch en `fetch` desde Alpine.js

**Sintoma:** `419 | Page Expired` al hacer `fetch('POST /contacto')` desde un componente Alpine.

**Causa:** Laravel espera el token CSRF en cada peticion POST. Las peticiones `fetch` nativas no envian la cookie `XSRF-TOKEN` automaticamente.

**Solucion:** Leer el token desde la meta tag en `<head>` de la plantilla Blade e incluirlo en los headers de cada `fetch`:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

```javascript
async enviarFormulario() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const response = await fetch('/contacto', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: fd,
    });
}
```

---

### Error 3 — Modal que no responde al evento Alpine dispatch

**Sintoma:** Se ejecuta `$dispatch('abrir-modal')` pero el componente modal no reacciona.

**Causa:** El componente que escucha el evento no esta en el mismo arbol DOM que el que lo emite, o el nombre del evento tiene diferencias de mayusculas/minusculas.

**Solucion:** Usar el sufijo `.window` en el listener para escuchar eventos en el objeto `window` global:

```html
<!-- Componente que emite el evento -->
<button @click="$dispatch('abrir-login')">Login</button>

<!-- Componente receptor -->
<div x-data="{ abierto: false }"
     @abrir-login.window="abierto = true">
```

---

### Error 4 — Imagenes base64 que no cargan en `<img src>`

**Sintoma:** La imagen no se muestra; el navegador muestra el icono de imagen rota.

**Causa:** El string base64 almacenado no incluye el prefijo `data:image/...;base64,` o el tipo MIME es incorrecto.

**Solucion:** Construir el Data URI completo con el tipo MIME correcto:

```php
$mime = $imagen->tipo_mime ?: 'image/jpeg';
$dataUrl = "data:{$mime};base64," . base64_encode($binary);
```

---

### Error 5 — Cookie httpOnly no se envia en fetch

**Sintoma:** Las rutas protegidas por `admin.auth` devuelven 401 o redirigen al inicio aunque el usuario este autenticado. Esto aplica al middleware `AdminAuth` que usa cookie-based auth.

**Causa:** `fetch` no envia cookies por defecto. La sesion de Laravel depende de la cookie `ingecon_session` que esta marcada como `httpOnly`.

**Solucion:** Incluir `credentials: 'same-origin'` en todas las llamadas `fetch` que requieran autenticacion:

```javascript
const response = await fetch('/admin/proyectos', {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
});
```

---

### Error 6 — Error 419 en POST sin @csrf en formularios Blade

**Sintoma:** Al enviar un formulario HTML (`<form method="POST">`), se obtiene la pantalla de error `419 | Page Expired`.

**Causa:** Laravel verifica el token CSRF en todas las peticiones POST/PUT/PATCH/DELETE. Si el formulario no incluye el campo `_token`, el middleware `VerifyCsrfToken` rechaza la peticion.

**Solucion:** Agregar la directiva `@csrf` dentro de cualquier formulario Blade que use metodos distintos a GET:

```html
<form method="POST" action="{{ route('auth.logout') }}">
    @csrf
    <button type="submit">Cerrar sesion</button>
</form>
```
