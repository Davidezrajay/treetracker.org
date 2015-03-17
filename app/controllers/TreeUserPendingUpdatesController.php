<?php

class TreeUserPendingUpdatesController extends \BaseController {
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$output = array();
		$pendingUpdates = DB::table('pending_update')->where('user_id', '=', $id)->get();
		if ($pendingUpdates != null){
			
			foreach ($pendingUpdates as $pendingUpdate){

				$updates = array();
				
				//"update_type": "global_settings"
				if ($pendingUpdate->settings_id != null) {
					$update['update_type'] = 'global_settings';
					$update['id'] = $pendingUpdate->settings_id;
					$updates[] = $update;
					unset($update);
				}
				
				//"update_type": "tree"
				if ($pendingUpdate->tree_id != null) {
					$update['update_type'] = 'tree';
					$update['id'] = $pendingUpdate->tree_id;
					$updates[] = $update;
					unset($update);
				}
				
				//"update_type": "trees_in_area"
// 				if ($pendingUpdate->location_id != null) {
// 					$update['update_type'] = 'trees_in_area';
// 					$location = Location::find($pendingUpdate->location_id);
// 					$loc = $location->toArray();
// 					$update['lat'] = $loc['lat'];
// 					$update['long'] = $loc['lon'];
// 					$update['radius'] = $loc['gps_accuracy'];
				
					
// 					$updates[] = $update;
// 					unset($update);
// 				}
							
				$output[] = $updates;
			}
				

			return Response::json($output);
				
		} else {
			$error['error'] = "No pending updates";
			return Response::json($error, 400);
		}
		
		
		
	}


}