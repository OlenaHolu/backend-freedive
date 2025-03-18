<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken; // Nuevo namespace correcto

class FirebaseAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $auth = (new Factory)
                ->withServiceAccount(storage_path(env('FIREBASE_CREDENTIALS')))
                ->createAuth();

            $verifiedIdToken = $auth->verifyIdToken($token);
            
            // Almacenar información del usuario sin sobrescribir `user`
            $request->merge(['firebase_user' => $verifiedIdToken->claims()]);

        } catch (FailedToVerifyToken $e) { // Nuevo nombre de la excepción
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        }

        return $next($request);
    }
}
