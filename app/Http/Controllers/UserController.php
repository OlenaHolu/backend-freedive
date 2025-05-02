<?php

namespace App\Http\Controllers;

use App\Constants\ErrorCodes;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function update(Request $request)
    {
        $rules = [
            'name'       => 'sometimes|required|string|max:255',
            'email'      => 'sometimes|required|email|max:255|unique:users,email,' . Auth::id(),
            'photo'      => 'nullable|url',
            'password'   => 'nullable|string|min:6|confirmed',
        ];

        $messages = [
            'name.required' => 'ERR_NAME_REQUIRED',
            'email.required' => 'ERR_EMAIL_REQUIRED',
            'email.unique' => 'ERR_EMAIL_TAKEN',
            'email.email'  => 'ERR_EMAIL_INVALID',
            'email.max'    => 'ERR_EMAIL_TOO_LONG',
            'password.confirmed' => 'ERR_PASSWORD_MISMATCH',
            'password.min' => 'ERR_PASSWORD_TOO_SHORT',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();

            if (isset($errors['email'])) {
                foreach ($errors['email'] as $msg) {
                    return match ($msg) {
                        'ERR_EMAIL_REQUIRED'   => response()->json(['errorCode' => ErrorCodes::EMAIL_REQUIRED, 'field' => 'email', 'message' => 'Email is required'], 422),
                        'ERR_EMAIL_TAKEN'      => response()->json(['errorCode' => ErrorCodes::EMAIL_ALREADY_EXISTS, 'field' => 'email', 'message' => 'Email already exists'], 422),
                        'ERR_EMAIL_INVALID'    => response()->json(['errorCode' => ErrorCodes::EMAIL_INVALID, 'field' => 'email', 'message' => 'Invalid email format'], 422),
                        'ERR_EMAIL_TOO_LONG'   => response()->json(['errorCode' => ErrorCodes::EMAIL_TOO_LONG, 'field' => 'email', 'message' => 'Email too long'], 422),
                        default                => null,
                    };
                }
            }

            if (isset($errors['password'])) {
                foreach ($errors['password'] as $msg) {
                    return match ($msg) {
                        'ERR_PASSWORD_MISMATCH' => response()->json(['errorCode' => ErrorCodes::PASSWORD_MISMATCH, 'field' => 'password', 'message' => 'Password mismatch'], 422),
                        'ERR_PASSWORD_TOO_SHORT' => response()->json(['errorCode' => ErrorCodes::PASSWORD_TOO_SHORT, 'field' => 'password', 'message' => 'Password too short'], 422),
                        default                 => null,
                    };
                }
            }

            if (isset($errors['name'])) {
                foreach ($errors['name'] as $msg) {
                    return match ($msg) {
                        'ERR_NAME_REQUIRED' => response()->json(['errorCode' => ErrorCodes::NAME_REQUIRED, 'field' => 'name', 'message' => 'Name is required'], 422),
                        default             => null,
                    };
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
        /** @var \App\Models\User $user **/
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'errorCode' => ErrorCodes::UNAUTHORIZED,
                'message' => 'User not authenticated',
            ], 401);
        }

        DB::beginTransaction();

        try {
            // 1. Eliminar avatar
            if ($user->photo) {
                $avatarFile = basename(parse_url($user->photo, PHP_URL_PATH));

                $response = Http::withToken(env('SUPABASE_SERVICE_ROLE_KEY'))->delete(
                    env('SUPABASE_URL') . "/storage/v1/object/" . env('SUPABASE_BUCKET_AVATARS') . "/{$avatarFile}"
                );

                if ($response->failed()) {
                    throw new \Exception('Failed to delete avatar: ' . $response->body());
                }
            }

            // 2. Delete posts
            $posts = Post::where('user_id', $user->id)->get();
            foreach ($posts as $post) {
                if ($post->image_path) {

                    $response = Http::withToken(env('SUPABASE_SERVICE_ROLE_KEY'))->delete(
                        env('SUPABASE_URL') . "/storage/v1/object/" . env('SUPABASE_BUCKET_POSTS') . "/{$post->image_path}"
                    );

                    if ($response->failed()) {
                        throw new \Exception('Failed to delete post image: ' . $response->body());
                    }
                }
                $post->delete();
            }

            $user->delete();

            DB::commit();

            return response()->json(['message' => 'User and all associated content deleted']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errorCode' => ErrorCodes::INTERNAL_SERVER_ERROR,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }
}
