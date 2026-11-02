<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// CU 15.4: cada acción registrable queda en el Historial de Cambios, verificado a través del propio Historial.
class HistorialRegistroTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $jefe;
    private Administrador $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->jefe = Administrador::factory()->create(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $this->editor = Administrador::factory()->create([
            'correo' => 'editor@ingecon.cl',
            'perm_editar_contenido' => true, 'perm_exportar_consultas' => true, 'perm_eliminar_consultas' => true,
        ]);
    }

    // Filas del listado como texto plano, para comparar con valores literales.
    private function filasDelHistorial(): array
    {
        $html = $this->loginAdmin($this->jefe)->get(route('admin.historial.index'))->assertOk()->getContent();
        preg_match_all('/<tr data-registro[^>]*>(.*?)<\/tr>/s', $html, $m);
        return array_map(fn ($fila) => trim(preg_replace('/\s+/', ' ', strip_tags($fila))), $m[1]);
    }

    public function test_crear_un_certificado_queda_registrado()
    {
        $this->loginAdmin($this->editor)->post(route('admin.certificados.store'), [
            'nombre' => 'NCh 1198', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasNoErrors();

        $filas = $this->filasDelHistorial();
        $this->assertCount(1, $filas);
        $this->assertStringContainsString('editor@ingecon.cl', $filas[0]);
        $this->assertStringContainsString('Creación', $filas[0]);
        $this->assertStringContainsString('Certificados', $filas[0]);
    }

    // Acción y módulo (tercera y cuarta celda) de cada fila, desde la más reciente.
    private function accionesRegistradas(): array
    {
        $html = $this->loginAdmin($this->jefe)->get(route('admin.historial.index'))->assertOk()->getContent();
        preg_match_all('/<tr data-registro[^>]*>(.*?)<\/tr>/s', $html, $filas);
        return array_map(function ($fila) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $fila, $celdas);
            $texto = fn ($i) => trim(preg_replace('/\s+/', ' ', strip_tags($celdas[1][$i])));
            return $texto(2) . ' · ' . $texto(3);
        }, $filas[1]);
    }

    public function test_editar_y_eliminar_un_certificado_quedan_registrados()
    {
        $cert = \App\Models\Certificado::create(['nombre' => 'NCh 1198', 'organismo' => 'INN', 'fecha_emision' => '2024-01-15', 'id_admin' => $this->editor->id_admin]);

        $this->loginAdmin($this->editor)->put(route('admin.certificados.update', $cert), [
            'nombre' => 'NCh 1198 Of.2006', 'organismo' => 'INN', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->delete(route('admin.certificados.destroy', $cert))->assertSessionHasNoErrors();

        $this->assertSame(['Eliminación · Certificados', 'Modificación · Certificados'], $this->accionesRegistradas());
    }

    public function test_crear_editar_y_eliminar_un_colaborador_quedan_registrados()
    {
        $this->loginAdmin($this->editor)->post(route('admin.colaboradores.store'), [
            'nombre_comercial' => 'Maderas del Sur', 'logotipo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png'),
        ])->assertSessionHasNoErrors();
        $id = \App\Models\Colaborador::query()->value('id_colaborador');
        $this->loginAdmin($this->editor)->put(route('admin.colaboradores.update', $id), ['nombre_comercial' => 'Maderas del Sur SpA'])
            ->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->delete(route('admin.colaboradores.destroy', $id))->assertSessionHasNoErrors();

        $this->assertSame(
            ['Eliminación · Colaboradores', 'Modificación · Colaboradores', 'Creación · Colaboradores'],
            $this->accionesRegistradas()
        );
    }

    public function test_crear_editar_y_eliminar_contenido_quedan_registrados()
    {
        $this->loginAdmin($this->editor)->post(route('admin.contenido.store'), [
            'seccion' => 'faq', 'titulo' => '¿Despachan a regiones?', 'cuerpo' => 'Sí, a todo Chile.',
        ])->assertSessionHasNoErrors();
        $id = \App\Models\Contenido::query()->where('seccion', 'faq')->value('id_contenido');
        $this->loginAdmin($this->editor)->put(route('admin.contenido.update', $id), [
            'titulo' => '¿Despachan a todo Chile?', 'cuerpo' => 'Sí.',
        ])->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->delete(route('admin.contenido.destroy', $id))->assertSessionHasNoErrors();

        $this->assertSame(
            ['Eliminación · Contenido del sitio', 'Modificación · Contenido del sitio', 'Creación · Contenido del sitio'],
            $this->accionesRegistradas()
        );
    }

    public function test_crear_y_editar_un_producto_quedan_registrados()
    {
        $this->loginAdmin($this->editor)->post(route('admin.productos.store'), [
            'nombre' => 'Conector T', 'descripcion' => 'Placa galvanizada.', 'imagen' => \Illuminate\Http\UploadedFile::fake()->image('t.png'),
        ])->assertSessionHasNoErrors();
        $id = \App\Models\Producto::query()->value('id_producto');
        $this->loginAdmin($this->editor)->put(route('admin.productos.update', $id), [
            'nombre' => 'Conector T reforzado', 'descripcion' => 'Placa galvanizada.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Modificación · Productos', 'Creación · Productos'], $this->accionesRegistradas());
    }

    public function test_las_acciones_sobre_proyectos_quedan_registradas()
    {
        $datos = [
            'nombre_obra' => 'Galpón Coronel', 'descripcion_tecnica' => 'Estructura de madera laminada para bodega.',
            'region' => 'Biobío', 'comuna' => 'Coronel', 'anio_ejecucion' => 2025, 'categoria' => 'industrial',
            'estado_publicacion' => 'borrador',
        ];
        $this->loginAdmin($this->editor)->post(route('admin.proyectos.store'), $datos + [
            'imagenes' => [\Illuminate\Http\UploadedFile::fake()->image('a.jpg'), \Illuminate\Http\UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasNoErrors();
        $proyecto = \App\Models\Proyecto::query()->firstOrFail();

        $this->loginAdmin($this->editor)->put(route('admin.proyectos.update', $proyecto), ['nombre_obra' => 'Galpón Coronel II'] + $datos)
            ->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->patch(route('admin.proyectos.visibilidad', $proyecto), ['estado_publicacion' => 'publicado'])
            ->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->delete(route('admin.imagenes.destroy', $proyecto->imagenes()->first()))
            ->assertSessionHasNoErrors();
        $this->loginAdmin($this->editor)->delete(route('admin.proyectos.destroy', $proyecto))->assertSessionHasNoErrors();

        $this->assertSame([
            'Eliminación · Proyectos', 'Modificación · Proyectos', 'Modificación · Proyectos',
            'Modificación · Proyectos', 'Creación · Proyectos',
        ], $this->accionesRegistradas());
    }

    public function test_las_acciones_sobre_cuentas_y_permisos_quedan_registradas()
    {
        $this->loginAdmin($this->jefe)->post(route('admin.administradores.store'), [
            'correo' => 'nuevo@ingecon.cl', 'password' => 'ClaveSegura1!', 'password_confirmation' => 'ClaveSegura1!',
        ])->assertSessionHasNoErrors();
        $nuevo = Administrador::query()->where('correo', 'nuevo@ingecon.cl')->firstOrFail();
        $this->loginAdmin($this->jefe)->patch(route('admin.administradores.permisos', $nuevo->id_admin), [
            'permiso' => 'exportar_consultas', 'activo' => '1',
        ])->assertSessionHasNoErrors();
        $this->loginAdmin($this->jefe)->delete(route('admin.administradores.destroy', $nuevo->id_admin))->assertSessionHasNoErrors();

        $this->assertSame(
            ['Eliminación · Administración', 'Asignación de permiso · Administración', 'Creación · Administración'],
            $this->accionesRegistradas()
        );
    }

    public function test_ver_exportar_y_eliminar_consultas_quedan_registrados()
    {
        [$a, $b] = \App\Models\Consulta::factory()->count(2)->create();

        $this->loginAdmin($this->editor)->getJson(route('admin.consultas.detalle', $a->id_consulta))->assertOk();
        $this->loginAdmin($this->editor)->get(route('admin.consultas.exportar', ['formato' => 'csv', 'ids' => [$a->id_consulta, $b->id_consulta]]))->assertOk();
        $this->loginAdmin($this->editor)->delete(route('admin.consultas.eliminar'), ['ids' => [$a->id_consulta, $b->id_consulta]])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            'Eliminación · Consultas', 'Eliminación · Consultas', 'Exportación · Consultas', 'Vista de detalle · Consultas',
        ], $this->accionesRegistradas());
    }

    public function test_si_el_historial_falla_la_exportacion_no_entrega_el_archivo()
    {
        $consulta = \App\Models\Consulta::factory()->create();
        DB::listen(function ($query) {
            if (preg_match('/^insert into .?historial_cambios/i', $query->sql)) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $respuesta = $this->loginAdmin($this->editor)->get(route('admin.consultas.exportar', ['formato' => 'csv', 'ids' => [$consulta->id_consulta]]));

        $this->assertNotInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $respuesta->baseResponse);
    }

    public function test_si_el_historial_falla_la_accion_se_cancela()
    {
        DB::listen(function ($query) {
            if (preg_match('/^insert into .?historial_cambios/i', $query->sql)) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $this->loginAdmin($this->editor)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma que no debe quedar', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasErrors();

        // Se revisa la vitrina pública: el formulario del panel repite lo escrito tras un error.
        $this->get(route('public.certificaciones'))->assertOk()->assertDontSee('Norma que no debe quedar');
    }

    public function test_una_accion_que_falla_no_se_registra()
    {
        $this->loginAdmin($this->editor)->post(route('admin.certificados.store'), [
            'nombre' => '', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasErrors('nombre');

        $this->assertSame([], $this->filasDelHistorial());
    }
}
