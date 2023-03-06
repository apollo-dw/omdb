<?php

namespace App\Http\Controllers;

use App\Models\Beatmap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartsController extends Controller
{
  public function show(Request $request)
  {
    $page_size = 50;
    $page = $request->query("page") ?? 1;
    $year = $request->query("year");
    $genre = $request->query("genre");

    $beatmaps_query = DB::table('beatmaps')
      ->join('beatmapsets', 'beatmaps.beatmapset_id', '=', 'beatmapsets.id')
      ->join('osu_users', 'beatmapsets.creator_id', '=', 'osu_users.user_id')
      ->whereNotNull("beatmaps.cached_rating");

    if ($genre !== null) {
      // TODO: Verify genre is valid
      $beatmaps_query = $beatmaps_query->where("beatmapsets.genre", "=", $genre);
    }

    if ($year !== null) {
      // TODO: Verify year is valid
      $beatmaps_query = $beatmaps_query->whereYear('beatmapsets.date_ranked', '=', $year);
    }

    $query_string = $beatmaps_query->toSql();

    $num_beatmaps = $beatmaps_query->count();
    $num_pages = ceil($num_beatmaps / $page_size);

    $beatmaps = $beatmaps_query->simplePaginate($page_size);

    $year = $request->query("year") ?? date("Y");

    return view("charts", [
      "year" => $year,
      "page" => $page,
      "query_string" => $query_string,
      "beatmaps" => $beatmaps,
      "num_pages" => $num_pages,
    ]);
  }
}
