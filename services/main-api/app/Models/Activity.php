<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['user_id', 'type', 'movie_id', 'movie_list_id', 'target_user_id', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function movieList()
    {
        return $this->belongsTo(MovieList::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public static function log(User $user, string $type, array $data = []): Activity
    {
        return self::create(array_merge(['user_id' => $user->id, 'type' => $type], $data));
    }

    public static function privacyFlagFor(string $type): ?string
    {
        return [
            'reviewed' => 'show_reviews',
            'rated' => 'show_ratings',
            'created_list' => 'show_lists',
            'watched' => 'show_watch_activity',
            'followed' => 'show_follows',
        ][$type] ?? null;
    }
}
