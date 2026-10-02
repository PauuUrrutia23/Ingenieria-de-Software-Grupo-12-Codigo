<?php

namespace Tests\Feature;

use App\Http\Controllers\DBRouterController;
use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * RF02 / CU2.1 — FASE 16: la URL vigente de Términos se resuelve desde BD
 * (Contenido 'terminos', último activo) sin depender de una URL hardcodeada.
 */
class PublicNavigationRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Administrador
    {
        return Administrador::firstOrCreate(
            ['correo' => 'jefe@ingecon.cl'],
            [
                'password_hash' => Hash::make('ClaveValida1!'),
                'rol' => 'admin_jefe',
                'activo' => true,
            ]
        );
    }

    private function terminos(?string $enlace, bool $activo): Contenido
    {
        return Contenido::create([
            'seccion' => 'terminos',
            'titulo' => 'Términos y Condiciones',
            'cuerpo' => 'Documento vigente.',
            'enlace' => $enlace,
            'activo' => $activo,
            'orden' => 0,
            'id_admin' => $this->admin()->id_admin,
        ]);
    }

    public function test_registro_activo_retorna_su_url()
    {
        $this->terminos('https://ejemplo.cl/terminos-v2', true);

        $this->get('/terminos')
            ->assertStatus(200)
            ->assertViewHas('terminosUrl', 'https://ejemplo.cl/terminos-v2');
    }

    public function test_registro_inactivo_no_provee_url()
    {
        $this->terminos('https://ejemplo.cl/terminos-viejo', false);

        $this->get('/terminos')->assertStatus(200)->assertViewHas('terminosUrl', null);
    }

    public function test_sin_registro_no_provee_url()
    {
        $this->get('/terminos')->assertStatus(200)->assertViewHas('terminosUrl', null);
    }

    public function test_el_mas_recente_gana_cuando_hay_varios_activos()
    {
        $this->terminos('https://ejemplo.cl/viejo', true);
        $this->terminos('https://ejemplo.cl/nuevo', true);

        $this->get('/terminos')->assertViewHas('terminosUrl', 'https://ejemplo.cl/nuevo');
    }

    public function test_fallo_de_bd_no_lanza_500_y_devuelve_estado_controlado()
    {
        $this->app->instance(DBRouterController::class, new class extends DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                throw new \RuntimeException('BD no disponible');
            }
        });

        $this->get('/terminos')
            ->assertStatus(200)
            ->assertViewHas('terminosUrl', null);
    }

    // --- FASE 17: footer conectado al flujo dinámico (sin '#' silencioso) ---

    public function test_footer_terminos_abre_en_nuevapestana()
    {
        $this->get('/')->assertStatus(200)->assertSee('target="_blank"', false);
    }

    public function test_footer_usa_la_url_dinamica_desde_bd()
    {
        $this->terminos('https://docs.ingecon.cl/terminos-oficial', true);

        $this->get('/')->assertStatus(200)
            ->assertSee('href="https://docs.ingecon.cl/terminos-oficial"', false);
    }

    public function test_footer_sin_documento_apunta_a_la_pagina_terminos_sin_hastago()
    {
        // Sin documento configurado, el enlace usa la página interna (route('terminos')), no '#'.
        $esperado = 'href="' . route('terminos') . '"';
        $this->get('/')->assertStatus(200)->assertSee($esperado, false);
    }

    // --- FASE 27: RF03 enlace de ubicación (Google Maps) resuelto desde BD ---

    private function ubicacion(?string $enlace, bool $activo): Contenido
    {
        return Contenido::create([
            'seccion' => 'ubicacion',
            'titulo' => 'Ubicación',
            'cuerpo' => 'Planta Talca',
            'enlace' => $enlace,
            'activo' => $activo,
            'orden' => 0,
            'id_admin' => $this->admin()->id_admin,
        ]);
    }

    public function test_ubicacion_activa_llega_como_url_a_home()
    {
        $this->ubicacion('https://maps.google.com/?q=Talca', true);

        $this->get('/')->assertStatus(200)->assertViewHas('ubicacionUrl', 'https://maps.google.com/?q=Talca');
    }

    public function test_ubicacion_sin_registro_es_null()
    {
        $this->get('/')->assertStatus(200)->assertViewHas('ubicacionUrl', null);
    }

    public function test_ubicacion_inactiva_es_null()
    {
        $this->ubicacion('https://maps.google.com/?q=Viejo', false);

        $this->get('/')->assertStatus(200)->assertViewHas('ubicacionUrl', null);
    }

    // --- FASE 28: RF03 mostrar ubicación en el footer ---

    public function test_footer_muestra_ubicacion_en_nueva_pestanha()
    {
        $this->ubicacion('https://maps.google.com/?q=Talca', true);

        $respuesta = $this->get('/')->assertStatus(200);
        $respuesta->assertSee('href="https://maps.google.com/?q=Talca"', false);
        $respuesta->assertSee('>Ubicación</a>', false);
    }

    public function test_footer_no_muestra_ubicacion_sin_url()
    {
        $this->get('/')->assertStatus(200)->assertDontSee('>Ubicación</a>', false);
    }
}
