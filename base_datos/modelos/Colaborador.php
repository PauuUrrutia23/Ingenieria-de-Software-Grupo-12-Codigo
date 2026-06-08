<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class Colaborador extends Model
{
    protected $table = 'colaborador';

    protected $primaryKey = 'id_colaborador';

    public $timestamps = false;

    protected $fillable = [
        'nombre_comercial',
        'logotipo',
        'tipo_mime',      // Necesario para el Data URI correcto en el accessor logotipoBase64
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
    // Mutators / Accessors
    // -------------------------------------------------------------------------

    protected function logotipo(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null
                ? null
                : DB::raw("decode('" . bin2hex($value) . "', 'hex')"),
        );
    }

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

                // Usar el tipo MIME almacenado en la columna tipo_mime.
                // La columna fue agregada en la migración para soportar PNG, JPG, SVG y WebP.
                // Por defecto es 'image/png' si no se especificó al guardar.
                $mime = $this->attributes['tipo_mime'] ?? 'image/png';

                return "data:{$mime};base64,{$base64}";
            }
        );
    }
}
