<?php


class TreeController extends \BaseController {
	
	private function base64_to_jpeg($base64_string, $output_file) {
		$ifp = fopen($output_file, "wb");
			
		$data = explode(',', $base64_string);
			
		fwrite($ifp, base64_decode($data[1]));
		fclose($ifp);
			
		return $output_file;
	}
	
	

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$output = null;
		
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
// 			$tree->overrideSettings;
			$tree->notes;
			$tree->users;
			
			$output = $tree->toArray();
			
			$tempTree = Tree::find($id);
			$tempTree->photos;
			foreach ($tempTree->photos as $photo) {
				if ($photo->outdated == '0') {
					try {
						$output['photo'] = $photo->location->toArray();
						$output['photo']['base64_image'] = base64_encode(file_get_contents('images/'.$photo->id.'_thumb.jpg'));
					} catch (Exception $e) {
						unset($output['photo']);
					}
					
				}
			}
			
			
				
		} else {
			$error['error'] = "Invalid tree ID.";
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
	public function getUserTrees($id) {
		$trees = User::find($id)->trees;
		
		$output = array();
		
		foreach($trees as $tree) {
			if (!$tree->missing) {
				$output[] = $tree->id;
			}
		}
		
		return json_encode($output);
	}
	
	public function showTrees() {
		$trees = Tree::all();
		
		foreach ($trees as $tree) {
			$photos = $tree->photos;
			$notes = $tree->notes;
			$location = $tree->primaryLocation;
			
			$mapsUrl = '<a href="http://maps.google.com/maps?q='.$location->lat.','.$location->lon.'">Location link</a>';
			
			
			$url = "";
			foreach($photos as $photo) {
				if (!$photo->outdated) {
					$url = URL::to('images/'.$photo->id.'_thumb.jpg');
					
				}
			}

			$treeNotes = array();
			foreach($notes as $note) {
				array_push($treeNotes, array('content' => $note->content, 'time_created' => $note->time_created));
			}
			
			
			echo '<div style="border:1px solid green;">';
			echo '<img style="border:5px solid white;" src="'.$url.'"/>';

			if (!empty($treeNotes)) {
				foreach($treeNotes as $treeNote) {
					echo '<div >'.$treeNote['content'].'</div>';
					echo '<div >'.$treeNote['time_created'].'</div>';
				}

				
			}
			echo $mapsUrl;
			echo '</div>';
				

		}
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
// 			var_dump('create');

//			DB::beginTransaction();

			if (!empty($input['photo'])) {
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
				
				$photo->outdated = $input['photo']['is_outdated'] == 'N' ? false : true ;
				$photo->save();
					
				file_put_contents('images/'.$photo->id.'.jpg' , base64_decode($input['photo']['base64_image']));
					
				$thumb = new Imagick();
				$thumb->readImage('images/'.$photo->id.'.jpg');

				$thumb->resizeImage(320,240,Imagick::FILTER_LANCZOS,1, true);
				$thumb->writeImage('images/'.$photo->id.'_thumb.jpg');
				$thumb->clear();
				$thumb->destroy();
			}

			
			
			//primary location
			$primaryLocation = new Location;
			$primaryLocation->lat = $input['primary_location']['lat'];
			$primaryLocation->lon = $input['primary_location']['long'];
			$primaryLocation->gps_accuracy = $input['primary_location']['gps_accuracy'];
			$primaryLocation->user_id = $input['user_id'];
			$primaryLocation->save();
			
			$noteIds = array();//save note id's in order to populate note_trees table later when we acquire tree id
			foreach ($input['notes'] as $currNote) {
				if (!empty($currNote)) 
				{
					$note = new Note;
					$note->content = $currNote['content'];
					$date = new DateTime($currNote['time_created']);
					$note->time_created = $date->format('Y-m-d H:i:s');
					$note->user_id = $input['user_id'];
					
					try {
						$note->save();
					} catch (Exception $e) {
						$note = Note::where('time_created', '=', $date->format('Y-m-d H:i:s'))->first();
					}
					
					array_push($noteIds, $note->id);
					
				} else {
					continue;
				}
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
			$tree->missing = $input['is_missing'] == 'N' ? false : true ;
			
			if (!isset($input['time_updated']))
				$input['time_updated'] = $input['time_created'];
			
			$date = new DateTime($input['time_updated']);
			$tree->time_updated = $date->format('Y-m-d H:i:s');
			$tree->cause_of_death_id = !isset($input['cause_of_death_id']) ? null : $input['cause_of_death_id'];
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
			
			if (!empty($input['photo'])) {
				$photoTree = new PhotoTree;
				$photoTree->photo_id = $photo->id;
				$photoTree->tree_id = $tree->id;
				$photoTree->save();
			}
			
			$output['id'] = $tree->id;
			$output['local_id'] = $input['local_id'];
			if (!empty($input['photo'])) {
				$output['photo_id'] = $photo->id;
			}

			$output['primary_location_id'] = $primaryLocation->id;
			$output['settings_id'] = $settings->id;
			$output['settings_override_id'] = $overrideSettings->id;
			$output['notes'] = array();
			
			foreach ($noteIds as $noteId) {
				$output['notes']['id'][] = $noteId;
			}
			
//			DB::commit();
			
// 			echo "<img alt=\"ic_launcher.jpg\"  src=\"data:image/jpg;base64,". $input['photo']['base64_image'] ." \" />";die;
			
			
		} else {
			//update
// 			var_dump('update');	

			DB::beginTransaction();
			
			if (!empty($input['photo'])) {
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
					
				$photo->outdated = $input['photo']['is_outdated'] == 'N' ? false : true ;
				$photo->save();
					
				
				if (!file_put_contents('images/'.$photo->id.'.jpg' , base64_decode($input['photo']['base64_image']))) {
					var_dump("NOT SAVED");die;
				}
					
				
				$thumb = new Imagick();
				$thumb->readImage('images/'.$photo->id.'.jpg');
				$thumb->resizeImage(320,240,Imagick::FILTER_LANCZOS,1, true);
				$thumb->writeImage('images/'.$photo->id.'_thumb.jpg');
				$thumb->clear();
				$thumb->destroy();
				
				// convert to jpg
// 				$im = new Imagick();
// 				$im->readImage('images/'.$photo->id.'.jpg');
// 				$im->setImageColorspace(255);
// 				$im->setCompression(Imagick::COMPRESSION_JPEG);
// 				$im->setCompressionQuality(100);
// 				$im->setImageFormat('jpeg');
				
// 				//write image on server
// 				$im->resizeImage(320,240,Imagick::FILTER_LANCZOS,1, true);
// 				$im->writeImage('images/'.$photo->id.'_thumb.jpg');
// 				$im->clear();
// 				$im->destroy();
			}

			
			$user = User::find(isset($input['user_id']) ? $input['user_id'] : -1 );
			$tree = Tree::find(isset($input['id']) ? $input['id'] : -1 );
			$primaryLocation = Location::find(isset($input['primary_location']['id']) ? $input['primary_location']['id'] : -1);
			$settings = Setting::find(isset($input['settings']['id']) ? $input['settings']['id'] : -1 );
			$overrideSettings = Setting::find(isset($input['settings_override']['id']) ? $input['settings_override']['id'] : -1);
			
			if ($tree == null) {
				$error['error'] = "Invalid tree ID.";
				return Response::json($error, 400);
			}
			
			$photos = $tree->photos;				
			foreach($photos as $currPhoto) {
				$currPhoto->outdated = true;
				$currPhoto->save();
			
			}
			
			if (!empty($input['photo'])) {
				$photoTree = new PhotoTree;
				$photoTree->photo_id = $photo->id;
				$photoTree->tree_id = $tree->id;
				$photoTree->save();
			}
			
			
			$noteIds = array();//save note id's in order to populate note_trees table later when we acquire tree id
			foreach ($input['notes'] as $currNote) {
				if (!empty($currNote))
				{
					$note = new Note;
					$note->content = $currNote['content'];
					$date = new DateTime($currNote['time_created']);
					$note->time_created = $date->format('Y-m-d H:i:s');
					$note->user_id = $input['user_id'];
					
					try {
						$note->save();
					} catch (Exception $e) {
						continue;
					}
					
						
					array_push($noteIds, $note->id);
						
				} else {
					continue;
				}
			}
			
			foreach($noteIds as $noteId) {
				$noteTree = new NoteTree;
				$noteTree->note_id = $noteId;
				$noteTree->tree_id = $tree->id;
				$noteTree->save();
			}
			
			$tree->missing = $input['is_missing'] == 'N' ? false : true ;
				
			if (isset($input['time_updated'])) {
				$date = new DateTime($input['time_updated']);
				$tree->time_updated = $date->format('Y-m-d H:i:s');
			}
			
			$tree->cause_of_death_id = !isset($input['cause_of_death_id']) ? null : $input['cause_of_death_id'];
			$tree->user_id = $input['user_id'];
			
			if ($primaryLocation != null) {
				$tree->primary_location_id = $primaryLocation->id;
			}
			
			if ($settings != null){
				$tree->settings_id = $settings->id;
			}
			
			if ($overrideSettings != null){
				$tree->override_settings_id = $overrideSettings->id;
			}
			
			
			$tree->save();
			
			DB::commit();
			
			if ($user == null) {
				$error['error'] = "Invalid user ID."; 
				return Response::json($error, 400);
			}
			
			if ($tree == null) {
				$error['error'] = "Invalid tree ID.";
				return Response::json($error, 400);
			} else {
				$output['id'] = $tree->id;
				$output['priority'] = $tree->priority;
				$output['local_id'] = $input['local_id'];
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
		//
	}

}