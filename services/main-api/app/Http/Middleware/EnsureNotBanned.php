<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_banned) {
            $request->user()->currentAccessToken()?->delete();

            return response()->json(['message' => 'Your account has been suspended by an administrator.'], 403);
        }

        return $next($request);
    }
}
