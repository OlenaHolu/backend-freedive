<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GmailService;

class ContactController extends Controller
{
    public function send(Request $request, GmailService $gmail)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|max:2000',
        ]);

        $html = view('emails.contact', [
            'name' => $data['name'],
            'email' => $data['email'],
            'bodyMessage' => $data['message'],
        ])->render();

        $gmail->send(
            'oleholu@gmail.com',
            'FreediveAnalyzer: New contact message',
            $html
        );

        return response()->json([
            'message' => 'Your message was sent successfully ✅',
        ]);
    }
}
