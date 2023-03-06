<?php

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\BeatmapSet;
use App\Models\Comment;
use App\Models\OmdbUser;
use App\Models\OsuUser;
use App\Models\Rating;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportDump extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "omdb:import_dump {path}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Import a legacy OMDB dump";

  private $db;
  private $access_token;

  /**
   * Execute the console command.
   */
  public function handle(): void
  {
    $path = $this->argument("path");
    $this->db = new \PDO("sqlite:{$path}");

    // Get an API key
    $response = Http::asForm()->post("https://osu.ppy.sh/oauth/token", [
      "client_id" => config("auth.osu_client_id"),
      "client_secret" => config("auth.osu_client_secret"),
      "grant_type" => "client_credentials",
      "scope" => "public",
    ]);
    $result = $response->json();
    $this->access_token = $result["access_token"];

    $this->info("Got access token.");

    $client = new GuzzleClient([
      "base_uri" => "https://osu.ppy.sh/api/v2",
      "request.options" => [
        "headers" => "Bearer {$this->access_token}",
      ],
    ]);

    // $this->import_users();
    // $this->import_beatmaps();
    $this->import_ratings();
    $this->import_comments();
  }

  private function import_users()
  {
    $osu_users = [];
    $omdb_users = [];

    // Import omdb users
    foreach ($this->db->query("SELECT * from users") as $row) {
      array_push($osu_users, [
        "user_id" => $row["UserID"],
        "username" => $row["Username"],
      ]);

      array_push($omdb_users, [
        "user_id" => $row["UserID"],
        "access_token" => "",
        "refresh_token" => "",
      ]);
    }

    DB::transaction(function () use ($osu_users, $omdb_users) {
      OsuUser::upsert($osu_users, ["user_id"], ["username"]);
      OmdbUser::upsert(
        $omdb_users,
        ["user_id"],
        ["access_token", "refresh_token"]
      );
    });
    $this->info("Inserted users.");
  }

  private function import_beatmaps()
  {
    $beatmapsets = [];
    $beatmaps = [];
    $creators = [];

    $insert_beatmaps = function ($beatmaps, $beatmapsets, $creators) {
      $this->info(
        "Inserting: " .
          json_encode([
            "beatmaps" => count($beatmaps),
            "beatmapsets" => count($beatmapsets),
            "creators" => count($creators),
          ])
      );

      foreach (array_keys($creators) as $creator) {
        $users = OsuUser::where("user_id", $creator)->count();
        if ($users > 0) {
          continue;
        }

        $response = Http::withToken($this->access_token)
          ->withUrlParameters(["id" => $creator])
          ->get("https://osu.ppy.sh/api/v2/users/{id}");
        $user_json = $response->json();

        if (!array_key_exists("username", $user_json)) {
          $this->info("Could not get user " . $creator);
          $user_json = ["username" => ""];
        }

        OsuUser::updateOrCreate(
          ["user_id" => $creator],
          ["username" => $user_json["username"]]
        );
      }

      $num_beatmapsets = count($beatmapsets);
      if ($num_beatmapsets == 0) {
        return;
      }
      $search_keys = ["id"];
      $keys = array_keys(array_values($beatmapsets)[0]);
      $value_keys = array_diff($keys, $search_keys);
      BeatmapSet::upsert(array_values($beatmapsets), $search_keys, $value_keys);
      $this->info("Inserted {$num_beatmapsets} beatmap sets.");

      $num_beatmaps = count($beatmaps);
      if ($num_beatmaps == 0) {
        return;
      }
      $search_keys = ["id"];
      $keys = array_keys($beatmaps[0]);
      $value_keys = array_diff($keys, $search_keys);
      Beatmap::upsert($beatmaps, $search_keys, $value_keys);
      $this->info("Inserted {$num_beatmaps} beatmaps.");
    };

    foreach ($this->db->query("SELECT * from beatmaps") as $row) {
      $creators[$row["SetCreatorID"]] = 1;
      $creators[$row["CreatorID"]] = 1;

      $beatmapsets[$row["SetID"]] = [
        "id" => $row["SetID"],
        "creator" => "", // TODO: Retrieve this from the OSU api
        "creator_id" => $row["SetCreatorID"],
        "date_ranked" => $row["DateRanked"],
        "artist" => $row["Artist"],
        "title" => $row["Title"],
        "genre" => $row["Genre"],
        "language" => $row["Lang"],
      ];

      array_push($beatmaps, [
        "id" => $row["BeatmapID"],
        "beatmapset_id" => $row["SetID"],
        "creator_id" =>
          $row["SetCreatorID"] == $row["CreatorID"] ? null : $row["CreatorID"],
        "difficulty_name" => $row["DifficultyName"],
        "mode" => $row["Mode"],
        "status" => $row["Status"],
        "star_rating" => $row["SR"],
      ]);

      if (count($beatmaps) > 1024) {
        $insert_beatmaps($beatmaps, $beatmapsets, $creators);
        $beatmaps = [];
        $beatmapsets = [];
        $creators = [];
      }
    }

    $insert_beatmaps($beatmaps, $beatmapsets, $creators);
    $this->info("Inserted beatmaps.");
  }

  private function import_ratings()
  {
    $ratings = [];

    $insert_ratings = function ($ratings) {
      if (count($ratings) == 0) {
        return;
      }
      $search_keys = ["id"];
      $keys = array_keys($ratings[0]);
      $value_keys = array_diff($keys, $search_keys);
      Rating::upsert($ratings, $search_keys, $value_keys);
    };

    foreach ($this->db->query("SELECT * from ratings") as $row) {
      $bm = Beatmap::where("id", $row["BeatmapID"])->first();
      if ($bm == null) {
        continue;
      }

      array_push($ratings, [
        "id" => $row["RatingID"],
        "user_id" => $row["UserID"],
        "beatmap_id" => $row["BeatmapID"],
        "beatmapset_id" => $bm->beatmapset_id,
        "score" => $row["Score"],
        "updated_at" => $row["date"],
      ]);

      if (count($ratings) > 1024) {
        $insert_ratings($ratings);
        $ratings = [];
      }
    }

    $insert_ratings($ratings);
    $this->info("Inserted ratings.");
  }

  private function import_comments()
  {
    $comments = [];

    $insert_comments = function ($comments) {
      if (count($comments) == 0) {
        return;
      }
      $search_keys = ["id"];
      $keys = array_keys($comments[0]);
      $value_keys = array_diff($keys, $search_keys);
      Comment::upsert($comments, $search_keys, $value_keys);
    };

    foreach ($this->db->query("SELECT * from comments") as $row) {
      $bms = BeatmapSet::where("id", $row["SetID"])->count();
      if ($bms == 0) {
        continue;
      }

      array_push($comments, [
        "id" => $row["CommentID"],
        "user_id" => $row["UserID"],
        "beatmapset_id" => $row["SetID"],
        "comment" => $row["Comment"],
        "created_at" => $row["date"],
      ]);

      if (count($comments) > 1024) {
        $insert_comments($comments);
        $comments = [];
      }
    }

    $insert_comments($comments);
    $this->info("Inserted comments.");
  }
}
