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
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists("api_keys");
  }
};
