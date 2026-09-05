<?php
namespace App\Http\Controllers;

use App\Models\Colaborador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradores = Colaborador::orderBy('id_colaborador', 'desc')->paginate(15);
        return view('admin.colaboradores.index', compact('colaboradores'));
    }

    public function create()
    {
        return view('admin.colaboradores.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_comercial' => 'required|string|max:120',
            'logotipo' => 'required|image|max:500',
        ]);

        $data['id_admin'] = Auth::id();
        $data['logotipo'] = $request->file('logotipo')->store('colaboradores', 'public');
        $data['tipo_mime'] = $request->file('logotipo')->getMimeType();

        Colaborador::create($data);
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor creado.');
    }

    public function edit(Colaborador $colaborador)
    {
        return view('admin.colaboradores.edit', compact('colaborador'));
    }

    public function update(Request $request, Colaborador $colaborador)
    {
        $data = $request->validate([
            'nombre_comercial' => 'required|string|max:120',
            'logotipo' => 'nullable|image|max:500',
        ]);

        if ($request->hasFile('logotipo')) {
            Storage::disk('public')->delete($colaborador->logotipo);
            $data['logotipo'] = $request->file('logotipo')->store('colaboradores', 'public');
            $data['tipo_mime'] = $request->file('logotipo')->getMimeType();
        }

        $colaborador->update($data);
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Colaborador $colaborador)
    {
        Storage::disk('public')->delete($colaborador->logotipo);
        $colaborador->delete();
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor eliminado.');
    }
}