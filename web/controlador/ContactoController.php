<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Visitante;
use App\Http\Requests\StoreConsultaRequest;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContactoController extends Controller
{
    private const LIMITE_CONSULTAS_24H = 5;

    public function __construct(
        private DBRouterController $db,
        private NotificationService $notificaciones,
    ) {
    }

    public function store(StoreConsultaRequest $request)
    {
        $datos = $request->validated();

        try {
            $consultasRecientes = $this->db->query(Consulta::class)
                ->whereHas('visitante', function ($q) use ($datos) {
                    $q->where('email', $datos['email']);
                })
                ->where('estado', 'pendiente')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->count();
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['mensaje' => 'El envío no está disponible temporalmente. Intente nuevamente.']);
        }

        if ($consultasRecientes >= self::LIMITE_CONSULTAS_24H) {
            return back()->withErrors([
                'mensaje' => 'Alcanzó el límite de ' . self::LIMITE_CONSULTAS_24H . ' consultas en las últimas 24 horas. Por favor, intente nuevamente más tarde.',
            ])->withInput();
        }

        try {
            [$visitante, $consulta] = DB::transaction(function () use ($datos) {
                $visitante = $this->db->firstOrCreate(
                    Visitante::class,
                    ['email' => $datos['email']],
                    ['nombre' => $datos['nombre'], 'apellido' => $datos['apellido'] ?? null]
                );
                $consulta = $this->db->create(Consulta::class, [
                    'id_visitante' => $visitante->id_visitante,
                    'mensaje' => $datos['mensaje'],
                    'estado' => 'pendiente',
                    'notificacion_admin_pendiente' => true,
                    'created_at' => Carbon::now(),
                ]);
                return [$visitante, $consulta];
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'mensaje' => 'El envío no pudo completarse por una congestión temporal del servidor. Por favor, intente nuevamente.',
            ])->withInput();
        }

        try {
            $registrada = $this->db->find(Consulta::class, $consulta->id_consulta);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['mensaje' => 'No se pudo confirmar el envío. Intente nuevamente.']);
        }

        if (!$registrada || $registrada->id_consulta !== $consulta->id_consulta) {
            return back()->withErrors([
                'mensaje' => 'El envío no pudo completarse. Por favor, intente nuevamente.',
            ])->withInput();
        }

        $confirmacionEnviada = $this->notificaciones->notificarConsultaRecibida($visitante->email, $consulta);
        $alertaEnviada = $this->notificaciones->notificarNuevaConsultaAdministracion($consulta);
        try {
            $this->db->update($consulta, [
                'notificacion_admin_pendiente' => !$alertaEnviada,
                'notificacion_admin_ultimo_error' => $alertaEnviada ? null : 'Envío pendiente',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect('/#contacto')
            ->with('contacto_success', $confirmacionEnviada
                ? 'Consulta registrada y correo de confirmación enviado.'
                : 'Consulta registrada, pero no fue posible enviar el correo de confirmación.')
            ->with('consulta_id', $registrada->id_consulta);
    }
}
