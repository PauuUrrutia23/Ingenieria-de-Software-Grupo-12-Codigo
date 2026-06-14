<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

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

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    protected function imagen(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null
                ? null
                : DB::raw("decode('" . bin2hex($value) . "', 'hex')"),
        );
    }

    /**
     * Imagen BYTEA como Data URI para usar en <img src="...">, o null si está
     * vacía. PDO con pgsql devuelve el BYTEA como resource stream.
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

                return "data:{$mime};base64,{$base64}";
            }
        );
    }
}
