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
     * Verifies the Firebase token and retrieves user data.
     */
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

    /**
     * Fetches user details from Firebase and updates the local database.
     */
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
            $token = $request->input('firebase_token');
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
            $token = $request->input('firebase_token');
            $name = $request->input('name');
            $photo = $request->input('photo');

            $firebaseData = $this->verifyFirebaseToken($token);

            // Update Firebase User Profile with Name
            $this->auth->updateUser($firebaseData['uid'], [
                'displayName' => $name ?? 'Unknown User',
            ]);

            $user = $this->syncUserWithFirebase($firebaseData['uid'], $firebaseData['email'], $name, $photo);

            return response()->json(['message' => 'User registered successfully', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $token = $request->input('firebase_token');
            $firebaseData = $this->verifyFirebaseToken($token);

            $user = $this->syncUserWithFirebase($firebaseData['uid'], $firebaseData['email']);

            return response()->json(['message' => 'Login exitoso', 'user' => $user]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid Firebase token'], 401);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }
}
