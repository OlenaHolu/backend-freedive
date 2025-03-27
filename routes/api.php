<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiveController;

//Protected routes
Route::middleware(['firebase'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/dives', [DiveController::class, 'store']);
    Route::post('/dives/bulk', [DiveController::class, 'storeBulk']);
    Route::get('/dives', [DiveController::class, 'index']);
    Route::put('/dives/{id}', [DiveController::class, 'update']);
    Route::delete('/dives/{id}', [DiveController::class, 'destroy']);

});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/', function () {
    return view('welcome');
});

