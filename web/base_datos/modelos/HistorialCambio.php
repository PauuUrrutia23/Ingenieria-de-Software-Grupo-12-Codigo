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

    // CU 15.3: los campos se muestran con el nombre que tienen en los formularios del panel.
    // Un nombre propio del módulo tiene prioridad sobre el general ("nombre" de un certificado).
    public const CAMPOS = [
        'certificados.nombre' => 'Nombre de la Normativa',
        'certificados.organismo' => 'Organismo Certificador',
        'certificados.url_organismo' => 'Enlace del Organismo',
        'certificados.imagen' => 'Imagen Representativa',
        'certificados.archivo_pdf' => 'Archivo PDF adjunto',
        'certificados.codigo' => 'Código',
        'certificados.fecha_emision' => 'Fecha de Emisión',
        'certificados.fecha_vencimiento' => 'Fecha de Vencimiento',
        'colaboradores.nombre_comercial' => 'Nombre Comercial',
        'colaboradores.logotipo' => 'Logotipo',
        'colaboradores.sitio_web' => 'Sitio Web del Colaborador',
        'proyectos.nombre_obra' => 'Nombre de Obra',
        'proyectos.descripcion_tecnica' => 'Descripción Técnica',
        'proyectos.anio_ejecucion' => 'Año de Ejecución',
        'proyectos.estado_publicacion' => 'Visibilidad',
        'proyectos.imagen' => 'Fotografía',
        'contenido.seccion' => 'Sección',
        'contenido.cuerpo' => 'Texto',
        'administradores.correo' => 'Correo Institucional',
        'administradores.perm_editar_contenido' => 'Editar Contenido',
        'administradores.perm_exportar_consultas' => 'Exportar Consultas',
        'administradores.perm_eliminar_consultas' => 'Eliminar Consultas',
        'nombre' => 'Nombre',
        'descripcion' => 'Descripción',
        'imagen' => 'Imagen',
        'archivo' => 'Archivo',
        'titulo' => 'Título',
        'enlace' => 'Enlace',
        'orden' => 'Orden',
        'activo' => 'Activo',
        'rol' => 'Rol',
        'region' => 'Región',
        'comuna' => 'Comuna',
        'latitud' => 'Latitud',
        'longitud' => 'Longitud',
        'categoria' => 'Categoría',
        'acepta_terminos' => 'Aceptación de Términos',
    ];

    // Campos de sí o no: se muestran como «Sí» o «No» en vez de 1 o 0.
    private const SI_NO = ['activo', 'acepta_terminos', 'perm_editar_contenido', 'perm_exportar_consultas', 'perm_eliminar_consultas'];

    public function etiquetaCampo(string $campo): string
    {
        return self::CAMPOS["{$this->modulo}.$campo"] ?? self::CAMPOS[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
    }

    public function valorLegible(string $campo, mixed $valor): mixed
    {
        if ($valor === null || !in_array($campo, self::SI_NO, true)) {
            return $valor;
        }

        return in_array($valor, [1, '1', true], true) ? 'Sí' : 'No';
    }

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
