<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(20)->by('auth|'.$request->ip()));
        RateLimiter::for('password-email', fn (Request $request) => Limit::perMinute(5)->by('pw|'.$request->ip()));

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('services.frontend_url').'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });
    }
}
