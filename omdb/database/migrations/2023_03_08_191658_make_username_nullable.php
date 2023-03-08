<?php

/*
 * Motivation for this change:
 *
 * Some users cannot be fetched through the osu API anymore, and we won't be
 * able to get their usernames at all. For displaying the beatmap info, we can
 * try to substitute with the creator name given in the map, but that should be
 * done in the renderer rather than the database layer.
 *
 * Case: HolyCOW https://osu.ppy.sh/beatmapsets/25#osu/165
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table("osu_users", function (Blueprint $table) {
      $table
        ->string("username")
        ->nullable()
        ->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table("osu_users", function (Blueprint $table) {
      $table->string("username")->change();
    });
  }
};
