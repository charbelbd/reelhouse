<?php

use App\Models\Message;
use Illuminate\Support\Facades\Artisan;

Artisan::command('reelhouse:install', function () {
    $this->call('migrate', ['--force' => true]);

    if (Message::count() === 0) {
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migrate, and seed demo chat messages if the database is empty');

Artisan::command('chat:cleanup', function () {
    $n = \App\Models\RoomPresence::where('last_seen_at', '<', now()->subHour())->delete();
    $this->info("$n old presence rows deleted.");
})->purpose('Remove old "who is in the room" rows');
