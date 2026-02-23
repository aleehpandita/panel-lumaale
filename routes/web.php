<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TourController as WebTourController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Aquí SÍ hay sesión, cookies, auth, etc.
*/

// 👉 Redirección raíz del panel
Route::get('/', function () {
    return Auth::check()
        ? redirect('/admin')
        : redirect('/admin/login');
});

// 👉 (Opcional) Redirección amigable del sitio público sin afectar el panel
// Si tú NO usas "/" para público, NO la necesitas.
// Si algún día quieres que "/" sea el sitio público, esto cambiaría.
Route::get('/tours', function () {
    return redirect('/es/tours');
});

/*
|--------------------------------------------------------------------------
| Public Frontend (Trevlo) con idioma en URL
|--------------------------------------------------------------------------
*/
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => 'es|en'],
    'middleware' => ['locale.route'],
], function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/about', [PageController::class, 'about'])->name('about');

    Route::get('/tours', [WebTourController::class, 'index'])->name('tours.index');

    Route::get('/tours/{slug}', [WebTourController::class, 'show'])->name('tours.show');
});