<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\View\View;

class InstitucionalCtrl extends Controller
{
    /**
     * Renderiza la página principal pública de Ingecon.
     * Precarga certificados activos para la sección #certificaciones.
     * Los proyectos se cargan vía Alpine.js (06_galeria_proyectos.md).
     *
     * @return View
     */
    public function index(): View
    {
        $certificados = Certificado::select([
                'id_certificado',
                'codigo_lote',
                'fecha_emision',
                'estado',
                'id_proyecto',
            ])
            ->where('estado', 'activo')
            ->with(['proyecto' => fn($q) => $q->select(['id_proyecto', 'nombre_obra', 'region'])])
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $certificados->transform(function (Certificado $cert) {
            $cert->fecha_formateada = $cert->fecha_emision?->format('d/m/Y') ?? '—';
            return $cert;
        });

        return view('public.index', compact('certificados'));
    }
}
