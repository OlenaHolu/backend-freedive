<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;

class OAuthController extends Controller
{
    public function handleCallback(Request $request)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GMAIL_REDIRECT_URI'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

        return response()->json([
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? '⚠️ No refresh token available',
            'expires_in' => $token['expires_in'] ?? null,
        ]);
    }
}
