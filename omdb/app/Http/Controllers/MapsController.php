<?php

namespace App\Http\Controllers;

use App\Models\BeatmapSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MapsController extends Controller
{
  public function show(Request $request) {
  }

  public function random(Request $request) {
    if (Auth::check()) {
      $omdb_user = Auth::user();
      // TODO: Check for supporter
    }

    $mapset = BeatmapSet::inRandomOrder()->first();

    if ($mapset === null) return abort(404);

    $mapset_id = $mapset->id;
    return redirect("/mapset/{$mapset_id}");
  }
}
