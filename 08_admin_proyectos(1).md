# 08 — Módulo de Gestión de Proyectos (Panel Admin)
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa del módulo admin de proyectos: rutas protegidas, controlador con CRUD de proyectos e imágenes BYTEA, vista Blade con modales Alpine.js para crear y editar.  
> **Requerimientos cubiertos:** RF49, RF50 / CU 7.6, CU 7.7.  
> **Dependencias:**
> - Middleware `admin.auth` definido en `03_autenticacion.md`
> - Modelos `Proyecto`, `ImagenProyecto`, `Administrador` según `02_modelos_eloquent.md`
> - **Acceso a datos:** `AdminController` recibe `DBRouterController` (`02b_dbrouter_controller.md`) por constructor y delega en él toda lectura/escritura. No hay llamadas directas a Eloquent en este archivo. (El mismo constructor sirve para los métodos de colaboradores en `09_admin_colaboradores.md`.)
> - El admin autenticado está disponible en `$request->attributes->get('admin')` (inyectado por `AdminAuth`)

---

## Estructura de Archivos a Crear o Modificar

```
app/
└── Http/
    └── Controllers/
        └── AdminController.php           ← crear o extender con nuevos métodos

resources/views/
├── layouts/
│   └── admin.blade.php                   ← layout del panel administrativo
└── admin/
    └── proyectos.blade.php               ← vista del módulo de proyectos
```

---

## 1. Rutas — `routes/web.php`

Agregar dentro del grupo `admin.auth` ya definido en `03_autenticacion.md`:

```php
use App\Http\Controllers\AdminController;

Route::prefix('admin')
    ->middleware('admin.auth')
    ->name('admin.')
    ->group(function () {

        // dashboard ya existe según 03_autenticacion.md

        // ---------------------------------------------------------------
        // Módulo de Proyectos (RF49, RF50)
        // ---------------------------------------------------------------

        // CU 7.6 / CU 7.7 — Listar proyectos del admin (retorna JSON)
        Route::get('/proyectos', [AdminController::class, 'indexProyectos'])
            ->name('proyectos.index');

        // CU 7.6 — Crear nuevo proyecto con imágenes (RF49)
        Route::post('/proyectos', [AdminController::class, 'storeProyecto'])
            ->name('proyectos.store');

        // CU 7.7 — Actualizar proyecto existente (RF50)
        Route::put('/proyectos/{id}', [AdminController::class, 'updateProyecto'])
            ->name('proyectos.update')
            ->where('id', '[0-9]+');

        // Vista HTML del módulo de proyectos
        Route::get('/proyectos/panel', function () {
            return view('admin.proyectos');
        })->name('proyectos.panel');

    });
```

> **Nota:** `GET /admin/proyectos` retorna JSON (consumido por Alpine). `GET /admin/proyectos/panel` retorna la vista HTML. Esto permite que la misma ruta no colisione y la vista cargue los datos dinámicamente vía fetch.

---

## 2. Layout del Panel Admin — `layouts/admin.blade.php`

**Archivo:** `resources/views/layouts/admin.blade.php`

```blade
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Gestión — Ingecon')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-800">

    <div class="min-h-full flex flex-col">

        {{-- Barra superior del panel --}}
        <header class="bg-slate-900 h-14 flex items-center px-6 gap-4 shadow-lg">
            <a href="{{ route('admin.proyectos.panel') }}"
               class="text-white font-bold text-lg tracking-wide">
                INGECON <span class="text-slate-400 font-normal text-sm ml-2">Panel Admin</span>
            </a>
            <div class="ml-auto flex items-center gap-4">
                <span class="text-slate-400 text-sm hidden sm:block">
                    {{ $request->attributes->get('admin')?->correo ?? '' }}
                </span>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-slate-400 hover:text-white text-sm transition-colors">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </header>

        {{-- Contenido principal --}}
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

    </div>

    @stack('scripts')
</body>
</html>
```

---

## 3. `AdminController` — Los Tres Métodos

**Archivo:** `app/Http/Controllers/AdminController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Límite máximo de imágenes por proyecto.
     */
    private const MAX_IMAGENES = 15;

    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Resuelto automáticamente por el contenedor de Laravel. Este mismo
     * constructor cubre también los métodos de colaboradores (09).
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    // =========================================================================
    // Listar proyectos del admin autenticado
    // =========================================================================

    /**
     * Retorna los proyectos del administrador en sesión.
     * Consumido por Alpine.js via fetch GET /admin/proyectos.
     *
     * El admin autenticado está disponible en $request->attributes
     * inyectado por el middleware AdminAuth (03_autenticacion.md).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexProyectos(Request $request): JsonResponse
    {
        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        $proyectos = $this->db->listarProyectosDeAdmin($admin->id_admin);

        $resultado = $proyectos->map(function (Proyecto $p) {
            $thumbnail = null;

            $primeraImagen = $p->imagenes->first();
            if ($primeraImagen) {
                $raw    = $primeraImagen->getRawOriginal('imagen');
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;
                if ($binary) {
                    $mime      = $primeraImagen->tipo_mime ?: 'image/jpeg';
                    $thumbnail = "data:{$mime};base64," . base64_encode($binary);
                }
            }

            return [
                'id_proyecto'        => $p->id_proyecto,
                'nombre_obra'        => $p->nombre_obra,
                'descripcion_tecnica'=> $p->descripcion_tecnica,
                'region'             => $p->region,
                'ubicacion_geografica'=> $p->ubicacion_geografica,
                'anio_ejecucion'     => $p->anio_ejecucion,
                'estado_publicacion' => $p->estado_publicacion,
                'categoria'          => $p->categoria,
                'cantidad_imagenes'  => $p->imagenes_count,
                'thumbnail_base64'   => $thumbnail,
            ];
        });

        return response()->json($resultado->values());
    }

    // =========================================================================
    // CU 7.6 — Registrar nuevo proyecto (RF49)
    // =========================================================================

    /**
     * Crea un nuevo proyecto con sus imágenes iniciales.
     * Estado inicial: 'borrador' (el admin lo publica manualmente después).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeProyecto(Request $request): JsonResponse
    {
        // ------------------------------------------------------------------
        // Validación
        // ------------------------------------------------------------------
        try {
            $validated = $request->validate([
                'nombre_obra'    => ['required', 'string', 'max:150'],
                'fotografias'    => ['required', 'array', 'max:' . self::MAX_IMAGENES],
                'fotografias.*'  => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ], [
                'nombre_obra.required'   => 'El nombre de la obra es obligatorio.',
                'nombre_obra.max'        => 'El nombre no puede superar los 150 caracteres.',
                'fotografias.required'   => 'Debes subir al menos una fotografía.',
                'fotografias.max'        => 'No puedes subir más de 15 fotografías.',
                'fotografias.*.mimes'    => 'Solo se permiten imágenes JPG, PNG o WebP.',
                'fotografias.*.max'      => 'Cada imagen no puede superar los 5 MB.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        // ------------------------------------------------------------------
        // a) Crear el proyecto en estado borrador
        // ------------------------------------------------------------------
        $proyecto = $this->db->crearProyecto([
            'nombre_obra'        => $validated['nombre_obra'],
            'descripcion_tecnica'=> null,
            'region'             => null,
            'ubicacion_geografica'=> null,
            'anio_ejecucion'     => null,
            'estado_publicacion' => 'borrador',
            'categoria'          => null,
            'id_admin'           => $admin->id_admin,
        ]);

        // ------------------------------------------------------------------
        // b) Persistir cada fotografía como BYTEA
        // ------------------------------------------------------------------
        $thumbnail = null;

        foreach ($request->file('fotografias') as $index => $foto) {
            if (! $foto->isValid()) {
                Log::warning('Fotografía inválida omitida', [
                    'nombre'      => $foto->getClientOriginalName(),
                    'id_proyecto' => $proyecto->id_proyecto,
                ]);
                continue;
            }

            $binary = file_get_contents($foto->getRealPath());

            if ($binary === false) {
                Log::error('No se pudo leer la fotografía', [
                    'nombre' => $foto->getClientOriginalName(),
                ]);
                continue;
            }

            $imagen = $this->db->crearImagenProyecto([
                'imagen'         => $binary,
                'nombre_archivo' => $foto->getClientOriginalName(),
                'tipo_mime'      => $foto->getMimeType(),
                'id_proyecto'    => $proyecto->id_proyecto,
            ]);

            // Guardar thumbnail de la primera imagen válida
            if ($index === 0 && $thumbnail === null) {
                $mime      = $foto->getMimeType();
                $thumbnail = "data:{$mime};base64," . base64_encode($binary);
            }
        }

        // ------------------------------------------------------------------
        // c) Retornar datos del proyecto creado
        // ------------------------------------------------------------------
        return response()->json([
            'success' => true,
            'proyecto' => [
                'id_proyecto'        => $proyecto->id_proyecto,
                'nombre_obra'        => $proyecto->nombre_obra,
                'estado_publicacion' => $proyecto->estado_publicacion,
                'categoria'          => $proyecto->categoria,
                'cantidad_imagenes'  => $this->db->contarImagenesDeProyecto($proyecto),
                'thumbnail_base64'   => $thumbnail,
            ],
        ], 201);
    }

    // =========================================================================
    // CU 7.7 — Editar proyecto existente (RF50)
    // =========================================================================

    /**
     * Actualiza los campos de un proyecto y gestiona su inventario de imágenes.
     * Solo el administrador propietario puede editar el proyecto (403 si no).
     *
     * @param  Request  $request
     * @param  int      $id  id_proyecto
     * @return JsonResponse
     */
    public function updateProyecto(Request $request, int $id): JsonResponse
    {
        // ------------------------------------------------------------------
        // Validación
        // ------------------------------------------------------------------
        try {
            $validated = $request->validate([
                'nombre_obra'            => ['required', 'string', 'max:150'],
                'descripcion_tecnica'    => ['nullable', 'string'],
                'region'                 => ['nullable', 'string', 'max:80'],
                'ubicacion_geografica'   => ['nullable', 'string', 'max:150'],
                'anio_ejecucion'         => ['nullable', 'integer', 'min:1900', 'max:2100'],
                'categoria'              => ['nullable', 'in:Habitacional,Industrial,Agrícola'],
                'estado_publicacion'     => ['nullable', 'in:borrador,publicado'],
                'fotografias_nuevas'     => ['nullable', 'array'],
                'fotografias_nuevas.*'   => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'imagenes_eliminar'      => ['nullable', 'array'],
                'imagenes_eliminar.*'    => ['integer'],
            ], [
                'nombre_obra.required'        => 'El nombre de la obra es obligatorio.',
                'categoria.in'                => 'La categoría debe ser Habitacional, Industrial o Agrícola.',
                'fotografias_nuevas.*.mimes'  => 'Solo se permiten imágenes JPG, PNG o WebP.',
                'fotografias_nuevas.*.max'    => 'Cada imagen no puede superar los 5 MB.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        // ------------------------------------------------------------------
        // a) Buscar proyecto y verificar propiedad
        // ------------------------------------------------------------------
        $proyecto = $this->db->buscarProyectoPorId($id);

        if (! $proyecto) {
            return response()->json(['success' => false, 'message' => 'Proyecto no encontrado.'], 404);
        }

        if ($proyecto->id_admin !== $admin->id_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar este proyecto.',
            ], 403);
        }

        // ------------------------------------------------------------------
        // b) Calcular total de imágenes resultante para validar límite
        // ------------------------------------------------------------------
        $idsEliminar  = $validated['imagenes_eliminar'] ?? [];
        $nuevasFotos  = $request->file('fotografias_nuevas') ?? [];
        $cantActual   = $this->db->contarImagenesDeProyecto($proyecto);
        $cantEliminar = count($idsEliminar);
        $cantNuevas   = count($nuevasFotos);
        $totalFinal   = $cantActual - $cantEliminar + $cantNuevas;

        if ($totalFinal > self::MAX_IMAGENES) {
            return response()->json([
                'success' => false,
                'errors'  => [
                    'fotografias_nuevas' => [
                        "El proyecto no puede tener más de " . self::MAX_IMAGENES . " imágenes. "
                        . "Actualmente tendrías {$totalFinal}.",
                    ],
                ],
            ], 422);
        }

        if ($totalFinal < 0) {
            $totalFinal = 0;
        }

        // ------------------------------------------------------------------
        // c) Actualizar campos del proyecto
        // ------------------------------------------------------------------
        $proyecto->fill([
            'nombre_obra'         => $validated['nombre_obra'],
            'descripcion_tecnica' => $validated['descripcion_tecnica'] ?? $proyecto->descripcion_tecnica,
            'region'              => $validated['region'] ?? $proyecto->region,
            'ubicacion_geografica'=> $validated['ubicacion_geografica'] ?? $proyecto->ubicacion_geografica,
            'anio_ejecucion'      => $validated['anio_ejecucion'] ?? $proyecto->anio_ejecucion,
            'categoria'           => $validated['categoria'] ?? $proyecto->categoria,
            'estado_publicacion'  => $validated['estado_publicacion'] ?? $proyecto->estado_publicacion,
        ]);
        $this->db->guardarProyecto($proyecto);

        // ------------------------------------------------------------------
        // d) Eliminar imágenes marcadas — solo las del proyecto correcto
        // ------------------------------------------------------------------
        if (! empty($idsEliminar)) {
            $this->db->eliminarImagenesDeProyecto($idsEliminar, $proyecto->id_proyecto);
        }

        // ------------------------------------------------------------------
        // e) Agregar imágenes nuevas
        // ------------------------------------------------------------------
        foreach ($nuevasFotos as $foto) {
            if (! $foto->isValid()) continue;

            $binary = file_get_contents($foto->getRealPath());
            if ($binary === false) continue;

            $this->db->crearImagenProyecto([
                'imagen'         => $binary,
                'nombre_archivo' => $foto->getClientOriginalName(),
                'tipo_mime'      => $foto->getMimeType(),
                'id_proyecto'    => $proyecto->id_proyecto,
            ]);
        }

        // ------------------------------------------------------------------
        // f) Recargar thumbnail actualizado
        // ------------------------------------------------------------------
        $primeraImagen = $this->db->primeraImagenDeProyecto($proyecto);
        $thumbnail     = null;

        if ($primeraImagen) {
            $raw    = $primeraImagen->getRawOriginal('imagen');
            $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;
            if ($binary) {
                $mime      = $primeraImagen->tipo_mime ?: 'image/jpeg';
                $thumbnail = "data:{$mime};base64," . base64_encode($binary);
            }
        }

        return response()->json([
            'success' => true,
            'proyecto' => [
                'id_proyecto'         => $proyecto->id_proyecto,
                'nombre_obra'         => $proyecto->nombre_obra,
                'descripcion_tecnica' => $proyecto->descripcion_tecnica,
                'region'              => $proyecto->region,
                'ubicacion_geografica'=> $proyecto->ubicacion_geografica,
                'anio_ejecucion'      => $proyecto->anio_ejecucion,
                'estado_publicacion'  => $proyecto->estado_publicacion,
                'categoria'           => $proyecto->categoria,
                'cantidad_imagenes'   => $this->db->contarImagenesDeProyecto($proyecto),
                'thumbnail_base64'    => $thumbnail,
            ],
        ]);
    }
}
```

---

## 4. Vista Blade — `admin/proyectos.blade.php`

**Archivo:** `resources/views/admin/proyectos.blade.php`

```blade
@extends('layouts.admin')

@section('title', 'Proyectos — Panel Ingecon')

@section('content')

<div x-data="adminProyectos()" x-init="cargarProyectos()">

    {{-- ====================================================================
         CABECERA CON BOTÓN "NUEVO PROYECTO"
         ==================================================================== --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Mis Proyectos</h1>
            <p class="text-slate-500 text-sm mt-0.5">
                Gestiona el portafolio de proyectos de Ingecon.
            </p>
        </div>
        <button
            type="button"
            @click="abrirModalCrear()"
            class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700
                   text-white font-semibold text-sm px-5 py-2.5 rounded-xl
                   transition-colors"
        >
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                 aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nuevo Proyecto
        </button>
    </div>

    {{-- ====================================================================
         SPINNER DE CARGA INICIAL
         ==================================================================== --}}
    <div x-show="cargandoLista" x-cloak
         class="flex justify-center items-center py-20">
        <svg class="animate-spin w-8 h-8 text-slate-400"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    </div>

    {{-- ====================================================================
         GRILLA DE TARJETAS DE PROYECTOS
         ==================================================================== --}}
    <div
        x-show="!cargandoLista"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
        role="list"
    >

        <template x-for="p in proyectos" :key="p.id_proyecto">
            <article
                class="bg-white rounded-2xl shadow-sm border border-slate-100
                       overflow-hidden hover:shadow-md transition-shadow"
                role="listitem"
            >
                {{-- Thumbnail --}}
                <div class="w-full h-44 bg-slate-100 overflow-hidden relative">
                    <img
                        x-show="p.thumbnail_base64"
                        :src="p.thumbnail_base64"
                        :alt="`Imagen de ${p.nombre_obra}`"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    >
                    <div x-show="!p.thumbnail_base64"
                         class="w-full h-full flex items-center justify-center text-slate-300"
                         aria-hidden="true">
                        <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159
                                     5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182
                                     0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0
                                     001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5
                                     0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                        </svg>
                    </div>
                    {{-- Badge cantidad imágenes --}}
                    <span class="absolute bottom-2 right-2 bg-black/60 text-white
                                 text-xs px-2 py-0.5 rounded-full"
                          x-text="`${p.cantidad_imagenes} foto${p.cantidad_imagenes !== 1 ? 's' : ''}`"
                          aria-hidden="true">
                    </span>
                </div>

                {{-- Contenido --}}
                <div class="p-5">

                    {{-- Badges estado y categoría --}}
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span
                            class="text-xs font-semibold px-2 py-0.5 rounded-full"
                            :class="{
                                'bg-yellow-100 text-yellow-700': p.estado_publicacion === 'borrador',
                                'bg-green-100 text-green-700':   p.estado_publicacion === 'publicado'
                            }"
                            x-text="p.estado_publicacion === 'publicado' ? 'Publicado' : 'Borrador'"
                        ></span>
                        <span
                            x-show="p.categoria"
                            class="text-xs font-medium px-2 py-0.5 rounded-full
                                   bg-slate-100 text-slate-600"
                            x-text="p.categoria"
                        ></span>
                    </div>

                    {{-- Nombre obra --}}
                    <h3 class="font-bold text-slate-900 text-sm leading-snug mb-1 line-clamp-2"
                        x-text="p.nombre_obra"></h3>

                    {{-- Ubicación --}}
                    <p x-show="p.ubicacion_geografica"
                       class="text-slate-400 text-xs truncate mb-4"
                       x-text="p.ubicacion_geografica"></p>

                    {{-- Botón editar --}}
                    <button
                        type="button"
                        @click="abrirModalEditar(p)"
                        class="w-full flex items-center justify-center gap-2
                               border border-slate-200 hover:border-slate-400
                               text-slate-700 text-sm font-medium py-2 rounded-lg
                               transition-colors hover:bg-slate-50"
                    >
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0
                                     112.652 2.652L10.582 16.07a4.5 4.5 0
                                     01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0
                                     011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                        </svg>
                        Editar Información
                    </button>

                </div>
            </article>
        </template>

        {{-- Estado vacío --}}
        <div
            x-show="proyectos.length === 0 && !cargandoLista"
            x-cloak
            class="col-span-full flex flex-col items-center justify-center py-20 text-center"
        >
            <svg class="w-14 h-14 text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5
                         3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125
                         1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
            </svg>
            <p class="text-slate-500 font-medium mb-1">No tienes proyectos aún</p>
            <p class="text-slate-400 text-sm mb-5">Crea tu primer proyecto usando el botón superior.</p>
            <button type="button" @click="abrirModalCrear()"
                    class="px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold
                           rounded-xl hover:bg-slate-700 transition-colors">
                Crear primer proyecto
            </button>
        </div>

    </div>

    {{-- ====================================================================
         MODAL CREAR PROYECTO (RF49 — CU 7.6)
         ==================================================================== --}}
    <div
        x-show="modalCrear"
        x-cloak
        @keydown.escape.window="cerrarModalCrear()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Nuevo proyecto"
    >
        <div @click="cerrarModalCrear()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-black/60 backdrop-blur-sm"
             aria-hidden="true"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-lg
                    max-h-[90vh] overflow-y-auto">

            {{-- Cabecera modal --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-lg">Nuevo Proyecto</h2>
                <button @click="cerrarModalCrear()"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700
                               hover:bg-slate-100 transition-colors"
                        aria-label="Cerrar">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Cuerpo modal --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Error general --}}
                <div x-show="erroresCrear.general" x-cloak
                     class="bg-red-50 border border-red-200 rounded-lg px-4 py-3
                            text-red-700 text-sm"
                     role="alert"
                     x-text="erroresCrear.general"></div>

                {{-- Nombre obra --}}
                <div>
                    <label for="crear-nombre"
                           class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nombre de la obra <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="crear-nombre"
                        type="text"
                        x-model="formCrear.nombre_obra"
                        @input="erroresCrear.nombre_obra = ''"
                        :class="erroresCrear.nombre_obra
                            ? 'border-red-400 focus:ring-red-300'
                            : 'border-slate-300 focus:ring-slate-300'"
                        class="w-full px-4 py-2.5 rounded-lg border text-sm
                               focus:outline-none focus:ring-2 transition-colors"
                        placeholder="Ej: Construcción Puente Lo Gallardo"
                        maxlength="150"
                        :disabled="enviando"
                    >
                    <p x-show="erroresCrear.nombre_obra" x-cloak
                       x-text="erroresCrear.nombre_obra"
                       class="mt-1.5 text-xs text-red-600" role="alert"></p>
                </div>

                {{-- Fotografías --}}
                <div>
                    <label for="crear-fotos"
                           class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Fotografías <span class="text-red-500">*</span>
                        <span class="font-normal text-slate-400">(máx. 15, hasta 5 MB c/u)</span>
                    </label>

                    <label for="crear-fotos"
                           :class="erroresCrear.fotografias
                               ? 'border-red-400 bg-red-50'
                               : 'border-slate-300 hover:border-slate-400 bg-slate-50'"
                           class="flex flex-col items-center justify-center gap-2
                                  border-2 border-dashed rounded-xl p-6 cursor-pointer
                                  transition-colors text-center">
                        <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0
                                     0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        <span class="text-sm text-slate-500">
                            Haz clic para seleccionar imágenes
                        </span>
                        <span class="text-xs text-slate-400">JPG, PNG, WebP</span>
                    </label>

                    <input
                        id="crear-fotos"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        @change="manejarFotografiasCrear($event)"
                        class="sr-only"
                        :disabled="enviando"
                    >

                    {{-- Contador y lista de archivos --}}
                    <div x-show="formCrear.archivos.length > 0" x-cloak class="mt-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-600"
                                  x-text="`${formCrear.archivos.length} de 15 fotos seleccionadas`">
                            </span>
                            <button type="button"
                                    @click="formCrear.archivos = []; document.getElementById('crear-fotos').value = ''"
                                    class="text-xs text-red-500 hover:text-red-700">
                                Quitar todas
                            </button>
                        </div>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            <template x-for="(archivo, i) in formCrear.archivos" :key="i">
                                <div class="flex items-center justify-between text-xs
                                            bg-slate-50 rounded px-3 py-1.5">
                                    <span class="truncate text-slate-700 max-w-[200px]"
                                          x-text="archivo.name"></span>
                                    <span class="text-slate-400 shrink-0 ml-2"
                                          x-text="`${(archivo.size / 1024 / 1024).toFixed(1)} MB`">
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p x-show="erroresCrear.fotografias" x-cloak
                       x-text="erroresCrear.fotografias"
                       class="mt-1.5 text-xs text-red-600" role="alert"></p>
                </div>

            </div>

            {{-- Footer modal --}}
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button"
                        @click="cerrarModalCrear()"
                        :disabled="enviando"
                        class="px-5 py-2.5 text-sm font-medium text-slate-600
                               border border-slate-200 rounded-lg hover:bg-slate-50
                               transition-colors disabled:opacity-50">
                    Cancelar
                </button>
                <button type="button"
                        @click="submitCrear()"
                        :disabled="enviando"
                        class="flex items-center gap-2 px-5 py-2.5 bg-slate-900
                               hover:bg-slate-700 disabled:bg-slate-400 text-white
                               text-sm font-semibold rounded-lg transition-colors">
                    <svg x-show="enviando" x-cloak
                         class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="enviando ? 'Guardando...' : 'Crear Proyecto'"></span>
                </button>
            </div>

        </div>
    </div>

    {{-- ====================================================================
         MODAL EDITAR PROYECTO (RF50 — CU 7.7)
         ==================================================================== --}}
    <div
        x-show="modalEditar"
        x-cloak
        @keydown.escape.window="cerrarModalEditar()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="proyectoEditando ? `Editar: ${proyectoEditando.nombre_obra}` : 'Editar proyecto'"
    >
        <div @click="cerrarModalEditar()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-black/60 backdrop-blur-sm"
             aria-hidden="true"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-2xl
                    max-h-[90vh] overflow-y-auto">

            <template x-if="proyectoEditando">
                <div>
                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 sticky top-0 bg-white z-10">
                        <h2 class="font-bold text-slate-900 text-lg">Editar Información</h2>
                        <button @click="cerrarModalEditar()"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700
                                       hover:bg-slate-100 transition-colors"
                                aria-label="Cerrar">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5">

                        {{-- Error general --}}
                        <div x-show="erroresEditar.general" x-cloak
                             class="bg-red-50 border border-red-200 rounded-lg px-4 py-3
                                    text-red-700 text-sm"
                             role="alert"
                             x-text="erroresEditar.general"></div>

                        {{-- Grid 2 columnas para campos de texto --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- Nombre obra --}}
                            <div class="sm:col-span-2">
                                <label for="editar-nombre"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Nombre de la obra <span class="text-red-500">*</span>
                                </label>
                                <input id="editar-nombre" type="text"
                                       x-model="formEditar.nombre_obra"
                                       @input="erroresEditar.nombre_obra = ''"
                                       :class="erroresEditar.nombre_obra ? 'border-red-400' : 'border-slate-300'"
                                       class="w-full px-4 py-2.5 rounded-lg border text-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-300"
                                       maxlength="150" :disabled="enviando">
                                <p x-show="erroresEditar.nombre_obra" x-cloak
                                   x-text="erroresEditar.nombre_obra"
                                   class="mt-1 text-xs text-red-600" role="alert"></p>
                            </div>

                            {{-- Región --}}
                            <div>
                                <label for="editar-region"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Región
                                </label>
                                <input id="editar-region" type="text"
                                       x-model="formEditar.region"
                                       class="w-full px-4 py-2.5 rounded-lg border
                                              border-slate-300 text-sm focus:outline-none
                                              focus:ring-2 focus:ring-slate-300"
                                       maxlength="80" :disabled="enviando"
                                       placeholder="Ej: Metropolitana">
                            </div>

                            {{-- Año ejecución --}}
                            <div>
                                <label for="editar-anio"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Año de ejecución
                                </label>
                                <input id="editar-anio" type="number"
                                       x-model="formEditar.anio_ejecucion"
                                       class="w-full px-4 py-2.5 rounded-lg border
                                              border-slate-300 text-sm focus:outline-none
                                              focus:ring-2 focus:ring-slate-300"
                                       min="1900" max="2100" :disabled="enviando"
                                       placeholder="Ej: 2023">
                            </div>

                            {{-- Ubicación geográfica --}}
                            <div class="sm:col-span-2">
                                <label for="editar-ubicacion"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Ubicación geográfica
                                </label>
                                <input id="editar-ubicacion" type="text"
                                       x-model="formEditar.ubicacion_geografica"
                                       class="w-full px-4 py-2.5 rounded-lg border
                                              border-slate-300 text-sm focus:outline-none
                                              focus:ring-2 focus:ring-slate-300"
                                       maxlength="150" :disabled="enviando"
                                       placeholder="Ej: Autopista del Sol, km 45">
                            </div>

                            {{-- Categoría --}}
                            <div>
                                <label for="editar-categoria"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Categoría
                                </label>
                                <select id="editar-categoria"
                                        x-model="formEditar.categoria"
                                        class="w-full px-4 py-2.5 rounded-lg border
                                               border-slate-300 text-sm bg-white
                                               focus:outline-none focus:ring-2
                                               focus:ring-slate-300 cursor-pointer"
                                        :disabled="enviando">
                                    <option value="">Sin categoría</option>
                                    <option value="Habitacional">Habitacional</option>
                                    <option value="Industrial">Industrial</option>
                                    <option value="Agrícola">Agrícola</option>
                                </select>
                            </div>

                            {{-- Estado publicación --}}
                            <div>
                                <label for="editar-estado"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Estado de publicación
                                </label>
                                <select id="editar-estado"
                                        x-model="formEditar.estado_publicacion"
                                        class="w-full px-4 py-2.5 rounded-lg border
                                               border-slate-300 text-sm bg-white
                                               focus:outline-none focus:ring-2
                                               focus:ring-slate-300 cursor-pointer"
                                        :disabled="enviando">
                                    <option value="borrador">Borrador</option>
                                    <option value="publicado">Publicado</option>
                                </select>
                            </div>

                            {{-- Descripción técnica --}}
                            <div class="sm:col-span-2">
                                <label for="editar-descripcion"
                                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Descripción técnica
                                </label>
                                <textarea id="editar-descripcion"
                                          x-model="formEditar.descripcion_tecnica"
                                          class="w-full px-4 py-2.5 rounded-lg border
                                                 border-slate-300 text-sm focus:outline-none
                                                 focus:ring-2 focus:ring-slate-300 resize-none"
                                          rows="3"
                                          :disabled="enviando"
                                          placeholder="Descripción técnica del proyecto...">
                                </textarea>
                            </div>

                        </div>

                        {{-- ================================================
                             GESTIÓN DE IMÁGENES EXISTENTES
                             ================================================ --}}
                        <div x-show="imagenesExistentes.length > 0">
                            <p class="text-sm font-semibold text-slate-700 mb-3">
                                Imágenes actuales
                                <span class="font-normal text-slate-400"
                                      x-text="`(${imagenesExistentes.length} foto${imagenesExistentes.length !== 1 ? 's' : ''})`">
                                </span>
                            </p>
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                <template x-for="img in imagenesExistentes" :key="img.id_imagen">
                                    <div class="relative group">
                                        <img :src="img.src"
                                             :alt="img.nombre_archivo"
                                             class="w-full aspect-square object-cover rounded-lg"
                                             :class="imagenMarcadaParaEliminar(img.id_imagen)
                                                 ? 'opacity-30 ring-2 ring-red-500'
                                                 : ''">
                                        <button
                                            type="button"
                                            @click="toggleEliminarImagen(img.id_imagen)"
                                            :class="imagenMarcadaParaEliminar(img.id_imagen)
                                                ? 'bg-red-500 text-white'
                                                : 'bg-black/50 text-white opacity-0 group-hover:opacity-100'"
                                            class="absolute top-1 right-1 w-6 h-6 rounded-full
                                                   flex items-center justify-center
                                                   transition-all text-xs"
                                            :aria-label="imagenMarcadaParaEliminar(img.id_imagen)
                                                ? 'Desmarcar eliminación'
                                                : 'Marcar para eliminar'"
                                        >
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg"
                                                 fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor" stroke-width="3"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <p x-show="idsEliminar.length > 0" x-cloak
                               class="mt-2 text-xs text-red-600"
                               x-text="`${idsEliminar.length} imagen${idsEliminar.length !== 1 ? 'es' : ''} marcada${idsEliminar.length !== 1 ? 's' : ''} para eliminar`">
                            </p>
                        </div>

                        {{-- ================================================
                             AGREGAR IMÁGENES NUEVAS
                             ================================================ --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Agregar fotografías nuevas
                                <span class="font-normal text-slate-400">(opcional)</span>
                            </label>

                            {{-- Contador total --}}
                            <div class="mb-2">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-slate-500"
                                          x-text="`Total resultante: ${totalImagenesEditar} / 15`">
                                    </span>
                                    <span x-show="totalImagenesEditar > 15" x-cloak
                                          class="text-red-600 font-semibold">
                                        ¡Límite superado!
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all"
                                         :class="totalImagenesEditar > 15
                                             ? 'bg-red-500'
                                             : totalImagenesEditar > 12
                                             ? 'bg-yellow-400'
                                             : 'bg-green-500'"
                                         :style="`width: ${Math.min(totalImagenesEditar / 15 * 100, 100)}%`">
                                    </div>
                                </div>
                            </div>

                            <label for="editar-fotos"
                                   class="flex items-center gap-3 px-4 py-3 rounded-xl
                                          border border-dashed border-slate-300
                                          hover:border-slate-400 bg-slate-50 cursor-pointer
                                          transition-colors">
                                <svg class="w-5 h-5 text-slate-400 shrink-0"
                                     xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor"
                                     stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0
                                             0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                <span class="text-sm text-slate-400"
                                      x-text="formEditar.archivosNuevos.length > 0
                                          ? `${formEditar.archivosNuevos.length} archivo${formEditar.archivosNuevos.length !== 1 ? 's' : ''} seleccionado${formEditar.archivosNuevos.length !== 1 ? 's' : ''}`
                                          : 'Seleccionar imágenes para agregar'">
                                </span>
                            </label>
                            <input
                                id="editar-fotos"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                @change="manejarFotografiasEditar($event)"
                                class="sr-only"
                                :disabled="enviando"
                            >

                            <p x-show="erroresEditar.fotografias_nuevas" x-cloak
                               x-text="erroresEditar.fotografias_nuevas"
                               class="mt-1.5 text-xs text-red-600" role="alert"></p>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 sticky bottom-0 bg-white">
                        <button type="button"
                                @click="cerrarModalEditar()"
                                :disabled="enviando"
                                class="px-5 py-2.5 text-sm font-medium text-slate-600
                                       border border-slate-200 rounded-lg hover:bg-slate-50
                                       transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="submitEditar()"
                                :disabled="enviando || totalImagenesEditar > 15"
                                class="flex items-center gap-2 px-5 py-2.5 bg-slate-900
                                       hover:bg-slate-700 disabled:bg-slate-400 text-white
                                       text-sm font-semibold rounded-lg transition-colors">
                            <svg x-show="enviando" x-cloak
                                 class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="enviando ? 'Guardando...' : 'Guardar Cambios'"></span>
                        </button>
                    </div>

                </div>
            </template>

        </div>
    </div>

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function adminProyectos() {
    return {
        // -----------------------------------------------------------------
        // Estado global
        // -----------------------------------------------------------------
        proyectos:        [],
        cargandoLista:    true,
        enviando:         false,

        // Modal crear
        modalCrear:    false,
        formCrear:     { nombre_obra: '', archivos: [] },
        erroresCrear:  {},

        // Modal editar
        modalEditar:        false,
        proyectoEditando:   null,
        imagenesExistentes: [],   // [{id_imagen, nombre_archivo, src}]
        idsEliminar:        [],   // IDs marcados para eliminar
        formEditar: {
            nombre_obra:          '',
            descripcion_tecnica:  '',
            region:               '',
            ubicacion_geografica: '',
            anio_ejecucion:       '',
            categoria:            '',
            estado_publicacion:   'borrador',
            archivosNuevos:       [],
        },
        erroresEditar: {},

        // -----------------------------------------------------------------
        // Computed: total imágenes resultante en edición
        // -----------------------------------------------------------------
        get totalImagenesEditar() {
            const existentes = this.imagenesExistentes.length;
            const aEliminar  = this.idsEliminar.length;
            const nuevas     = this.formEditar.archivosNuevos.length;
            return existentes - aEliminar + nuevas;
        },

        // -----------------------------------------------------------------
        // Cargar proyectos del admin
        // -----------------------------------------------------------------
        async cargarProyectos() {
            this.cargandoLista = true;
            try {
                const res = await fetch('/admin/proyectos', {
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (res.ok) {
                    this.proyectos = await res.json();
                } else {
                    console.error('Error al cargar proyectos:', res.status);
                }
            } catch (err) {
                console.error('Error de red:', err);
            } finally {
                this.cargandoLista = false;
            }
        },

        // -----------------------------------------------------------------
        // Modal Crear
        // -----------------------------------------------------------------
        abrirModalCrear() {
            this.formCrear    = { nombre_obra: '', archivos: [] };
            this.erroresCrear = {};
            this.modalCrear   = true;
        },

        cerrarModalCrear() {
            if (this.enviando) return;
            this.modalCrear = false;
            document.getElementById('crear-fotos').value = '';
        },

        manejarFotografiasCrear(event) {
            this.erroresCrear.fotografias = '';
            const archivos = Array.from(event.target.files);

            if (archivos.length > 15) {
                this.erroresCrear.fotografias = 'No puedes seleccionar más de 15 fotografías.';
                event.target.value = '';
                return;
            }

            const maxBytes = 5 * 1024 * 1024;
            for (const archivo of archivos) {
                if (archivo.size > maxBytes) {
                    this.erroresCrear.fotografias =
                        `"${archivo.name}" supera el límite de 5 MB por imagen.`;
                    event.target.value = '';
                    return;
                }
                const ext = archivo.name.split('.').pop().toLowerCase();
                if (!['jpg','jpeg','png','webp'].includes(ext)) {
                    this.erroresCrear.fotografias =
                        `"${archivo.name}" no es un formato de imagen válido (JPG, PNG, WebP).`;
                    event.target.value = '';
                    return;
                }
            }

            this.formCrear.archivos = archivos;
        },

        async submitCrear() {
            if (this.enviando) return;

            // Validación frontend
            this.erroresCrear = {};
            let valido = true;

            if (!this.formCrear.nombre_obra.trim()) {
                this.erroresCrear.nombre_obra = 'El nombre de la obra es obligatorio.';
                valido = false;
            } else if (this.formCrear.nombre_obra.trim().length > 150) {
                this.erroresCrear.nombre_obra = 'El nombre no puede superar los 150 caracteres.';
                valido = false;
            }

            if (this.formCrear.archivos.length === 0) {
                this.erroresCrear.fotografias = 'Debes subir al menos una fotografía.';
                valido = false;
            }

            if (!valido) return;

            this.enviando = true;

            const fd = new FormData();
            fd.append('nombre_obra', this.formCrear.nombre_obra.trim());
            this.formCrear.archivos.forEach(archivo => {
                fd.append('fotografias[]', archivo, archivo.name);
            });

            try {
                const res = await fetch('/admin/proyectos', {
                    method: 'POST',
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: fd,
                });

                const data = await res.json();

                if (data.success) {
                    // Agregar el nuevo proyecto al inicio de la lista
                    this.proyectos.unshift(data.proyecto);
                    this.cerrarModalCrear();
                    return;
                }

                if (data.errors) {
                    for (const [campo, msgs] of Object.entries(data.errors)) {
                        const c = campo.replace('fotografias.0', 'fotografias')
                                       .replace(/fotografias\.\d+/, 'fotografias');
                        this.erroresCrear[c] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                } else {
                    this.erroresCrear.general = data.message ?? 'Error al crear el proyecto.';
                }

            } catch (err) {
                this.erroresCrear.general = 'Error de conexión. Intenta nuevamente.';
                console.error(err);
            } finally {
                this.enviando = false;
            }
        },

        // -----------------------------------------------------------------
        // Modal Editar
        // -----------------------------------------------------------------
        async abrirModalEditar(proyecto) {
            this.proyectoEditando = proyecto;
            this.idsEliminar      = [];
            this.erroresEditar    = {};

            this.formEditar = {
                nombre_obra:          proyecto.nombre_obra          ?? '',
                descripcion_tecnica:  proyecto.descripcion_tecnica  ?? '',
                region:               proyecto.region               ?? '',
                ubicacion_geografica: proyecto.ubicacion_geografica ?? '',
                anio_ejecucion:       proyecto.anio_ejecucion       ?? '',
                categoria:            proyecto.categoria            ?? '',
                estado_publicacion:   proyecto.estado_publicacion   ?? 'borrador',
                archivosNuevos:       [],
            };

            // Cargar imágenes existentes desde el endpoint de detalle
            // (reutiliza ProyectoController@detalle de 06_galeria_proyectos.md)
            this.imagenesExistentes = [];
            this.modalEditar        = true;

            try {
                const res = await fetch(`/proyectos/${proyecto.id_proyecto}/detalle`, {
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.imagenesExistentes = data.imagenes ?? [];
                }
            } catch (err) {
                console.error('Error al cargar imágenes del proyecto:', err);
            }
        },

        cerrarModalEditar() {
            if (this.enviando) return;
            this.modalEditar        = false;
            this.proyectoEditando   = null;
            this.imagenesExistentes = [];
            this.idsEliminar        = [];
            document.getElementById('editar-fotos').value = '';
        },

        imagenMarcadaParaEliminar(id) {
            return this.idsEliminar.includes(id);
        },

        toggleEliminarImagen(id) {
            const idx = this.idsEliminar.indexOf(id);
            if (idx === -1) {
                this.idsEliminar.push(id);
            } else {
                this.idsEliminar.splice(idx, 1);
            }
        },

        manejarFotografiasEditar(event) {
            this.erroresEditar.fotografias_nuevas = '';
            const archivos = Array.from(event.target.files);
            const maxBytes = 5 * 1024 * 1024;

            for (const archivo of archivos) {
                if (archivo.size > maxBytes) {
                    this.erroresEditar.fotografias_nuevas =
                        `"${archivo.name}" supera el límite de 5 MB por imagen.`;
                    event.target.value = '';
                    this.formEditar.archivosNuevos = [];
                    return;
                }
                const ext = archivo.name.split('.').pop().toLowerCase();
                if (!['jpg','jpeg','png','webp'].includes(ext)) {
                    this.erroresEditar.fotografias_nuevas =
                        `"${archivo.name}" no es un formato válido (JPG, PNG, WebP).`;
                    event.target.value = '';
                    this.formEditar.archivosNuevos = [];
                    return;
                }
            }

            this.formEditar.archivosNuevos = archivos;
        },

        async submitEditar() {
            if (this.enviando || !this.proyectoEditando) return;

            // Validación frontend
            this.erroresEditar = {};
            let valido = true;

            if (!this.formEditar.nombre_obra.trim()) {
                this.erroresEditar.nombre_obra = 'El nombre de la obra es obligatorio.';
                valido = false;
            }

            if (this.totalImagenesEditar > 15) {
                this.erroresEditar.fotografias_nuevas =
                    `El proyecto no puede tener más de 15 imágenes. Tienes ${this.totalImagenesEditar}.`;
                valido = false;
            }

            if (!valido) return;

            this.enviando = true;

            const fd = new FormData();
            fd.append('_method',              'PUT');
            fd.append('nombre_obra',          this.formEditar.nombre_obra.trim());
            fd.append('descripcion_tecnica',  this.formEditar.descripcion_tecnica ?? '');
            fd.append('region',               this.formEditar.region ?? '');
            fd.append('ubicacion_geografica', this.formEditar.ubicacion_geografica ?? '');
            fd.append('anio_ejecucion',       this.formEditar.anio_ejecucion ?? '');
            fd.append('categoria',            this.formEditar.categoria ?? '');
            fd.append('estado_publicacion',   this.formEditar.estado_publicacion ?? 'borrador');

            this.idsEliminar.forEach(id => fd.append('imagenes_eliminar[]', id));

            this.formEditar.archivosNuevos.forEach(archivo => {
                fd.append('fotografias_nuevas[]', archivo, archivo.name);
            });

            try {
                const res = await fetch(`/admin/proyectos/${this.proyectoEditando.id_proyecto}`, {
                    method: 'POST',   // Laravel procesa _method=PUT via method spoofing
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: fd,
                });

                const data = await res.json();

                if (data.success) {
                    // Actualizar el proyecto en la lista sin recargar todo
                    const idx = this.proyectos.findIndex(
                        p => p.id_proyecto === this.proyectoEditando.id_proyecto
                    );
                    if (idx !== -1) {
                        this.proyectos[idx] = data.proyecto;
                    }
                    this.cerrarModalEditar();
                    return;
                }

                if (data.errors) {
                    for (const [campo, msgs] of Object.entries(data.errors)) {
                        this.erroresEditar[campo] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                } else {
                    this.erroresEditar.general = data.message ?? 'Error al guardar los cambios.';
                }

            } catch (err) {
                this.erroresEditar.general = 'Error de conexión. Intenta nuevamente.';
                console.error(err);
            } finally {
                this.enviando = false;
            }
        },
    };
}
</script>
@endpush
```

---

## 5. Notas de Implementación

### Method Spoofing para PUT con FormData

Los formularios HTML y `FormData` solo soportan `GET` y `POST`. Para que Laravel procese la ruta `PUT /admin/proyectos/{id}`, `submitEditar()` envía `POST` con el campo oculto `_method=PUT` vía `fd.append('_method', 'PUT')`. Laravel detecta este campo y enruta correctamente.

### Imágenes existentes en el modal de edición

El modal editar reutiliza el endpoint `GET /proyectos/{id}/detalle` de `ProyectoController` (especificado en `06_galeria_proyectos.md`) para cargar las imágenes en base64. Esto evita crear un endpoint duplicado y reutiliza la lógica ya probada de conversión BYTEA → base64.

### Actualización reactiva sin recarga

Tras crear o editar, la lista se actualiza localmente sin recargar desde el servidor:
- **Crear:** `this.proyectos.unshift(data.proyecto)` añade al inicio.
- **Editar:** `this.proyectos[idx] = data.proyecto` reemplaza in-place.

Alpine.js detecta el cambio de referencia del array y actualiza el DOM reactivamente.

### `withCount` para cantidad de imágenes

El método `DBRouterController::listarProyectosDeAdmin()` usa internamente `Proyecto::withCount('imagenes')`, que genera la columna `imagenes_count` mediante un subquery SQL eficiente. No requiere cargar las imágenes completas en memoria solo para contar.

### Prevención de eliminación cruzada

En `updateProyecto()`, la eliminación se delega en `$this->db->eliminarImagenesDeProyecto($idsEliminar, $proyecto->id_proyecto)`. Dentro del router, la query siempre incluye `.where('id_proyecto', $idProyecto)`:

```php
ImagenProyecto::whereIn('id_imagen', $idsImagenes)
    ->where('id_proyecto', $idProyecto)
    ->delete();
```

Esto previene que un admin malintencionado envíe IDs de imágenes de proyectos ajenos en el campo `imagenes_eliminar[]`.
