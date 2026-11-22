<?php

namespace App\Http\Controllers;

use App\Models\Screening;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $screenings = Screening::with('movie.genres')
            ->whereNull('movie_night_id')
            ->where('starts_at', '>', now()->subHours(6))
            ->where('starts_at', '<', now()->addDays($request->integer('days', 14)))
            ->orderBy('starts_at')
            ->get()
            ->filter(fn ($s) => $s->status !== 'ended')
            ->values();

        return [
            'server_time' => now()->toIso8601String(),
            'live' => $screenings->where('status', 'live')->values(),
            'upcoming' => $screenings->where('status', 'upcoming')->values(),
        ];
    }

    public function show(Request $request, $id)
    {
        $screening = Screening::with(['movie.genres', 'movie.directors'])->findOrFail($id);

        $data = $screening->toArray();
        $data['server_time'] = now()->toIso8601String();
        $data['position_seconds'] = round($screening->starts_at->diffInSeconds(now(), false), 2);
        $data['video_url'] = $screening->movie->video_url;

        return $data;
    }

    public function time()
    {
        return ['now' => now()->toIso8601String(), 'timestamp_ms' => (int) floor(microtime(true) * 1000)];
    }
}
