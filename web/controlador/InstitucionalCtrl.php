<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;
use App\Models\Proyecto;

class InstitucionalCtrl extends Controller
{
    private const SECCION_DOCUMENTACION = 'documentacion';

    public function __construct(private DBRouterController $db)
    {
    }

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

    public function producto()
    {
        return view('public.producto', [
            'docsUrl' => $this->urlDocumentacionVigente() ?: '#',
        ]);
    }

    public function terminos()
    {
        return view('legal.terminos');
    }

    public function documentacionConectores()
    {
        $url = $this->urlDocumentacionVigente();

        if (!$url) {
            return redirect()->route('public.producto')->with(
                'doc_no_disponible',
                'La documentación técnica no está disponible temporalmente. Intente nuevamente más tarde.'
            );
        }

        return redirect()->away($url);
    }

    private function urlDocumentacionVigente(): ?string
    {
        try {
            $registro = $this->db->query(Contenido::class)
                ->where('seccion', self::SECCION_DOCUMENTACION)
                ->where('activo', true)
                ->orderBy('id_contenido', 'desc')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }

        $url = $registro->enlace ?? env('DOCS_CONECTORES_URL');

        return ($url && $url !== '#') ? $url : null;
    }

    public function colaboradores()
    {
        $colaboradores = $this->db->query(Colaborador::class)->orderBy('nombre_comercial')->get();

        return view('public.colaboradores', compact('colaboradores'));
    }
}
