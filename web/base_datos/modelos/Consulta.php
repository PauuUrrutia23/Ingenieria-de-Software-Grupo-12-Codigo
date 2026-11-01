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
    protected $fillable = ['mensaje', 'estado', 'prioridad', 'id_visitante', 'id_admin_responsable', 'created_at', 'acepta_terminos', 'fecha_cambio_estado', 'notificacion_admin_pendiente', 'notificacion_admin_ultimo_error'];
    protected $casts = [
        'created_at' => 'datetime',
        'acepta_terminos' => 'boolean',
        'fecha_cambio_estado' => 'datetime',
        'notificacion_admin_pendiente' => 'boolean',
    ];

    // RNF24 cuenta los plazos desde el último cambio de estado.
    protected static function booted(): void
    {
        static::creating(function (Consulta $c) {
            $c->fecha_cambio_estado ??= $c->created_at ?? now();
        });
        static::updating(function (Consulta $c) {
            if ($c->isDirty('estado')) {
                $c->fecha_cambio_estado = now();
            }
        });
    }

    public function visitante() { return $this->belongsTo(Visitante::class, 'id_visitante', 'id_visitante'); }
    public function adminResponsable() { return $this->belongsTo(Administrador::class, 'id_admin_responsable', 'id_admin'); }
}
