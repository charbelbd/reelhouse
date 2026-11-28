<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieNight;
use App\Models\Screening;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function access(Request $request, string $room)
    {
        $user = $request->user();

        if ($room === 'lobby') {
            return ['allowed' => true, 'title' => 'Lobby'];
        }

        if (! preg_match('/^(movie|screening|night)-(\d+)$/', $room, $m)) {
            return response()->json(['allowed' => false, 'message' => 'Unknown room.'], 404);
        }

        [$all, $type, $id] = $m;

        if ($type === 'movie') {
            $movie = Movie::findOrFail($id);

            return ['allowed' => true, 'title' => $movie->title.' discussion'];
        }

        if ($type === 'screening') {
            $s = Screening::with(['movie', 'movieNight'])->findOrFail($id);
            $allowed = ! $s->movie_night_id || $s->movieNight->isParticipant($user);

            return response()->json(['allowed' => $allowed, 'title' => 'Live: '.$s->movie->title], $allowed ? 200 : 403);
        }

        $night = MovieNight::findOrFail($id);
        $allowed = $night->isParticipant($user);

        return response()->json(['allowed' => $allowed, 'title' => $night->title], $allowed ? 200 : 403);
    }
}
