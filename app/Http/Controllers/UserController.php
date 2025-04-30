<?php

namespace App\Http\Controllers;

use App\Constants\ErrorCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Error;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function update(Request $request)
    {
        $rules = [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'photo'      => 'nullable|url',
            'password'   => 'nullable|string|min:6|confirmed',
        ];
    
        $messages = [
            'email.unique' => 'ERR_EMAIL_TAKEN',
            'email.email'  => 'ERR_EMAIL_INVALID',
            'email.max'    => 'ERR_EMAIL_TOO_LONG',
            'password.confirmed' => 'ERR_PASSWORD_MISMATCH',
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
    
            if (isset($errors['email'])) {
                foreach ($errors['email'] as $msg) {
                    if ($msg === 'ERR_EMAIL_TAKEN') {
                        return response()->json([
                            'errorCode' => ErrorCodes::EMAIL_ALREADY_EXISTS,
                            'field' => 'email',
                            'message' => 'Email already in use',
                        ], 422);
                    }
                    if ($msg === 'ERR_EMAIL_INVALID') {
                        return response()->json([
                            'errorCode' => ErrorCodes::EMAIL_INVALID,
                            'field' => 'email',
                            'message' => 'Invalid email format',
                        ], 422);
                    }
                    if ($msg === 'ERR_EMAIL_TOO_LONG') {
                        return response()->json([
                            'errorCode' => ErrorCodes::EMAIL_TOO_LONG,
                            'field' => 'email',
                            'message' => 'Email is too long',
                        ], 422);
                    }
                }
            }
    
            if (isset($errors['password'])) {
                if (in_array('ERR_PASSWORD_MISMATCH', $errors['password'])) {
                    return response()->json([
                        'errorCode' => ErrorCodes::PASSWORD_MISMATCH,
                        'field' => 'password',
                        'message' => 'Passwords do not match',
                    ], 422);
                }
            }
            // Handle other validation errors
            return response()->json([
                'errorCode' => ErrorCodes::VALIDATION_FAILED,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }
    
        /** @var \App\Models\User $user **/
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'errorCode' => ErrorCodes::UNAUTHORIZED,
                'message' => 'User not authenticated',
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
                'errorCode' => ErrorCodes::INTERNAL_SERVER_ERROR,
                'message' => 'Failed to update profile',
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
                'errorCode' => ErrorCodes::INTERNAL_SERVER_ERROR,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }
}
