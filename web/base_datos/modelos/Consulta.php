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
        'fecha_consulta' => 'datetime',
    ];

    public function visitante(): BelongsTo
    {
        return $this->belongsTo(Visitante::class, 'id_visitante', 'id_visitante');
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(
            Administrador::class,
            'id_admin_responsable',
            'id_admin'
        );
    }

    public function archivoAdjunto(): HasOne
    {
        return $this->hasOne(ArchivoAdjunto::class, 'id_consulta', 'id_consulta');
    }
}
