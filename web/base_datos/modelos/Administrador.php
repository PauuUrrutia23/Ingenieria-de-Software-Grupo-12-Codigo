<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrador extends Model
{
    protected $table = 'administrador';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;

    // password_hash se excluye: se asigna siempre de forma explícita tras Hash::make().
    protected $fillable = [
        'correo',
        'intentos_fallidos',
        'bloqueado_hasta',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'bloqueado_hasta'   => 'datetime',
        'activo'            => 'boolean',
        'intentos_fallidos' => 'integer',
    ];

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'id_admin', 'id_admin');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'id_admin_responsable', 'id_admin');
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_admin', 'id_admin');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'id_admin', 'id_admin');
    }

    /**
     * Administradores activos y no bloqueados en este momento.
     */
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true)
                     ->where(function (Builder $q) {
                         $q->whereNull('bloqueado_hasta')
                           ->orWhere('bloqueado_hasta', '<', now());
                     });
    }
}
