<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'name'       => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255|unique:users,email,' . Auth::id(),
            'photo'      => 'nullable|url',
            'password'   => 'nullable|string|min:6|confirmed',
        ]);

        /** @var \App\Models\User $user **/
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'errorCode' => 401,
                'error' => 'User not authenticated',
            ], 401);
        }

        try {
            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->filled('email')) {
                $user->email = $request->email;
            }

            if ($request->filled('photo')) {
                $user->photo = $request->photo;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            if ($request->filled('device_token')) {
                $user->device_token = $request->device_token;
            }
            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Update error: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 1000,
                'error' => 'Failed to update profile',
            ], 500);
        }
    }

    public function destroy()
    {
        try {
            /** @var \App\Models\User $user **/
            $user = Auth::user();
            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());

            return response()->json([
                'errorCode' => 1000,
                'error' => 'Failed to delete user',
            ], 500);
        }
    }
}
