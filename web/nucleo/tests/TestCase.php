<?php

namespace Tests;

use App\Models\Sesion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private float $inicioPrueba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inicioPrueba = microtime(true);
    }

    // La exportación borra su archivo al enviarlo; en las pruebas la respuesta no se envía,
    // así que se borran aquí los archivos que generó esta prueba.
    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/exports/consultas-*')) ?: [] as $archivo) {
            if (filemtime($archivo) >= floor($this->inicioPrueba)) {
                @unlink($archivo);
            }
        }
        parent::tearDown();
    }

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
