<?php

namespace Tests;

use App\Models\Sesion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

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
