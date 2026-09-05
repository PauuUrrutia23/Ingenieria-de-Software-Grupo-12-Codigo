<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $proyectos_recientes = App\Models\Proyecto::where('estado_publicacion', 'publicado')
        ->orderBy('anio_ejecucion', 'desc')
        ->with('imagenes')
        ->take(3)
        ->get();
    
    $certificados = App\Models\Certificado::where('estado', 'vigente')->take(3)->get();
    $proveedores = App\Models\Colaborador::all();

    return view('public.index', compact('proyectos_recientes', 'certificados', 'proveedores'));
});

Route::get('/proyectos', [App\Http\Controllers\PublicProyectoController::class, 'index'])->name('public.proyectos.index');

// Producto: Conectores Metálicos
Route::get('/producto', function () {
    return view('public.producto', [
        'docsUrl' => App\Http\Controllers\DocumentacionController::urlVigente() ?: '#',
    ]);
})->name('public.producto');

// Documentación técnica de Conectores Metálicos (RF10 / CU 10.1): el enlace del
// encabezado pasa por el Controlador, que resuelve la URL vigente desde BD.
Route::get('/conectores/documentacion', [App\Http\Controllers\DocumentacionController::class, 'conectores'])
    ->name('public.documentacion.conectores');

// Certificaciones vigentes (RF24 / RF25 / CU 24.1)
Route::get('/certificaciones', [App\Http\Controllers\PublicCertificadoController::class, 'index'])->name('public.certificaciones');

// Descarga del PDF con nombre de archivo seguro (RF25 / CU 25.1)
Route::get('/certificaciones/{certificado}/descargar', [App\Http\Controllers\PublicCertificadoController::class, 'descargar'])
    ->name('public.certificaciones.descargar');

// Colaboradores (RF11 / CU 11.2)
Route::get('/colaboradores', [App\Http\Controllers\PublicColaboradorController::class, 'index'])->name('public.colaboradores');

Route::post('/contacto', [App\Http\Controllers\ContactoController::class, 'store'])->name('contacto.store');

// Términos y Condiciones (CU 2.1)
Route::get('/terminos', function () {
    return view('legal.terminos');
})->name('terminos');

// Auth Routes
Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Recuperación de contraseña (CU 29.1 / 30.1 / 31.1 / 31.2) - guest
Route::middleware('guest')->group(function () {
    Route::post('/password/email', [App\Http\Controllers\PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/restablecer/{token}', [App\Http\Controllers\PasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/restablecer', [App\Http\Controllers\PasswordController::class, 'reset'])->name('password.update');
});

// Admin Routes (Protected)
Route::middleware(['auth', App\Http\Middleware\CheckAdminSession::class])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    });
    
    // Proyectos CRUD
    Route::resource('admin/proyectos', App\Http\Controllers\ProyectoController::class)->names('admin.proyectos');
    Route::delete('admin/imagenes/{imagen}', [App\Http\Controllers\ProyectoController::class, 'destroyImage'])->name('admin.imagenes.destroy');

    // Visibilidad Borrador ⇄ Publicado desde la propia tarjeta (RF50 / CU 50.1)
    Route::patch('admin/proyectos/{proyecto}/visibilidad', [App\Http\Controllers\ProyectoController::class, 'updateVisibilidad'])
        ->name('admin.proyectos.visibilidad');

    // Certificados CRUD
    Route::resource('admin/certificados', App\Http\Controllers\CertificadoController::class)->names('admin.certificados');
    
    // Colaboradores CRUD
    Route::resource('admin/colaboradores', App\Http\Controllers\ColaboradorController::class)->names('admin.colaboradores');
    
    // Consultas Comerciales
    Route::resource('admin/consultas', App\Http\Controllers\ConsultaController::class)
        ->only(['index', 'show', 'update'])
        ->names('admin.consultas');

    // Cambiar contraseña (CU 28.1 / 28.2)
    Route::get('admin/password', [App\Http\Controllers\PasswordController::class, 'edit'])->name('admin.password.edit');
    Route::put('admin/password', [App\Http\Controllers\PasswordController::class, 'update'])->name('admin.password.update');

    // Panel de gestión - Contenido multimedia (CU 34.5, 43.x, 44.x)
    Route::resource('admin/contenido', App\Http\Controllers\ContenidoController::class)
        ->except(['show'])
        ->names('admin.contenido');

    // Bitácora automática de pruebas (corre la suite real de tests en vivo)
    if (app()->environment('local')) {
        Route::get('admin/qa', [App\Http\Controllers\QaController::class, 'index'])->name('admin.qa.index');
        Route::get('admin/qa/stream', [App\Http\Controllers\QaController::class, 'stream'])->name('admin.qa.stream');
    }
});