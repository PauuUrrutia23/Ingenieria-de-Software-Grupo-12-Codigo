<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ImagenProyecto extends Model
{
    use HasFactory;

    protected $table = 'imagenes_proyecto';
    protected $primaryKey = 'id_imagen';
    protected $fillable = ['imagen', 'nombre_archivo', 'tipo_mime', 'id_proyecto'];

    public function proyecto() { return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto'); }
}
