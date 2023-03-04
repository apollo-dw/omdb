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

    return view("home", [
      "counts" => $counts,
      "recent_ratings" => $recent_ratings,
      "recent_comments" => $recent_comments,
      "latest_mapsets" => $latest_mapsets,
    ]);
  }
}
