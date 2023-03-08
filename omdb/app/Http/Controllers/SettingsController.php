<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\ApiKey;

class SettingsController extends Controller
{
  public function __construct()
  {
    $this->middleware("auth");
  }

  public function index(Request $request): View
  {
    $user = Auth::user();

    $api_keys = ApiKey::where("user_id", $user->user_id)->get();

    return view("settings", ["api_keys" => $api_keys]);
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

  public function api_key(Request $request)
  {
    $user = Auth::user();

    $characters =
      "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randomString = "";
    for ($i = 0; $i < 32; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    $apiKey = hash("sha256", $user->user_id . $randomString . "omdb!");

    $name = $request->input("apiname");
    info("Name ", ["name" => $name]);

    // TODO: Fix toctou
    // https://stackoverflow.com/questions/33852382/constraint-on-table-to-limit-number-of-records-to-be-stored
    if (ApiKey::where("user_id", $user->user_id)->count() < 5) {
      $api_key = new ApiKey();
      $api_key->api_key = $apiKey;
      $api_key->user_id = $user->user_id;
      $api_key->name = $name;
      $api_key->save();
    }

    $request->session()->flash("status", "Created API key: " . $apiKey);
    return redirect("/settings");
  }
}
