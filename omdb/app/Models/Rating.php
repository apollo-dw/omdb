<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
  use HasFactory;

  protected $table = "ratings";

  protected $fillable = ["user_id", "beatmapset_id", "beatmap_id", "score"];

  public function beatmap()
  {
    return $this->hasOne("App\\Models\\Beatmap", "id", "beatmap_id");
  }

  public function osu_user()
  {
    $osu_user = $this->hasOne("App\\Models\\OsuUser", "user_id", "user_id");
    return $osu_user;
  }

  public function omdb_user()
  {
    $omdb_user = $this->hasOne("App\\Models\\OmdbUser", "user_id", "user_id");
    return $omdb_user;
  }
}
