<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeatmapSet extends Model
{
  use HasFactory;

  protected $table = "beatmapsets";

  protected $fillable = ["id", "beatmapset_id", "creator", "creator_id"];

  protected $casts = [
    "date_ranked" => "datetime:c",
  ];

  public function creator_user()
  {
    return $this->hasOne("App\\Models\\OsuUser", "user_id", "creator_id");
  }

  public function ratings()
  {
    return $this->hasMany("App\\Models\\Rating", "beatmapset_id");
  }
}
