<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_nights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at');
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('status', 20)->default('voting');
            $table->foreignId('winner_movie_id')->nullable()->constrained('movies')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('movie_night_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_night_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['movie_night_id', 'user_id']);
        });

        Schema::create('movie_night_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_night_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['movie_night_id', 'movie_id']);
        });

        Schema::create('movie_night_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_night_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('movie_night_candidates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['movie_night_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_night_votes');
        Schema::dropIfExists('movie_night_candidates');
        Schema::dropIfExists('movie_night_invites');
        Schema::dropIfExists('movie_nights');
    }
};
