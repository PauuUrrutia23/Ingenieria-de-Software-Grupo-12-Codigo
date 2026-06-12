<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
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
        try {
            $certificados = $this->db->listarCertificadosActivos();
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los certificados para la página principal', [
                'error' => $e->getMessage(),
            ]);
            $certificados = collect();
        }

        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision?->format('d/m/Y') ?? '—';
            return $cert;
        });

        try {
            $colaboradores = $this->db->listarColaboradores();
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los colaboradores para la página principal', [
                'error' => $e->getMessage(),
            ]);
            $colaboradores = collect();
        }

        $this->procesarLogotipos($colaboradores);

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
        try {
            $colaboradores = $this->db->listarColaboradores();
        } catch (QueryException $e) {
            Log::error('BD: No se pudieron listar los colaboradores para la página dedicada', [
                'error' => $e->getMessage(),
            ]);
            $colaboradores = collect();
        }

        $this->procesarLogotipos($colaboradores);

        return view('public.colaboradores-pagina', compact('colaboradores'));
    }

    private function procesarLogotipos($colaboradores): void
    {
        $colaboradores->transform(function (Colaborador $c) {
            $raw = $c->getRawOriginal('logotipo');
            $b64 = null;

            if ($raw !== null) {
                $binary = is_resource($raw) ? stream_get_contents($raw) : $raw;

                if ($binary && strlen($binary) > 0) {
                    $mime = $c->getRawOriginal('tipo_mime') ?: 'image/png';
                    $b64 = "data:{$mime};base64," . base64_encode($binary);
                }
            }

            $c->setAttribute('logo_b64', $b64);

            return $c;
        });
    }
}
