<?php

namespace App\Http\Controllers;

use App\Services\MainApi;
use Illuminate\Http\Request;

class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request, MainApi $mainApi)
    {
        $data = $request->validate([
            'socket_id' => 'required|string|max:100',
            'channel_name' => 'required|string|max:120',
        ]);

        if (! preg_match('/^private-room\.([a-z]+(-\d+)?)$/', $data['channel_name'], $m)) {
            return response()->json(['message' => 'Unknown channel.'], 403);
        }

        $token = $request->bearerToken();
        if (! $token || ! $mainApi->userFromToken($token) || ! $mainApi->canAccess($token, $m[1])) {
            return response()->json(['message' => 'You cannot join this room.'], 403);
        }

        $key = config('reverb.apps.apps.0.key');
        $secret = config('reverb.apps.apps.0.secret');
        $signature = hash_hmac('sha256', $data['socket_id'].':'.$data['channel_name'], $secret);

        return ['auth' => $key.':'.$signature];
    }
}
