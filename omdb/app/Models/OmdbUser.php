<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Http;

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

  public function ratings()
  {
    return $this->hasMany("App\\Models\\Rating", "user_id", "user_id");
  }

  public function comments()
  {
    return $this->hasMany("App\\Models\\Comment", "user_id", "user_id");
  }

  public function random_played_beatmap()
  {
    $sortOrder = ["_asc", "_desc"];
    $sortFields = ["artist", "creator", "ranked", "title", "difficulty"];
    $sortString =
      $sortFields[array_rand($sortFields)] . $sortOrder[array_rand($sortOrder)];

    $randLetter = substr(md5(microtime()), rand(0, 26), 1);

    $first_date = "2007-08-14 10:21:02";
    $second_date = date("Y-m-d");
    $first_time = strtotime($first_date);
    $second_time = strtotime($second_date);
    $rand_time = rand($first_time, $second_time);
    $randDate = date("Y-m-d", $rand_time);

    // TODO: Use the users' authentication token here somehow?
    // Get an API key
    $response = Http::asForm()->post("https://osu.ppy.sh/oauth/token", [
      "client_id" => config("auth.osu_client_id"),
      "client_secret" => config("auth.osu_client_secret"),
      "grant_type" => "client_credentials",
      "scope" => "public",
    ]);
    $result = $response->json();
    $access_token = $result["access_token"];
    info("Got access token.", ["access_token" => $access_token]);

    $response = Http::withToken($access_token)->get(
      "https://osu.ppy.sh/api/v2/beatmapsets/search",
      [
        "played" => "played",
        "status" => "ranked",
        "sort" => $sortString,
        "q" => "{$randLetter} ranked>{$randDate}",
        "m" => 0,
      ]
    );

    info("status " . json_encode($response->status()));
    info("result " . json_encode($response->body()));
    $result = $response->json();

    $beatmapsets = $result["beatmapsets"];
    if (count($beatmapsets) === 0) {
      return null;
    }

    $rand_key = array_rand($beatmapsets);
    $beatmapset = $beatmapsets[$rand_key];
    $id = $beatmapset["id"];

    // TODO: Import the beatmap into OMDB if it's not already

    return BeatmapSet::where("id", $id)->first();
  }
}
