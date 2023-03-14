<?php

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\BeatmapSet;
use App\Models\OsuUser;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RetrieveBeatmaps extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "omdb:retrieve_beatmaps";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Update the list of beatmaps from OSU";

  /**
   * Execute the console command.
   */
  public function handle(): void
  {
    // Get an API key
    $response = Http::asForm()->post("https://osu.ppy.sh/oauth/token", [
      "client_id" => config("auth.osu_client_id"),
      "client_secret" => config("auth.osu_client_secret"),
      "grant_type" => "client_credentials",
      "scope" => "public",
    ]);
    $result = $response->json();
    $access_token = $result["access_token"];

    $this->info("Got access token.");

    $cursor = null;

    $client = new GuzzleClient([
      "base_uri" => "https://osu.ppy.sh/api/v2",
      "request.options" => [
        "headers" => "Bearer {$access_token}",
      ],
    ]);

    $latest_beatmap = BeatmapSet::latest("date_ranked")->first();

    while (true) {
      $params = [
        "sort" => "ranked_asc",
        "explicit_content" => "show",
        "cursor_string" => $cursor,
      ];

      if ($latest_beatmap !== null) {
        $retrieve_since = $latest_beatmap->date_ranked;
        $date = $retrieve_since->format("Y/m/d");
        $params["query"] = "ranked>={$date}";
        $this->info("Retrieving beatmaps with query " . $params["query"]);
      }

      // Retrieve beatmaps
      $response = Http::withToken($access_token)->get(
        "https://osu.ppy.sh/api/v2/beatmapsets/search",
        $params
      );

      $result = $response->json();
      $beatmapsets = $result["beatmapsets"];

      if (count($beatmapsets) == 0) {
        $this->info("Done.");
        break;
      }

      $osu_users = [];
      $db_beatmapsets = [];
      $db_beatmaps = [];

      $osu_users_to_fetch = [];

      foreach ($beatmapsets as $beatmapset) {
        // TODO: This is slow when running sequentially, need to
        // refactor this to use Guzzle async requests.
        $response = Http::withToken($access_token)
          ->withUrlParameters(["id" => $beatmapset["id"]])
          ->get("https://osu.ppy.sh/api/v2/beatmapsets/{id}");

        $full_beatmapset = $response->json();

        $creator_id = $beatmapset["user_id"];
        $osu_users_to_fetch[$creator_id] = 1;

        array_push($db_beatmapsets, [
          "id" => $beatmapset["id"],
          "creator" => $full_beatmapset["creator"],
          "creator_id" => $beatmapset["user_id"],
          "artist" => $beatmapset["artist"],
          "title" => $beatmapset["title"],
          "genre" => $full_beatmapset["genre"]["id"],
          "language" => $full_beatmapset["language"]["id"],
          "status" => $beatmapset["ranked"],
          "date_ranked" => Carbon::parse($beatmapset["ranked_date"]),
        ]);

        // TODO: Blacklisting
        foreach ($beatmapset["beatmaps"] as $beatmap) {
          $is_guest = $beatmap["user_id"] != $creator_id;

          array_push($db_beatmaps, [
            "id" => $beatmap["id"],
            "beatmapset_id" => $beatmapset["id"],
            "difficulty_name" => $beatmap["version"],
            "mode" => $beatmap["mode_int"],
            "star_rating" => $beatmap["difficulty_rating"],
            "creator_id" => $beatmap["user_id"],
            "is_guest" => $is_guest,
          ]);
        }
      }

      foreach (array_keys($osu_users_to_fetch) as $user_id) {
        $response = Http::withToken($access_token)
          ->withUrlParameters(["user_id" => $user_id])
          ->get("https://osu.ppy.sh/api/v2/users/{user_id}?key=id");

        $mapper = $response->json();
        if (!array_key_exists("id", $mapper)) {
          continue;
        }

        $username = $mapper["username"] ?? null;
        $osu_users[$user_id] = [
          "user_id" => $user_id,
          "username" => $username,
        ];
      }

      OsuUser::upsert(array_values($osu_users), ["user_id"], ["username"]);

      BeatmapSet::insertOrIgnore(
        $db_beatmapsets,
        ["id"],
        [
          "creator",
          "creator_id",
          "artist",
          "title",
          "genre",
          "language",
          "date_ranked",
        ]
      );

      Beatmap::insertOrIgnore(
        $db_beatmaps,
        ["id"],
        ["beatmapset_id", "difficulty_name", "mode", "status", "star_rating"]
      );

      $this->info(
        "Found and inserted " .
          count($osu_users) .
          " users and " .
          count($beatmapsets) .
          " sets."
      );

      $cursor = $result["cursor_string"];
      $this->info("Cursor: " . json_encode($result["cursor"]));
      if ($cursor === null) {
        break;
      }
    }
  }
}
