<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\View\View;

class InstitucionalCtrl extends Controller
{
    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Resuelto automáticamente por el contenedor de Laravel.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Renderiza la página principal pública de Ingecon.
     * Precarga certificados activos para la sección #certificaciones.
     * Los proyectos se cargan vía Alpine.js (06_galeria_proyectos.md).
     *
     * @return View
     */
    public function index(): View
    {
        // Mismo origen de datos que ProyectoController@certificaciones:
        // el router centraliza la query (resuelve la duplicación DRY).
        $certificados = $this->db->listarCertificadosActivos();

        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision?->format('d/m/Y') ?? '—';
            return $cert;
        });

        return view('public.index', compact('certificados'));
    }
}
