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
    Schema::create("api_keys", function (Blueprint $table) {
      $table->string("api_key")->primary();
      $table->timestamps();

      $table->string("name");
      $table->foreignId("user_id");

      $table
        ->foreign("user_id")
        ->references("user_id")
        ->on("omdb_users");
    });

    DB::unprepared("
      CREATE TABLE tmp_ratings SELECT * FROM ratings;
      TRUNCATE TABLE ratings;
      ALTER TABLE ratings ADD UNIQUE INDEX idx_ratings_unique_beatmap_user(beatmap_id, user_id);
      INSERT IGNORE INTO ratings SELECT * from tmp_ratings;
      DROP TABLE tmp_ratings;
    ");

    info("Finished migrating ratings.");
    /*
    Schema::table('ratings', function(Blueprint $table) {
      $table->unique(['beatmap_id', 'user_id'], 'idx_ratings_unique_beatmap_user');
    });
     */
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists("api_keys");

    /* Schema::table('ratings', function(Blueprint $table) {
      $table->dropUnique('idx_ratings_unique_beatmap_user');
    }); */
  }
};
