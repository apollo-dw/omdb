<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create("config", function (Blueprint $table) {
      $table->dateTime("last_beatmap_retrieval")->nullable();
    });

    Schema::create("osu_users", function (Blueprint $table) {
      $table->id("user_id");
      $table->timestamps();

      $table->string("username");

      // TODO: Implement this elsewhere
      $table->boolean("is_banned");
    });

    Schema::create("omdb_users", function (Blueprint $table) {
      $table->id("user_id");
      $table->timestamps();

      $table->text("access_token");
      $table->text("refresh_token");
      $table->boolean("banned")->default(false);
      $table->boolean("do_true_random")->default(false);
      $table->json("custom_ratings")->nullable();

      // Cached info
      $table->float("cached_weight", 6, 4)->nullable();
      $table
        ->foreign("user_id")
        ->references("user_id")
        ->on("osu_users");
    });

    Schema::create("beatmapsets", function (Blueprint $table) {
      $table->id();
      $table->timestamps();

      $table->string("creator");
      $table->integer("creator_id");

      // Metadata
      $table->string("artist");
      $table->string("title");
      $table->integer("genre");
      $table->integer("language");
      $table->dateTime("date_ranked");
      $table->integer("status");

      // Full text index
      $table->fullText(["artist", "title"]);
    });

    Schema::create("beatmaps", function (Blueprint $table) {
      $table->id();
      $table->timestamps();

      // IDs
      $table->foreignId("beatmapset_id");
      $table->boolean("is_guest");
      $table->integer("creator_id");

      // Metadata
      $table->string("difficulty_name");
      $table->integer("mode");
      $table->float("star_rating");
      $table->boolean("blacklisted")->default(false);

      // Cached info
      $table->float("cached_rating")->nullable();
      $table->float("cached_weighted_avg")->nullable();
      $table->integer("cached_rating_count")->nullable();
      $table->integer("cached_chart_rank")->nullable();
      $table->integer("cached_chart_year_rank")->nullable();

      // Foreign keys
      $table
        ->foreign("beatmapset_id")
        ->references("id")
        ->on("beatmapsets");

      // Full text index
      $table->fullText(["difficulty_name"]);
    });

    Schema::create("comments", function (Blueprint $table) {
      $table->id();
      $table->timestamps();

      $table->foreignId("user_id");
      $table->foreignId("beatmapset_id");

      $table->text("comment");

      $table
        ->foreign("beatmapset_id")
        ->references("id")
        ->on("beatmapsets");
      $table
        ->foreign("user_id")
        ->references("user_id")
        ->on("omdb_users");
    });

    Schema::create("ratings", function (Blueprint $table) {
      $table->id();
      $table->timestamps();

      $table->foreignId("user_id");
      $table->foreignId("beatmap_id");
      $table->foreignId("beatmapset_id");

      $table->decimal("score");

      $table
        ->foreign("user_id")
        ->references("user_id")
        ->on("omdb_users");
      $table
        ->foreign("beatmap_id")
        ->references("id")
        ->on("beatmaps");
      $table
        ->foreign("beatmapset_id")
        ->references("id")
        ->on("beatmapsets");
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists("ratings");
    Schema::dropIfExists("comments");
    Schema::dropIfExists("beatmaps");
    Schema::dropIfExists("beatmapsets");
    Schema::dropIfExists("omdb_users");
    Schema::dropIfExists("osu_users");
    Schema::dropIfExists("config");
  }
};
