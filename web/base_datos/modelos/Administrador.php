<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Administrador extends Authenticatable implements \Illuminate\Contracts\Auth\Authenticatable
{
    use HasFactory;

    protected $table = 'administradores';
    protected $primaryKey = 'id_admin';
    protected $fillable = ['correo', 'password_hash', 'rol', 'intentos_fallidos', 'bloqueado_hasta', 'activo'];
    protected $hidden = ['password_hash'];
    protected $casts = ['activo' => 'boolean', 'bloqueado_hasta' => 'datetime'];

    public function getAuthPassword() { return $this->password_hash; }
    public function getAuthIdentifierName() { return 'id_admin'; }
    public function sesiones() { return $this->hasMany(Sesion::class, 'id_admin', 'id_admin'); }
    public function recuperaciones() { return $this->hasMany(RecuperacionPassword::class, 'id_admin', 'id_admin'); }
    public function colaboradores() { return $this->hasMany(Colaborador::class, 'id_admin', 'id_admin'); }
    public function proyectos() { return $this->hasMany(Proyecto::class, 'id_admin', 'id_admin'); }
    public function consultasResponsable() { return $this->hasMany(Consulta::class, 'id_admin_responsable', 'id_admin'); }
    public function certificados() { return $this->hasMany(Certificado::class, 'id_admin', 'id_admin'); }
    public function contenidos() { return $this->hasMany(Contenido::class, 'id_admin', 'id_admin'); }
}
