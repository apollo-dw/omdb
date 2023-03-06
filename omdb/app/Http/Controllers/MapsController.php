<?php

namespace App\Http\Controllers;

use App\Models\BeatmapSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MapsController extends Controller
{
  public function show(Request $request)
  {
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
