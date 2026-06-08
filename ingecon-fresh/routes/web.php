<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\InstitucionalCtrl;
use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Pagina publica principal — sin autenticacion
// -----------------------------------------------------------------------
Route::get('/', [InstitucionalCtrl::class, 'index'])->name('inicio');

// -----------------------------------------------------------------------
// Rutas publicas — Galeria de proyectos
// Sin autenticacion. Retornan JSON para consumo por Alpine.js.
// -----------------------------------------------------------------------

// RF12 — Pagina dedicada de proyectos (acceso desde el Menu Lateral)
Route::get('/proyectos', [ProyectoController::class, 'galeria'])
    ->name('proyectos.index');

// CU 3.2 / CU 3.3 — Busqueda y filtrado de proyectos (RF20, RF21)
Route::get('/proyectos/buscar', [ProyectoController::class, 'buscar'])
    ->name('proyectos.buscar');

// CU 3.6 — Detalle de proyecto (RF24)
Route::get('/proyectos/{id}/detalle', [ProyectoController::class, 'detalle'])
    ->name('proyectos.detalle')
    ->where('id', '[0-9]+');

// -----------------------------------------------------------------------
// Rutas publicas — Certificaciones (RF25, RF26)
// -----------------------------------------------------------------------

// CU 4.1 — Visualizar listado de certificaciones
Route::get('/certificaciones', [ProyectoController::class, 'certificaciones'])
    ->name('certificaciones.index');

// RF12 — Pagina dedicada de colaboradores (acceso desde el Menu Lateral)
Route::get('/colaboradores', [InstitucionalCtrl::class, 'colaboradores'])
    ->name('colaboradores.index');

// CU 4.2 — Descargar PDF de un certificado
Route::get('/certificaciones/{id}/descargar', [ProyectoController::class, 'descargarCertificado'])
    ->name('certificaciones.descargar')
    ->where('id', '[0-9]+');

// -------------------------------------------------------------------------
// Rutas publicas de autenticacion
// -------------------------------------------------------------------------

Route::post('/login', [AuthController::class, 'login'])
    ->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('admin.auth');

// -------------------------------------------------------------------------
// Ruta publica — Formulario de contacto
// No requiere autenticacion.
// -------------------------------------------------------------------------
Route::post('/contacto', [ContactoController::class, 'store'])
    ->name('contacto.store');

// -------------------------------------------------------------------------
// Rutas protegidas del panel de administracion
// Todas las rutas bajo /admin requieren sesion activa valida.
// -------------------------------------------------------------------------

Route::prefix('admin')
    ->middleware('admin.auth')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // ---------------------------------------------------------------
        // Modulo de Proyectos (RF49, RF50)
        // ---------------------------------------------------------------

        // CU 7.6 / CU 7.7 — Listar proyectos del admin (retorna JSON)
        Route::get('/proyectos', [AdminController::class, 'indexProyectos'])
            ->name('proyectos.index');

        // CU 7.6 — Crear nuevo proyecto con imagenes (RF49)
        Route::post('/proyectos', [AdminController::class, 'storeProyecto'])
            ->name('proyectos.store');

        // CU 7.7 — Actualizar proyecto existente (RF50)
        Route::put('/proyectos/{id}', [AdminController::class, 'updateProyecto'])
            ->name('proyectos.update')
            ->where('id', '[0-9]+');

        // Vista HTML del modulo de proyectos
        Route::get('/proyectos/panel', function () {
            return view('admin.proyectos');
        })->name('proyectos.panel');

        // -----------------------------------------------------------------------
        // Modulo de Colaboradores (RF46 — CU 7.3)
        // -----------------------------------------------------------------------

        // Listar colaboradores del admin autenticado (retorna JSON)
        Route::get('/colaboradores', [AdminController::class, 'indexColaboradores'])
            ->name('colaboradores.index');

        // Registrar nuevo colaborador
        Route::post('/colaboradores', [AdminController::class, 'storeColaborador'])
            ->name('colaboradores.store');

        // Vista HTML del modulo de colaboradores
        Route::get('/colaboradores/panel', function () {
            return view('admin.colaboradores');
        })->name('colaboradores.panel');

    });
