<?php


class TreeController extends \BaseController
{

    private function base64_to_jpeg($base64_string, $output_file)
    {
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

        $input = Input::json()->all();

        $output = [];

        try {
            DB::beginTransaction();

            //location
            $location = new Location;
            $location->lat = $input['lat'];
            $location->lon = $input['lon'];
            $location->gps_accuracy = $input['gps_accuracy'];
            $location->user_id = $input['user_id'];
            $location->save();

            Log::info("Location id:" . $location->id);
            //date
            $date = new DateTime();
            $timestamp = $input['timestamp'];
            $date->setTimestamp($timestamp);
            $date_string = $date->format('Y-m-d H:i:s');
            Log::info("Date:" . $date_string);
            //photo
            $photo = new Photo;
            $photo->location_id = $location->id;
            $photo->user_id = $input['user_id'];
            $photo->time_taken = $date_string;

            $photo->outdated = false;
            $photo->save();

            Log::info("Photo id:" . $photo->id);
            file_put_contents('images/' . $photo->id . '.jpg', base64_decode($input['base_64_image']));

            $thumb = new Imagick();
            $thumb->readImage('images/' . $photo->id . '.jpg');

            $thumb->resizeImage(320, 240, Imagick::FILTER_LANCZOS, 1, true);
            $thumb->writeImage('images/' . $photo->id . '_thumb.jpg');
            $thumb->clear();
            $thumb->destroy();


            //primary location
            $primaryLocation = new Location;
            $primaryLocation->lat = $input['lat'];
            $primaryLocation->lon = $input['lon'];
            $primaryLocation->gps_accuracy = $input['gps_accuracy'];
            $primaryLocation->user_id = $input['user_id'];
            $primaryLocation->save();

            Log::info("PrimaryLocation id:" . $primaryLocation->id);

						$tree = new Tree;
            $tree->time_created = $date_string;
            $tree->missing = false;

            $tree->time_updated = $date_string;
            $tree->cause_of_death_id = null;
            $tree->user_id = $input['user_id'];
            $tree->primary_location_id = $primaryLocation->id;
            $tree->save();

            $photoTree = new PhotoTree;
            $photoTree->photo_id = $photo->id;
            $photoTree->tree_id = $tree->id;
            $photoTree->save();

            $note = new Note();
            $note->content = $input['note'];
            $note->time_created = $date_string;
            $note->user_id = $input['user_id'];
            $note->save();

            $noteTree = new NoteTree;
            $noteTree->note_id = $note->id;
            $noteTree->tree_id = $tree->id;
            $noteTree->save();

            DB::commit();
            $output['status'] = $tree->id;
        } catch (Exception $e) {

            $output['status'] = $e->getMessage() . ' ' . $e->getTraceAsString();


        }

        return json_encode($output);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function updateTree()
    {

        //try {
            $input = Input::json()->all();

            DB::beginTransaction();

            $objTree = Tree::find(($input['id']));

            $objTree->dead = $input['dead'];
            $objTree->missing = $input['missing'];
            $objTree->priority = 0;
            Log::info("Time updated:" . $input['time_taken'] . " END");
            $date = new DateTime();
            $timestamp = $input['time_taken'];
            $date->setTimestamp($timestamp);
            $date_string = $date->format('Y-m-d H:i:s');
            $objTree->time_updated = $date_string;

            //Photos
            if (isset($input['base_64_image'])) {
                $photos = $objTree->photos;

                foreach ($photos as $photo) {
                    if (!$photo->outdated) {

                        $photo->outdated = 1;
                        $photo->save();
                    }
                }
            }

            $location=$objTree->primaryLocation;
            Log::info("Update location id:" .$location->id);
            //Store new photo

            $photo = new Photo;
            $photo->location_id = $location->id;
            $photo->user_id = $input['user_id'];
            $photo->time_taken = $date_string;

            $photo->outdated = false;
            $photo->save();

        $photoTree = new PhotoTree;
        $photoTree->photo_id = $photo->id;
        $photoTree->tree_id = $objTree->id;
        $photoTree->save();

            Log::info("Photo id:" . $photo->id);
            file_put_contents('images/' . $photo->id . '.jpg', base64_decode($input['base_64_image']));

            $thumb = new Imagick();
            $thumb->readImage('images/' . $photo->id . '.jpg');

            $thumb->resizeImage(320, 240, Imagick::FILTER_LANCZOS, 1, true);
            $thumb->writeImage('images/' . $photo->id . '_thumb.jpg');
            $thumb->clear();
            $thumb->destroy();

            //Add notes and image
            $output = [];

            $objTree->save();
            DB::commit();

            $output['status'] = 1;
        /*} catch (Exception $e) {

            $output['status'] = 0;


        }*/

        return json_encode($output);

    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function getUserTreesDetailed($id)
    {
        $trees = User::find($id)->trees;

        $output = array();

        foreach ($trees as $tree) {
            if (!$tree->missing && !$tree->dead) {
                $treeObj = [];
                $treeObj['id'] = $tree->id;
                $treeObj['created'] = $tree->time_created;
                $treeObj['updated'] = $tree->time_updated;
                $treeObj['priority'] = $tree->priority;

                $location = $tree->primaryLocation;
                $treeObj['lat'] = $location->lat;
                $treeObj['lng'] = $location->lon;

                //$settings = $tree->settings;
               	// $treeObj['gps'] = $settings->min_gps_accuracy;
                //$treeObj['next_update'] = $settings->next_update;

                $photos = $tree->photos;
                $url = "";
                foreach ($photos as $photo) {
                    if (!$photo->outdated) {
                        $url = URL::to('images/' . $photo->id . '_thumb.jpg');

                    }
                }


                $treeObj['imageUrl'] = $url;
                array_push($output, $treeObj);
            }
        }

        return json_encode($output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function getUserTrees($id)
    {
        $trees = User::find($id)->trees;

        $output = array();

        foreach ($trees as $tree) {
            if (!$tree->missing) {
                $output[] = $tree->id;
            }
        }

        return json_encode($output);
    }

    public function showTrees()
    {
        $trees = Tree::all();

        foreach ($trees as $tree) {
            $photos = $tree->photos;
            $notes = $tree->notes;
            $location = $tree->primaryLocation;

            $mapsUrl = '<a href="http://maps.google.com/maps?q=' . $location->lat . ',' . $location->lon . '">Location link</a>';


            $url = "";
            foreach ($photos as $photo) {
                if (!$photo->outdated) {
                    $url = URL::to('images/' . $photo->id . '_thumb.jpg');

                }
            }

            $treeNotes = array();
            foreach ($notes as $note) {
                array_push($treeNotes, array('content' => $note->content, 'time_created' => $note->time_created));
            }


            echo '<div style="border:1px solid green;">';
            echo '<img style="border:5px solid white;" src="' . $url . '"/>';

            if (!empty($treeNotes)) {
                foreach ($treeNotes as $treeNote) {
                    echo '<div >' . $treeNote['content'] . '</div>';
                    echo '<div >' . $treeNote['time_created'] . '</div>';
                }


            }
            echo $mapsUrl;
            echo '</div>';


        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
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

            DB::beginTransaction();

            if (!empty($input['photo'])) {
                //location
                $location = new Location;
                $location->lat = $input['photo']['location']['lat'];
                $location->lon = $input['photo']['location']['lon'];
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

                $photo->outdated = $input['photo']['is_outdated'] == 'N' ? false : true;
                $photo->save();

                file_put_contents('images/' . $photo->id . '.jpg', base64_decode($input['photo']['base64_image']));

                $thumb = new Imagick();
                $thumb->readImage('images/' . $photo->id . '.jpg');

                $thumb->resizeImage(320, 240, Imagick::FILTER_LANCZOS, 1, true);
                $thumb->writeImage('images/' . $photo->id . '_thumb.jpg');
                $thumb->clear();
                $thumb->destroy();
            }


            //primary location
            $primaryLocation = new Location;
            $primaryLocation->lat = $input['primary_location']['lat'];
            $primaryLocation->lon = $input['primary_location']['lon'];
            $primaryLocation->gps_accuracy = $input['primary_location']['gps_accuracy'];
            $primaryLocation->user_id = $input['user_id'];
            $primaryLocation->save();

            $noteIds = array();//save note id's in order to populate note_trees table later when we acquire tree id
            foreach ($input['notes'] as $currNote) {
                if (!empty($currNote)) {
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
            $tree->missing = $input['is_missing'] == 'N' ? false : true;

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

            DB::commit();

// 			echo "<img alt=\"ic_launcher.jpg\"  src=\"data:image/jpg;base64,". $input['photo']['base64_image'] ." \" />";die;


        } else {
            //update
// 			var_dump('update');	

            DB::beginTransaction();

            if (!empty($input['photo'])) {
                //location
                $location = new Location;
                $location->lat = $input['photo']['location']['lat'];
                $location->lon = $input['photo']['location']['lon'];
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

                $photo->outdated = $input['photo']['is_outdated'] == 'N' ? false : true;
                $photo->save();


                if (!file_put_contents('images/' . $photo->id . '.jpg', base64_decode($input['photo']['base64_image']))) {
                    var_dump("NOT SAVED");
                    die;
                }


                $thumb = new Imagick();
                $thumb->readImage('images/' . $photo->id . '.jpg');
                $thumb->resizeImage(320, 240, Imagick::FILTER_LANCZOS, 1, true);
                $thumb->writeImage('images/' . $photo->id . '_thumb.jpg');
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


            $user = User::find(isset($input['user_id']) ? $input['user_id'] : -1);
            $tree = Tree::find(isset($input['id']) ? $input['id'] : -1);
            $primaryLocation = Location::find(isset($input['primary_location']['id']) ? $input['primary_location']['id'] : -1);
            $settings = Setting::find(isset($input['settings']['id']) ? $input['settings']['id'] : -1);
            $overrideSettings = Setting::find(isset($input['settings_override']['id']) ? $input['settings_override']['id'] : -1);

            if ($tree == null) {
                $error['error'] = "Invalid tree ID.";
                return Response::json($error, 400);
            }

            $photos = $tree->photos;
            foreach ($photos as $currPhoto) {
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
                if (!empty($currNote)) {
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

            foreach ($noteIds as $noteId) {
                $noteTree = new NoteTree;
                $noteTree->note_id = $noteId;
                $noteTree->tree_id = $tree->id;
                $noteTree->save();
            }

            $tree->missing = $input['is_missing'] == 'N' ? false : true;

            if (isset($input['time_updated'])) {
                $date = new DateTime($input['time_updated']);
                $tree->time_updated = $date->format('Y-m-d H:i:s');
            }

            $tree->cause_of_death_id = !isset($input['cause_of_death_id']) ? null : $input['cause_of_death_id'];
            $tree->user_id = $input['user_id'];

            if ($primaryLocation != null) {
                $tree->primary_location_id = $primaryLocation->id;
            }

            if ($settings != null) {
                $tree->settings_id = $settings->id;
            }

            if ($overrideSettings != null) {
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
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

}
