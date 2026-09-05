<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;
use App\Models\Proyecto;
use App\Services\StorageAdapter;
use Illuminate\Support\Str;

/**
 * InstitucionalCtrl («Control») — Diagrama de Componentes: "Páginas institucionales".
 *
 * Todo el contenido público que no es el formulario de contacto ni la galería de
 * proyectos con filtros (esa vive en ProyectoController): inicio, línea de
 * producto, certificaciones, colaboradores, términos y el enlace a documentación
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
            'docsUrl' => self::urlDocumentacionVigente() ?: '#',
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
        $url = self::urlDocumentacionVigente();

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
    public static function urlDocumentacionVigente(): ?string
    {
        try {
            $registro = Contenido::where('seccion', self::SECCION_DOCUMENTACION)
                ->where('activo', true)
                ->orderBy('orden')
                ->first();
        } catch (\Throwable $e) {
            // Excepción 2: falla la consulta a BD. No se propaga el error técnico (RNF10).
            return null;
        }

        $url = $registro->enlace ?? env('DOCS_CONECTORES_URL');

        return ($url && $url !== '#') ? $url : null;
    }

    /** RF24 / CU 24.1 - Listado público de certificaciones vigentes. */
    public function certificaciones()
    {
        $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->orderBy('nombre')->get();

        return view('public.certificaciones', compact('certificados'));
    }

    /**
     * RF25 / CU 25.1 - Descarga del PDF del certificado.
     *
     * No se enlaza el archivo directo desde storage: pasa por el Controlador para
     * poder resolver las excepciones del caso de uso y, sobre todo, para generar
     * un nombre de archivo seguro cuando el registro no tiene uno válido
     * (CU 25.1 Excepción 4).
     */
    public function certificacionesDescargar(Certificado $certificado, StorageAdapter $storage)
    {
        // CU 25.2 Excepciones 1 y 2: el certificado no tiene PDF cargado, o el archivo
        // referenciado en BD ya no está en disco.
        if (!$certificado->archivo_pdf || !$storage->existe($certificado->archivo_pdf)) {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        // CU 25.1 Excepción 4: si el nombre almacenado no sirve, se construye uno seguro
        // a partir del código y el nombre de la normativa.
        $nombreSeguro = Str::slug($certificado->codigo . '-' . $certificado->nombre);
        if ($nombreSeguro === '') {
            $nombreSeguro = 'certificado-' . $certificado->id_certificado;
        }

        return $storage->descargar($certificado->archivo_pdf, $nombreSeguro . '.pdf');
    }

    /** RF11 / CU 11.2 - Página pública de colaboradores (logos y nombres desde BD). */
    public function colaboradores()
    {
        $colaboradores = $this->db->query(Colaborador::class)->orderBy('nombre_comercial')->get();

        return view('public.colaboradores', compact('colaboradores'));
    }
}
