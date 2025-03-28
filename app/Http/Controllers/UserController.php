<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $claims = $request->firebase_user ?? null;

        if (!$claims || !isset($claims['email'])) {
            return response()->json(['error' => 'Token no válido'], 401);
        }

        $user = User::where('email', $claims['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // ✅ Validación elegante
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'photo' => 'nullable|string',
            // puedes agregar más campos aquí
            // 'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Datos inválidos', 'details' => $validator->errors()], 422);
        }

        // ✅ Asignación controlada
        $user->name = $request->input('name');

        if ($request->filled('photo')) {
            $user->photo = $request->input('photo');
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
            ]
        ]);
    }
}
