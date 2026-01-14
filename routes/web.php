<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Aquí SÍ hay sesión, cookies y auth()->check()
*/

// 👉 Redirección raíz del panel
Route::get('/', function () {
    return Auth::check()
        ? redirect('/admin')
        : redirect('/admin/login');
});

/*
|--------------------------------------------------------------------------
| API Routes (públicas, sin sesión)
|--------------------------------------------------------------------------
| Si quieres, luego puedes moverlas a api.php
*/

Route::prefix('api')->group(function () {

    Route::prefix('tours')->group(function () {
        Route::get('/', [TourController::class, 'index']);
        Route::get('{slug}', [TourController::class, 'show']);
        Route::get('{slug}/availability', [TourController::class, 'availability']);
    });

    Route::post('bookings', [BookingController::class, 'store']);
});
