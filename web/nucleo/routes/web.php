<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\InstitucionalCtrl;
use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;

// Página pública principal
Route::get('/', [InstitucionalCtrl::class, 'index'])->name('inicio');

// Galería de proyectos (JSON para Alpine.js)
Route::get('/proyectos', [ProyectoController::class, 'galeria'])
    ->name('proyectos.index');

Route::get('/proyectos/buscar', [ProyectoController::class, 'buscar'])
    ->name('proyectos.buscar');

Route::get('/proyectos/{id}/detalle', [ProyectoController::class, 'detalle'])
    ->name('proyectos.detalle')
    ->where('id', '[0-9]+');

// Certificaciones
Route::get('/certificaciones', [ProyectoController::class, 'certificaciones'])
    ->name('certificaciones.index');

Route::get('/colaboradores', [InstitucionalCtrl::class, 'colaboradores'])
    ->name('colaboradores.index');

Route::get('/certificaciones/{id}/ver', [ProyectoController::class, 'verCertificado'])
    ->name('certificaciones.ver')
    ->where('id', '[0-9]+');

Route::get('/certificaciones/{id}/descargar', [ProyectoController::class, 'descargarCertificado'])
    ->name('certificaciones.descargar')
    ->where('id', '[0-9]+');

// Autenticación
Route::post('/login', [AuthController::class, 'login'])
    ->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('admin.auth');

// Formulario de contacto
Route::post('/contacto', [ContactoController::class, 'store'])
    ->name('contacto.store');

// Panel de administración (requiere sesión activa)
Route::prefix('admin')
    ->middleware('admin.auth')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Proyectos
        Route::get('/proyectos', [AdminController::class, 'indexProyectos'])
            ->name('proyectos.index');

        Route::post('/proyectos', [AdminController::class, 'storeProyecto'])
            ->name('proyectos.store');

        Route::get('/proyectos/{id}', [AdminController::class, 'showProyecto'])
            ->name('proyectos.show')
            ->where('id', '[0-9]+');

        Route::put('/proyectos/{id}', [AdminController::class, 'updateProyecto'])
            ->name('proyectos.update')
            ->where('id', '[0-9]+');

        Route::get('/proyectos/panel', function () {
            return view('admin.proyectos');
        })->name('proyectos.panel');

        // Colaboradores
        Route::get('/colaboradores', [AdminController::class, 'indexColaboradores'])
            ->name('colaboradores.index');

        Route::post('/colaboradores', [AdminController::class, 'storeColaborador'])
            ->name('colaboradores.store');

        Route::delete('/colaboradores/{id}', [AdminController::class, 'destroyColaborador'])
            ->name('colaboradores.destroy')
            ->where('id', '[0-9]+');

        Route::get('/colaboradores/panel', function () {
            return view('admin.colaboradores');
        })->name('colaboradores.panel');

    });
