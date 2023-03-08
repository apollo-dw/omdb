<?php

namespace App\Http\Controllers;

use App\Models\OsuUser;
use App\Models\BeatmapSet;
use Illuminate\Support\Facades\DB;
use App\Models\OmdbUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ProfileController extends Controller
{
  public function show(Request $request): View
  {
    $user_id = $request->route("user_id");

    $osu_user = OsuUser::where("user_id", $user_id)
      ->with("omdb_user")
      ->first();
    $omdb_user = $osu_user?->omdb_user;

    $context = [
      "is_you" => false,
      "rating_counts" => null,
    ];

    // Only applicable to OMDB users
    if ($omdb_user !== null) {
      $context["osu_user"] = $osu_user;

      // Check if the user requested is the logged in user ("you")
      if (Auth::check()) {
        $context["is_you"] = Auth::user()->user_id == $osu_user->user_id;
      }

      $rating_counts_orig = $omdb_user
        ->ratings()
        ->groupBy("score")
        ->select("score", DB::raw("count(*) as count"))
        ->get();

      // Can't use keyBy + toArray because we need specific precision
      $rating_counts = [];
      foreach ($rating_counts_orig as $row) {
        $rs = number_format($row["score"], 1);
        $rating_counts[$rs] = $row["count"];
      }

      $context["rating_counts"] = $rating_counts;
      $context["total_ratings"] = array_sum(array_values($rating_counts));

      info("rating", [
        "rating_counts" => $rating_counts,
        "id" => $osu_user->user_id,
        "sum" => $context["total_ratings"],
      ]);

      if (count($rating_counts) == 0) {
        $context["max_rating"] = 1;
      } else {
        $context["max_rating"] = max(array_values($rating_counts));
      }

      $comment_count = $omdb_user->comments()->count();
      $context["comment_count"] = $comment_count;
    } else {
      // Fetch the user from OSU api
      // TODO: Figure out a way to get some kind of global API client so
      // we don't need to do OAuth flow each time

      // Get an API key
      $response = Http::asForm()->post("https://osu.ppy.sh/oauth/token", [
        "client_id" => config("auth.osu_client_id"),
        "client_secret" => config("auth.osu_client_secret"),
        "grant_type" => "client_credentials",
        "scope" => "public",
      ]);
      $result = $response->json();
      $access_token = $result["access_token"];

      $response = Http::withToken($access_token)
        ->withURLParameters(["user" => $user_id])
        ->get("https://osu.ppy.sh/api/v2/users/{user}", []);

      if (!$response->ok()) {
        return abort(404);
      }

      $data = $response->json();

      // Create a synthetic user so we can query just the data we need
      $osu_user = new \stdClass();
      $osu_user->omdb_user = null;
      $osu_user->user_id = $user_id;
      $osu_user->username = $data["username"];

      $context["osu_user"] = $osu_user;
    }

    $ranked_beatmaps = BeatmapSet::where("creator_id", $user_id)
      ->orderByDesc("date_ranked")
      ->get();
    $context["ranked_beatmaps"] = $ranked_beatmaps;

    return view("profile", $context);
  }

  public function comments(Request $request): View
  {
    $page_size = 25;
    $page = $request->query("page") ?? 1;
    $user_id = $request->route("user_id");

    $omdb_user = OmdbUser::where("user_id", $user_id)
      ->with("osu_user")
      ->first();

    if ($omdb_user == null) {
      return abort(404);
    }

    $comments = $omdb_user
      ->comments()
      ->orderByDesc("created_at")
      ->simplePaginate($page_size);
    $comment_count = $omdb_user->comments()->count();
    $num_pages = ceil($comment_count / $page_size);

    return view("profile.comments", [
      "page" => $page,
      "num_pages" => $num_pages,
      "comments" => $comments,
    ]);
  }

  public function ratings(Request $request): View
  {
    $page_size = 25;
    $page = $request->query("page") ?? 1;
    $user_id = $request->route("user_id");
    $score = $request->query("score");

    $omdb_user = OmdbUser::where("user_id", $user_id)
      ->with("osu_user")
      ->first();

    if ($omdb_user == null) {
      return abort(404);
    }

    $ratings_query = $omdb_user->ratings()->orderByDesc("updated_at");

    if ($score === null) {
      return abort(400);
    }

    $score = floatval($score);
    $ratings_query = $ratings_query->where("score", $score);

    $ratings = $ratings_query->simplePaginate($page_size);
    $rating_count = $omdb_user->ratings()->count();
    $num_pages = ceil($rating_count / $page_size);

    return view("profile.ratings", [
      "page" => $page,
      "num_pages" => $num_pages,
      "ratings" => $ratings,
      "omdb_user" => $omdb_user,
      "score" => $score,
    ]);
  }
}
