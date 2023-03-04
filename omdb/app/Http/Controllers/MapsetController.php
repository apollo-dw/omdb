<?php

namespace App\Http\Controllers;

use App\Models\Beatmap;
use App\Models\BeatmapSet;
use App\Models\Comment;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MapsetController extends Controller
{
  public function show(Request $request): View
  {
    $mapset_id = $request->route("mapset_id");

    $mapset = BeatmapSet::where("id", $mapset_id)
      ->with("creator_user")
      ->first();
    if ($mapset === null) {
      return abort(404);
    }

    $beatmaps = Beatmap::where("beatmapset_id", $mapset_id)
      ->with("ratings")
      ->get();

    $average_rating = DB::select("
            SELECT ROUND(AVG(Score), 2) as average
            FROM `ratings`
            WHERE id IN (SELECT id FROM beatmaps WHERE beatmapset_id={$mapset_id})
        ")[0]->average;

    $comments = Comment::where("beatmapset_id", $mapset_id)
      ->with("osu_user")
      ->orderByDesc("created_at")
      ->get();

    return view("mapset", [
      "mapset" => $mapset,
      "beatmaps" => $beatmaps,
      "average_rating" => $average_rating,
      "comments" => $comments,
    ]);
  }

  public function post_comment(Request $request)
  {
    $mapset_id = $request->route("mapset_id");
    $comment_content = $request->input("comment");

    $omdb_user = Auth::user();

    // TODO: Make sure the beatmapset exists

    Comment::create([
      "user_id" => $omdb_user->user_id,
      "beatmapset_id" => $mapset_id,
      "comment" => $comment_content,
    ]);

    return response()->json(["success" => "success"], 200);
  }

  public function post_rating(Request $request)
  {
    $mapset_id = $request->route("mapset_id");
    $map_id = $request->input("beatmap_id");
    $rating = $request->input("rating");

    $omdb_user = Auth::user();

    // TODO: Make sure the beatmapset exists
    // TODO: Make sure the beatmap exists

    Rating::updateOrCreate(
      [
        "user_id" => $omdb_user->user_id,
        "beatmapset_id" => $mapset_id,
        "beatmap_id" => $map_id,
      ],
      [
        "score" => $rating,
      ]
    );

    return response()->json(["success" => "success"], 200);
  }
}
