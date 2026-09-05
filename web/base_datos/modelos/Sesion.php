<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Sesion extends Model
{
    use HasFactory;

    protected $table = 'sesiones';
    protected $primaryKey = 'id_sesion';
    protected $fillable = ['token_hash', 'fecha_inicio', 'estado', 'id_admin'];
    protected $casts = ['fecha_inicio' => 'datetime'];

    public function administrador() {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }
}
