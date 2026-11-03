<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Correcciones de la revisión del código contra RF y CU (02-11-2026).
class CorreccionesRevisionRfCuTest extends TestCase
{
    use RefreshDatabase;

    public static function listadosDelPanel(): array
    {
        return [
            'Módulo comercial (CU 34.2, 36.1)' => ['admin.consultas.index', 'consultas'],
            'Gestión de proyectos (CU 34.3, 48.3)' => ['admin.proyectos.index', 'proyectos'],
            'Colaboradores (CU 34.4, 45.2)' => ['admin.colaboradores.index', 'colaboradores'],
            'Panel de gestión (CU 34.5)' => ['admin.contenido.index', 'contenidos'],
            'Productos (CU 43.1)' => ['admin.productos.index', 'productos'],
            'Certificados (CU 34.6)' => ['admin.certificados.index', 'certificados'],
            'Administración (CU 52.2)' => ['admin.administradores.index', 'administradores'],
        ];
    }

    /** @dataProvider listadosDelPanel */
    public function test_si_la_base_falla_el_listado_muestra_un_aviso_y_no_un_error($ruta, $tabla)
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        \Illuminate\Support\Facades\DB::listen(function ($query) use ($tabla) {
            // Falla solo el listado: la sesión sigue leyendo la cuenta autenticada.
            $esLecturaDeLaCuenta = $tabla === 'administradores' && str_contains($query->sql, 'limit 1');
            if (preg_match('/^select .* from .?' . $tabla . '.?( |$)/i', $query->sql) && !$esLecturaDeLaCuenta) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $this->loginAdmin($jefe)->get(route($ruta))->assertOk()
            ->assertSee('No fue posible cargar el listado. Intente nuevamente más tarde.')
            // Solo el aviso: el estado vacío diría que no hay registros, lo que no se sabe.
            ->assertDontSee('Aún no hay');
    }

    // Administración no tiene estado vacío: siempre está al menos la cuenta del jefe.
    public static function listadosConEstadoVacio(): array
    {
        return array_filter(self::listadosDelPanel(), fn ($caso) => $caso[1] !== 'administradores');
    }

    /** @dataProvider listadosConEstadoVacio */
    public function test_sin_falla_un_listado_vacio_muestra_su_estado_vacio($ruta, $tabla)
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);

        $this->loginAdmin($jefe)->get(route($ruta))->assertOk()
            ->assertSee('Aún no hay')
            ->assertDontSee('No fue posible cargar el listado.');
    }

    // CU 37.1, Excepción 1: sin consultas no se puede ordenar.
    public function test_sin_consultas_el_control_de_orden_queda_desactivado()
    {
        $admin = Administrador::factory()->create();
        $ordenar = 'aria-label="Ordenar por fecha';

        $vacio = $this->loginAdmin($admin)->get(route('admin.consultas.index'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*' . preg_quote($ordenar, '/') . '/', $vacio);
        $this->assertMatchesRegularExpression('/<span[^>]*aria-disabled="true"[^>]*>\s*Fecha/', $vacio);

        \App\Models\Consulta::factory()->create();
        $conDatos = $this->loginAdmin($admin)->get(route('admin.consultas.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<a[^>]*\s' . preg_quote($ordenar, '/') . '/', $conDatos);
    }

    // CU 15.2, Excepción 3: si falla el filtrado se informa y se mantienen los criterios.
    public function test_si_falla_el_filtrado_del_historial_lo_informa_y_conserva_los_criterios()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (preg_match('/from .?historial_cambios/i', $query->sql)) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $html = $this->loginAdmin($jefe)->get(route('admin.historial.index', ['tipo' => 'creaciones', 'q' => 'certificados']))->assertOk()
            ->assertSee('No fue posible aplicar los filtros. Intente nuevamente.')
            ->assertSee('value="certificados"', false)
            ->getContent();
        $this->assertMatchesRegularExpression('/aria-current="true"\s*>Creaciones/', $html);
    }

    // Grupo B: los botones y enlaces llevan el nombre que les dan los RF y CU.
    public function test_los_botones_usan_los_nombres_de_los_documentos()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        \App\Models\Proyecto::factory()->create(['id_admin' => $jefe->id_admin]);

        $this->get('/login')->assertSee('Olvidé mi contraseña');                                   // RF29, CU 29.1
        $proyectos = $this->loginAdmin($jefe)->get(route('admin.proyectos.index'));
        $proyectos->assertSee('Editar Información')->assertDontSee('Editar Info<', false);          // RF49, CU 49.1
        $proyectos->assertSee('Crear Proyecto');                                                    // CU 48.1
        $panel = fn ($seccion) => $this->loginAdmin($jefe)->get(route('admin.contenido.index', ['seccion' => $seccion]));
        $panel('faq')->assertSee('Agregar Pregunta');                                               // CU 43.2
        $panel('banner')->assertSee('Agregar Banner');                                              // CU 43.3
        $panel('fases_industriales')->assertSee('Agregar Fase');                                    // CU 43.4
        $this->loginAdmin($jefe)->get(route('admin.productos.index'))->assertSee('Agregar Producto'); // CU 43.1
        $this->loginAdmin($jefe)->get(route('admin.consultas.index'))                               // RF38, CU 38.1
            ->assertSee('aria-label="Buscar en los mensajes"', false);
    }

    // RF34, CU 34.1: el Menú Lateral muestra solo los módulos que nombra el RF.
    public function test_el_menu_lateral_tiene_solo_los_modulos_de_rf34()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $html = $this->loginAdmin($jefe)->get(route('admin.consultas.index'))->assertOk()->getContent();
        preg_match('/<nav[^>]*aria-label="Menú del panel de gestión"[^>]*>(.*?)<\/nav>/s', $html, $menu);
        preg_match_all('/<span class="min-w-0 truncate">([^<]+)<\/span>/', $menu[1] ?? '', $enlaces);

        $this->assertSame(
            ['Gestión de proyectos', 'Certificados', 'Colaboradores', 'Módulo comercial', 'Panel de gestión', 'Administración', 'Historial de Cambios'],
            array_map('trim', $enlaces[1])
        );
    }

    // CU 59.2, Excepciones 2 y 3: la Vista informa que la cuenta no tiene permiso.
    public function test_una_accion_sin_permiso_informa_que_la_cuenta_no_tiene_permiso()
    {
        $this->loginAdmin(Administrador::factory()->create())
            ->get(route('admin.certificados.index'))
            ->assertForbidden()
            ->assertSee('Acceso no permitido')
            ->assertSee('Su cuenta no tiene el permiso necesario para esta acción.')
            ->assertSee('href="/admin/dashboard"', false);
    }
}
