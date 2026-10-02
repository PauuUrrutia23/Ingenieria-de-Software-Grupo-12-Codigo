<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\InstitucionalCtrl;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProductoAdminController;
use App\Http\Controllers\AdminGestionController;
use App\Http\Middleware\CheckAdminJefe;
use App\Http\Middleware\CheckAdminSession;

Route::get('/', [InstitucionalCtrl::class, 'home'])->name('inicio');

Route::get('/proyectos', [ProyectoController::class, 'galeriaPublica'])->name('public.proyectos.index');
Route::get('/proyectos/marcadores', [ProyectoController::class, 'marcadoresPublicos'])->name('public.proyectos.marcadores');
Route::get('/proyectos/{proyecto}/detalle', [ProyectoController::class, 'detallePublico'])
    ->whereNumber('proyecto')->name('public.proyectos.detalle');

Route::get('/producto', [InstitucionalCtrl::class, 'producto'])->name('public.producto');

Route::get('/conectores/documentacion', [InstitucionalCtrl::class, 'documentacionConectores'])
    ->name('public.documentacion.conectores');

Route::get('/certificaciones', [CertificadoController::class, 'listadoPublico'])->name('public.certificaciones');

Route::get('/certificaciones/{certificado}/descargar', [CertificadoController::class, 'descargar'])
    ->name('public.certificaciones.descargar');

Route::get('/certificaciones/{certificado}/preview', [CertificadoController::class, 'preview'])
    ->name('public.certificaciones.preview');

Route::get('/colaboradores', [InstitucionalCtrl::class, 'colaboradores'])->name('public.colaboradores');

Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');

Route::get('/terminos', [InstitucionalCtrl::class, 'terminos'])->name('terminos');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::post('/password/email', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/restablecer/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/restablecer', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth', CheckAdminSession::class])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    Route::get('admin/proyectos/{proyecto}/detalle-edicion', [ProyectoController::class, 'detalleEdicion'])
        ->whereNumber('proyecto')->name('admin.proyectos.detalle-edicion');
    Route::resource('admin/proyectos', ProyectoController::class)->names('admin.proyectos');
    Route::delete('admin/imagenes/{imagen}', [ProyectoController::class, 'destroyImage'])->name('admin.imagenes.destroy');

    Route::patch('admin/proyectos/{proyecto}/visibilidad', [ProyectoController::class, 'updateVisibilidad'])
        ->name('admin.proyectos.visibilidad');

    Route::resource('admin/certificados', CertificadoController::class)
        ->except(['show'])
        ->names('admin.certificados')
        ->parameters(['certificados' => 'certificado']);

    Route::get('admin/colaboradores', [AdminController::class, 'colaboradoresIndex'])->name('admin.colaboradores.index');
    Route::get('admin/colaboradores/create', [AdminController::class, 'colaboradoresCreate'])->name('admin.colaboradores.create');
    Route::get('admin/colaboradores/{colaboradore}/detalle', [AdminController::class, 'colaboradoresDetalle'])
        ->whereNumber('colaboradore')->name('admin.colaboradores.detalle');
    Route::post('admin/colaboradores', [AdminController::class, 'colaboradoresStore'])->name('admin.colaboradores.store');
    Route::get('admin/colaboradores/{colaboradore}/edit', [AdminController::class, 'colaboradoresEdit'])->name('admin.colaboradores.edit');
    Route::put('admin/colaboradores/{colaboradore}', [AdminController::class, 'colaboradoresUpdate'])->name('admin.colaboradores.update');
    Route::delete('admin/colaboradores/{colaboradore}', [AdminController::class, 'colaboradoresDestroy'])->name('admin.colaboradores.destroy');

    Route::get('admin/consultas', [AdminController::class, 'consultasIndex'])->name('admin.consultas.index');
    Route::get('admin/consultas/exportar/{formato}', [AdminController::class, 'consultasExportar'])
        ->whereIn('formato', ['csv', 'xlsx'])->name('admin.consultas.exportar');
    Route::get('admin/consultas/{consulta}/detalle', [AdminController::class, 'consultasDetalle'])
        ->whereNumber('consulta')->name('admin.consultas.detalle');
    Route::get('admin/consultas/{consulta}', [AdminController::class, 'consultasShow'])->name('admin.consultas.show');
    Route::put('admin/consultas/{consulta}', [AdminController::class, 'consultasUpdate'])->name('admin.consultas.update');

    Route::get('admin/password', [AuthController::class, 'passwordEdit'])->name('admin.password.edit');
    Route::put('admin/password', [AuthController::class, 'passwordUpdate'])->name('admin.password.update');

    Route::get('admin/contenido', [AdminController::class, 'contenidoIndex'])->name('admin.contenido.index');
    Route::get('admin/productos', [ProductoAdminController::class, 'index'])->name('admin.productos.index');
    Route::get('admin/productos/{producto}/detalle', [ProductoAdminController::class, 'detalle'])
        ->whereNumber('producto')->name('admin.productos.detalle');
    Route::post('admin/productos', [ProductoAdminController::class, 'store'])->name('admin.productos.store');
    Route::put('admin/productos/{producto}', [ProductoAdminController::class, 'update'])
        ->whereNumber('producto')->name('admin.productos.update');
    Route::get('admin/contenido/create', [AdminController::class, 'contenidoCreate'])->name('admin.contenido.create');
    Route::post('admin/contenido', [AdminController::class, 'contenidoStore'])->name('admin.contenido.store');
    Route::get('admin/contenido/{contenido}/edit', [AdminController::class, 'contenidoEdit'])->name('admin.contenido.edit');
    Route::put('admin/contenido/{contenido}', [AdminController::class, 'contenidoUpdate'])->name('admin.contenido.update');
    Route::delete('admin/contenido/{contenido}', [AdminController::class, 'contenidoDestroy'])->name('admin.contenido.destroy');

    Route::middleware(CheckAdminJefe::class)->group(function () {
        Route::get('admin/administradores', [AdminGestionController::class, 'index'])->name('admin.administradores.index');
        Route::post('admin/administradores', [AdminGestionController::class, 'store'])->name('admin.administradores.store');
        Route::delete('admin/administradores/{administrador}', [AdminGestionController::class, 'destroy'])
            ->whereNumber('administrador')->name('admin.administradores.destroy');
    });
});
