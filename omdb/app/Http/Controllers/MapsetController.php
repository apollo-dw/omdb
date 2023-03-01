<?php

namespace App\Http\Controllers;

use App\Models\BeatmapSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapsetController extends Controller
{
    public function show(Request $request): View
    {
        $mapset_id = $request->route('mapset_id');

        $mapset = BeatmapSet::where('id', $mapset_id)->first();
        if ($mapset === null) return abort(404);

        $average_rating = DB::select("
            SELECT ROUND(AVG(Score), 2) as average
            FROM `ratings`
            WHERE id IN (SELECT id FROM beatmaps WHERE beatmapset_id={$mapset_id})
        ")[0]->average;

        return view('mapset', [
            'mapset' => $mapset,
            'average_rating' => $average_rating,
        ]);
    }
}
