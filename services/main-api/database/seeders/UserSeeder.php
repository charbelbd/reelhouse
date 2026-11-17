<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@reelhouse.test'], [
            'name' => 'Reelhouse Admin',
            'username' => 'admin',
            'password' => 'Admin1234',
            'role' => 'admin',
            'bio' => 'I keep the projector running.',
            'email_verified_at' => now(),
        ]);

        $users = [
            ['maya', 'Maya Haddad', 'Rainy days, long dramas, and anything with a good score.', ['drama', 'romance', 'mystery']],
            ['karim', 'Karim Mansour', 'Sci-fi first, questions later.', ['science-fiction', 'thriller', 'action']],
            ['lea', 'Lea Khalil', 'Animation is cinema. Fight me.', ['animation', 'family', 'fantasy']],
            ['omar', 'Omar Fares', 'Crime, thrillers and the occasional comedy.', ['crime', 'thriller', 'comedy']],
            ['nour', 'Nour Salameh', 'Documentaries and music films.', ['documentary', 'music', 'history']],
        ];

        foreach ($users as [$username, $name, $bio, $genres]) {
            $user = User::updateOrCreate(['email' => $username.'@reelhouse.test'], [
                'name' => $name,
                'username' => $username,
                'password' => 'Password1',
                'bio' => $bio,
                'email_verified_at' => now(),
            ]);
        }

        User::where('username', 'nour')->update(['show_watch_activity' => false]);
    }
}
