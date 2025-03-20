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
        $firebaseCredentials = config('firebase.credentials');

        // 🛑 Revisa si realmente es un array antes de pasar a Firebase
        if (!is_array($firebaseCredentials)) {
            \Log::error('Firebase credentials are not an array: ' . json_encode($firebaseCredentials));
            throw new \Exception('Firebase credentials are not an array!');
        }

        $this->auth = (new Factory)
            ->withServiceAccount($firebaseCredentials) // ← 🛑 Este debe ser un array
            ->createAuth();
    }


    private function verifyFirebaseToken($token)
    {
        if (!$token) {
            throw new \InvalidArgumentException('Token not provided');
        }

        $verifiedIdToken = $this->auth->verifyIdToken($token);
        $firebaseUid = $verifiedIdToken->claims()->get('sub');
        $firebaseEmail = $verifiedIdToken->claims()->get('email');

        return [
            'uid' => $firebaseUid,
            'email' => $firebaseEmail,
        ];
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
            $token = $request->bearerToken(); // 🔥 Corrected token retrieval
            $name = $request->input('name');
            $photo = $request->input('photo');

            $firebaseData = $this->verifyFirebaseToken($token);

            // 🔹 Update Firebase User Profile with Name
            $this->auth->updateUser($firebaseData['uid'], [
                'displayName' => $name ?? 'Unknown User',
            ]);

            $user = $this->syncUserWithFirebase($firebaseData['uid'], $firebaseData['email'], $name, $photo);

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
