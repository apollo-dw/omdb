<?php

namespace App\Http\Controllers;

use App\Models\OsuUser;
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

      $rating_counts = $omdb_user
        ->ratings()
        ->groupBy("score")
        ->select("score", DB::raw("count(*) as count"))
        ->get()
        ->keyBy("score")
        ->toArray();
      $context["rating_counts"] = $rating_counts;
      $context["total_ratings"] = array_sum(array_values($rating_counts));

      if (count($rating_counts) == 0)
        $context["max_rating"] = 1;
      else
        $context["max_rating"] = max(array_values($rating_counts))["count"];

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

    return view("profile", $context);
    /*[
      "osu_user" => $osu_user,
      "is_you" => $is_you,
      "rating_counts" => $rating_counts,
      "max_rating" => $max_rating,
      'comments' => $comments,
      ]);*/
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
}
