<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DiveController;
use App\Http\Controllers\UserController;

// ✅ Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactController::class, 'send']);

// 🔐 Rutas protegidas con JWT (auth:api)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Perfil del usuario
    Route::patch('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    // Buceos
    Route::post('/dives', [DiveController::class, 'store']);
    Route::post('/dives/bulk', [DiveController::class, 'storeMany']);
    Route::get('/dives', [DiveController::class, 'index']);
    Route::get('/dives/{id}', [DiveController::class, 'show']);
    Route::put('/dives/{id}', [DiveController::class, 'update']);
    Route::delete('/dives/{id}', [DiveController::class, 'destroy']);
    Route::post('/dives/delete-many', [DiveController::class, 'destroyMany']);
});
