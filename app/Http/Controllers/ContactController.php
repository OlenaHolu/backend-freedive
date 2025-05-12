<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        Mail::raw("Mensaje de: {$data['name']} <{$data['email']}>\n\n{$data['message']}", function ($message) {
            $message->to('oleholu@gmail.com')
                    ->subject('Nuevo mensaje desde el formulario de contacto');
        });

        return response()->json(['message' => 'Mensaje enviado'], 200);
    }
}
