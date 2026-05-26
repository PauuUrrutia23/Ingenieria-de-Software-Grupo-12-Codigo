<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitante extends Model
{
    protected $table = 'visitante';

    protected $primaryKey = 'id_visitante';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un visitante puede enviar muchas consultas.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'id_visitante', 'id_visitante');
    }
}
