<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'message' => 'required|string',
        ]);

        // Puedes enviar un correo o guardar en DB
        Mail::raw($validated['message'], function ($message) use ($validated) {
            $message->to('oleholu@gmail.com')
                    ->subject('Nuevo mensaje de contacto')
                    ->from($validated['email'], $validated['name']);
        });

        return response()->json(['message' => 'Mensaje enviado correctamente'], 200);
    }
}
