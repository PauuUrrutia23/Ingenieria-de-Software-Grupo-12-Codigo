<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

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

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'id_consulta', 'id_consulta');
    }

    protected function archivoPdf(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null
                ? null
                : DB::raw("decode('" . bin2hex($value) . "', 'hex')"),
        );
    }

    /**
     * PDF almacenado en BYTEA como cadena base64, o null si no hay archivo.
     * PostgreSQL devuelve el BYTEA como resource stream en PHP.
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
