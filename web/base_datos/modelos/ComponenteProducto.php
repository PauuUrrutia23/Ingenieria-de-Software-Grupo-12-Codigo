<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponenteProducto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'componentes_producto';
    protected $primaryKey = 'id_componente';
    protected $fillable = ['nombre', 'orden', 'id_producto'];
    protected $casts = ['orden' => 'integer'];

    public function producto() { return $this->belongsTo(Producto::class, 'id_producto', 'id_producto'); }
}
