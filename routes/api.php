<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//Protected routes
Route::middleware(['firebase'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/', function () {
    return view('welcome');
});

