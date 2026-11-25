<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;
use RuntimeException;

class TmdbController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function status()
    {
        return ['configured' => $this->tmdb->isConfigured()];
    }

    public function popular(Request $request)
    {
        return $this->safe(fn () => $this->tmdb->popular($request->integer('page', 1)));
    }

    public function upcoming(Request $request)
    {
        return $this->safe(fn () => $this->tmdb->upcoming($request->integer('page', 1)));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);

        return $this->safe(fn () => $this->tmdb->search($request->q, $request->integer('page', 1)));
    }

    public function show($tmdbId)
    {
        return $this->safe(fn () => $this->tmdb->details((int) $tmdbId));
    }

    private function safe(callable $callback)
    {
        try {
            return $callback();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
