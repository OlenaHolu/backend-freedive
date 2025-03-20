<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use App\Models\User;


class AuthController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $this->auth = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth();
    }

    public function getUser(Request $request)
    {
        try {
            Log::info('Token recibido en /user:', ['token' => $request->bearerToken()]);

            if (!$request->firebase_user) {
                return response()->json(['error' => 'Token no válido o expirado'], 401);
            }

            return response()->json([
                'user' => [
                    'id' => $request->firebase_user['sub'],
                    'email' => $request->firebase_user['email'],
                    'name' => $request->firebase_user['name'] ?? 'Sin nombre',
                    'photo' => $request->firebase_user['picture'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            Log::info('Token recibido:', ['token' => $request->input('firebase_token')]);

            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json(['error' => 'Token no proporcionado'], 401);
            }

            $auth = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->createAuth();
            $verifiedIdToken = $auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            // 🔹 Guardar usuario en la base de datos con su UID de Firebase
            $user = User::updateOrCreate(
                ['email' => $firebaseUser->get('email')],
                [
                    'name' => $request->input('name'),
                    'firebase_uid' => $firebaseUser->get('sub'),
                    'photo' => $firebaseUser->get('picture') ?? null
                ]
            );

            return response()->json(['message' => 'User registered successfully', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json(['error' => 'Token no proporcionado'], 401);
            }
            // 🔹 Verify token with Firebase
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            // find user in database
            $user = User::where('firebase_uid', $firebaseUid)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json(['message' => 'Login successful', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal error', 'details' => $e->getMessage()], 500);
        }
    }
}
