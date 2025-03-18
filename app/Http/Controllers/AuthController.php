<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use App\Models\User;

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
     * Login de usuario
     */
    public function login(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json(['error' => 'Token no proporcionado'], 401);
            }

            // 🔹 Verificar token con Firebase
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            // 🔹 Buscar o crear el usuario en la base de datos
            $user = User::updateOrCreate(
                ['email' => $firebaseUser->get('email')],
                [
                    'name' => $firebaseUser->get('name'),
                    'photo' => $firebaseUser->get('picture') ?? null, // 🔹 Guardar la foto
                ]
            );

            return response()->json(['message' => 'Login exitoso', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Token de Firebase inválido'], 401);
        }
    }
}
