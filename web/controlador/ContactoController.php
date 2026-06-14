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
    private const MAX_ADJUNTO_BYTES = 10 * 1024 * 1024;

    // Todo PDF comienza con la firma "%PDF".
    private const PDF_MAGIC_BYTES = '%PDF';

    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Recibe, valida y persiste una consulta de contacto pública. Retorna JSON.
     *
     * Campos (multipart/form-data): nombre, apellido, email, mensaje,
     * fecha_consulta y un adjunto PDF opcional (máx. 10 MB).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre'          => ['required', 'string', 'max:80'],
                'apellido'        => ['required', 'string', 'max:80'],
                'email'           => ['required', 'email', 'max:150'],
                'mensaje'         => ['required', 'string', 'min:10'],
                'fecha_consulta'  => ['required', 'date', 'after_or_equal:today'],
                'adjunto'         => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            ], [
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
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        // firstOrCreate evita duplicar visitantes con el mismo email.
        $visitante = $this->db->obtenerOCrearVisitante(
            $validated['email'],
            [
                'nombre'   => $validated['nombre'],
                'apellido' => $validated['apellido'],
            ]
        );

        // id_admin_responsable queda NULL hasta que un admin tome la consulta.
        $consulta = $this->db->crearConsulta([
            'mensaje'              => $validated['mensaje'],
            'fecha_consulta'       => $validated['fecha_consulta'],
            'estado'               => 'pendiente',
            'prioridad'            => 'media',
            'id_visitante'         => $visitante->id_visitante,
            'id_admin_responsable' => null,
        ]);

        if ($request->hasFile('adjunto') && $request->file('adjunto')->isValid()) {
            $archivo = $request->file('adjunto');
            $rutaTemporal = $archivo->getRealPath();

            // El archivo puede tener extensión .pdf sin ser un PDF real:
            // verificar los magic bytes.
            $primerosBytesRaw = file_get_contents($rutaTemporal, false, null, 0, 4);

            if ($primerosBytesRaw === false || $primerosBytesRaw !== self::PDF_MAGIC_BYTES) {
                Log::warning('Adjunto rechazado: magic bytes inválidos', [
                    'nombre_archivo'  => $archivo->getClientOriginalName(),
                    'bytes_detectados' => bin2hex($primerosBytesRaw ?: ''),
                    'id_consulta'     => $consulta->id_consulta,
                ]);

                // Compensar la consulta ya creada.
                $this->db->eliminarConsulta($consulta);

                return response()->json([
                    'success' => false,
                    'errors'  => [
                        'adjunto' => ['Solo se permiten archivos en formato PDF.'],
                    ],
                ], 422);
            }

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

            $this->db->crearArchivoAdjunto([
                'archivo_pdf'    => $contenidoBinario,
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_mime'      => 'application/pdf',
                'id_consulta'    => $consulta->id_consulta,
            ]);
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Consulta registrada correctamente. Nos pondremos en contacto a la brevedad.',
        ], 201);
    }
}
