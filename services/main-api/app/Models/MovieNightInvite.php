<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieNightInvite extends Model
{
    protected $fillable = ['movie_night_id', 'user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movieNight()
    {
        return $this->belongsTo(MovieNight::class);
    }
}
