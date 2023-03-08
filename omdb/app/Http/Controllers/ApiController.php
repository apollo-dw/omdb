<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatmapSet;
use App\Models\Beatmap;
use App\Models\OmdbUser;
use App\Models\Rating;
use App\Models\ApiKey;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
  public function __construct()
  {
    $this->middleware(function (Request $request, \Closure $next) {
      $api_key = $request->query("key");
      if (empty($api_key)) {
        return abort(401);
      }

      $api_key = ApiKey::where('api_key', $api_key)->first();
      if ($api_key === null) return abort(401);

      $request->attributes->add(['user_id' => $api_key->user_id]);

      return $next($request);
    });
  }

  public function set(Request $request)
  {
    $mapset_id = $request->route("mapset_id");

    $mapset = BeatmapSet::with("beatmaps")
      ->where("id", $mapset_id)
      ->first();
    if ($mapset === null) {
      return abort(404);
    }

    $beatmaps = [];
    foreach ($mapset->beatmaps as $beatmap) {
      array_push($beatmaps, [
        "BeatmapID" => $beatmap->id,
        "Artist" => $mapset->artist,
        "Title" => $mapset->title,
        "Difficulty" => $beatmap->difficulty_name,
        "ChartRank" => $beatmap->cached_chart_rank,
        "ChartYearRank" => $beatmap->cached_chart_year_rank,
        "RatingCount" => $beatmap->cached_rating_count,
        "WeightedAvg" => $beatmap->cached_weighted_avg,
      ]);
    }

    return response()->json($beatmaps);
  }

  public function beatmap(Request $request)
  {
    $beatmap_id = $request->route("beatmap_id");

    $beatmap = Beatmap::with("beatmapset")
      ->where("id", $beatmap_id)
      ->first();

    if ($beatmap === null) {
      return abort(404);
    }

    return response()->json([
      "SetID" => $beatmap->beatmapset_id,
      "Artist" => $beatmap->beatmapset->artist,
      "Title" => $beatmap->beatmapset->title,
      "Difficulty" => $beatmap->difficulty_name,
      "ChartRank" => $beatmap->cached_chart_rank,
      "ChartYearRank" => $beatmap->cached_chart_year_rank,
      "RatingCount" => $beatmap->cached_rating_count,
      "WeightedAvg" => $beatmap->cached_weighted_avg,
    ]);
  }

  public function user_ratings(Request $request)
  {
    $user_id = $request->route("user_id");

    $year = $request->query('year');
    $score = $request->query('score');

    $ratings_query = DB::table('ratings')
      ->join('beatmapsets', 'beatmapsets.id', '=', 'ratings.beatmapset_id')
      ->join('beatmaps', 'beatmaps.id', '=', 'ratings.beatmap_id')
      ->where('user_id', $user_id);

    if ($year !== null)
      $ratings_query = $ratings_query->whereYear('beatmapsets.date_ranked');

    if ($score !== null)
      $ratings_query = $ratings_query->where('score', '=', floatval($score));

    $ratings = $ratings_query->orderByDesc('ratings.updated_at')->get();

    $ratings_array = [];
    foreach ($ratings as $rating) {
      array_push($ratings_array, [
        'SetID' => $rating->beatmapset_id,
        'BeatmapID' => $rating->beatmap_id,
        'Score' => $rating->score,
        'Artist' => $rating->artist,
        'Title' => $rating->title,
        'Difficulty' => $rating->difficulty_name,
      ]);
    }

    return response()->json($ratings_array);
  }

  public function rate(Request $request)
  {
    $auth_user_id = $request->get('user_id');
    $auth_user = OmdbUser::where('user_id', $auth_user_id)->first();
    if ($auth_user === null) return abort(401);

    $beatmap_id = $request->route('beatmap_id');
    $beatmap = Beatmap::where('id', $beatmap_id)->first();
    if ($beatmap === null) return abort(400);

    $score = $request->query('score');
    if ($score === null) return abort(400);

    $score = floatval($score);

    Rating::updateOrCreate(
      ['beatmap_id' => $beatmap->id, 'user_id' => $auth_user->user_id],
      ['beatmapset_id' => $beatmap->beatmapset_id, 'score' => $score],
    );

    return response()->json('success');
  }
}
