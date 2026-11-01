<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class HistorialCambio extends Model
{
    protected $table = 'historial_cambios';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;
    protected $fillable = ['fecha_hora', 'id_admin', 'correo_responsable', 'accion', 'modulo', 'valores_anteriores', 'valores_nuevos', 'id_registro_afectado'];
    protected $casts = ['fecha_hora' => 'datetime'];

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
