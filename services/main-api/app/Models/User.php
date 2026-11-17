<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'avatar_url', 'bio',
        'role', 'is_banned', 'provider', 'provider_id',
        'show_reviews', 'show_ratings', 'show_lists', 'show_watch_activity', 'show_follows',
    ];

    protected $hidden = ['password', 'remember_token', 'provider_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'show_reviews' => 'boolean',
            'show_ratings' => 'boolean',
            'show_lists' => 'boolean',
            'show_watch_activity' => 'boolean',
            'show_follows' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function watchlistItems()
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function movieLists()
    {
        return $this->hasMany(MovieList::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function favoriteGenres()
    {
        return $this->belongsToMany(Genre::class, 'genre_user', 'user_id', 'genre_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function isFollowing(User $other): bool
    {
        return $this->following()->where('following_id', $other->id)->exists();
    }
}
