 # 02 — Modelos Eloquent
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Definir los 9 modelos Eloquent del sistema con relaciones, casts, scopes y accessors completos.  
> **Ubicación de archivos:** `app/Models/`  
> **Convenciones:**
> - Nombres de tabla en singular snake_case (sin prefijo), coincidiendo exactamente con las migraciones de `01_setup_laravel_migraciones.md`.
> - Primary keys no estándar declaradas explícitamente.
> - `$timestamps = false` en todos los modelos — las tablas no tienen columnas `created_at`/`updated_at`.
> - `$incrementing = true` (por defecto) en todos los modelos.
> - `$keyType = 'int'` (por defecto) en todos los modelos.

---

## Modelo 1 — `Administrador`

**Archivo:** `app/Models/Administrador.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrador extends Model
{
    /**
     * Nombre exacto de la tabla en PostgreSQL.
     */
    protected $table = 'administrador';

    /**
     * Clave primaria no estándar.
     */
    protected $primaryKey = 'id_admin';

    /**
     * Las tablas del sistema no usan timestamps de Eloquent.
     */
    public $timestamps = false;

    /**
     * Campos asignables masivamente.
     * password_hash se excluye: debe asignarse siempre de forma explícita
     * tras pasar por Hash::make().
     */
    protected $fillable = [
        'correo',
        'intentos_fallidos',
        'bloqueado_hasta',
        'activo',
    ];

    /**
     * Campos ocultos en serialización JSON (API responses, toArray()).
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Conversión automática de tipos al leer desde la base de datos.
     */
    protected $casts = [
        'bloqueado_hasta'   => 'datetime',   // Carbon instance
        'activo'            => 'boolean',
        'intentos_fallidos' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un administrador tiene muchas sesiones activas o históricas.
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'id_admin', 'id_admin');
    }

    /**
     * Un administrador puede ser responsable de muchas consultas.
     * FK personalizada: id_admin_responsable en la tabla consulta.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'id_admin_responsable', 'id_admin');
    }

    /**
     * Un administrador gestiona muchos proyectos.
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_admin', 'id_admin');
    }

    /**
     * Un administrador crea muchos colaboradores.
     */
    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'id_admin', 'id_admin');
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    /**
     * Filtra administradores activos y que no estén bloqueados en este momento.
     *
     * Uso: Administrador::activo()->get()
     *
     * Condiciones:
     *   - activo = true
     *   - bloqueado_hasta IS NULL  O  bloqueado_hasta < NOW()
     */
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true)
                     ->where(function (Builder $q) {
                         $q->whereNull('bloqueado_hasta')
                           ->orWhere('bloqueado_hasta', '<', now());
                     });
    }
}
```

---

## Modelo 2 — `Visitante`

**Archivo:** `app/Models/Visitante.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitante extends Model
{
    protected $table = 'visitante';

    protected $primaryKey = 'id_visitante';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un visitante puede enviar muchas consultas.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'id_visitante', 'id_visitante');
    }
}
```

---

## Modelo 3 — `Sesion`

**Archivo:** `app/Models/Sesion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sesion extends Model
{
    protected $table = 'sesion';

    protected $primaryKey = 'id_sesion';

    public $timestamps = false;

    protected $fillable = [
        'token_hash',
        'fecha_inicio',
        'estado',
        'id_admin',
    ];

    /**
     * token_hash nunca debe exponerse en respuestas JSON o arrays.
     */
    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',   // Carbon instance
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Una sesión pertenece a un administrador.
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }
}
```

---

## Modelo 4 — `Consulta`

**Archivo:** `app/Models/Consulta.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consulta extends Model
{
    protected $table = 'consulta';

    protected $primaryKey = 'id_consulta';

    public $timestamps = false;

    protected $fillable = [
        'mensaje',
        'fecha_consulta',
        'estado',
        'prioridad',
        'id_visitante',
        'id_admin_responsable',
    ];

    protected $casts = [
        'fecha_consulta' => 'datetime',   // Carbon instance
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Una consulta fue enviada por un visitante.
     */
    public function visitante(): BelongsTo
    {
        return $this->belongsTo(Visitante::class, 'id_visitante', 'id_visitante');
    }

    /**
     * Una consulta tiene un administrador responsable de atenderla.
     * El segundo argumento es la FK local; el tercero es la PK del modelo padre.
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(
            Administrador::class,
            'id_admin_responsable',  // FK en tabla consulta
            'id_admin'               // PK en tabla administrador
        );
    }

    /**
     * Una consulta puede tener un único archivo adjunto (PDF).
     */
    public function archivoAdjunto(): HasOne
    {
        return $this->hasOne(ArchivoAdjunto::class, 'id_consulta', 'id_consulta');
    }
}
```

---

## Modelo 5 — `ArchivoAdjunto`

**Archivo:** `app/Models/ArchivoAdjunto.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ArchivoAdjunto extends Model
{
    protected $table = 'archivo_adjunto';

    protected $primaryKey = 'id_adjunto';

    public $timestamps = false;

    protected $fillable = [
        'archivo_pdf',
        'nombre_archivo',
        'tipo_mime',
        'id_consulta',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un archivo adjunto pertenece a una consulta.
     */
    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'id_consulta', 'id_consulta');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Retorna el PDF almacenado en BYTEA como cadena base64 lista para embeber
     * en una etiqueta <a> o respuesta de descarga.
     *
     * Uso en Blade:
     *   <a href="data:{{ $archivo->tipo_mime }};base64,{{ $archivo->archivo_pdf_base64 }}"
     *      download="{{ $archivo->nombre_archivo }}">Descargar</a>
     *
     * Nota: PostgreSQL devuelve BYTEA como un resource stream en PHP.
     *       stream_get_contents() convierte el stream a string antes de base64.
     */
    protected function archivoPdfBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['archivo_pdf'] ?? null;

                if ($raw === null) {
                    return null;
                }

                // PDO con pgsql devuelve BYTEA como resource stream
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                return base64_encode($binary);
            }
        );
    }
}
```

---

## Modelo 6 — `Proyecto`

**Archivo:** `app/Models/Proyecto.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'proyecto';

    protected $primaryKey = 'id_proyecto';

    public $timestamps = false;

    protected $fillable = [
        'nombre_obra',
        'descripcion_tecnica',
        'region',
        'ubicacion_geografica',
        'anio_ejecucion',
        'estado_publicacion',
        'categoria',
        'id_admin',
    ];

    protected $casts = [
        'anio_ejecucion' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un proyecto pertenece al administrador que lo gestiona.
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }

    /**
     * Un proyecto tiene muchas imágenes asociadas.
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenProyecto::class, 'id_proyecto', 'id_proyecto');
    }

    /**
     * Un proyecto puede tener muchos certificados de calidad/lote.
     */
    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class, 'id_proyecto', 'id_proyecto');
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    /**
     * Filtra proyectos cuyo estado_publicacion sea 'publicado'.
     *
     * Uso: Proyecto::publicados()->get()
     */
    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('estado_publicacion', 'publicado');
    }

    /**
     * Filtra proyectos por categoría exacta (case-insensitive con ILIKE).
     *
     * Uso: Proyecto::porCategoria('infraestructura')->get()
     *
     * @param string $categoria  Valor de la categoría a filtrar.
     */
    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->whereRaw('categoria ILIKE ?', [$categoria]);
    }

    /**
     * Búsqueda de texto libre en nombre_obra y ubicacion_geografica.
     * Usa ILIKE de PostgreSQL para búsqueda case-insensitive sin índice full-text.
     *
     * Uso: Proyecto::buscarTexto('puente')->get()
     *      Proyecto::publicados()->buscarTexto('santiago')->paginate(10)
     *
     * @param string $texto  Término de búsqueda (se añade % automáticamente).
     */
    public function scopeBuscarTexto(Builder $query, string $texto): Builder
    {
        $termino = '%' . $texto . '%';

        return $query->where(function (Builder $q) use ($termino) {
            $q->whereRaw('nombre_obra ILIKE ?', [$termino])
              ->orWhereRaw('ubicacion_geografica ILIKE ?', [$termino]);
        });
    }
}
```

---

## Modelo 7 — `ImagenProyecto`

**Archivo:** `app/Models/ImagenProyecto.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ImagenProyecto extends Model
{
    protected $table = 'imagen_proyecto';

    protected $primaryKey = 'id_imagen';

    public $timestamps = false;

    protected $fillable = [
        'imagen',
        'nombre_archivo',
        'tipo_mime',
        'id_proyecto',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Una imagen pertenece a un proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Retorna la imagen BYTEA como Data URI lista para usar en <img src="...">.
     *
     * Uso en Blade:
     *   <img src="{{ $imagen->imagen_base64 }}" alt="{{ $imagen->nombre_archivo }}">
     *
     * Retorna null si el campo imagen está vacío, permitiendo que la vista
     * muestre una imagen placeholder en su lugar.
     *
     * Nota: PDO con pgsql devuelve BYTEA como resource stream.
     *       stream_get_contents() convierte el stream a string binario.
     */
    protected function imagenBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['imagen'] ?? null;

                if ($raw === null) {
                    return null;
                }

                $binary  = is_resource($raw) ? stream_get_contents($raw) : $raw;
                $base64  = base64_encode($binary);
                $mime    = $this->attributes['tipo_mime'] ?? 'image/jpeg';

                // Data URI completa lista para src de <img>
                return "data:{$mime};base64,{$base64}";
            }
        );
    }
}
```

---

## Modelo 8 — `Certificado`

**Archivo:** `app/Models/Certificado.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Certificado extends Model
{
    protected $table = 'certificado';

    protected $primaryKey = 'id_certificado';

    public $timestamps = false;

    protected $fillable = [
        'codigo_lote',
        'archivo_pdf',
        'fecha_emision',
        'estado',
        'id_proyecto',
    ];

    protected $casts = [
        'fecha_emision' => 'date',   // Carbon instance sin componente horario
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un certificado pertenece a un proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Retorna el PDF del certificado como cadena base64.
     *
     * Uso en Blade (descarga directa):
     *   <a href="data:application/pdf;base64,{{ $cert->archivo_pdf_base64 }}"
     *      download="certificado-{{ $cert->codigo_lote }}.pdf">Descargar PDF</a>
     *
     * Nota: PDO con pgsql devuelve BYTEA como resource stream.
     */
    protected function archivoPdfBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['archivo_pdf'] ?? null;

                if ($raw === null) {
                    return null;
                }

                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                return base64_encode($binary);
            }
        );
    }
}
```

---

## Modelo 9 — `Colaborador`

**Archivo:** `app/Models/Colaborador.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Colaborador extends Model
{
    protected $table = 'colaborador';

    protected $primaryKey = 'id_colaborador';

    public $timestamps = false;

    protected $fillable = [
        'nombre_comercial',
        'logotipo',
        'tipo_mime_logotipo',  // Necesario para el Data URI correcto en el accessor logotipoBase64
        'id_admin',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un colaborador fue creado/gestionado por un administrador.
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Retorna el logotipo BYTEA como Data URI lista para usar en <img src="...">.
     * Si el logotipo es null retorna null; la vista debe manejar el fallback.
     *
     * Uso en Blade:
     *   @if($colaborador->logotipo_base64)
     *       <img src="{{ $colaborador->logotipo_base64 }}"
     *            alt="Logo {{ $colaborador->nombre_comercial }}">
     *   @else
     *       <img src="/images/logo-placeholder.svg" alt="Sin logotipo">
     *   @endif
     *
     * Nota: PDO con pgsql devuelve BYTEA como resource stream.
     *       El tipo MIME se asume image/png para logotipos; ajustar si se almacena
     *       el tipo MIME en una columna adicional en el futuro.
     */
    protected function logotipoBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['logotipo'] ?? null;

                if ($raw === null) {
                    return null;
                }

                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;
                $base64 = base64_encode($binary);

                // Usar el tipo MIME almacenado en la columna tipo_mime_logotipo.
                // La columna fue agregada en la migración para soportar PNG, JPG, SVG y WebP.
                // Por defecto es 'image/png' si no se especificó al guardar.
                $mime = $this->attributes['tipo_mime_logotipo'] ?? 'image/png';

                return "data:{$mime};base64,{$base64}";
            }
        );
    }
}
```

---

## Resumen de Modelos y Relaciones

| Modelo | Tabla | PK | Relaciones salientes |
|---|---|---|---|
| `Administrador` | `administrador` | `id_admin` | `hasMany` Sesion, Consulta\*, Proyecto, Colaborador |
| `Visitante` | `visitante` | `id_visitante` | `hasMany` Consulta |
| `Sesion` | `sesion` | `id_sesion` | `belongsTo` Administrador |
| `Consulta` | `consulta` | `id_consulta` | `belongsTo` Visitante, Administrador\*; `hasOne` ArchivoAdjunto |
| `ArchivoAdjunto` | `archivo_adjunto` | `id_adjunto` | `belongsTo` Consulta |
| `Proyecto` | `proyecto` | `id_proyecto` | `belongsTo` Administrador; `hasMany` ImagenProyecto, Certificado |
| `ImagenProyecto` | `imagen_proyecto` | `id_imagen` | `belongsTo` Proyecto |
| `Certificado` | `certificado` | `id_certificado` | `belongsTo` Proyecto |
| `Colaborador` | `colaborador` | `id_colaborador` | `belongsTo` Administrador — accessor `logotipoBase64` usa `tipo_mime_logotipo` |

> \* FK personalizada `id_admin_responsable` en `Consulta` → `Administrador`.

---

## Notas de Implementación

### Campos BYTEA y streams de PostgreSQL

Todos los campos de tipo `BYTEA` (`archivo_pdf`, `imagen`, `logotipo`) son devueltos por PDO/pgsql como **PHP resources (streams)**, no como strings. Por esta razón, todos los accessors base64 aplican:

```php
$binary = is_resource($raw) ? stream_get_contents($raw) : $raw;
```

Nunca usar `base64_encode($raw)` directamente sobre el valor de `$this->attributes` sin esta conversión.

### Argon2id en Administrador

El campo `password_hash` se excluye de `$fillable` intencionalmente. La única forma válida de asignarlo es:

```php
$admin->password_hash = \Illuminate\Support\Facades\Hash::make($plaintext);
$admin->save();
```

El driver `argon2id` debe estar configurado en `config/hashing.php` según `01_setup_laravel_migraciones.md`.

### Scopes encadenables

Los scopes de `Proyecto` son encadenables entre sí y con cualquier método de Query Builder:

```php
// Ejemplo de uso combinado
$resultados = Proyecto::publicados()
    ->porCategoria('infraestructura vial')
    ->buscarTexto('región metropolitana')
    ->with(['imagenes', 'certificados'])
    ->orderBy('anio_ejecucion', 'desc')
    ->paginate(12);
```

### Eager Loading recomendado

Para evitar el problema N+1 en vistas de listado, usar `with()` explícito:

```php
// Consultas con su visitante y admin responsable
Consulta::with(['visitante', 'administrador', 'archivoAdjunto'])->get();

// Proyectos con primera imagen (para thumbnail en listado)
Proyecto::publicados()->with(['imagenes' => fn($q) => $q->limit(1)])->get();
```
