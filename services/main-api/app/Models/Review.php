<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'movie_id', 'rating', 'body', 'sentiment', 'sentiment_score', 'is_hidden'];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'rating' => 'integer',
            'sentiment_score' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
