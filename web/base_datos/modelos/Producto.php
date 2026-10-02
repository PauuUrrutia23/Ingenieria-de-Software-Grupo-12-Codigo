<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    protected $fillable = ['nombre', 'descripcion', 'imagen', 'tipo_mime', 'id_admin'];

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
    public function componentes() { return $this->hasMany(ComponenteProducto::class, 'id_producto', 'id_producto'); }
}
