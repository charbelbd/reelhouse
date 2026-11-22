<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Screening extends Model
{
    protected $fillable = ['movie_id', 'title', 'starts_at', 'created_by', 'movie_night_id'];

    protected $appends = ['ends_at', 'status', 'room'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function movieNight()
    {
        return $this->belongsTo(MovieNight::class);
    }

    public function getEndsAtAttribute()
    {
        $seconds = $this->movie?->video_duration ?: (($this->movie?->runtime ?: 120) * 60);

        return $this->starts_at?->copy()->addSeconds($seconds);
    }

    public function getStatusAttribute(): string
    {
        $now = now();
        if ($now->lt($this->starts_at)) {
            return 'upcoming';
        }

        return $now->lt($this->ends_at) ? 'live' : 'ended';
    }

    public function getRoomAttribute(): string
    {
        return 'screening-'.$this->id;
    }
}
