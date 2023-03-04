<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BeatmapSet;
use App\Models\Beatmap;
use App\Models\Comment;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
  public function show(): View
  {
    $counts = DB::select("
            SELECT
                (SELECT COUNT(*) FROM omdb_users) as user_count,
                (SELECT COUNT(*) FROM comments) as comment_count,
                (SELECT COUNT(*) FROM ratings) as rating_count")[0];

    $recent_ratings = Rating::latest()
      ->with("osu_user")
      ->take(40)
      ->get();

    $recent_comments = Comment::latest()
      ->with("osu_user")
      ->take(20)
      ->get();

    $latest_mapsets = BeatmapSet::latest("date_ranked")
      ->with("creator_user")
      ->take(8)
      ->get();

    $ratings_subquery = DB::table("ratings")
      ->whereDate("ratings.created_at", ">=", Carbon::now()->subWeek())
      ->groupBy("ratings.beatmap_id")
      ->select("beatmap_id", DB::raw("count(*) as num_ratings"));

    $last_7_days_ratings = DB::table("beatmaps")
      ->joinSub($ratings_subquery, "ratings", function ($join) {
        $join->on("ratings.beatmap_id", "=", "beatmaps.id");
      })
      ->join("beatmapsets", "beatmaps.beatmapset_id", "=", "beatmapsets.id")
      ->select(
        "beatmaps.id",
        "beatmaps.beatmapset_id",
        "beatmapsets.artist",
        "beatmapsets.title",
        "beatmaps.difficulty_name",
        "ratings.num_ratings"
      )
      ->orderByDesc("num_ratings")
      ->get();

    /*DB::table("beatmaps")
      ->joinSub($ratings_subquery, "r", function ($join) {
        $join->on("beatmaps.id", "=", "r.beatmap_id");
      })
      ->joinSub($max_ratings_subquery, "m", function ($join) {
        $join->on("beatmaps.beatmapset_id", "=", "m.beatmapset_id");
        $join->on("r.num_ratings", "=", "m.max_ratings");
      })
      ->orderByDesc("num_ratings")
      ->orderByDesc("id")
      ->take(10)
      ->get();*/

    /* $last_7_days_ratings = DB::select("
      SELECT b.id, b.beatmapset_id, s.title, s.artist, b.difficulty_name, num_ratings
      FROM beatmaps b
      INNER JOIN beatmapsets s ON b.beatmapset_id = s.id
      INNER JOIN (
            SELECT beatmap_id, COUNT(*) as num_ratings
            FROM ratings
            WHERE created_at >= now() - interval 1 week
            GROUP BY beatmap_id
      ) r ON b.id = r.beatmap_id
      INNER JOIN (
            SELECT beatmapset_id, MAX(num_ratings) as max_ratings
            FROM (
                SELECT b.beatmapset_id, b.id, COUNT(*) as num_ratings
                FROM beatmaps b
                INNER JOIN ratings r ON b.id = r.beatmap_id
                WHERE r.created_at >= now() - interval 1 week
                GROUP BY b.beatmapset_id, b.id
            ) t
            GROUP BY beatmapset_id
      ) m ON b.beatmapset_id = m.beatmapset_id AND r.num_ratings = m.max_ratings
      ORDER BY num_ratings DESC, b.id DESC
      LIMIT 10;
    "); */

    return view("home", [
      "counts" => $counts,
      "recent_ratings" => $recent_ratings,
      "recent_comments" => $recent_comments,
      "latest_mapsets" => $latest_mapsets,
      "last_7_days_ratings" => $last_7_days_ratings,
    ]);
  }
}
