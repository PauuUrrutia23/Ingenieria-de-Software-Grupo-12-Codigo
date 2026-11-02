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

    public const ACCIONES = [
        'creacion' => 'Creación',
        'modificacion' => 'Modificación',
        'eliminacion' => 'Eliminación',
        'exportacion' => 'Exportación',
        'visualizacion' => 'Vista de detalle',
        'asignacion_permiso' => 'Asignación de permiso',
    ];

    // CU 15.2: accesos rápidos por tipo de acción; "Modificaciones" incluye la asignación de permisos.
    public const TIPOS = [
        'exportaciones' => ['exportacion'],
        'eliminaciones' => ['eliminacion'],
        'modificaciones' => ['modificacion', 'asignacion_permiso'],
        'creaciones' => ['creacion'],
        'visualizaciones' => ['visualizacion'],
    ];

    public const MODULOS = [
        'contenido' => 'Contenido del sitio',
        'productos' => 'Productos',
        'proyectos' => 'Proyectos',
        'colaboradores' => 'Colaboradores',
        'certificados' => 'Certificados',
        'administradores' => 'Administración',
        'consultas' => 'Consultas',
    ];

    // RF15: ningún usuario puede modificar ni eliminar registros desde el sistema.
    protected static function booted(): void
    {
        $inmutable = fn () => throw new \LogicException('Los registros del Historial de Cambios no se pueden modificar ni eliminar.');
        static::updating($inmutable);
        static::deleting($inmutable);
    }

    public function getDescripcionAttribute(): string
    {
        return $this->accion_etiqueta . ' en ' . $this->modulo_etiqueta
            . ($this->id_registro_afectado ? " (registro N° {$this->id_registro_afectado})" : '');
    }

    public function getAccionEtiquetaAttribute(): string { return self::ACCIONES[$this->accion] ?? $this->accion; }
    public function getModuloEtiquetaAttribute(): string { return self::MODULOS[$this->modulo] ?? $this->modulo; }

    public function administrador() { return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin'); }
}
