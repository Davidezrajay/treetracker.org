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
Route::get('/', 'HomeController@showWelcome');
Route::get('/showtreedata/{id}', 'HomeController@showTreeData');

Route::resource('users/signup', 'UserController@signup');
Route::resource('users/login', 'UserController@login');
Route::controller('password', 'RemindersController');


Route::group(array('before' => 'token'), function()
{
	
	Route::resource('users', 'UserController');
	
	Route::get('trees/show', 'TreeController@showTrees');
	
	// Route::get('trees/show', array('before' => 'token', 'uses' => 'TreeController@showTrees'));
	
	
	Route::resource('trees', 'TreeController');
	Route::resource('settings', 'SettingController');
	Route::resource('trees/updates', 'TreeUpdatesController');
	Route::resource('trees/updates/user', 'TreeUpdatesController@userPendingUpdates');
	Route::resource('trees/user', 'TreeController@getUserTrees');
// 	Route::delete('trees/updates/clear', 'TreeUpdatesController@clearPendingUpdates');
});


// Route::resource('users', 'UserController');
// Route::resource('users/signup', 'UserController@signup');
// Route::resource('users/login', 'UserController@login');


// Route::resource('users/reset', 'RemindersController@postRemind');

// Route::controller('password', 'RemindersController');

// Route::get('trees/show', 'TreeController@showTrees');

// // Route::get('trees/show', array('before' => 'token', 'uses' => 'TreeController@showTrees'));


// Route::resource('trees', 'TreeController');
// Route::resource('trees/updates', 'TreeUpdatesController');
// Route::resource('trees/updates/user', 'TreeUpdatesController@userPendingUpdates');
// Route::delete('trees/updates/clear', 'TreeUpdatesController@clearPendingUpdates');
