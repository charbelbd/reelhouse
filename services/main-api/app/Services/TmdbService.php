<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Person;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TmdbService
{
    private string $baseUrl = 'https://api.themoviedb.org/3';

    private string $imageUrl = 'https://image.tmdb.org/t/p/';

    public function isConfigured(): bool
    {
        return ! empty(config('services.tmdb.token')) || ! empty(config('services.tmdb.key'));
    }

    public function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('TMDB is not configured. Add TMDB_API_KEY or TMDB_READ_TOKEN to the .env file.');
        }

        $cacheKey = 'tmdb:'.$path.':'.md5(json_encode($query));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($path, $query) {
            $request = Http::timeout(10)->acceptJson();

            if (config('services.tmdb.token')) {
                $request = $request->withToken(config('services.tmdb.token'));
            } else {
                $query['api_key'] = config('services.tmdb.key');
            }

            $response = $request->get($this->baseUrl.$path, $query);

            if ($response->failed()) {
                throw new RuntimeException('TMDB request failed with status '.$response->status());
            }

            return $response->json();
        });
    }

    public function popular(int $page = 1): array
    {
        return $this->formatList($this->get('/movie/popular', ['page' => $page]));
    }

    public function upcoming(int $page = 1): array
    {
        return $this->formatList($this->get('/movie/upcoming', ['page' => $page]));
    }

    public function search(string $text, int $page = 1): array
    {
        return $this->formatList($this->get('/search/movie', ['query' => $text, 'page' => $page]));
    }

    public function details(int $tmdbId): array
    {
        $data = $this->get('/movie/'.$tmdbId, ['append_to_response' => 'credits,videos']);

        $directors = collect($data['credits']['crew'] ?? [])->where('job', 'Director')->values();
        $trailer = collect($data['videos']['results'] ?? [])
            ->first(fn ($v) => $v['site'] === 'YouTube' && $v['type'] === 'Trailer');

        return [
            'tmdb_id' => $data['id'],
            'title' => $data['title'],
            'overview' => $data['overview'] ?? null,
            'release_date' => $data['release_date'] ?: null,
            'runtime' => $data['runtime'] ?? null,
            'poster_url' => $this->image($data['poster_path'] ?? null, 'w500'),
            'backdrop_url' => $this->image($data['backdrop_path'] ?? null, 'w1280'),
            'tmdb_rating' => isset($data['vote_average']) ? round($data['vote_average'], 1) : null,
            'language' => $data['original_language'] ?? null,
            'trailer_key' => $trailer['key'] ?? null,
            'genres' => collect($data['genres'] ?? [])->map(fn ($g) => ['tmdb_id' => $g['id'], 'name' => $g['name']])->all(),
            'cast' => collect($data['credits']['cast'] ?? [])->take(10)->map(fn ($c) => [
                'tmdb_id' => $c['id'],
                'name' => $c['name'],
                'character' => $c['character'] ?? null,
                'profile_url' => $this->image($c['profile_path'] ?? null, 'w185'),
            ])->all(),
            'directors' => $directors->map(fn ($c) => [
                'tmdb_id' => $c['id'],
                'name' => $c['name'],
                'profile_url' => $this->image($c['profile_path'] ?? null, 'w185'),
            ])->all(),
            'local_id' => Movie::where('tmdb_id', $data['id'])->value('id'),
        ];
    }

    public function import(int $tmdbId): Movie
    {
        $d = $this->details($tmdbId);

        $movie = Movie::updateOrCreate(
            ['tmdb_id' => $d['tmdb_id']],
            collect($d)->only([
                'title', 'overview', 'release_date', 'runtime', 'poster_url', 'backdrop_url',
                'tmdb_rating', 'language', 'trailer_key',
            ])->all()
        );

        $genreIds = [];
        foreach ($d['genres'] as $g) {
            $genre = Genre::firstOrCreate(
                ['slug' => Str::slug($g['name'])],
                ['name' => $g['name'], 'tmdb_id' => $g['tmdb_id']]
            );
            $genreIds[] = $genre->id;
        }
        $movie->genres()->sync($genreIds);

        $people = [];
        foreach ($d['cast'] as $i => $c) {
            $person = Person::updateOrCreate(['tmdb_id' => $c['tmdb_id']], ['name' => $c['name'], 'profile_url' => $c['profile_url']]);
            $people[] = ['person_id' => $person->id, 'role' => 'cast', 'character' => $c['character'], 'order' => $i];
        }
        foreach ($d['directors'] as $c) {
            $person = Person::updateOrCreate(['tmdb_id' => $c['tmdb_id']], ['name' => $c['name'], 'profile_url' => $c['profile_url']]);
            $people[] = ['person_id' => $person->id, 'role' => 'director', 'character' => null, 'order' => 0];
        }
        $movie->people()->detach();
        foreach ($people as $p) {
            $movie->people()->attach($p['person_id'], collect($p)->except('person_id')->all());
        }

        return $movie->load(['genres', 'cast', 'directors']);
    }

    private function formatList(array $data): array
    {
        $ids = collect($data['results'] ?? [])->pluck('id');
        $local = Movie::whereIn('tmdb_id', $ids)->pluck('id', 'tmdb_id');

        return [
            'page' => $data['page'] ?? 1,
            'total_pages' => min($data['total_pages'] ?? 1, 500),
            'results' => collect($data['results'] ?? [])->map(fn ($m) => [
                'tmdb_id' => $m['id'],
                'title' => $m['title'],
                'overview' => $m['overview'] ?? null,
                'release_date' => ($m['release_date'] ?? null) ?: null,
                'poster_url' => $this->image($m['poster_path'] ?? null, 'w342'),
                'tmdb_rating' => isset($m['vote_average']) ? round($m['vote_average'], 1) : null,
                'local_id' => $local[$m['id']] ?? null,
            ])->values()->all(),
        ];
    }

    private function image(?string $path, string $size): ?string
    {
        return $path ? $this->imageUrl.$size.$path : null;
    }
}
