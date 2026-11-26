<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public function index(Request $request, string $room)
    {
        return Message::where('room', $room)
            ->when($request->filled('before_id'), fn ($q) => $q->where('id', '<', $request->before_id))
            ->latest('id')
            ->take(60)
            ->get()
            ->reverse()
            ->values();
    }

    public function store(Request $request, string $room)
    {
        $data = $request->validate(['body' => 'required|string|max:500']);
        $user = $request->attributes->get('chat_user');

        $key = 'chat:'.$user['id'];
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json(['message' => 'Slow down a little!'], 429);
        }
        RateLimiter::hit($key, 30);

        $message = new Message;
        $message->room = $room;
        $message->user_id = $user['id'];
        $message->user_name = $user['name'];
        $message->username = $user['username'];
        $message->avatar_url = $user['avatar_url'] ?? null;
        $message->body = trim($data['body']);
        $message->save();

        broadcast(new MessageSent($message));

        return response()->json($message, 201);
    }

    public function destroy(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $user = $request->attributes->get('chat_user');

        if ($message->user_id !== $user['id'] && ! ($user['is_admin'] ?? false)) {
            return response()->json(['message' => 'You can only delete your own messages.'], 403);
        }

        $message->delete();
        broadcast(new MessageDeleted($message->room, $message->id));

        return response()->json(['message' => 'Message deleted.']);
    }
}
