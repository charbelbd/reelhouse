<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Movie extends Model
{
    protected $fillable = [
        'tmdb_id', 'title', 'overview', 'release_date', 'runtime', 'poster_url', 'backdrop_url',
        'trailer_key', 'tmdb_rating', 'language', 'video_path', 'video_duration',
    ];

    protected $hidden = ['video_path'];

    protected $appends = ['video_url', 'has_video', 'release_year'];

    protected function casts(): array
    {
        return [
            'release_date' => 'date:Y-m-d',
            'tmdb_rating' => 'float',
        ];
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_movie', 'movie_id', 'genre_id');
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'movie_person', 'movie_id', 'person_id')
            ->withPivot(['role', 'character', 'order']);
    }

    public function cast()
    {
        return $this->people()->wherePivot('role', 'cast')->orderBy('movie_person.order');
    }

    public function directors()
    {
        return $this->people()->wherePivot('role', 'director');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class);
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? '/media/'.$this->video_path : null;
    }

    public function getHasVideoAttribute(): bool
    {
        return ! empty($this->video_path);
    }

    public function getReleaseYearAttribute(): ?int
    {
        return $this->release_date ? (int) $this->release_date->format('Y') : null;
    }

    protected static function booted(): void
    {
        static::deleting(function (Movie $movie) {
            if ($movie->video_path) {
                Storage::disk('public')->delete($movie->video_path);
            }
            if ($movie->poster_url && str_starts_with($movie->poster_url, '/media/')) {
                Storage::disk('public')->delete(substr($movie->poster_url, 7));
            }
            if ($movie->backdrop_url && str_starts_with($movie->backdrop_url, '/media/')) {
                Storage::disk('public')->delete(substr($movie->backdrop_url, 7));
            }
        });
    }
}
