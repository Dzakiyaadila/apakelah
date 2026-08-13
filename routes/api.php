<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CvController;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/cv', [CvController::class, 'index']);
    Route::post('/cv', [CvController::class, 'store']);
    Route::get('/cv/{id}', [CvController::class, 'show']);
    Route::post('/cv/upload', [CvController::class, 'upload']);
    Route::post('/cv/{id}/analyze', [CvController::class, 'analyze']);
    Route::post('/cv/{id}/generate', [CvController::class, 'generate']);
});