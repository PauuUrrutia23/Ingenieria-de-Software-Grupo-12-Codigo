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

        // RF13/RF14 — La página de inicio consulta también los colaboradores en
        // el mismo render server-side, de modo que las secciones a las que
        // apunta la Barra de Navegación Fija se construyen con datos obtenidos
        // desde la Base de Datos (los proyectos se cargan en la galería).
        $colaboradores = $this->db->listarColaboradores();

        return view('public.index', compact('certificados', 'colaboradores'));
    }

    /**
     * RF12 — Página dedicada de Colaboradores (acceso desde el Menú Lateral).
     *
     * Renderiza la PÁGINA COMPLETA de colaboradores. El contenido (logotipos
     * y nombres comerciales) se obtiene desde la Base de Datos en el mismo
     * request GET /colaboradores a través del DBRouterController.
     *
     * @return View
     */
    public function colaboradores(): View
    {
        $colaboradores = $this->db->listarColaboradores();

        return view('public.colaboradores-pagina', compact('colaboradores'));
    }
}
