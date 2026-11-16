<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'main-api', 'status' => 'ok']);
