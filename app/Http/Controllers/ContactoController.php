<?php

namespace App\Http\Controllers;

use App\Models\ArchivoAdjunto;
use App\Models\Consulta;
use App\Models\Visitante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContactoController extends Controller
{
    /**
     * Tamaño máximo del adjunto en bytes (10 MB).
     */
    private const MAX_ADJUNTO_BYTES = 10 * 1024 * 1024;

    /**
     * Magic bytes que identifican un archivo PDF válido.
     * Todo PDF comienza con la firma "%PDF".
     */
    private const PDF_MAGIC_BYTES = '%PDF';

    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Resuelto automáticamente por el contenedor de Laravel.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    // =========================================================================
    // CU 1.1 / CU 1.2 — Enviando consulta / Ingresando datos (RF01, RF02)
    // =========================================================================

    /**
     * Recibe, valida y persiste una consulta de contacto pública.
     * Siempre retorna JSON para ser consumido por fetch() en el frontend Alpine.js.
     *
     * Campos esperados (multipart/form-data por FormData):
     *   nombre          string, max 80
     *   apellido        string, max 80
     *   email           string email, max 150
     *   mensaje         string, min 10
     *   fecha_consulta  date, >= hoy
     *   adjunto         file PDF, opcional, max 10 MB
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // ------------------------------------------------------------------
        // a) Validación server-side (RF07 — errores de validación)
        //    Laravel retorna automáticamente 422 con $errors si falla,
        //    pero aquí lo capturamos para devolver JSON consistente.
        // ------------------------------------------------------------------
        try {
            $validated = $request->validate([
                'nombre'          => ['required', 'string', 'max:80'],
                'apellido'        => ['required', 'string', 'max:80'],
                'email'           => ['required', 'email', 'max:150'],
                'mensaje'         => ['required', 'string', 'min:10'],
                'fecha_consulta'  => ['required', 'date', 'after_or_equal:today'],
                'adjunto'         => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            ], [
                // Mensajes de error personalizados en español (RF07)
                'nombre.required'         => 'El campo Nombre es obligatorio.',
                'nombre.max'              => 'El nombre no puede superar los 80 caracteres.',
                'apellido.required'       => 'El campo Apellido es obligatorio.',
                'apellido.max'            => 'El apellido no puede superar los 80 caracteres.',
                'email.required'          => 'El campo Email es obligatorio.',
                'email.email'             => 'Ingresa un correo electrónico válido.',
                'email.max'               => 'El correo no puede superar los 150 caracteres.',
                'mensaje.required'        => 'El campo Mensaje es obligatorio.',
                'mensaje.min'             => 'El mensaje debe tener al menos 10 caracteres.',
                'fecha_consulta.required' => 'El campo Fecha es obligatorio.',
                'fecha_consulta.date'     => 'La fecha ingresada no es válida.',
                'fecha_consulta.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
                'adjunto.file'            => 'El adjunto debe ser un archivo válido.',
                'adjunto.mimes'           => 'Solo se permiten archivos en formato PDF.',
                'adjunto.max'             => 'El archivo no puede superar los 10 MB.',
            ]);
        } catch (ValidationException $e) {
            // CU 1.7 — Mostrar errores de validación al visitante
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        // ------------------------------------------------------------------
        // b) Buscar Visitante por email; crear si no existe (RF01)
        //    firstOrCreate evita duplicados de visitantes para el mismo email.
        // ------------------------------------------------------------------
        $visitante = $this->db->obtenerOCrearVisitante(
            $validated['email'],
            [
                'nombre'   => $validated['nombre'],
                'apellido' => $validated['apellido'],
            ]
        );

        // ------------------------------------------------------------------
        // c) Crear registro CONSULTA
        //    estado='pendiente', prioridad='media' por defecto.
        //    id_admin_responsable es nullable (columna corregida en migración):
        //    queda NULL hasta que un administrador tome la consulta.
        // ------------------------------------------------------------------
        $consulta = $this->db->crearConsulta([
            'mensaje'              => $validated['mensaje'],
            'fecha_consulta'       => $validated['fecha_consulta'],
            'estado'               => 'pendiente',
            'prioridad'            => 'media',
            'id_visitante'         => $visitante->id_visitante,
            'id_admin_responsable' => null,
        ]);

        // ------------------------------------------------------------------
        // d) Procesar adjunto PDF si fue enviado
        // ------------------------------------------------------------------
        if ($request->hasFile('adjunto') && $request->file('adjunto')->isValid()) {
            $archivo = $request->file('adjunto');
            $rutaTemporal = $archivo->getRealPath();

            // Verificar magic bytes: los primeros 4 bytes deben ser "%PDF"
            $primerosBytesRaw = file_get_contents($rutaTemporal, false, null, 0, 4);

            if ($primerosBytesRaw === false || $primerosBytesRaw !== self::PDF_MAGIC_BYTES) {
                // El archivo tiene extensión .pdf pero no es un PDF real
                Log::warning('Adjunto rechazado: magic bytes inválidos', [
                    'nombre_archivo'  => $archivo->getClientOriginalName(),
                    'bytes_detectados' => bin2hex($primerosBytesRaw ?: ''),
                    'id_consulta'     => $consulta->id_consulta,
                ]);

                // Eliminar la consulta ya creada para mantener consistencia
                $this->db->eliminarConsulta($consulta);

                return response()->json([
                    'success' => false,
                    'errors'  => [
                        'adjunto' => ['Solo se permiten archivos en formato PDF.'],
                    ],
                ], 422);
            }

            // Leer contenido binario completo del archivo para almacenar en BYTEA
            $contenidoBinario = file_get_contents($rutaTemporal);

            if ($contenidoBinario === false) {
                Log::error('Error al leer el archivo adjunto', [
                    'ruta'       => $rutaTemporal,
                    'id_consulta' => $consulta->id_consulta,
                ]);

                $this->db->eliminarConsulta($consulta);

                return response()->json([
                    'success' => false,
                    'errors'  => [
                        'adjunto' => ['Ocurrió un error al procesar el archivo. Por favor intenta nuevamente.'],
                    ],
                ], 500);
            }

            // Crear registro ARCHIVO_ADJUNTO vinculado a la consulta
            $this->db->crearArchivoAdjunto([
                'archivo_pdf'    => $contenidoBinario,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime'      => 'application/pdf',
                'id_consulta'    => $consulta->id_consulta,
            ]);
        }

        // ------------------------------------------------------------------
        // e) Respuesta de éxito
        // ------------------------------------------------------------------
        return response()->json([
            'success' => true,
            'mensaje' => 'Consulta registrada correctamente. Nos pondremos en contacto a la brevedad.',
        ], 201);
    }
}
