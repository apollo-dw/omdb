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
  protected $signature = "omdb:import_dump {path} {--cache=}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Import a legacy OMDB dump";

  private $db;
  private $cache_db;
  private $client;
  private $access_token;

  /**
   * Execute the console command.
   */
  public function handle(): void
  {
    $path = $this->argument("path");
    $this->db = new \PDO("sqlite:{$path}");

    $cache_path = $this->option("cache");
    $cache = $cache_path !== null;
    if ($cache) {
      $this->cache_db = new \PDO("sqlite:{$cache_path}");
      $this->cache_db->exec("
        create table if not exists osus_api (
          key string primary key,
          status int,
          value json
        )
      ");
      $this->info("Using cache.");
    }

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

    $this->client = new GuzzleClient([
      "base_uri" => "https://osu.ppy.sh/api/v2",
      "headers" => [
        "Authorization" => "Bearer {$this->access_token}",
      ],
    ]);

    $this->import_users();
    $this->import_beatmaps();
    $this->import_ratings();
    $this->import_comments();
    $this->import_blacklisted_users();
  }

  private function api_request(string $method, string $url, $options)
  {
    $get_result = function ($method, $url, $options) {
      $options["http_errors"] = false;
      $response = $this->client->request($method, $url, $options);
      $status = $response->getStatusCode();
      $body = null;
      if ($status === 200) {
        $body = json_decode($response->getBody(), true);
      }
      return ["status" => $status, "body" => $body];
    };

    if ($this->cache_db === null) {
      return $get_result($method, $url, $options);
    }

    $key_json = [
      "method" => $method,
      "url" => $url,
    ];
    ksort($key_json); // OOF
    $key = json_encode($key_json);

    $stmt = $this->cache_db->prepare(
      "SELECT status, value FROM osus_api WHERE key = ?"
    );
    $stmt->execute([$key]);
    $result = $stmt->fetch();

    if ($result !== false) {
      // $this->info("Cache hit on {$key}, status " . $result['status']);
      if ($result["status"] != 504) {
        return [
          "status" => $result["status"],
          "body" => json_decode($result["value"], true),
        ];
      }
    }

    // $this->info("Cache miss on {$key}, making a request instead.");
    $result = $get_result($method, $url, $options);
    $body_json = json_encode($result["body"]);

    $sql = "INSERT INTO osus_api (key, status, value) VALUES (?, ?, ?)
            ON CONFLICT(key) DO UPDATE SET status=excluded.status, value=excluded.value";
    $stmt = $this->cache_db->prepare($sql);
    $stmt->execute([$key, $result["status"], $body_json]);

    return $result;
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

      $custom_ratings = [
        "0.0" => $row["Custom00Rating"],
        "0.5" => $row["Custom05Rating"],
        "1.0" => $row["Custom10Rating"],
        "1.5" => $row["Custom15Rating"],
        "2.0" => $row["Custom20Rating"],
        "2.5" => $row["Custom25Rating"],
        "3.0" => $row["Custom30Rating"],
        "3.5" => $row["Custom35Rating"],
        "4.0" => $row["Custom40Rating"],
        "4.5" => $row["Custom45Rating"],
        "5.0" => $row["Custom50Rating"],
      ];

      array_push($omdb_users, [
        "user_id" => $row["UserID"],
        "access_token" => "",
        "refresh_token" => "",
        "custom_ratings" => json_encode($custom_ratings),
      ]);
    }

    DB::transaction(function () use ($osu_users, $omdb_users) {
      OsuUser::upsert($osu_users, ["user_id"], ["username"]);
      OmdbUser::upsert(
        $omdb_users,
        ["user_id"],
        ["access_token", "refresh_token", "custom_ratings"]
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

        $result = $this->api_request(
          "GET",
          "https://osu.ppy.sh/api/v2/users/{$creator}",
          []
        );

        if (
          $result["status"] !== 200 ||
          !array_key_exists("username", $result["body"])
        ) {
          $this->info("Could not get user " . $creator);
          $result["body"] = ["username" => ""];
        }

        OsuUser::updateOrCreate(
          ["user_id" => $creator],
          ["username" => $result["body"]["username"]]
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
      $bm = Beatmap::where("id", $row["BeatmapID"])->first();
      if ($bm !== null) {
        continue;
      }

      $bsid = $row["SetID"];
      $result = $this->api_request(
        "GET",
        "https://osu.ppy.sh/api/v2/beatmapsets/{$bsid}",
        []
      );

      if ($result["status"] !== 200) {
        $this->error(
          "Could not fetch beatmap " .
            $row["SetID"] .
            " " .
            $row["Artist"] .
            " - " .
            $row["Title"] .
            " (Status: " .
            $result["status"] .
            ")"
        );
        continue;
      }

      $creators[$row["SetCreatorID"]] = 1;
      $creators[$row["CreatorID"]] = 1;

      $beatmapsets[$row["SetID"]] = [
        "id" => $row["SetID"],
        "creator" => $result["body"]["creator"],
        "creator_id" => $row["SetCreatorID"],
        "date_ranked" => $row["DateRanked"],
        "artist" => $row["Artist"],
        "title" => $row["Title"],
        "genre" => $row["Genre"],
        "language" => $row["Lang"],
        "status" => $row["Status"],
      ];

      // Only take the beatmap creator id if it's different from the host (guest diff)
      $creator_id =
        $row["SetCreatorID"] == $row["CreatorID"] ? null : $row["CreatorID"];

      array_push($beatmaps, [
        "id" => $row["BeatmapID"],
        "beatmapset_id" => $row["SetID"],
        "creator_id" => $creator_id,
        "difficulty_name" => $row["DifficultyName"],
        "mode" => $row["Mode"],
        "star_rating" => $row["SR"],
        "blacklisted" => $row["Blacklisted"],
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
      $search_keys = ["beatmap_id", "user_id"];
      $value_keys = ["beatmapset_id", "score", "updated_at"];
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

  private function import_blacklisted_users()
  {
    $blacklisted_users = [];

    foreach ($this->db->query("SELECT * from blacklist") as $row) {
      array_push($blacklisted_users, ["user_id" => $row["UserID"]]);
    }

    // TODO: Insert model
    $this->info("Inserted blacklisted users.");
  }
}
