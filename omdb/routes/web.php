<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get("/", "HomeController@show");
Route::resource("/settings", "SettingsController")->only(["index", "store"]);
Route::post("/settings/api_key", "SettingsController@api_key");
Route::get("/charts", "ChartsController@show");
Route::get("/maps", "MapsController@show");
Route::get("/maps/random", "MapsController@random");
Route::get("/mapset/{mapset_id}", "MapsetController@show");
Route::get("/profile/{user_id}", "ProfileController@show");
Route::get("/profile/{user_id}/comments", "ProfileController@comments");
Route::get("/profile/{user_id}/ratings", "ProfileController@ratings");
Route::any("/search", "SearchController@query");

Route::group(["middleware" => "auth"], function () {
  Route::get("/relogin/{user_id}", "AuthController@relogin");
  Route::post("/mapset/{mapset_id}/comment", "MapsetController@post_comment");
  Route::post("/mapset/{mapset_id}/rating", "MapsetController@post_rating");
});

Route::group(["prefix" => "auth"], function () {
  Route::get("login", "AuthController@login")->name("login");
  Route::get("callback", "AuthController@callback");
  Route::get("logout", "AuthController@logout");
});

Route::group(["prefix" => "api"], function () {
  Route::get("set/{mapset_id}", "ApiController@set");
  Route::get("beatmap/{beatmap_id}", "ApiController@beatmap");
  Route::get("user/{user_id}/ratings", "ApiController@user_ratings");
  Route::post("rate/{beatmap_id}", "ApiController@rate");
});
