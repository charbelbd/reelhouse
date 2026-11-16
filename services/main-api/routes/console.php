<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('reelhouse:install', function () {
    $this->call('migrate', ['--force' => true]);
})->purpose('Migrate, and seed the demo data if the database is empty');
