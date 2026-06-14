<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Colaborador;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InstitucionalCtrl extends Controller
{
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Página principal pública. Precarga certificados activos y colaboradores;
     * los proyectos se cargan aparte vía Alpine.js.
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
     * Página dedicada de colaboradores, accesible desde el menú lateral.
     * Renderiza logotipos y nombres comerciales server-side.
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
