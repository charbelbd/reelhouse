<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieNight extends Model
{
    protected $fillable = [
        'host_id', 'title', 'description', 'scheduled_at', 'location', 'meeting_link', 'status', 'winner_movie_id',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function invites()
    {
        return $this->hasMany(MovieNightInvite::class);
    }

    public function candidates()
    {
        return $this->hasMany(MovieNightCandidate::class);
    }

    public function votes()
    {
        return $this->hasMany(MovieNightVote::class);
    }

    public function winner()
    {
        return $this->belongsTo(Movie::class, 'winner_movie_id');
    }

    public function screening()
    {
        return $this->hasOne(Screening::class);
    }

    public function isParticipant(User $user): bool
    {
        return $this->host_id === $user->id
            || $this->invites()->where('user_id', $user->id)->exists();
    }
}
