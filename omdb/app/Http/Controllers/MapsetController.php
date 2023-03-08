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
      ->withCount("ratings")
      ->first();
    if ($mapset === null) {
      return abort(404);
    }

    $beatmaps_query = Beatmap::where("beatmaps.beatmapset_id", $mapset_id)
      ->with("ratings")
      ->orderByDesc("star_rating");

    if (Auth::check()) {
      $user = Auth::user();

      $rating_subquery = DB::table("ratings")
        ->where("user_id", $user->user_id)
        ->where("ratings.beatmapset_id", $mapset->id)
      ->select('beatmap_id', DB::raw('ratings.score as user_score'));
      $beatmaps_query = $beatmaps_query->leftJoinSub(
        $rating_subquery,
        "user_ratings",
        function ($join) {
          $join->on("user_ratings.beatmap_id", "=", "beatmaps.id");
        }
      );
    }

    $beatmaps = $beatmaps_query->get();

    $average_rating = DB::table("ratings")
      ->where("beatmapset_id", $mapset_id)
      ->selectRaw("round(avg(score), 2) as average")
      ->first()->average;

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
