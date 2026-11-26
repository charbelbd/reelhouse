<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MainApi
{
    public function userFromToken(string $token): ?array
    {
        return Cache::remember('user:'.sha1($token), 60, function () use ($token) {
            try {
                $response = Http::timeout(5)->acceptJson()->withToken($token)
                    ->get(config('services.main_api.url').'/api/me');

                return $response->successful() ? $response->json() : null;
            } catch (Throwable $e) {
                return null;
            }
        });
    }

    public function canAccess(string $token, string $room): bool
    {
        if ($room === 'lobby' || str_starts_with($room, 'movie-')) {
            return true;
        }

        return Cache::remember('access:'.sha1($token).':'.$room, 60, function () use ($token, $room) {
            try {
                $response = Http::timeout(5)->acceptJson()->withToken($token)
                    ->get(config('services.main_api.url').'/api/rooms/'.$room.'/access');

                return $response->successful() && $response->json('allowed') === true;
            } catch (Throwable $e) {
                return false;
            }
        });
    }
}
