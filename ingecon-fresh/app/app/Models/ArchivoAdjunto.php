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
