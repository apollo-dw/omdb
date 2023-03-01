<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('access_token');
            $table->string('refresh_token');
            $table->boolean('banned')->default(false);
            $table->boolean('do_true_random')->default(false);
            $table->json('custom_ratings')->default('{}');

            // Cached info
            $table->float('cached_weight', 6, 4)->nullable();
        });

        Schema::create('usernames', function (Blueprint $table) {
            $table->id('user_id');
            $table->timestamps();

            $table->string('username');
        });

        Schema::create('beatmapsets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('creator_id');

            // Metadata
            $table->string('artist');
            $table->string('title');
            $table->integer('genre');
            $table->integer('language');
            $table->timestamp('date_ranked');
        });

        Schema::create('beatmaps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // IDs
            $table->foreignId('beatmapset_id');
            $table->integer('creator_id')->nullable();

            // Metadata
            $table->string('difficulty_name');
            $table->integer('mode');
            $table->integer('status');
            $table->float('star_rating');

            // Cached info
            $table->float('cached_rating')->nullable();
            $table->float('cached_weighted_avg')->nullable();
            $table->integer('cached_rating_count')->nullable();
            $table->integer('cached_chart_rank')->nullable();
            $table->integer('cached_chart_year_rank')->nullable();

            // Foreign keys
            $table->foreign('beatmapset_id')->references('id')->on('beatmapsets');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id');
            $table->foreignId('beatmapset_id');

            $table->text('comment');

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('beatmap_id');
            $table->decimal('score');

            $table->foreign('beatmap_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beatmaps');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('users');
    }
};
