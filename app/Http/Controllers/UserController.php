<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\UserNotFound;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'avatar_url' => 'nullable|url'
        ]);
    
        $firebaseUser = $request->firebase_user;
    
        $user = User::where('email', $firebaseUser['email'])->first();
        if (!$user) {
            return response()->json([
                'errorCode' => 1501,
                'error' => 'User not registered'
            ], 404);
        }
    
        if ($request->has('name')) {
            $user->name = $request->input('name');
        }
    
        if ($request->has('avatar_url')) {
            $user->photo = $request->input('avatar_url');
        }
    
        $user->save();
    
        return response()->json([
            'message' => 'Profile was updated successfully', 
            'user' => $user
        ]);
    }

public function destroy(Request $request)
{
    $firebaseUser = $request->firebase_user;
    $uid = $firebaseUser['uid'];
    $user = User::where('email', $firebaseUser['email'])->first();

    if (!$user) {
        return response()->json([
            'errorCode' => 1501,
            'error' => 'User not registered'
        ], 404);
    }

    try {
        // Delete from Firebase Auth
        $auth = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth();

        $auth->deleteUser($uid);

    } catch (UserNotFound $e) {
        Log::warning("User not found in Firebase: $uid");

    } catch (\Throwable $e) {
        Log::error('Firabase delete error', [
            'error' => $e->getMessage(),
            'uid' => $uid,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'errorCode' => 1406,
            'error' => 'Failded to delete user from Firebase'
        ], 500);
    }

    $user->delete();

    return response()->json([
        'message' => 'User was deleted successfully']);
}   
}
