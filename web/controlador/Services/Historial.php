<?php

namespace App\Services;

use App\Http\Controllers\DBRouterController;
use App\Models\HistorialCambio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

// RF15 / CU 15.4: registra una acción en el Historial de Cambios. Se llama dentro de la misma
// transacción que la acción: si el registro falla, la acción completa se cancela (Excepción 4).
class Historial
{
    // Campos técnicos y secretos que nunca se guardan.
    private const EXCLUIDOS = ['created_at', 'updated_at', 'password_hash', 'tipo_mime', 'id_admin', 'intentos_fallidos', 'bloqueado_hasta'];

    public static function registrar(string $accion, string $modulo, ?int $idRegistro = null, ?array $antes = null, ?array $despues = null): void
    {
        $admin = Auth::user();
        // Excepción 3: de una Consulta solo se guarda su identificador, nunca el mensaje ni los datos del Visitante.
        [$antes, $despues] = $modulo === 'consultas' ? [null, null] : self::valores($antes, $despues);

        app(DBRouterController::class)->create(HistorialCambio::class, [
            'fecha_hora' => Carbon::now(),
            'id_admin' => $admin->id_admin,
            'correo_responsable' => $admin->correo,
            'accion' => $accion,
            'modulo' => $modulo,
            'valores_anteriores' => $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            'valores_nuevos' => $despues ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
            'id_registro_afectado' => $idRegistro,
        ]);
    }

    // En una modificación quedan solo los campos que cambiaron.
    private static function valores(?array $antes, ?array $despues): array
    {
        // Valores normalizados como vienen de la base, para comparar sin falsos cambios: booleanos como 0/1
        // y fechas a medianoche como día (MySQL entrega DATE como "2024-01-15" y el modelo como "2024-01-15 00:00:00").
        $normalizar = fn ($x) => is_bool($x) ? (int) $x
            : (is_string($x) ? preg_replace('/^(\d{4}-\d{2}-\d{2}) 00:00:00$/', '$1', $x) : $x);
        $limpiar = fn (?array $v) => $v === null ? null
            : array_map($normalizar, array_diff_key($v, array_flip(self::EXCLUIDOS)));
        $antes = $limpiar($antes);
        $despues = $limpiar($despues);

        if ($antes !== null && $despues !== null) {
            $campos = array_keys(array_filter($despues, fn ($valor, $campo) => ($antes[$campo] ?? null) != $valor, ARRAY_FILTER_USE_BOTH));
            $antes = array_intersect_key($antes, array_flip($campos));
            $despues = array_intersect_key($despues, array_flip($campos));
        }

        return [$antes ?: null, $despues ?: null];
    }
}
