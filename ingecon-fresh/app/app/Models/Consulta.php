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
