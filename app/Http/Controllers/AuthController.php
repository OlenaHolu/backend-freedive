<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = Auth::guard('api')->login($user);

            return response()->json([
                'message' => 'User registered successfully',
                'user'    => $user,
                'token'   => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Register error: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 1000,
                'error'     => 'Internal server error',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'errorCode' => 1503,
                'error'     => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user'    => Auth::user(),
            'token'   => $token,
            'expires_in' => Auth::factory()->getTTL() * 60,
        ]);
    }

    public function me()
    {
        return response()->json([
            'user' => Auth::user()
        ]);
    }


    public function logout()
    {
        Auth::logout();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        try {
            $refreshToken = JWTAuth::getToken();

            if ($refreshToken) {
                $token = JWTAuth::refresh($refreshToken);
                return response()->json([
                    'token' => $token,
                ]);
            }

            return response()->json([
                'errorCode' => 1101,
                'error' => 'Token not provided',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'errorCode' => 1102,
                'error' => 'Token is invalid or cannot be refreshed',
            ], 500);
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $code = $request->input('code');

            if (!$code) {
                return response()->json([
                    'errorCode' => 1401,
                    'error' => 'Missing Google authorization code',
                ], 400);
            }

            $googleUser = Socialite::driver('google')->stateless()->getAccessTokenResponse($code);

            $token = $googleUser['access_token'];

            $googleUserDetails = Socialite::driver('google')->stateless()->userFromToken($token);

            $user = User::where('email', $googleUserDetails->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUserDetails->getName(),
                    'email' => $googleUserDetails->getEmail(),
                    'photo' => $googleUserDetails->getAvatar() ?: null,
                    'password' => Hash::make(uniqid()), // valor aleatorio
                ]);
            }

            $jwt = Auth::guard('api')->login($user);

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $jwt,
                'expires_in' => Auth::factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'errorCode' => 1500,
                'error' => 'Google login failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
