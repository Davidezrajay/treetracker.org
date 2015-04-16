<?php

class HomeController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	/**
	 * @return Shows welcome screen containing Google Map popoulated with tree markers
     */
	public function showWelcome()
	{
		//Return all the tree objects to view
		return View::make('mainmap')->with('trees', Tree::all());
	}

	public function showTreeData($id){
		//Returns tree data to the view. This is used in Google Maps infowindow
		return View::make('treedata')->with('tree', Tree::find($id));
	}

}