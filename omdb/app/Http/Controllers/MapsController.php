<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\BeatmapSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MapsController extends Controller
{
  public function show(Request $request)
  {
    $page = $request->query("page") ?? 1;
    $page_size = 20;

    $year = $request->query("year") ?? now()->year;
    $month = $request->query("month") ?? now()->month;

    $ratings_subquery = DB::table("ratings")
      ->groupBy("beatmapset_id")
      ->select(
        "beatmapset_id",
        DB::raw("round(avg(ratings.score), 2) as rating_avg"),
        DB::raw("count(*) as rating_count")
      );

    $beatmapsets_query = BeatmapSet::whereYear("date_ranked", $year)
      ->orderByDesc("date_ranked")
      ->whereMonth("date_ranked", $month)
      ->leftJoinSub($ratings_subquery, "ratings", function ($join) {
        $join->on("ratings.beatmapset_id", "=", "beatmapsets.id");
      });

    $num_beatmapsets = $beatmapsets_query->count();
    $beatmapsets = $beatmapsets_query->paginate($page_size);

    $num_pages = ceil($num_beatmapsets / $page_size);

    return view("maps", [
      "year" => $year,
      "month" => $month,
      "page" => $page,
      "num_pages" => $num_pages,
      "beatmapsets" => $beatmapsets,
    ]);
  }

  public function random(Request $request)
  {
    if (!Auth::check()) {
      goto true_random;
    }

    $omdb_user = Auth::user();

    if ($omdb_user->do_true_random) {
      goto true_random;
    }

    $mapset = $omdb_user->random_played_beatmap();
    // TODO: Check for supporter

    if ($mapset === null) {
      goto true_random;
    }

    $mapset_id = $mapset->id;
    return redirect("/mapset/{$mapset_id}");

    // ------------------------------------------------------------
    // I LOVE GOTO
    true_random:

    $mapset = BeatmapSet::inRandomOrder()->first();
    if ($mapset === null) {
      return abort(404);
    }

    $mapset_id = $mapset->id;
    return redirect("/mapset/{$mapset_id}");
  }
}
