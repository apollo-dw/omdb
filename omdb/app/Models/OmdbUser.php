<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OmdbUser extends Authenticatable
{
  use HasApiTokens;
  use HasFactory;
  use Notifiable;

  protected $table = "omdb_users";
  protected $primaryKey = "user_id";

  protected $attributes = [
    "banned" => false,
    "custom_ratings" => "{}",
  ];

  protected $fillable = ["user_id", "access_token", "refresh_token"];

  public function osu_user()
  {
    $osu_user = $this->hasOne("App\\Models\\OsuUser", "user_id", "user_id");
    return $osu_user;
  }
}
