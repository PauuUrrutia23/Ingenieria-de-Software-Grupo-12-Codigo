<?php
namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * DBRouterController («Control») — Diagrama de Componentes, intermediario BD.
 *
 * Todos los controladores del Servidor Web pasan por acá para llegar a Eloquent
 * ORM en vez de invocar los modelos de forma estática directamente. Es un punto
 * único donde, si el proyecto migrara de motor de base de datos o necesitara
 * instrumentar/loguear consultas de forma centralizada, se haría en un solo
 * lugar en vez de en cada controlador.
 *
 * @template TModel of Model
 */
class DBRouterController extends Controller
{
    /** Inicia una consulta sobre el modelo indicado. */
    public function query(string $modelo): Builder
    {
        return $modelo::query();
    }

    /** Busca por clave primaria; null si no existe. */
    public function find(string $modelo, int|string $id): ?Model
    {
        return $modelo::find($id);
    }

    /** Crea un nuevo registro. */
    public function create(string $modelo, array $datos): Model
    {
        return $modelo::create($datos);
    }

    /**
     * Busca por los atributos indicados y, si no existe, crea el registro
     * agregando los valores adicionales. Es la operación que necesita el
     * Formulario de Contacto: un mismo Visitante puede enviar varias Consultas
     * y no debe duplicarse en la tabla.
     */
    public function firstOrCreate(string $modelo, array $atributos, array $valores = []): Model
    {
        return $modelo::firstOrCreate($atributos, $valores);
    }

    /** Actualiza un registro ya cargado y devuelve la instancia actualizada. */
    public function update(Model $registro, array $datos): Model
    {
        $registro->update($datos);

        return $registro;
    }

    /** Elimina un registro ya cargado. */
    public function delete(Model $registro): bool
    {
        return (bool) $registro->delete();
    }
}
