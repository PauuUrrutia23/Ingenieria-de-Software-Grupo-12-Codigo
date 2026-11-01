<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Colaborador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// RF13, RF45, RF46; CU 13.1, 45.1 y 46.1.
class ColaboradorSitioWebTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = Administrador::factory()->editorContenido()->create();
    }

    private function registrar(array $campos): \Illuminate\Testing\TestResponse
    {
        return $this->loginAdmin($this->admin)->post('/admin/colaboradores', array_merge([
            'nombre_comercial' => 'Maderas del Sur',
            'logotipo' => UploadedFile::fake()->image('logo.png'),
        ], $campos));
    }

    private function detalle(): array
    {
        $id = Colaborador::query()->where('nombre_comercial', 'Maderas del Sur')->value('id_colaborador');
        return $this->loginAdmin($this->admin)->getJson(route('admin.colaboradores.detalle', $id))->assertOk()->json();
    }

    public function test_registrar_con_sitio_web_lo_precarga_al_editar()
    {
        $this->registrar(['sitio_web' => 'https://maderasdelsur.cl'])->assertSessionHasNoErrors();

        $this->assertSame('https://maderasdelsur.cl', $this->detalle()['sitio_web']);
    }

    public function test_sitio_web_invalido_se_rechaza_en_su_campo()
    {
        $this->registrar(['sitio_web' => 'maderas del sur'])->assertSessionHasErrors('sitio_web');
    }

    public function test_editar_cambia_y_valida_el_sitio_web()
    {
        $this->registrar(['sitio_web' => 'https://maderasdelsur.cl']);
        $id = Colaborador::query()->value('id_colaborador');
        $editar = fn (string $url) => $this->loginAdmin($this->admin)
            ->put(route('admin.colaboradores.update', $id), ['nombre_comercial' => 'Maderas del Sur', 'sitio_web' => $url]);

        $editar('https://www.maderasdelsur.cl/productos')->assertSessionHasNoErrors();
        $this->assertSame('https://www.maderasdelsur.cl/productos', $this->detalle()['sitio_web']);

        $editar('no es una url')->assertSessionHasErrors('sitio_web');
        $this->assertSame('https://www.maderasdelsur.cl/productos', $this->detalle()['sitio_web']);
    }

    public function test_los_formularios_ofrecen_el_campo_sitio_web()
    {
        $this->registrar(['sitio_web' => 'https://maderasdelsur.cl']);
        $colaborador = Colaborador::query()->firstOrFail();

        $listado = $this->loginAdmin($this->admin)->get(route('admin.colaboradores.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*id="crear-sitio"[^>]*type="url"[^>]*name="sitio_web"/', $listado);
        $this->assertMatchesRegularExpression('/<input[^>]*id="editar-sitio"[^>]*name="sitio_web"[^>]*x-model="seleccionado.sitio_web"/', $listado);

        $this->loginAdmin($this->admin)->get(route('admin.colaboradores.create'))->assertOk()
            ->assertSee('name="sitio_web"', false);
        $this->loginAdmin($this->admin)->get(route('admin.colaboradores.edit', $colaborador->id_colaborador))->assertOk()
            ->assertSee('value="https://maderasdelsur.cl"', false);
    }

    public function test_el_logo_con_sitio_web_abre_en_nueva_pestana_y_sin_sitio_no_enlaza()
    {
        Colaborador::create(['nombre_comercial' => 'Con Sitio', 'logotipo' => 'img/colaboradores/a.png', 'sitio_web' => 'https://consitio.cl', 'id_admin' => $this->admin->id_admin]);
        Colaborador::create(['nombre_comercial' => 'Sin Sitio', 'logotipo' => 'img/colaboradores/b.png', 'id_admin' => $this->admin->id_admin]);

        foreach (['/', '/colaboradores'] as $pagina) {
            $html = $this->get($pagina)->assertOk()->getContent();

            $this->assertMatchesRegularExpression(
                '/<a[^>]*href="https:\/\/consitio\.cl"[^>]*target="_blank"[^>]*rel="noopener noreferrer"[^>]*>\s*<img[^>]*alt="Con Sitio"/',
                $html, "En $pagina el logo con sitio web no abre en nueva pestaña"
            );
            $this->assertDoesNotMatchRegularExpression('/<a[^>]*>\s*<img[^>]*alt="Sin Sitio"/', $html, "En $pagina el logo sin sitio web quedó enlazado");
        }
    }

    public function test_sitio_web_es_opcional()
    {
        $this->registrar(['sitio_web' => ''])->assertSessionHasNoErrors();

        $this->assertNull($this->detalle()['sitio_web']);
    }
}
