<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TmdbController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'main-api', 'status' => 'ok']);

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-email');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-email');
Route::post('email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])->middleware('throttle:password-email');

Route::get('auth/providers', [SocialAuthController::class, 'providers']);
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback']);

Route::get('movies', [MovieController::class, 'index']);
Route::get('movies/{id}', [MovieController::class, 'show'])->whereNumber('id');
Route::get('genres', [MovieController::class, 'genres']);

Route::get('tmdb/status', [TmdbController::class, 'status']);
Route::get('tmdb/popular', [TmdbController::class, 'popular']);
Route::get('tmdb/upcoming', [TmdbController::class, 'upcoming']);
Route::get('tmdb/search', [TmdbController::class, 'search']);
Route::get('tmdb/movies/{tmdbId}', [TmdbController::class, 'show'])->whereNumber('tmdbId');

Route::get('screenings', [ScreeningController::class, 'index']);
Route::get('screenings/{id}', [ScreeningController::class, 'show'])->whereNumber('id');
Route::get('time', [ScreeningController::class, 'time']);

Route::get('cinemas', [RecommendationController::class, 'cinemas']);

Route::middleware(['auth:sanctum', 'not-banned'])->group(function () {
    Route::post('email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'send'])->middleware('throttle:password-email');

    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'changePassword']);
    Route::put('me/privacy', [AuthController::class, 'updatePrivacy']);
    Route::put('me/genres', [AuthController::class, 'updateGenres']);

    Route::post('movies/tmdb/{tmdbId}', [MovieController::class, 'fromTmdb'])->whereNumber('tmdbId');

    Route::get('rooms/{room}/access', [RoomController::class, 'access']);
});
