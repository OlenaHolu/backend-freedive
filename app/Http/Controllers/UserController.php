<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
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

public function destroy(Request $request)
{
    $firebaseUser = $request->firebase_user;
    $uid = $firebaseUser['sub'] ?? null;

    if (!$uid) {
        return response()->json(['error' => 'UID no encontrado'], 400);
    }

    // Buscar el usuario en tu base de datos
    $user = User::where('email', $firebaseUser['email'])->first();

    if (!$user) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }

    try {
        // Eliminar de Firebase Auth
        $auth = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->createAuth();

        $auth->deleteUser($uid);
    } catch (UserNotFound $e) {
        // Ya no existe en Firebase, ignoramos
        \Log::warning("Usuario no encontrado en Firebase: $uid");
    } catch (\Throwable $e) {
        return response()->json(['error' => 'Error eliminando usuario en Firebase', 'details' => $e->getMessage()], 500);
    }

    // Eliminar de la base de datos local
    $user->delete();

    return response()->json(['message' => 'Usuario eliminado correctamente']);
}


    
}
