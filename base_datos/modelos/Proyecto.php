<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'proyecto';

    protected $primaryKey = 'id_proyecto';

    public $timestamps = false;

    protected $fillable = [
        'nombre_obra',
        'descripcion_tecnica',
        'region',
        'ubicacion_geografica',
        'anio_ejecucion',
        'estado_publicacion',
        'categoria',
        'id_admin',
    ];

    protected $casts = [
        'anio_ejecucion' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /**
     * Un proyecto pertenece al administrador que lo gestiona.
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'id_admin', 'id_admin');
    }

    /**
     * Un proyecto tiene muchas imágenes asociadas.
     */
    public function imagenesProyecto(): HasMany
    {
        return $this->hasMany(ImagenProyecto::class, 'id_proyecto', 'id_proyecto');
    }

    /**
     * Alias de imagenesProyecto() usado por el panel admin (DBRouterController).
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenProyecto::class, 'id_proyecto', 'id_proyecto');
    }

    /**
     * Un proyecto puede tener muchos certificados de calidad/lote.
     */
    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class, 'id_proyecto', 'id_proyecto');
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    /**
     * Filtra proyectos cuyo estado_publicacion sea 'publicado'.
     *
     * Uso: Proyecto::publicados()->get()
     */
    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('estado_publicacion', 'publicado');
    }

    /**
     * Filtra proyectos por categoría exacta (case-insensitive con ILIKE).
     *
     * Uso: Proyecto::porCategoria('infraestructura')->get()
     *
     * @param string $categoria  Valor de la categoría a filtrar.
     */
    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->whereRaw('categoria ILIKE ?', [$categoria]);
    }

    /**
     * Búsqueda de texto libre en nombre_obra y ubicacion_geografica.
     * Usa ILIKE de PostgreSQL para búsqueda case-insensitive sin índice full-text.
     *
     * Uso: Proyecto::buscarTexto('puente')->get()
     *      Proyecto::publicados()->buscarTexto('santiago')->paginate(10)
     *
     * @param string $texto  Término de búsqueda (se añade % automáticamente).
     */
    public function scopeBuscarTexto(Builder $query, string $texto): Builder
    {
        $termino = '%' . $texto . '%';

        return $query->where(function (Builder $q) use ($termino) {
            $q->whereRaw('nombre_obra ILIKE ?', [$termino])
              ->orWhereRaw('ubicacion_geografica ILIKE ?', [$termino]);
        });
    }
}
