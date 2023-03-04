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
Route::get("/charts", "ChartsController@show");
Route::get("/maps", "MapsController@show");
Route::get("/mapset/{mapset_id}", "MapsetController@show");
Route::get("/profile/{user_id}", "ProfileController@show");
Route::get("/random", "RandomController@show");
Route::post("/search", "SearchController@query");

Route::group(["middleware" => "auth"], function () {
  Route::post("/mapset/{mapset_id}/comment", "MapsetController@post_comment");
  Route::post("/mapset/{mapset_id}/rating", "MapsetController@post_rating");
});

Route::group(["prefix" => "auth"], function () {
  Route::get("login", "AuthController@login")->name("login");
  Route::get("callback", "AuthController@callback");
});
