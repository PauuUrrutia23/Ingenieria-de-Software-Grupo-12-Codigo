<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function certificado(array $attrs = []): Certificado
    {
        $admin = Administrador::factory()->create();

        return Certificado::create(array_merge([
            'nombre' => 'Madera preservada - Pino radiata',
            'organismo' => 'INN Chile',
            'estado' => 'vigente',
            'id_admin' => $admin->id_admin,
        ], $attrs));
    }

    public function test_preview_devuelve_pdf_inline_con_nombre_seguro()
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificados_pdf/x.pdf', "%PDF-1.4 contenido");
        $cert = $this->certificado(['archivo_pdf' => 'certificados_pdf/x.pdf']);

        $respuesta = $this->get(route('public.certificaciones.preview', $cert));

        $respuesta->assertStatus(200);
        $respuesta->assertHeader('content-type', 'application/pdf');
        $respuesta->assertHeader(
            'content-disposition',
            'inline; filename="madera-preservada-pino-radiata.pdf"'
        );
    }

    public function test_sin_archivo_redirige_con_aviso()
    {
        $cert = $this->certificado(['archivo_pdf' => null]);

        $this->get(route('public.certificaciones.preview', $cert))
            ->assertRedirect(route('public.certificaciones'))
            ->assertSessionHas('doc_no_disponible');
    }

    public function test_archivo_inexistente_redirige_con_aviso()
    {
        Storage::fake('public');
        $cert = $this->certificado(['archivo_pdf' => 'certificados_pdf/no-existe.pdf']);

        $this->get(route('public.certificaciones.preview', $cert))
            ->assertRedirect(route('public.certificaciones'))
            ->assertSessionHas('doc_no_disponible');
    }

    public function test_certificado_no_vigente_no_se_previsualiza()
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificados_pdf/y.pdf', "%PDF-1.4");
        $cert = $this->certificado(['estado' => 'vencido', 'archivo_pdf' => 'certificados_pdf/y.pdf']);

        $this->get(route('public.certificaciones.preview', $cert))
            ->assertRedirect(route('public.certificaciones'))
            ->assertSessionHas('doc_no_disponible');
    }

    public function test_la_lista_enlaza_a_preview_en_nueva_pestanha()
    {
        $cert = $this->certificado(['archivo_pdf' => 'certificados_pdf/a.pdf']);

        $respuesta = $this->get(route('public.certificaciones'))->assertStatus(200);

        $respuesta->assertSee(route('public.certificaciones.preview', $cert), false);
        $respuesta->assertSee('target="_blank"', false);
    }

    public function test_se_oculta_ver_certificado_cuando_no_hay_pdf()
    {
        $this->certificado(['archivo_pdf' => null]);

        $this->get(route('public.certificaciones'))
            ->assertStatus(200)
            ->assertDontSee('Ver certificado');
    }
}
