<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\HistorialCambio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitioEnConstruccionTest extends TestCase
{
    use RefreshDatabase;

    private function jefe(): Administrador
    {
        return Administrador::factory()->create(['rol' => 'admin_jefe']);
    }

    private function cambiarInterruptor(Administrador $admin, bool $enConstruccion)
    {
        return $this->loginAdmin($admin)->patch(route('admin.sitio.construccion'), ['activo' => $enConstruccion ? '1' : '0']);
    }

    public function test_en_construccion_el_visitante_ve_la_pagina_en_construccion()
    {
        $this->cambiarInterruptor($this->jefe(), true)->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/')->assertOk()
            ->assertSee('Bienvenido a Ingecon Ltda.')
            ->assertSee('img/en-construccion.png', false)
            ->assertDontSee('Industrialización de la madera');
    }

    public function test_en_construccion_ninguna_pagina_publica_muestra_contenido_ni_recibe_consultas()
    {
        $this->cambiarInterruptor($this->jefe(), true);
        auth()->logout();

        foreach (['/proyectos', '/producto', '/certificaciones', '/colaboradores', '/terminos'] as $pagina) {
            $this->get($pagina)->assertOk()->assertSee('Bienvenido a Ingecon Ltda.');
        }

        $this->post(route('contacto.store'), [
            'nombre' => 'Ana Pérez',
            'correo' => 'ana@example.com',
            'mensaje' => 'Quiero una cotización.',
        ])->assertSee('Bienvenido a Ingecon Ltda.');
        $this->assertSame(0, Consulta::count());
    }

    public function test_en_construccion_el_login_funciona_y_un_administrador_ve_el_sitio_real()
    {
        $jefe = $this->jefe();
        $this->cambiarInterruptor($jefe, true);

        $this->get('/')->assertOk()->assertSee('Industrialización de la madera');

        auth()->logout();
        $this->get(route('login'))->assertOk()->assertDontSee('Bienvenido a Ingecon Ltda.');
    }

    public function test_solo_el_jefe_cambia_el_interruptor_y_queda_en_el_historial()
    {
        $otro = Administrador::factory()->create(['rol' => 'admin', 'perm_editar_contenido' => true]);
        $this->cambiarInterruptor($otro, true)->assertForbidden();
        auth()->logout();
        $this->get('/')->assertSee('Industrialización de la madera');

        $jefe = $this->jefe();
        $this->cambiarInterruptor($jefe, true);
        $this->cambiarInterruptor($jefe, false);
        auth()->logout();
        $this->get('/')->assertSee('Industrialización de la madera');

        $registros = HistorialCambio::where('modulo', 'contenido')->orderBy('id_historial')->get();
        $this->assertSame(['creacion', 'modificacion'], $registros->pluck('accion')->all());
        $this->assertSame($jefe->correo, $registros->last()->correo_responsable);
    }

    public function test_si_nunca_se_activo_el_sitio_esta_publicado()
    {
        $this->get('/')->assertOk()->assertSee('Industrialización de la madera');
    }
}
