<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Services\Historial;
use App\Services\SitioEnConstruccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminGestionController extends Controller
{
    public function __construct(private DBRouterController $db)
    {
    }

    public function index()
    {
        $administradores = $this->cargarListado(fn () => $this->db->query(Administrador::class)->orderBy('id_admin')->paginate(15), $this->paginaVacia(15));
        return view('admin.administradores.index', compact('administradores'));
    }

    public function store(Request $request)
    {
        $dominio = config('app.dominio_correo');
        $datos = $request->validate([
            'correo' => ['required', 'email:rfc', 'not_regex:/[\r\n]/', 'max:150', 'regex:/@' . preg_quote($dominio, '/') . '$/i', Rule::unique('administradores', 'correo')],
            'password' => ['required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'],
        ], [
            'correo.regex' => "El correo debe ser un Correo Institucional (@{$dominio}).",
            'correo.not_regex' => 'Ingrese un correo electrónico válido.',
        ]);

        try {
            $this->db->transaccion(function () use ($datos) {
                $cuenta = $this->db->create(Administrador::class, [
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
    public function cambiarSitioEnConstruccion(Request $request)
    {
        $datos = $request->validate(['activo' => ['required', 'boolean']]);
        $activo = (bool) $datos['activo'];

        try {
            $this->db->transaccion(function () use ($activo) {
                [$registro, $antes] = SitioEnConstruccion::cambiar($activo);
                Historial::registrar($antes ? 'modificacion' : 'creacion', 'contenido', $registro->id_contenido, $antes, $registro->getAttributes());
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['sitio' => 'No se pudo cambiar el estado del sitio.']);
        }

        return back()->with('success', $activo
            ? 'El sitio quedó en construcción: los visitantes ven la página de espera.'
            : 'El sitio quedó publicado: los visitantes ya ven el contenido.');
    }

    public function actualizarPermiso(Request $request, int $administrador)
    {
        $datos = $request->validate([
            'permiso' => ['required', Rule::in(Administrador::PERMISOS)],
            'activo' => ['required', 'boolean'],
        ]);

        try {
            $objetivo = $this->db->query(Administrador::class)->find($administrador);
            if (!$objetivo) {
                return redirect()->route('admin.administradores.index')
                    ->withErrors(['administrador' => 'El administrador ya no está disponible.']);
            }
            if ($objetivo->rol === 'admin_jefe') {
                return back()->withErrors(['administrador' => 'El Administrador jefe tiene todos los permisos; no se modifican.']);
            }
            $this->db->transaccion(function () use ($objetivo, $datos) {
                $antes = $objetivo->getAttributes();
                $this->db->update($objetivo, ['perm_' . $datos['permiso'] => (bool) $datos['activo']]);
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
            $eliminado = $this->db->transaccion(function () use ($administrador) {
                $objetivo = $this->db->query(Administrador::class)->whereKey($administrador)->lockForUpdate()->first();
                if (!$objetivo) return false;
                if ($objetivo->rol === 'admin_jefe') return null;

                $this->db->tabla('sesiones')->where('id_admin', $administrador)->update(['estado' => 'cerrada']);
                $this->db->tabla('sesiones')->where('id_admin', $administrador)->delete();
                $this->db->tabla('recuperaciones_password')->where('id_admin', $administrador)->delete();
                $this->db->tabla('consultas')->where('id_admin_responsable', $administrador)
                    ->update(['id_admin_responsable' => null]);
                foreach (['proyectos', 'certificados', 'contenidos', 'colaboradores', 'productos'] as $tabla) {
                    $this->db->tabla($tabla)->where('id_admin', $administrador)
                        ->update(['id_admin' => Auth::id()]);
                }
                $this->db->delete($objetivo);
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
