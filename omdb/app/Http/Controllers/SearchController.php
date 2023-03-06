<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SearchController extends Controller
{
  public function query(Request $request)
  {
    $query = $request->query("query") ?? $request->input("query");

    if (
      preg_match(
        "/https:\/\/osu\.ppy\.sh\/(beatmapsets|beatmapset|s)\/(\d+)/",
        $query,
        $matches
      )
    ) {
      info("Matched search regex");
      $setID = $matches[2];

      $mapset = BeatmapSet::where("id", $setID)->first();
      if ($mapset === null) {
        return response()->json([]);
      }

      return response()->json([
        [
          "beatmapset_id" => $setID,
          "artist" => $mapset->artist,
          "title" => $mapset->title,
        ],
      ]);
    }

    $like = "%{$query}%";

    $beatmapsets_subquery = DB::table("beatmapsets")->orWhereFullText(
      ["artist", "title"],
      $like
    );

    $beatmaps = DB::table("beatmaps")
      ->orWhereFullText("difficulty_name", $like)
      ->joinSub($beatmapsets_subquery, "beatmapsets", function ($join) {
        $join->on("beatmapsets.id", "=", "beatmaps.beatmapset_id");
      })
      ->take(25)
      ->get();

    // TODO: Probably a simpler way to convert this
    $results = [];
    foreach ($beatmaps as $beatmap) {
      array_push($results, [
        "beatmapset_id" => $beatmap->beatmapset_id,
        "beatmap_id" => $beatmap->id,
        "artist" => $beatmap->artist,
        "title" => $beatmap->title,
        "difficulty_name" => $beatmap->difficulty_name,
      ]);
    }

    info("Results " . json_encode($results));
    return response()->json($results);
  }
}
