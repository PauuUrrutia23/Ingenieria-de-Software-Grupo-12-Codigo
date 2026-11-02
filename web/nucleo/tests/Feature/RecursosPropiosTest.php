<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RNF22: las páginas no cargan fuentes ni librerías desde servidores de terceros.
class RecursosPropiosTest extends TestCase
{
    use RefreshDatabase;

    // Hosts externos de los que la página carga recursos (no cuenta los enlaces <a>, que solo se siguen con un clic).
    private function hostsExternosCargados(string $html): array
    {
        preg_match_all('/<(?:script|img|iframe|source)\b[^>]*\bsrc="([^"]+)"|<link\b[^>]*\bhref="([^"]+)"/i', $html, $m);
        $hosts = [];
        foreach (array_filter(array_merge($m[1], $m[2])) as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && !in_array($host, ['localhost', '127.0.0.1'], true)) {
                $hosts[] = $host;
            }
        }
        return array_values(array_unique($hosts));
    }

    public function test_la_pagina_de_inicio_no_carga_recursos_de_terceros()
    {
        $this->assertSame([], $this->hostsExternosCargados($this->get('/')->assertOk()->getContent()));
    }

    public function test_proyectos_login_y_panel_no_cargan_recursos_de_terceros()
    {
        $this->assertSame([], $this->hostsExternosCargados($this->get('/proyectos')->assertOk()->getContent()), 'Proyectos');
        $this->assertSame([], $this->hostsExternosCargados($this->get('/login')->assertOk()->getContent()), 'Login');

        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $this->assertSame([], $this->hostsExternosCargados($this->loginAdmin($jefe)->get('/admin/dashboard')->assertOk()->getContent()), 'Panel');
    }

    public function test_el_mapa_solo_pide_mosaicos_a_openstreetmap()
    {
        $html = $this->get('/proyectos')->assertOk()->getContent();

        preg_match_all('/https?:\/\/([a-z0-9.-]+)\/[^\'"\s]*\{z\}/i', $html, $m);
        $this->assertSame(['tile.openstreetmap.org'], array_values(array_unique($m[1])));
    }
}
