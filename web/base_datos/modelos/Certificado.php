<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

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
        'fecha_emision' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
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
     * PDF del certificado como cadena base64, o null si no hay archivo.
     * PDO con pgsql devuelve el BYTEA como resource stream.
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
