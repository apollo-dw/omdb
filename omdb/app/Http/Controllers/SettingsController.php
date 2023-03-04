<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
  public function __construct()
  {
    $this->middleware("auth");
  }

  public function index(Request $request): View
  {
    return view("settings", []);
  }

  public function store(Request $request)
  {
    $random_behavior = $request->input("random_behavior");
    $custom_ratings = $request->input("custom_ratings");

    $custom_ratings2 = [];
    $counter = 0;
    for ($i = 5.0; $i >= 0.0; $i -= 0.5) {
      $is = number_format($i, 1);
      $custom_ratings2[$is] = $custom_ratings[$counter];
      $counter += 1;
    }

    $user = Auth::user();

    $user->do_true_random = $random_behavior;
    $user->custom_ratings = $custom_ratings2;
    $user->save();
  }
}
