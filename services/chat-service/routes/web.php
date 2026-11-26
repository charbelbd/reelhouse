<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'Reelhouse chat service', 'websocket' => 'Laravel Reverb']);
