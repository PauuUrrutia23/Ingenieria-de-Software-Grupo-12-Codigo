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
