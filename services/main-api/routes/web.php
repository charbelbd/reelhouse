<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => 'Reelhouse main API',
    'docs' => 'All endpoints start with /api (see the Postman collection).',
]));
