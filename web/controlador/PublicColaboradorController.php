<?php
namespace App\Http\Controllers;

use App\Models\Colaborador;

class PublicColaboradorController extends Controller
{
    /** RF11 / CU 11.2 - página pública de colaboradores (logos y nombres desde BD) */
    public function index()
    {
        $colaboradores = Colaborador::orderBy('nombre_comercial')->get();

        return view('public.colaboradores', compact('colaboradores'));
    }
}
