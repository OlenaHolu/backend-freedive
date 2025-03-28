<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

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
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
    
        if ($request->has('name')) {
            $user->name = $request->input('name');
        }
    
        if ($request->has('avatar_url')) {
            $user->photo = $request->input('avatar_url');
        }
    
        $user->save();
    
        return response()->json(['message' => 'Perfil actualizado', 'user' => $user]);
    }
    
}
