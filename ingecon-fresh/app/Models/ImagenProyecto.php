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
