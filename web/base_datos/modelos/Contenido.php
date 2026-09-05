<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Contenido extends Model
{
    use HasFactory;

    protected $table = 'contenidos';
    protected $primaryKey = 'id_contenido';
    protected $fillable = ['seccion', 'titulo', 'cuerpo', 'archivo', 'tipo_mime', 'enlace', 'orden', 'activo', 'id_admin'];
    protected $casts = ['activo' => 'boolean'];

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
