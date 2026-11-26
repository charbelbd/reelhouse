<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            ['lobby', 2, 'Maya Haddad', 'maya', 'Hi everyone! Who is joining the next screening?'],
            ['lobby', 3, 'Karim Mansour', 'karim', 'Me! Glass Orbit again, cannot miss it.'],
            ['movie-5', 3, 'Karim Mansour', 'karim', 'That twist at the airlock... no spoilers but WOW.'],
            ['movie-5', 5, 'Omar Fares', 'omar', 'Best villain reveal this year.'],
            ['movie-2', 2, 'Maya Haddad', 'maya', 'The choir scene at the end gets me every time.'],
            ['night-1', 2, 'Maya Haddad', 'maya', 'Vote for the movie please! I will bring popcorn.'],
            ['night-1', 5, 'Omar Fares', 'omar', 'Glass Orbit all the way.'],
        ];

        foreach ($messages as $i => [$room, $userId, $name, $username, $body]) {
            Message::create([
                'room' => $room, 'user_id' => $userId, 'user_name' => $name,
                'username' => $username, 'body' => $body,
                'created_at' => now()->subMinutes(60 - $i * 5),
            ]);
        }
    }
}
