# 07 — Sección Pública de Certificaciones
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa del listado público de certificaciones y descarga de PDFs desde BYTEA en PostgreSQL.  
> **Requerimientos cubiertos:** RF25, RF26 / CU 4.1, CU 4.2.  
> **Convención de modelos:** `Certificado`, `Proyecto` según `02_modelos_eloquent.md`.  
> **Acceso a datos:** los métodos de certificados se añaden a `ProyectoController` (que ya recibe `DBRouterController` por constructor, `06_galeria_proyectos.md`) y a `InstitucionalCtrl` (que también lo recibe por constructor). Toda consulta pasa por el router (`02b_dbrouter_controller.md`); el listado activo se centraliza en `listarCertificadosActivos()`. No hay llamadas directas a Eloquent en este archivo.  
> **La vista** reemplaza el placeholder `resources/views/public/partials/certificaciones.blade.php` definido en `05_navegacion_publica.md`.

---

## Estructura de Archivos a Crear o Modificar

```
app/
└── Http/
    └── Controllers/
        ├── ProyectoController.php        ← agregar métodos certificaciones() y descargarCertificado()
        └── InstitucionalCtrl.php         ← modificar index() para precargar $certificados

resources/views/
├── public/
│   ├── certificaciones.blade.php         ← NUEVA: vista completa para GET /certificaciones
│   └── partials/
│       └── certificaciones.blade.php     ← reemplazar placeholder (listado reutilizable)
└── errors/
    └── 404.blade.php                     ← página de error amigable (si no existe)
```

---

## 1. Rutas — `routes/web.php`

Agregar al bloque de rutas públicas, junto a las rutas de proyectos:

```php
// -----------------------------------------------------------------------
// Rutas públicas — Certificaciones (RF25, RF26)
// -----------------------------------------------------------------------

// CU 4.1 — Visualizar listado de certificaciones
Route::get('/certificaciones', [ProyectoController::class, 'certificaciones'])
    ->name('certificaciones.index');

// CU 4.2 — Descargar PDF de un certificado
Route::get('/certificaciones/{id}/descargar', [ProyectoController::class, 'descargarCertificado'])
    ->name('certificaciones.descargar')
    ->where('id', '[0-9]+');
```

---

## 2. `ProyectoController` — Nuevos Métodos

Agregar los siguientes métodos a la clase `ProyectoController` existente en `app/Http/Controllers/ProyectoController.php`.

> **Importante:** No reemplazar la clase completa. Añadir los métodos `certificaciones()` y `descargarCertificado()` debajo de los métodos `buscar()` y `detalle()` ya especificados en `06_galeria_proyectos.md`.

```php
<?php

namespace App\Http\Controllers;

// Añadir estos use al bloque existente si no están presentes:
use App\Models\Certificado;
use Illuminate\Http\Response;
use Illuminate\View\View;

// =========================================================================
// Los métodos buscar() y detalle() ya existen según 06_galeria_proyectos.md
// Agregar a continuación:
// =========================================================================

    // =========================================================================
    // CU 4.1 — Visualizando Certificaciones (RF25)
    // =========================================================================

    /**
     * Retorna el listado público de certificados activos con metadatos.
     *
     * CRÍTICO PARA PERFORMANCE: Se usa select() explícito para excluir la
     * columna archivo_pdf (BYTEA) del listado. Traer binarios de todos los
     * certificados en el listado dispararía un consumo de memoria inaceptable.
     * El BYTEA solo se carga en descargarCertificado() donde se necesita.
     *
     * @return View
     */
    public function certificaciones(): View
    {
        // ------------------------------------------------------------------
        // a) Consultar certificados activos con metadatos únicamente.
        //    El router excluye archivo_pdf (BYTEA) del SELECT y precarga
        //    el proyecto (id, nombre_obra, region) para evitar N+1.
        // ------------------------------------------------------------------
        $certificados = $this->db->listarCertificadosActivos();

        // ------------------------------------------------------------------
        // b) Formatear fecha_emision a d/m/Y para la vista
        // ------------------------------------------------------------------
        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision
                ? $cert->fecha_emision->format('d/m/Y')
                : '—';
            return $cert;
        });

        // ------------------------------------------------------------------
        // c) Retornar la página COMPLETA con layout público
        //
        //    ⚠️  IMPORTANTE: NO retornar view('public.partials.certificaciones')
        //    directamente — eso renderizaría solo el partial sin navbar,
        //    sidebar ni layout, resultando en una página visualmente rota.
        //
        //    Se retorna public.certificaciones (vista completa) que extiende
        //    layouts.public e incluye el partial internamente.
        //    Ver archivo resources/views/public/certificaciones.blade.php
        //    definido en la sección 3b de este documento.
        // ------------------------------------------------------------------
        return view('public.certificaciones', compact('certificados'));
    }

    // =========================================================================
    // CU 4.2 — Descargando Certificados (RF26)
    // =========================================================================

    /**
     * Descarga el archivo PDF de un certificado almacenado en BYTEA.
     *
     * Esta ruta SÍ carga el BYTEA completo — es su única responsabilidad.
     * El archivo se sirve como attachment para forzar la descarga en el navegador.
     *
     * @param  int  $id  id_certificado
     * @return Response
     */
    public function descargarCertificado(int $id): Response
    {
        // ------------------------------------------------------------------
        // a) Buscar certificado — esta vez SÍ incluimos archivo_pdf.
        //    El router selecciona columnas específicas (id, codigo_lote,
        //    archivo_pdf, estado) en lugar de un select * innecesario.
        // ------------------------------------------------------------------
        $certificado = $this->db->buscarCertificadoParaDescarga($id);

        // ------------------------------------------------------------------
        // b) No existe → 404
        // ------------------------------------------------------------------
        if (! $certificado) {
            abort(404, 'El certificado solicitado no existe.');
        }

        // ------------------------------------------------------------------
        // c) Verificar que el binario exista en la BD
        //    BYTEA de PostgreSQL llega como PHP resource stream.
        //    Convertir a string binario antes de operar.
        // ------------------------------------------------------------------
        $rawPdf = $certificado->getRawOriginal('archivo_pdf');

        if ($rawPdf === null) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        // Convertir stream PostgreSQL a string binario
        $binary = is_resource($rawPdf) ? stream_get_contents($rawPdf) : $rawPdf;

        if (! $binary || strlen($binary) === 0) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        // ------------------------------------------------------------------
        // d) Nombre de archivo seguro para el header Content-Disposition
        //    Sanitizar codigo_lote para evitar caracteres inválidos en nombres
        //    de archivo Windows/macOS/Linux.
        // ------------------------------------------------------------------
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $certificado->codigo_lote)
            . '.pdf';

        // ------------------------------------------------------------------
        // e) Retornar respuesta de descarga
        // ------------------------------------------------------------------
        return response($binary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->header('Content-Length', (string) strlen($binary))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
```

---

## 3. Vista Blade — `certificaciones.blade.php`

**Archivo:** `resources/views/public/partials/certificaciones.blade.php`

Esta vista recibe la colección `$certificados` desde el controlador. Es incluida directamente desde `public/index.blade.php` vía `@include('public.partials.certificaciones')` según `05_navegacion_publica.md`, **pero** requiere que los datos se pasen al partial. Ver sección 3a para la actualización necesaria en `index.blade.php`.

```blade
{{--
    Sección Certificaciones Pública — Ingecon (RF25, RF26)
    CU 4.1: Listado de certificados activos con metadatos
    CU 4.2: Descarga directa de PDF vía enlace

    Variables recibidas:
      $certificados  \Illuminate\Database\Eloquent\Collection<Certificado>
                     Con relación proyecto cargada (eager loaded).
                     Incluye propiedad fecha_formateada (string d/m/Y).
--}}

{{-- ====================================================================
     CABECERA DE SECCIÓN
     ==================================================================== --}}
<div class="text-center mb-10">
    <h2
        id="certificaciones-titulo"
        class="text-3xl font-bold text-slate-900 mb-3"
    >
        Certificaciones
    </h2>
    <p class="text-slate-600 text-lg max-w-xl mx-auto">
        Documentación de calidad y cumplimiento normativo de nuestras obras ejecutadas.
    </p>
</div>

{{-- ====================================================================
     ESTADO VACÍO — Sin certificados activos
     ==================================================================== --}}
@if($certificados->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <svg
            class="w-14 h-14 text-slate-300 mb-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.25"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                     1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0
                     12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                     1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0
                     1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <p class="text-slate-500 font-medium text-base mb-1">
            No hay certificaciones disponibles por el momento
        </p>
        <p class="text-slate-400 text-sm max-w-xs">
            Los certificados técnicos de nuestros proyectos aparecerán aquí cuando estén disponibles.
        </p>
    </div>

@else

{{-- ====================================================================
     GRILLA DE TARJETAS DE CERTIFICADOS
     ==================================================================== --}}
<div
    class="grid grid-cols-1 md:grid-cols-2 gap-4"
    role="list"
    aria-label="Listado de certificados disponibles para descarga"
>

    @foreach($certificados as $cert)
        <article
            class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm
                   hover:shadow-md transition-shadow duration-200
                   flex items-center justify-between gap-4"
            role="listitem"
        >

            {{-- ============================================================
                 LADO IZQUIERDO — Metadatos del certificado
                 ============================================================ --}}
            <div class="flex items-start gap-4 min-w-0">

                {{-- Ícono PDF --}}
                <div class="shrink-0 w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center"
                     aria-hidden="true">
                    <svg
                        class="w-6 h-6 text-red-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.75"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                                 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25
                                 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125
                                 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0
                                 00-9-9z"/>
                    </svg>
                </div>

                {{-- Datos textuales --}}
                <div class="min-w-0">

                    {{-- Código de lote --}}
                    <p class="font-bold text-slate-900 text-sm truncate"
                       title="{{ $cert->codigo_lote }}">
                        {{ $cert->codigo_lote }}
                    </p>

                    {{-- Nombre del proyecto (relación eager loaded) --}}
                    @if($cert->proyecto)
                        <p class="text-slate-500 text-xs mt-0.5 truncate"
                           title="{{ $cert->proyecto->nombre_obra }}">
                            {{ $cert->proyecto->nombre_obra }}
                        </p>
                        @if($cert->proyecto->region)
                            <p class="text-slate-400 text-xs truncate">
                                {{ $cert->proyecto->region }}
                            </p>
                        @endif
                    @endif

                    {{-- Fecha de emisión formateada --}}
                    <div class="flex items-center gap-1 mt-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0"
                             xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0
                                     012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18
                                     0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021
                                     18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25
                                     2.25 0 0121 11.25v7.5"/>
                        </svg>
                        <time
                            datetime="{{ $cert->fecha_emision?->format('Y-m-d') }}"
                            class="text-xs text-slate-400"
                        >
                            {{ $cert->fecha_formateada }}
                        </time>
                    </div>

                </div>
            </div>

            {{-- ============================================================
                 LADO DERECHO — Botón de descarga (RF26 — CU 4.2)
                 ============================================================ --}}
            <div class="shrink-0">
                <a
                    href="{{ route('certificaciones.descargar', $cert->id_certificado) }}"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700
                           active:bg-blue-800 text-white text-sm font-semibold
                           px-4 py-2 rounded-lg transition-colors duration-150
                           focus:outline-none focus-visible:ring-2
                           focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    download
                    aria-label="Descargar certificado {{ $cert->codigo_lote }} en PDF"
                >
                    {{-- Ícono descarga --}}
                    <svg
                        class="w-4 h-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0
                                 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5
                                 4.5V3"/>
                    </svg>
                    Descargar
                </a>
            </div>

        </article>
    @endforeach

</div>

@endif
```

---

## 3b. Vista Completa — `public/certificaciones.blade.php`

**Archivo:** `resources/views/public/certificaciones.blade.php`

Esta vista es retornada por `ProyectoController@certificaciones` cuando el visitante accede directamente a `/certificaciones`. Extiende el layout público completo (navbar, sidebar, modal de login) e incluye el partial del listado.

```blade
{{--
    Página completa de Certificaciones — ruta GET /certificaciones
    Retornada por ProyectoController@certificaciones (RF25, RF26)

    Diferencia con public/index.blade.php:
    - index.blade.php: página principal con TODAS las secciones (scroll one-page)
    - Esta vista: página independiente enfocada solo en certificaciones
      útil para compartir el enlace directo o acceder desde buscadores.

    Variable recibida:
      $certificados  Collection<Certificado>  (eager loaded con proyecto)
--}}

@extends('layouts.public')

@section('title', 'Certificaciones Técnicas — Ingecon')

@section('content')

<main class="min-h-screen pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-5xl mx-auto">

        {{-- Breadcrumb de navegación --}}
        <nav class="mb-8 text-sm text-slate-500" aria-label="Ruta de navegación">
            <ol class="flex items-center gap-2">
                <li>
                    <a href="/" class="hover:text-slate-700 transition-colors">Inicio</a>
                </li>
                <li aria-hidden="true">
                    <svg class="w-4 h-4 text-slate-300" xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </li>
                <li>
                    <span class="text-slate-900 font-medium" aria-current="page">Certificaciones</span>
                </li>
            </ol>
        </nav>

        {{-- Incluir el partial del listado — $certificados se hereda del scope --}}
        @include('public.partials.certificaciones')

        {{-- Enlace de regreso al inicio --}}
        <div class="mt-12 text-center">
            <a href="/#inicio"
               class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700
                      text-sm transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al inicio
            </a>
        </div>

    </div>
</main>

@endsection
```

> **Nota:** `public/index.blade.php` (definido en `05_navegacion_publica.md`) sigue usando `@include('public.partials.certificaciones')` directamente, heredando `$certificados` desde `InstitucionalCtrl@index`. Esta vista separada es adicional, no reemplaza ese flujo.

---

La sección `#certificaciones` en `public/index.blade.php` usa `@include`, que hereda variables del scope de la vista padre. Como el partial necesita `$certificados`, el controlador `InstitucionalCtrl@index` debe precargar los datos.

**Modificar `InstitucionalCtrl@index`** en `app/Http/Controllers/InstitucionalCtrl.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\View\View;

class InstitucionalCtrl extends Controller
{
    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Resuelto automáticamente por el contenedor de Laravel.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Renderiza la página principal pública de Ingecon.
     * Precarga certificados activos para la sección #certificaciones.
     * Los proyectos se cargan vía Alpine.js (06_galeria_proyectos.md).
     *
     * @return View
     */
    public function index(): View
    {
        // Mismo origen de datos que ProyectoController@certificaciones:
        // el router centraliza la query (resuelve la duplicación DRY).
        $certificados = $this->db->listarCertificadosActivos();

        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision?->format('d/m/Y') ?? '—';
            return $cert;
        });

        return view('public.index', compact('certificados'));
    }
}
```

> **Nota:** La ruta `GET /certificaciones` (`ProyectoController@certificaciones`) es una URL independiente coexistente — útil para compartir o enlazar directamente la sección de certificaciones.

---

## 4. Página de Error 404 Amigable

**Archivo:** `resources/views/errors/404.blade.php`

Laravel usa automáticamente esta vista para todas las respuestas `abort(404)` si el archivo existe.

```blade
@extends('layouts.public')

@section('title', 'Página no encontrada — Ingecon')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">

        <p class="text-8xl font-black text-slate-200 leading-none mb-6"
           aria-hidden="true">404</p>

        <div class="flex justify-center mb-6" aria-hidden="true">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                             1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0
                             12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                             1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0
                             1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-3">
            Recurso no encontrado
        </h1>

        <p class="text-slate-500 text-base mb-8 leading-relaxed">
            {{--
                En producción (APP_ENV=production) mostrar mensaje genérico.
                En desarrollo mostrar el mensaje real del abort() para facilitar debug.
                Evita exponer mensajes internos de Laravel a usuarios finales.
            --}}
            @if(app()->isProduction())
                La página o archivo que buscas no existe o ya no está disponible.
            @else
                {{ $exception->getMessage() ?: 'La página o archivo que buscas no existe o ya no está disponible.' }}
            @endif
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/"
               class="px-6 py-2.5 bg-slate-900 hover:bg-slate-700 text-white
                      font-semibold text-sm rounded-xl transition-colors">
                Volver al inicio
            </a>
            <a href="javascript:history.back()"
               class="px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-700
                      font-semibold text-sm rounded-xl border border-slate-200
                      transition-colors">
                Página anterior
            </a>
        </div>

    </div>
</div>
@endsection
```

---

## 5. Prevención de N+1 — Resumen de Estrategia

### Columnas seleccionadas por método

| Método | Columnas `certificado` | Columnas `proyecto` | `archivo_pdf` |
|---|---|---|---|
| `certificaciones()` | id, codigo_lote, fecha_emision, estado, id_proyecto | id_proyecto, nombre_obra, region | ❌ Excluido |
| `index()` en InstitucionalCtrl | id, codigo_lote, fecha_emision, estado, id_proyecto | id_proyecto, nombre_obra, region | ❌ Excluido |
| `descargarCertificado()` | id, codigo_lote, **archivo_pdf**, estado | — | ✅ Incluido |

### Queries generadas (con optimización)

```
GET /certificaciones o GET /:
  Query 1: SELECT id_certificado, codigo_lote, fecha_emision, estado, id_proyecto
           FROM certificado WHERE estado = 'activo' ORDER BY fecha_emision DESC;
  Query 2: SELECT id_proyecto, nombre_obra, region
           FROM proyecto WHERE id_proyecto IN (...todos los ids...);
  Total: 2 queries fijas, sin importar N de certificados. Sin BYTEA en memoria.

GET /certificaciones/{id}/descargar:
  Query 1: SELECT id_certificado, codigo_lote, archivo_pdf, estado
           FROM certificado WHERE id_certificado = ?;
  Total: 1 query, BYTEA cargado solo para ese certificado específico.
```

---

## 6. Notas de Implementación

### BYTEA como stream en PostgreSQL

El driver `pgsql` de PHP devuelve columnas `BYTEA` como resource streams, no strings. En `descargarCertificado()`:

```php
$rawPdf = $certificado->getRawOriginal('archivo_pdf');
$binary = is_resource($rawPdf) ? stream_get_contents($rawPdf) : $rawPdf;
```

Pasar `$rawPdf` directamente a `response()` sin convertir puede provocar comportamiento indefinido.

### Sanitización del nombre de archivo

`preg_replace('/[^a-zA-Z0-9\-_]/', '_', $codigo_lote)` elimina caracteres inválidos en nombres de archivo en Windows, macOS y Linux, previniendo también vulnerabilidades de path traversal en el header `Content-Disposition`.

### `Content-Length` y binarios PHP

`strlen($binary)` es seguro para strings binarios en PHP 8.x con `mbstring.func_overload = 0` (configuración por defecto). Si el servidor tiene `mbstring.func_overload` activo, usar `mb_strlen($binary, '8bit')` como alternativa.

### `Cache-Control: private, no-store`

Los PDFs de certificados son documentos potencialmente sensibles. `no-store` previene almacenamiento en proxies o CDNs intermediarios. `private` restringe el caché al cliente final únicamente.

### Método privado DRY (ya resuelto por el router)

La duplicación de la query entre `certificaciones()` e `index()` ya **no existe**: ambas llaman a `DBRouterController::listarCertificadosActivos()` (`02b_dbrouter_controller.md`), que centraliza el `select` sin BYTEA, el `where('estado','activo')`, el eager-load del proyecto y el orden. Cada controlador aplica únicamente el formateo de presentación (`fecha_formateada`). No se requiere un método privado adicional.
