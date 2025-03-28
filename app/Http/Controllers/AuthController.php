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

            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            $email = $firebaseUser->get('email');
            $uid = $firebaseUser->get('sub');
            $photo = $firebaseUser->get('picture') ?? null;
            $name = $request->input('name') ?? $firebaseUser->get('name') ?? 'Sin nombre';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'firebase_uid' => $uid,
                    'name' => $name,
                    'photo' => $photo,
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

            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            $email = $firebaseUser->get('email');
            $photo = $firebaseUser->get('picture') ?? null;

            $user = User::where('email', $email)->first();

            if ($user) {
                if (!$user->photo && $photo) {
                    $user->photo = $photo;
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $firebaseUser->get('name') ?? 'Unknown User',
                    'email' => $email,
                    'firebase_uid' => $firebaseUser->get('sub'),
                    'photo' => $photo
                ]);
            }

            return response()->json(['message' => 'Login successful', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal error', 'details' => $e->getMessage()], 500);
        }
    }

    public function updateAvatar(Request $request)
{
    try {
        $token = $request->input('firebase_token');
        if (!$token) {
            return response()->json(['error' => 'Token no proporcionado'], 401);
        }

        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUser = $verifiedIdToken->claims();
        $email = $firebaseUser->get('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'image' => 'required|string'
        ]);

        $user->photo = $request->image;
        $user->save();

        return response()->json(['message' => 'Avatar actualizado']);
    } catch (FailedToVerifyToken $e) {
        return response()->json(['error' => 'Firebase token inválido'], 401);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
    }
}

}
