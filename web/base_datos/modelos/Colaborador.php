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
        'tipo_mime',      // necesario para el Data URI del accessor logotipoBase64
        'id_admin',
    ];

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }

    protected function logotipo(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null
                ? null
                : DB::raw("decode('" . bin2hex($value) . "', 'hex')"),
        );
    }

    /**
     * Logotipo BYTEA como Data URI para usar en <img src="...">, o null si no
     * hay logotipo. PDO con pgsql devuelve el BYTEA como resource stream.
     */
    protected function logotipoBase64(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->getRawOriginal('logotipo');

                if ($raw === null) {
                    return null;
                }

                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                if (! $binary || strlen($binary) === 0) {
                    return null;
                }

                $base64 = base64_encode($binary);

                $mime = $this->getRawOriginal('tipo_mime') ?: 'image/png';

                return "data:{$mime};base64,{$base64}";
            }
        );
    }
}
