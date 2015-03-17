<?php

class TreeUpdatesController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$output = null;
		

		$pendingUpdates =  
		
		$output = $tree = Tree::find(1);
		
		$tree->primaryLocation;
		$tree->settings;
		$tree->overrideSettings;
		$tree->notes;
		$tree->photos;
		$tree->users;

		
		foreach ($tree->photos as $photo) {
			$photo->location;
		}
		
		return Response::json($output);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{

	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//TODO we do not wan't to store, but to return trees on given input
		
		$input = Input::json()->all();
		$output = array();
		
		$treeIds = array();
		
		if (isset($input['lat']) && isset($input['long']) && isset($input['radius'])) {
			$lat = $latitude = $input['lat'];
			$lon = $longitude = $input['long'];
			$distance = $input['radius'];
			
			//result is in meters
			
			$query = "select id from trees where primary_location_id in 
					(SELECT id FROM (SELECT *,
					((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() /
					 180) + COS($lat * PI() / 180) * COS(lat * PI() / 180)
					 * COS(($lon - lon) * PI() / 180)) * 180 / PI()) * 60 * 1.1515 * 1.609344 * 1000) 
					AS distance FROM locations HAVING distance<=$distance) AS id)";

			foreach (DB::select(DB::raw($query)) as $treeResObj) {
				$tree = Tree::find($treeResObj->id);
				
				$tree->primaryLocation;
				$tree->settings;
				$tree->overrideSettings;
				$tree->notes;
				$tree->photos;
				$tree->users;
				
				
				foreach ($tree->photos as $photo) {
					$photo->location;
				}
				
				$output[] = $tree->toArray();
				
			} 
		
		} else {
			$error['error'] = "Invalid coordinates or radius given.";
			return Response::json($error, 400);
		}
		
		return Response::json($output);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$tree = Tree::find($id);
				
		if ($tree != null) {
			$tree->primaryLocation;
			$tree->settings;
			$tree->overrideSettings;
			$tree->notes;
			$tree->photos;
			$tree->users;
			
			
			foreach ($tree->photos as $photo) {
				$photo->location;
			}
			
			$output = $tree->toArray();
				
		} else {
			$error['error'] = "Invalid tree ID.";
			return Response::json($error, 400);
		}
		
		
		return Response::json($output);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$error = array();
		$output = array();
		
		//TODO update if $id != 0 and $input['id'] != 0, create otherwise
		
		$input = Input::json()->all();
		
		if ($id == 0 && $input['id'] == 0) {
			//create
			var_dump('create');
			
			//location
			$location = new Location;
			$location->lat = $input['photo']['location']['lat'];
			$location->lon = $input['photo']['location']['long'];
			$location->gps_accuracy = $input['photo']['location']['gps_accuracy'];
			$location->user_id = $input['user_id'];
			$location->save();
			
			//photo
			$photo = new Photo;
			$photo->location_id = $location->id;
			$photo->user_id = $input['user_id'];
				//08:00:00 18.11.2013.  to  2013-11-18 08:00:00
			$date = new DateTime($input['photo']['time_taken']);
			$photo->time_taken = $date->format('Y-m-d H:i:s');
			$photo->base64_image = $input['photo']['base64_image'];
			$photo->outdated = $input['photo']['is_outdated'];
			$photo->save();
			
			//primary location
			$primaryLocation = new Location;
			$primaryLocation->lat = $input['primary_location']['lat'];
			$primaryLocation->lon = $input['primary_location']['long'];
			$primaryLocation->gps_accuracy = $input['primary_location']['gps_accuracy'];
			$primaryLocation->user_id = $input['user_id'];
			$primaryLocation->save();
			
			$noteIds = array();//save note id's in order to populate note_trees table later when we acquire tree id
			foreach ($input['notes'] as $currNote) {
				$note = new Note;
				$note->content = $currNote['content'];
				$date = new DateTime($currNote['time_created']);
				$note->time_created = $date->format('Y-m-d H:i:s');
				$note->user_id = $input['user_id'];
				$note->save();
				
				array_push($noteIds, $note->id);
			}
			
			//settings
			$settings = new Setting;
			$settings->next_update = $input['settings']['time_to_update'];
			$settings->min_gps_accuracy = $input['settings']['min_gps_accuracy'];
			$settings->save();
			
			//override settings
			$overrideSettings = new Setting;
			$overrideSettings->next_update = !isset($input['settings_override']['time_to_update']) ? null : $input['settings_override']['time_to_update'];
			$overrideSettings->min_gps_accuracy = !isset($input['settings_override']['min_gps_accuracy']) ? null : $input['settings_override']['min_gps_accuracy'];
			$overrideSettings->save();

			
// 			"time_created": "08:00:00 18.11.2013.",
// 			"is_missing": false

			$tree = new Tree;
			
			$date = new DateTime($input['time_created']);
			$tree->time_created = $date->format('Y-m-d H:i:s');
			$tree->missing = $input['is_missing'];
			
			if (!isset($input['time_updated']))
				$input['time_updated'] = $input['time_created'];
			
			$date = new DateTime($input['time_updated']);
			$tree->time_updated = $date->format('Y-m-d H:i:s');
			$tree->death_cause = !isset($input['death_cause']) ? null : $input['death_cause'];
			$tree->user_id = $input['user_id'];
			$tree->primary_location_id = $primaryLocation->id;
			$tree->settings_id = $settings->id;
			$tree->override_settings_id = $overrideSettings->id;
			$tree->save();
			
			foreach ($noteIds as $noteId) {
				$noteTree = new NoteTree;
				$noteTree->note_id = $noteId;
				$noteTree->tree_id = $tree->id;
				$noteTree->save();
			}
			
			$photoTree = new PhotoTree;
			$photoTree->photo_id = $photo->id;
			$photoTree->tree_id = $tree->id;
			$photoTree->save();
			
			$output['id'] = $tree->id;
			$output['photo_id'] = $photo->id;
			$output['primary_location_id'] = $primaryLocation->id;
			$output['settings_id'] = $settings->id;
			$output['settings_override_id'] = $overrideSettings->id;
			$output['notes'] = array();
			
			foreach ($noteIds as $noteId) {
				$output['notes']['id'][] = $noteId;
			}
			
// 			echo "<img alt=\"ic_launcher.png\"  src=\"data:image/png;base64,". $input['photo']['base64_image'] ." \" />";die;
			
			
		} else {
			//update
			var_dump('update');	
			
			$user = User::find(isset($input['user_id']) ? $input['user_id'] : -1 );
			$tree = Tree::find(isset($input['id']) ? $input['id'] : -1 );
			$primaryLocation = Location::find(isset($input['primary_location']['id']) ? $input['primary_location']['id'] : -1);
			$settings = Setting::find(isset($input['settings']['id']) ? $input['settings']['id'] : -1 );
			$overrideSettings = Setting::find(isset($input['settings_override']['id']) ? $input['settings_override']['id'] : -1);
			
			
			if ($user == null) {
				$error['error'] = "Invalid user ID."; 
				return Response::json($error, 400);
			}
			
			if ($tree == null) {
				$error['error'] = "Invalid tree ID.";
				return Response::json($error, 400);
			} else {
				$output['id'] = $tree->id;
			}
			
		
		}

		
		return json_encode($output);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		
		$pendingUpdates = PendingUpdates::find($id);
		$pendingUpdates->delete();
		
	}
		
	public function clearPendingUpdates() {
		$input = Input::json()->all();
		
		var_dump($input);die;
		
	}
	
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function userPendingUpdates($id) {
		
		$output = array();
		$pendingUpdates = DB::table('pending_update')->where('user_id', '=', $id)->get();
		
		if ($pendingUpdates != null){
				
			foreach ($pendingUpdates as $pendingUpdate){
	
				$updates = array();
	
				//"update_type": "global_settings"
				if ($pendingUpdate->settings_id != null) {
					$update['update_type'] = 'global_settings';
					$update['id'] = $pendingUpdate->settings_id;
					$update['main_db_id'] = $pendingUpdate->id;
					$updates[] = $update;
					unset($update);
				}
	
				//"update_type": "tree"
				if ($pendingUpdate->tree_id != null) {
					$update['update_type'] = 'tree';
					$update['id'] = $pendingUpdate->tree_id;
					$update['main_db_id'] = $pendingUpdate->id;
					$updates[] = $update;
					unset($update);
				}
				
				$output[] = $updates;
			}
	
	
			return Response::json($output);
	
		} else {
			$error['error'] = "No pending updates.";
			return Response::json($error, 400);
		}
	}

}