<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class Colaborador extends Model
{
    use HasFactory;

    protected $table = 'colaboradores';
    protected $primaryKey = 'id_colaborador';
    protected $fillable = ['nombre_comercial', 'logotipo', 'tipo_mime', 'id_admin'];

    public function getLogoUrlAttribute(): ?string
    {
        $ruta = (string) $this->logotipo;
        if ($ruta === '' || str_contains($ruta, '..')) {
            return null;
        }

        if (str_starts_with($ruta, 'img/colaboradores/')) {
            return is_file(public_path($ruta)) ? asset($ruta) : null;
        }

        return Storage::disk('public')->exists($ruta)
            ? Storage::disk('public')->url($ruta)
            : null;
    }

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
