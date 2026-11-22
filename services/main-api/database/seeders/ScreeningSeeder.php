<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScreeningSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $movies = Movie::whereNotNull('video_path')->orderBy('id')->get();

        $schedule = [
            [0, now()->addMinutes(2), 'Opening night'],
            [4, now()->addMinutes(15), null],
            [1, now()->addHours(2), 'Sunset session'],
            [2, now()->addDay()->setTime(21, 0), 'Rooftop double feature'],
            [3, now()->addDays(2)->setTime(18, 0), 'Family matinee'],
            [4, now()->addDays(3)->setTime(22, 0), 'Late-night sci-fi'],
            [0, now()->addDays(5)->setTime(20, 0), null],
        ];

        foreach ($schedule as [$index, $startsAt, $title]) {
            if (! isset($movies[$index])) {
                continue;
            }
            Screening::create([
                'movie_id' => $movies[$index]->id,
                'title' => $title,
                'starts_at' => $startsAt,
                'created_by' => $admin->id,
            ]);
        }
    }
}
