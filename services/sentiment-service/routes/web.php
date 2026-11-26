<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'Reelhouse sentiment service', 'endpoint' => 'POST /api/analyze']);
