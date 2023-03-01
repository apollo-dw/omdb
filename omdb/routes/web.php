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

Route::get('/', 'HomeController@show');
Route::get('/settings', 'HomeController@show');
Route::get('/charts', 'HomeController@show');
Route::get('/maps', 'HomeController@show');
Route::get('/random', 'HomeController@show');
Route::get('/mapset/{mapset_id}', 'HomeController@show');
Route::get('/profile/{user_id}', 'ProfileController@show');
Route::resource('/rating', 'RatingController');

Route::group(['prefix' => 'auth'], function () {
    Route::get('login', 'AuthController@login');
    Route::get('callback', 'AuthController@callback');
});
