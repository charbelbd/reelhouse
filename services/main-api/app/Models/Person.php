<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'people';

    protected $fillable = ['tmdb_id', 'name', 'profile_url'];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_person', 'person_id', 'movie_id')
            ->withPivot(['role', 'character', 'order']);
    }
}
