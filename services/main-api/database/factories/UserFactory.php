<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $name = 'User '.Str::random(5);

        return [
            'name' => $name,
            'username' => Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.com',
            'email_verified_at' => now(),
            'password' => 'Password1',
            'remember_token' => Str::random(10),
        ];
    }
}
