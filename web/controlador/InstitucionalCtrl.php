<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;
use App\Models\Proyecto;

/**
 * InstitucionalCtrl («Control») — Diagrama de Componentes: "Páginas institucionales".
 *
 * Todo el contenido público que no es el formulario de contacto ni la galería de
 * proyectos con filtros (esa vive en ProyectoController): inicio, línea de
 * producto, colaboradores, términos y el enlace a documentación
 * técnica externa.
 */
class InstitucionalCtrl extends Controller
{
    private const SECCION_DOCUMENTACION = 'documentacion';

    public function __construct(private DBRouterController $db)
    {
    }

    /** RF12 / CU 12.1 - Página de inicio con las secciones de la Barra de Navegación Fija. */
    public function home()
    {
        $proyectosRecientes = $this->db->query(Proyecto::class)
            ->where('estado_publicacion', 'publicado')
            ->orderBy('anio_ejecucion', 'desc')
            ->with('imagenes')
            ->take(3)
            ->get();

        $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->take(3)->get();
        $proveedores = $this->db->query(Colaborador::class)->get();

        return view('public.index', [
            'proyectos_recientes' => $proyectosRecientes,
            'certificados' => $certificados,
            'proveedores' => $proveedores,
        ]);
    }

    /** RF10 - Ficha de Conectores Metálicos. */
    public function producto()
    {
        return view('public.producto', [
            'docsUrl' => $this->urlDocumentacionVigente() ?: '#',
        ]);
    }

    /** CU 2.1 - Términos y Condiciones y Política de Privacidad. */
    public function terminos()
    {
        return view('legal.terminos');
    }

    /**
     * RF10 / CU 10.1 - Redirección a la documentación técnica de Conectores
     * Metálicos. La URL se resuelve desde BD (tabla `contenidos`, sección
     * "documentacion"), no está fija en código.
     */
    public function documentacionConectores()
    {
        $url = $this->urlDocumentacionVigente();

        // Excepción 2: URL registrada en BD no disponible o eliminada.
        if (!$url) {
            return redirect()->route('public.producto')->with(
                'doc_no_disponible',
                'La documentación técnica no está disponible temporalmente. Intente nuevamente más tarde.'
            );
        }

        return redirect()->away($url);
    }

    /** Resuelve la URL vigente de documentación; null si no hay ninguna registrada. */
    private function urlDocumentacionVigente(): ?string
    {
        try {
            $registro = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_DOCUMENTACION)
                ->where('activo', true)
                ->orderBy('id_contenido', 'desc')
                ->first();
        } catch (\Throwable $e) {
            // Excepción 2: falla la consulta a BD. No se propaga el error técnico (RNF10).
            return null;
        }

        $url = $registro->enlace ?? env('DOCS_CONECTORES_URL');

        return ($url && $url !== '#') ? $url : null;
    }

    /** RF11 / CU 11.2 - Página pública de colaboradores (logos y nombres desde BD). */
    public function colaboradores()
    {
        $colaboradores = $this->db->query(Colaborador::class)->orderBy('nombre_comercial')->get();

        return view('public.colaboradores', compact('colaboradores'));
    }
}
