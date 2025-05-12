<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;

Route::get('/oauth2callback', [OAuthController::class, 'handleCallback']);

Route::get('/', function () {
    return view('welcome');
});
