<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Certificado extends Model
{
    use HasFactory;

    protected $table = 'certificados';
    protected $primaryKey = 'id_certificado';
    protected $fillable = ['nombre', 'descripcion', 'imagen', 'tipo_mime', 'archivo_pdf', 'estado', 'organismo', 'url_organismo', 'id_admin'];

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
