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

/**
 * Cobertura de los requerimientos que faltaban al cerrar el Incremento 2:
 * RF04, RF05, RF08, RF09, RF10, RF21, RF23, RF39, RF41, RF50, RNF04 y RNF11.
 */
class IncrementoCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Administrador
    {
        return Administrador::factory()->create();
    }

    // ---------------------------------------------------------------- RF23

    /** RF23 / CU 23.1 - la galería expone la ficha técnica para la Ventana Modal */
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
        // Los tres datos que RF23 exige mostrar en el modal viajan en la respuesta.
        $response->assertSee('Galpon Industrial Coronel');
        $response->assertSee('luz libre de 25 metros', false);
        $response->assertSee('Coronel');
        $response->assertSee('Ver especificaciones técnicas', false);
    }

    /** RF23 - un proyecto en Borrador no filtra su ficha técnica al público */
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

    // ---------------------------------------------------------------- RF21

    /** RF21 / CU 21.1 - la petición AJAX devuelve solo el fragmento de resultados */
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
        // Es un fragmento: no trae el layout completo ni el formulario de filtros.
        $response->assertDontSee('<html', false);
        $response->assertDontSee('filtros-proyectos', false);
    }

    /** CU 21.1 Excepción 1 - criterios sin intersección válida */
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

    // ------------------------------------------------------- RF04/RF05/RF08

    /** RF04, RF05 y RF08 - la casilla, el modal de confirmación y "Limpiar" están en el formulario */
    public function test_contact_form_has_terms_gate_confirm_modal_and_clear_button()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // RF08: botón "Limpiar"
        $response->assertSee('Limpiar', false);
        // RF04: el envío queda deshabilitado mientras no se acepten los T&C
        $response->assertSee(':disabled="!aceptaTerminos || enviando"', false);
        // RF05: Ventana Modal de confirmación previa
        $response->assertSee('¿Enviar su consulta?', false);
    }

    // ---------------------------------------------------------------- RF09

    /** RF09 / CU 9.1 - solo se confirma tras verificar la ID registrada en BD */
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

    /** CU 9.1 - si la validación falla, no se emite ID ni se muestra confirmación */
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

    // ---------------------------------------------------------------- RF10

    /** RF10 / CU 10.1 - el enlace del encabezado resuelve la URL vigente desde BD */
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

    /** CU 10.1 Excepción 2 - sin URL registrada, avisa en vez de romper */
    public function test_documentation_link_without_url_informs_unavailability()
    {
        $response = $this->get('/conectores/documentacion');

        $response->assertRedirect(route('public.producto'));
        $response->assertSessionHas('doc_no_disponible');
    }

    // ---------------------------------------------------------------- RF50

    /** RF50 / CU 50.1 - cambio de visibilidad desde la tarjeta */
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

    /** CU 48.2 - un proyecto sin fotografías no puede publicarse */
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

    /** CU 50.1 Excepción 1 - elegir el mismo estado vigente no genera transacción */
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

    /** RNF03 - un visitante no puede cambiar la visibilidad de un proyecto */
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

    // ----------------------------------------------------------- RF39/RF41

    /** RF39 / CU 39.1 - el listado entrega el contenido completo para la Ventana Modal */
    public function test_consulta_detail_is_available_in_the_listing_for_the_modal()
    {
        $admin = $this->admin();
        Consulta::factory()->create(['mensaje' => 'Quiero cotizar cerchas para una bodega.']);

        $response = $this->actingAs($admin)->get('/admin/consultas');

        $response->assertStatus(200);
        $response->assertSee('Quiero cotizar cerchas para una bodega.', false);
        $response->assertSee('abrirDetalle(', false);
    }

    /** RF41 / CU 41.1 - se actualiza el estado desde el modal de detalle */
    public function test_admin_updates_consulta_state_from_the_detail_modal()
    {
        $admin = $this->admin();
        $consulta = Consulta::factory()->create(['estado' => 'pendiente']);

        $this->actingAs($admin)
            ->put(route('admin.consultas.update', $consulta), ['estado' => 'en_proceso'])
            ->assertRedirect();

        $this->assertEquals('en_proceso', $consulta->fresh()->estado);
    }

    // --------------------------------------------------------------- RNF04

    /** RNF04 - un archivo que no es PDF real se rechaza aunque tenga extensión .pdf */
    public function test_fake_pdf_is_rejected_by_content_not_by_extension()
    {
        Storage::fake('public');
        $admin = $this->admin();

        // Ejecutable disfrazado: extensión .pdf pero cabecera y MIME de otra cosa.
        $falso = UploadedFile::fake()->createWithContent('manual.pdf', 'MZ ejecutable, no es un PDF');

        $response = $this->actingAs($admin)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma de prueba',
            'organismo' => 'Organismo de prueba',
            'archivo_pdf' => $falso,
        ]);

        $response->assertSessionHasErrors('archivo_pdf');
        $this->assertDatabaseMissing('certificados', ['nombre' => 'Norma de prueba']);
    }

    /** RNF04 - un PDF con cabecera válida sí se acepta */
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

    // ---------------------------------------------------------------- RNF11

    /** RNF11 - los años de experiencia se calculan desde 1994, no están escritos a mano */
    public function test_years_of_experience_are_calculated_dynamically_since_1994()
    {
        $esperado = now()->year - 1994;

        $this->get('/')->assertSee("{$esperado} años de experiencia", false);
    }

    // ---------------------------------------------------------------- RF27

    /** RF27 / CU 27.1 - la autenticación se presenta en Ventana Modal */
    public function test_login_is_presented_as_a_modal()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-labelledby="acceso-titulo"', false);
        // RF29 / CU 29.1: el modal de recuperación también está disponible desde el login.
        $response->assertSee('aria-labelledby="recuperar-titulo"', false);
    }

    // ---------------------------------------------------------------- RF33

    /** CU 27.1 Exc. 2 / CU 33.1 Exc. 2 - informa el tiempo restante sin reiniciar el bloqueo */
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
        // El periodo no se reinicia.
        $this->assertEquals(
            $bloqueadoHasta->format('Y-m-d H:i'),
            $admin->fresh()->bloqueado_hasta->format('Y-m-d H:i')
        );
    }

    /** CU 33.1 Exc. 3 y 4 - si el correo de aviso falla, el bloqueo se aplica igual */
    public function test_lockout_is_applied_even_if_the_notification_email_fails()
    {
        $admin = Administrador::factory()->create([
            'correo' => 'sincorreo@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('Correcta123!'),
            'intentos_fallidos' => 4,
            'bloqueado_hasta' => null,
        ]);

        // Simula un servicio de correo caído.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('servicio de correo caido'));

        $this->post('/login', ['correo' => $admin->correo, 'password' => 'incorrecta']);

        $this->assertNotNull($admin->fresh()->bloqueado_hasta, 'El bloqueo debe aplicarse aunque el correo falle.');
    }

    // ---------------------------------------------------------------- RF25

    /** RF25 / CU 25.1 Exc. 4 - la descarga genera un nombre de archivo seguro */
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

    /** CU 25.2 Exc. 1 y 2 - certificado sin PDF cargado informa indisponibilidad */
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

    // ---------------------------------------------------------------- RF43

    /** CU 43.2 Exc. 1 - Pregunta y Respuesta vacías se rechazan en FAQ */
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

    /** CU 43.3 Exc. 2 - el Banner exige Texto Descriptivo, pero no título */
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

    /** CU 43.4 / 43.5 Exc. 1 - Fases y Opiniones exigen nombre y texto */
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

    // ----------------------------------------------------------------- CU 1.1

    /** CU 1.1 Exc. 4 - dominio de correo inexistente se rechaza */
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
