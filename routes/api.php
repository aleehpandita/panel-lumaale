<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\TourController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\PostController;
/*
|--------------------------------------------------------------------------
| API PRIVADA (token) - para admin/integraciones
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::get('/tours', [TourController::class, 'index']);
    Route::get('/tours/{slug}', [TourController::class, 'show']);
    Route::get('/tours/{slug}/availability', [TourController::class, 'availability']);

    Route::get('/destinations', [DestinationController::class, 'index']);
    Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{slug}', [PostController::class, 'show']);

    Route::get('/me', fn (Request $r) => $r->user());
});


/*
|--------------------------------------------------------------------------
| API PÚBLICA (sin token) - para Trevlo
|--------------------------------------------------------------------------
| Reutiliza los mismos métodos; NO se rompen tus cosas.
*/
Route::prefix('public/v1')->group(function () {

    Route::get('/tours', [TourController::class, 'index']);
    Route::get('/tours/{slug}', [TourController::class, 'show']);
    Route::get('/tours/{slug}/availability', [TourController::class, 'availability']);

    Route::get('/destinations', [DestinationController::class, 'index']);
    Route::get('/destinations/{slug}', [DestinationController::class, 'show']);

    Route::post('/bookings', [BookingController::class, 'store']);
});