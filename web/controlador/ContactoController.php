<?php
namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Visitante;
use App\Rules\DominioCorreoValido;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * ContactoController («Control») — Diagrama de Componentes: "Formularios y consultas".
 */
class ContactoController extends Controller
{
    // CU 1.1, Excepción 3: máximo 5 consultas pendientes por visitante en 24h.
    private const LIMITE_CONSULTAS_24H = 5;

    public function __construct(
        private DBRouterController $db,
        private NotificationService $notificaciones,
    ) {
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'apellido' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\s]+$/u'],
            // CU 1.1 Excepción 5 (formato) y Excepción 4 (dominio inexistente).
            'email' => ['required', 'email:rfc', 'max:150', new DominioCorreoValido()],
            // DS-42: alfanumérico, 10 a 1000 caracteres.
            'mensaje' => ['required', 'string', 'min:10', 'max:1000'],
            'acepta_terminos' => ['required', 'accepted'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido.regex' => 'El apellido solo puede contener letras y espacios.',
            'mensaje.min' => 'El mensaje debe tener al menos 10 caracteres.',
            'mensaje.max' => 'El mensaje no puede superar los 1000 caracteres.',
            'email.email' => 'Ingrese un correo electrónico válido.',
        ]);

        // CU 1.1, Excepción 3: bloquear si el visitante ya tiene 5+ consultas
        // pendientes registradas en las últimas 24 horas.
        $consultasRecientes = $this->db->query(Consulta::class)
            ->whereHas('visitante', function ($q) use ($request) {
                $q->where('email', $request->email);
            })
            ->where('estado', 'pendiente')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        if ($consultasRecientes >= self::LIMITE_CONSULTAS_24H) {
            return back()->withErrors([
                'mensaje' => 'Alcanzó el límite de ' . self::LIMITE_CONSULTAS_24H . ' consultas en las últimas 24 horas. Por favor, intente nuevamente más tarde.',
            ])->withInput();
        }

        // CU 1.1 Excepción 6 / CU 9.1 Excepción 1: si la BD no logra registrar la
        // Consulta, se informa en lenguaje claro (RNF10) sin exponer el error técnico.
        try {
            $visitante = Visitante::firstOrCreate(
                ['email' => $request->email],
                ['nombre' => $request->nombre, 'apellido' => $request->apellido]
            );

            $consulta = $this->db->create(Consulta::class, [
                'id_visitante' => $visitante->id_visitante,
                'mensaje' => $request->mensaje,
                'fecha_consulta' => Carbon::now()->toDateString(),
                'estado' => 'pendiente',
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'mensaje' => 'El envío no pudo completarse por una congestión temporal del servidor. Por favor, intente nuevamente.',
            ])->withInput();
        }

        // CU 9.1: antes de confirmar al Visitante hay que verificar que la ID retornada
        // por el insert coincida con la que quedó efectivamente registrada en BD.
        // Excepción 1: la BD no logró registrar la Consulta.
        // Excepción 2: la ID no coincide → no se muestra confirmación, se pide reintentar.
        $registrada = $this->db->find(Consulta::class, $consulta->id_consulta);

        if (!$registrada || $registrada->id_consulta !== $consulta->id_consulta) {
            return back()->withErrors([
                'mensaje' => 'El envío no pudo completarse. Por favor, intente nuevamente.',
            ])->withInput();
        }

        // La consulta ya quedó registrada: si el correo de acuse falla, no se pierde
        // el registro ni se le muestra un error al Visitante (RNF10).
        $this->notificaciones->notificarConsultaRecibida($visitante->email, $consulta);

        return redirect('/#contacto')
            ->with('contacto_success', 'Mensaje enviado correctamente. Le hemos enviado un correo de confirmación.')
            ->with('consulta_id', $registrada->id_consulta);
    }
}
