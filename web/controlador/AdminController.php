<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Database\QueryException;
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

        try {
            $proyectos = $this->db->listarProyectosDeAdmin($admin->id_admin);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo listar los proyectos', [
                'error'   => $e->getMessage(),
                'id_admin'=> $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El listado de proyectos no está disponible temporalmente.',
            ], 500);
        }

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
    // Obtener un proyecto del admin con sus imágenes (cualquier estado)
    // =========================================================================

    /**
     * Retorna un proyecto del administrador en sesión con TODAS sus imágenes,
     * sin importar su estado de publicación. Lo consume el modal de edición.
     *
     * Se agrega porque el endpoint público /proyectos/{id}/detalle solo sirve
     * proyectos en estado "publicado": al editar un borrador no devolvía las
     * imágenes (se contaban pero no se mostraban para eliminar).
     *
     * @param  Request  $request
     * @param  int      $id
     * @return JsonResponse
     */
    public function showProyecto(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\Administrador $admin */
        $admin = $request->attributes->get('admin');

        try {
            $proyecto = $this->db->buscarProyectoConImagenes($id);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo obtener el proyecto para edición', [
                'error'       => $e->getMessage(),
                'id_proyecto' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El proyecto no está disponible temporalmente.',
            ], 500);
        }

        // No existe o no pertenece al admin en sesión → 404
        if (! $proyecto || (int) $proyecto->id_admin !== (int) $admin->id_admin) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

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
            'estado_publicacion'   => $proyecto->estado_publicacion,
            'imagenes'             => $imagenes,
        ]);
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
                'fotografias'    => ['nullable', 'array', 'max:' . self::MAX_IMAGENES],
                'fotografias.*'  => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ], [
                'nombre_obra.required'   => 'El nombre de la obra es obligatorio.',
                'nombre_obra.max'        => 'El nombre no puede superar los 150 caracteres.',
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
        // a) Crear el proyecto en estado borrador (CU 49.1 Exc 3)
        // ------------------------------------------------------------------
        try {
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
        } catch (QueryException $e) {
            Log::error('BD: No se pudo crear el proyecto', [
                'error'   => $e->getMessage(),
                'id_admin'=> $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el proyecto. La base de datos no está disponible temporalmente.',
            ], 500);
        }

        // ------------------------------------------------------------------
        // b) Persistir cada fotografía como BYTEA (CU 49.2 Exc 4)
        //    Si no se suben imágenes, el proyecto se crea igual (quedará
        //    como borrador y no podrá publicarse hasta tener al menos una).
        // ------------------------------------------------------------------
        $thumbnail = null;
        $imagenesGuardadas = 0;

        $archivos = $request->file('fotografias');

        if (! empty($archivos)) {
            foreach ($archivos as $index => $foto) {
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

                try {
                    $this->db->crearImagenProyecto([
                        'imagen'         => $binary,
                        'nombre_archivo' => $foto->getClientOriginalName(),
                        'tipo_mime'      => $foto->getMimeType(),
                        'id_proyecto'    => $proyecto->id_proyecto,
                    ]);
                    $imagenesGuardadas++;
                } catch (QueryException $e) {
                    Log::error('BD: No se pudo almacenar la imagen del proyecto', [
                        'error'       => $e->getMessage(),
                        'id_proyecto' => $proyecto->id_proyecto,
                        'archivo'     => $foto->getClientOriginalName(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo almacenar una de las imágenes. La base de datos no está disponible temporalmente.',
                    ], 500);
                }

                // Guardar thumbnail de la primera imagen válida
                if ($index === 0 && $thumbnail === null) {
                    $mime      = $foto->getMimeType();
                    $thumbnail = "data:{$mime};base64," . base64_encode($binary);
                }
            }

            if ($imagenesGuardadas === 0) {
                $this->db->eliminarProyecto($proyecto);
                return response()->json([
                    'success' => false,
                    'errors'  => ['fotografias' => ['Ninguna de las imágenes seleccionadas pudo ser procesada. Intenta con otros archivos.']],
                ], 422);
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
        // c.1) Validaciones previas a la publicación (CU 49.3 Exc 2, Exc 3)
        // ------------------------------------------------------------------
        $nuevoEstado = $validated['estado_publicacion'] ?? $proyecto->estado_publicacion;

        if ($nuevoEstado === 'publicado') {
            $erroresPublicacion = [];

            $descripcion = $validated['descripcion_tecnica'] ?? $proyecto->descripcion_tecnica;
            $region      = $validated['region'] ?? $proyecto->region;
            $ubicacion   = $validated['ubicacion_geografica'] ?? $proyecto->ubicacion_geografica;
            $categoria   = $validated['categoria'] ?? $proyecto->categoria;

            if (empty(trim($descripcion ?? ''))) {
                $erroresPublicacion['descripcion_tecnica'] = ['La descripción técnica es obligatoria para publicar.'];
            }
            if (empty(trim($region ?? ''))) {
                $erroresPublicacion['region'] = ['La región es obligatoria para publicar.'];
            }
            if (empty(trim($ubicacion ?? ''))) {
                $erroresPublicacion['ubicacion_geografica'] = ['La ubicación geográfica es obligatoria para publicar.'];
            }
            if (empty($categoria)) {
                $erroresPublicacion['categoria'] = ['La categoría es obligatoria para publicar.'];
            }

            if ($totalFinal === 0) {
                $erroresPublicacion['fotografias_nuevas'] = ['El proyecto debe tener al menos una imagen para ser publicado.'];
            }

            if (! empty($erroresPublicacion)) {
                return response()->json([
                    'success' => false,
                    'errors'  => $erroresPublicacion,
                ], 422);
            }
        }

        // ------------------------------------------------------------------
        // c.2) Actualizar campos del proyecto (CU 50.1 Exc 5 / CU 49.3 Exc 4)
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

        try {
            $this->db->guardarProyecto($proyecto);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo actualizar el proyecto', [
                'error'       => $e->getMessage(),
                'id_proyecto' => $proyecto->id_proyecto,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron guardar los cambios. La base de datos no está disponible temporalmente.',
            ], 500);
        }

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

        try {
            $colaboradores = $this->db->listarColaboradoresDeAdmin($admin->id_admin);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo listar los colaboradores', [
                'error'   => $e->getMessage(),
                'id_admin'=> $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'El listado de colaboradores no está disponible temporalmente.',
            ], 500);
        }

        $resultado = $colaboradores->map(function (Colaborador $c) {
            $logotipoBase64 = null;

            $raw = $c->getRawOriginal('logotipo');

            if ($raw !== null) {
                // PostgreSQL BYTEA llega como resource stream — convertir a string
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                if ($binary) {
                    // Usar tipo_mime almacenado o fallback a image/png
                    $mime           = $c->tipo_mime ?: 'image/png';
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
        // c) Crear el colaborador con logotipo en BYTEA (CU 46.1 Exc 3)
        // ------------------------------------------------------------------
        try {
            $colaborador = $this->db->crearColaborador([
                'nombre_comercial' => $validated['nombre_comercial'],
                'logotipo'         => $binary,
                'tipo_mime'        => $tipoMime,
                'id_admin'         => $admin->id_admin,
            ]);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo crear el colaborador', [
                'error'   => $e->getMessage(),
                'id_admin'=> $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'errors'  => [
                    'general' => ['No se pudo registrar el colaborador. La base de datos no está disponible temporalmente.'],
                ],
            ], 500);
        }

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
