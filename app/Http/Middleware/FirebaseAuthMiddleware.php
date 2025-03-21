<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

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
                ->withServiceAccount(config('firebase.credentials'))
                ->createAuth();

            $verifiedIdToken = $auth->verifyIdToken($token);

            // Add claims to the request
            $request->merge(['firebase_user' => $verifiedIdToken->claims()->all()]);

        } catch (FailedToVerifyToken $e) { 
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        }

        return $next($request);
    }
}
