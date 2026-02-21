<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TourController;


Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/tours', [\App\Http\Controllers\Api\TourController::class, 'index']);
    Route::get('/tours/{slug}', [\App\Http\Controllers\Api\TourController::class, 'show']);
    Route::get('/tours/{slug}/availability', [\App\Http\Controllers\Api\TourController::class, 'availability']);
});