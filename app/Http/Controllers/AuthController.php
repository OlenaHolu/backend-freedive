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
                if (!$request->firebase_user) {
                return response()->json(['error' => 'Token no válido o expirado'], 401);
            }

            $claims = $request->firebase_user;
            $user = User::where('email', $claims['email'])->first();

            return response()->json([
                'user' => [
                    'id' => $claims['sub'],
                    'email' => $claims['email'],
                    'name' => $user->name ?? $claims['name'] ?? 'Unknown User',
                    'photo' => $user->photo ?? $claims['picture'] ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json(['error' => 'Token no proporcionado'], 401);
            }

            $auth = (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->createAuth();
            $verifiedIdToken = $auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            $name = $request->input('name') ?? $firebaseUser->get('name') ?? 'Unknown User';

            // 🔹 Guardar usuario en la base de datos con su UID de Firebase
            $user = User::updateOrCreate(
                ['email' => $firebaseUser->get('email')],
                [
                    'name' => $name,
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
            $firebaseEmail = $verifiedIdToken->claims()->get('email');

            // 🔹 Fetch user info from Firebase
            $firebaseUser = $this->auth->getUser($firebaseUid);
            $firebaseDisplayName = $firebaseUser->displayName ?? 'Unknown User';
            $firebasePhoto = $firebaseUser->photoUrl ?? null;

    // find user in database
            $user = User::updateOrCreate(
                ['email' => $firebaseEmail],
                [
                    'name' => $firebaseDisplayName,
                    'firebase_uid' => $firebaseUid,
                    'photo' => $firebasePhoto,
                ]
            );

            return response()->json(['message' => 'Login successful', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal error', 'details' => $e->getMessage()], 500);
        }
    }
}
