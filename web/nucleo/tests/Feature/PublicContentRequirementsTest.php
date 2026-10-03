<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * FASE 18 — RF10: la ficha de conectores se recupera desde Contenido, sin hardcodear,
 * y la ausencia de bloques o de archivo produce respaldo/placeholder (nunca 500).
 */
class PublicContentRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function adminId(): int
    {
        return Administrador::firstOrCreate(
            ['correo' => 'jefe@ingecon.cl'],
            ['password_hash' => Hash::make('ClaveValida1!'), 'rol' => 'admin_jefe', 'activo' => true]
        )->id_admin;
    }

    private function fila(string $titulo, ?string $cuerpo, ?string $archivo = null, int $orden = 0): void
    {
        Contenido::create([
            'seccion' => 'ficha_conectores',
            'titulo' => $titulo,
            'cuerpo' => $cuerpo,
            'archivo' => $archivo,
            'activo' => true,
            'orden' => $orden,
            'id_admin' => $this->adminId(),
        ]);
    }

    public function test_controlador_recupera_los_campos_de_la_ficha()
    {
        $this->fila('descripcion', 'Texto descriptivo de prueba.', null, 1);
        $this->fila('especificaciones', json_encode([['label' => 'Material', 'value' => 'Acero'], ['label' => 'Espesor', 'value' => '2mm']]), null, 2);
        $this->fila('aplicaciones', json_encode([['icono' => 'home', 'titulo' => 'Cerchas', 'texto' => 'Unión.']]), null, 3);
        $this->fila('imagen_principal', 'Alt de la imagen principal.', 'img/mi-conector.jpg', 4);
        $this->fila('miniaturas', json_encode([['src' => 'img/a.jpg', 'alt' => 'A'], ['src' => 'img/b.jpg', 'alt' => 'B']]), null, 5);

        $this->get('/producto')->assertStatus(200)->assertViewHas('ficha', function (array $ficha) {
            return $ficha['descripcion'] === 'Texto descriptivo de prueba.'
                && count($ficha['especificaciones']) === 2
                && $ficha['especificaciones'][0]['label'] === 'Material'
                && count($ficha['aplicaciones']) === 1
                && $ficha['imagen_principal']['src'] === 'img/mi-conector.jpg'
                && count($ficha['miniaturas']) === 2;
        });
    }

    public function test_ausencia_de_bloques_no_rompe()
    {
        $this->fila('descripcion', 'Sólo descripción.', null, 1);

        $this->get('/producto')->assertStatus(200)->assertViewHas('ficha', function (array $ficha) {
            return $ficha['descripcion'] === 'Sólo descripción.'
                && $ficha['especificaciones'] === []
                && $ficha['aplicaciones'] === []
                && $ficha['miniaturas'] === [];
        });
    }

    public function test_imagen_principal_ausente_produce_placeholder()
    {
        $this->get('/producto')->assertStatus(200)->assertViewHas('ficha', function (array $ficha) {
            return $ficha['imagen_principal']['src'] === 'img/conector-pieza-sola.jpg';
        });
    }

    public function test_sin_fila_de_imagen_o_archivo_vacio_ignora_y_usa_respaldo()
    {
        // imagen_principal presente pero sin archivo -> placeholder, sin romper.
        $this->fila('imagen_principal', 'Solo alt, sin archivo.', null, 4);

        $this->get('/producto')->assertStatus(200)->assertViewHas('ficha', function (array $ficha) {
            return $ficha['imagen_principal']['src'] === 'img/conector-pieza-sola.jpg'
                && $ficha['imagen_principal']['alt'] === 'Solo alt, sin archivo.';
        });
    }

    public function test_la_vista_muestra_la_ficha_desde_bd_no_hardcodeada()
    {
        // El seeder de producto requiere un administrador; se siembra antes.
        $this->seed([
            \Database\Seeders\AdminJefeSeeder::class,
            \Database\Seeders\ProductoContenidoSeeder::class,
        ]);

        $respuesta = $this->get('/producto')->assertStatus(200);

        // Contenido del seeder (fuente BD), no texto fijo de la vista.
        $respuesta->assertSee('dimensionados según el cálculo de cada proyecto', false);
        $respuesta->assertSee('Acero estructural galvanizado', false);
        $respuesta->assertSee('Cerchas de techumbre', false);
        $respuesta->assertSee('img/conector-pieza-sola.jpg', false);
    }

    // --- FASE 29: RF13 nombre comercial visible bajo cada logo de colaborador ---

    public function test_colaborador_muestra_nombre_y_alt_bajo_el_logo()
    {
        \App\Models\Colaborador::create([
            'nombre_comercial' => 'MultiAcero',
            'logotipo' => 'colaboradores/multiacero.png',
            'tipo_mime' => 'image/png',
            'id_admin' => $this->adminId(),
        ]);

        $respuesta = $this->get('/')->assertStatus(200);
        // Nombre visible como texto (span) y como alt de la imagen.
        $respuesta->assertSee('MultiAcero', false);
        $respuesta->assertSee('alt="MultiAcero"', false);
    }

    public function test_estado_vacio_de_proveedores_no_rompe()
    {
        $this->get('/')->assertStatus(200)->assertSee('Pronto publicaremos nuestros colaboradores', false);
    }

    // --- FASE 31: RF14 la FAQ vigente llega a home(); su fallo no rompe la página ---

    public function test_faq_llega_a_la_vista_ordenada()
    {
        foreach ([['P1', 'R1', 2], ['P2', 'R2', 1]] as [$p, $r, $o]) {
            \App\Models\Contenido::create([
                'seccion' => 'faq', 'titulo' => $p, 'cuerpo' => $r,
                'activo' => true, 'orden' => $o, 'id_admin' => $this->adminId(),
            ]);
        }

        $this->get('/')->assertStatus(200)->assertViewHas('faqs', function ($faqs) {
            return $faqs->count() === 2 && $faqs->first()->titulo === 'P2'; // orden asc
        });
    }

    public function test_faq_vacia_es_lista_vacia()
    {
        $this->get('/')->assertStatus(200)->assertViewHas('faqs', fn ($f) => $f->isEmpty());
    }

    public function test_fallo_de_bd_en_contenido_no_rompe_el_home()
    {
        $this->app->instance(\App\Http\Controllers\DBRouterController::class, new class extends \App\Http\Controllers\DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                if ($modelo === \App\Models\Contenido::class) {
                    throw new \RuntimeException('BD de contenidos caída');
                }
                return $modelo::query();
            }
        });

        $this->get('/')->assertStatus(200)->assertViewHas('faqs', fn ($f) => $f->isEmpty());
    }

    // --- FASE 32: RF14 acordeón FAQ ---

    public function test_faq_renderiza_pregunta_y_respuesta_oculta_inicialmente()
    {
        $faq = Contenido::create([
            'seccion' => 'faq', 'titulo' => '¿Cómo cotizar?', 'cuerpo' => 'Escríbanos desde contacto.',
            'activo' => true, 'orden' => 1, 'id_admin' => $this->adminId(),
        ]);

        $respuesta = $this->get('/')->assertStatus(200);
        $respuesta->assertSee('¿Cómo cotizar?');
        $respuesta->assertSee('Escríbanos desde contacto.');
        $respuesta->assertSee('id="faq-respuesta-'.$faq->id_contenido.'"', false);
        $respuesta->assertSee('x-show="abierta === '.$faq->id_contenido.'" x-cloak style="display: none;"', false);
        $respuesta->assertSee('abierta = abierta === '.$faq->id_contenido.' ? null : '.$faq->id_contenido, false);
    }

    public function test_faq_vacia_muestra_estado_funcional()
    {
        $this->get('/')->assertStatus(200)->assertSee('Aún no hay preguntas frecuentes.');
    }

    // --- FASE 33: RF15 cargar opiniones desde BD (excepción aislada) ---

    public function test_opiniones_llegan_a_la_vista()
    {
        \App\Models\Contenido::create([
            'seccion' => 'opiniones', 'titulo' => 'Ana R.', 'cuerpo' => 'Excelente trabajo.',
            'activo' => true, 'orden' => 1, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)->assertViewHas('opiniones', fn ($o) => $o->count() === 1 && $o->first()->titulo === 'Ana R.');
    }

    public function test_opiniones_vacias_es_lista_vacia()
    {
        $this->get('/')->assertStatus(200)->assertViewHas('opiniones', fn ($o) => $o->isEmpty());
    }

    public function test_fallo_de_bd_en_contenido_no_rompe_opiniones()
    {
        $this->app->instance(\App\Http\Controllers\DBRouterController::class, new class extends \App\Http\Controllers\DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                if ($modelo === \App\Models\Contenido::class) {
                    throw new \RuntimeException('bd');
                }
                return $modelo::query();
            }
        });

        $this->get('/')->assertStatus(200)->assertViewHas('opiniones', fn ($o) => $o->isEmpty());
    }

    // --- FASE 34: RF15 testimonios visibles sin datos inventados ---

    public function test_opinion_muestra_nombre_y_testimonio_desde_bd()
    {
        Contenido::create([
            'seccion' => 'opiniones', 'titulo' => 'Ana R.', 'cuerpo' => 'Excelente trabajo.',
            'activo' => true, 'orden' => 1, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)
            ->assertSee('Ana R.')
            ->assertSee('Excelente trabajo.');
    }

    public function test_opiniones_vacias_muestran_estado_funcional()
    {
        $this->get('/')->assertStatus(200)->assertSee('Aún no hay opiniones publicadas.');
    }

    public function test_opinion_escapa_caracteres_especiales()
    {
        Contenido::create([
            'seccion' => 'opiniones', 'titulo' => '<script>alert(1)</script>',
            'cuerpo' => '<b>Muy buen servicio</b>',
            'activo' => true, 'orden' => 1, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;b&gt;Muy buen servicio&lt;/b&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // --- FASE 35: RF16 etapas industriales vigentes agrupadas desde BD ---

    private function filaFase(
        string $nombre,
        string $descripcion,
        int $orden,
        bool $activa = true,
        ?string $archivo = null
    ): void
    {
        Contenido::create([
            'seccion' => 'fases_industriales', 'titulo' => $nombre, 'cuerpo' => $descripcion,
            'activo' => $activa, 'orden' => $orden, 'id_admin' => $this->adminId(),
            'archivo' => $archivo, 'tipo_mime' => $archivo ? 'image/jpeg' : null,
        ]);
    }

    public function test_fases_industriales_llegan_agrupadas_en_orden_canonico()
    {
        $this->filaFase('Ensamblado', 'Armado.', 1);
        $this->filaFase('Descortezado', 'Preparación.', 2);
        $this->filaFase('Descortezado', 'Otra imagen.', 3);
        $this->filaFase('Impregnación Vacío-Presión', 'Tratamiento.', 4);
        $this->filaFase('Descortezado', 'No vigente.', 5, false);

        $this->get('/')->assertStatus(200)->assertViewHas('fasesIndustriales', function ($fases) {
            return $fases->keys()->all() === [
                'Descortezado', 'Impregnación Vacío-Presión', 'Ensamblado',
            ] && $fases->get('Descortezado')->count() === 2;
        });
    }

    public function test_fase_ausente_no_se_inventa()
    {
        $this->filaFase('Descortezado', 'Preparación.', 1);

        $this->get('/')->assertStatus(200)->assertViewHas('fasesIndustriales', function ($fases) {
            return $fases->keys()->all() === ['Descortezado'];
        });
    }

    public function test_sin_fases_industriales_la_coleccion_es_vacia()
    {
        $this->get('/')->assertStatus(200)->assertViewHas('fasesIndustriales', fn ($fases) => $fases->isEmpty());
    }

    public function test_fallo_de_bd_en_fases_no_rompe_el_home()
    {
        $this->app->instance(\App\Http\Controllers\DBRouterController::class, new class extends \App\Http\Controllers\DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                if ($modelo === Contenido::class) {
                    throw new \RuntimeException('BD de contenidos caída');
                }
                return $modelo::query();
            }
        });

        $this->get('/')->assertStatus(200)->assertViewHas('fasesIndustriales', fn ($fases) => $fases->isEmpty());
    }

    // --- FASE 36: RF16 pestañas de fases con descripción vigente ---

    public function test_seeder_de_fases_es_idempotente()
    {
        $this->seed([\Database\Seeders\AdminJefeSeeder::class, \Database\Seeders\FasesIndustrialesSeeder::class]);
        $this->seed(\Database\Seeders\FasesIndustrialesSeeder::class);

        $this->assertSame(3, Contenido::where('seccion', 'fases_industriales')->count());
    }

    public function test_pestanas_muestran_tres_etapas_y_cambian_sin_recarga()
    {
        $this->seed([\Database\Seeders\AdminJefeSeeder::class, \Database\Seeders\FasesIndustrialesSeeder::class]);

        $this->get('/')->assertStatus(200)
            ->assertSee('Descortezado')
            ->assertSee('Impregnación Vacío-Presión')
            ->assertSee('Ensamblado')
            ->assertSee('Preparación de la madera en rollizos.')
            ->assertSee('x-data="fasesIndustriales()"', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('@click="activa = 1"', false)
            ->assertSee('x-show="activa === 1"', false);
    }

    public function test_sin_etapas_se_muestra_estado_vacio()
    {
        $this->get('/')->assertStatus(200)->assertSee('Aún no hay etapas industriales publicadas.');
    }

    // --- FASE 37: RF17 galería por etapa, con archivos existentes ---

    public function test_fase_muestra_varias_imagenes_existentes()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('contenido/primera.jpg', 'foto 1');
        \Illuminate\Support\Facades\Storage::disk('public')->put('contenido/segunda.jpg', 'foto 2');
        $this->filaFase('Descortezado', 'Primera toma', 1, true, 'contenido/primera.jpg');
        $this->filaFase('Descortezado', 'Segunda toma', 2, true, 'contenido/segunda.jpg');

        $this->get('/')->assertStatus(200)
            ->assertSee('contenido/primera.jpg', false)
            ->assertSee('contenido/segunda.jpg', false)
            ->assertSee('alt="Descortezado: Primera toma"', false)
            ->assertSee('Segunda toma');
    }

    public function test_archivo_de_fase_ausente_no_deja_imagen_rota()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->filaFase('Ensamblado', 'Texto conservado.', 1, true, 'contenido/falta.jpg');

        $this->get('/')->assertStatus(200)
            ->assertSee('Texto conservado.')
            ->assertDontSee('contenido/falta.jpg', false);
    }

    public function test_fase_solo_con_texto_no_muestra_galeria_vacia()
    {
        $this->filaFase('Descortezado', 'Descripción sin foto.', 1);

        $this->get('/')->assertStatus(200)
            ->assertSee('Descripción sin foto.')
            ->assertDontSee('<figure class="border border-line bg-paper-deep">', false);
    }

    // --- FASE 38: RF17 ampliación en modal ---

    public function test_imagen_existente_ofrece_modal_con_cierre_y_descripcion()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('contenido/fase.jpg', 'foto');
        $this->filaFase('Descortezado', 'Rollizos preparados.', 1, true, 'contenido/fase.jpg');

        $this->get('/')->assertStatus(200)
            ->assertSee('data-descripcion="Rollizos preparados."', false)
            ->assertSee('@click="abrirImagen($event.currentTarget)"', false)
            ->assertSee('role="dialog" aria-modal="true"', false)
            ->assertSee('@keydown.escape.window="cerrarImagen()"', false)
            ->assertSee('@click="cerrarImagen()"', false);
    }

    public function test_archivo_ausente_no_ofrece_apertura_de_modal()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->filaFase('Descortezado', 'Solo texto.', 1, true, 'contenido/ausente.jpg');

        $this->get('/')->assertStatus(200)
            ->assertSee('Solo texto.')
            ->assertDontSee('@click="abrirImagen($event.currentTarget)"', false);
    }

    // --- FASES 39-40: RF54 productos públicos con componentes ---

    public function test_producto_con_componentes_llega_a_la_vista_y_se_renderiza()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('productos/cercha.jpg', 'foto');
        $producto = \App\Models\Producto::create([
            'nombre' => 'Cercha laminada', 'descripcion' => 'Para cubiertas industriales.',
            'imagen' => 'productos/cercha.jpg', 'tipo_mime' => 'image/jpeg',
            'id_admin' => $this->adminId(),
        ]);
        $producto->componentes()->create(['nombre' => 'Montante']);
        $producto->componentes()->create(['nombre' => 'Conector']);

        $this->get('/')->assertStatus(200)
            ->assertViewHas('productos', fn ($productos) => $productos->count() === 1
                && $productos->first()->componentes->count() === 2)
            ->assertSee('Cercha laminada')
            ->assertSee('Para cubiertas industriales.')
            ->assertSee('productos/cercha.jpg', false)
            ->assertSee('Montante')
            ->assertSee('Conector');
    }

    public function test_producto_sin_componentes_y_sin_archivo_tiene_placeholder()
    {
        \App\Models\Producto::create([
            'nombre' => 'Panel', 'descripcion' => 'Panel a medida.',
            'imagen' => 'productos/ausente.jpg', 'tipo_mime' => 'image/jpeg',
            'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)
            ->assertSee('Panel a medida.')
            ->assertSee('Sin componentes registrados.')
            ->assertSee('Imagen no disponible.')
            ->assertDontSee('productos/ausente.jpg', false);
    }

    public function test_productos_vacios_muestran_estado_funcional()
    {
        $this->get('/')->assertStatus(200)
            ->assertViewHas('productos', fn ($productos) => $productos->isEmpty())
            ->assertSee('Aún no hay productos publicados.');
    }

    public function test_fallo_de_bd_en_productos_no_rompe_home()
    {
        $this->app->instance(\App\Http\Controllers\DBRouterController::class, new class extends \App\Http\Controllers\DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                if ($modelo === \App\Models\Producto::class) {
                    throw new \RuntimeException('BD de productos caída');
                }
                return $modelo::query();
            }
        });

        $this->get('/')->assertStatus(200)
            ->assertViewHas('productos', fn ($productos) => $productos->isEmpty())
            ->assertSee('Aún no hay productos publicados.');
    }

    // --- FASES 41-42: RF55 banner vigente desde BD ---

    public function test_banner_ganador_por_orden_y_id()
    {
        foreach ([
            ['Antiguo', 1, true], ['Inactivo', 9, false],
            ['Vigente uno', 2, true], ['Vigente dos', 2, true],
        ] as [$texto, $orden, $activo]) {
            Contenido::create([
                'seccion' => 'banner', 'cuerpo' => $texto,
                'orden' => $orden, 'activo' => $activo, 'id_admin' => $this->adminId(),
            ]);
        }

        $this->get('/')->assertStatus(200)
            ->assertViewHas('banner', fn ($banner) => $banner->cuerpo === 'Vigente dos')
            ->assertSee('Vigente dos');
    }

    public function test_banner_con_imagen_existente_muestra_imagen_y_texto()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('contenido/banner.jpg', 'foto');
        Contenido::create([
            'seccion' => 'banner', 'cuerpo' => 'Construimos en madera.',
            'archivo' => 'contenido/banner.jpg', 'tipo_mime' => 'image/jpeg',
            'orden' => 1, 'activo' => true, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)
            ->assertSee('Construimos en madera.')
            ->assertSee('contenido/banner.jpg', false);
    }

    public function test_banner_sin_imagen_conserva_texto_y_fondo()
    {
        Contenido::create([
            'seccion' => 'banner', 'cuerpo' => 'Texto sin imagen.',
            'archivo' => 'contenido/falta.jpg', 'tipo_mime' => 'image/jpeg',
            'orden' => 1, 'activo' => true, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/')->assertStatus(200)
            ->assertSee('Texto sin imagen.')
            ->assertSee('ig-blueprint', false)
            ->assertDontSee('contenido/falta.jpg', false);
    }

    public function test_sin_banner_el_resto_del_home_sigue_visible()
    {
        $this->get('/')->assertStatus(200)
            ->assertViewHas('banner', fn ($banner) => $banner === null)
            ->assertDontSee('data-ig-hero-overlay', false)
            ->assertSee('Productos');
    }

    public function test_fallo_de_bd_en_banner_no_rompe_home()
    {
        $this->app->instance(\App\Http\Controllers\DBRouterController::class, new class extends \App\Http\Controllers\DBRouterController {
            public function query(string $modelo): \Illuminate\Database\Eloquent\Builder
            {
                if ($modelo === Contenido::class) {
                    throw new \RuntimeException('BD de banners caída');
                }
                return $modelo::query();
            }
        });

        $this->get('/')->assertStatus(200)
            ->assertViewHas('banner', fn ($banner) => $banner === null)
            ->assertSee('Productos');
    }
}
