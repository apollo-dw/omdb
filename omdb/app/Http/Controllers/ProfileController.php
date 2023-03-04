<?php

namespace App\Http\Controllers;

use App\Models\OsuUser;
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

    $is_you = false;
    $rating_counts = null;
    $max_rating = 1.0;

    // Only applicable to OMDB users
    if ($osu_user !== null) {
      // Check if the user requested is the logged in user ("you")
      if (Auth::check()) {
        $is_you = Auth::user()->user_id == $osu_user->user_id;
      }

      // TODO: Actually fetch this
      $rating_counts = [
        "0.0" => 1,
        "1.0" => 1,
        "2.0" => 1,
        "3.0" => 1,
        "4.0" => 1,
        "5.0" => 1,
        "0.5" => 1,
        "1.5" => 1,
        "2.5" => 1,
        "3.5" => 1,
        "4.5" => 1,
      ];
      $max_rating = max($rating_counts);
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
      $osu_user->id = $user_id;
      $osu_user->username = $data["username"];
    }

    return view("profile", [
      "osu_user" => $osu_user,
      "is_you" => $is_you,
      "rating_counts" => $rating_counts,
      "max_rating" => $max_rating,
    ]);
  }
}
