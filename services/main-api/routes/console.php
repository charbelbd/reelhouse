<?php

use App\Models\Movie;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('reelhouse:screening {movie?} {--in=1}', function ($movie = null) {
    $film = $movie ? Movie::findOrFail($movie) : Movie::whereNotNull('video_path')->inRandomOrder()->first();
    if (! $film || ! $film->has_video) {
        $this->error('This movie has no video.');

        return;
    }

    $screening = Screening::create([
        'movie_id' => $film->id,
        'starts_at' => now()->addMinutes((int) $this->option('in')),
        'created_by' => User::where('role', 'admin')->value('id'),
    ]);

    $this->info("Scheduled \"{$film->title}\" at {$screening->starts_at} (screening #{$screening->id}).");
})->purpose('Schedule a live screening that starts soon (for demos)');

Artisan::command('reelhouse:install', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::count() === 0) {
        $this->info('Empty database: adding the demo data...');
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migrate, and seed the demo data if the database is empty');
