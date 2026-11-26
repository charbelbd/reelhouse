<?php

use App\Models\Movie;
use App\Models\Review;
use App\Models\Screening;
use App\Models\User;
use App\Services\SentimentClient;
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

Artisan::command('reelhouse:analyze-reviews', function (SentimentClient $client) {
    $count = 0;
    Review::whereNull('sentiment')->whereNotNull('body')->each(function ($review) use ($client, &$count) {
        $result = $client->analyze($review->body);
        if ($result) {
            $review->update($result);
            $count++;
        }
    });
    $this->info("$count reviews analysed.");
})->purpose('Send reviews without a sentiment label to the sentiment service');

Artisan::command('reelhouse:install', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::count() === 0) {
        $this->info('Empty database: adding the demo data...');
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migrate, and seed the demo data if the database is empty');

Artisan::command('reelhouse:import-recommended', function (\App\Services\TmdbService $tmdb) {
    if (! $tmdb->isConfigured()) {
        $this->error('TMDB is not configured. Add TMDB_API_KEY to the .env file, then run: docker compose up -d main-api');

        return;
    }

    $films = [
        ['Blade Runner', 1982],
        ['The Matrix', 1999],
        ['Ex Machina', 2014],
        ['Avatar', 2009],
        ['Gravity', 2013],
        ['Oblivion', 2013],
        ['Ready Player One', 2018],
        ['Edge of Tomorrow', 2014],
        ['Mad Max: Fury Road', 2015],
        ['John Wick', 2014],
        ['Top Gun: Maverick', 2022],
        ['Mission: Impossible - Fallout', 2018],
        ['Knives Out', 2019],
        ['Gone Girl', 2014],
        ['Zodiac', 2007],
        ['Shutter Island', 2010],
        ['Parasite', 2019],
        ['Nightcrawler', 2014],
        ['The Shining', 1980],
        ['Hereditary', 2018],
        ['It Follows', 2014],
        ['Whiplash', 2014],
        ['The Social Network', 2010],
        ['Eternal Sunshine of the Spotless Mind', 2004],
        ['Lost in Translation', 2003],
        ['Before Sunrise', 1995],
        ['Howl\'s Moving Castle', 2004],
        ['Klaus', 2019],
        ['Up', 2009],
        ['WALL·E', 2008],
    ];

    $added = $skipped = $failed = 0;
    foreach ($films as [$title, $year]) {
        try {
            $results = $tmdb->get('/search/movie', ['query' => $title, 'year' => $year])['results'] ?? [];
            $hit = collect($results)->first(fn ($r) => strcasecmp($r['title'] ?? '', $title) === 0 && str_starts_with($r['release_date'] ?? '', (string) $year))
                ?? ($results[0] ?? null);

            if (! $hit) {
                $this->warn("  not found  $title ($year)");
                $failed++;
                continue;
            }
            if (Movie::where('tmdb_id', $hit['id'])->exists()) {
                $this->line("  skipped    {$hit['title']} (already in the catalog)");
                $skipped++;
                continue;
            }

            $movie = $tmdb->import((int) $hit['id']);
            $this->info("  added      {$movie->title} ({$year})");
            $added++;
        } catch (\Throwable $e) {
            $this->error("  failed     $title ($year): ".$e->getMessage());
            $failed++;
        }
    }

    $this->newLine();
    $this->info("Done: $added added, $skipped already there, $failed failed.");
})->purpose('Import 30 hand-picked films from TMDB');
