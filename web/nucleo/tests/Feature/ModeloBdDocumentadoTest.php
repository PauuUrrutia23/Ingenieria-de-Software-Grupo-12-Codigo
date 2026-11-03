<?php

namespace Tests\Feature;

use App\Models\Colaborador;
use App\Models\Contenido;
use App\Models\Producto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Vista Lógica de BD (carpeta_final): el esquema migrado coincide con el modelo documentado.
class ModeloBdDocumentadoTest extends TestCase
{
    use RefreshDatabase;

    // COLABORADOR, CONTENIDO, PROYECTO y PRODUCTO tienen id_admin como "FK NULL".
    public function test_id_admin_acepta_null_donde_el_modelo_lo_marca_fk_null()
    {
        Proyecto::factory()->create(['id_admin' => null]);
        Colaborador::factory()->create(['id_admin' => null]);
        Contenido::create(['seccion' => 'faq', 'titulo' => '¿Despachan?', 'cuerpo' => 'Sí.', 'orden' => 0, 'activo' => true, 'id_admin' => null]);
        Producto::create(['nombre' => 'Viga laminada', 'descripcion' => 'Madera.', 'orden' => 0, 'activo' => true, 'id_admin' => null]);

        $this->assertSame(1, Proyecto::whereNull('id_admin')->count());
        $this->assertSame(1, Colaborador::whereNull('id_admin')->count());
        $this->assertSame(1, Contenido::whereNull('id_admin')->count());
        $this->assertSame(1, Producto::whereNull('id_admin')->count());
    }

    // El modelo no tiene tokens de API: el sitio no expone una API.
    public function test_no_existe_la_tabla_de_tokens_de_api()
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens'));
    }
}
