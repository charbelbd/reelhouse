<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Person;
use Illuminate\Http\Request;

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
            'sort' => 'nullable|in:newest,oldest,title,rating',
            'per_page' => 'nullable|integer|min:1|max:60',
        ]);

        $query = Movie::with('genres');

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

        if ($request->boolean('has_video')) {
            $query->whereNotNull('video_path');
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
            default:
                $query->orderByDesc('release_date');
        }

        return $query->orderBy('id')->paginate($request->input('per_page', 24));
    }

    public function show(Request $request, $id)
    {
        $movie = Movie::with(['genres', 'cast', 'directors'])->findOrFail($id);

        $data = $movie->toArray();

        return $data;
    }

    public function genres()
    {
        return Genre::withCount('movies')->orderBy('name')->get();
    }
}
