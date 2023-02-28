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
            $table->boolean('banned');
            $table->boolean('do_true_random');
            $table->json('custom_ratings');

            // Cached info
            $table->float('cached_weight', 6, 4)->nullable();
        });

        Schema::create('beatmaps', function (Blueprint $table) {
            $table->id('beatmap_id');
            $table->timestamps();

            // IDs
            $table->integer('set_id');
            $table->integer('creator_id');
            $table->integer('set_creator_id');

            // Metadata
            $table->string('difficulty_name');
            $table->string('artist');
            $table->string('title');
            $table->integer('mode');
            $table->integer('status');
            $table->integer('genre');
            $table->integer('language');
            $table->float('star_rating');
            $table->timestamp('date_ranked');

            // Cached info
            $table->float('cached_rating')->nullable();
            $table->float('cached_weighted_avg')->nullable();
            $table->integer('cached_rating_count')->nullable();
            $table->integer('cached_chart_rank')->nullable();
            $table->integer('cached_chart_year_rank')->nullable();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id');
            $table->foreignId('set_id');

            $table->text('comment');

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
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
