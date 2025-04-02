<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DiveController;
use App\Http\Controllers\UserController;

//Protected routes
Route::middleware(['firebase'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);
    
    Route::post('/dives', [DiveController::class, 'store']);
    Route::post('/dives/bulk', [DiveController::class, 'storeBulk']);
    Route::get('/dives', [DiveController::class, 'index']);
    Route::get('/dives/{id}', [DiveController::class, 'show']);
    Route::put('/dives/{id}', [DiveController::class, 'update']);
    Route::delete('/dives/{id}', [DiveController::class, 'destroy']);

});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactController::class, 'send']);

Route::get('/', function () {
    return view('welcome');
});

