<?php

namespace App\Models;

use App\Models\OmdbUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
  use HasFactory;

  protected $table = "comments";

  protected $fillable = ["user_id", "beatmapset_id", "comment"];

  public function beatmapset()
  {
    return $this->hasOne("App\\Models\\BeatmapSet", "id", "beatmapset_id");
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
