<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CvController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JobController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [AuthController::class, 'updateProfile']);
    Route::delete('/me', [AuthController::class, 'deleteAccount']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/cv', [CvController::class, 'index']);
    Route::post('/cv', [CvController::class, 'store']);
    Route::get('/cv/{id}', [CvController::class, 'show']);
    Route::patch('/cv/{id}', [CvController::class, 'update']);
    Route::get('/cv/{id}/download', [CvController::class, 'download']);
    Route::post('/cv/upload', [CvController::class, 'upload']);
    Route::post('/cv/{id}/analyze', [CvController::class, 'analyze']);
    Route::post('/cv/{id}/generate', [CvController::class, 'generate']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::get('/jobs/{id}/match', [JobController::class, 'match']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::patch('/applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
});