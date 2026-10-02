<?php

namespace Tests;

use App\Models\Sesion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Autentica a un administrador con una sesión persistida válida (RF27/RF32).
     * Equivale a pasar por el login real sin depender del id de sesión volátil:
     * planta el token en los datos de sesión (que sí persisten entre peticiones)
     * y crea la fila "sesiones" que CheckAdminSession exige.
     */
    protected function loginAdmin($admin, ?string $guard = null)
    {
        $token = Str::random(48);
        $this->app['session.store']->put('sesion_ingecon', $token);

        Sesion::create([
            'id_admin' => $admin->id_admin,
            'token_hash' => hash('sha256', $token),
            'fecha_inicio' => Carbon::now(),
            'estado' => 'activa',
        ]);

        return $this->actingAs($admin, $guard);
    }
}
