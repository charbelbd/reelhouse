<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Only administrators can do this.'], 403);
        }

        return $next($request);
    }
}
