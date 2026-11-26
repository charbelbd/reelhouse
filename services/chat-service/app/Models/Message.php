<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['room', 'user_id', 'user_name', 'username', 'avatar_url', 'body', 'created_at'];
}
