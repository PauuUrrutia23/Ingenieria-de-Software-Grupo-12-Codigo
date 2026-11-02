<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// RNF20: aviso de las Certificaciones cuya Fecha de Vencimiento se cumple dentro de los próximos 30 días.
class AvisoVencimientoCertificadosTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_avisa_solo_las_que_vencen_entre_hoy_y_30_dias()
    {
        Carbon::setTestNow('2026-11-02 10:00:00');
        $admin = Administrador::factory()->editorContenido()->create();
        foreach ([
            ['NCh 819', '2026-11-02'],      // vence hoy
            ['NCh 1198', '2026-12-02'],     // en 30 días
            ['ANSI TPI', '2026-12-03'],     // en 31 días
            ['ISO 9001', null],             // sin vencimiento
            ['NCh 1207', '2026-11-01'],     // venció ayer
        ] as [$nombre, $vence]) {
            Certificado::create(['nombre' => $nombre, 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-01',
                'fecha_vencimiento' => $vence, 'id_admin' => $admin->id_admin]);
        }

        $html = $this->loginAdmin($admin)->get(route('admin.certificados.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div[^>]*data-aviso-vencimiento[^>]*>(.*?)<\/div>/s', $html);
        preg_match('/<div[^>]*data-aviso-vencimiento[^>]*>(.*?)<\/div>/s', $html, $aviso);
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($aviso[1])));
        $this->assertStringContainsString('NCh 819 vence el 02/11/2026', $texto);
        $this->assertStringContainsString('NCh 1198 vence el 02/12/2026', $texto);
        foreach (['ANSI TPI', 'ISO 9001', 'NCh 1207'] as $fuera) {
            $this->assertStringNotContainsString($fuera, $texto);
        }
    }

    public function test_sin_certificados_por_vencer_no_hay_aviso()
    {
        $admin = Administrador::factory()->editorContenido()->create();
        Certificado::create(['nombre' => 'ISO 9001', 'organismo' => 'Bureau', 'fecha_emision' => '2024-01-01', 'id_admin' => $admin->id_admin]);

        $this->loginAdmin($admin)->get(route('admin.certificados.index'))->assertOk()
            ->assertDontSee('data-aviso-vencimiento', false);
    }
}
