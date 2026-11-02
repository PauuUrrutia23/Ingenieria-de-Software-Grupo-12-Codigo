<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Services\Historial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminGestionController extends Controller
{
    public function index()
    {
        $administradores = Administrador::query()->orderBy('id_admin')->paginate(15);
        return view('admin.administradores.index', compact('administradores'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'correo' => ['required', 'email:rfc', 'max:150', 'regex:/@ingecon\.cl$/i', Rule::unique('administradores', 'correo')],
            'password' => ['required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'],
        ], [
            'correo.regex' => 'El correo debe ser un Correo Institucional (@ingecon.cl).',
        ]);

        try {
            DB::transaction(function () use ($datos) {
                $cuenta = Administrador::create([
                    'correo' => mb_strtolower($datos['correo']),
                    'password_hash' => Hash::make($datos['password']),
                    'rol' => 'admin',
                    'activo' => true,
                    'intentos_fallidos' => 0,
                ]);
                Historial::registrar('creacion', 'administradores', $cuenta->id_admin, null, $cuenta->getAttributes());
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['correo' => 'No se pudo crear el administrador.']);
        }

        return redirect()->route('admin.administradores.index')->with('success', 'Administrador creado.');
    }

    // CU 59.1: cada Casilla de Selección se guarda al marcarla o desmarcarla.
    public function actualizarPermiso(Request $request, int $administrador)
    {
        $datos = $request->validate([
            'permiso' => ['required', Rule::in(Administrador::PERMISOS)],
            'activo' => ['required', 'boolean'],
        ]);

        try {
            $objetivo = Administrador::query()->find($administrador);
            if (!$objetivo) {
                return redirect()->route('admin.administradores.index')
                    ->withErrors(['administrador' => 'El administrador ya no está disponible.']);
            }
            if ($objetivo->rol === 'admin_jefe') {
                return back()->withErrors(['administrador' => 'El Administrador jefe tiene todos los permisos; no se modifican.']);
            }
            DB::transaction(function () use ($objetivo, $datos) {
                $antes = $objetivo->getAttributes();
                $objetivo->update(['perm_' . $datos['permiso'] => (bool) $datos['activo']]);
                Historial::registrar('asignacion_permiso', 'administradores', $objetivo->id_admin, $antes, $objetivo->getAttributes());
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['administrador' => 'No se pudo actualizar el permiso.']);
        }

        return redirect()->route('admin.administradores.index')->with('success', 'Permiso actualizado.');
    }

    public function destroy(int $administrador)
    {
        if ($administrador === Auth::id()) {
            return back()->withErrors(['administrador' => 'No puede eliminar su propia cuenta.']);
        }

        try {
            $eliminado = DB::transaction(function () use ($administrador) {
                $objetivo = Administrador::query()->whereKey($administrador)->lockForUpdate()->first();
                if (!$objetivo) return false;
                if ($objetivo->rol === 'admin_jefe') return null;

                DB::table('sesiones')->where('id_admin', $administrador)->update(['estado' => 'cerrada']);
                DB::table('sesiones')->where('id_admin', $administrador)->delete();
                DB::table('recuperaciones_password')->where('id_admin', $administrador)->delete();
                DB::table('consultas')->where('id_admin_responsable', $administrador)
                    ->update(['id_admin_responsable' => null]);
                foreach (['proyectos', 'certificados', 'contenidos', 'colaboradores', 'productos'] as $tabla) {
                    DB::table($tabla)->where('id_admin', $administrador)
                        ->update(['id_admin' => Auth::id()]);
                }
                $objetivo->delete();
                Historial::registrar('eliminacion', 'administradores', $administrador, $objetivo->getAttributes());
                return true;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['administrador' => 'No se pudo eliminar el administrador.']);
        }

        if ($eliminado === false) {
            return redirect()->route('admin.administradores.index')
                ->withErrors(['administrador' => 'El administrador ya no está disponible.']);
        }
        if ($eliminado === null) {
            return back()->withErrors(['administrador' => 'No se puede eliminar una cuenta de administrador jefe.']);
        }
        return redirect()->route('admin.administradores.index')->with('success', 'Administrador eliminado.');
    }
}
