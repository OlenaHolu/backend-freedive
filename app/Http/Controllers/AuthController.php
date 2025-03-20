<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth();
    }

    /**
     * Obtener el usuario autenticado
     */
    public function getUser(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->firebase_user['sub'],
                'email' => $request->firebase_user['email'],
                'name' => $request->firebase_user['name'] ?? 'Sin nombre',
                'photo' => $request->firebase_user['picture'] ?? null,
            ]
        ]);
    }

    /**
     * Registrar usuario
     */
    public function register(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // 🔹 Verificar token con Firebase
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            // 🔹 Guardar usuario en la base de datos
            $user = User::updateOrCreate(
                ['email' => $firebaseUser->get('email')],
                [
                    'name' => $request->input('name', 'Sin nombre'),
                    'firebase_uid' => $firebaseUser->get('sub')
                ]
            );

            return response()->json(['message' => 'User registered successfully', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Login
     */
    public function login(Request $request)
{
    try {
        $token = $request->input('firebase_token');
        if (!$token) {
            return response()->json(['error' => 'Token no proporcionado'], 401);
        }

        // 🔹 Verify token with Firebase
        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUser = $verifiedIdToken->claims();

        // 🔹 Log the received data for debugging
        Log::info('Firebase User Data:', (array) $firebaseUser);

        // 🔹 Ensure the name field is not null
        $userName = $firebaseUser->get('name') ?? 'Unknown User';

        // 🔹 Update or create user in the database
        $user = User::updateOrCreate(
            ['email' => $firebaseUser->get('email')],
            [
                'name' => $userName, // 🔥 This prevents NULL values
                'photo' => $firebaseUser->get('picture') ?? null,
            ]
        );

        return response()->json(['message' => 'Login exitoso', 'user' => $user]);
    } catch (FailedToVerifyToken $e) {
        return response()->json(['error' => 'Token de Firebase inválido'], 401);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
    }
}

}
