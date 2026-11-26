<?php

use App\Http\Controllers\SentimentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'sentiment-service', 'status' => 'ok']);
Route::post('analyze', [SentimentController::class, 'analyze']);
Route::post('analyze/batch', [SentimentController::class, 'batch']);
