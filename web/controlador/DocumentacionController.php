<?php
namespace App\Http\Controllers;

use App\Models\Contenido;

/**
 * CU 10.1 - Accediendo a documentación técnica (RF10).
 *
 * El enlace del encabezado no apunta directo a un dominio externo: pide la URL
 * vigente al Controlador, que la recupera desde BD (tabla `contenidos`, sección
 * "documentacion") y recién ahí redirige. Así la URL se puede cambiar sin tocar
 * código y se pueden manejar las excepciones del caso de uso.
 */
class DocumentacionController extends Controller
{
    public const SECCION = 'documentacion';

    /** Resuelve la URL vigente; null si no hay ninguna registrada. */
    public static function urlVigente(): ?string
    {
        try {
            $registro = Contenido::where('seccion', self::SECCION)
                ->where('activo', true)
                ->orderBy('orden')
                ->first();
        } catch (\Throwable $e) {
            // Excepción 2: falla la consulta a BD. No se propaga el error técnico (RNF10).
            return null;
        }

        $url = $registro->enlace ?? env('DOCS_CONECTORES_URL');

        return ($url && $url !== '#') ? $url : null;
    }

    public function conectores()
    {
        $url = self::urlVigente();

        // Excepción 2: URL registrada en BD no disponible o eliminada.
        if (!$url) {
            return redirect()->route('public.producto')->with(
                'doc_no_disponible',
                'La documentación técnica no está disponible temporalmente. Intente nuevamente más tarde.'
            );
        }

        return redirect()->away($url);
    }
}
