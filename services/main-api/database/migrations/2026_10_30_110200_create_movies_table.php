<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->unique();
            $table->string('title', 200);
            $table->text('overview')->nullable();
            $table->date('release_date')->nullable();
            $table->unsignedInteger('runtime')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->string('trailer_key')->nullable();
            $table->decimal('tmdb_rating', 3, 1)->nullable();
            $table->string('language', 10)->nullable();

            $table->string('video_path')->nullable();
            $table->unsignedInteger('video_duration')->nullable();

            $table->timestamps();
        });

        Schema::create('genre_movie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->unique(['movie_id', 'genre_id']);
        });

        Schema::create('movie_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('character', 150)->nullable();
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('genre_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_user');
        Schema::dropIfExists('movie_person');
        Schema::dropIfExists('genre_movie');
        Schema::dropIfExists('movies');
    }
};
