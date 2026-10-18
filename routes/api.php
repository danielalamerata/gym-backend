<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SocioController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/planes', [PlanController::class, 'index']);

    Route::middleware('admin')->group(function () {
        Route::post('/planes', [PlanController::class, 'store']);
        Route::put('/planes/{plan}', [PlanController::class, 'update']);
        Route::delete('/planes/{plan}', [PlanController::class, 'destroy']);

        Route::get('/socios', [SocioController::class, 'index']);
        Route::get('/socios/{socio}', [SocioController::class, 'show']);
        Route::put('/socios/{socio}', [SocioController::class, 'update']);
        Route::delete('/socios/{socio}', [SocioController::class, 'destroy']);
    });
});
