<?php

class Tree extends Eloquent {

	public $timestamps = false;
	
	public function notes()
	{
		return $this->belongsToMany('Note', 'note_trees');
	}
	
	public function photos()
	{
		return $this->belongsToMany('Photo', 'photo_trees');
	}
	
	public function users()
	{
		return $this->hasOne('User', 'id');
	}

	public function primaryLocation()
	{
		return $this->hasOne('Location', 'id', 'primary_location_id');
	}
	
	public function settings()
	{
		return $this->hasOne('Setting', 'id', 'settings_id');
	}
	
	public function overrideSettings()
	{
		return $this->hasOne('Setting', 'id', 'override_settings_id');
	}
}