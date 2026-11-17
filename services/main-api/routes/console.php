<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('reelhouse:install', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::count() === 0) {
        $this->info('Empty database: adding the demo data...');
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migrate, and seed the demo data if the database is empty');
