<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\InstitucionalCtrl;
use App\Http\Controllers\ProyectoController;
use App\Http\Middleware\CheckAdminSession;

// ============================================================================
// Sitio público — InstitucionalCtrl + ProyectoController (galería) + ContactoController
// ============================================================================

Route::get('/', [InstitucionalCtrl::class, 'home'])->name('inicio');

Route::get('/proyectos', [ProyectoController::class, 'galeriaPublica'])->name('public.proyectos.index');

// Producto: Conectores Metálicos
Route::get('/producto', [InstitucionalCtrl::class, 'producto'])->name('public.producto');

// Documentación técnica de Conectores Metálicos (RF10 / CU 10.1): el enlace del
// encabezado pasa por el Controlador, que resuelve la URL vigente desde BD.
Route::get('/conectores/documentacion', [InstitucionalCtrl::class, 'documentacionConectores'])
    ->name('public.documentacion.conectores');

// Certificaciones vigentes (RF24 / RF25 / CU 24.1)
Route::get('/certificaciones', [InstitucionalCtrl::class, 'certificaciones'])->name('public.certificaciones');

// Descarga del PDF con nombre de archivo seguro (RF25 / CU 25.1)
Route::get('/certificaciones/{certificado}/descargar', [InstitucionalCtrl::class, 'certificacionesDescargar'])
    ->name('public.certificaciones.descargar');

// Colaboradores (RF11 / CU 11.2)
Route::get('/colaboradores', [InstitucionalCtrl::class, 'colaboradores'])->name('public.colaboradores');

Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');

// Términos y Condiciones (CU 2.1)
Route::get('/terminos', [InstitucionalCtrl::class, 'terminos'])->name('terminos');

// ============================================================================
// Autenticación — AuthController
// ============================================================================

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Recuperación de contraseña (CU 29.1 / 30.1 / 31.1 / 31.2) - guest
Route::middleware('guest')->group(function () {
    Route::post('/password/email', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/restablecer/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/restablecer', [AuthController::class, 'reset'])->name('password.update');
});

// ============================================================================
// Panel de Gestión (protegido) — ProyectoController, CrmController, AdminController
// ============================================================================

Route::middleware(['auth', CheckAdminSession::class])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    // Proyectos CRUD (ProyectoController)
    Route::resource('admin/proyectos', ProyectoController::class)->names('admin.proyectos');
    Route::delete('admin/imagenes/{imagen}', [ProyectoController::class, 'destroyImage'])->name('admin.imagenes.destroy');

    // Visibilidad Borrador ⇄ Publicado desde la propia tarjeta (RF50 / CU 50.1)
    Route::patch('admin/proyectos/{proyecto}/visibilidad', [ProyectoController::class, 'updateVisibilidad'])
        ->name('admin.proyectos.visibilidad');

    // Certificados CRUD (AdminController)
    Route::get('admin/certificados', [AdminController::class, 'certificadosIndex'])->name('admin.certificados.index');
    Route::get('admin/certificados/create', [AdminController::class, 'certificadosCreate'])->name('admin.certificados.create');
    Route::post('admin/certificados', [AdminController::class, 'certificadosStore'])->name('admin.certificados.store');
    Route::get('admin/certificados/{certificado}/edit', [AdminController::class, 'certificadosEdit'])->name('admin.certificados.edit');
    Route::put('admin/certificados/{certificado}', [AdminController::class, 'certificadosUpdate'])->name('admin.certificados.update');
    Route::delete('admin/certificados/{certificado}', [AdminController::class, 'certificadosDestroy'])->name('admin.certificados.destroy');

    // Colaboradores CRUD (AdminController)
    Route::get('admin/colaboradores', [AdminController::class, 'colaboradoresIndex'])->name('admin.colaboradores.index');
    Route::get('admin/colaboradores/create', [AdminController::class, 'colaboradoresCreate'])->name('admin.colaboradores.create');
    Route::post('admin/colaboradores', [AdminController::class, 'colaboradoresStore'])->name('admin.colaboradores.store');
    Route::get('admin/colaboradores/{colaboradore}/edit', [AdminController::class, 'colaboradoresEdit'])->name('admin.colaboradores.edit');
    Route::put('admin/colaboradores/{colaboradore}', [AdminController::class, 'colaboradoresUpdate'])->name('admin.colaboradores.update');
    Route::delete('admin/colaboradores/{colaboradore}', [AdminController::class, 'colaboradoresDestroy'])->name('admin.colaboradores.destroy');

    // Consultas Comerciales (CrmController)
    Route::get('admin/consultas', [CrmController::class, 'index'])->name('admin.consultas.index');
    Route::get('admin/consultas/{consulta}', [CrmController::class, 'show'])->name('admin.consultas.show');
    Route::put('admin/consultas/{consulta}', [CrmController::class, 'update'])->name('admin.consultas.update');

    // Cambiar contraseña (CU 28.1 / 28.2) (AuthController)
    Route::get('admin/password', [AuthController::class, 'passwordEdit'])->name('admin.password.edit');
    Route::put('admin/password', [AuthController::class, 'passwordUpdate'])->name('admin.password.update');

    // Panel de gestión - Contenido multimedia (CU 34.5, 43.x, 44.x) (AdminController)
    Route::get('admin/contenido', [AdminController::class, 'contenidoIndex'])->name('admin.contenido.index');
    Route::get('admin/contenido/create', [AdminController::class, 'contenidoCreate'])->name('admin.contenido.create');
    Route::post('admin/contenido', [AdminController::class, 'contenidoStore'])->name('admin.contenido.store');
    Route::get('admin/contenido/{contenido}/edit', [AdminController::class, 'contenidoEdit'])->name('admin.contenido.edit');
    Route::put('admin/contenido/{contenido}', [AdminController::class, 'contenidoUpdate'])->name('admin.contenido.update');
    Route::delete('admin/contenido/{contenido}', [AdminController::class, 'contenidoDestroy'])->name('admin.contenido.destroy');
});
