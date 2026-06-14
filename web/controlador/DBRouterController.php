<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\ArchivoAdjunto;
use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Consulta;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use App\Models\Sesion;
use App\Models\Visitante;
use Illuminate\Database\Eloquent\Collection;

/**
 * Intermediario de acceso a datos.
 *
 * Única clase autorizada a invocar Eloquent. Los controladores, el middleware
 * AdminAuth y los jobs reciben una instancia por inyección de dependencias y
 * delegan aquí toda lectura/escritura. No es enrutable: no recibe Request ni
 * devuelve respuestas HTTP. Devuelve modelos o Collections; la conversión
 * BYTEA→base64 y el formateo quedan en los controladores.
 */
class DBRouterController
{
    // Administrador y sesión

    public function buscarAdminPorCorreo(string $correo): ?Administrador
    {
        return Administrador::where('correo', $correo)->first();
    }

    public function buscarAdminPorId(int $idAdmin): ?Administrador
    {
        return Administrador::find($idAdmin);
    }

    public function guardarAdmin(Administrador $admin): bool
    {
        return $admin->save();
    }

    /**
     * @param array{token_hash:string,fecha_inicio:mixed,estado:string,id_admin:int} $datos
     */
    public function crearSesion(array $datos): Sesion
    {
        return Sesion::create($datos);
    }

    public function buscarSesionActivaPorId(int $idSesion): ?Sesion
    {
        return Sesion::where('id_sesion', $idSesion)
            ->where('estado', 'activa')
            ->first();
    }

    public function buscarSesionActivaDeAdmin(int $idSesion, int $idAdmin): ?Sesion
    {
        return Sesion::where('id_sesion', $idSesion)
            ->where('id_admin', $idAdmin)
            ->where('estado', 'activa')
            ->first();
    }

    public function guardarSesion(Sesion $sesion): bool
    {
        return $sesion->save();
    }

    // Visitante, consulta y archivo adjunto

    /**
     * Devuelve el visitante con ese email o lo crea si no existe.
     *
     * @param array{nombre:string,apellido:string} $datos
     */
    public function obtenerOCrearVisitante(string $email, array $datos): Visitante
    {
        return Visitante::firstOrCreate(['email' => $email], $datos);
    }

    public function crearConsulta(array $datos): Consulta
    {
        return Consulta::create($datos);
    }

    public function eliminarConsulta(Consulta $consulta): void
    {
        $consulta->delete();
    }

    public function crearArchivoAdjunto(array $datos): ArchivoAdjunto
    {
        return ArchivoAdjunto::create($datos);
    }

    // Galería pública de proyectos

    /**
     * Proyectos publicados con filtros opcionales de texto libre
     * (nombre_obra / ubicacion_geografica vía ILIKE) y categoría exacta.
     * Incluye las imágenes ordenadas; el controlador toma la primera.
     *
     * @param  string|null $texto      término libre ('' o null = sin filtro)
     * @param  string|null $categoria  'Habitacional'|'Industrial'|'Agrícola' ('' = sin filtro)
     * @return Collection<int,Proyecto>
     */
    public function buscarProyectosPublicados(?string $texto, ?string $categoria): Collection
    {
        $query = Proyecto::where('estado_publicacion', 'publicado');

        if (filled($texto)) {
            $termino = '%' . $texto . '%';
            $query->where(function ($q) use ($termino) {
                $q->whereRaw('nombre_obra ILIKE ?', [$termino])
                  ->orWhereRaw('ubicacion_geografica ILIKE ?', [$termino]);
            });
        }

        if (filled($categoria)) {
            $query->where('categoria', $categoria);
        }

        $query->with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');
        }]);

        return $query->orderByRaw('anio_ejecucion DESC NULLS LAST')->get();
    }

    /**
     * Proyecto con todas sus imágenes. No filtra por estado: el controlador
     * valida que esté 'publicado' antes de exponerlo.
     */
    public function buscarProyectoConImagenes(int $id): ?Proyecto
    {
        return Proyecto::with(['imagenesProyecto' => function ($q) {
            $q->orderBy('id_imagen', 'asc');
        }])->find($id);
    }

    // Certificados

    /**
     * Certificados vigentes con metadatos, sin el BYTEA archivo_pdf
     * (rendimiento) y con su proyecto (id, nombre_obra, region).
     *
     * @return Collection<int,Certificado>
     */
    public function listarCertificadosActivos(): Collection
    {
        return Certificado::select([
                'id_certificado',
                'codigo_lote',
                'fecha_emision',
                'estado',
                'id_proyecto',
            ])
            ->where('estado', 'Vigente')
            ->with(['proyecto' => function ($query) {
                $query->select(['id_proyecto', 'nombre_obra', 'region']);
            }])
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }

    /**
     * Certificado con su BYTEA archivo_pdf para descarga o preview.
     */
    public function buscarCertificadoParaDescarga(int $id): ?Certificado
    {
        return Certificado::select([
                'id_certificado',
                'codigo_lote',
                'archivo_pdf',
                'estado',
            ])
            ->where('id_certificado', $id)
            ->first();
    }

    // Proyectos del panel de administración

    /**
     * Proyectos de un administrador con la primera imagen y el conteo
     * de imágenes (imagenes_count).
     *
     * @return Collection<int,Proyecto>
     */
    public function listarProyectosDeAdmin(int $idAdmin): Collection
    {
        return Proyecto::where('id_admin', $idAdmin)
            ->with(['imagenes' => fn($q) => $q->orderBy('id_imagen')->limit(1)])
            ->withCount('imagenes')
            ->orderBy('id_proyecto', 'desc')
            ->get();
    }

    public function crearProyecto(array $datos): Proyecto
    {
        return Proyecto::create($datos);
    }

    public function buscarProyectoPorId(int $id): ?Proyecto
    {
        return Proyecto::find($id);
    }

    public function guardarProyecto(Proyecto $proyecto): bool
    {
        return $proyecto->save();
    }

    /**
     * Elimina un proyecto. Sus imágenes se borran en cascada por la FK.
     */
    public function eliminarProyecto(Proyecto $proyecto): bool
    {
        return $proyecto->delete();
    }

    public function crearImagenProyecto(array $datos): ImagenProyecto
    {
        return ImagenProyecto::create($datos);
    }

    public function contarImagenesDeProyecto(Proyecto $proyecto): int
    {
        return $proyecto->imagenes()->count();
    }

    public function primeraImagenDeProyecto(Proyecto $proyecto): ?ImagenProyecto
    {
        return $proyecto->imagenes()->orderBy('id_imagen')->first();
    }

    /**
     * Elimina las imágenes indicadas que pertenezcan a un proyecto.
     *
     * @param int[] $idsImagenes
     * @return int  filas eliminadas
     */
    public function eliminarImagenesDeProyecto(array $idsImagenes, int $idProyecto): int
    {
        if (empty($idsImagenes)) {
            return 0;
        }

        return ImagenProyecto::whereIn('id_imagen', $idsImagenes)
            ->where('id_proyecto', $idProyecto)
            ->delete();
    }

    // Colaboradores del panel de administración

    /**
     * Colaboradores de un administrador con su logotipo (BYTEA) y tipo_mime.
     * El controlador convierte el BYTEA a Data URI.
     *
     * @return Collection<int,Colaborador>
     */
    public function listarColaboradoresDeAdmin(int $idAdmin): Collection
    {
        return Colaborador::where('id_admin', $idAdmin)
            ->select([
                'id_colaborador',
                'nombre_comercial',
                'logotipo',
                'tipo_mime',
            ])
            ->orderBy('nombre_comercial', 'asc')
            ->get();
    }

    /**
     * Todos los colaboradores (vista pública) con logotipo y tipo_mime.
     *
     * @return Collection<int,Colaborador>
     */
    public function listarColaboradores(): Collection
    {
        return Colaborador::select([
                'id_colaborador',
                'nombre_comercial',
                'logotipo',
                'tipo_mime',
            ])
            ->orderBy('nombre_comercial', 'asc')
            ->get();
    }

    public function crearColaborador(array $datos): Colaborador
    {
        return Colaborador::create($datos);
    }

    public function buscarColaborador(int $id): ?Colaborador
    {
        return Colaborador::find($id);
    }

    public function eliminarColaborador(Colaborador $colaborador): void
    {
        $colaborador->delete();
    }
}
