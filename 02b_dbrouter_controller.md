# 02b — DBRouterController (Intermediario de Base de Datos)
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.
> **Propósito:** Definir el `DBRouterController` (C_DBRouter en los diagramas de secuencia), el **único** componente autorizado para acceder al ORM Eloquent. Todos los controladores de dominio (`AuthController`, `ContactoController`, `ProyectoController`, `InstitucionalCtrl`, `AdminController`), el middleware `AdminAuth` y el job `EnviarEmailBloqueoJob` delegan **toda** lectura y escritura de la base de datos en esta clase.
> **Ubicación del archivo:** `app/Http/Controllers/DBRouterController.php`
> **Depende de:** los 9 modelos de `02_modelos_eloquent.md`. Las tablas existen según `01_setup_laravel_migraciones.md`.
> **Leer ANTES de:** `03_autenticacion.md`, `04_formulario_contacto.md`, `06_galeria_proyectos.md`, `07_certificaciones_publicas.md`, `08_admin_proyectos.md`, `09_admin_colaboradores.md`.

---

## 1. Rol Arquitectónico

En el Modelo de Vistas 4+1 (Vista Lógica), la capa de control se subdivide en dos niveles:

```
┌─────────────────────────────────────────────────────────────┐
│  Controladores de dominio (orquestan la petición HTTP)        │
│  AuthController · ContactoController · ProyectoController      │
│  InstitucionalCtrl · AdminController                          │
│  + Middleware AdminAuth · Job EnviarEmailBloqueoJob           │
└───────────────────────────────┬───────────────────────────────┘
                                 │ inyección por constructor / handle()
                                 ▼
┌─────────────────────────────────────────────────────────────┐
│  DBRouterController  «Control»  (Intermediario BD)            │
│  ─ única clase que invoca métodos de Eloquent                 │
│  ─ centraliza queries, eager-loading y persistencia           │
└───────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────┐
│  Eloquent ORM «Entity»  →  PostgreSQL 16                      │
│  Administrador · Visitante · Sesion · Consulta ·              │
│  ArchivoAdjunto · Proyecto · ImagenProyecto ·                 │
│  Certificado · Colaborador                                    │
└─────────────────────────────────────────────────────────────┘
```

**Reglas de uso obligatorias para el generador de código:**

1. Ningún controlador de dominio, middleware ni job puede contener llamadas directas a Eloquent (`Modelo::where`, `Modelo::create`, `Modelo::find`, `$modelo->save()`, `Modelo::whereIn(...)->delete()`, etc.). Toda interacción con la BD pasa por un método de `DBRouterController`.
2. `DBRouterController` **no expone rutas HTTP**. Pese a su nombre y a residir en `app/Http/Controllers/`, no es un controlador enrutable: no se registra en `routes/web.php` y no recibe `Request`. Es un mediador de datos inyectable. (El nombre se conserva por fidelidad al diagrama de clases, donde aparece como `DBRouterController` con estereotipo «Control».)
3. Los métodos del router devuelven **modelos Eloquent o colecciones**. La conversión de BYTEA a base64, el formateo de fechas y el armado de los arreglos JSON son responsabilidad de cada controlador de dominio (capa de presentación de datos), no del router.
4. Los métodos del router son **operaciones atómicas simples** (una intención de negocio cada uno). No agregan transacciones implícitas: la lógica de compensación (p. ej. borrar una consulta si su adjunto resulta inválido) permanece en el controlador, que invoca los métodos del router en el orden correcto. (Ver nota opcional sobre transacciones en la sección 4.)

---

## 2. Clase Completa — `DBRouterController`

**Archivo:** `app/Http/Controllers/DBRouterController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\ArchivoAdjunto;
use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Consulta;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use App\Models\Sesion;
use App\Models\Visitante;
use Illuminate\Database\Eloquent\Collection;

/**
 * DBRouterController — Intermediario de Base de Datos (C_DBRouter).
 *
 * ÚNICA clase del sistema autorizada para invocar el ORM Eloquent.
 * Todos los controladores de dominio, el middleware AdminAuth y el job
 * EnviarEmailBloqueoJob reciben una instancia de esta clase por inyección
 * de dependencias y delegan en ella toda lectura/escritura de la BD.
 *
 * ⚠️  NO es un controlador enrutable: no se registra en routes/web.php,
 *     no recibe Request y no devuelve respuestas HTTP. El nombre se conserva
 *     por fidelidad al diagrama de clases (estereotipo «Control»).
 *
 * Convenciones:
 *  - Devuelve modelos Eloquent o Collections; nunca arreglos JSON ni base64.
 *  - Cada método es una operación de negocio simple y atómica.
 *  - La conversión BYTEA→base64 y el formateo se hacen en los controladores.
 */
class DBRouterController
{
    // =========================================================================
    // ADMINISTRADOR · SESIÓN  (consumidos por AuthController, AdminAuth, Job)
    // =========================================================================

    /**
     * Busca un administrador por su correo electrónico.
     * Reemplaza: Administrador::where('correo', $correo)->first()
     */
    public function buscarAdminPorCorreo(string $correo): ?Administrador
    {
        return Administrador::where('correo', $correo)->first();
    }

    /**
     * Busca un administrador por su id.
     * Reemplaza: Administrador::find($idAdmin)
     */
    public function buscarAdminPorId(int $idAdmin): ?Administrador
    {
        return Administrador::find($idAdmin);
    }

    /**
     * Persiste los cambios de un administrador ya cargado en memoria
     * (intentos_fallidos, bloqueado_hasta, reset de contadores, etc.).
     * Reemplaza: $admin->save()
     */
    public function guardarAdmin(Administrador $admin): bool
    {
        return $admin->save();
    }

    /**
     * Crea un nuevo registro de sesión.
     * Reemplaza: Sesion::create([...])
     *
     * @param array{token_hash:string,fecha_inicio:mixed,estado:string,id_admin:int} $datos
     */
    public function crearSesion(array $datos): Sesion
    {
        return Sesion::create($datos);
    }

    /**
     * Busca una sesión activa por su id (sin filtrar por admin).
     * Usado por el middleware AdminAuth.
     * Reemplaza: Sesion::where('id_sesion', $id)->where('estado','activa')->first()
     */
    public function buscarSesionActivaPorId(int $idSesion): ?Sesion
    {
        return Sesion::where('id_sesion', $idSesion)
            ->where('estado', 'activa')
            ->first();
    }

    /**
     * Busca la sesión activa de un administrador concreto.
     * Usado por AuthController@logout.
     * Reemplaza: Sesion::where('id_sesion',$id)->where('id_admin',$a)->where('estado','activa')->first()
     */
    public function buscarSesionActivaDeAdmin(int $idSesion, int $idAdmin): ?Sesion
    {
        return Sesion::where('id_sesion', $idSesion)
            ->where('id_admin', $idAdmin)
            ->where('estado', 'activa')
            ->first();
    }

    /**
     * Persiste los cambios de una sesión (p. ej. estado='cerrada').
     * Reemplaza: $sesion->save()
     */
    public function guardarSesion(Sesion $sesion): bool
    {
        return $sesion->save();
    }

    // =========================================================================
    // VISITANTE · CONSULTA · ARCHIVO_ADJUNTO  (consumidos por ContactoController)
    // =========================================================================

    /**
     * Devuelve el visitante con ese email o lo crea si no existe.
     * Reemplaza: Visitante::firstOrCreate(['email'=>$email], $datos)
     *
     * @param array{nombre:string,apellido:string} $datos
     */
    public function obtenerOCrearVisitante(string $email, array $datos): Visitante
    {
        return Visitante::firstOrCreate(['email' => $email], $datos);
    }

    /**
     * Crea una consulta de contacto.
     * Reemplaza: Consulta::create([...])
     */
    public function crearConsulta(array $datos): Consulta
    {
        return Consulta::create($datos);
    }

    /**
     * Elimina una consulta (compensación si el adjunto resulta inválido).
     * Reemplaza: $consulta->delete()
     */
    public function eliminarConsulta(Consulta $consulta): void
    {
        $consulta->delete();
    }

    /**
     * Crea el registro del archivo adjunto (PDF en BYTEA) de una consulta.
     * Reemplaza: ArchivoAdjunto::create([...])
     */
    public function crearArchivoAdjunto(array $datos): ArchivoAdjunto
    {
        return ArchivoAdjunto::create($datos);
    }

    // =========================================================================
    // PROYECTOS — GALERÍA PÚBLICA  (consumidos por ProyectoController)
    // =========================================================================

    /**
     * Devuelve los proyectos PUBLICADOS aplicando filtros opcionales de
     * texto libre (nombre_obra / ubicacion_geografica con ILIKE) y categoría
     * exacta. Eager-load de imágenes (relación imagenesProyecto) ordenadas.
     *
     * Encapsula toda la construcción de query de ProyectoController@buscar.
     * Devuelve la colección de modelos; el controlador arma el JSON.
     *
     * @param  string|null $texto      término de búsqueda libre ('' o null = sin filtro)
     * @param  string|null $categoria  'Habitacional'|'Industrial'|'Agrícola' ('' = sin filtro)
     * @return Collection<int,Proyecto>
     */
    public function buscarProyectosPublicados(?string $texto, ?string $categoria): Collection
    {
        $query = Proyecto::where('estado_publicacion', 'publicado');

        // Filtro por texto libre (RF20) — ILIKE case-insensitive en dos campos.
        if (filled($texto)) {
            $termino = '%' . $texto . '%';
            $query->where(function ($q) use ($termino) {
                $q->whereRaw('nombre_obra ILIKE ?', [$termino])
                  ->orWhereRaw('ubicacion_geografica ILIKE ?', [$termino]);
            });
        }

        // Filtro por categoría exacta (RF21).
        if (filled($categoria)) {
            $query->where('categoria', $categoria);
        }

        // Eager-load de todas las imágenes (ver nota sobre el bug de limit()
        // en with() en 06_galeria_proyectos.md). El controlador toma la primera.
        $query->with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');
        }]);

        return $query->orderByRaw('anio_ejecucion DESC NULLS LAST')->get();
    }

    /**
     * Busca un proyecto por id con TODAS sus imágenes (relación imagenesProyecto)
     * para el modal de detalle. NO filtra por estado: el controlador valida que
     * esté 'publicado' antes de exponerlo.
     *
     * Reemplaza: Proyecto::with(['imagenesProyecto'=>...])->find($id)
     */
    public function buscarProyectoConImagenes(int $id): ?Proyecto
    {
        return Proyecto::with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');
        }])->find($id);
    }

    // =========================================================================
    // CERTIFICADOS  (consumidos por ProyectoController e InstitucionalCtrl)
    // =========================================================================

    /**
     * Lista los certificados ACTIVOS con metadatos, SIN el BYTEA archivo_pdf
     * (crítico para performance) y con su proyecto (id, nombre_obra, region).
     *
     * Centraliza la query duplicada entre InstitucionalCtrl@index y
     * ProyectoController@certificaciones (resuelve la nota DRY de 07).
     * El formateo d/m/Y de fecha_emision lo aplica cada controlador.
     *
     * @return Collection<int,Certificado>
     */
    public function listarCertificadosActivos(): Collection
    {
        return Certificado::select([
                'id_certificado',
                'codigo_lote',
                'fecha_emision',
                'estado',
                'id_proyecto',
                // archivo_pdf intencionalmente excluido del listado
            ])
            ->where('estado', 'activo')
            ->with(['proyecto' => function ($query) {
                $query->select(['id_proyecto', 'nombre_obra', 'region']);
            }])
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }

    /**
     * Recupera un certificado con su BYTEA archivo_pdf para descarga/preview.
     * Reemplaza: Certificado::select([...,'archivo_pdf'])->where('id_certificado',$id)->first()
     */
    public function buscarCertificadoParaDescarga(int $id): ?Certificado
    {
        return Certificado::select([
                'id_certificado',
                'codigo_lote',
                'archivo_pdf',
                'estado',
            ])
            ->where('id_certificado', $id)
            ->first();
    }

    // =========================================================================
    // PROYECTOS — PANEL ADMIN  (consumidos por AdminController)
    // =========================================================================

    /**
     * Lista los proyectos de un administrador con la primera imagen
     * (relación imagenes) y el conteo de imágenes (imagenes_count).
     *
     * Reemplaza: Proyecto::where('id_admin',$id)->with(['imagenes'=>...])->withCount('imagenes')->orderBy(...)->get()
     *
     * @return Collection<int,Proyecto>
     */
    public function listarProyectosDeAdmin(int $idAdmin): Collection
    {
        return Proyecto::where('id_admin', $idAdmin)
            ->with(['imagenes' => fn($q) => $q->orderBy('id_imagen')->limit(1)])
            ->withCount('imagenes')
            ->orderBy('id_proyecto', 'desc')
            ->get();
    }

    /**
     * Crea un proyecto.
     * Reemplaza: Proyecto::create([...])
     */
    public function crearProyecto(array $datos): Proyecto
    {
        return Proyecto::create($datos);
    }

    /**
     * Busca un proyecto por id (sin eager-load), para edición.
     * Reemplaza: Proyecto::find($id)
     */
    public function buscarProyectoPorId(int $id): ?Proyecto
    {
        return Proyecto::find($id);
    }

    /**
     * Persiste los cambios de un proyecto ya cargado.
     * Reemplaza: $proyecto->save()
     */
    public function guardarProyecto(Proyecto $proyecto): bool
    {
        return $proyecto->save();
    }

    /**
     * Crea una imagen (BYTEA) asociada a un proyecto.
     * Reemplaza: ImagenProyecto::create([...])
     */
    public function crearImagenProyecto(array $datos): ImagenProyecto
    {
        return ImagenProyecto::create($datos);
    }

    /**
     * Cuenta las imágenes de un proyecto.
     * Reemplaza: $proyecto->imagenes()->count()
     */
    public function contarImagenesDeProyecto(Proyecto $proyecto): int
    {
        return $proyecto->imagenes()->count();
    }

    /**
     * Devuelve la primera imagen de un proyecto (para regenerar thumbnail).
     * Reemplaza: $proyecto->imagenes()->orderBy('id_imagen')->first()
     */
    public function primeraImagenDeProyecto(Proyecto $proyecto): ?ImagenProyecto
    {
        return $proyecto->imagenes()->orderBy('id_imagen')->first();
    }

    /**
     * Elimina las imágenes indicadas que pertenezcan a un proyecto concreto.
     * Reemplaza: ImagenProyecto::whereIn('id_imagen',$ids)->where('id_proyecto',$p)->delete()
     *
     * @param int[] $idsImagenes
     * @return int  número de filas eliminadas
     */
    public function eliminarImagenesDeProyecto(array $idsImagenes, int $idProyecto): int
    {
        if (empty($idsImagenes)) {
            return 0;
        }

        return ImagenProyecto::whereIn('id_imagen', $idsImagenes)
            ->where('id_proyecto', $idProyecto)
            ->delete();
    }

    // =========================================================================
    // COLABORADORES — PANEL ADMIN  (consumidos por AdminController)
    // =========================================================================

    /**
     * Lista los colaboradores de un administrador con su logotipo (BYTEA)
     * y tipo_mime. El controlador convierte el BYTEA a Data URI.
     *
     * Reemplaza: Colaborador::where('id_admin',$id)->select([...])->orderBy(...)->get()
     *
     * @return Collection<int,Colaborador>
     */
    public function listarColaboradoresDeAdmin(int $idAdmin): Collection
    {
        return Colaborador::where('id_admin', $idAdmin)
            ->select([
                'id_colaborador',
                'nombre_comercial',
                'logotipo',
                'tipo_mime',
            ])
            ->orderBy('nombre_comercial', 'asc')
            ->get();
    }

    /**
     * Crea un colaborador con su logotipo en BYTEA.
     * Reemplaza: Colaborador::create([...])
     */
    public function crearColaborador(array $datos): Colaborador
    {
        return Colaborador::create($datos);
    }
}
```

---

## 3. Patrón de Inyección de Dependencias

El contenedor de servicios de Laravel resuelve `DBRouterController` automáticamente. No requiere binding manual en ningún `ServiceProvider`.

### 3.1 En un controlador de dominio (mismo namespace `App\Http\Controllers`)

```php
class ContactoController extends Controller
{
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    public function store(Request $request): JsonResponse
    {
        // ...
        $visitante = $this->db->obtenerOCrearVisitante($email, [...]);
    }
}
```

> No se necesita `use App\Http\Controllers\DBRouterController;` cuando el controlador ya está en `App\Http\Controllers`.

### 3.2 En el middleware `AdminAuth` (namespace distinto)

```php
namespace App\Http\Middleware;

use App\Http\Controllers\DBRouterController;

class AdminAuth
{
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sesion = $this->db->buscarSesionActivaPorId((int) $idSesion);
        // ...
        $admin = $this->db->buscarAdminPorId($sesion->id_admin);
    }
}
```

### 3.3 En el job `EnviarEmailBloqueoJob` (resolver en `handle()`)

Los jobs encolados no deben recibir dependencias del contenedor por constructor (se serializan). Inyectar en `handle()`:

```php
use App\Http\Controllers\DBRouterController;

public function handle(DBRouterController $db): void
{
    $admin = $db->buscarAdminPorId($this->adminId);
    // ...
}
```

---

## 4. Notas de Implementación

### Relaciones `imagenes` vs `imagenesProyecto`
El plan original usa la relación `imagenesProyecto` en la galería pública (`06`) y `imagenes` en el panel admin (`08`). El router preserva ese contrato: `buscarProyectosPublicados()` y `buscarProyectoConImagenes()` cargan `imagenesProyecto`; `listarProyectosDeAdmin()`, `contarImagenesDeProyecto()` y `primeraImagenDeProyecto()` usan `imagenes`. Ambas relaciones deben existir en el modelo `Proyecto` (`02_modelos_eloquent.md`).

### Transacciones (opcional, no incluido por defecto)
Para reforzar la atomicidad de operaciones multi-paso (crear proyecto + N imágenes, crear consulta + adjunto), puede envolverse la secuencia con `DB::transaction(function () use (...) { ... })` **dentro del controlador**, llamando a los métodos del router en la closure. No se aplica por defecto para no alterar la lógica de compensación ya descrita en los controladores.

### El router devuelve modelos, no JSON
Toda conversión `BYTEA → base64` (`getRawOriginal()` + `stream_get_contents()` + `base64_encode()`), el formateo de fechas (`format('d/m/Y')`) y el armado de los arreglos de respuesta permanecen en los controladores. El router nunca serializa.

---

## 5. Mapa de Reemplazos (referencia rápida para el generador)

| Archivo / clase consumidora | Llamada Eloquent directa (antes) | Método de DBRouterController (después) |
|---|---|---|
| `AuthController@login` | `Administrador::where('correo',…)->first()` | `buscarAdminPorCorreo($correo)` |
| `AuthController@login` | `$admin->save()` (intentos/bloqueo/reset) | `guardarAdmin($admin)` |
| `AuthController@login` | `Sesion::create([...])` | `crearSesion([...])` |
| `AuthController@logout` | `Sesion::where(...)->where(...)->where('estado','activa')->first()` | `buscarSesionActivaDeAdmin($idSesion,$idAdmin)` |
| `AuthController@logout` | `$sesion->save()` | `guardarSesion($sesion)` |
| `AdminAuth@handle` | `Sesion::where('id_sesion',…)->where('estado','activa')->first()` | `buscarSesionActivaPorId($idSesion)` |
| `AdminAuth@handle` | `Administrador::find($id)` | `buscarAdminPorId($id)` |
| `EnviarEmailBloqueoJob@handle` | `Administrador::find($id)` | `buscarAdminPorId($id)` |
| `ContactoController@store` | `Visitante::firstOrCreate(...)` | `obtenerOCrearVisitante($email,[...])` |
| `ContactoController@store` | `Consulta::create([...])` | `crearConsulta([...])` |
| `ContactoController@store` | `$consulta->delete()` | `eliminarConsulta($consulta)` |
| `ContactoController@store` | `ArchivoAdjunto::create([...])` | `crearArchivoAdjunto([...])` |
| `ProyectoController@buscar` | `Proyecto::where('estado_publicacion','publicado')…->get()` | `buscarProyectosPublicados($texto,$categoria)` |
| `ProyectoController@detalle` | `Proyecto::with([...])->find($id)` | `buscarProyectoConImagenes($id)` |
| `ProyectoController@certificaciones` | `Certificado::select([...])->where('estado','activo')…->get()` | `listarCertificadosActivos()` |
| `ProyectoController@descargarCertificado` | `Certificado::select([...,'archivo_pdf'])->where(…)->first()` | `buscarCertificadoParaDescarga($id)` |
| `InstitucionalCtrl@index` | `Certificado::select([...])->where('estado','activo')…->get()` | `listarCertificadosActivos()` |
| `AdminController@indexProyectos` | `Proyecto::where('id_admin',…)->with(...)->withCount(...)->get()` | `listarProyectosDeAdmin($idAdmin)` |
| `AdminController@storeProyecto` | `Proyecto::create([...])` | `crearProyecto([...])` |
| `AdminController@storeProyecto` / `updateProyecto` | `ImagenProyecto::create([...])` | `crearImagenProyecto([...])` |
| `AdminController@updateProyecto` | `Proyecto::find($id)` | `buscarProyectoPorId($id)` |
| `AdminController@updateProyecto` | `$proyecto->save()` | `guardarProyecto($proyecto)` |
| `AdminController@updateProyecto` | `$proyecto->imagenes()->count()` | `contarImagenesDeProyecto($proyecto)` |
| `AdminController@updateProyecto` | `$proyecto->imagenes()->orderBy('id_imagen')->first()` | `primeraImagenDeProyecto($proyecto)` |
| `AdminController@updateProyecto` | `ImagenProyecto::whereIn(...)->where(...)->delete()` | `eliminarImagenesDeProyecto($ids,$idProyecto)` |
| `AdminController@indexColaboradores` | `Colaborador::where('id_admin',…)->select([...])->get()` | `listarColaboradoresDeAdmin($idAdmin)` |
| `AdminController@storeColaborador` | `Colaborador::create([...])` | `crearColaborador([...])` |
