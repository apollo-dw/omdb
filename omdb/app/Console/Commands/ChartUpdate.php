<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\OmdbUser;
use App\Models\Rating;
use App\Models\Beatmap;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class ChartUpdate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "omdb:chart_update";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Chart update";

  /**
   * Execute the console command.
   */
  public function handle(): void
  {
    $time_start = microtime(true);
    set_time_limit(300);

    // TODO: Query optimization?
    // DB::enableQueryLog();
    // dd(DB::getQueryLog());

    $this->calculate_user_weights();
    $this->calculate_bayesian_average_rating();
    $this->calculate_chart_information();

    echo "Total execution time in seconds: " . (microtime(true) - $time_start);
  }

  private function calculate_user_weights()
  {
    $time_start = microtime(true);
    $num_users = OmdbUser::count();
    $this->info("Calculating weights for {$num_users} users.");

    $users = OmdbUser::get();

    foreach ($users as $user) {
      $userID = $user->user_id;
      $entropy = 0;
      $countWeight = 0;

      $ratings = [];
      $result = Rating::groupBy("score")
        ->where("user_id", "=", $userID)
        ->select("score", DB::raw("count(*) as count"))
        ->groupBy("score")
        ->get();

      foreach ($result as $rating) {
        $ratings[] = $rating["count"];
      }

      $entropy = $this->calculateEntropy($ratings);

      $total = array_sum($ratings);
      $countWeight = min(1, $total / 50);

      $totalWeight = pow(min(1, $entropy / 2.5), 3) * $countWeight;

      OmdbUser::where("user_id", $userID)->update([
        "cached_weight" => $totalWeight,
      ]);
      $user->save();
    }

    $elapsed = microtime(true) - $time_start;
    $this->info("Updated user weights in {$elapsed} seconds.");
  }

  private function calculate_bayesian_average_rating()
  {
    $time_start = microtime(true);

    // Average over all beatmaps
    $m = DB::select(
      "SELECT
        SUM(r.score * u.cached_weight) / SUM(u.cached_weight) as average
      FROM ratings r
      INNER JOIN omdb_users u ON r.user_id = u.user_id
      GROUP BY r.beatmap_id HAVING COUNT(*) > 1
    "
    )[0]->average;
    $this->info("m = " . json_encode($m));

    $confidence = config("app.bayesian_confidence");

    $rating_subquery = DB::table("ratings")
      ->groupBy("beatmap_id")
      ->having("count", ">", 2)
      ->select("beatmap_id", DB::raw("count(*) as count"));
    $beatmap_query = DB::table("beatmaps")
      ->joinSub($rating_subquery, "ratings", function ($join) {
        $join->on("ratings.beatmap_id", "=", "beatmaps.id");
      })
      ->where("beatmaps.blacklisted", "=", false);

    $num_beatmaps = $beatmap_query->count();
    $this->info("Calculating bayesian average for {$num_beatmaps} beatmaps.");

    $resultBeatmaps = $beatmap_query->get();

    foreach ($resultBeatmaps as $beatmap) {
      $bID = $beatmap->id;

      // $result = DB::select("
      //   SELECT
      //     SUM(r.score * u.cached_weight) / SUM(u.cached_weight) AS cached_weighted_avg,
      //     SUM(u.cached_weight) AS weight_sum
      //   FROM ratings r
      //   INNER JOIN omdb_users u ON r.user_id = u.user_id
      //   WHERE r.beatmap_id = ? AND (SELECT COUNT(*) FROM ratings r WHERE r.beatmap_id = ?) >= 2");
      $result = DB::table("ratings")
        ->join("omdb_users", "omdb_users.user_id", "=", "ratings.user_id")
        ->select(
          DB::raw("SUM(ratings.score * omdb_users.cached_weight)
                / SUM(omdb_users.cached_weight) as cached_weighted_avg"),
          DB::raw("SUM(omdb_users.cached_weight) as weight_sum")
        )
        ->where("ratings.beatmap_id", "=", $bID)
        ->first();
      // $stmt = $conn->prepare($query);
      // $stmt->bind_param("ii", $bID, $bID);
      // $stmt->execute();
      // $result = $stmt->get_result();

      // $row = $result->fetch_assoc();

      if ($result !== null) {
        $avg = $result->cached_weighted_avg;
        $count = $result->weight_sum;

        $bayesian = ($count * $avg + $m * $confidence) / ($count + $confidence);

        Beatmap::where("id", $bID)->update(["cached_rating" => $bayesian]);
      }
    }

    $elapsed = microtime(true) - $time_start;
    $this->info("Updated beatmap bayesian averages in {$elapsed} seconds.");
  }

  private function calculate_chart_information()
  {
    $time_start = microtime(true);

    $rating_subquery = DB::table("ratings")
      ->join("omdb_users", "omdb_users.user_id", "=", "ratings.user_id")
      ->groupBy("beatmap_id")
      ->select(
        "ratings.beatmap_id",
        DB::raw(
          "SUM(ratings.score * omdb_users.cached_weight) / SUM(omdb_users.cached_weight) as weighted_avg"
        ),
        DB::raw("count(*) as rating_count")
      );
    $query = DB::table("beatmaps")
      ->join("beatmapsets", "beatmaps.beatmapset_id", "=", "beatmapsets.id")
      ->joinSub($rating_subquery, "ratings", function ($join) {
        $join->on("beatmaps.id", "=", "ratings.beatmap_id");
      })
      ->where("beatmaps.blacklisted", "=", false)
      ->whereNotNull("beatmaps.cached_rating")
      ->orderByDesc("beatmaps.cached_rating")
      ->select(
        "beatmaps.id",
        "beatmaps.cached_rating",
        "beatmapsets.date_ranked",
        "ratings.weighted_avg",
        "ratings.rating_count"
      );

    $num_beatmaps = $query->count();
    $this->info("Updating chart information for {$num_beatmaps} beatmaps.");

    $result = $query->get();

    $RankCounter = 1;
    $YearRankCounter = [
      "2007" => 1,
      "2008" => 1,
      "2009" => 1,
      "2010" => 1,
      "2011" => 1,
      "2012" => 1,
      "2013" => 1,
      "2014" => 1,
      "2015" => 1,
      "2016" => 1,
      "2017" => 1,
      "2018" => 1,
      "2019" => 1,
      "2020" => 1,
      "2021" => 1,
      "2022" => 1,
      "2023" => 1,
      "2024" => 1,
    ];

    // while ($row = $result->fetch_assoc()) {
    foreach ($result as $row) {
      // TODO: Shouldn't this be retrieved from the database as Carbon?
      $beatmapYear = Carbon::parse($row->date_ranked)->year;
      $beatmapId = $row->id;

      $avg = $row->weighted_avg;
      $count = $row->rating_count;

      Beatmap::where("id", $beatmapId)->update([
        "cached_chart_rank" => $RankCounter,
        "cached_chart_year_rank" => $YearRankCounter[$beatmapYear],
        "cached_weighted_avg" => $avg,
        "cached_rating_count" => $count,
      ]);

      $RankCounter += 1;
      $YearRankCounter[$beatmapYear] += 1;
    }

    $elapsed = microtime(true) - $time_start;
    $this->info("Updated chart information in {$elapsed} seconds.");
  }

  private function calculateEntropy($arr)
  {
    $total = array_sum($arr);
    if ($total == 0) {
      return 0;
    }

    $entropy = 0;
    $probabilities = [];

    foreach ($arr as $rating) {
      $probabilities[] = $rating / $total;
    }

    foreach ($probabilities as $probability) {
      if ($probability > 0) {
        $entropy -= $probability * log($probability, 2);
      }
    }

    return $entropy;
  }
}
