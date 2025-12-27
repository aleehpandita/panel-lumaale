<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\BookingController;

Route::prefix('api')->group(function () {

    Route::prefix('tours')->group(function () {
        Route::get('/', [TourController::class, 'index']);
        Route::get('{slug}', [TourController::class, 'show']);
        Route::get('{slug}/availability', [TourController::class, 'availability']);
    });

    Route::post('bookings', [BookingController::class, 'store']);
});