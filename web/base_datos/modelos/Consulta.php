<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Consulta extends Model
{
    use HasFactory;

    protected $table = 'consultas';
    protected $primaryKey = 'id_consulta';
    public $timestamps = false;
    protected $fillable = ['mensaje', 'fecha_consulta', 'estado', 'prioridad', 'id_visitante', 'id_admin_responsable', 'created_at'];
    protected $casts = ['fecha_consulta' => 'date', 'created_at' => 'datetime'];

    public function visitante() { return $this->belongsTo(Visitante::class, 'id_visitante', 'id_visitante'); }
    public function adminResponsable() { return $this->belongsTo(Administrador::class, 'id_admin_responsable', 'id_admin'); }
}
