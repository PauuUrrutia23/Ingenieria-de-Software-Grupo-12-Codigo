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
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Proyectos publicados filtrados opcionalmente por texto libre
     * (nombre_obra, ubicacion_geografica) y/o categoría exacta. Retorna JSON.
     *
     * Query params: texto (string|null), categoria (Habitacional|Industrial|Agrícola|null).
     */
    public function buscar(Request $request): JsonResponse
    {
        $texto     = $request->query('texto', '');
        $categoria = $request->query('categoria', '');

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

        $resultado = $proyectos->map(function (Proyecto $proyecto) {
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

        return response()->json($resultado->values());
    }

    /**
     * Datos completos de un proyecto publicado, con todas sus imágenes en
     * base64 para el modal de detalle.
     */
    public function detalle(int $id): JsonResponse
    {
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

        if (! $proyecto || $proyecto->estado_publicacion !== 'publicado') {
            return response()->json([
                'error' => 'No encontrado',
            ], 404);
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
            'imagenes'             => $imagenes,
        ]);
    }

    /**
     * Página completa de proyectos publicados (sección accesible desde el
     * menú lateral). Renderiza server-side, a diferencia de la galería AJAX.
     */
    public function galeria(): View
    {
        try {
            $proyectos = $this->db->buscarProyectosPublicados('', '');
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los proyectos para la galería', [
                'error' => $e->getMessage(),
            ]);
            return view('public.proyectos-pagina', ['proyectos' => collect()]);
        }

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

    /**
     * Listado público de certificados vigentes con metadatos.
     *
     * Excluye la columna archivo_pdf (BYTEA) del SELECT: traer los binarios
     * de todos los certificados dispararía un consumo de memoria inaceptable.
     * El BYTEA solo se carga al ver o descargar un certificado.
     */
    public function certificaciones(): View
    {
        try {
            $certificados = $this->db->listarCertificadosActivos();
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los certificados activos', [
                'error' => $e->getMessage(),
            ]);
            return view('public.certificaciones', ['certificados' => collect()]);
        }

        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision
                ? $cert->fecha_emision->format('d/m/Y')
                : '—';
            return $cert;
        });

        // Devolver la vista completa (con layout), no el partial suelto.
        return view('public.certificaciones', compact('certificados'));
    }

    /**
     * Muestra el PDF de un certificado inline en el navegador, sin forzar
     * la descarga.
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

    /**
     * Descarga el PDF de un certificado (almacenado en BYTEA) como attachment.
     * Es la única ruta que carga el binario completo.
     */
    public function descargarCertificado(int $id): Response
    {
        try {
            $certificado = $this->db->buscarCertificadoParaDescarga($id);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo recuperar el certificado para descarga', [
                'error'          => $e->getMessage(),
                'id_certificado' => $id,
            ]);
            abort(500, 'La descarga no está disponible temporalmente.');
        }

        if (! $certificado) {
            abort(404, 'El certificado solicitado no existe.');
        }

        // BYTEA de PostgreSQL llega como resource stream.
        $rawPdf = $certificado->getRawOriginal('archivo_pdf');

        if ($rawPdf === null) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        $binary = is_resource($rawPdf) ? stream_get_contents($rawPdf) : $rawPdf;

        if (! $binary || strlen($binary) === 0) {
            abort(404, 'El archivo PDF de este certificado no está disponible.');
        }

        // Sanitizar codigo_lote para el nombre de archivo.
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $certificado->codigo_lote)
            . '.pdf';

        return response($binary)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->header('Content-Length', (string) strlen($binary))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
