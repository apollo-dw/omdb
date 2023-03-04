<?php

namespace App\Http\Controllers;

use App\Models\BeatmapSet;
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

    /* $latest_mapsets = DB::select("
                SELECT DISTINCT
                    id, artist, title, creator_id, updated_at, date_ranked
                FROM `beatmapsets`
                ORDER BY `date_ranked` DESC, `updated_at` DESC
                LIMIT 8
                "); */

    $last_7_days_ratings = DB::select("
      SELECT b.id, b.beatmapset_id, s.title, s.artist, b.difficulty_name, num_ratings
      FROM beatmaps b
      INNER JOIN beatmapsets s ON b.beatmapset_id = s.id
      INNER JOIN (
            SELECT beatmap_id, COUNT(*) as num_ratings
            FROM ratings
            WHERE created_at >= interval 1 week
            GROUP BY beatmap_id
      ) r ON b.id = r.beatmap_id
      INNER JOIN (
            SELECT beatmapset_id, MAX(num_ratings) as max_ratings
            FROM (
                SELECT b.beatmapset_id, b.id, COUNT(*) as num_ratings
                FROM beatmaps b
                INNER JOIN ratings r ON b.id = r.beatmap_id
                WHERE r.created_at >= interval 1 week
                GROUP BY b.beatmapset_id, b.id
            ) t
            GROUP BY beatmapset_id
      ) m ON b.beatmapset_id = m.beatmapset_id AND r.num_ratings = m.max_ratings
      ORDER BY num_ratings DESC, b.id DESC
      LIMIT 10;
    ");

    return view("home", [
      "counts" => $counts,
      "recent_ratings" => $recent_ratings,
      "recent_comments" => $recent_comments,
      "latest_mapsets" => $latest_mapsets,
      "last_7_days_ratings" => $last_7_days_ratings,
    ]);
  }
}
