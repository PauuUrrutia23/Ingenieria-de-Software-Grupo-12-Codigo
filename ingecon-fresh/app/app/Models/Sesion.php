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
