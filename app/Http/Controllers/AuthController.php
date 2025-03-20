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
        $credentials = config('firebase.credentials');
    
        if (empty($credentials) || !is_array($credentials)) {
            Log::error('Error al cargar las credenciales de Firebase');
            throw new \Exception("No se han cargado las credenciales de Firebase.");
        }
    
        $this->auth = (new Factory)
            ->withServiceAccount($credentials)
            ->createAuth();
    }

    private function verifyFirebaseToken($token)
{
    if (!$token) {
        Log::error("🔴 Error: No se recibió un token de Firebase.");
        throw new \InvalidArgumentException('Token not provided');
    }

    try {
        Log::info("🔵 Token recibido: " . $token);

        $verifiedIdToken = $this->auth->verifyIdToken($token);
        Log::info("🟢 Token verificado correctamente", ['claims' => $verifiedIdToken->claims()]);

        return [
            'uid' => $verifiedIdToken->claims()->get('sub'),
            'email' => $verifiedIdToken->claims()->get('email'),
        ];
    } catch (\Throwable $e) {
        Log::error("🔴 Error al verificar el token de Firebase", ['error' => $e->getMessage()]);
        throw new \Exception("Token inválido.");
    }
}

    

    private function syncUserWithFirebase($firebaseUid, $firebaseEmail, $name = null, $photo = null)
    {
        $firebaseUser = $this->auth->getUser($firebaseUid);
        $firebaseDisplayName = $name ?? $firebaseUser->displayName ?? 'Unknown User';

        return User::updateOrCreate(
            ['email' => $firebaseEmail],
            [
                'name' => $firebaseDisplayName,
                'firebase_uid' => $firebaseUid,
                'photo' => $photo ?? $firebaseUser->photoUrl ?? null,
            ]
        );
    }

    public function getUser(Request $request)
    {
        try {
            $token = $request->bearerToken(); // 🔥 Corrected method for getting token
            $firebaseData = $this->verifyFirebaseToken($token);

            $user = $this->syncUserWithFirebase($firebaseData['uid'], $firebaseData['email']);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'photo' => $user->photo,
                ]
            ]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            Log::info('Token recibido:', ['token' => $request->input('firebase_token')]); // 👀 Verifica si Laravel recibe el token

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
        $user = \App\Models\User::updateOrCreate(
            ['email' => $firebaseUser->get('email')],
            [
                'name' => $request->input('name', 'Sin nombre'),
                'firebase_uid' => $firebaseUser->get('sub'),
                'photo' => $firebaseUser->get('picture') ?? null // 👀 Verificar si la foto se está guardando
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
            $token = $request->bearerToken(); // 🔥 Corrected token retrieval

            // 🔹 Verify token with Firebase
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $firebaseEmail = $verifiedIdToken->claims()->get('email');

            // 🔹 Fetch user info from Firebase
            $firebaseUser = $this->auth->getUser($firebaseUid);
            $firebaseDisplayName = $firebaseUser->displayName ?? 'Unknown User';

            // 🔹 Update local database user
            $user = User::updateOrCreate(
                ['email' => $firebaseEmail],
                [
                    'name' => $firebaseDisplayName, // 🔥 Always update name from Firebase
                    'firebase_uid' => $firebaseUid,
                    'photo' => $firebaseUser->photoUrl ?? null,
                ]
            );

            // 🔹 If Firebase user has no name, update it
            if (empty($firebaseUser->displayName)) {
                $this->auth->updateUser($firebaseUid, ['displayName' => $user->name]);
            }

            return response()->json(['message' => 'Login successful', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal error', 'details' => $e->getMessage()], 500);
        }
    }
}
