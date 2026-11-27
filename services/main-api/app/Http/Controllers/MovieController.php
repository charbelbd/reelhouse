<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Person;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use RuntimeException;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'genre' => 'nullable|string|max:300',
            'year' => 'nullable|integer|min:1880|max:2100',
            'year_from' => 'nullable|integer|min:1880|max:2100',
            'year_to' => 'nullable|integer|min:1880|max:2100',
            'runtime_min' => 'nullable|integer|min:1|max:1000',
            'runtime_max' => 'nullable|integer|min:1|max:1000',
            'min_tmdb' => 'nullable|numeric|min:0|max:10',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort' => 'nullable|in:newest,oldest,title,rating,user_rating',
            'per_page' => 'nullable|integer|min:1|max:60',
        ]);

        $query = Movie::with('genres')
            ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'rating');

        if ($request->filled('q')) {
            $text = $request->q;
            $query->where(function ($q) use ($text) {
                $q->where('title', 'like', '%'.$text.'%')
                    ->orWhereHas('people', fn ($p) => $p->where('name', 'like', '%'.$text.'%'));
            });
        }

        if ($request->filled('genre')) {
            $genres = array_filter(array_map('trim', explode(',', $request->genre)));
            $query->whereHas('genres', fn ($g) => $g->whereIn('slug', $genres)->orWhereIn('genres.id', $genres));
        }

        if ($request->filled('year')) {
            $query->whereYear('release_date', $request->year);
        }
        if ($request->filled('year_from')) {
            $query->whereYear('release_date', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->whereYear('release_date', '<=', $request->year_to);
        }

        if ($request->filled('runtime_min')) {
            $query->where('runtime', '>=', $request->runtime_min);
        }
        if ($request->filled('runtime_max')) {
            $query->where('runtime', '<=', $request->runtime_max);
        }

        if ($request->filled('min_tmdb')) {
            $query->where('tmdb_rating', '>=', $request->min_tmdb);
        }

        if ($request->filled('min_rating')) {
            $query->whereRaw(
                '(SELECT AVG(rating) FROM reviews WHERE reviews.movie_id = movies.id AND reviews.is_hidden = 0) >= CAST(? AS DECIMAL(3,2))',
                [(float) $request->min_rating]
            );
        }

        if ($request->boolean('has_video')) {
            $query->whereNotNull('video_path');
        }

        if ($request->filled('person')) {
            $query->whereHas('people', fn ($p) => $p->where('people.id', $request->person));
        }

        switch ($request->input('sort', 'newest')) {
            case 'oldest':
                $query->orderBy('release_date', 'asc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'rating':
                $query->orderByDesc('tmdb_rating');
                break;
            case 'user_rating':
                $query->orderByDesc('reviews_avg_rating');
                break;
            default:
                $query->orderByDesc('release_date');
        }

        return $query->orderBy('id')->paginate($request->input('per_page', 24));
    }

    public function show(Request $request, $id)
    {
        $movie = Movie::with(['genres', 'cast', 'directors'])
            ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'rating')
            ->findOrFail($id);

        $data = $movie->toArray();

        $data['screenings'] = $movie->screenings()->with('movie')->whereNull('movie_night_id')
            ->where('starts_at', '>', now()->subHours(4))->orderBy('starts_at')->get()
            ->filter(fn ($s) => $s->status !== 'ended')->values();

        return $data;
    }

    public function genres()
    {
        return Genre::withCount('movies')->orderBy('name')->get();
    }

    public function person($id)
    {
        $person = Person::findOrFail($id);
        $movies = $person->movies()->with('genres')->orderByDesc('release_date')->get();

        return ['person' => $person, 'movies' => $movies];
    }

    public function fromTmdb($tmdbId, TmdbService $tmdb)
    {
        $existing = Movie::where('tmdb_id', $tmdbId)->first();
        if ($existing) {
            return response()->json(['id' => $existing->id]);
        }

        try {
            $movie = $tmdb->import((int) $tmdbId);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json(['id' => $movie->id], 201);
    }
}
