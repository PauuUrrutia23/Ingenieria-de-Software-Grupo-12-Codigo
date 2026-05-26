# 06 — Galería de Proyectos Pública
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa de la galería de proyectos pública: rutas, controlador con filtros ILIKE, vista Blade con Alpine.js, grilla de tarjetas y modal de detalle con carrusel.  
> **Requerimientos cubiertos:** RF20, RF21, RF24 / CU 3.2, CU 3.3, CU 3.6.  
> **Convención de modelos:** `Proyecto`, `ImagenProyecto` según `02_modelos_eloquent.md`.  
> **La sección se incluye** en `resources/views/public/partials/proyectos.blade.php`, que a su vez es llamada desde `public/index.blade.php` según `05_navegacion_publica.md`.

---

## Estructura de Archivos a Crear

```
app/
└── Http/
    └── Controllers/
        └── ProyectoController.php

resources/views/
└── public/
    └── partials/
        └── galeria.blade.php
```

El archivo `resources/views/public/partials/proyectos.blade.php` ya existe como placeholder según `05_navegacion_publica.md` y se reemplaza con un `@include` a la galería.

---

## 1. Rutas — `routes/web.php`

Agregar al bloque de rutas públicas, después de `Route::get('/', ...)`:

```php
use App\Http\Controllers\ProyectoController;

// -----------------------------------------------------------------------
// Rutas públicas — Galería de proyectos
// Sin autenticación. Retornan JSON para consumo por Alpine.js.
// -----------------------------------------------------------------------

// CU 3.2 / CU 3.3 — Búsqueda y filtrado de proyectos (RF20, RF21)
Route::get('/proyectos/buscar', [ProyectoController::class, 'buscar'])
    ->name('proyectos.buscar');

// CU 3.6 — Detalle de proyecto (RF24)
Route::get('/proyectos/{id}/detalle', [ProyectoController::class, 'detalle'])
    ->name('proyectos.detalle')
    ->where('id', '[0-9]+');
```

---

## 2. `ProyectoController` — Métodos `buscar()` y `detalle()`

**Archivo:** `app/Http/Controllers/ProyectoController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    // =========================================================================
    // CU 3.2 / CU 3.3 — Búsqueda y filtrado (RF20, RF21)
    // =========================================================================

    /**
     * Retorna proyectos publicados filtrados opcionalmente por texto libre
     * (nombre_obra, ubicacion_geografica con ILIKE) y/o categoría exacta.
     *
     * Siempre retorna JSON. Consumido por Alpine.js vía fetch().
     *
     * Query params:
     *   texto     string|null  Término de búsqueda libre
     *   categoria string|null  Categoría exacta: Habitacional|Industrial|Agrícola
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function buscar(Request $request): JsonResponse
    {
        // ------------------------------------------------------------------
        // a) Leer parámetros de query — ninguno es obligatorio
        // ------------------------------------------------------------------
        $texto     = $request->query('texto', '');
        $categoria = $request->query('categoria', '');

        // ------------------------------------------------------------------
        // b) Query base: solo proyectos publicados
        // ------------------------------------------------------------------
        $query = Proyecto::where('estado_publicacion', 'publicado');

        // ------------------------------------------------------------------
        // c) Filtro por texto libre (RF20 — CU 3.2)
        //    ILIKE de PostgreSQL: case-insensitive, sin índice full-text.
        //    Busca coincidencia parcial (%texto%) en dos campos con OR.
        // ------------------------------------------------------------------
        if (filled($texto)) {
            $termino = '%' . $texto . '%';
            $query->where(function ($q) use ($termino) {
                $q->whereRaw('nombre_obra ILIKE ?', [$termino])
                  ->orWhereRaw('ubicacion_geografica ILIKE ?', [$termino]);
            });
        }

        // ------------------------------------------------------------------
        // d) Filtro por categoría exacta (RF21 — CU 3.3)
        //    Valores válidos: 'Habitacional', 'Industrial', 'Agrícola'
        // ------------------------------------------------------------------
        if (filled($categoria)) {
            $query->where('categoria', $categoria);
        }

        // ------------------------------------------------------------------
        // e) Eager load de imágenes para thumbnail
        //
        //    ⚠️  IMPORTANTE — Bug conocido de Laravel con limit() en with():
        //    Usar ->limit(1) dentro de with('imagenes') aplica el LIMIT
        //    globalmente a la query SQL, no "1 por proyecto". El resultado
        //    es que TODOS los proyectos comparten la misma única imagen.
        //
        //    Solución: cargar TODAS las imágenes de cada proyecto y tomar
        //    la primera al mapear. El costo de memoria es aceptable para
        //    el volumen esperado en el incremento 1.
        //
        //    Alternativa para escala futura: agregar en el modelo Proyecto
        //    una relación hasOne llamada imagenPrincipal() con orderBy,
        //    que Laravel resuelve correctamente con un subquery.
        // ------------------------------------------------------------------
        $query->with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');  // orden consistente
        }]);

        // Ordenar por año descendente (más reciente primero)
        // nullable: proyectos sin año van al final
        $proyectos = $query->orderByRaw('anio_ejecucion DESC NULLS LAST')->get();

        // ------------------------------------------------------------------
        // f) Mapear colección a JSON serializable
        //    BYTEA de PostgreSQL llega como PHP resource stream → convertir
        // ------------------------------------------------------------------
        $resultado = $proyectos->map(function (Proyecto $proyecto) {
            $thumbnail = null;

            // Relación definida como imagenesProyecto() en 02_modelos_eloquent.md
            // Se toma solo la primera imagen cargada (orderBy id_imagen asc)
            /** @var ImagenProyecto|null $imagen */
            $imagen = $proyecto->imagenesProyecto->first();

            if ($imagen) {
                $raw    = $imagen->getRawOriginal('imagen');
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                if ($binary) {
                    $mime      = $imagen->tipo_mime ?: 'image/jpeg';
                    $thumbnail = "data:{$mime};base64," . base64_encode($binary);
                }
            }

            return [
                'id_proyecto'          => $proyecto->id_proyecto,
                'nombre_obra'          => $proyecto->nombre_obra,
                'descripcion_tecnica'  => $proyecto->descripcion_tecnica,
                'region'               => $proyecto->region,
                'ubicacion_geografica' => $proyecto->ubicacion_geografica,
                'anio_ejecucion'       => $proyecto->anio_ejecucion,
                'categoria'            => $proyecto->categoria,
                'imagen_thumbnail'     => $thumbnail,
            ];
        });

        // ------------------------------------------------------------------
        // g) Retornar array (vacío si sin resultados)
        // ------------------------------------------------------------------
        return response()->json($resultado->values());
    }

    // =========================================================================
    // CU 3.6 — Detalle de proyecto (RF24)
    // =========================================================================

    /**
     * Retorna los datos completos de un proyecto publicado, incluyendo
     * todas sus imágenes en base64 para el modal de detalle.
     *
     * @param  int  $id   id_proyecto
     * @return JsonResponse
     */
    public function detalle(int $id): JsonResponse
    {
        // ------------------------------------------------------------------
        // a) Buscar proyecto con TODAS sus imágenes para el carrusel del modal
        //    Relación: imagenesProyecto() definida en 02_modelos_eloquent.md
        // ------------------------------------------------------------------
        $proyecto = Proyecto::with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');
        }])->find($id);

        // ------------------------------------------------------------------
        // b) No existe o no está publicado → 404
        // ------------------------------------------------------------------
        if (! $proyecto || $proyecto->estado_publicacion !== 'publicado') {
            return response()->json([
                'error' => 'No encontrado',
            ], 404);
        }

        // ------------------------------------------------------------------
        // c) Serializar todas las imágenes como Data URIs base64
        // ------------------------------------------------------------------
        $imagenes = $proyecto->imagenesProyecto->map(function (ImagenProyecto $imagen) {
            $raw    = $imagen->getRawOriginal('imagen');
            $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

            if (! $binary) {
                return null;
            }

            $mime = $imagen->tipo_mime ?: 'image/jpeg';

            return [
                'id_imagen'      => $imagen->id_imagen,
                'nombre_archivo' => $imagen->nombre_archivo,
                'src'            => "data:{$mime};base64," . base64_encode($binary),
            ];
        })->filter()->values();

        return response()->json([
            'id_proyecto'          => $proyecto->id_proyecto,
            'nombre_obra'          => $proyecto->nombre_obra,
            'descripcion_tecnica'  => $proyecto->descripcion_tecnica,
            'region'               => $proyecto->region,
            'ubicacion_geografica' => $proyecto->ubicacion_geografica,
            'anio_ejecucion'       => $proyecto->anio_ejecucion,
            'categoria'            => $proyecto->categoria,
            'imagenes'             => $imagenes,
        ]);
    }
}
```

---

## 3. Actualizar `proyectos.blade.php` para incluir la galería

**Archivo:** `resources/views/public/partials/proyectos.blade.php`  
Reemplazar el contenido placeholder de `05_navegacion_publica.md` completamente:

```blade
{{--
    Sección Proyectos — incluye el componente de galería completo
--}}
@include('public.partials.galeria')
```

---

## 4. Galería de Proyectos — Vista Blade Completa

**Archivo:** `resources/views/public/partials/galeria.blade.php`

```blade
{{--
    Galería de Proyectos Pública — Ingecon (RF20, RF21, RF24)
    CU 3.2: Filtrado por texto (ILIKE en nombre_obra y ubicacion_geografica)
    CU 3.3: Filtrado por categoría
    CU 3.6: Modal de detalle con carrusel de imágenes
--}}

<div
    x-data="galeriaProyectos()"
    x-init="cargarProyectos()"
>

    {{-- ================================================================
         CABECERA DE SECCIÓN
         ================================================================ --}}
    <div class="text-center mb-10">
        <h2
            id="proyectos-titulo"
            class="text-3xl font-bold text-slate-900 mb-3"
        >
            Nuestros Proyectos
        </h2>
        <p class="text-slate-600 text-lg max-w-xl mx-auto">
            Obras ejecutadas con estándares de calidad en todo Chile.
        </p>
    </div>

    {{-- ================================================================
         BARRA DE FILTROS (RF20, RF21)
         ================================================================ --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-8 items-center">

        {{-- Input búsqueda por texto (CU 3.2) --}}
        <div class="relative flex-1 w-full">
            {{-- Ícono lupa --}}
            <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input
                type="search"
                x-model="texto"
                @input.debounce.400ms="cargarProyectos()"
                placeholder="Buscar por nombre o ubicación..."
                class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-300
                       text-sm focus:outline-none focus:ring-2 focus:ring-slate-300
                       placeholder:text-slate-400 bg-white"
                aria-label="Buscar proyectos por nombre o ubicación"
            >
        </div>

        {{-- Select categoría (CU 3.3) --}}
        <div class="relative w-full sm:w-52">
            <select
                x-model="categoria"
                @change="cargarProyectos()"
                class="w-full appearance-none px-4 py-2.5 pr-9 rounded-xl border
                       border-slate-300 text-sm text-slate-700 bg-white
                       focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer"
                aria-label="Filtrar por categoría"
            >
                <option value="">Todas las categorías</option>
                <option value="Habitacional">Habitacional</option>
                <option value="Industrial">Industrial</option>
                <option value="Agrícola">Agrícola</option>
            </select>
            {{-- Flecha select --}}
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                 xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </div>

        {{-- Spinner de carga --}}
        <div
            x-show="cargando"
            x-cloak
            class="flex items-center gap-2 text-slate-500 text-sm shrink-0"
            aria-live="polite"
            aria-label="Cargando proyectos"
        >
            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                 fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Buscando...</span>
        </div>

        {{-- Botón limpiar filtros --}}
        <button
            type="button"
            x-show="texto !== '' || categoria !== ''"
            x-cloak
            @click="texto = ''; categoria = ''; cargarProyectos()"
            class="shrink-0 px-4 py-2.5 rounded-xl border border-slate-300
                   text-sm text-slate-600 hover:bg-slate-50 hover:border-slate-400
                   transition-colors flex items-center gap-1.5"
            aria-label="Limpiar todos los filtros"
        >
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Limpiar
        </button>

    </div>

    {{-- ================================================================
         GRILLA DE TARJETAS DE PROYECTOS
         ================================================================ --}}
    <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        role="list"
        aria-label="Listado de proyectos"
    >
        <template x-for="proyecto in proyectos" :key="proyecto.id_proyecto">

            <article
                @click="abrirDetalle(proyecto.id_proyecto)"
                class="bg-white rounded-2xl shadow-md overflow-hidden cursor-pointer
                       hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5
                       focus-within:ring-2 focus-within:ring-slate-400"
                role="listitem"
                tabindex="0"
                @keydown.enter="abrirDetalle(proyecto.id_proyecto)"
                :aria-label="`Ver detalles de ${proyecto.nombre_obra}`"
            >

                {{-- Imagen thumbnail o placeholder --}}
                <div class="w-full h-48 overflow-hidden bg-slate-100 relative">

                    {{-- Imagen real --}}
                    <img
                        x-show="proyecto.imagen_thumbnail"
                        :src="proyecto.imagen_thumbnail"
                        :alt="`Imagen de ${proyecto.nombre_obra}`"
                        class="w-full h-full object-cover transition-transform
                               duration-300 hover:scale-105"
                        loading="lazy"
                    >

                    {{-- Placeholder sin imagen --}}
                    <div
                        x-show="!proyecto.imagen_thumbnail"
                        class="w-full h-full flex flex-col items-center justify-center
                               bg-slate-100 text-slate-300"
                        aria-hidden="true"
                    >
                        <svg class="w-12 h-12 mb-2" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.25">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182
                                     0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25
                                     0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5
                                     0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5
                                     1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        <span class="text-xs">Sin imagen</span>
                    </div>

                    {{-- Badge año en esquina --}}
                    <span
                        x-show="proyecto.anio_ejecucion"
                        class="absolute top-3 right-3 bg-black/60 text-white
                               text-xs font-semibold px-2.5 py-1 rounded-full
                               backdrop-blur-sm"
                        x-text="proyecto.anio_ejecucion"
                        aria-hidden="true"
                    ></span>

                </div>

                {{-- Contenido de la tarjeta --}}
                <div class="p-5">

                    {{-- Badge de categoría con color semántico --}}
                    <div class="mb-3">
                        <span
                            class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full"
                            :class="{
                                'bg-blue-100 text-blue-700':   proyecto.categoria === 'Habitacional',
                                'bg-orange-100 text-orange-700': proyecto.categoria === 'Industrial',
                                'bg-green-100 text-green-700':  proyecto.categoria === 'Agrícola',
                                'bg-slate-100 text-slate-600':  !['Habitacional','Industrial','Agrícola'].includes(proyecto.categoria)
                            }"
                            x-text="proyecto.categoria || 'Sin categoría'"
                        ></span>
                    </div>

                    {{-- Nombre de la obra --}}
                    <h3
                        class="text-slate-900 font-bold text-base leading-snug mb-2
                               line-clamp-2"
                        x-text="proyecto.nombre_obra"
                    ></h3>

                    {{-- Descripción técnica (truncada) --}}
                    <p
                        class="text-slate-500 text-sm leading-relaxed line-clamp-2 mb-3"
                        x-text="proyecto.descripcion_tecnica"
                    ></p>

                    {{-- Ubicación geográfica con ícono pin --}}
                    <div class="flex items-start gap-1.5 text-slate-400">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5
                                     17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                        <span class="text-xs line-clamp-1"
                              x-text="proyecto.ubicacion_geografica"></span>
                    </div>

                </div>

            </article>

        </template>
    </div>

    {{-- ================================================================
         ESTADO VACÍO — Sin resultados
         ================================================================ --}}
    <div
        x-show="proyectos.length === 0 && !cargando"
        x-cloak
        class="flex flex-col items-center justify-center py-20 text-center"
        role="status"
        aria-live="polite"
    >
        <svg class="w-14 h-14 text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <p class="text-slate-500 font-medium text-base mb-1">
            No se encontraron proyectos
        </p>
        <p class="text-slate-400 text-sm max-w-xs">
            Prueba con otro término de búsqueda o selecciona una categoría diferente.
        </p>
        <button
            type="button"
            @click="texto = ''; categoria = ''; cargarProyectos()"
            class="mt-5 px-5 py-2 text-sm font-medium text-slate-700
                   border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
        >
            Ver todos los proyectos
        </button>
    </div>

    {{-- ================================================================
         MODAL DE DETALLE (RF24 — CU 3.6)
         ================================================================ --}}
    <div
        x-show="modalAbierto"
        x-cloak
        @keydown.escape.window="modalAbierto = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="proyectoSeleccionado ? `Detalles: ${proyectoSeleccionado.nombre_obra}` : 'Detalle de proyecto'"
    >

        {{-- Overlay oscuro --}}
        <div
            @click="modalAbierto = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        {{-- Panel del modal --}}
        <div
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-2xl
                   max-h-[90vh] overflow-y-auto"
        >

            {{-- Contenido cuando hay proyecto seleccionado --}}
            <template x-if="proyectoSeleccionado">
                <div>

                    {{-- ================================================
                         CARRUSEL DE IMÁGENES
                         ================================================ --}}
                    <div
                        x-data="{ indice: 0 }"
                        class="relative w-full bg-slate-900"
                    >
                        {{-- Imagen activa --}}
                        <div class="w-full h-64 overflow-hidden">
                            <template
                                x-if="proyectoSeleccionado.imagenes &&
                                      proyectoSeleccionado.imagenes.length > 0"
                            >
                                <img
                                    :src="proyectoSeleccionado.imagenes[indice].src"
                                    :alt="`Imagen ${indice + 1} de ${proyectoSeleccionado.nombre_obra}`"
                                    class="w-full h-64 object-cover"
                                >
                            </template>
                            <template
                                x-if="!proyectoSeleccionado.imagenes ||
                                      proyectoSeleccionado.imagenes.length === 0"
                            >
                                <div class="w-full h-64 flex items-center justify-center
                                            bg-slate-800 text-slate-500">
                                    <svg class="w-16 h-16" xmlns="http://www.w3.org/2000/svg"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182
                                                 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25
                                                 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5
                                                 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5
                                                 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>

                        {{-- Botón Anterior --}}
                        <button
                            type="button"
                            x-show="proyectoSeleccionado.imagenes &&
                                    proyectoSeleccionado.imagenes.length > 1"
                            @click="indice = (indice - 1 + proyectoSeleccionado.imagenes.length)
                                           % proyectoSeleccionado.imagenes.length"
                            class="absolute left-3 top-1/2 -translate-y-1/2 p-2
                                   bg-black/50 hover:bg-black/70 text-white rounded-full
                                   transition-colors backdrop-blur-sm"
                            aria-label="Imagen anterior"
                        >
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                        </button>

                        {{-- Botón Siguiente --}}
                        <button
                            type="button"
                            x-show="proyectoSeleccionado.imagenes &&
                                    proyectoSeleccionado.imagenes.length > 1"
                            @click="indice = (indice + 1) % proyectoSeleccionado.imagenes.length"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-2
                                   bg-black/50 hover:bg-black/70 text-white rounded-full
                                   transition-colors backdrop-blur-sm"
                            aria-label="Imagen siguiente"
                        >
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>

                        {{-- Indicadores de puntos --}}
                        <div
                            x-show="proyectoSeleccionado.imagenes &&
                                    proyectoSeleccionado.imagenes.length > 1"
                            class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5"
                            aria-hidden="true"
                        >
                            <template
                                x-for="(img, i) in proyectoSeleccionado.imagenes"
                                :key="i"
                            >
                                <button
                                    type="button"
                                    @click="indice = i"
                                    :class="i === indice
                                        ? 'bg-white w-5'
                                        : 'bg-white/50 w-2'"
                                    class="h-2 rounded-full transition-all duration-200"
                                    :aria-label="`Ir a imagen ${i + 1}`"
                                ></button>
                            </template>
                        </div>

                        {{-- Botón cerrar modal sobre la imagen --}}
                        <button
                            type="button"
                            @click="modalAbierto = false"
                            class="absolute top-3 right-3 p-1.5 bg-black/50 hover:bg-black/70
                                   text-white rounded-full transition-colors backdrop-blur-sm"
                            aria-label="Cerrar modal"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                    </div>{{-- /carrusel --}}

                    {{-- ================================================
                         INFORMACIÓN DEL PROYECTO (RF24)
                         ================================================ --}}
                    <div class="p-6 sm:p-8">

                        {{-- Badge categoría + año --}}
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span
                                class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                :class="{
                                    'bg-blue-100 text-blue-700':     proyectoSeleccionado.categoria === 'Habitacional',
                                    'bg-orange-100 text-orange-700': proyectoSeleccionado.categoria === 'Industrial',
                                    'bg-green-100 text-green-700':   proyectoSeleccionado.categoria === 'Agrícola',
                                    'bg-slate-100 text-slate-600':   !['Habitacional','Industrial','Agrícola'].includes(proyectoSeleccionado.categoria)
                                }"
                                x-text="proyectoSeleccionado.categoria"
                            ></span>
                            <span
                                x-show="proyectoSeleccionado.anio_ejecucion"
                                class="text-xs text-slate-400 font-medium"
                                x-text="`Año ${proyectoSeleccionado.anio_ejecucion}`"
                            ></span>
                        </div>

                        {{-- Nombre de la obra (H2) --}}
                        <h2
                            class="text-xl sm:text-2xl font-bold text-slate-900
                                   leading-tight mb-4"
                            x-text="proyectoSeleccionado.nombre_obra"
                        ></h2>

                        {{-- Descripción técnica --}}
                        <div class="mb-5">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase
                                       tracking-widest mb-2">
                                Descripción técnica
                            </h3>
                            <p
                                class="text-slate-600 text-sm leading-relaxed"
                                x-text="proyectoSeleccionado.descripcion_tecnica"
                            ></p>
                        </div>

                        {{-- Región y ubicación geográfica --}}
                        <div class="flex flex-col gap-2">

                            <div
                                x-show="proyectoSeleccionado.region"
                                class="flex items-center gap-2 text-slate-500"
                            >
                                <svg class="w-4 h-4 shrink-0 text-slate-400"
                                     xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor"
                                     stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>
                                </svg>
                                <span class="text-sm" x-text="proyectoSeleccionado.region"></span>
                            </div>

                            <div
                                x-show="proyectoSeleccionado.ubicacion_geografica"
                                class="flex items-start gap-2 text-slate-500"
                            >
                                <svg class="w-4 h-4 shrink-0 mt-0.5 text-slate-400"
                                     xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor"
                                     stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5
                                             17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                <span class="text-sm"
                                      x-text="proyectoSeleccionado.ubicacion_geografica"></span>
                            </div>

                        </div>

                    </div>{{-- /información --}}

                </div>
            </template>

            {{-- Spinner mientras carga el detalle --}}
            <template x-if="!proyectoSeleccionado">
                <div class="flex items-center justify-center h-64">
                    <svg class="animate-spin w-8 h-8 text-slate-400"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
            </template>

        </div>{{-- /panel --}}

    </div>{{-- /modal --}}

</div>{{-- /x-data galeriaProyectos --}}

@once
@push('scripts')
<script>
    function galeriaProyectos() {
        return {
            // -----------------------------------------------------------------
            // Estado del componente
            // -----------------------------------------------------------------
            proyectos:           [],
            texto:               '',
            categoria:           '',
            cargando:            false,
            proyectoSeleccionado: null,
            modalAbierto:        false,
            _abortController:    null,   // para cancelar fetch previo en debounce

            // -----------------------------------------------------------------
            // CU 3.2 / CU 3.3 — Cargar proyectos con filtros (RF20, RF21)
            // -----------------------------------------------------------------

            /**
             * Consulta GET /proyectos/buscar con los parámetros actuales.
             * Cancela cualquier fetch anterior pendiente (debounce de input).
             * Actualiza this.proyectos con el resultado.
             */
            async cargarProyectos() {
                // Cancelar petición anterior si aún está pendiente
                if (this._abortController) {
                    this._abortController.abort();
                }
                this._abortController = new AbortController();

                this.cargando = true;

                // Construir query string solo con parámetros no vacíos
                const params = new URLSearchParams();
                if (this.texto.trim())    params.set('texto',     this.texto.trim());
                if (this.categoria.trim()) params.set('categoria', this.categoria.trim());

                const url = '/proyectos/buscar'
                    + (params.toString() ? '?' + params.toString() : '');

                try {
                    const response = await fetch(url, {
                        method:  'GET',
                        headers: {
                            'Accept':        'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        signal: this._abortController.signal,
                    });

                    if (! response.ok) {
                        console.error('Error al cargar proyectos:', response.status);
                        this.proyectos = [];
                        return;
                    }

                    this.proyectos = await response.json();

                } catch (err) {
                    // AbortError ocurre cuando se cancela el fetch anterior — ignorar
                    if (err.name !== 'AbortError') {
                        console.error('Error de red al cargar proyectos:', err);
                        this.proyectos = [];
                    }
                } finally {
                    this.cargando = false;
                }
            },

            // -----------------------------------------------------------------
            // CU 3.6 — Abrir modal de detalle (RF24)
            // -----------------------------------------------------------------

            /**
             * Obtiene los datos completos del proyecto (incluidas todas sus
             * imágenes) y abre el modal de detalle.
             *
             * @param {number} id  id_proyecto
             */
            async abrirDetalle(id) {
                // Abrir modal inmediatamente con spinner (proyectoSeleccionado = null)
                this.proyectoSeleccionado = null;
                this.modalAbierto         = true;

                try {
                    const response = await fetch(`/proyectos/${id}/detalle`, {
                        method:  'GET',
                        headers: {
                            'Accept':        'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });

                    if (response.status === 404) {
                        // Proyecto no encontrado o no publicado — cerrar modal
                        this.modalAbierto = false;
                        console.warn(`Proyecto ${id} no encontrado o no publicado.`);
                        return;
                    }

                    if (! response.ok) {
                        this.modalAbierto = false;
                        console.error('Error al cargar detalle del proyecto:', response.status);
                        return;
                    }

                    const data = await response.json();

                    // Asignar datos — el template x-if renderizará el contenido
                    this.proyectoSeleccionado = data;

                } catch (err) {
                    this.modalAbierto = false;
                    console.error('Error de red al cargar detalle:', err);
                }
            },
        };
    }
</script>
@endpush
@endonce
```

---

## 5. Resumen de Flujos Alpine.js

```
[Montaje del componente]
    x-init="cargarProyectos()"
    → GET /proyectos/buscar (sin parámetros)
    → proyectos = [...todos los publicados]

[Usuario escribe en búsqueda]
    @input.debounce.400ms="cargarProyectos()"
    → AbortController cancela fetch anterior
    → GET /proyectos/buscar?texto=TÉRMINO
    → proyectos = [...filtrados por ILIKE]

[Usuario elige categoría]
    @change="cargarProyectos()"
    → GET /proyectos/buscar?texto=TÉRMINO&categoria=Industrial
    → proyectos = [...filtrados por texto Y categoría]

[Usuario hace click en tarjeta]
    @click="abrirDetalle(proyecto.id_proyecto)"
    → modalAbierto = true  (spinner visible)
    → GET /proyectos/{id}/detalle
    → proyectoSeleccionado = {datos + array imagenes[]}
    → carrusel renderiza imagenes[indice]

[Usuario navega el carrusel]
    @click botón prev/next
    → indice = (indice ± 1 + length) % length
    → img :src cambia reactivamente

[Cierre del modal]
    @click overlay / botón X / tecla Escape
    → modalAbierto = false
```

---

## 6. Notas de Implementación

### Performance con imágenes BYTEA

Cargar múltiples imágenes BYTEA en base64 puede generar respuestas JSON grandes. Para la carga inicial (`buscar`), se cargan todas las imágenes de cada proyecto pero **se serializa solo la primera** como thumbnail en la respuesta. Solo al abrir el modal (`detalle`) se cargan y serializan todas las imágenes de ese proyecto.

> **¿Por qué no `limit(1)` en el eager load de `buscar`?**  
> `->limit(1)` dentro de `with()` en Laravel aplica el límite globalmente a la query SQL, no "1 imagen por proyecto". El resultado es que todos los proyectos comparten la misma imagen. La solución correcta es cargar todas las imágenes y tomar `->first()` al mapear, como está implementado arriba.

Si en el futuro el número de proyectos crece significativamente, considerar paginación en el endpoint `buscar` con `->paginate(12)` y scroll infinito en Alpine.

### `getRawOriginal` vs accessor y nombre de relación

En el controlador se usa `$imagen->getRawOriginal('imagen')` en lugar del accessor `imagenBase64` del modelo para **evitar la doble codificación base64** en el contexto del controlador, donde se construye el Data URI manualmente con el `tipo_mime` correcto.

La relación utilizada es `imagenesProyecto` (no `imagenes`), consistente con el nombre definido en `02_modelos_eloquent.md`. Usar cualquier otro nombre causará `Property [imagenes] does not exist on this collection` en tiempo de ejecución.

### Carrusel con Alpine `x-data` anidado

El carrusel usa `x-data="{ indice: 0 }"` dentro del modal, que es un scope Alpine independiente del scope padre `galeriaProyectos()`. El `indice` se resetea a `0` automáticamente cada vez que `x-if="proyectoSeleccionado"` desmonta y vuelve a montar el DOM al cambiar de proyecto.

### Colores de categoría

Los colores de badge están definidos con clases Tailwind en el binding `:class`. Los valores posibles son exactamente `'Habitacional'`, `'Industrial'` y `'Agrícola'` — deben coincidir con los valores almacenados en la columna `categoria` de la tabla `proyecto`.
