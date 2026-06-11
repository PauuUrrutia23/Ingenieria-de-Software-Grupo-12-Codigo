<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Resuelto automáticamente por el contenedor de Laravel.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

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
        // b) Delegar TODA la construcción de la query al DBRouterController.
        //    El router aplica: estado_publicacion='publicado', filtro ILIKE
        //    de texto (RF20), filtro de categoría (RF21), eager-load de
        //    imagenesProyecto ordenadas y orden por anio_ejecucion DESC.
        //    Devuelve una Collection<Proyecto>; aquí solo se arma el JSON.
        // ------------------------------------------------------------------
        try {
            $proyectos = $this->db->buscarProyectosPublicados($texto, $categoria);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo buscar proyectos publicados', [
                'error'     => $e->getMessage(),
                'texto'     => $texto,
                'categoria' => $categoria,
            ]);
            return response()->json([
                'error'   => true,
                'message' => 'La búsqueda no está disponible temporalmente.',
            ], 500);
        }

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
        //    El router carga la relación imagenesProyecto ordenada.
        // ------------------------------------------------------------------
        try {
            $proyecto = $this->db->buscarProyectoConImagenes($id);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo obtener el detalle del proyecto', [
                'error'       => $e->getMessage(),
                'id_proyecto' => $id,
            ]);
            return response()->json([
                'error'   => 'No disponible',
                'message' => 'El detalle no está disponible temporalmente.',
            ], 500);
        }

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

    // =========================================================================
    // RF12 — Página dedicada de Proyectos (acceso desde el Menú Lateral)
    // =========================================================================

    /**
     * Renderiza la PÁGINA COMPLETA de proyectos publicados.
     *
     * A diferencia de la galería one-page (que carga vía Alpine.js/AJAX a
     * buscar()), esta es una página independiente accesible desde el Menú
     * Lateral (RF12). El contenido se obtiene desde la Base de Datos en el
     * mismo request GET /proyectos a través del DBRouterController, por lo
     * que el renderizado server-side ya consulta las entidades reales.
     *
     * @return View
     */
    public function galeria(): View
    {
        // Sin filtros: lista todos los proyectos publicados (RF20/RF21 viven
        // en la galería interactiva; aquí es el listado completo de la sección).
        try {
            $proyectos = $this->db->buscarProyectosPublicados('', '');
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los proyectos para la galería', [
                'error' => $e->getMessage(),
            ]);
            return view('public.proyectos-pagina', ['proyectos' => collect()]);
        }

        // Serializar la imagen de portada (BYTEA → Data URI) para render directo.
        $listado = $proyectos->map(function (Proyecto $proyecto) {
            $thumbnail = null;
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

            return (object) [
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

        return view('public.proyectos-pagina', ['proyectos' => $listado]);
    }

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
        try {
            $certificados = $this->db->listarCertificadosActivos();
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los certificados activos', [
                'error' => $e->getMessage(),
            ]);
            return view('public.certificaciones', ['certificados' => collect()]);
        }

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
    // CU 4.1 — Visualizando Certificado PDF en el navegador (RF25)
    // =========================================================================

    /**
     * Muestra el archivo PDF de un certificado en el navegador (inline),
     * sin forzar la descarga. Útil para previsualizar antes de descargar.
     *
     * @param  int  $id  id_certificado
     * @return Response
     */
    public function verCertificado(int $id): Response
    {
        try {
            $certificado = $this->db->buscarCertificadoParaDescarga($id);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo recuperar el certificado para visualización', [
                'error'          => $e->getMessage(),
                'id_certificado' => $id,
            ]);
            abort(500, 'La visualización no está disponible temporalmente.');
        }

        if (! $certificado) {
            abort(404, 'El certificado solicitado no existe.');
        }

        $rawPdf = $certificado->getRawOriginal('archivo_pdf');

        if ($rawPdf === null) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        $binary = is_resource($rawPdf) ? stream_get_contents($rawPdf) : $rawPdf;

        if (! $binary || strlen($binary) === 0) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        $nombreArchivo = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $certificado->codigo_lote)
            . '.pdf';

        return response($binary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"')
            ->header('Content-Length', (string) strlen($binary))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
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
        try {
            $certificado = $this->db->buscarCertificadoParaDescarga($id);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo recuperar el certificado para descarga', [
                'error'          => $e->getMessage(),
                'id_certificado' => $id,
            ]);
            abort(500, 'La descarga no está disponible temporalmente.');
        }

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
}
