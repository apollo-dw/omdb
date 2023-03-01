<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        $counts = DB::select("
            SELECT
                (SELECT COUNT(*) FROM users) as user_count,
                (SELECT COUNT(*) FROM comments) as comment_count,
                (SELECT COUNT(*) FROM ratings) as rating_count");

        $recent_ratings = DB::select("
            SELECT
                r.*,
                b.difficulty_name,
                b.beatmapset_id
            FROM `ratings` r
            INNER JOIN `beatmaps` b
            ON r.beatmap_id = b.beatmap_id
            ORDER BY r.updated_at DESC
            LIMIT 40
        ");

        $recent_comments = DB::select("SELECT * FROM `comments` ORDER BY `updated_at` DESC LIMIT 20");

        return view('home', [
            'counts' => $counts[0],
            'recent_ratings' => $recent_ratings,
            'recent_comments' => $recent_comments,
        ]);
    }
}
