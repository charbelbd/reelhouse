<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'main-api', 'status' => 'ok']);

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

Route::middleware(['auth:sanctum', 'not-banned'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'changePassword']);
    Route::put('me/privacy', [AuthController::class, 'updatePrivacy']);
});
