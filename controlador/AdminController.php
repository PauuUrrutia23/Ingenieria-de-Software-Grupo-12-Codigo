<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
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

        $proyectos = Proyecto::where('id_admin', $admin->id_admin)
            ->with(['imagenesProyecto' => fn($q) => $q->orderBy('id_imagen')->limit(1)])
            ->withCount('imagenesProyecto')
            ->orderBy('id_proyecto', 'desc')
            ->get();

        $resultado = $proyectos->map(function (Proyecto $p) {
            $thumbnail = null;

            $primeraImagen = $p->imagenesProyecto->first();
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
                'cantidad_imagenes'  => $p->imagenes_proyecto_count,
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
        $proyecto = Proyecto::create([
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

            $imagen = ImagenProyecto::create([
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
                'cantidad_imagenes'  => $proyecto->imagenesProyecto()->count(),
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
        $proyecto = Proyecto::find($id);

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
        $cantActual   = $proyecto->imagenesProyecto()->count();
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
        $proyecto->save();

        // ------------------------------------------------------------------
        // d) Eliminar imágenes marcadas — solo las del proyecto correcto
        // ------------------------------------------------------------------
        if (! empty($idsEliminar)) {
            ImagenProyecto::whereIn('id_imagen', $idsEliminar)
                ->where('id_proyecto', $proyecto->id_proyecto)
                ->delete();
        }

        // ------------------------------------------------------------------
        // e) Agregar imágenes nuevas
        // ------------------------------------------------------------------
        foreach ($nuevasFotos as $foto) {
            if (! $foto->isValid()) continue;

            $binary = file_get_contents($foto->getRealPath());
            if ($binary === false) continue;

            ImagenProyecto::create([
                'imagen'         => $binary,
                'nombre_archivo' => $foto->getClientOriginalName(),
                'tipo_mime'      => $foto->getMimeType(),
                'id_proyecto'    => $proyecto->id_proyecto,
            ]);
        }

        // ------------------------------------------------------------------
        // f) Recargar thumbnail actualizado
        // ------------------------------------------------------------------
        $primeraImagen = $proyecto->imagenesProyecto()->orderBy('id_imagen')->first();
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
                'cantidad_imagenes'   => $proyecto->imagenesProyecto()->count(),
                'thumbnail_base64'    => $thumbnail,
            ],
        ]);
    }

    // =========================================================================
    // CU 7.3 — Módulo de Colaboradores (RF46)
    // =========================================================================

    /**
     * Lista todos los colaboradores del administrador autenticado.
     * Genera el Data URI del logotipo para cada colaborador.
     *
     * NOTA: El campo tipo_mime se agregó en la migración adicional
     * 2024_01_01_000010_add_tipo_mime_to_colaborador_table.php.
     * Si es null, se usa 'image/png' como valor por defecto.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexColaboradores(Request $request): JsonResponse
    {
        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        $colaboradores = Colaborador::where('id_admin', $admin->id_admin)
            ->select([
                'id_colaborador',
                'nombre_comercial',
                'logotipo',
                'tipo_mime_logotipo',
            ])
            ->orderBy('nombre_comercial', 'asc')
            ->get();

        $resultado = $colaboradores->map(function (Colaborador $c) {
            $logotipoBase64 = null;

            $raw = $c->getRawOriginal('logotipo');

            if ($raw !== null) {
                // PostgreSQL BYTEA llega como resource stream — convertir a string
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                if ($binary) {
                    // Usar tipo_mime almacenado o fallback a image/png
                    $mime           = $c->tipo_mime_logotipo ?: 'image/png';
                    $logotipoBase64 = "data:{$mime};base64," . base64_encode($binary);
                }
            }

            return [
                'id_colaborador'  => $c->id_colaborador,
                'nombre_comercial'=> $c->nombre_comercial,
                'logotipo_base64' => $logotipoBase64,
            ];
        });

        return response()->json($resultado->values());
    }

    /**
     * Registra un nuevo colaborador con su logotipo almacenado como BYTEA.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeColaborador(Request $request): JsonResponse
    {
        // ------------------------------------------------------------------
        // Validación
        // ------------------------------------------------------------------
        try {
            $validated = $request->validate([
                'nombre_comercial' => ['required', 'string', 'max:120'],
                'logotipo'         => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,svg,webp',
                    'max:2048',
                ],
            ], [
                'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
                'nombre_comercial.max'      => 'El nombre no puede superar los 120 caracteres.',
                'logotipo.required'         => 'El logotipo es obligatorio.',
                'logotipo.mimes'            => 'El logotipo debe ser una imagen JPG, PNG, SVG o WebP.',
                'logotipo.max'              => 'El logotipo no puede superar los 2 MB.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        $archivo = $request->file('logotipo');

        // ------------------------------------------------------------------
        // a) Leer contenido binario del logotipo
        // ------------------------------------------------------------------
        $binary = file_get_contents($archivo->getRealPath());

        if ($binary === false) {
            return response()->json([
                'success' => false,
                'errors'  => [
                    'logotipo' => ['No se pudo procesar el archivo. Intenta nuevamente.'],
                ],
            ], 500);
        }

        // ------------------------------------------------------------------
        // b) Obtener tipo MIME real del archivo (no solo la extensión)
        // ------------------------------------------------------------------
        $tipoMime = $archivo->getMimeType();

        // ------------------------------------------------------------------
        // c) Crear el colaborador con logotipo en BYTEA
        // ------------------------------------------------------------------
        $colaborador = Colaborador::create([
            'nombre_comercial'    => $validated['nombre_comercial'],
            'logotipo'            => $binary,
            'tipo_mime_logotipo'  => $tipoMime,
            'id_admin'            => $admin->id_admin,
        ]);

        // ------------------------------------------------------------------
        // d) Generar Data URI para la respuesta inmediata en Alpine
        // ------------------------------------------------------------------
        $logotipoBase64 = "data:{$tipoMime};base64," . base64_encode($binary);

        // ------------------------------------------------------------------
        // e) Retornar datos del colaborador creado
        // ------------------------------------------------------------------
        return response()->json([
            'success' => true,
            'colaborador' => [
                'id_colaborador'  => $colaborador->id_colaborador,
                'nombre_comercial'=> $colaborador->nombre_comercial,
                'logotipo_base64' => $logotipoBase64,
            ],
        ], 201);
    }
}
