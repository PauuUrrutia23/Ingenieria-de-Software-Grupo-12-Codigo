<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Visitante extends Model
{
    use HasFactory;

    protected $table = 'visitantes';
    protected $primaryKey = 'id_visitante';
    protected $fillable = ['nombre', 'apellido', 'email'];

    public function consultas() {
        return $this->hasMany(Consulta::class, 'id_visitante', 'id_visitante');
    }
}
