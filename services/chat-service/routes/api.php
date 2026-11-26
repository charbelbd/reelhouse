<?php

use App\Http\Controllers\BroadcastAuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'chat-service', 'status' => 'ok']);

$room = '(lobby|(movie|screening|night)-[0-9]+)';

Route::middleware('main-api-auth')->group(function () use ($room) {
    Route::post('broadcasting/auth', BroadcastAuthController::class);

    Route::get('rooms/{room}/messages', [ChatController::class, 'index'])->where('room', $room);
    Route::post('rooms/{room}/messages', [ChatController::class, 'store'])->where('room', $room);
    Route::delete('messages/{id}', [ChatController::class, 'destroy'])->whereNumber('id');
});
