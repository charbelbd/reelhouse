<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieNightCandidate extends Model
{
    protected $fillable = ['movie_night_id', 'movie_id', 'added_by'];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function votes()
    {
        return $this->hasMany(MovieNightVote::class, 'candidate_id');
    }
}
