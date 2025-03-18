<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use App\Models\User;

// 🔹 Ruta para obtener el usuario autenticado (requiere token de Firebase)
Route::middleware(['firebase'])->get('/user', function (Request $request) {
    return response()->json([
        'user' => [
            'id' => $request->firebase_user['sub'],
            'email' => $request->firebase_user['email'],
            'name' => $request->firebase_user['name'] ?? 'Sin nombre',
            'photo' => $request->firebase_user['picture'] ?? null,
        ]
    ]);
});

// 🔹 Ruta para registrar usuarios
Route::post('/register', function (Request $request) {
    try {
        $token = $request->input('firebase_token');
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        // 🔹 Verificar token con Firebase
        $auth = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth();
        $verifiedIdToken = $auth->verifyIdToken($token);
        $firebaseUser = $verifiedIdToken->claims();

        // 🔹 Guardar usuario en la base de datos con su UID de Firebase
        $user = \App\Models\User::updateOrCreate(
            ['email' => $firebaseUser->get('email')],
            [
                'name' => $request->input('name', 'Sin nombre'),
                'firebase_uid' => $firebaseUser->get('sub') // 🔹 Guardar el UID de Firebase
            ]
        );

        return response()->json(['message' => 'User registered successfully', 'user' => $user]);
    } catch (FailedToVerifyToken $e) {
        return response()->json(['error' => 'Invalid Firebase token'], 401);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
    }
});

Route::post('/login', function (Request $request) {
    try {
        $token = $request->input('firebase_token');
        if (!$token) {
            return response()->json(['error' => 'Token no proporcionado'], 401);
        }

        // 🔹 Verificar token con Firebase
        $auth = (new Factory)->withServiceAccount(config('firebase.credentials'))->createAuth();
        $verifiedIdToken = $auth->verifyIdToken($token);
        $firebaseUser = $verifiedIdToken->claims();

        // 🔹 Buscar o crear el usuario en la base de datos
        $user = \App\Models\User::updateOrCreate(
            ['email' => $firebaseUser->get('email')],
            ['name' => $firebaseUser->get('name')]
        );

        return response()->json(['message' => 'Login exitoso', 'user' => $user]);
    } catch (FailedToVerifyToken $e) {
        return response()->json(['error' => 'Token de Firebase inválido'], 401);
    }
});

Route::get('/', function () {
    return view('welcome');
});

