<?php

use Illuminate\Support\Facades\Route;
use Google\Client;
use App\Http\Controllers\OAuthController;

Route::get('/oauth2callback', [OAuthController::class, 'handleCallback']);



Route::get('/auth-url', function () {
    $client = new Client();
    $client->setClientId(env('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(env('GMAIL_REDIRECT_URI'));
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

    return redirect()->away($client->createAuthUrl());
});

Route::get('/debug-env', function () {
    return [
        'GOOGLE_CLIENT_ID' => env('GOOGLE_CLIENT_ID'),
        'GOOGLE_CLIENT_SECRET' => env('GOOGLE_CLIENT_SECRET'),
        'GMAIL_REDIRECT_URI' => env('GMAIL_REDIRECT_URI'),
    ];
});



Route::get('/', function () {
    return view('welcome');
});
