<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 🔹 Rutas de autenticación
Route::middleware(['firebase'])->get('/user', [AuthController::class, 'getUser']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-firebase', function () {
    return response()->json([
        'firebase_credentials' => env('FIREBASE_CREDENTIALS'),
    ]);
});

