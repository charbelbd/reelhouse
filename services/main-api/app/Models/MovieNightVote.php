<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieNightVote extends Model
{
    protected $fillable = ['movie_night_id', 'candidate_id', 'user_id'];
}
