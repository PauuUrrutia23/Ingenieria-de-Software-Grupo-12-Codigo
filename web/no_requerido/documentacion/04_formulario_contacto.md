# 04 — Formulario de Contacto Público
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa del formulario de contacto público: ruta, controlador, validaciones backend/frontend y vista Blade con Alpine.js.  
> **Requerimientos cubiertos:** RF01, RF02, RF07 / CU 1.1, CU 1.2, CU 1.7.  
> **Convención de modelos:** `Visitante`, `Consulta`, `ArchivoAdjunto` según `02_modelos_eloquent.md`.
> **Acceso a datos:** `ContactoController` recibe `DBRouterController` (`02b_dbrouter_controller.md`) por constructor y delega en él toda lectura/escritura. No hay llamadas directas a Eloquent en este archivo.

---

## Estructura de Archivos a Crear

```
app/
└── Http/
    └── Controllers/
        └── ContactoController.php

resources/views/
└── public/
    └── partials/
        └── contacto.blade.php
```

---

## 1. Ruta — `routes/web.php`

Agregar la siguiente ruta al bloque de rutas públicas existente:

```php
use App\Http\Controllers\ContactoController;

// -----------------------------------------------------------------------
// Ruta pública — Formulario de contacto
// No requiere autenticación.
// -----------------------------------------------------------------------
Route::post('/contacto', [ContactoController::class, 'store'])
    ->name('contacto.store');
```

---

## 2. `ContactoController` — Método `store()`

**Archivo:** `app/Http/Controllers/ContactoController.php`

```php
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
```

---

## 3. Vista Blade con Alpine.js

**Archivo:** `resources/views/public/partials/contacto.blade.php`

Este partial se incluye en la página pública con `@include('public.partials.contacto')`.

```blade
{{--
    Formulario de Contacto Público — Ingecon
    Inclusión: @include('public.partials.contacto')
    Requiere: meta csrf-token en el <head> del layout
              Alpine.js cargado en el layout
--}}

<section
    id="contacto"
    x-data="formularioContacto()"
    class="w-full"
>
    {{-- ================================================================
         Mensaje de éxito — visible tras envío correcto
         ================================================================ --}}
    <div
        x-show="exito"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-emerald-50 border border-emerald-200 rounded-xl px-6 py-5 flex items-start gap-4"
        role="alert"
        aria-live="polite"
    >
        <svg class="w-6 h-6 text-emerald-500 shrink-0 mt-0.5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="font-semibold text-emerald-800 text-sm">¡Consulta enviada con éxito!</p>
            <p class="text-emerald-700 text-sm mt-0.5">
                Nos pondremos en contacto contigo a la brevedad al correo ingresado.
            </p>
        </div>
    </div>

    {{-- ================================================================
         Formulario — oculto tras envío exitoso
         ================================================================ --}}
    <div x-show="!exito">

        {{-- Error general del servidor --}}
        <div
            x-show="errorServidor"
            x-cloak
            class="mb-6 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex items-center gap-3"
            role="alert"
        >
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <p class="text-red-700 text-sm" x-text="errorServidor"></p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

            {{-- --------------------------------------------------------
                 Nombre
                 -------------------------------------------------------- --}}
            <div>
                <label for="ctc-nombre"
                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Nombre <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    id="ctc-nombre"
                    type="text"
                    x-model="form.nombre"
                    @input="limpiarError('nombre')"
                    :class="errores.nombre
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400 disabled:bg-slate-100"
                    placeholder="Ej: Juan"
                    maxlength="80"
                    autocomplete="given-name"
                    :disabled="enviando"
                    aria-describedby="ctc-nombre-error"
                >
                <p
                    id="ctc-nombre-error"
                    x-show="errores.nombre"
                    x-cloak
                    x-text="errores.nombre"
                    class="mt-1.5 text-xs text-red-600"
                    role="alert"
                ></p>
            </div>

            {{-- --------------------------------------------------------
                 Apellido
                 -------------------------------------------------------- --}}
            <div>
                <label for="ctc-apellido"
                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Apellido <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    id="ctc-apellido"
                    type="text"
                    x-model="form.apellido"
                    @input="limpiarError('apellido')"
                    :class="errores.apellido
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400 disabled:bg-slate-100"
                    placeholder="Ej: Pérez"
                    maxlength="80"
                    autocomplete="family-name"
                    :disabled="enviando"
                    aria-describedby="ctc-apellido-error"
                >
                <p
                    id="ctc-apellido-error"
                    x-show="errores.apellido"
                    x-cloak
                    x-text="errores.apellido"
                    class="mt-1.5 text-xs text-red-600"
                    role="alert"
                ></p>
            </div>

            {{-- --------------------------------------------------------
                 Email (ocupa columna completa)
                 -------------------------------------------------------- --}}
            <div class="sm:col-span-2">
                <label for="ctc-email"
                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Correo electrónico <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    id="ctc-email"
                    type="email"
                    x-model="form.email"
                    @input="limpiarError('email')"
                    :class="errores.email
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400 disabled:bg-slate-100"
                    placeholder="correo@ejemplo.cl"
                    maxlength="150"
                    autocomplete="email"
                    inputmode="email"
                    :disabled="enviando"
                    aria-describedby="ctc-email-error"
                >
                <p
                    id="ctc-email-error"
                    x-show="errores.email"
                    x-cloak
                    x-text="errores.email"
                    class="mt-1.5 text-xs text-red-600"
                    role="alert"
                ></p>
            </div>

            {{-- --------------------------------------------------------
                 Mensaje (ocupa columna completa)
                 -------------------------------------------------------- --}}
            <div class="sm:col-span-2">
                <label for="ctc-mensaje"
                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Mensaje <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <textarea
                    id="ctc-mensaje"
                    x-model="form.mensaje"
                    @input="limpiarError('mensaje')"
                    :class="errores.mensaje
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400 disabled:bg-slate-100 resize-none"
                    placeholder="Describe tu consulta o proyecto..."
                    rows="4"
                    :disabled="enviando"
                    aria-describedby="ctc-mensaje-error"
                ></textarea>
                <div class="flex justify-between items-center mt-1">
                    <p
                        id="ctc-mensaje-error"
                        x-show="errores.mensaje"
                        x-cloak
                        x-text="errores.mensaje"
                        class="text-xs text-red-600"
                        role="alert"
                    ></p>
                    <p class="text-xs text-slate-400 ml-auto"
                       x-text="`${form.mensaje.length} caracteres`"></p>
                </div>
            </div>

            {{-- --------------------------------------------------------
                 Fecha de consulta
                 -------------------------------------------------------- --}}
            <div>
                <label for="ctc-fecha"
                       class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Fecha de consulta <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input
                    id="ctc-fecha"
                    type="date"
                    x-model="form.fecha"
                    @change="limpiarError('fecha')"
                    :min="hoyISO()"
                    :class="errores.fecha
                        ? 'border-red-400 focus:ring-red-300'
                        : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors disabled:bg-slate-100"
                    :disabled="enviando"
                    aria-describedby="ctc-fecha-error"
                >
                <p
                    id="ctc-fecha-error"
                    x-show="errores.fecha"
                    x-cloak
                    x-text="errores.fecha"
                    class="mt-1.5 text-xs text-red-600"
                    role="alert"
                ></p>
            </div>

            {{-- --------------------------------------------------------
                 Adjunto PDF (opcional)
                 -------------------------------------------------------- --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Adjunto PDF
                    <span class="font-normal text-slate-400">(opcional, máx. 10 MB)</span>
                </label>

                {{-- Zona de carga personalizada --}}
                <label
                    for="ctc-adjunto"
                    :class="[
                        'flex items-center gap-3 px-4 py-2.5 rounded-lg border cursor-pointer transition-colors',
                        errores.adjunto
                            ? 'border-red-400 bg-red-50'
                            : 'border-slate-300 hover:border-slate-400 bg-white',
                        enviando ? 'opacity-60 pointer-events-none' : ''
                    ]"
                >
                    <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                    </svg>
                    <span
                        class="text-sm truncate"
                        :class="form.nombreArchivo ? 'text-slate-800' : 'text-slate-400'"
                        x-text="form.nombreArchivo || 'Seleccionar archivo PDF...'"
                    ></span>
                    {{-- Botón limpiar archivo --}}
                    <button
                        x-show="form.nombreArchivo"
                        x-cloak
                        type="button"
                        @click.prevent="limpiarAdjunto()"
                        class="ml-auto text-slate-400 hover:text-red-500 transition-colors shrink-0"
                        aria-label="Quitar archivo adjunto"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </label>

                {{-- Input file real — oculto visualmente --}}
                <input
                    id="ctc-adjunto"
                    type="file"
                    accept=".pdf,application/pdf"
                    @change="manejarAdjunto($event)"
                    class="sr-only"
                    :disabled="enviando"
                    aria-describedby="ctc-adjunto-error"
                >

                <p
                    id="ctc-adjunto-error"
                    x-show="errores.adjunto"
                    x-cloak
                    x-text="errores.adjunto"
                    class="mt-1.5 text-xs text-red-600"
                    role="alert"
                ></p>
            </div>

        </div>{{-- /grid --}}

        {{-- ----------------------------------------------------------------
             Botón de envío
             ---------------------------------------------------------------- --}}
        <div class="mt-7">
            <button
                type="button"
                @click="enviar()"
                :disabled="enviando"
                class="w-full sm:w-auto px-8 py-3 bg-slate-900 hover:bg-slate-700
                       disabled:bg-slate-400 text-white font-semibold text-sm
                       rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <svg
                    x-show="enviando"
                    x-cloak
                    class="animate-spin w-4 h-4 text-white shrink-0"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="enviando ? 'Enviando consulta...' : 'Enviar consulta'"></span>
            </button>

            <p class="mt-3 text-xs text-slate-400">
                Los campos marcados con
                <span class="text-red-500">*</span> son obligatorios.
            </p>
        </div>

    </div>{{-- /x-show="!exito" --}}

</section>

@once
@push('scripts')
<script>
    function formularioContacto() {
        return {
            // -----------------------------------------------------------------
            // Estado del componente
            // -----------------------------------------------------------------
            form: {
                nombre:       '',
                apellido:     '',
                email:        '',
                mensaje:      '',
                fecha:        '',
                nombreArchivo: '',
            },
            archivoRef: null,        // Referencia al objeto File seleccionado
            errores: {},
            errorServidor: '',
            enviando: false,
            exito: false,

            // -----------------------------------------------------------------
            // Utilidades de fecha
            // -----------------------------------------------------------------

            /**
             * Retorna la fecha de hoy en formato YYYY-MM-DD para el atributo min
             * del input[type=date].
             */
            hoyISO() {
                const hoy = new Date();
                const y   = hoy.getFullYear();
                const m   = String(hoy.getMonth() + 1).padStart(2, '0');
                const d   = String(hoy.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            },

            // -----------------------------------------------------------------
            // Manejo del adjunto
            // -----------------------------------------------------------------

            manejarAdjunto(event) {
                this.limpiarError('adjunto');
                const archivo = event.target.files[0];

                if (! archivo) return;

                // Validación de extensión en frontend
                const nombre = archivo.name.toLowerCase();
                if (! nombre.endsWith('.pdf')) {
                    this.errores.adjunto = 'Solo se permiten archivos en formato PDF.';
                    event.target.value = '';
                    this.archivoRef = null;
                    this.form.nombreArchivo = '';
                    return;
                }

                // Validación de tamaño: < 10 MB
                const maxBytes = 10 * 1024 * 1024;
                if (archivo.size > maxBytes) {
                    this.errores.adjunto = 'El archivo no puede superar los 10 MB.';
                    event.target.value = '';
                    this.archivoRef = null;
                    this.form.nombreArchivo = '';
                    return;
                }

                this.archivoRef         = archivo;
                this.form.nombreArchivo = archivo.name;
            },

            limpiarAdjunto() {
                this.archivoRef         = null;
                this.form.nombreArchivo = '';
                this.errores.adjunto    = '';
                // Resetear el input file nativo
                const input = document.getElementById('ctc-adjunto');
                if (input) input.value = '';
            },

            // -----------------------------------------------------------------
            // Limpieza de errores por campo
            // -----------------------------------------------------------------

            limpiarError(campo) {
                this.errores[campo]  = '';
                this.errorServidor   = '';
            },

            // -----------------------------------------------------------------
            // Validación frontend completa (RF07 — CU 1.7)
            // -----------------------------------------------------------------

            /**
             * Ejecuta todas las validaciones client-side.
             * Retorna true si el formulario es válido, false en caso contrario.
             * Popula this.errores con mensajes descriptivos en español.
             */
            validar() {
                this.errores       = {};
                this.errorServidor = '';
                let valido         = true;

                // -- Nombre --------------------------------------------------
                if (! this.form.nombre.trim()) {
                    this.errores.nombre = 'El campo Nombre es obligatorio.';
                    valido = false;
                } else if (! /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(this.form.nombre.trim())) {
                    this.errores.nombre = 'El nombre solo puede contener letras y espacios.';
                    valido = false;
                } else if (this.form.nombre.trim().length > 80) {
                    this.errores.nombre = 'El nombre no puede superar los 80 caracteres.';
                    valido = false;
                }

                // -- Apellido ------------------------------------------------
                if (! this.form.apellido.trim()) {
                    this.errores.apellido = 'El campo Apellido es obligatorio.';
                    valido = false;
                } else if (! /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(this.form.apellido.trim())) {
                    this.errores.apellido = 'El apellido solo puede contener letras y espacios.';
                    valido = false;
                } else if (this.form.apellido.trim().length > 80) {
                    this.errores.apellido = 'El apellido no puede superar los 80 caracteres.';
                    valido = false;
                }

                // -- Email ---------------------------------------------------
                if (! this.form.email.trim()) {
                    this.errores.email = 'El campo Email es obligatorio.';
                    valido = false;
                } else if (! /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(this.form.email.trim())) {
                    this.errores.email = 'Ingresa un correo electrónico válido.';
                    valido = false;
                } else if (this.form.email.trim().length > 150) {
                    this.errores.email = 'El correo no puede superar los 150 caracteres.';
                    valido = false;
                }

                // -- Mensaje -------------------------------------------------
                if (! this.form.mensaje.trim()) {
                    this.errores.mensaje = 'El campo Mensaje es obligatorio.';
                    valido = false;
                } else if (this.form.mensaje.trim().length < 10) {
                    this.errores.mensaje = 'El mensaje debe tener al menos 10 caracteres.';
                    valido = false;
                }

                // -- Fecha ---------------------------------------------------
                if (! this.form.fecha) {
                    this.errores.fecha = 'El campo Fecha es obligatorio.';
                    valido = false;
                } else {
                    // Comparar fechas en UTC para evitar problemas de zona horaria
                    const hoy      = new Date(this.hoyISO() + 'T00:00:00');
                    const elegida  = new Date(this.form.fecha + 'T00:00:00');
                    if (elegida < hoy) {
                        this.errores.fecha = 'La fecha no puede ser anterior a hoy.';
                        valido = false;
                    }
                }

                // -- Adjunto (si fue seleccionado) ---------------------------
                if (this.archivoRef) {
                    const nombre  = this.archivoRef.name.toLowerCase();
                    const maxBytes = 10 * 1024 * 1024;

                    if (! nombre.endsWith('.pdf')) {
                        this.errores.adjunto = 'Solo se permiten archivos en formato PDF.';
                        valido = false;
                    } else if (this.archivoRef.size > maxBytes) {
                        this.errores.adjunto = 'El archivo no puede superar los 10 MB.';
                        valido = false;
                    }
                }

                return valido;
            },

            // -----------------------------------------------------------------
            // Envío del formulario
            // -----------------------------------------------------------------

            async enviar() {
                if (this.enviando) return;

                // Ejecutar validaciones client-side antes del fetch
                if (! this.validar()) return;

                this.enviando = true;

                // Construir FormData para incluir el archivo binario
                const fd = new FormData();
                fd.append('nombre',          this.form.nombre.trim());
                fd.append('apellido',        this.form.apellido.trim());
                fd.append('email',           this.form.email.trim());
                fd.append('mensaje',         this.form.mensaje.trim());
                fd.append('fecha_consulta',  this.form.fecha);

                if (this.archivoRef) {
                    fd.append('adjunto', this.archivoRef, this.archivoRef.name);
                }

                try {
                    const response = await fetch('/contacto', {
                        method:  'POST',
                        headers: {
                            'Accept':        'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                            // NO incluir Content-Type: el navegador lo establece
                            // automáticamente con el boundary correcto para multipart/form-data
                        },
                        body: fd,
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Éxito: mostrar confirmación y resetear formulario
                        this.exito = true;
                        this.resetear();
                        return;
                    }

                    // Errores de validación del servidor (422)
                    if (data.errors) {
                        // Mapear errores de Laravel al objeto errores Alpine
                        // Laravel retorna { campo: ['mensaje1', 'mensaje2'] }
                        // Solo mostramos el primer mensaje por campo
                        for (const [campo, mensajes] of Object.entries(data.errors)) {
                            const campoMapeado = campo === 'fecha_consulta' ? 'fecha' : campo;
                            this.errores[campoMapeado] = Array.isArray(mensajes)
                                ? mensajes[0]
                                : mensajes;
                        }
                        return;
                    }

                    // Error genérico del servidor sin estructura conocida
                    this.errorServidor = 'Ocurrió un error. Por favor intenta nuevamente.';

                } catch (err) {
                    // Error de red o fetch fallido
                    this.errorServidor = 'Ocurrió un error. Por favor intenta nuevamente.';
                    console.error('Error al enviar consulta:', err);
                } finally {
                    this.enviando = false;
                }
            },

            // -----------------------------------------------------------------
            // Reset completo del formulario
            // -----------------------------------------------------------------

            resetear() {
                this.form = {
                    nombre:        '',
                    apellido:      '',
                    email:         '',
                    mensaje:       '',
                    fecha:         '',
                    nombreArchivo: '',
                };
                this.archivoRef    = null;
                this.errores       = {};
                this.errorServidor = '';
                this.enviando      = false;

                const input = document.getElementById('ctc-adjunto');
                if (input) input.value = '';
            },
        };
    }
</script>
@endpush
@endonce
```

---

## 4. Resumen de Validaciones

### 4.1 Validaciones Frontend (Alpine.js — antes del fetch)

| Campo | Regla | Mensaje de error |
|---|---|---|
| nombre | No vacío | "El campo Nombre es obligatorio." |
| nombre | Regex `/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/` | "El nombre solo puede contener letras y espacios." |
| nombre | max 80 chars | "El nombre no puede superar los 80 caracteres." |
| apellido | No vacío | "El campo Apellido es obligatorio." |
| apellido | Regex letras+espacios | "El apellido solo puede contener letras y espacios." |
| apellido | max 80 chars | "El apellido no puede superar los 80 caracteres." |
| email | No vacío | "El campo Email es obligatorio." |
| email | Regex `/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/` | "Ingresa un correo electrónico válido." |
| email | max 150 chars | "El correo no puede superar los 150 caracteres." |
| mensaje | No vacío | "El campo Mensaje es obligatorio." |
| mensaje | min 10 chars | "El mensaje debe tener al menos 10 caracteres." |
| fecha | No vacía | "El campo Fecha es obligatorio." |
| fecha | >= hoy | "La fecha no puede ser anterior a hoy." |
| adjunto | Extensión `.pdf` | "Solo se permiten archivos en formato PDF." |
| adjunto | Tamaño < 10 MB | "El archivo no puede superar los 10 MB." |
| (red/servidor) | fetch falló | "Ocurrió un error. Por favor intenta nuevamente." |

### 4.2 Validaciones Backend (Laravel — segunda línea de defensa)

| Campo | Reglas Laravel | Mensaje personalizado |
|---|---|---|
| nombre | required, string, max:80 | "El campo Nombre es obligatorio." |
| apellido | required, string, max:80 | "El campo Apellido es obligatorio." |
| email | required, email, max:150 | "Ingresa un correo electrónico válido." |
| mensaje | required, string, min:10 | "El mensaje debe tener al menos 10 caracteres." |
| fecha_consulta | required, date, after_or_equal:today | "La fecha no puede ser anterior a hoy." |
| adjunto | nullable, file, mimes:pdf, max:10240 | "Solo se permiten archivos en formato PDF." |
| adjunto (magic bytes) | Verificación manual `%PDF` | "Solo se permiten archivos en formato PDF." |

---

## 5. Notas de Implementación

### BYTEA y PostgreSQL

`$this->db->crearArchivoAdjunto(['archivo_pdf' => $contenidoBinario, ...])` (que internamente ejecuta `ArchivoAdjunto::create(...)`) persiste el string binario de PHP directamente. Eloquent con el driver `pgsql` convierte automáticamente strings PHP a `BYTEA` de PostgreSQL. No se necesita codificación adicional.

Al leer, el valor retorna como stream PHP (`resource`). Usar `stream_get_contents($model->archivo_pdf)` antes de `base64_encode()`, según lo especificado en `02_modelos_eloquent.md`.

### `firstOrCreate` para Visitantes

`$this->db->obtenerOCrearVisitante($email, [...])` (que internamente ejecuta `Visitante::firstOrCreate(['email' => $email], [...])`) busca primero por email. Si el visitante cambia su nombre entre consultas, el nombre almacenado será el del **primer registro**. Si se requiere actualizar nombre/apellido en visitas posteriores, modificar el cuerpo del método en `DBRouterController` (`02b_dbrouter_controller.md`) por la siguiente lógica:

```php
$visitante = Visitante::where('email', $validated['email'])->first();

if ($visitante) {
    // Actualizar nombre/apellido si cambiaron
    $visitante->update([
        'nombre'   => $validated['nombre'],
        'apellido' => $validated['apellido'],
    ]);
} else {
    $visitante = Visitante::create([
        'email'    => $validated['email'],
        'nombre'   => $validated['nombre'],
        'apellido' => $validated['apellido'],
    ]);
}
```

### Content-Type en el fetch

El header `Content-Type` **no debe incluirse** manualmente en el fetch al usar `FormData`. El navegador lo establece automáticamente con el `boundary` correcto para `multipart/form-data`. Incluirlo manualmente rompe el parsing del archivo en el servidor.

### Mapeo `fecha_consulta` → `fecha`

El campo se llama `fecha_consulta` en la base de datos y en el request Laravel, pero `fecha` en el estado Alpine para brevedad. El controlador recibe `fecha_consulta` desde `FormData`. El mapeo de errores del servidor al estado Alpine se realiza en el bloque:

```javascript
const campoMapeado = campo === 'fecha_consulta' ? 'fecha' : campo;
this.errores[campoMapeado] = mensajes[0];
```
