<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {

        $user_id = $request->route('user_id');

        $user = User::where('id', $user_id)->first();

        if ($user === null) abort(404);

        $is_you = false;
        if (Auth::check())
            $is_you = Auth::user()->id == $user->id;

        // TODO: Actually fetch this
        $rating_counts = [
            '0.0' => 1,
            '1.0' => 1,
            '2.0' => 1,
            '3.0' => 1,
            '4.0' => 1,
            '5.0' => 1,
            '0.5' => 1,
            '1.5' => 1,
            '2.5' => 1,
            '3.5' => 1,
            '4.5' => 1,
        ];

        return view('profile', [
            'user' => $user,
            'is_you' => $is_you,
            'rating_counts' => $rating_counts,
            'max_rating' => max($rating_counts),
        ]);
    }
}
