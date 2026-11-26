<?php

namespace App\Http\Middleware;

use App\Services\MainApi;
use Closure;
use Illuminate\Http\Request;

class AuthenticateWithMainApi
{
    public function __construct(private MainApi $mainApi) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $user = $token ? $this->mainApi->userFromToken($token) : null;

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('chat_user', $user);

        $room = $request->route('room');
        if ($room && ! $this->mainApi->canAccess($token, $room)) {
            return response()->json(['message' => 'You cannot join this room.'], 403);
        }

        return $next($request);
    }
}
