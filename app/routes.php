<?php


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

// Website
Route::get('/', 'MapController@showWelcome');
Route::get('/showtreedata/{id}', 'MapController@showTreeData');


// API
Route::resource('users/signup', 'UserController@signup');
Route::resource('users/login', 'UserController@login');
Route::controller('password', 'RemindersController');

Route::resource('trees/details/user', 'TreeController@getUserTreesDetailed');
Route::resource('trees/create', 'TreeController@create');
Route::resource('trees/update', 'TreeController@updateTree');
