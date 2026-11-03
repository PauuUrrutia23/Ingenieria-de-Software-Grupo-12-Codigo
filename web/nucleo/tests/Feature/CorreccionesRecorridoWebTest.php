<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Contenido;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Errores encontrados en el recorrido completo de la web (03-11-2026).
class CorreccionesRecorridoWebTest extends TestCase
{
    use RefreshDatabase;

    private function jefe(): Administrador
    {
        return Administrador::factory()->create(['rol' => 'admin_jefe']);
    }

    // La Gestión de proyectos no tiene página propia por proyecto: la dirección solo acepta PUT y DELETE.
    public function test_la_ruta_de_un_proyecto_del_panel_no_existe()
    {
        $jefe = $this->jefe();
        $proyecto = Proyecto::factory()->create(['id_admin' => $jefe->id_admin]);

        $this->loginAdmin($jefe)->get('/admin/proyectos/' . $proyecto->id_proyecto)->assertStatus(405);
    }

    // La ficha de Conectores no es una sección del Panel de gestión: no se edita ni se borra desde ahí.
    public function test_el_panel_no_toca_contenido_de_secciones_que_no_administra()
    {
        $jefe = $this->jefe();
        $ficha = Contenido::create(['seccion' => 'ficha_conectores', 'titulo' => 'descripcion', 'cuerpo' => 'Placas de acero.', 'orden' => 1, 'activo' => true, 'id_admin' => $jefe->id_admin]);

        $this->loginAdmin($jefe)->get(route('admin.contenido.edit', $ficha))->assertNotFound();
        $this->loginAdmin($jefe)->put(route('admin.contenido.update', $ficha), ['titulo' => 'x', 'cuerpo' => 'Otro texto.'])->assertNotFound();
        $this->loginAdmin($jefe)->delete(route('admin.contenido.destroy', $ficha))->assertNotFound();

        $this->assertSame('Placas de acero.', $ficha->fresh()?->cuerpo);
    }

    // CU 43.4: una Fase Industrial nueva queda visible en el inicio, en el orden de su campo orden.
    public function test_una_fase_nueva_aparece_en_el_inicio_segun_su_orden()
    {
        $jefe = $this->jefe();
        foreach ([['Ensamblado', 3], ['Secado en cámara', 4], ['Descortezado', 1]] as [$nombre, $orden]) {
            Contenido::create(['seccion' => 'fases_industriales', 'titulo' => $nombre, 'cuerpo' => "Texto de $nombre.",
                'orden' => $orden, 'activo' => true, 'id_admin' => $jefe->id_admin]);
        }

        $this->get('/')->assertOk()
            ->assertViewHas('fasesIndustriales', fn ($fases) => $fases->keys()->all() === ['Descortezado', 'Ensamblado', 'Secado en cámara'])
            ->assertSee('Texto de Secado en cámara.');
    }

    // El formulario no pide orden: una fase agregada con "Agregar Fase" queda al final.
    public function test_una_fase_agregada_desde_el_panel_queda_al_final()
    {
        $jefe = $this->jefe();
        foreach (['Descortezado' => 1, 'Ensamblado' => 2] as $nombre => $orden) {
            Contenido::create(['seccion' => 'fases_industriales', 'titulo' => $nombre, 'cuerpo' => "Texto de $nombre.",
                'orden' => $orden, 'activo' => true, 'id_admin' => $jefe->id_admin]);
        }

        $this->loginAdmin($jefe)->post(route('admin.contenido.store'), [
            'seccion' => 'fases_industriales', 'titulo' => 'Secado en cámara', 'cuerpo' => 'Texto de Secado en cámara.',
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertViewHas('fasesIndustriales',
            fn ($fases) => $fases->keys()->all() === ['Descortezado', 'Ensamblado', 'Secado en cámara']);
    }

    // Un proyecto publicado debe tener al menos una fotografía (CU 48.2).
    public function test_no_se_borra_la_ultima_foto_de_un_proyecto_publicado()
    {
        $jefe = $this->jefe();
        $proyecto = Proyecto::factory()->create(['id_admin' => $jefe->id_admin, 'estado_publicacion' => 'publicado']);
        $foto = ImagenProyecto::factory()->create(['id_proyecto' => $proyecto->id_proyecto]);

        $this->loginAdmin($jefe)->delete(route('admin.imagenes.destroy', $foto))
            ->assertSessionHasErrors(['imagen' => 'Un proyecto publicado debe conservar al menos una fotografía.']);
        $this->assertNotNull($foto->fresh());
    }

    // RF24/RF25: solo se descargan certificados vigentes, igual que en la vista previa.
    public function test_no_se_descarga_el_pdf_de_un_certificado_vencido()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('certificados_pdf/x.pdf', '%PDF-1.4 contenido');
        $vencido = \App\Models\Certificado::factory()->create([
            'archivo_pdf' => 'certificados_pdf/x.pdf',
            'fecha_emision' => now()->subYear()->toDateString(),
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('public.certificaciones.descargar', $vencido))
            ->assertRedirect(route('public.certificaciones'))
            ->assertSessionHas('doc_no_disponible');
    }

    // Con config:cache, env() devuelve null fuera de config/: la URL de respaldo se lee desde la configuración.
    public function test_la_documentacion_de_conectores_usa_la_url_configurada()
    {
        config(['services.docs_conectores.url' => 'https://docs.ingecon.cl/conectores.pdf']);

        $this->get(route('public.documentacion.conectores'))
            ->assertRedirect('https://docs.ingecon.cl/conectores.pdf');
    }

    // "Olvidé mi contraseña" (CU 29.1): cinco solicitudes por hora para un mismo correo.
    public function test_la_recuperacion_de_contrasena_tiene_un_limite_por_hora()
    {
        \Illuminate\Support\Facades\Mail::fake();
        Administrador::factory()->create(['correo' => 'admin@ingecon.cl']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/password/email', ['correo' => 'admin@ingecon.cl'])->assertSessionHasNoErrors();
        }

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PasswordResetLinkMail::class, 5);
        $this->assertDatabaseCount('recuperaciones_password', 5);
    }

    // RNF10: si la Base de Datos no responde, se informa con un Mensaje del Sistema y no con un error 500.
    public function test_la_recuperacion_de_contrasena_informa_si_la_base_falla()
    {
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (str_contains($query->sql, 'administradores')) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $this->from('/login')->post('/password/email', ['correo' => 'admin@ingecon.cl'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['correo' => 'La recuperación no está disponible temporalmente. Intente nuevamente.']);
    }

    // Una instalación limpia (migrate --seed) muestra los proyectos de ejemplo con fotos reales.
    public function test_las_fotos_de_los_proyectos_de_ejemplo_existen_despues_de_sembrar()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $imagenes = ImagenProyecto::all();
        $this->assertNotEmpty($imagenes);
        foreach ($imagenes as $imagen) {
            \Illuminate\Support\Facades\Storage::disk('public')->assertExists($imagen->imagen);
            $this->assertStringNotContainsString('ChatGPT', $imagen->nombre_archivo);
        }
    }
}
