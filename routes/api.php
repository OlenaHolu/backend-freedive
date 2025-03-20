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

use Kreait\Firebase\Factory;

Route::get('/test-firebase', function () {
    try {
        $firebaseCredentials = json_decode(env('FIREBASE_CREDENTIALS'), true);
        
        if (!$firebaseCredentials) {
            throw new Exception('Invalid Firebase credentials');
        }

        $firebase = (new Factory)
            ->withServiceAccount($firebaseCredentials)
            ->createAuth();

        return response()->json([
            'success' => true,
            'message' => 'Firebase is connected!',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

