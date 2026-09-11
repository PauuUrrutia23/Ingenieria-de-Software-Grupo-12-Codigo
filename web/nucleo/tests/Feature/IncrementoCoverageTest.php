<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Contenido;
use App\Models\Proyecto;

class IncrementoCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Administrador
    {
        return Administrador::factory()->create();
    }

    public function test_gallery_exposes_technical_specs_for_the_modal()
    {
        Proyecto::factory()->create([
            'nombre_obra' => 'Galpon Industrial Coronel',
            'descripcion_tecnica' => 'Estructura de cerchas de pino radiata impregnado, luz libre de 25 metros.',
            'ubicacion_geografica' => 'Coronel',
            'estado_publicacion' => 'publicado',
            'id_admin' => $this->admin()->id_admin,
        ]);

        $response = $this->get('/proyectos');

        $response->assertStatus(200);

        $response->assertSee('Galpon Industrial Coronel');
        $response->assertSee('luz libre de 25 metros', false);
        $response->assertSee('Coronel');
        $response->assertSee('Ver especificaciones técnicas', false);
    }

    public function test_draft_project_technical_specs_are_not_exposed()
    {
        Proyecto::factory()->create([
            'nombre_obra' => 'Obra Reservada',
            'descripcion_tecnica' => 'Detalle confidencial del cliente.',
            'estado_publicacion' => 'borrador',
            'id_admin' => $this->admin()->id_admin,
        ]);

        $this->get('/proyectos')->assertDontSee('Detalle confidencial del cliente.');
    }

    public function test_combined_filters_return_only_the_results_partial()
    {
        $admin = $this->admin();
        Proyecto::factory()->create([
            'nombre_obra' => 'Galpon Los Angeles',
            'categoria' => 'industrial',
            'region' => 'Biobio',
            'ubicacion_geografica' => 'Los Angeles',
            'estado_publicacion' => 'publicado',
            'id_admin' => $admin->id_admin,
        ]);
        Proyecto::factory()->create([
            'nombre_obra' => 'Vivienda Santiago',
            'categoria' => 'construccion',
            'region' => 'Metropolitana',
            'ubicacion_geografica' => 'Santiago',
            'estado_publicacion' => 'publicado',
            'id_admin' => $admin->id_admin,
        ]);

        $response = $this->get('/proyectos?q=Angeles&categoria=industrial&region=Biobio', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Galpon Los Angeles');
        $response->assertDontSee('Vivienda Santiago');

        $response->assertDontSee('<html', false);
        $response->assertDontSee('filtros-proyectos', false);
    }

    public function test_combined_filters_without_matches_show_empty_state()
    {
        Proyecto::factory()->create([
            'nombre_obra' => 'Galpon Los Angeles',
            'categoria' => 'industrial',
            'region' => 'Biobio',
            'estado_publicacion' => 'publicado',
            'id_admin' => $this->admin()->id_admin,
        ]);

        $response = $this->get('/proyectos?q=Angeles&categoria=construccion', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('No se encontraron proyectos que cumplan todos los criterios.', false);
    }

    public function test_contact_form_has_terms_gate_confirm_modal_and_clear_button()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee('Limpiar', false);

        $response->assertSee(':disabled="!aceptaTerminos || enviando"', false);

        $response->assertSee('¿Enviar su consulta?', false);
    }

    public function test_successful_consulta_returns_verified_id_for_the_confirmation_modal()
    {
        Mail::fake();

        $response = $this->post('/contacto', [
            'nombre' => 'Ana',
            'apellido' => 'Soto',
            'email' => 'ana@example.com',
            'mensaje' => 'Necesito cotizar un galpon de 400 metros cuadrados.',
            'acepta_terminos' => 'on',
        ]);

        $consulta = Consulta::first();
        $this->assertNotNull($consulta);
        $response->assertSessionHas('consulta_id', $consulta->id_consulta);
    }

    public function test_failed_consulta_does_not_emit_confirmation_id()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Ana',
            'email' => 'no-es-un-correo',
            'mensaje' => 'Necesito cotizar un galpon de 400 metros cuadrados.',
            'acepta_terminos' => 'on',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('consulta_id');
    }

    public function test_documentation_link_redirects_to_the_url_stored_in_db()
    {
        Contenido::factory()->create([
            'seccion' => 'documentacion',
            'enlace' => 'https://ejemplo.cl/conectores.pdf',
            'activo' => true,
            'id_admin' => $this->admin()->id_admin,
        ]);

        $this->get('/conectores/documentacion')
            ->assertRedirect('https://ejemplo.cl/conectores.pdf');
    }

    public function test_documentation_link_without_url_informs_unavailability()
    {
        $response = $this->get('/conectores/documentacion');

        $response->assertRedirect(route('public.producto'));
        $response->assertSessionHas('doc_no_disponible');
    }

    public function test_admin_toggles_project_visibility_from_the_card()
    {
        $admin = $this->admin();
        $proyecto = Proyecto::factory()->create([
            'estado_publicacion' => 'borrador',
            'id_admin' => $admin->id_admin,
        ]);
        \App\Models\ImagenProyecto::factory()->create(['id_proyecto' => $proyecto->id_proyecto]);

        $this->actingAs($admin)
            ->patch(route('admin.proyectos.visibilidad', $proyecto), ['estado_publicacion' => 'publicado'])
            ->assertRedirect();

        $this->assertEquals('publicado', $proyecto->fresh()->estado_publicacion);
    }

    public function test_project_without_images_cannot_be_published()
    {
        $admin = $this->admin();
        $proyecto = Proyecto::factory()->create([
            'estado_publicacion' => 'borrador',
            'id_admin' => $admin->id_admin,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.proyectos.visibilidad', $proyecto), ['estado_publicacion' => 'publicado'])
            ->assertSessionHasErrors('estado_publicacion');

        $this->assertEquals('borrador', $proyecto->fresh()->estado_publicacion);
    }

    public function test_selecting_the_same_visibility_does_not_change_the_record()
    {
        $admin = $this->admin();
        $proyecto = Proyecto::factory()->create([
            'estado_publicacion' => 'publicado',
            'id_admin' => $admin->id_admin,
        ]);
        $actualizadoAntes = $proyecto->updated_at;

        $this->actingAs($admin)
            ->patch(route('admin.proyectos.visibilidad', $proyecto), ['estado_publicacion' => 'publicado']);

        $this->assertEquals($actualizadoAntes, $proyecto->fresh()->updated_at);
    }

    public function test_guest_cannot_change_project_visibility()
    {
        $proyecto = Proyecto::factory()->create([
            'estado_publicacion' => 'borrador',
            'id_admin' => $this->admin()->id_admin,
        ]);

        $this->patch(route('admin.proyectos.visibilidad', $proyecto), ['estado_publicacion' => 'publicado'])
            ->assertRedirect('/login');

        $this->assertEquals('borrador', $proyecto->fresh()->estado_publicacion);
    }

    public function test_consulta_detail_is_available_in_the_listing_for_the_modal()
    {
        $admin = $this->admin();
        Consulta::factory()->create(['mensaje' => 'Quiero cotizar cerchas para una bodega.']);

        $response = $this->actingAs($admin)->get('/admin/consultas');

        $response->assertStatus(200);
        $response->assertSee('Quiero cotizar cerchas para una bodega.', false);
        $response->assertSee('abrirDetalle(', false);
    }

    public function test_admin_updates_consulta_state_from_the_detail_modal()
    {
        $admin = $this->admin();
        $consulta = Consulta::factory()->create(['estado' => 'pendiente']);

        $this->actingAs($admin)
            ->put(route('admin.consultas.update', $consulta), ['estado' => 'en_proceso'])
            ->assertRedirect();

        $this->assertEquals('en_proceso', $consulta->fresh()->estado);
    }

    public function test_fake_pdf_is_rejected_by_content_not_by_extension()
    {
        Storage::fake('public');
        $admin = $this->admin();

        $falso = UploadedFile::fake()->createWithContent('manual.pdf', 'MZ ejecutable, no es un PDF');

        $response = $this->actingAs($admin)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma de prueba',
            'organismo' => 'Organismo de prueba',
            'archivo_pdf' => $falso,
        ]);

        $response->assertSessionHasErrors('archivo_pdf');
        $this->assertDatabaseMissing('certificados', ['nombre' => 'Norma de prueba']);
    }

    public function test_real_pdf_header_is_accepted()
    {
        Storage::fake('public');
        $admin = $this->admin();

        $pdf = UploadedFile::fake()->createWithContent(
            'certificado.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
        );

        $this->actingAs($admin)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma de prueba valida',
            'organismo' => 'Organismo de prueba',
            'archivo_pdf' => $pdf,
        ]);

        $this->assertDatabaseHas('certificados', ['nombre' => 'Norma de prueba valida']);
    }

    public function test_years_of_experience_are_calculated_dynamically_since_1994()
    {
        $esperado = now()->year - 1994;

        $this->get('/')->assertSee("{$esperado} años de experiencia", false);
    }

    public function test_login_is_presented_as_a_modal()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-labelledby="acceso-titulo"', false);

        $response->assertSee('aria-labelledby="recuperar-titulo"', false);
    }

    public function test_locked_account_reports_remaining_time_without_restarting_the_lock()
    {
        $bloqueadoHasta = now()->addMinutes(42);
        $admin = Administrador::factory()->create([
            'correo' => 'bloqueado@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('Correcta123!'),
            'intentos_fallidos' => 5,
            'bloqueado_hasta' => $bloqueadoHasta,
        ]);

        $response = $this->post('/login', ['correo' => $admin->correo, 'password' => 'loQueSea']);

        $response->assertSessionHasErrors('correo');
        $this->assertStringContainsString('minuto', session('errors')->first('correo'));

        $this->assertEquals(
            $bloqueadoHasta->format('Y-m-d H:i'),
            $admin->fresh()->bloqueado_hasta->format('Y-m-d H:i')
        );
    }

    public function test_lockout_is_applied_even_if_the_notification_email_fails()
    {
        $admin = Administrador::factory()->create([
            'correo' => 'sincorreo@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('Correcta123!'),
            'intentos_fallidos' => 4,
            'bloqueado_hasta' => null,
        ]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('servicio de correo caido'));

        $this->post('/login', ['correo' => $admin->correo, 'password' => 'incorrecta']);

        $this->assertNotNull($admin->fresh()->bloqueado_hasta, 'El bloqueo debe aplicarse aunque el correo falle.');
    }

    public function test_certificate_download_uses_a_safe_generated_filename()
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificados_pdf/x.pdf', '%PDF-1.4 contenido');

        $certificado = \App\Models\Certificado::factory()->create([
            'nombre' => 'Madera / Construcciones',
            'estado' => 'vigente',
            'archivo_pdf' => 'certificados_pdf/x.pdf',
        ]);

        $response = $this->get(route('public.certificaciones.descargar', $certificado));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=madera-construcciones.pdf');
    }

    public function test_certificate_without_pdf_reports_unavailability()
    {
        $certificado = \App\Models\Certificado::factory()->create([
            'estado' => 'vigente',
            'archivo_pdf' => null,
        ]);

        $response = $this->get(route('public.certificaciones.descargar', $certificado));

        $response->assertRedirect(route('public.certificaciones'));
        $response->assertSessionHas('doc_no_disponible');
    }

    public function test_faq_requires_question_and_answer()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.contenido.store'), [
            'seccion' => 'faq',
            'titulo' => '',
            'cuerpo' => '',
        ]);

        $response->assertSessionHasErrors(['titulo', 'cuerpo']);
        $this->assertDatabaseCount('contenidos', 0);
    }

    public function test_banner_requires_description_text_only()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.contenido.store'), ['seccion' => 'banner', 'cuerpo' => ''])
            ->assertSessionHasErrors('cuerpo');

        $this->actingAs($admin)
            ->post(route('admin.contenido.store'), ['seccion' => 'banner', 'cuerpo' => 'Construimos en madera desde 1994.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contenidos', ['seccion' => 'banner']);
    }

    public function test_fases_and_opiniones_require_name_and_text()
    {
        $admin = $this->admin();

        foreach (['fases_industriales', 'opiniones'] as $seccion) {
            $this->actingAs($admin)
                ->post(route('admin.contenido.store'), ['seccion' => $seccion, 'titulo' => '', 'cuerpo' => ''])
                ->assertSessionHasErrors(['titulo', 'cuerpo']);
        }

        $this->assertDatabaseCount('contenidos', 0);
    }

    public function test_contact_form_rejects_nonexistent_email_domain()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Ana',
            'apellido' => 'Soto',
            'email' => 'alguien@dominio-que-no-existe-abc123xyz.cl',
            'mensaje' => 'Necesito cotizar un galpon industrial de 400 metros cuadrados.',
            'acepta_terminos' => 'on',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('consultas', 0);
    }
}
