<?php

namespace App\Services;

use App\Http\Controllers\DBRouterController;
use App\Models\Contenido;
use Illuminate\Support\Facades\Auth;

// Subida al hosting: interruptor del sitio en construcción.
class SitioEnConstruccion
{
    public const SECCION = 'sitio_construccion';

    public static function registro(): ?Contenido
    {
        return app(DBRouterController::class)->query(Contenido::class)
            ->where('seccion', self::SECCION)->first();
    }

    public static function activo(): bool
    {
        return (bool) self::registro()?->activo;
    }

    public static function cambiar(bool $activo): array
    {
        $db = app(DBRouterController::class);
        $registro = self::registro();
        $antes = $registro?->getAttributes();

        if ($registro) {
            $db->update($registro, ['activo' => $activo]);
        } else {
            $registro = $db->create(Contenido::class, [
                'seccion' => self::SECCION,
                'titulo' => 'Sitio en construcción',
                'activo' => $activo,
                'id_admin' => Auth::id(),
            ]);
        }

        return [$registro, $antes];
    }
}
