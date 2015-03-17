<?php


class PendingUpdates extends Eloquent {

	public $timestamps = false;
	
	protected $table = 'pending_update';
	
	public function globalSettings()
	{
		return $this->hasOne('Setting', 'id');
	}

}