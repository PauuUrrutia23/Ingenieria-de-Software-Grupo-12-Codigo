<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Proyecto extends Model
{
    use HasFactory;

    protected $table = 'proyectos';
    protected $primaryKey = 'id_proyecto';
    protected $fillable = ['nombre_obra', 'descripcion_tecnica', 'region', 'ubicacion_geografica', 'latitud', 'longitud', 'anio_ejecucion', 'estado_publicacion', 'categoria', 'id_admin'];

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
    public function imagenes() { return $this->hasMany(ImagenProyecto::class, 'id_proyecto', 'id_proyecto'); }
}
