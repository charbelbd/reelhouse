<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('room', 60)->index();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 100);
            $table->string('username', 50);
            $table->string('avatar_url')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('room_presences', function (Blueprint $table) {
            $table->id();
            $table->string('room', 60);
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 100);
            $table->string('username', 50);
            $table->string('avatar_url')->nullable();
            $table->timestamp('last_seen_at');
            $table->unique(['room', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_presences');
        Schema::dropIfExists('messages');
    }
};
