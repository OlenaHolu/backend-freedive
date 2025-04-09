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
                return response()->json([
                    'errorCode' => 1401,
                    'error' => 'Invalid or expired token',
                ], 401);
            }

            $claims = $request->firebase_user;
            $user = User::where('email', $claims['email'])->first();

            return response()->json([
                'user' => [
                    'id' => $claims['sub'],
                    'email' => $claims['email'],
                    'name' => $user->name ?? $claims['name'] ?? 'Freedive Analyzer User',
                    'photo' => $user->photo ?? $claims['picture'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 1000,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json([
                    'errorCode' => 1101,
                    'error' => 'Token not provided',
                ], 401);
            }

            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUser = $verifiedIdToken->claims();

            $email = $firebaseUser->get('email');
            $uid = $firebaseUser->get('sub');
            $photo = $firebaseUser->get('picture') ?? null;
            $name = $request->input('name') ?? $firebaseUser->get('name') ?? 'Freedive Analyzer User';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'firebase_uid' => $uid,
                    'name' => $name,
                    'photo' => $photo,
                ]
            );

            return response()->json([
                'message' => 'User registered successfully', 
                'user' => $user
            ]);
        } catch (FailedToVerifyToken $e) {
            return response()->json([
                'errorCode' => 1401,
                'error' => 'Invalid Firebase token'
            ], 401);
        } catch (\Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 1000,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            if (!$token) {
                return response()->json([
                    'errorCode' => 1101,
                    'error' => 'Token not provided',
                ], 401);
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
                    'name' => $firebaseUser->get('name') ?? 'Freedive Analyzer User',
                    'email' => $email,
                    'firebase_uid' => $firebaseUser->get('sub'),
                    'photo' => $photo
                ]);
            }

            return response()->json([
                'message' => 'Login successful', 
                'user' => $user
            ]);
        } catch (FailedToVerifyToken $e) {
            return response()->json([
                'errorCode' => 1401,
                'error' => 'Invalid Firebase token'
            ], 401);
        } catch (\Exception $e) {
            Log::error('Error logging in user: ' . $e->getMessage());
            
            return response()->json([
                'errorCode' => 1000,
                'error' => 'Internal server error'
            ], 500);
        }
    }

}
