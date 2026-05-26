<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrador extends Model
{
    /**
     * Nombre exacto de la tabla en PostgreSQL.
     */
    protected $table = 'administrador';

    /**
     * Clave primaria no estándar.
     */
    protected $primaryKey = 'id_admin';

    /**
     * Las tablas del sistema no usan timestamps de Eloquent.
     */
    public $timestamps = false;

    /**
     * Campos asignables masivamente.
     * password_hash se excluye: debe asignarse siempre de forma explícita
     * tras pasar por Hash::make().
     */
    protected $fillable = [
        'correo',
        'intentos_fallidos',
        'bloqueado_hasta',
        'activo',
    ];

    /**
     * Campos ocultos en serialización JSON (API responses, toArray()).
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Conversión automática de tipos al leer desde la base de datos.
     */
    protected $casts = [
        'bloqueado_hasta'   => 'datetime',   // Carbon instance
        'activo'            => 'boolean',
        'intentos_fallidos' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un administrador tiene muchas sesiones activas o históricas.
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'id_admin', 'id_admin');
    }

    /**
     * Un administrador puede ser responsable de muchas consultas.
     * FK personalizada: id_admin_responsable en la tabla consulta.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'id_admin_responsable', 'id_admin');
    }

    /**
     * Un administrador gestiona muchos proyectos.
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_admin', 'id_admin');
    }

    /**
     * Un administrador crea muchos colaboradores.
     */
    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'id_admin', 'id_admin');
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    /**
     * Filtra administradores activos y que no estén bloqueados en este momento.
     *
     * Uso: Administrador::activo()->get()
     *
     * Condiciones:
     *   - activo = true
     *   - bloqueado_hasta IS NULL  O  bloqueado_hasta < NOW()
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
