<?php

namespace App\Http\Controllers;

use App\Services\CinemaService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function cinemas(Request $request, CinemaService $cinemas)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1000|max:50000',
        ]);

        $list = $cinemas->nearby($data['lat'], $data['lon'], $data['radius'] ?? 15000);

        if ($list === null) {
            return response()->json(['message' => 'The map service is not reachable right now.', 'detail' => $cinemas->lastError, 'cinemas' => []], 503);
        }

        return ['cinemas' => $list];
    }
}
